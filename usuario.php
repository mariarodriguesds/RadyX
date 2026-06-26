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

/* =========================
   DADOS DO FUNCIONÁRIO
========================= */

$sql = $pdo->prepare("
    SELECT * FROM funcionario 
    WHERE id = ?
");

$sql->execute([$id_funcionario]);

$func = $sql->fetch(PDO::FETCH_ASSOC);

/* =========================
   EXPOSIÇÕES
========================= */

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

/* =========================
   TOTAL HORAS
========================= */

$stmtHorasTotais = $pdo->prepare("
    SELECT 
        data_inicio,
        data_termino,
        hora_inicio,
        hora_termino
    FROM registro_exposicao
    WHERE id_funcionario = ?
      AND MONTH(data_inicio) = MONTH(CURRENT_DATE())
      AND YEAR(data_inicio) = YEAR(CURRENT_DATE())
");

$stmtHorasTotais->execute([$id_funcionario]);

$registrosHoras = $stmtHorasTotais->fetchAll(PDO::FETCH_ASSOC);

$totalHoras = 0;

$totalHoras = 0;

foreach ($registrosHoras as $r) {

    $inicio = strtotime(
        $r['data_inicio'] . ' ' . $r['hora_inicio']
    );

    $fim = strtotime(
        $r['data_termino'] . ' ' . $r['hora_termino']
    );

    if ($fim > $inicio) {
        $totalHoras += ($fim - $inicio) / 3600;
    }
}

/* =========================
   EXAMES
========================= */

$sqlExames = $pdo->prepare("
    SELECT *
    FROM exame_preventivo
    WHERE id_funcionario = ?
      AND MONTH(data_exame) = MONTH(CURRENT_DATE())
      AND YEAR(data_exame) = YEAR(CURRENT_DATE())
");

$sqlExames->execute([$id_funcionario]);

$exames = $sqlExames->fetchAll(PDO::FETCH_ASSOC);

$totalExames = count($exames);

/* =========================
   DOSE MENSAL
========================= */

$sqlDose = $pdo->prepare("
    SELECT 
        nivel_radiacao_dossimetro AS dose
    FROM dose_mensal
    WHERE id_funcionario = ?
    AND MONTH(data_dossimetro) = MONTH(CURRENT_DATE())
    AND YEAR(data_dossimetro) = YEAR(CURRENT_DATE())
");

$sqlDose->execute([$id_funcionario]);

$doses = $sqlDose->fetchAll(PDO::FETCH_ASSOC);

$totalDose = 0;

foreach ($doses as $dose) {

    $totalDose += (float)$dose['dose'];
}

/* =========================
   TELEFONE FORMATADO
========================= */

$telefone = preg_replace('/\D/', '', $func['telefone']);

if (strlen($telefone) == 11) {

    $telefoneFormatado = preg_replace(
        "/(\d{2})(\d{5})(\d{4})/",
        "($1) $2-$3",
        $telefone
    );

} else {

    $telefoneFormatado = $telefone;
}

/* =========================
   INICIAIS
========================= */

$partesNome = explode(' ', trim($func['nome']));

$primeiraInicial = strtoupper(
    substr($partesNome[0], 0, 1)
);

$ultimaInicial = strtoupper(
    substr($partesNome[count($partesNome) - 1], 0, 1)
);

$iniciais = $primeiraInicial . $ultimaInicial;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>RadyX - Perfil do Usuário</title>

    <link rel="icon" href="imagens/logoradyx.png">

    <link rel="stylesheet" href="styles.css">

</head>

<style>
    .stats-card table {
    margin: 0 auto;
    text-align: center;
}

.stats-card th,
.stats-card td {
    text-align: center;
    vertical-align: middle;
}

.stats-card h2 {
    text-align: center;
}
.stats-card {
    display: flex;
    flex-direction: column;
    align-items: center;
}
</style>

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

    <!-- CONTEÚDO -->

    <div class="user-container">

        <h1 style="
            text-align: center;
            color: #7c3aed;
            padding: 2rem 0;
            font-size: 2.5rem;
        ">

            Perfil do Usuário

        </h1>

        <!-- PERFIL -->

        <div class="user-profile-grid">

            <div class="user-profile-card">

                <div class="user-avatar">
                    <?= $iniciais ?>
                </div>

                <h2 style="
                    text-align:center;
                    margin:1rem 0;
                ">

                    <?= htmlspecialchars($func['nome']) ?>

                </h2>

                <p style="
                    text-align:center;
                    color:#9ca3af;
                    margin-bottom:2rem;
                ">

                    <?= htmlspecialchars($func['profissao']) ?>

                </p>

                <div class="user-info-item">
                    <span class="info-icon">✉</span>

                    <span>
                        <?= htmlspecialchars($func['email']) ?>
                    </span>
                </div>

                <div class="user-info-item">
                    <span class="info-icon">📞</span>

                    <span>
                        <?= $telefoneFormatado ?>
                    </span>
                </div>

                <div class="user-info-item">
                    <span class="info-icon">🛡️</span>

                    <span>
                        <?= htmlspecialchars($func['registroProfissional']) ?>
                    </span>
                </div>

            </div>

            <!-- INFORMAÇÕES -->

            <div class="user-details-card">

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:2rem;
                ">

                    <h2>Informações Pessoais</h2>

                </div>

                <div class="user-form-grid">

                    <div class="form-group">

                        <label>Nome Completo</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['nome']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Email</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['email']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Telefone</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($telefoneFormatado) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Data de Nascimento</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['data_nascimento']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Profissão</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['profissao']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Registro Profissional</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['registroProfissional']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Instituição</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['instituicao']) ?>
                        </span>

                    </div>

                    <div class="form-group">

                        <label>Tempo de Trabalho</label>

                        <span class="profile-value">
                            <?= htmlspecialchars($func['tempo']) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>

        <!-- ESTATÍSTICAS -->

        <div class="stats-card">

            <h2 style="margin-bottom: 2rem;">

                Estatísticas do Mês

            </h2>

            <div class="stats-grid">

                <div class="stat-item">

                    <h3>
                        <?= number_format($totalDose, 3) ?> mSv
                    </h3>

                    <p>Dose Mensal</p>

                </div>

                <div class="stat-item">

                    <h3>
                        <?= round($totalHoras, 1) ?> h
                    </h3>

                    <p>Horas Expostas</p>

                </div>

                <div class="stat-item">

                    <h3>
                        <?= $totalExames ?>
                    </h3>

                    <p>Exames Preventivos</p>

                </div>

            </div>

        </div>

        <br><br>

        <!-- HISTÓRICO -->

        <div class="table-wrap">

        <br>

            <center><h2 style="margin-bottom:2rem;">

                Histórico de Exposição

            </h2></center>

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

    </div>

    <!-- FOOTER -->

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

                    <li>
                        <a href="monitor.php">
                            Monitor de Radiação
                        </a>
                    </li>

                    <li><a href="usuario.php">Usuário</a></li>

                    <li><a href="contato.html">Contato</a></li>

                </ul>

            </div>

        </div>

        <div class="footer-bottom">

            <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>

            <a href="#"
               class="back-to-top"
               onclick="window.scrollTo(0,0)">

                Voltar ao topo

            </a>

        </div>

    </footer>
    
<script> 

    function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>

</body>
</html>
