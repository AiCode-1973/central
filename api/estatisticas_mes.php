<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$ano = intval($_GET['ano'] ?? 0);
$mes = intval($_GET['mes'] ?? 0);
if (!$ano || !$mes) { echo json_encode([]); exit; }

$conn = getConnection();

// Todas as semanas do mês (data_inicio ou data_fim dentro do mês)
$stmtSem = $conn->prepare(
    "SELECT id, data_inicio, data_fim, descricao
     FROM semanas
     WHERE YEAR(data_inicio) = ? AND MONTH(data_inicio) = ?
        OR YEAR(data_fim)    = ? AND MONTH(data_fim)    = ?
     ORDER BY data_inicio"
);
$stmtSem->bind_param('iiii', $ano, $mes, $ano, $mes);
$stmtSem->execute();
$semanas = $stmtSem->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$semanas) {
    echo json_encode(['totais' => ['total_atendidos' => 0], 'por_semana' => [], 'picos' => [], 'fechamentos' => []]);
    $conn->close();
    exit;
}

$ids        = array_column($semanas, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types      = str_repeat('i', count($ids));

// Totais do mês
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

// Atendimentos por semana (label = data_inicio da semana)
$stmtSem2 = $conn->prepare(
    "SELECT s.data_inicio, s.data_fim, s.descricao,
            COALESCE(SUM(a.total_atendidos),0)  AS total_atendidos
     FROM semanas s
     LEFT JOIN atendimentos a ON a.semana_id = s.id
     WHERE s.id IN ($placeholders)
     GROUP BY s.id ORDER BY s.data_inicio"
);
$stmtSem2->bind_param($types, ...$ids);
$stmtSem2->execute();
$por_semana = $stmtSem2->get_result()->fetch_all(MYSQLI_ASSOC);

// Top 5 horários de pico do mês
$stmtPico = $conn->prepare(
    "SELECT hora, SUM(total_atendimentos) AS total
     FROM horarios_pico WHERE semana_id IN ($placeholders)
     GROUP BY hora ORDER BY total DESC LIMIT 5"
);
$stmtPico->bind_param($types, ...$ids);
$stmtPico->execute();
$picos = $stmtPico->get_result()->fetch_all(MYSQLI_ASSOC);

// Motivos de fechamento do mês
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

echo json_encode([
    'totais'      => $totais,
    'por_semana'  => $por_semana,
    'picos'       => $picos,
    'fechamentos' => $fechamentos,
]);

$conn->close();
