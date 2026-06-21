<?php
session_start();
include_once('conn.php');

if (isset($_POST['email_funcionario'], $_POST['senha'])) {

    $email = mysqli_real_escape_string($conexao, $_POST['email_funcionario']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    // Consulta o funcionário pelo email
    $sql = "SELECT * FROM funcionario WHERE email_funcionario = '$email' LIMIT 1";
    $result = mysqli_query($conexao, $sql);

    if(mysqli_num_rows($result) > 0){
        $func = mysqli_fetch_assoc($result);

        // Se a senha não estiver criptografada, comparação direta:
        if ($func['senha'] === $senha) {

            // Criar sessão
            $_SESSION['funcionario_id'] = $func['id'];
            $_SESSION['id_empresa'] = $func['id_empresa'];
            $_SESSION['nome_funcionario'] = $func['nome_funcionario'];

            header("Location: home.html");
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
    <title>RadyX - Login Funcionário</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav>
        <a href="index.html" class="logo">RadyX</a>
        <div>
            <a href="login_funcionario.html" class="btn-secondary" style="margin-right: 1rem;">Login</a>
            <a href="cadastro_funcionario.html" class="btn-primary">Cadastro</a>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-box">
            <h1>Login Funcionário</h1>
            <p>Entre na sua conta</p>

            <form action="login_funcionario.php" method="POST">

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email_funcionario" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" placeholder="Sua senha" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">Login</button>

                <div class="login-link">
                    Não tem conta? <a href="cadastro_funcionario.html">Cadastre-se aqui</a>
                </div>

            </form>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>RadyX</h3>
                <p>Sistema de monitoramento de exposição à radiação ionizante.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>
        </div>
    </footer>

</body>
</html>
