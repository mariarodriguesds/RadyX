<?php
// form_dose_mensal.php
session_start();
require "conn.php"; // $pdo

$id_funcionario = $_SESSION['funcionario_id'] ?? $_SESSION['id_funcionario'] ?? null;

if (!$id_funcionario) {
    header("Location: login.php");
    exit();
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nivel_radiacao_dossimetro = $_POST['nivel_radiacao_dossimetro'] ?? null;
    $data_dossimetro = $_POST['data_dossimetro'] ?? null;

    if (!$nivel_radiacao_dossimetro || !$data_dossimetro) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            $sql = "INSERT INTO dose_mensal
                (id_funcionario, nivel_radiacao_dossimetro, data_dossimetro)
                VALUES
                (:id_funcionario, :nivel_radiacao_dossimetro, :data_dossimetro)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_funcionario' => $id_funcionario,
                ':nivel_radiacao_dossimetro' => $nivel_radiacao_dossimetro,
                ':data_dossimetro' => $data_dossimetro
            ]);

            header("Location: dose-mensal.php");
            exit();

        } catch (Exception $e) {
            $erro = "Erro ao salvar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Dose Mensal — RadyX</title>
    <link rel="icon" href="imagens/logoradyx.png">
<link rel="stylesheet" href="styles.css">
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

<div style="max-width:900px;margin:2rem auto;padding:1rem;">
  <div class="content-box">

    <h2 style="color:#7c3aed;">Registrar Dose Mensal</h2>

    <?php if (!empty($erro)): ?>
        <div style="color:#ef4444; margin:0.75rem 0;"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST">

      <div class="form-row">
        <div>
          <label class="label">Nível de radiação (dosímetro)</label>
          <input required type="number" step="0.001" name="nivel_radiacao_dossimetro">
        </div>

        <div>
          <label class="label">Data da leitura</label>
          <input required type="date" name="data_dossimetro">
        </div>
      </div>

      <div style="margin-top:1.25rem; display:flex; gap:1rem; justify-content:flex-end;">
        <a href="dose-mensal.php" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">Salvar Registro</button>
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
