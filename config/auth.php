<?php

function _authStartSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Retorna o usuário da sessão ou null.
 */
function usuarioLogado(): ?array {
    _authStartSession();
    return $_SESSION['usuario'] ?? null;
}

/**
 * Exige autenticação.
 * $asJson = true → retorna JSON 401 (para APIs).
 * $asJson = false → redireciona para login.php (para páginas HTML).
 */
function requireLogin(bool $asJson = false): array {
    _authStartSession();
    if (empty($_SESSION['usuario'])) {
        if ($asJson) {
            http_response_code(401);
            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
    return $_SESSION['usuario'];
}

/**
 * Exige que o usuário tenha um dos perfis informados.
 * Sempre retorna JSON em caso de erro (uso interno das APIs).
 */
function requirePerfil(array $perfis): array {
    $u = requireLogin(true);
    if (!in_array($u['perfil'], $perfis, true)) {
        http_response_code(403);
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Acesso negado. Perfil insuficiente.']);
        exit;
    }
    return $u;
}

/**
 * Carrega permissões de um perfil do banco.
 * Admin sempre retorna todos os módulos.
 */
function carregarPermissoes(string $perfil, mysqli $conn): array {
    if ($perfil === 'admin') {
        return ['dashboard','atendimentos','picos','fechamentos','motivos','semanas','pesquisa','usuarios','autorizacoes','convenios','procedimentos','autorizar_exames'];
    }
    $stmt = $conn->prepare(
        "SELECT modulo FROM perfis_permissoes WHERE perfil_slug = ? ORDER BY modulo"
    );
    $stmt->bind_param('s', $perfil);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return array_column($rows, 'modulo');
}

/**
 * Inicia sessão com os dados do usuário autenticado.
 */
function loginUsuario(array $u, mysqli $conn): void {
    _authStartSession();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'          => (int)$u['id'],
        'nome'        => $u['nome'],
        'email'       => $u['email'],
        'perfil'      => $u['perfil'],
        'permissoes'  => carregarPermissoes($u['perfil'], $conn),
    ];
}

/**
 * Encerra a sessão completamente.
 */
function logoutUsuario(): void {
    _authStartSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'] ?? false, $p['httponly'] ?? true
        );
    }
    session_destroy();
}
