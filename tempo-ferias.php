<?php
// ferias.php
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

// 1) Férias por mês (últimos 6 meses)
$meses_labels = [];
$ferias_mes = [];

if ($id_funcionario) {
    $sql = "
        SELECT DATE_FORMAT(data_inicio, '%Y-%m') AS mes,
               COUNT(*) AS qtd
        FROM ferias
        WHERE id_funcionario = :id
        GROUP BY mes
        ORDER BY mes DESC
        LIMIT 6
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id'=>$id_funcionario]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_reverse($rows);

    foreach ($rows as $r) {
        $meses_labels[] = date("M/Y", strtotime($r['mes'] . "-01"));
        $ferias_mes[] = (int)$r['qtd'];
    }
}

if (empty($meses_labels)) {
    $today = new DateTime();
    for ($i=4; $i>=0; $i--) {
        $m = (clone $today)->modify("-{$i} months");
        $meses_labels[] = $m->format('M/Y');
        $ferias_mes[] = 0;
    }
}

// 2) Gráfico diário últimos 30 dias
$labels_dias = [];
$ferias_dia = [];

if ($id_funcionario) {
    $sql = "
        SELECT DATE(data_inicio) AS dia,
               COUNT(*) AS qtd
        FROM ferias
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
        $ferias_dia[] = (int)$r['qtd'];
    }
}

if (empty($labels_dias)) {
    $today = new DateTime();
    for ($i=6; $i>=0; $i--) {
        $d = (clone $today)->modify("-{$i} days");
        $labels_dias[] = $d->format('d/m');
        $ferias_dia[] = 0;
    }
}

// 3) Comparação com empresa
$comparacao_labels = $meses_labels;
$voce_mes = $ferias_mes;
$empresa_mes = [];

if ($id_empresa) {
    foreach ($comparacao_labels as $lab) {
        $dt = DateTime::createFromFormat('M/Y', $lab);
        $mesAno = $dt ? $dt->format('Y-m') : date('Y-m');

        $sql = "
            SELECT AVG(qtd) AS media FROM (
                SELECT f.id AS id_func,
                       COUNT(*) AS qtd
                FROM ferias fr
                INNER JOIN funcionario f ON f.id = fr.id_funcionario
                WHERE f.id_empresa = :id_empresa
                  AND DATE_FORMAT(fr.data_inicio,'%Y-%m') = :mes
                GROUP BY f.id
            ) sub
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_empresa'=>$id_empresa, ':mes'=>$mesAno]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $empresa_mes[] = $r && $r['media'] !== null ? (float)$r['media'] : 0;
    }
} else {
    $empresa_mes = array_fill(0, count($comparacao_labels), 0);
}

// tabela últimos 50 registros
$detalhes = [];
if ($id_funcionario) {
    $stmt = $pdo->prepare("SELECT * FROM ferias WHERE id_funcionario = :id ORDER BY data_inicio DESC LIMIT 50");
    $stmt->execute([':id'=>$id_funcionario]);
    $detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// JSON
$json_meses = json_encode($meses_labels, JSON_UNESCAPED_UNICODE);
$json_ferias_mes = json_encode($ferias_mes);
$json_labels_dias = json_encode($labels_dias);
$json_ferias_dia = json_encode($ferias_dia);
$json_empresa = json_encode($empresa_mes);
$json_voce = json_encode($voce_mes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Férias — RadyX</title>
    <link rel="icon" href="imagens/logoradyx.png">
<link rel="stylesheet" href="styles.css">

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
    <div class="detail-avatar">🏖️</div>
    <div class="detail-title">
      <h1>FÉRIAS</h1>
      <p style="color:#e6dbff; margin-top:6px;">Resumo dos seus registros de férias</p>
    </div>
  </div>


    <div class="detail-body">
      <p>Abaixo estão seus registros e visualizações — férias por mês e comparação com a média da sua empresa.</p>

      <div class="stats-row">
        <div class="stat-card">
          <h4>Férias (últimos meses)</h4>
          <div class="value"><?= array_sum($ferias_mes) ?></div>
          <div class="small">Quantidade total</div>
        </div>

        <div class="stat-card">
          <h4>Registros recentes</h4>
          <div class="value"><?= count($detalhes) ?></div>
          <div class="small">Últimos 50 registros</div>
        </div>

        <div class="stat-card">
          <h4>Último período</h4>
          <div class="value">
            <?php if(!empty($detalhes)):
                    echo htmlspecialchars($detalhes[0]['data_inicio']);
                else: echo '—';
                endif; ?>
          </div>
          <div class="small">Início</div>
        </div>
      </div>

    <div class="chart-card">
      <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">Férias por mês</h3>
      <canvas id="chartMes" style="max-height:300px;"></canvas>
    </div>


    <div class="chart-card">
      <h3 style="margin:0 0 .5rem 0; color:var(--lilas);">Comparação: você vs empresa</h3>
      <canvas id="chartComp" style="max-height:360px;"></canvas>
    </div>

    <div class="table-wrap">
      <h3 style="color:var(--lilas); margin-bottom:.6rem;">Registros recentes</h3>
      <table class="report-table">
        <thead>
          <tr>
            <th>Data início</th>
            <th>Data término</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($detalhes)): ?>
            <tr><td colspan="2">Nenhum registro encontrado.</td></tr>
          <?php else: foreach($detalhes as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['data_inicio']) ?></td>
              <td><?= htmlspecialchars($d['data_termino']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="edit-button" style="text-align:center; margin-top:2rem;">
      <a href="form-tempo-ferias.php" class="btn-primary" style="background-color:#7c3aed;color:white;padding:12px 28px;border-radius:12px;font-size:1.1rem;text-decoration:none;">
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
const feriasMes = <?= $json_ferias_mes ?>;
const dias = <?= $json_labels_dias ?>;
const feriasDia = <?= $json_ferias_dia ?>;
const voce = <?= $json_voce ?>;
const empresa = <?= $json_empresa ?>;

// mensal
new Chart(document.getElementById('chartMes'), {
  type:'bar',
  data:{ labels:meses, datasets:[{ label:'Férias', data:feriasMes, backgroundColor:'#7c3aed' }] },
  options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// diário
new Chart(document.getElementById('chartDia'), {
  type:'line',
  data:{ labels:dias, datasets:[{ label:'Registros', data:feriasDia, borderColor:'#10b981', tension:0.25 }] },
  options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// comparação empresa
new Chart(document.getElementById('chartComp'), {
  type:'bar',
  data:{
    labels:meses,
    datasets:[
      { label:'Você', data:voce, backgroundColor:'#7c3aed' },
      { label:'Média empresa', data:empresa, backgroundColor:'#10b981' }
    ]
  },
  options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>

</body>
</html>
