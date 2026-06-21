<?php
// dose_mensal.php
session_start();
require "conn.php";

if (!isset($_SESSION['funcionario_id'])) {
    header("Location: login.php");
    exit();
}

$id_funcionario = $_SESSION['funcionario_id'] ?? null;

// buscar id_empresa
$id_empresa = null;
if ($id_funcionario) {
    $stmtE = $pdo->prepare("SELECT id_empresa FROM funcionario WHERE id = ?");
    $stmtE->execute([$id_funcionario]);
    $rowE = $stmtE->fetch(PDO::FETCH_ASSOC);
    $id_empresa = $rowE['id_empresa'] ?? null;
}

// 1) doses por mês
$meses_labels = [];
$doses_mes = [];

if ($id_funcionario) {
    $sql = "
        SELECT DATE_FORMAT(data_dossimetro, '%Y-%m') AS mes,
               SUM(nivel_radiacao_dossimetro) AS total
        FROM dose_mensal
        WHERE id_funcionario = :id
        GROUP BY mes
        ORDER BY mes DESC
        LIMIT 6
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id_funcionario]);
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    foreach ($rows as $r) {
        $meses_labels[] = date("M/Y", strtotime($r['mes'] . "-01"));
        $doses_mes[] = (float)$r['total'];
    }
}

if (empty($meses_labels)) {
    $today = new DateTime();
    for ($i=4; $i>=0; $i--) {
        $m = (clone $today)->modify("-{$i} months");
        $meses_labels[] = $m->format('M/Y');
        $doses_mes[] = 0;
    }
}

// 2) gráfico diário
$labels_dias = [];
$doses_dia = [];

if ($id_funcionario) {
    $sql = "
        SELECT DATE(data_dossimetro) AS dia,
               SUM(nivel_radiacao_dossimetro) AS total
        FROM dose_mensal
        WHERE id_funcionario = :id
        GROUP BY dia
        ORDER BY dia DESC
        LIMIT 30
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id_funcionario]);
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    foreach ($rows as $r) {
        $labels_dias[] = date("d/m", strtotime($r['dia']));
        $doses_dia[] = (float)$r['total'];
    }
}

if (empty($labels_dias)) {
    $today = new DateTime();
    for ($i=6; $i>=0; $i--) {
        $d = (clone $today)->modify("-{$i} days");
        $labels_dias[] = $d->format('d/m');
        $doses_dia[] = 0;
    }
}

// 3) comparação empresa
$comparacao_labels = $meses_labels;
$voce_mes = $doses_mes;
$empresa_mes = [];

if ($id_empresa) {
    foreach ($comparacao_labels as $lab) {
        $dt = DateTime::createFromFormat('M/Y', $lab);
        $mesAno = $dt ? $dt->format('Y-m') : date('Y-m');

        $sql = "
            SELECT AVG(total) AS media FROM (
                SELECT f.id AS id_func,
                       SUM(dm.nivel_radiacao_dossimetro) AS total
                FROM dose_mensal dm
                INNER JOIN funcionario f ON f.id = dm.id_funcionario
                WHERE f.id_empresa = :id_empresa
                  AND DATE_FORMAT(dm.data_dossimetro,'%Y-%m') = :mes
                GROUP BY f.id
            ) sub
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_empresa'=>$id_empresa,
            ':mes'=>$mesAno
        ]);

        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $empresa_mes[] = $r && $r['media'] !== null ? (float)$r['media'] : 0;
    }
} else {
    $empresa_mes = array_fill(0, count($comparacao_labels), 0);
}

// tabela últimos 50 registros
$detalhes = [];
if ($id_funcionario) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM dose_mensal
        WHERE id_funcionario = :id
        ORDER BY data_dossimetro DESC
        LIMIT 50
    ");
    $stmt->execute([':id'=>$id_funcionario]);
    $detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$json_meses = json_encode($meses_labels, JSON_UNESCAPED_UNICODE);
$json_doses_mes = json_encode($doses_mes);
$json_labels_dias = json_encode($labels_dias);
$json_doses_dia = json_encode($doses_dia);
$json_empresa = json_encode($empresa_mes);
$json_voce = json_encode($voce_mes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dose Mensal — RadyX</title>

    <link rel="icon" href="imagens/logoradyx.png">
<link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<div class="detail-page-container">
  <a class="back-button" href="monitor.php">← Voltar</a>

  <div class="detail-header">
    <div class="detail-avatar">📟</div>
    <div class="detail-title">
      <h1>DOSE MENSAL</h1>
      <p style="color:#e6dbff; margin-top:6px;">Resumo das doses registradas</p>
    </div>
  </div>

  <div class="detail-body">
    <p>Abaixo estão suas visualizações mensais, diárias e comparação com sua empresa.</p>

    <div class="stats-row">

      <div class="stat-card">
        <h4>Dose total (últimos meses)</h4>
        <div class="value"><?= array_sum($doses_mes) ?></div>
        <div class="small">Somatório</div>
      </div>

      <div class="stat-card">
        <h4>Registros</h4>
        <div class="value"><?= count($detalhes) ?></div>
        <div class="small">Últimos 50</div>
      </div>

      <div class="stat-card">
        <h4>Última leitura</h4>
        <div class="value">
          <?php 
          if (!empty($detalhes)) echo htmlspecialchars($detalhes[0]['data_dossimetro']);
          else echo "—";
          ?>
        </div>
        <div class="small">Data</div>
      </div>

    </div>

    <div class="chart-card">
      <h3 style="color:#7c3aed;margin-bottom:.5rem;">Doses por mês</h3>
      <canvas id="chartMes" style="max-height:300px;"></canvas>
    </div>

    <div class="chart-card">
      <h3 style="color:#7c3aed;margin-bottom:.5rem;">Doses diárias</h3>
      <canvas id="chartDia" style="max-height:300px;"></canvas>
    </div>

    <div class="chart-card">
      <h3 style="color:#7c3aed;margin-bottom:.5rem;">Comparação: você vs empresa</h3>
      <canvas id="chartComp" style="max-height:360px;"></canvas>
    </div>

    <div class="table-wrap">
      <h3 style="color:#7c3aed;margin-bottom:.5rem;">Registros recentes</h3>

      <table class="report-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Nível (mSv)</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($detalhes)): ?>
              <tr><td colspan="2">Nenhum registro encontrado.</td></tr>
          <?php else: foreach ($detalhes as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['data_dossimetro']) ?></td>
                <td><?= htmlspecialchars($d['nivel_radiacao_dossimetro']) ?></td>
              </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    </div>

    <div class="edit-button" style="text-align:center; margin-top:2rem;">
      <a href="form-dose-mensal.php" class="btn-primary" style="background-color:#7c3aed;color:white;padding:12px 28px;border-radius:12px;font-size:1.1rem;text-decoration:none;">
        Novo Registro
      </a>
    </div>

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
const meses = <?= $json_meses ?>;
const dosesMes = <?= $json_doses_mes ?>;
const dias = <?= $json_labels_dias ?>;
const dosesDia = <?= $json_doses_dia ?>;
const voce = <?= $json_voce ?>;
const empresa = <?= $json_empresa ?>;

new Chart(document.getElementById('chartMes'), {
  type:'bar',
  data:{ labels:meses, datasets:[{ label:'Doses', data:dosesMes, backgroundColor:'#7c3aed' }] },
  options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('chartDia'), {
  type:'line',
  data:{ labels:dias, datasets:[{ label:'Doses', data:dosesDia, borderColor:'#10b981', tension:0.25 }] },
  options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('chartComp'), {
  type:'bar',
  data:{
    labels:meses,
    datasets:[
      { label:'Você', data:voce, backgroundColor:'#7c3aed' },
      { label:'Empresa', data:empresa, backgroundColor:'#10b981' }
    ]
  },
  options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>

</body>
</html>
