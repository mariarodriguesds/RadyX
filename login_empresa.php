<?php
session_start();
include_once('conn.php');

if (isset($_POST['email_empresa'], $_POST['senha'])) {

    $email = mysqli_real_escape_string($conexao, $_POST['email_empresa']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    // Consulta ao banco
    $sql = "SELECT * FROM empresa WHERE email_empresa = '$email' LIMIT 1";
    $result = mysqli_query($conexao, $sql);

    if(mysqli_num_rows($result) > 0){
        $empresa = mysqli_fetch_assoc($result);

        // Como seu cadastro não usa hash, a comparação é diretaa
        if ($empresa['senha'] === $senha) {

            // Criar sessão
            $_SESSION['empresa_id'] = $empresa['id'];
            $_SESSION['empresa_nome'] = $empresa['nome_empresa'];

            // REDIRECIONA PARA A PÁGINA DE FUNCIONÁRIOS
            header("Location: funcionarios.html");
            exit;
        } 
        else {
            echo "<p style='color:red; text-align:center;'>Senha incorreta!</p>";
        }
    } 
    else {
        echo "<p style='color:red; text-align:center;'>Email não encontrado!</p>";
    }
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
    <!-- Navigation -->
    <nav>
        <a href="index.html" class="logo">RadyX</a>
        <div>
            <a href="login.html" class="btn-secondary" style="margin-right: 1rem;">Login</a>
            <a href="cadastro.html" class="btn-primary">Cadastro</a>
        </div>
    </nav>

    <!-- Login Content -->
    <div class="login-container">
        <div class="login-box">
            <h1>Login</h1>
            <p>Entre na sua conta</p>

            <!-- FORM CORRIGIDO -->
            <form action="login_empresa.php" method="POST">

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email_empresa" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" placeholder="Sua senha" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">Login</button>

                <div class="login-link">
                    Não tem conta? <a href="cadastro.html">Cadastre-se aqui</a>
                </div>

            </form>
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
                <h3>Redes Sociais</h3>
                <div class="social-links">
                    <a href="#" style="color: white;">f</a>
                    <a href="#" style="color: white;">📷</a>
                    <a href="#" style="color: white;">in</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>
            <a href="#" class="back-to-top" onclick="window.scrollTo(0,0)">Voltar ao topo</a>
        </div>
    </footer>
</body>
</html>
