<?php

session_start();
require "conn.php";

if (!isset($_SESSION['funcionario_id'])) {
    header("Location: login.php");
    exit();
}

$id_funcionario = $_SESSION['funcionario_id'];

$hoje = new DateTime();

$data_fim_def = $hoje->format('Y-m-d');

$data_inicio_def = (clone $hoje)
    ->modify('-12 months')
    ->format('Y-m-d');

$erro = null;

$data_inicio = $_GET['data_inicio'] ?? $data_inicio_def;
$data_fim = $_GET['data_fim'] ?? $data_fim_def;

try {

    $dInicio = new DateTime($data_inicio);
    $dFim = new DateTime($data_fim);

} catch (Exception $e) {

    $erro = "Datas inválidas.";

    $dInicio = new DateTime($data_inicio_def);
    $dFim = new DateTime($data_fim_def);

}

if (!$erro) {

    if ($dInicio > $dFim) {

        $erro = "Data de início não pode ser posterior à data final.";

    } else {

        $interval = $dInicio->diff($dFim)->days;

        if ($interval > 365) {

            $erro = "O período não pode exceder 12 meses (365 dias).";

        }

    }

}

if ($erro) {

    $data_inicio = $dInicio->format('Y-m-d');
    $data_fim = $dFim->format('Y-m-d');

}

$sqlFuncionario = $pdo->prepare("
SELECT
    id,
    id_empresa,
    nome,
    cpf,
    data_nascimento,
    email,
    telefone,
    profissao,
    registroProfissional,
    instituicao,
    tempo

FROM funcionario

WHERE id = ?
");

$sqlFuncionario->execute([$id_funcionario]);

$func = $sqlFuncionario->fetch(PDO::FETCH_ASSOC);

$dosesPorData = [];

$stmtDose = $pdo->prepare("
SELECT
    data_dossimetro,
    nivel_radiacao_dossimetro

FROM dose_mensal

WHERE id_funcionario = :id
AND data_dossimetro BETWEEN :start AND :end
");

$stmtDose->execute([
    ':id' => $id_funcionario,
    ':start' => $data_inicio,
    ':end' => $data_fim
]);

while ($r = $stmtDose->fetch(PDO::FETCH_ASSOC)) {

    $dosesPorData[$r['data_dossimetro']] =
        (float)$r['nivel_radiacao_dossimetro'];

}

$registros = [];

$sql = "

SELECT

    re.id,
    re.equipamento_emissor,
    re.data_inicio,
    re.data_termino,
    re.hora_inicio,
    re.hora_termino,
    re.distancia,
    re.barreira,
    re.qual_barreira,
    re.pausa,
    re.quantas_pausas

FROM registro_exposicao re

WHERE re.id_funcionario = :id

AND re.data_inicio BETWEEN :start AND :end

ORDER BY re.data_inicio ASC,
         re.hora_inicio ASC

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id_funcionario,
    ':start' => $data_inicio,
    ':end' => $data_fim
]);

$exposicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtPausas = $pdo->prepare("
SELECT
    data_inicio_pausa,
    data_termino_pausa,
    hora_inicio_pausa,
    hora_termino_pausa

FROM pausa_exposicao

WHERE id_exposicao = :id_exposicao
");

foreach ($exposicoes as $ex) {

    $idEx = $ex['id'];

    $inicio_ts = strtotime(
        $ex['data_inicio'] . ' ' . $ex['hora_inicio']
    );

    $termino_ts = strtotime(
        $ex['data_termino'] . ' ' . $ex['hora_termino']
    );

    $dur_h = $termino_ts > $inicio_ts
        ? round(($termino_ts - $inicio_ts) / 3600, 2)
        : 0;

    $stmtPausas->execute([
        ':id_exposicao' => $idEx
    ]);

    $pausas = $stmtPausas->fetchAll(PDO::FETCH_ASSOC);

    $total_pausa_min = 0;

    foreach ($pausas as $p) {

        $p_inicio_ts = strtotime(
            $p['data_inicio_pausa'] . ' ' .
            $p['hora_inicio_pausa']
        );

        $p_termino_ts = strtotime(
            $p['data_termino_pausa'] . ' ' .
            $p['hora_termino_pausa']
        );

        if ($p_termino_ts > $p_inicio_ts) {

            $total_pausa_min +=
                ($p_termino_ts - $p_inicio_ts) / 60;

        }

    }

    $total_pausa_h = round(
        $total_pausa_min / 60,
        2
    );

    $dose = $dosesPorData[$ex['data_inicio']] ?? null;

    $registros[] = [

        'data' => $ex['data_inicio'],

        'equipamento' => $ex['equipamento_emissor'],

        'distancia' => $ex['distancia'],

        'duracao_h' => $dur_h,

        'quantas_pausas' => (int)$ex['quantas_pausas'],

        'duracao_pausa_h' => $total_pausa_h,

        'barreira' => (int)$ex['barreira'],

        'qual_barreira' => $ex['qual_barreira'],

        'dose_msv' => $dose

    ];

}

$sqlExames = $pdo->prepare("
SELECT
    tipo_exame,
    data_exame,
    resultado_exame

FROM exame_preventivo

WHERE id_funcionario = :id
AND data_exame BETWEEN :start AND :end

ORDER BY data_exame ASC
");

$sqlExames->execute([
    ':id' => $id_funcionario,
    ':start' => $data_inicio,
    ':end' => $data_fim
]);

$exames = $sqlExames->fetchAll(PDO::FETCH_ASSOC);

$sqlFerias = $pdo->prepare("
SELECT
    data_inicio,
    data_termino

FROM ferias

WHERE id_funcionario = :id

AND (
    data_inicio BETWEEN :start AND :end
    OR
    data_termino BETWEEN :start AND :end
)

ORDER BY data_inicio ASC
");

$sqlFerias->execute([
    ':id' => $id_funcionario,
    ':start' => $data_inicio,
    ':end' => $data_fim
]);

$ferias = $sqlFerias->fetchAll(PDO::FETCH_ASSOC);

usort($registros, function($a,$b){

    if ($a['data'] === $b['data']) {
        return 0;
    }

    return ($a['data'] < $b['data']) ? -1 : 1;

});

$totalDose = 0;

foreach ($dosesPorData as $dVal) {

    $totalDose += $dVal;

}

$tituloPeriodo =
    $dInicio->format('d/m/Y') .
    " — " .
    $dFim->format('d/m/Y');

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width,initial-scale=1">

<title>
    Relatório Anual — RadyX
</title>

<link rel="icon" href="imagens/logoradyx.png">

<link rel="stylesheet" href="styles.css">

</head>

<body>

<div class="report-container">

    <a href="monitor.php"
       class="back-button">

       ← Voltar

    </a>

    <div class="report-header">

        <h1>
            Relatório Anual de Exposição
        </h1>

        <h2>
            Período:
            <?= htmlspecialchars($tituloPeriodo) ?>
        </h2>

    </div>

    <div class="report-body">

        <div style="margin-bottom:1rem;">

            <form method="get"
                  style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">

                <label>

                    Data início:

                    <input type="date"
                           name="data_inicio"
                           value="<?= htmlspecialchars($data_inicio) ?>"
                           required>

                </label>

                <label>

                    Data fim:

                    <input type="date"
                           name="data_fim"
                           value="<?= htmlspecialchars($data_fim) ?>"
                           required>

                </label>

                <button class="btn-primary"
                        type="submit">

                    Aplicar

                </button>

                <div style="color:#ef4444; margin-left:1rem;">

                    <?= $erro ? htmlspecialchars($erro) : '' ?>

                </div>

            </form>

        </div>

        <div class="report-info-grid">

            <div class="report-info-item">
                <label>Nome</label>
                <span><?= htmlspecialchars($func['nome'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Matrícula / Empresa</label>
                <span><?= htmlspecialchars($func['id_empresa'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Nascimento</label>
                <span>
                    <?= !empty($func['data_nascimento'])
                        ? date('d/m/Y', strtotime($func['data_nascimento']))
                        : '—' ?>
                </span>
            </div>

            <div class="report-info-item">
                <label>Telefone</label>
                <span><?= htmlspecialchars($func['telefone'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>CPF</label>
                <span><?= htmlspecialchars($func['cpf'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>E-mail</label>
                <span><?= htmlspecialchars($func['email'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Profissão</label>
                <span><?= htmlspecialchars($func['profissao'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Registro</label>
                <span><?= htmlspecialchars($func['registroProfissional'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Instituição</label>
                <span><?= htmlspecialchars($func['instituicao'] ?? '—') ?></span>
            </div>

            <div class="report-info-item">
                <label>Tempo</label>
                <span><?= htmlspecialchars($func['tempo'] ?? '—') ?></span>
            </div>

        </div>

        <div class="table-responsive">

            <table class="report-table" style="margin-top:1rem;">

                <thead>

                    <tr>

                        <th>Data</th>
                        <th>Equipamento</th>
                        <th>Distância (m)</th>
                        <th>Duração (h)</th>
                        <th>Pausas</th>
                        <th>Duração pausa (h)</th>
                        <th>Barreiras</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($registros)): ?>

                    <tr>

                        <td colspan="7">
                            Nenhum registro encontrado.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($registros as $r): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'd/m/Y',
                                        strtotime($r['data'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['equipamento']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['distancia']) ?>
                            </td>

                            <td>
                                <?= number_format($r['duracao_h'],2) ?> h
                            </td>

                            <td>
                                <?= (int)$r['quantas_pausas'] ?>
                            </td>

                            <td>
                                <?= number_format($r['duracao_pausa_h'],2) ?> h
                            </td>

                            <td>

                                <?=
                                    $r['barreira']
                                    ? 'Sim — ' .
                                    htmlspecialchars($r['qual_barreira'])
                                    : 'Não'
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="report-summary" style="margin-top:1rem;">

        <h3>Dose do Dosímetro</h3>

        <div class="stats-row">

            <div class="stat-card">
                <div class="summary-value">
                    <?= number_format($totalDose,3) ?>
                </div>
                <div class="summary-label">
                    Dose total no período (mSv)
                </div>
            </div>

            <div class="stat-card">
                <div class="summary-value">
                    <?= number_format(
                        count($dosesPorData)
                        ? $totalDose / count($dosesPorData)
                        : 0,
                        3
                    ) ?>
                </div>
                <div class="summary-label">
                    Média diária registrada
                </div>
            </div>

            <div class="stat-card">
                <div class="summary-value">
                    <?= !empty($dosesPorData)
                        ? number_format(max($dosesPorData),3)
                        : '0.000' ?>
                </div>
                <div class="summary-label">
                    Maior dose registrada
                </div>
            </div>

            <div class="stat-card">
                <div class="summary-value">
                    <?= number_format(
                        array_sum(array_column($registros,'duracao_h')),
                        2
                    ) ?> h
                </div>
                <div class="summary-label">
                    Horas reportadas
                </div>
            </div>

        </div>

    </div>

       

        <div class="report-exams" style="margin-top:1rem;">

            <h3>
                Exames realizados no período
            </h3>

            <?php if (empty($exames)): ?>

                <div class="exam-item">
                    Nenhum exame registrado.
                </div>

            <?php else: ?>

                <?php foreach ($exames as $ex): ?>

                    <div class="ferias-item">

                        <span>

                            <strong>Data:</strong>

                            <?= htmlspecialchars(
                                date(
                                    'd/m/Y',
                                    strtotime($ex['data_exame'])
                                )
                            ) ?>

                        </span>

                        <span>

                            <strong>Tipo:</strong>

                            <?= htmlspecialchars($ex['tipo_exame']) ?>

                        </span>

                        <span>

                            <strong>Resultado:</strong>

                            <?= htmlspecialchars($ex['resultado_exame']) ?>

                        </span>

                        <br><br>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <div class="report-exams" style="margin-top:1rem;">

            <h3>
                Férias no período
            </h3>

            <?php if (empty($ferias)): ?>

                <div class="exam-item">
                    Nenhum período de férias registrado.
                </div>

            <?php else: ?>

                <?php foreach ($ferias as $f): ?>

                    <div class="ferias-item">

                        <span>

                            <strong>Início:</strong>

                            <?= htmlspecialchars(
                                date(
                                    'd/m/Y',
                                    strtotime($f['data_inicio'])
                                )
                            ) ?>

                        </span>

                        <span>

                            <strong>Término:</strong>

                            <?= htmlspecialchars(
                                date(
                                    'd/m/Y',
                                    strtotime($f['data_termino'])
                                )
                            ) ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

            <div class="report-dates" style="margin-top:1rem;">

            <div>

                <label>Data início:</label>

                <span>
                    <?= htmlspecialchars(
                        $dInicio->format('d/m/Y')
                    ) ?>
                </span>

            </div>

            <div>

                <label>Data término:</label>

                <span>
                    <?= htmlspecialchars(
                        $dFim->format('d/m/Y')
                    ) ?>
                </span>

            </div>

        </div>


        <div class="report-footer" style="margin-top:1rem;">

            <h3>
                Observações
            </h3>

            <p>

                Este relatório foi gerado com base
                nos registros de exposição,
                pausas, distâncias,
                barreiras de proteção,
                doses registradas
                e exames preventivos do sistema.

            </p>

        </div>

        </div>

    </div>

</div>

</body>
</html>
