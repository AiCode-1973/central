<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
requireLogin(true);
require_once __DIR__ . '/../config/db.php';

define('UPLOAD_DIR', __DIR__ . '/../uploads/pedidos/');
define('UPLOAD_MAX_MB', 10);
define('UPLOAD_TIPOS', ['application/pdf','image/jpeg','image/png','image/webp']);

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

/* ── helper: salvar arquivo ───────────────────────────────── */
function salvarArquivo(): ?string {
    if (empty($_FILES['pedido_arquivo']) || $_FILES['pedido_arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES['pedido_arquivo'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro no upload do arquivo (código ' . $f['error'] . ').');
    }
    if ($f['size'] > UPLOAD_MAX_MB * 1024 * 1024) {
        throw new RuntimeException('Arquivo muito grande. Máximo ' . UPLOAD_MAX_MB . ' MB.');
    }
    $tipo = mime_content_type($f['tmp_name']);
    if (!in_array($tipo, UPLOAD_TIPOS, true)) {
        throw new RuntimeException('Tipo de arquivo não permitido. Use PDF, JPG ou PNG.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $ext      = pathinfo($f['name'], PATHINFO_EXTENSION);
    $filename = uniqid('pedido_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }
    return $filename;
}

function excluirArquivo(?string $filename): void {
    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
        @unlink(UPLOAD_DIR . $filename);
    }
}

try {
    switch ($method) {

        /* ── LISTAR ───────────────────────────────────────────── */
        case 'GET':
            if (isset($_GET['id'])) {
                $id   = intval($_GET['id']);
                $stmt = $conn->prepare(
                    "SELECT a.*, c.nome AS convenio_nome, p.nome AS procedimento_nome
                     FROM autorizacoes a
                     JOIN convenios   c ON c.id = a.convenio_id
                     JOIN procedimentos p ON p.id = a.procedimento_id
                     WHERE a.id = ?"
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if (!$row) { http_response_code(404); echo json_encode(['erro' => 'Não encontrado.']); break; }
                echo json_encode($row);
            } else {
                $stmt = $conn->prepare(
                    "SELECT a.id, a.paciente_nome, a.paciente_cpf, a.paciente_telefone,
                            DATE_FORMAT(a.data_agendamento,'%d/%m/%Y') AS data_agendamento,
                            a.status, a.pedido_arquivo, a.observacao,
                            DATE_FORMAT(a.criado_em,'%d/%m/%Y %H:%i')     AS criado_em,
                            DATE_FORMAT(a.atualizado_em,'%d/%m/%Y %H:%i') AS atualizado_em,
                            c.id AS convenio_id,   c.nome AS convenio_nome,
                            p.id AS procedimento_id, p.nome AS procedimento_nome
                     FROM autorizacoes a
                     JOIN convenios    c ON c.id = a.convenio_id
                     JOIN procedimentos p ON p.id = a.procedimento_id
                     ORDER BY a.data_agendamento DESC, a.id DESC"
                );
                $stmt->execute();
                echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            }
            break;

        /* ── CRIAR ────────────────────────────────────────────── */
        case 'POST':
            $nome      = trim($_POST['paciente_nome']     ?? '');
            $cpf       = trim($_POST['paciente_cpf']      ?? '');
            $tel       = trim($_POST['paciente_telefone'] ?? '');
            $dtAg      = trim($_POST['data_agendamento']  ?? '');
            $convId    = intval($_POST['convenio_id']     ?? 0);
            $procId    = intval($_POST['procedimento_id'] ?? 0);
            $status    = $_POST['status']                 ?? 'pendente';
            $obs       = trim($_POST['observacao']        ?? '');

            if (!$nome || !$dtAg || !$convId || !$procId) {
                http_response_code(422);
                echo json_encode(['erro' => 'Nome do paciente, data, convênio e procedimento são obrigatórios.']);
                break;
            }
            if (!in_array($status, ['pendente','autorizado','negado'], true)) $status = 'pendente';

            $arquivo = salvarArquivo();

            $stmt = $conn->prepare(
                "INSERT INTO autorizacoes
                    (convenio_id, paciente_nome, paciente_cpf, paciente_telefone,
                     data_agendamento, procedimento_id, pedido_arquivo, status, observacao)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param('issssisss', $convId, $nome, $cpf, $tel, $dtAg, $procId, $arquivo, $status, $obs);
            if (!$stmt->execute()) { throw new RuntimeException($conn->error); }
            echo json_encode(['mensagem' => 'Autorização criada.', 'id' => $conn->insert_id]);
            break;

        /* ── ATUALIZAR ────────────────────────────────────────── */
        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id obrigatório.']); break; }

            // Suporta JSON (sem arquivo) ou multipart (com arquivo)
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'multipart/form-data')) {
                $nome   = trim($_POST['paciente_nome']     ?? '');
                $cpf    = trim($_POST['paciente_cpf']      ?? '');
                $tel    = trim($_POST['paciente_telefone'] ?? '');
                $dtAg   = trim($_POST['data_agendamento']  ?? '');
                $convId = intval($_POST['convenio_id']     ?? 0);
                $procId = intval($_POST['procedimento_id'] ?? 0);
                $status = $_POST['status']                 ?? 'pendente';
                $obs    = trim($_POST['observacao']        ?? '');
            } else {
                $body   = json_decode(file_get_contents('php://input'), true) ?? [];
                $nome   = trim($body['paciente_nome']     ?? '');
                $cpf    = trim($body['paciente_cpf']      ?? '');
                $tel    = trim($body['paciente_telefone'] ?? '');
                $dtAg   = trim($body['data_agendamento']  ?? '');
                $convId = intval($body['convenio_id']     ?? 0);
                $procId = intval($body['procedimento_id'] ?? 0);
                $status = $body['status']                 ?? 'pendente';
                $obs    = trim($body['observacao']        ?? '');
            }

            if (!$nome || !$dtAg || !$convId || !$procId) {
                http_response_code(422);
                echo json_encode(['erro' => 'Campos obrigatórios faltando.']);
                break;
            }
            if (!in_array($status, ['pendente','autorizado','negado'], true)) $status = 'pendente';

            // Busca arquivo atual
            $curRow = $conn->query("SELECT pedido_arquivo FROM autorizacoes WHERE id = $id")->fetch_assoc();
            $arquivoAtual = $curRow['pedido_arquivo'] ?? null;

            $novoArquivo = salvarArquivo();
            if ($novoArquivo !== null) {
                excluirArquivo($arquivoAtual);
                $arquivoFinal = $novoArquivo;
            } else {
                $arquivoFinal = $arquivoAtual;
            }

            $stmt = $conn->prepare(
                "UPDATE autorizacoes
                 SET convenio_id=?, paciente_nome=?, paciente_cpf=?, paciente_telefone=?,
                     data_agendamento=?, procedimento_id=?, pedido_arquivo=?, status=?, observacao=?
                 WHERE id=?"
            );
            $stmt->bind_param('issssisssi', $convId, $nome, $cpf, $tel, $dtAg, $procId, $arquivoFinal, $status, $obs, $id);
            if (!$stmt->execute()) { throw new RuntimeException($conn->error); }
            echo json_encode(['mensagem' => 'Autorização atualizada.']);
            break;

        /* ── EXCLUIR ──────────────────────────────────────────── */
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id obrigatório.']); break; }
            $curRow = $conn->query("SELECT pedido_arquivo FROM autorizacoes WHERE id = $id")->fetch_assoc();
            $stmt = $conn->prepare("DELETE FROM autorizacoes WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) excluirArquivo($curRow['pedido_arquivo'] ?? null);
            echo json_encode(['mensagem' => 'Autorização excluída.']);
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
