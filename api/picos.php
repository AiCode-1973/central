<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
$_authUser = requireLogin(true);

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {
        case 'GET':
            $semana_id = intval($_GET['semana_id'] ?? 0);
            if (!$semana_id) { echo json_encode([]); break; }
            $stmt = $conn->prepare(
                "SELECT id, data, hora, total_atendimentos
                 FROM horarios_pico
                 WHERE semana_id = ?
                 ORDER BY data, hora"
            );
            $stmt->bind_param('i', $semana_id);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        case 'POST':
            $body  = json_decode(file_get_contents('php://input'), true);
            $items = $body['items'] ?? [$body];
            $stmt  = $conn->prepare(
                "INSERT INTO horarios_pico (semana_id, data, hora, total_atendimentos)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE total_atendimentos = VALUES(total_atendimentos)"
            );
            foreach ($items as $item) {
                $sid   = intval($item['semana_id']          ?? 0);
                $dt    = trim($item['data']                 ?? '');
                $hora  = trim($item['hora']                 ?? '');
                $total = intval($item['total_atendimentos'] ?? 0);
                if (!$sid || !$dt || !$hora) continue;
                $stmt->bind_param('issi', $sid, $dt, $hora, $total);
                $stmt->execute();
            }
            echo json_encode(['mensagem' => 'Horários de pico salvos.']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id inválido.']); break; }
            $stmt = $conn->prepare("DELETE FROM horarios_pico WHERE id = ?");
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
