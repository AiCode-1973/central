<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {
        case 'GET':
            $stmt = $conn->prepare(
                "SELECT id, descricao, ativo, criado_em
                 FROM motivos_fechamento ORDER BY descricao"
            );
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true);
            $desc = trim($body['descricao'] ?? '');
            if (!$desc) {
                http_response_code(422);
                echo json_encode(['erro' => 'Descrição é obrigatória.']);
                break;
            }
            $stmt = $conn->prepare("INSERT INTO motivos_fechamento (descricao) VALUES (?)");
            $stmt->bind_param('s', $desc);
            $stmt->execute();
            echo json_encode(['id' => $conn->insert_id, 'mensagem' => 'Motivo cadastrado.']);
            break;

        case 'PUT':
            $body = json_decode(file_get_contents('php://input'), true);
            $id   = intval($body['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id inválido.']);
                break;
            }
            if (isset($body['ativo'])) {
                $ativo = $body['ativo'] ? 1 : 0;
                $stmt  = $conn->prepare("UPDATE motivos_fechamento SET ativo = ? WHERE id = ?");
                $stmt->bind_param('ii', $ativo, $id);
            } else {
                $desc = trim($body['descricao'] ?? '');
                $stmt = $conn->prepare("UPDATE motivos_fechamento SET descricao = ? WHERE id = ?");
                $stmt->bind_param('si', $desc, $id);
            }
            $stmt->execute();
            echo json_encode(['mensagem' => 'Motivo atualizado.']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id inválido.']); break; }
            $stmt = $conn->prepare("DELETE FROM motivos_fechamento WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Motivo removido.']);
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
