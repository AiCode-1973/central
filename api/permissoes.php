<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
$_au = requirePerfil(['admin']);
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {

        /* ── GET: permissões de um perfil ──────────────────────
           ?slug=operador → retorna array de módulos habilitados
        ────────────────────────────────────────────────────── */
        case 'GET':
            $slug = trim($_GET['slug'] ?? '');
            if (!$slug) {
                // Retorna mapa completo { slug: [modulos] }
                $res  = $conn->query("SELECT perfil_slug, modulo FROM perfis_permissoes ORDER BY perfil_slug, modulo");
                $mapa = [];
                while ($r = $res->fetch_assoc()) {
                    $mapa[$r['perfil_slug']][] = $r['modulo'];
                }
                echo json_encode($mapa);
            } else {
                $stmt = $conn->prepare(
                    "SELECT modulo FROM perfis_permissoes WHERE perfil_slug = ? ORDER BY modulo"
                );
                $stmt->bind_param('s', $slug);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(array_column($rows, 'modulo'));
            }
            break;

        /* ── POST: salva permissões de um perfil (replace) ─────
           Body: { slug: "operador", modulos: ["dashboard","atendimentos"] }
        ────────────────────────────────────────────────────── */
        case 'POST':
            $body    = json_decode(file_get_contents('php://input'), true);
            $slug    = trim($body['slug']    ?? '');
            $modulos = $body['modulos'] ?? [];

            if (!$slug) {
                http_response_code(422);
                echo json_encode(['erro' => 'slug obrigatório.']);
                break;
            }

            // Admin sempre tem todas — não deixa remover
            if ($slug === 'admin') {
                http_response_code(400);
                echo json_encode(['erro' => 'Permissões do perfil admin não podem ser alteradas.']);
                break;
            }

            $modulosValidos = ['dashboard','atendimentos','picos','fechamentos','motivos','semanas','pesquisa','usuarios','autorizacoes','convenios','procedimentos','autorizar_exames'];
            $modulos = array_values(array_intersect((array)$modulos, $modulosValidos));

            // Remove 'usuarios' de perfis não-admin (segurança)
            $modulos = array_values(array_diff($modulos, ['usuarios']));

            $conn->begin_transaction();
            $del = $conn->prepare("DELETE FROM perfis_permissoes WHERE perfil_slug = ?");
            $del->bind_param('s', $slug);
            $del->execute();

            if ($modulos) {
                $ins = $conn->prepare("INSERT IGNORE INTO perfis_permissoes (perfil_slug, modulo) VALUES (?, ?)");
                foreach ($modulos as $m) {
                    $ins->bind_param('ss', $slug, $m);
                    $ins->execute();
                }
            }
            $conn->commit();
            echo json_encode(['mensagem' => 'Permissões salvas.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    if ($conn->in_transaction ?? false) $conn->rollback();
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
} finally {
    $conn->close();
}
