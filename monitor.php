<?php

session_start();
require "conn.php";

$funcionario_id = $_SESSION['funcionario_id'];

$sqlExp = $pdo->prepare("
    SELECT *
    FROM registro_exposicao
    WHERE id_funcionario = ?
");

$sqlExp->execute([$funcionario_id]);

$exposicoes = $sqlExp->fetchAll(PDO::FETCH_ASSOC);

$totalHoras = 0;

foreach ($exposicoes as $exp) {

    $inicio = strtotime($exp['data_inicio'] . ' ' . $exp['hora_inicio']);

    $fim = strtotime($exp['data_termino'] . ' ' . $exp['hora_termino']);

    if ($fim > $inicio) {

        $totalHoras += ($fim - $inicio) / 3600;

    }
}

$sqlExames = $pdo->prepare("
    SELECT *
    FROM exame_preventivo
    WHERE id_funcionario = ?
");

$sqlExames->execute([$funcionario_id]);

$totalExames = $sqlExames->rowCount();

$sqlDose = $pdo->prepare("
    SELECT nivel_radiacao_dossimetro
    FROM dose_mensal
    WHERE id_funcionario = ?
");

$sqlDose->execute([$funcionario_id]);

$doses = $sqlDose->fetchAll(PDO::FETCH_ASSOC);

$totalDose = 0;

foreach ($doses as $d) {

    $totalDose += $d['nivel_radiacao_dossimetro'];

}

$sqlFerias = $pdo->prepare("
    SELECT *
    FROM ferias
    WHERE id_funcionario = ?
");

$sqlFerias->execute([$funcionario_id]);

$ferias = $sqlFerias->fetchAll(PDO::FETCH_ASSOC);

$totalDiasFerias = 0;

foreach ($ferias as $f) {

    $inicio = strtotime($f['data_inicio']);

    $fim = strtotime($f['data_termino']);

    if ($fim > $inicio) {

        $dias = ($fim - $inicio) / 86400;

        $totalDiasFerias += $dias;

    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RadyX - Monitor de Radiação</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->

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

    <!-- Monitor Content -->
    <div class="monitor-container">
        <h1 style="text-align: center; color: #7c3aed; padding: 2rem 0; font-size: 2.5rem;">Monitor de Radiação</h1>
        
        <div class="monitor-grid">
            <div class="monitor-card">
                <h2>Registro de Exposição</h2>
                <p>Foram registrados <strong><?= round($totalHoras,1) ?> horas</strong> de exposição neste mês.</p>
                <a href="registro-exposicao.php" class="btn-secondary" style="margin-top: 1rem; display: inline-block;">Clique aqui para mais informações</a>
            </div>
            
            <div class="monitor-card">
                <h2>Tempo de Férias</h2>
                <p>Você teve <strong><?= round($totalDiasFerias) ?> dias</strong> de férias registrados.</p>
                <a href="tempo-ferias.php" class="btn-secondary" style="margin-top: 1rem; display: inline-block;">Clique aqui para mais informações</a>
            </div>

            <div class="monitor-card">
                <h2>Exames Preventivos</h2>
                <p>Este mês foram realizados <strong><?= $totalExames ?></strong> exames preventivos.</p>
                <a href="exames-preventivos.php" class="btn-secondary" style="margin-top: 1rem; display: inline-block;">Clique aqui para mais informações</a>
            </div>

            <div class="monitor-card">
                <h2>Nível do Dosímetro</h2>
                <p>Seu nível registrado foi de <strong><?= number_format($totalDose,2) ?> mSv</strong>.</p>
                <a href="dose-mensal.php" class="btn-secondary" style="margin-top: 1rem; display: inline-block;">Clique aqui para mais informações</a>
            </div>
        </div>

        <div class="reports-section">
            <h2 style="text-align: center; color: #7c3aed; font-size: 2rem; margin: 3rem 0 2rem 0;">Relatórios</h2>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="relatorio-mensal.php" class="btn-primary">Relatório Mensal</a>
                <a href="relatorio-semestral.php" class="btn-primary">Relatório Semestral</a>
                <a href="relatorio-anual.php" class="btn-primary">Relatório Anual</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>RadyX</h3>
                <p>Sistema de monitoramento de exposição à radiação ionizante para profissionais de saúde.</p>
            </div>
            <div class="footer-section">
                <h3>Links Rápidos</h3>
                <ul class="footer-links">
                    <li><a href="home.html">Home</a></li>
                    <li><a href="monitor.php">Monitor de Radiação</a></li>
                    <li><a href="usuario.php">Usuário</a></li>
                    <li><a href="contato.html">Contato</a></li>
                </ul>
            </div>
            
        </div>
        <div class="footer-bottom">
            <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>
            <a href="#" class="back-to-top" onclick="window.scrollTo(0,0)">Voltar ao topo</a>
        </div>
    </footer>

<script> 

function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>
</body>
</html>