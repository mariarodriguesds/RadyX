<?php 
session_start();
require_once "conn.php"; // contém $pdo (PDO)

// Se o formulário foi enviado
if (isset($_POST['email'], $_POST['senha'])) {

    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    /*
     * 1️⃣ PRIMEIRO TENTA LOGAR COMO EMPRESA
     */
    $sqlEmpresa = "SELECT * FROM empresa WHERE email_empresa = :email LIMIT 1";
    $stmt = $pdo->prepare($sqlEmpresa);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {

        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empresa['senha'] === $senha) {

            $_SESSION['empresa_id'] = $empresa['id'];
            $_SESSION['empresa_nome'] = $empresa['nome_empresa'];

            header("Location: funcionarios.php");
            exit;
        }
    }

    /*
     * 2️⃣ NÃO É EMPRESA → TENTA FUNCIONÁRIO
     */
    $sqlFunc = "SELECT * FROM funcionario WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sqlFunc);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {

        $func = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($func['senha'] === $senha) {

            $_SESSION['funcionario_id'] = $func['id'];
            $_SESSION['funcionario_nome'] = $func['nome'];

            header("Location: home.html");
            exit;
        }
    }

    // Se chegar aqui → email ou senha inválidos
    $erro = "Email ou senha incorretos!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RadyX - Login</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav>
        <a href="index.html" class="logo">RadyX</a>
        <div>
            <a href="login.php" class="btn-secondary" style="margin-right: 1rem;">Login</a>
            <a href="cadastro.php" class="btn-primary">Cadastro</a>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-box">
            <h1>Login</h1>
            <p>Entre na sua conta</p>

            <?php if(isset($erro)): ?>
                <p style="color:red; text-align:center; margin-bottom:10px;">
                    <?= $erro ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">

    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
    </div>

    <div class="form-group">
        <label>Senha</label>
        <input type="password" name="senha" required>
    </div>

    <button type="submit" class="btn-primary" style="width: 100%;">Login</button>

    <div class="login-link">
        Não tem conta? <a href="cadastro.php">Cadastre-se aqui</a>
    </div>
</form>

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
