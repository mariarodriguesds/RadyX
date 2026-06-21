<?php
session_start();
require "conn.php";

$id_funcionario = $_SESSION['funcionario_id'] ?? $_SESSION['id_funcionario'] ?? null;

if (!$id_funcionario) {
    header("Location: login.php");
    exit();
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $equipamento_emissor = $_POST['equipamento_emissor'] ?? null;

    $data_inicio = $_POST['data_inicio'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;

    $data_termino = $_POST['data_termino'] ?? null;
    $hora_termino = $_POST['hora_termino'] ?? null;

    $distancia = $_POST['distancia'] ?? null;

    $barreira = isset($_POST['barreira']) ? 1 : 0;
    $qual_barreira = $_POST['qual_barreira'] ?? '';

    $pausa = isset($_POST['pausa']) ? 1 : 0;
    $quantas_pausas = (int) ($_POST['quantas_pausas'] ?? 0);

    if (
        !$equipamento_emissor ||
        !$data_inicio ||
        !$hora_inicio ||
        !$data_termino ||
        !$hora_termino ||
        !$distancia
    ) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {

        try {

            $pdo->beginTransaction();

            $sql = "INSERT INTO registro_exposicao (
                id_funcionario,
                equipamento_emissor,

                data_inicio,
                hora_inicio,

                data_termino,
                hora_termino,

                distancia,

                barreira,
                qual_barreira,

                pausa,
                quantas_pausas

            ) VALUES (

                :id_funcionario,
                :equipamento_emissor,

                :data_inicio,
                :hora_inicio,

                :data_termino,
                :hora_termino,

                :distancia,

                :barreira,
                :qual_barreira,

                :pausa,
                :quantas_pausas
            )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id_funcionario' => $id_funcionario,
                ':equipamento_emissor' => $equipamento_emissor,

                ':data_inicio' => $data_inicio,
                ':hora_inicio' => $hora_inicio,

                ':data_termino' => $data_termino,
                ':hora_termino' => $hora_termino,

                ':distancia' => $distancia,

                ':barreira' => $barreira,
                ':qual_barreira' => $qual_barreira,

                ':pausa' => $pausa,
                ':quantas_pausas' => $quantas_pausas
            ]);

            $id_exposicao = $pdo->lastInsertId();

            if ($pausa && $quantas_pausas > 0) {

                $data_inicio_pausa_arr  = $_POST['data_inicio_pausa'] ?? [];
                $hora_inicio_pausa_arr  = $_POST['hora_inicio_pausa'] ?? [];

                $data_termino_pausa_arr = $_POST['data_termino_pausa'] ?? [];
                $hora_termino_pausa_arr = $_POST['hora_termino_pausa'] ?? [];

                $sqlP = "INSERT INTO pausa_exposicao (

                    id_exposicao,

                    data_inicio_pausa,
                    hora_inicio_pausa,

                    data_termino_pausa,
                    hora_termino_pausa

                ) VALUES (

                    :id_exposicao,

                    :data_inicio_pausa,
                    :hora_inicio_pausa,

                    :data_termino_pausa,
                    :hora_termino_pausa
                )";

                $stmtP = $pdo->prepare($sqlP);

                for ($i = 0; $i < $quantas_pausas; $i++) {

                    $stmtP->execute([
                        ':id_exposicao' => $id_exposicao,

                        ':data_inicio_pausa' => $data_inicio_pausa_arr[$i],
                        ':hora_inicio_pausa' => $hora_inicio_pausa_arr[$i],

                        ':data_termino_pausa' => $data_termino_pausa_arr[$i],
                        ':hora_termino_pausa' => $hora_termino_pausa_arr[$i]
                    ]);
                }
            }

            $pdo->commit();

            header("Location: registro-exposicao.php");
            exit();

        } catch (Exception $e) {

            $pdo->rollBack();

            $erro = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

$barreira = isset($_POST['barreira']) ? 1 : 0;
$qual_barreira = $_POST['qual_barreira'] ?? '';

$pausa = isset($_POST['pausa']) ? 1 : 0;
$quantas_pausas = (int) ($_POST['quantas_pausas'] ?? 0);

if(!$pausa){
    $quantas_pausas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registro de Exposição</title>

<link rel="stylesheet" href="styles.css">

<style>

.section-title{
    color: #7c3aed;
    margin-bottom: 1rem;
    margin-top: 2rem;
}

.form-row{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

input{
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.btn{
    background: #7c3aed;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    cursor: pointer;
}

.pause-box{
    background: #f3f4f6;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.check-group{
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .5rem;
}

</style>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registrar Férias — RadyX</title>
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

<div class="form-box">

    <h1 style="color:#7c3aed;">Registro de Exposição</h1>

    <?php if($erro): ?>
        <p style="color:red;">
            <?= $erro ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <h3 class="section-title">Informações Gerais</h3>

        <div class="form-row">

            <div>
                <label>Equipamento Emissor</label>
                <input type="text" name="equipamento_emissor" required>
            </div>

            <div>
                <label>Distância do aparelho (m)</label>
                <input type="number" step="0.01" name="distancia" required>
            </div>

        </div>

        <h3 class="section-title">Período de Exposição</h3>

        <div class="form-row">

            <div>
                <label>Data início</label>
                <input type="date" name="data_inicio" required>
            </div>

            <div>
                <label>Hora início</label>
                <input type="time" name="hora_inicio" required>
            </div>

        </div>

        <div class="form-row">

            <div>
                <label>Data término</label>
                <input type="date" name="data_termino" required>
            </div>

            <div>
                <label>Hora término</label>
                <input type="time" name="hora_termino" required>
            </div>

        </div>

        <h3 class="section-title">Barreira de Proteção</h3>

        <label class="check-group">
            Possui barreira?
            <input type="checkbox" name="barreira" id="barreira_chk" onchange="toggleBarreira()">
        </label>

        <div style="margin-top:1rem;">
            <label>Qual barreira?</label>
            <input type="text" name="qual_barreira" id="qual_barreira" disabled>
        </div>

        <label class="check-group">
            Teve pausas?
            <input type="checkbox" name="pausa" id="pausa_chk" onchange="togglePausas()">
        </label>

        <div style="margin-top:1rem;">
            <label>Quantidade de pausas</label>
            <input
                type="number"
                min="0"
                value="0"
                id="quantas_pausas"
                name="quantas_pausas"
                oninput="renderPausas()"
                disabled
            >
        </div>

<div id="pausas_area" style="display:none;"></div>

        <div id="pausas_area"></div>

        <div style="margin-top:1.25rem; display:flex; gap:1rem; justify-content:flex-end;">
        <a href="registro-exposicao.php" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">Salvar Registro</button>
      </div>

    </form>

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

function togglePausas(){

    const chk = document.getElementById('pausa_chk');
    const qtyInput = document.getElementById('quantas_pausas');
    const area = document.getElementById('pausas_area');

    if(chk.checked){
        qtyInput.disabled = false;
        area.style.display = 'block';
    } else {
        qtyInput.disabled = true;
        qtyInput.value = 0;
        area.innerHTML = '';
        area.style.display = 'none';
    }
}
function renderPausas(){

    const chk = document.getElementById('pausa_chk');
    if(!chk.checked) return;

    const qty = parseInt(document.getElementById('quantas_pausas').value) || 0;
    const area = document.getElementById('pausas_area');

    area.innerHTML = '';

    for(let i = 0; i < qty; i++){

        area.innerHTML += `
            <div class="pause-box">

                <h4>Pausa ${i+1}</h4>

                <div class="form-row">
                    <div>
                        <label>Data início pausa</label>
                        <input type="date" name="data_inicio_pausa[]">
                    </div>

                    <div>
                        <label>Hora início pausa</label>
                        <input type="time" name="hora_inicio_pausa[]">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Data término pausa</label>
                        <input type="date" name="data_termino_pausa[]">
                    </div>

                    <div>
                        <label>Hora término pausa</label>
                        <input type="time" name="hora_termino_pausa[]">
                    </div>
                </div>

            </div>
        `;
    }
}

function toggleBarreira(){

    const chk = document.getElementById('barreira_chk');
    const input = document.getElementById('qual_barreira');

    if(chk.checked){
        input.disabled = false;
    } else {
        input.disabled = true;
        input.value = '';
    }
}

function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}

</script>

</body>
</html>