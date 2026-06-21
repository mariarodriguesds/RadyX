<?php
include_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome  = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    // verifica se as senhas batem
    if ($senha !== $confirmar) {
        echo "<script>alert('As senhas não coincidem!');</script>";
    } else {
        // verifica se o email já existe
        $check = $pdo->prepare("SELECT id FROM empresa WHERE email_empresa = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            echo "<script>alert('Este email já está cadastrado!');</script>";
        } else {
            // inserir empresa
            $insert = $pdo->prepare("
                INSERT INTO empresa (nome_empresa, email_empresa, senha)
                VALUES (?, ?, ?)
            ");

            if ($insert->execute([$nome, $email, $senha])) {
                echo "<script>
                        alert('Cadastro realizado com sucesso!');
                        window.location.href='login.php';
                      </script>";
                exit;
            } else {
                echo "<script>alert('Erro ao inserir no banco');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RadyX - Cadastro</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="index.html" class="logo">RadyX</a>
        <ul class="nav-links"></ul>
        <div>
            <a href="login.php" class="btn-secondary" style="margin-right: 1rem;">Login</a>
            <a href="cadastro.php" class="btn-primary">Cadastro</a>
        </div>
    </nav>

    <!-- Cadastro Content -->
    <div class="login-container">
        <div class="login-box">
            <h1>Cadastro</h1>
            <p>Crie sua conta</p>

            <!-- FORMULÁRIO FUNCIONANDO -->
            <form method="POST" action="cadastro.php">
                <div class="form-group">
                    <label>Nome da Instituição</label>
                    <input type="text" name="nome" placeholder="Nome" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" placeholder="Sua senha" required>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha</label>
                    <input type="password" name="confirmar_senha" placeholder="Confirme sua senha" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">Cadastrar</button>

                <div class="login-link">
                    Já tem conta?
                    <a href="login.php">Faça login aqui</a>
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
            
            
        </div>
        <div class="footer-bottom">
            <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>
            <a href="#" class="back-to-top" onclick="window.scrollTo(0,0)">Voltar ao topo</a>
        </div>
    </footer>
</body>
</html>
