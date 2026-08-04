<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
$_authUser = requireLogin(true);

$ano = intval($_GET['ano'] ?? 0);
if (!$ano) { echo json_encode([]); exit; }

$conn = getConnection();

// Semanas do ano
$stmtSem = $conn->prepare(
    "SELECT id, data_inicio, data_fim, descricao
     FROM semanas
     WHERE YEAR(data_inicio) = ? OR YEAR(data_fim) = ?
     ORDER BY data_inicio"
);
$stmtSem->bind_param('ii', $ano, $ano);
$stmtSem->execute();
$semanas = $stmtSem->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$semanas) {
    echo json_encode(['totais' => ['total_agendados'=>0,'total_atendidos'=>0,'total_cancelados'=>0,'total_faltas'=>0], 'por_mes' => [], 'picos' => [], 'fechamentos' => [], 'pesquisa' => null]);
    $conn->close();
    exit;
}

$ids          = array_column($semanas, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types        = str_repeat('i', count($ids));

// Totais do ano
$stmtTot = $conn->prepare(
    "SELECT COALESCE(SUM(total_agendados),0)  AS total_agendados,
            COALESCE(SUM(total_atendidos),0)  AS total_atendidos,
            COALESCE(SUM(total_cancelados),0) AS total_cancelados,
            COALESCE(SUM(total_faltas),0)     AS total_faltas
     FROM atendimentos WHERE semana_id IN ($placeholders)"
);
$stmtTot->bind_param($types, ...$ids);
$stmtTot->execute();
$totais = $stmtTot->get_result()->fetch_assoc();

// Atendimentos agrupados por mês
$stmtMes = $conn->prepare(
    "SELECT MONTH(a.data) AS mes,
            COALESCE(SUM(a.total_agendados),0)  AS total_agendados,
            COALESCE(SUM(a.total_atendidos),0)  AS total_atendidos,
            COALESCE(SUM(a.total_cancelados),0) AS total_cancelados,
            COALESCE(SUM(a.total_faltas),0)     AS total_faltas
     FROM atendimentos a
     WHERE a.semana_id IN ($placeholders) AND YEAR(a.data) = ?
     GROUP BY MONTH(a.data) ORDER BY MONTH(a.data)"
);
$typesAno = $types . 'i';
$paramsAno = [...$ids, $ano];
$stmtMes->bind_param($typesAno, ...$paramsAno);
$stmtMes->execute();
$por_mes_raw = $stmtMes->get_result()->fetch_all(MYSQLI_ASSOC);

// Garante todos os 12 meses no array
$meses_nomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
$por_mes = [];
for ($m = 1; $m <= 12; $m++) {
    $found = array_filter($por_mes_raw, fn($r) => (int)$r['mes'] === $m);
    $found = array_values($found);
    $por_mes[] = [
        'mes'              => $m,
        'mes_nome'         => $meses_nomes[$m - 1],
        'total_agendados'  => $found ? $found[0]['total_agendados']  : 0,
        'total_atendidos'  => $found ? $found[0]['total_atendidos']  : 0,
        'total_cancelados' => $found ? $found[0]['total_cancelados'] : 0,
        'total_faltas'     => $found ? $found[0]['total_faltas']     : 0,
    ];
}

// Top 5 horários de pico do ano
$stmtPico = $conn->prepare(
    "SELECT hora, SUM(total_atendimentos) AS total
     FROM horarios_pico WHERE semana_id IN ($placeholders)
     GROUP BY hora ORDER BY total DESC LIMIT 5"
);
$stmtPico->bind_param($types, ...$ids);
$stmtPico->execute();
$picos = $stmtPico->get_result()->fetch_all(MYSQLI_ASSOC);

// Motivos de fechamento do ano
$stmtFech = $conn->prepare(
    "SELECT m.descricao, SUM(f.total) AS total
     FROM fechamentos f
     JOIN motivos_fechamento m ON m.id = f.motivo_id
     WHERE f.semana_id IN ($placeholders)
     GROUP BY m.id ORDER BY total DESC"
);
$stmtFech->bind_param($types, ...$ids);
$stmtFech->execute();
$fechamentos = $stmtFech->get_result()->fetch_all(MYSQLI_ASSOC);

// Pesquisa de satisfação do ano
$stmtPesq = $conn->prepare(
    "SELECT COALESCE(SUM(pessimo),0)   AS pessimo,
            COALESCE(SUM(ruim),0)      AS ruim,
            COALESCE(SUM(neutro),0)    AS neutro,
            COALESCE(SUM(bom),0)       AS bom,
            COALESCE(SUM(excelente),0) AS excelente
     FROM pesquisa_satisfacao WHERE semana_id IN ($placeholders)"
);
$stmtPesq->bind_param($types, ...$ids);
$stmtPesq->execute();
$pesquisa = $stmtPesq->get_result()->fetch_assoc();
if (array_sum($pesquisa) == 0) $pesquisa = null;

echo json_encode([
    'totais'              => $totais,
    'por_mes'             => $por_mes,
    'fechamentos_por_mes' => $fechamentos_por_mes,
    'picos'               => $picos,
    'fechamentos'         => $fechamentos,
    'pesquisa'            => $pesquisa,
]);

$conn->close();
