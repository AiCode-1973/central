<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {
        case 'GET':
            $semana_id = intval($_GET['semana_id'] ?? 0);
            if (!$semana_id) { echo json_encode([]); break; }
            $stmt = $conn->prepare(
                "SELECT id, semana_id, data, total_agendados, total_atendidos,
                        total_cancelados, total_faltas, observacao
                 FROM atendimentos
                 WHERE semana_id = ?
                 ORDER BY data"
            );
            $stmt->bind_param('i', $semana_id);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        case 'POST':
            $body  = json_decode(file_get_contents('php://input'), true);
            $items = $body['items'] ?? [$body];
            $stmt  = $conn->prepare(
                "INSERT INTO atendimentos
                    (semana_id, data, total_agendados, total_atendidos, total_cancelados, total_faltas, observacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    total_agendados  = VALUES(total_agendados),
                    total_atendidos  = VALUES(total_atendidos),
                    total_cancelados = VALUES(total_cancelados),
                    total_faltas     = VALUES(total_faltas),
                    observacao       = VALUES(observacao)"
            );
            foreach ($items as $item) {
                $sid = intval($item['semana_id'] ?? 0);
                $dt  = trim($item['data']        ?? '');
                $ag  = intval($item['total_agendados']  ?? 0);
                $at  = intval($item['total_atendidos']  ?? 0);
                $ca  = intval($item['total_cancelados'] ?? 0);
                $fa  = intval($item['total_faltas']     ?? 0);
                $obs = trim($item['observacao']         ?? '');
                if (!$sid || !$dt) continue;
                $stmt->bind_param('isiiiis', $sid, $dt, $ag, $at, $ca, $fa, $obs);
                $stmt->execute();
            }
            echo json_encode(['mensagem' => 'Atendimentos salvos.']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id inválido.']); break; }
            $stmt = $conn->prepare("DELETE FROM atendimentos WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Registro removido.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}

$conn->close();
