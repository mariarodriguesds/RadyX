<?php
session_start();
require_once "conn.php";

// Verifica se empresa está logada
if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $data_nascimento = $_POST['data_nascimento'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $profissao = $_POST['profissao'];
    $registro = $_POST['registroProfissional'];
    $instituicao = $_POST['instituicao'];
    $tempo = $_POST['tempo'];
    $telefone = $_POST['telefone'];

    $sql = "INSERT INTO funcionario 
        (id_empresa, nome, cpf, data_nascimento, email, senha, profissao, registroProfissional, instituicao, tempo, telefone)
        VALUES 
        (:id_empresa, :nome, :cpf, :data_nascimento, :email, :senha, :profissao, :registroProfissional, :instituicao, :tempo, :telefone)";
    
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id_empresa', $empresa_id);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':data_nascimento', $data_nascimento);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);
    $stmt->bindParam(':profissao', $profissao);
    $stmt->bindParam(':registroProfissional', $registro);
    $stmt->bindParam(':instituicao', $instituicao);
    $stmt->bindParam(':tempo', $tempo);
    $stmt->bindParam(':telefone', $telefone);

    if ($stmt->execute()) {
        header("Location: funcionarios.php");
        exit;
    } else {
        echo "<script>alert('Erro ao cadastrar funcionário');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - RadyX</title>
    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">

    <style>
        /* TÍTULO */
        .cadastro-box h1 {
            font-size: 2.3rem;
            font-weight: 800;
            color: #7c3aed;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .cadastro-box p {
            color: #555;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* CONTAINER PRINCIPAL */
        .cadastro-container {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 2rem;
        }

        .cadastro-box {
            background: #fff;
            width: 90%;
            max-width: 1000px;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        /* SEÇÕES */
        .form-section {
            background: #fafafa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid #eee;
        }

        .form-section h2 {
            font-size: 1.4rem;
            color: #7c3aed;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        /* GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        /* GRUPO DE INPUT */
        .form-group label {
            font-weight: 600;
            color: #444;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            background: #fff;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 2px rgba(124,58,237,0.2);
        }

        /* BOTÃO */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            border-radius: 12px;
            font-weight: 700;
        }
    </style>
</head>

<body>
    
<nav>
    <a href="#" class="logo">RadyX</a>
    </ul>
    <div>
        <a href="index.html" class="btn-secondary" style="margin-right: 1rem;">Sair</a>
    </div>
</nav>

<div class="cadastro-container">
    <div class="cadastro-box">

        <h1>Cadastro</h1>
        <p>Crie um novo profissional no sistema</p>

        <form action="cadastro_funcionario.php" method="POST">

            <div class="sections-container">
                
                <!-- SEÇÃO 1 -->
                <div class="form-section">
                    <h2>Dados Pessoais e Cadastrais</h2>

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" required>
                        </div>

                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="cpf" required>
                        </div>

                        <div class="form-group">
                            <label>Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" required>
                        </div>

                        <div class="form-group">
                            <label>Telefone *</label>
                            <input type="tel" name="telefone" required>
                        </div>

                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label>Confirmar Email *</label>
                            <input type="email" name="email_confirm" required>
                        </div>

                        <div class="form-group">
                            <label>Senha *</label>
                            <input type="password" name="senha" required>
                        </div>

                        <div class="form-group">
                            <label>Confirmar Senha *</label>
                            <input type="password" name="senha_confirm" required>
                        </div>

                    </div>
                </div>

                <!-- SEÇÃO 2 -->
                <div class="form-section">
                    <h2>Dados Profissionais</h2>

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Profissão *</label>
                            <select name="profissao" required>
                                <option value="">Selecione...</option>
                                <option value="Técnico em Radiologia">Técnico em Radiologia</option>
                                <option value="Tecnólogo em Radiologia">Tecnólogo</option>
                                <option value="Médico Radiologista">Médico Radiologista</option>
                                <option value="Dentista">Dentista</option>
                                <option value="Enfermeiro(a)">Enfermeiro(a)</option>
                                <option value="Veterinario(a)">Veterinário(a)</option>
                                <option value="Físico Médico">Físico Médico</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Registro Profissional *</label>
                            <input type="text" name="registroProfissional" required>
                        </div>

                        <div class="form-group">
                            <label>Instituição *</label>
                            <input type="text" name="instituicao" required>
                        </div>

                        <div class="form-group">
                            <label>Tempo de Experiência *</label>
                            <select name="tempo" required>
                                <option value="">Selecione...</option>
                                <option value="0-1">Menos de 1 ano</option>
                                <option value="1-3">1 a 3 anos</option>
                                <option value="3-5">3 a 5 anos</option>
                                <option value="5-10">5 a 10 anos</option>
                                <option value="10+">Mais de 10 anos</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">Criar Conta</button>

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
