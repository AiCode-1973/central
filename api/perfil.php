<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
$_au = requireLogin(true);
require_once __DIR__ . '/../config/db.php';
$conn = getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $body        = json_decode(file_get_contents('php://input'), true);
    $senhaAtual  = $body['senha_atual']    ?? '';
    $novaSenha   = $body['nova_senha']     ?? '';
    $confirmacao = $body['confirmacao']    ?? '';

    if (!$senhaAtual || !$novaSenha || !$confirmacao) {
        http_response_code(422);
        echo json_encode(['erro' => 'Todos os campos são obrigatórios.']);
        exit;
    }
    if (strlen($novaSenha) < 6) {
        http_response_code(422);
        echo json_encode(['erro' => 'A nova senha deve ter no mínimo 6 caracteres.']);
        exit;
    }
    if ($novaSenha !== $confirmacao) {
        http_response_code(422);
        echo json_encode(['erro' => 'A nova senha e a confirmação não coincidem.']);
        exit;
    }

    // Busca hash atual
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ? AND ativo = 1");
    $stmt->bind_param('i', $_au['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['erro' => 'Usuário não encontrado.']);
        exit;
    }

    if (!password_verify($senhaAtual, $row['senha'])) {
        http_response_code(403);
        echo json_encode(['erro' => 'Senha atual incorreta.']);
        exit;
    }

    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $upd->bind_param('si', $novoHash, $_au['id']);
    $upd->execute();

    echo json_encode(['mensagem' => 'Senha alterada com sucesso.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno.']);
}
