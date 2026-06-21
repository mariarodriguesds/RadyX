<?php
session_start();
require "conn.php";

if (!isset($_SESSION['funcionario_id'])) {
    header("Location: login.php");
    exit();
}

$id_funcionario = $_SESSION['funcionario_id'] ?? null;

// buscar empresa
$id_empresa = null;

if ($id_funcionario) {

    $stmtE = $pdo->prepare("
        SELECT id_empresa
        FROM funcionario
        WHERE id = ?
    ");

    $stmtE->execute([$id_funcionario]);

    $rowE = $stmtE->fetch(PDO::FETCH_ASSOC);

    $id_empresa = $rowE['id_empresa'] ?? null;
}


// ===========================
// HORAS POR MÊS
// ===========================

$meses_labels = [];
$horas_mes = [];

$sql = "
SELECT
    DATE_FORMAT(data_inicio, '%Y-%m') AS mes,

    SUM(
        TIMESTAMPDIFF(
            MINUTE,
            CONCAT(data_inicio,' ',hora_inicio),
            CONCAT(data_termino,' ',hora_termino)
        )
    ) / 60 AS horas

FROM registro_exposicao

WHERE id_funcionario = :id

GROUP BY mes
ORDER BY mes DESC
LIMIT 6
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id_funcionario
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rows = array_reverse($rows);

foreach ($rows as $r) {

    $meses_labels[] = date("M/Y", strtotime($r['mes']."-01"));

    $horas_mes[] = (float)$r['horas'];
}

if (empty($meses_labels)) {

    $today = new DateTime();

    for ($i=4; $i>=0; $i--) {

        $m = (clone $today)->modify("-{$i} months");

        $meses_labels[] = $m->format('M/Y');

        $horas_mes[] = 0;
    }
}


// ===========================
// DISTÂNCIA MÉDIA
// ===========================

$distancia_mes = [];

$sql = "
SELECT
    DATE_FORMAT(data_inicio, '%Y-%m') AS mes,
    AVG(distancia) AS media

FROM registro_exposicao

WHERE id_funcionario = :id

GROUP BY mes
ORDER BY mes DESC
LIMIT 6
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id_funcionario
]);

$rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($rows as $r) {

    $distancia_mes[] = (float)$r['media'];
}


// ===========================
// BARREIRAS
// ===========================

$barreira_mes = [];

$sql = "
SELECT
    DATE_FORMAT(data_inicio, '%Y-%m') AS mes,
    SUM(barreira) AS total

FROM registro_exposicao

WHERE id_funcionario = :id

GROUP BY mes
ORDER BY mes DESC
LIMIT 6
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id_funcionario
]);

$rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($rows as $r) {

    $barreira_mes[] = (int)$r['total'];
}


// ===========================
// PAUSAS
// ===========================

$pausas_mes = [];

$sql = "
SELECT
    DATE_FORMAT(data_inicio, '%Y-%m') AS mes,
    SUM(quantas_pausas) AS total

FROM registro_exposicao

WHERE id_funcionario = :id

GROUP BY mes
ORDER BY mes DESC
LIMIT 6
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id_funcionario
]);

$rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($rows as $r) {

    $pausas_mes[] = (int)$r['total'];
}


// ===========================
// COMPARAÇÃO EMPRESA
// ===========================

$empresa_mes = [];

foreach ($meses_labels as $lab) {

    $dt = DateTime::createFromFormat('M/Y', $lab);

    $mesAno = $dt ? $dt->format('Y-m') : date('Y-m');

    $sql = "
        SELECT AVG(horas) AS media FROM (

            SELECT
                r.id_funcionario,

                SUM(
                    TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(r.data_inicio,' ',r.hora_inicio),
                        CONCAT(r.data_termino,' ',r.hora_termino)
                    )
                ) / 60 AS horas

            FROM registro_exposicao r

            INNER JOIN funcionario f
            ON f.id = r.id_funcionario

            WHERE f.id_empresa = :id_empresa
            AND DATE_FORMAT(r.data_inicio,'%Y-%m') = :mes

            GROUP BY r.id_funcionario

        ) sub
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id_empresa' => $id_empresa,
        ':mes' => $mesAno
    ]);

    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    $empresa_mes[] = $r && $r['media'] !== null
        ? (float)$r['media']
        : 0;
}


// ===========================
// TABELA
// ===========================

$detalhes = [];

$stmt = $pdo->prepare("
SELECT *
FROM registro_exposicao
WHERE id_funcionario = :id
ORDER BY data_inicio DESC
LIMIT 50
");

$stmt->execute([
    ':id' => $id_funcionario
]);

$detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// JSON

$json_meses = json_encode($meses_labels, JSON_UNESCAPED_UNICODE);

$json_horas = json_encode($horas_mes);

$json_distancia = json_encode($distancia_mes);

$json_barreira = json_encode($barreira_mes);

$json_pausas = json_encode($pausas_mes);

$json_empresa = json_encode($empresa_mes);

$json_voce = json_encode($horas_mes);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registro de Exposição — RadyX</title>
<link rel="icon" href="imagens/logoradyx.png">
<link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<nav>

    <a class="logo">RadyX</a>

    <button class="menu-toggle" onclick="toggleMenu()">☰</button>

    <ul class="nav-links" id="menu">
        <li><a href="home.html">Home</a></li>
        <li><a href="monitor.php">Monitor de radiação</a></li>
        <li><a href="usuario.php">Usuário</a></li>
        <li><a href="contato.html">Contato</a></li>
        <li class="logout-item">
            <a href="index.html" class="btn-secondary">Sair</a>
        </li>
    </ul>

    <div class="nav-btn">
        <a href="index.html" class="btn-secondary">Sair</a>
    </div>

</nav>

<div class="detail-page-container">

    <a class="back-button" href="monitor.php">
        ← Voltar
    </a>

    <div class="detail-header">

        <div class="detail-avatar">
            ☢️
        </div>

        <div class="detail-title">

            <h1>
                REGISTRO DE EXPOSIÇÃO
            </h1>

            <p style="color:#e6dbff; margin-top:6px;">
                Resumo dos seus registros de exposição
            </p>

        </div>

    </div>

    <div class="detail-body">

        <p>
            Abaixo estão seus registros e visualizações —
            horas de exposição, distância média, pausas,
            barreiras e comparação com sua empresa.
        </p>

        <div class="stats-row">

            <div class="stat-card">

                <h4>Total de Horas</h4>

                <div class="value">
                    <?= number_format(array_sum($horas_mes),1) ?>h
                </div>

                <div class="small">
                    Últimos meses
                </div>

            </div>

            <div class="stat-card">

                <h4>Distância Média (m)</h4>

                <div class="value">
                    <?= number_format(array_sum($distancia_mes)/max(count($distancia_mes),1),1) ?>
                </div>

                <div class="small">
                    Média registrada
                </div>

            </div>

            <div class="stat-card">

                <h4>Pausas</h4>

                <div class="value">
                    <?= array_sum($pausas_mes) ?>
                </div>

                <div class="small">
                    Total de pausas
                </div>

            </div>

            <div class="stat-card">

                <h4>Barreiras</h4>

                <div class="value">
                    <?= array_sum($barreira_mes) ?>
                </div>

                <div class="small">
                    Registros protegidos
                </div>

            </div>

        </div>


        <div class="chart-card">

            <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">
                Horas por mês
            </h3>

            <canvas id="chartHoras" style="max-height:320px;"></canvas>

        </div>


        <div class="chart-card">

            <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">
                Distância média por mês (m)
            </h3>

            <canvas id="chartDistancia" style="max-height:320px;"></canvas>

        </div>


        <div class="chart-card">

            <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">
                Pausas por mês
            </h3>

            <canvas id="chartPausas" style="max-height:320px;"></canvas>

        </div>


        <div class="chart-card">

            <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">
                Barreiras por mês
            </h3>

            <canvas id="chartBarreira" style="max-height:320px;"></canvas>

        </div>


        <div class="chart-card">

            <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">
                Comparação: você vs empresa (horas de exposição)
            </h3>

            <canvas id="chartComp" style="max-height:360px;"></canvas>

        </div>


        <div class="table-wrap">

            <h3 style="color:var(--lilas); margin-bottom:.6rem;">
                Registros recentes
            </h3>

            <table class="report-table">

                <thead>

                    <tr>

                        <th>Equipamento</th>

                        <th>Distância (m)</th>

                        <th>Início</th>

                        <th>Término</th>

                        <th>Barreira</th>

                        <th>Pausas</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if(empty($detalhes)): ?>

                        <tr>
                            <td colspan="6">
                                Nenhum registro encontrado.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach($detalhes as $d): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($d['equipamento_emissor']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['distancia']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['data_inicio']) ?>
                                    <?= htmlspecialchars($d['hora_inicio']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['data_termino']) ?>
                                    <?= htmlspecialchars($d['hora_termino']) ?>
                                </td>

                                <td>
                                    <?= $d['barreira']
                                        ? htmlspecialchars($d['qual_barreira'])
                                        : 'Não'
                                    ?>
                                </td>

                                <td>
                                    <?= (int)$d['quantas_pausas'] ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <div class="edit-button" style="text-align:center; margin-top:2rem;">

            <a
                href="form-registro-exposicao.php"
                class="btn-primary"
                style="background-color:#7c3aed;color:white;padding:12px 28px;border-radius:12px;font-size:1.1rem;text-decoration:none;"
            >
                Novo Registro
            </a>

        </div>

    </div>

</div>

<footer>

    <div class="footer-content">

        <div class="footer-section">

            <h3>RadyX</h3>

            <p>
                Sistema de monitoramento de exposição
                à radiação ionizante para profissionais
                de saúde.
            </p>

        </div>

        <div class="footer-section">

            <h3>Links Rápidos</h3>

            <ul class="footer-links">

                <li><a href="home.html">Home</a></li>

                <li><a href="monitor.php">
                    Monitor de Radiação
                </a></li>

                <li><a href="usuario.php">Usuário</a></li>

                <li><a href="contato.html">Contato</a></li>

            </ul>

        </div>

    </div>

    <div class="footer-bottom">

        <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>

        <a
            href="#"
            class="back-to-top"
            onclick="window.scrollTo(0,0)"
        >
            Voltar ao topo
        </a>

    </div>

</footer>


<script>

const meses = <?= $json_meses ?>;

const horas = <?= $json_horas ?>;

const distancia = <?= $json_distancia ?>;

const pausas = <?= $json_pausas ?>;

const barreira = <?= $json_barreira ?>;

const voce = <?= $json_voce ?>;

const empresa = <?= $json_empresa ?>;


// HORAS

new Chart(document.getElementById('chartHoras'), {

    type:'line',

    data:{
        labels:meses,

        datasets:[{
            label:'Horas',
            data:horas,
            borderColor:'#7c3aed',
            tension:0.25
        }]
    },

    options:{
        responsive:true,
        scales:{
            y:{ beginAtZero:true }
        }
    }

});


// DISTÂNCIA

new Chart(document.getElementById('chartDistancia'), {

    type:'line',

    data:{
        labels:meses,

        datasets:[{
            label:'Distância',
            data:distancia,
            borderColor:'#10b981',
            tension:0.25
        }]
    },

    options:{
        responsive:true,
        scales:{
            y:{ beginAtZero:true }
        }
    }

});


// PAUSAS

new Chart(document.getElementById('chartPausas'), {

    type:'bar',

    data:{
        labels:meses,

        datasets:[{
            label:'Pausas',
            data:pausas,
            backgroundColor:'#f59e0b'
        }]
    },

    options:{
        responsive:true,
        scales:{
            y:{ beginAtZero:true }
        }
    }

});


// BARREIRA

new Chart(document.getElementById('chartBarreira'), {

    type:'bar',

    data:{
        labels:meses,

        datasets:[{
            label:'Barreiras',
            data:barreira,
            backgroundColor:'#ef4444'
        }]
    },

    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }

});


// EMPRESA

new Chart(document.getElementById('chartComp'), {

    type:'bar',

    data:{

        labels:meses,

        datasets:[

            {
                label:'Você',
                data:voce,
                backgroundColor:'#7c3aed'
            },

            {
                label:'Empresa',
                data:empresa,
                backgroundColor:'#10b981'
            }

        ]
    },

    options:{
        responsive:true,
        scales:{
            y:{ beginAtZero:true }
        }
    }

});

function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>

</body>
</html>

