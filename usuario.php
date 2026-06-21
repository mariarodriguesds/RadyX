<?php
session_start();
require "conn.php";

// apenas funcionários podem acessar
if (!isset($_SESSION['funcionario_id'])) {
    header("Location: login.php");
    exit;
}

$funcionario_id = $_SESSION['funcionario_id'];

/* =========================
   DADOS DO FUNCIONÁRIO
========================= */

$sql = $pdo->prepare("
    SELECT * FROM funcionario 
    WHERE id = ?
");

$sql->execute([$funcionario_id]);

$func = $sql->fetch(PDO::FETCH_ASSOC);

/* =========================
   EXPOSIÇÕES
========================= */

$sqlExp = $pdo->prepare("
    SELECT 
        re.id,
        re.data_inicio,
        re.data_termino,
        re.hora_inicio,
        re.hora_termino,
        re.quantas_pausas,

        re.equipamento_emissor,
        re.distancia,
        re.barreira,
        re.qual_barreira,

        ee.nome_equipamento

    FROM registro_exposicao re

    LEFT JOIN equipamento_exposicao ee 
        ON ee.id_exposicao = re.id

    WHERE re.id_funcionario = ?

    ORDER BY re.data_inicio DESC
");

$sqlExp->execute([$funcionario_id]);

$exposicoes = $sqlExp->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   EQUIPAMENTOS
========================= */

$sqlEquipamentos = $pdo->prepare("
    SELECT *
    FROM equipamento_exposicao
    WHERE id_exposicao = ?
");

/* =========================
   PAUSAS
========================= */

$sqlPausas = $pdo->prepare("
    SELECT *
    FROM pausa_exposicao
    WHERE id_exposicao = ?
");

/* =========================
   TOTAL HORAS
========================= */

$totalHoras = 0;

foreach ($exposicoes as $exp) {

    $inicio = strtotime(
        $exp['data_inicio'] . ' ' . $exp['hora_inicio']
    );

    $fim = strtotime(
        $exp['data_termino'] . ' ' . $exp['hora_termino']
    );

    if ($fim > $inicio) {

        $diferencaSegundos = $fim - $inicio;

        $totalHoras += $diferencaSegundos / 3600;
    }
}

/* =========================
   EXAMES
========================= */

$sqlExames = $pdo->prepare("
    SELECT *
    FROM exame_preventivo 
    WHERE id_funcionario = ?
");

$sqlExames->execute([$funcionario_id]);

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

$sqlDose->execute([$funcionario_id]);

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

        <!-- HISTÓRICO -->

        <div class="stats-card" style="margin-top:2rem;">

            <h2 style="margin-bottom:2rem;">

                Histórico de Exposição

            </h2>

            <?php if ($exposicoes): ?>

                <div class="table-responsive2">

                    <table style="width:95%; margin:auto; border-collapse:collapse;">


                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Equipamento</th>
                                <th>Distância (m)</th>
                                <th>Barreira</th>
                                <th>Pausas</th>
                                <th>Duração (h)</th>
                            </tr>
                        </thead>

                        </thead>

                        <tbody>

                            <?php foreach ($exposicoes as $exp): ?>

                                <?php

                                    $sqlEquipamentos->execute([$exp['id']]);

                                    $equipamentos = $sqlEquipamentos->fetchAll(PDO::FETCH_ASSOC);

                                    $inicio = strtotime(
                                        $exp['data_inicio'] . ' ' . $exp['hora_inicio']
                                    );

                                    $fim = strtotime(
                                        $exp['data_termino'] . ' ' . $exp['hora_termino']
                                    );

                                    $duracao = 0;

                                    if ($fim > $inicio) {

                                        $duracao = round(
                                            ($fim - $inicio) / 3600,
                                            2
                                        );
                                    }

                                ?>

                                <tr>

                                    <td>
                                        <?= date('d/m/Y', strtotime($exp['data_inicio'])) ?>
                                    </td>

                                    <td>

                                        <?php

                                        if (!empty($exp['nome_equipamento'])) {

                                            echo htmlspecialchars($exp['nome_equipamento']);

                                        } elseif (!empty($exp['equipamento_emissor'])) {

                                            echo htmlspecialchars($exp['equipamento_emissor']);

                                        } else {

                                            echo "-";

                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?= $exp['distancia']
                                            ? htmlspecialchars($exp['distancia']) . ' m'
                                            : '-' ?>

                                    </td>

                                    <td>

                                        <?php

                                        if ($exp['barreira']) {

                                            echo !empty($exp['qual_barreira'])
                                                ? htmlspecialchars($exp['qual_barreira'])
                                                : 'Sim';

                                        } else {

                                            echo 'Não';

                                        }

                                        ?>

                                    </td>

                                    <td>
                                        <?= (int)$exp['quantas_pausas'] ?>
                                    </td>

                                    <td>
                                        <?= number_format($duracao, 2) ?> h
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>
                </div>

            <?php else: ?>

                <p>Nenhuma exposição registrada.</p>

            <?php endif; ?>

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