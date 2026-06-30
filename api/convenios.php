<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin(true);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {

        /* ── LISTAR ───────────────────────────────────────────── */
        case 'GET':
            $somenteAtivos = isset($_GET['ativos']);
            if ($somenteAtivos) {
                $stmt = $conn->prepare("SELECT id, nome FROM convenios WHERE ativo = 1 ORDER BY nome");
            } else {
                $stmt = $conn->prepare(
                    "SELECT id, nome, ativo,
                            DATE_FORMAT(criado_em,'%d/%m/%Y') AS criado_em
                     FROM convenios ORDER BY nome"
                );
            }
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        /* ── CRIAR ────────────────────────────────────────────── */
        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true);
            $nome = trim($body['nome'] ?? '');
            if (!$nome) {
                http_response_code(422);
                echo json_encode(['erro' => 'Nome é obrigatório.']);
                break;
            }
            $stmt = $conn->prepare("INSERT INTO convenios (nome) VALUES (?)");
            $stmt->bind_param('s', $nome);
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'Convênio já cadastrado.']);
                } else {
                    throw new RuntimeException($conn->error);
                }
                break;
            }
            echo json_encode(['mensagem' => 'Convênio criado.', 'id' => $conn->insert_id]);
            break;

        /* ── ATUALIZAR ────────────────────────────────────────── */
        case 'PUT':
            $id   = intval($_GET['id'] ?? 0);
            $body = json_decode(file_get_contents('php://input'), true);
            $nome = trim($body['nome'] ?? '');
            $ativo = isset($body['ativo']) ? (int)(bool)$body['ativo'] : null;

            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }
            if ($nome !== '') {
                if ($ativo !== null) {
                    $stmt = $conn->prepare("UPDATE convenios SET nome = ?, ativo = ? WHERE id = ?");
                    $stmt->bind_param('sii', $nome, $ativo, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE convenios SET nome = ? WHERE id = ?");
                    $stmt->bind_param('si', $nome, $id);
                }
            } else {
                // apenas toggle ativo
                if ($ativo === null) {
                    http_response_code(422);
                    echo json_encode(['erro' => 'Nenhum dado para atualizar.']);
                    break;
                }
                $stmt = $conn->prepare("UPDATE convenios SET ativo = ? WHERE id = ?");
                $stmt->bind_param('ii', $ativo, $id);
            }
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'Nome já em uso.']);
                } else {
                    throw new RuntimeException($conn->error);
                }
                break;
            }
            echo json_encode(['mensagem' => 'Convênio atualizado.']);
            break;

        /* ── EXCLUIR ──────────────────────────────────────────── */
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }
            // Verifica se está em uso
            $chk = $conn->prepare("SELECT COUNT(*) AS c FROM autorizacoes WHERE convenio_id = ?");
            $chk->bind_param('i', $id);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()['c'] > 0) {
                http_response_code(400);
                echo json_encode(['erro' => 'Convênio possui autorizações vinculadas. Desative em vez de excluir.']);
                break;
            }
            $stmt = $conn->prepare("DELETE FROM convenios WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Convênio excluído.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
} finally {
    $conn->close();
}
