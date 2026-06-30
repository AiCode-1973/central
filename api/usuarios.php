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
                "SELECT id, nome, email, perfil, ativo,
                        DATE_FORMAT(ultimo_acesso,'%d/%m/%Y %H:%i') AS ultimo_acesso,
                        DATE_FORMAT(criado_em,'%d/%m/%Y')           AS criado_em
                 FROM usuarios ORDER BY nome"
            );
            $stmt->execute();
            echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            break;

        /* ── CRIAR ────────────────────────────────────────────── */
        case 'POST':
            $body  = json_decode(file_get_contents('php://input'), true);
            $nome  = trim($body['nome']  ?? '');
            $email = trim($body['email'] ?? '');
            $senha = $body['senha'] ?? '';
            $perfil = $body['perfil'] ?? 'operador';

            if (!$nome || !$email || !$senha) {
                http_response_code(422);
                echo json_encode(['erro' => 'Nome, e-mail e senha são obrigatórios.']);
                break;
            }
            if (strlen($senha) < 6) {
                http_response_code(422);
                echo json_encode(['erro' => 'Senha deve ter no mínimo 6 caracteres.']);
                break;
            }
            // Valida perfil contra tabela
            $chkP = $conn->prepare("SELECT id FROM perfis WHERE slug = ? AND ativo = 1 LIMIT 1");
            $chkP->bind_param('s', $perfil);
            $chkP->execute();
            if (!$chkP->get_result()->fetch_assoc()) {
                http_response_code(422);
                echo json_encode(['erro' => 'Perfil inválido ou inativo.']);
                break;
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $nome, $email, $hash, $perfil);
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'E-mail já cadastrado.']);
                } else {
                    throw new RuntimeException($conn->error);
                }
                break;
            }
            echo json_encode(['mensagem' => 'Usuário criado.', 'id' => $conn->insert_id]);
            break;

        /* ── ATUALIZAR ────────────────────────────────────────── */
        case 'PUT':
            $id   = intval($_GET['id'] ?? 0);
            $body = json_decode(file_get_contents('php://input'), true);

            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }

            // Modo: alterar senha
            if (isset($body['senha'])) {
                $nova = $body['senha'] ?? '';
                if (strlen($nova) < 6) {
                    http_response_code(422);
                    echo json_encode(['erro' => 'Senha deve ter no mínimo 6 caracteres.']);
                    break;
                }
                $hash = password_hash($nova, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->bind_param('si', $hash, $id);
                $stmt->execute();
                echo json_encode(['mensagem' => 'Senha alterada.']);
                break;
            }

            // Modo: atualizar dados gerais
            $nome   = trim($body['nome']   ?? '');
            $email  = trim($body['email']  ?? '');
            $perfil = $body['perfil'] ?? '';
            $ativo  = isset($body['ativo']) ? (int)(bool)$body['ativo'] : null;

            if (!$nome || !$email) {
                http_response_code(422);
                echo json_encode(['erro' => 'Nome e e-mail são obrigatórios.']);
                break;
            }
            // Valida perfil contra tabela
            $chkP = $conn->prepare("SELECT id FROM perfis WHERE slug = ? AND ativo = 1 LIMIT 1");
            $chkP->bind_param('s', $perfil);
            $chkP->execute();
            if (!$chkP->get_result()->fetch_assoc()) {
                http_response_code(422);
                echo json_encode(['erro' => 'Perfil inválido ou inativo.']);
                break;
            }

            // Impede que o admin se rebaixe ou se desative
            if ($id === $_au['id']) {
                $ativo  = 1;
                $perfil = 'admin';
            }

            $ativoVal = $ativo ?? 1;
            $stmt = $conn->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, perfil = ?, ativo = ? WHERE id = ?"
            );
            $stmt->bind_param('sssii', $nome, $email, $perfil, $ativoVal, $id);
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    http_response_code(409);
                    echo json_encode(['erro' => 'E-mail já em uso por outro usuário.']);
                } else {
                    throw new RuntimeException($conn->error);
                }
                break;
            }
            echo json_encode(['mensagem' => 'Usuário atualizado.']);
            break;

        /* ── EXCLUIR ──────────────────────────────────────────── */
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(422);
                echo json_encode(['erro' => 'id obrigatório.']);
                break;
            }
            if ($id === $_au['id']) {
                http_response_code(400);
                echo json_encode(['erro' => 'Não é possível excluir o próprio usuário.']);
                break;
            }
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Usuário excluído.']);
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
