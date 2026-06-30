<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
$_au = requirePerfil(['admin']);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {

        /* ── LISTAR ───────────────────────────────────────────── */
        case 'GET':
            $stmt = $conn->prepare(
                "SELECT id, slug, label, descricao, ativo,
                        DATE_FORMAT(criado_em,'%d/%m/%Y') AS criado_em
                 FROM perfis ORDER BY label"
            );
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        /* ── CRIAR ────────────────────────────────────────────── */
        case 'POST':
            $body      = json_decode(file_get_contents('php://input'), true);
            $slug      = trim(preg_replace('/[^a-z0-9_]/', '', strtolower($body['slug']  ?? '')));
            $label     = trim($body['label']     ?? '');
            $descricao = trim($body['descricao'] ?? '');

            if (!$slug || !$label) {
                http_response_code(422);
                echo json_encode(['erro' => 'Slug e label são obrigatórios.']);
                break;
            }

            $stmt = $conn->prepare(
                "INSERT INTO perfis (slug, label, descricao) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('sss', $slug, $label, $descricao);
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    http_response_code(409);
                    echo json_encode(['erro' => "Slug '$slug' já existe."]);
                } else {
                    throw new RuntimeException($conn->error);
                }
                break;
            }
            echo json_encode(['mensagem' => 'Perfil criado.', 'id' => $conn->insert_id]);
            break;

        /* ── ATUALIZAR ────────────────────────────────────────── */
        case 'PUT':
            $id        = intval($_GET['id'] ?? 0);
            $body      = json_decode(file_get_contents('php://input'), true);
            $label     = trim($body['label']     ?? '');
            $descricao = trim($body['descricao'] ?? '');
            $ativo     = isset($body['ativo']) ? (int)(bool)$body['ativo'] : null;

            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }

            // Modo toggle ativo
            if ($ativo !== null && !$label) {
                // Impede desativar 'admin'
                $chk = $conn->prepare("SELECT slug FROM perfis WHERE id = ?");
                $chk->bind_param('i', $id);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                if ($row && $row['slug'] === 'admin' && !$ativo) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Não é possível desativar o perfil admin.']);
                    break;
                }
                $stmt = $conn->prepare("UPDATE perfis SET ativo = ? WHERE id = ?");
                $stmt->bind_param('ii', $ativo, $id);
                $stmt->execute();
                echo json_encode(['mensagem' => 'Status atualizado.']);
                break;
            }

            if (!$label) {
                http_response_code(422);
                echo json_encode(['erro' => 'Label é obrigatório.']);
                break;
            }

            $ativoVal = $ativo ?? 1;
            $stmt = $conn->prepare(
                "UPDATE perfis SET label = ?, descricao = ?, ativo = ? WHERE id = ?"
            );
            $stmt->bind_param('ssii', $label, $descricao, $ativoVal, $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Perfil atualizado.']);
            break;

        /* ── EXCLUIR ──────────────────────────────────────────── */
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }

            // Bloqueia exclusão de perfis padrão
            $chk = $conn->prepare("SELECT slug FROM perfis WHERE id = ?");
            $chk->bind_param('i', $id);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            if ($row && in_array($row['slug'], ['admin', 'operador', 'visualizador'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Perfis padrão não podem ser excluídos.']);
                break;
            }

            // Bloqueia se houver usuários usando o perfil
            $slugRow = $row['slug'] ?? '';
            $inUso = $conn->prepare("SELECT COUNT(*) AS c FROM usuarios WHERE perfil = ?");
            $inUso->bind_param('s', $slugRow);
            $inUso->execute();
            if ($inUso->get_result()->fetch_assoc()['c'] > 0) {
                http_response_code(400);
                echo json_encode(['erro' => 'Perfil em uso por usuários. Reatribua-os antes de excluir.']);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM perfis WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Perfil excluído.']);
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
