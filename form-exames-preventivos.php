<?php
// form_exame_preventivo.php
session_start();
require "conn.php"; // $pdo

$id_funcionario = $_SESSION['funcionario_id'] ?? $_SESSION['id_funcionario'] ?? null;

if (!$id_funcionario) {
    header("Location: login.php");
    exit();
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_exame = $_POST['tipo_exame'] ?? null;
    $data_exame = $_POST['data_exame'] ?? null;
    $resultado_exame = $_POST['resultado_exame'] ?? null;

    if (!$tipo_exame || !$data_exame || !$resultado_exame) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            $sql = "INSERT INTO exame_preventivo
                (id_funcionario, tipo_exame, data_exame, resultado_exame)
                VALUES
                (:id_funcionario, :tipo_exame, :data_exame, :resultado_exame)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_funcionario' => $id_funcionario,
                ':tipo_exame' => $tipo_exame,
                ':data_exame' => $data_exame,
                ':resultado_exame' => $resultado_exame
            ]);

            header("Location: exames-preventivos.php");
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registrar Exame Preventivo — RadyX</title>
    <link rel="icon" href="imagens/logoradyx.png">
<link rel="stylesheet" href="styles.css">
</head>

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
    <h2 style="color:#7c3aed;">Registrar Exame Preventivo</h2>

    <?php if (!empty($erro)): ?>
        <div style="color:#ef4444; margin:0.75rem 0;"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row">
        <div>
          <label class="label">Tipo de Exame</label>
          <input required type="text" name="tipo_exame" />
        </div>
        <div>
          <label class="label">Data do Exame</label>
          <input required type="date" name="data_exame" />
        </div>
      </div>

      <div>
        <label class="label">Resultado do Exame</label>
        <textarea required name="resultado_exame" rows="4"></textarea>
      </div>

      <div style="margin-top:1.25rem; display:flex; gap:1rem; justify-content:flex-end;">
        <a href="exames-preventivos.php" class="btn-secondary">Cancelar</a>
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
