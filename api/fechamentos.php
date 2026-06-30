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
                "SELECT f.id, f.data, f.observacao,
                        m.id AS motivo_id, m.descricao AS motivo
                 FROM fechamentos f
                 JOIN motivos_fechamento m ON m.id = f.motivo_id
                 WHERE f.semana_id = ?
                 ORDER BY f.data"
            );
            $stmt->bind_param('i', $semana_id);
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        case 'POST':
            $body      = json_decode(file_get_contents('php://input'), true);
            $semana_id = intval($body['semana_id'] ?? 0);
            $data      = trim($body['data']        ?? '');
            $motivo_id = intval($body['motivo_id'] ?? 0);
            $obs       = trim($body['observacao']  ?? '');
            if (!$semana_id || !$data || !$motivo_id) {
                http_response_code(422);
                echo json_encode(['erro' => 'semana_id, data e motivo_id são obrigatórios.']);
                break;
            }
            $stmt = $conn->prepare(
                "INSERT INTO fechamentos (semana_id, data, motivo_id, observacao) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('isis', $semana_id, $data, $motivo_id, $obs);
            $stmt->execute();
            echo json_encode(['id' => $conn->insert_id, 'mensagem' => 'Fechamento registrado.']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id inválido.']); break; }
            $stmt = $conn->prepare("DELETE FROM fechamentos WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Fechamento removido.']);
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
