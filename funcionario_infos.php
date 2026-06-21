<?php
session_start();
require "conn.php";

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Funcionário não especificado.");
}

$empresa_id = $_SESSION['empresa_id'];
$funcionario_id = $_GET['id'];

$sql = $pdo->prepare("
    SELECT * FROM funcionario 
    WHERE id = ? AND id_empresa = ?
");

$sql->execute([$funcionario_id, $empresa_id]);
$func = $sql->fetch(PDO::FETCH_ASSOC);

if (!$func) {
    die("Funcionário não encontrado.");
}

$sqlTempo = $pdo->prepare("
    SELECT 
        id,
        data_inicio,
        data_termino,
        hora_inicio,
        hora_termino,
        quantas_pausas,
        equipamento_emissor,
        distancia,
        barreira,
        qual_barreira
    FROM registro_exposicao
    WHERE id_funcionario = ?
    ORDER BY data_inicio DESC
");

$sqlTempo->execute([$funcionario_id]);
$exposicoes = $sqlTempo->fetchAll(PDO::FETCH_ASSOC);



$sqlPausas = $pdo->prepare("
    SELECT *
    FROM pausa_exposicao
    WHERE id_exposicao = ?
");



$sqlExames = $pdo->prepare("
    SELECT *
    FROM exame_preventivo 
    WHERE id_funcionario = ?
    ORDER BY data_exame DESC
");

$sqlExames->execute([$funcionario_id]);
$exames = $sqlExames->fetchAll(PDO::FETCH_ASSOC);

$sqlFerias = $pdo->prepare("
    SELECT *
    FROM ferias
    WHERE id_funcionario = ?
    ORDER BY data_inicio DESC
");

$sqlFerias->execute([$funcionario_id]);
$ferias = $sqlFerias->fetchAll(PDO::FETCH_ASSOC);

$sqlDose = $pdo->prepare("
    SELECT 
        nivel_radiacao_dossimetro,
        data_dossimetro
    FROM dose_mensal
    WHERE id_funcionario = ?
    ORDER BY data_dossimetro DESC
");

$sqlDose->execute([$funcionario_id]);
$doses = $sqlDose->fetchAll(PDO::FETCH_ASSOC);

$totalDose = 0;

foreach ($doses as $d) {
    $totalDose += (float)$d['nivel_radiacao_dossimetro'];
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informações do Profissional</title>

    <link rel="icon" href="imagens/logoradyx.png">
    <link rel="stylesheet" href="styles.css">

    <style>

        body {
            background: #e9d8fd;
        }

        .container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .card1,
        .card2 {
            background: #fff;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
            width: 40%;
            min-width: 350px;
        }

        .titulo {
            font-size: 2rem;
            text-align: center;
            color: #4c1d95;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #faf5ff;
            color: #4c1d95;
        }

        .dose-box {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }

        .sub {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .summary-item {
            background: #faf5ff;
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
        }

        .summary-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4c1d95;
        }

        .summary-label {
            color: #666;
            margin-top: .5rem;
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


<br><br><br>

<div>

    

    <a class="back-button" href="funcionarios.php">
        ← Voltar
    </a>

</div>

    <h1 class="titulo">
        Informações do Profissional
    </h1>

    <br><br>

    <center>

        <div class="container">

            <div class="card1">

                <h2 style="color:#4c1d95;">
                    Dados Pessoais 
                </h2>

                <br>

                <p><strong>Nome:</strong> <?= htmlspecialchars($func['nome']) ?></p>

                <p><strong>CPF:</strong> <?= htmlspecialchars($func['cpf']) ?></p>

                <p>
                    <strong>Nascimento:</strong>

                    <?= !empty($func['data_nascimento'])
                        ? date('d/m/Y', strtotime($func['data_nascimento']))
                        : '-' ?>
                </p>

                <p><strong>Telefone:</strong> <?= htmlspecialchars($func['telefone']) ?></p>

                <p><strong>Email:</strong> <?= htmlspecialchars($func['email']) ?></p>

            </div>

            <div class="card2">

                <h2 style="color:#4c1d95;">
                    Informações Profissionais 
                </h2> 
                
                <br>

                <p><strong>Profissão:</strong> <?= htmlspecialchars($func['profissao']) ?></p>

                <p>
                    <strong>Registro Profissional:</strong>

                    <?= htmlspecialchars($func['registroProfissional']) ?>
                </p>

                <p>
                    <strong>Instituição:</strong>

                    <?= htmlspecialchars($func['instituicao']) ?>
                </p>

                <p>
                    <strong>Tempo de Trabalho:</strong>

                    <?= htmlspecialchars($func['tempo']) ?>
                </p>

            </div>

        </div>

        <div class="container" style="flex-direction: column; align-items:center;">

            <div class="card1" style="width:82%;">

                <h2 style="color:#4c1d95;">
                    Histórico de Exposição 
                </h2>
                
                <br>

                <?php if ($exposicoes): ?>

                    <div class = "table-responsive">
                        <table>

                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Duração (h)</th>
                                    <th>Pausas</th>
                                    <th>Equipamentos</th>
                                    <th>Distância (m)</th>
                                    <th>Barreira</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($exposicoes as $e): ?>

                                    <?php


                                        $sqlPausas->execute([$e['id']]);
                                        $pausas = $sqlPausas->fetchAll(PDO::FETCH_ASSOC);

                                        $inicio = strtotime($e['data_inicio'] . ' ' . $e['hora_inicio']);

                                        $fim = strtotime($e['data_termino'] . ' ' . $e['hora_termino']);

                                        $duracao = 0;

                                        if ($fim > $inicio) {
                                            $duracao = round(($fim - $inicio) / 3600, 2);
                                        }

                                    ?>

                                    <tr>

                                        <td>
                                            <?= date('d/m/Y', strtotime($e['data_inicio'])) ?>
                                        </td>

                                        <td>
                                            <?= $e['hora_inicio'] ?> -
                                            <?= $e['hora_termino'] ?>
                                        </td>

                                        <td>
                                            <?= number_format($duracao,2) ?> h
                                        </td>

                                        <td>
                                            <?= count($pausas) ?>
                                        </td>

                                        <td>

                                            <?php
                                                if (!empty($e['equipamento_emissor'])) {
                                                    echo htmlspecialchars($e['equipamento_emissor']);
                                                } else {
                                                    echo '-';
                                                }
                                            ?>

                                        </td>

                                        <td>

                                            <?php
                                                if (!empty($e['distancia'])) {
                                                    echo htmlspecialchars($e['distancia']) . ' m';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>

                                        </td>

                                        <td>

                                            <?php

                                                if ($e['barreira']) {

                                                    echo !empty($e['qual_barreira'])
                                                        ? htmlspecialchars($e['qual_barreira'])
                                                        : 'Sim';

                                                } else {

                                                    echo 'Não';

                                                }

                                            ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>  

                <?php else: ?>

                    <p>Nenhuma exposição registrada.</p>

                <?php endif; ?>

            </div>

            

        </div>

        

        

        </div>

        <div class="container">

            <div class="card1" style="width:82%;">

                <h2 style="color:#4c1d95;">
                    Recursos e Observações
                </h2>

                <p>

                    Este relatório apresenta o histórico ocupacional
                    relacionado à exposição à radiação ionizante,
                    incluindo tempo de exposição, pausas, distância,
                    barreiras de proteção, exames preventivos,
                    férias e registros do dosímetro.

                </p>

            </div>

        </div>

    </center>

</div>

<footer>

    <div class="footer-content">

        <div class="footer-section">

            <h3>RadyX</h3>

            <p>
                Sistema de monitoramento de exposição à radiação
                ionizante para profissionais da saúde.
            </p>

        </div>

    </div>

    <div class="footer-bottom">

        <p>Trabalho Acadêmico - Sem fins lucrativos<br>Maria Rodrigues,  Leonardo Viana e Kayky Padella 2025 - &copyTodos os direitos reservados</p><br>

        <a href="#"
           class="back-to-top"
           onclick="window.scrollTo(0,0)">

            Voltar ao topo

        </a>

    </div>

</footer>

</body>
</html>