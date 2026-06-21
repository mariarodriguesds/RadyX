<?php
session_start();
require_once "conn.php";

// apenas empresas podem acessar
if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

// Busca todos os funcionários da empresa logada
$stmt = $pdo->prepare("SELECT * FROM funcionario WHERE id_empresa = :id_empresa");
$stmt->bindParam(':id_empresa', $empresa_id);
$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// função para gerar iniciais
function iniciais($nome) {
    $partes = explode(" ", $nome);
    $iniciais = "";
    foreach ($partes as $p) {
        if (trim($p) !== "") {
            $iniciais .= strtoupper($p[0]);
        }
    }
    return substr($iniciais, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RadyX - Funcionários</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav>
    <a href="#" class="logo">RadyX</a>
    </ul>
    <div>
        <a href="index.html" class="btn-secondary" style="margin-right: 1rem;">Sair</a>
    </div>
</nav>

<div class="funcionarios-container">
    <h1 style="text-align: center; color: #7c3aed; padding: 2rem 0; font-size: 2.5rem;">Funcionários</h1>

    <div class="funcionarios-grid">

        <!-- Card de adicionar funcionário -->
        <div class="funcionario-card-add">
            <div class="add-icon">+</div>
            <h3>Adicionar Profissional</h3>
            <p>Cadastre um novo profissional no sistema</p>
            <a href="cadastro_funcionario.php" class="btn-primary" style="width: 100%; margin-top: 1rem;">Cadastrar</a>
        </div>

        <!-- LISTAGEM DINÂMICA -->
        <?php foreach ($funcionarios as $f): ?>
            <div class="funcionario-card">
                <div class="funcionario-avatar">
                    <?= iniciais($f['nome']); ?>
                </div>

                <h3><?= htmlspecialchars($f['nome']); ?></h3>

                <p class="funcionario-cargo">
                    <?= htmlspecialchars($f['profissao']); ?>
                </p>

                <div class="funcionario-info">
                    <p><strong>Registro:</strong> <?= htmlspecialchars($f['registroProfissional']); ?></p>
                    <p><strong>Instituição:</strong> <?= htmlspecialchars($f['instituicao']); ?></p>
                    <p><strong>Telefone:</strong> <?= htmlspecialchars($f['telefone']); ?></p>
                </div>

                <!-- AQUI ESTÁ O AJUSTE: PASSANDO O ID -->
                <a href="funcionario_infos.php?id=<?= $f['id'] ?>" 
                   class="btn-primary" 
                   style="width: 100%; margin-top: 1rem;">Ver Detalhes</a>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>RadyX</h3>
            <p>Sistema de monitoramento de exposição à radiação ionizante para profissionais de saúde.</p>
        </div>
        
        
    </div>
    <div class="footer-bottom">
        <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>
        <a href="#" class="back-to-top" onclick="window.scrollTo(0,0)">Voltar ao topo</a>
    </div>
</footer>

</body>
</html>
