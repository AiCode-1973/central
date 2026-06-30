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
            $stmt = $conn->prepare(
                "SELECT id, data_inicio, data_fim, descricao, criado_em
                 FROM semanas ORDER BY data_inicio DESC"
            );
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true);
            $di   = trim($body['data_inicio'] ?? '');
            $df   = trim($body['data_fim']    ?? '');
            $desc = trim($body['descricao']   ?? '');
            if (!$di || !$df) {
                http_response_code(422);
                echo json_encode(['erro' => 'data_inicio e data_fim são obrigatórios.']);
                break;
            }
            $stmt = $conn->prepare(
                "INSERT INTO semanas (data_inicio, data_fim, descricao) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('sss', $di, $df, $desc);
            $stmt->execute();
            echo json_encode(['id' => $conn->insert_id, 'mensagem' => 'Semana cadastrada.']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id inválido.']); break; }
            $stmt = $conn->prepare("DELETE FROM semanas WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Semana removida.']);
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
