<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
$_authUser = requireLogin(true);

$semana_id = intval($_GET['semana_id'] ?? 0);
if (!$semana_id) { echo json_encode([]); exit; }

$conn = getConnection();

// Totais gerais da semana
$stmtTotais = $conn->prepare(
    "SELECT COALESCE(SUM(total_agendados),0)  AS total_agendados,
            COALESCE(SUM(total_atendidos),0)  AS total_atendidos,
            COALESCE(SUM(total_cancelados),0) AS total_cancelados,
            COALESCE(SUM(total_faltas),0)     AS total_faltas
     FROM atendimentos WHERE semana_id = ?"
);
$stmtTotais->bind_param('i', $semana_id);
$stmtTotais->execute();
$totais = $stmtTotais->get_result()->fetch_assoc();

// Atendimentos por dia
$stmtDia = $conn->prepare(
    "SELECT data, total_agendados, total_atendidos, total_cancelados, total_faltas
     FROM atendimentos WHERE semana_id = ? ORDER BY data"
);
$stmtDia->bind_param('i', $semana_id);
$stmtDia->execute();
$por_dia = $stmtDia->get_result()->fetch_all(MYSQLI_ASSOC);

// Top 5 horários de pico
$stmtPico = $conn->prepare(
    "SELECT data, hora, total_atendimentos AS total
     FROM horarios_pico WHERE semana_id = ?
     ORDER BY total DESC LIMIT 5"
);
$stmtPico->bind_param('i', $semana_id);
$stmtPico->execute();
$picos = $stmtPico->get_result()->fetch_all(MYSQLI_ASSOC);

// Motivos de fechamento
$stmtFech = $conn->prepare(
    "SELECT m.descricao, f.total
     FROM fechamentos f
     JOIN motivos_fechamento m ON m.id = f.motivo_id
     WHERE f.semana_id = ?
     ORDER BY f.total DESC"
);
$stmtFech->bind_param('i', $semana_id);
$stmtFech->execute();
$fechamentos = $stmtFech->get_result()->fetch_all(MYSQLI_ASSOC);

// Pesquisa de satisfação da semana
$stmtPesq = $conn->prepare(
    "SELECT pessimo, ruim, neutro, bom, excelente
     FROM pesquisa_satisfacao WHERE semana_id = ? LIMIT 1"
);
$stmtPesq->bind_param('i', $semana_id);
$stmtPesq->execute();
$pesquisa = $stmtPesq->get_result()->fetch_assoc();

echo json_encode([
    'totais'      => $totais,
    'por_dia'     => $por_dia,
    'picos'       => $picos,
    'fechamentos' => $fechamentos,
    'pesquisa'    => $pesquisa ?: null,
]);

$conn->close();
