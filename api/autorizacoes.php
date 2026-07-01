<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
$_autUser = requireLogin(true);
require_once __DIR__ . '/../config/db.php';

define('UPLOAD_DIR', __DIR__ . '/../uploads/pedidos/');
define('UPLOAD_MAX_MB', 10);
define('UPLOAD_TIPOS', ['application/pdf','image/jpeg','image/png','image/webp']);

$method = $_SERVER['REQUEST_METHOD'];
// PHP não popula $_POST/$_FILES em PUT multipart; usamos POST + _method=PUT
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT') {
    $method = 'PUT';
}
$conn = getConnection();

// Verifica se o usuário pode alterar o status (permissão autorizar_exames)
$_podeAutorizar = in_array('autorizar_exames', $_autUser['permissoes'] ?? [], true);

/* ── helper: salvar múltiplos arquivos ───────────────────── */
function salvarArquivos(): array {
    if (empty($_FILES['pedido_arquivo']) || !isset($_FILES['pedido_arquivo']['name'])) {
        return [];
    }
    $f = $_FILES['pedido_arquivo'];
    // Normaliza para array (múltiplos files vêm como arrays de arrays)
    $names    = is_array($f['name'])     ? $f['name']     : [$f['name']];
    $tmps     = is_array($f['tmp_name']) ? $f['tmp_name'] : [$f['tmp_name']];
    $errors   = is_array($f['error'])    ? $f['error']    : [$f['error']];
    $sizes    = is_array($f['size'])     ? $f['size']     : [$f['size']];

    $salvos = [];
    foreach ($names as $i => $name) {
        if ($errors[$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($errors[$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erro no upload de "' . htmlspecialchars($name) . '" (código ' . $errors[$i] . ').');
        }
        if ($sizes[$i] > UPLOAD_MAX_MB * 1024 * 1024) {
            throw new RuntimeException('"' . htmlspecialchars($name) . '" excede ' . UPLOAD_MAX_MB . ' MB.');
        }
        $tipo = mime_content_type($tmps[$i]);
        if (!in_array($tipo, UPLOAD_TIPOS, true)) {
            throw new RuntimeException('"' . htmlspecialchars($name) . '" tem tipo não permitido. Use PDF, JPG ou PNG.');
        }
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $filename = uniqid('pedido_', true) . '.' . $ext;
        if (!move_uploaded_file($tmps[$i], UPLOAD_DIR . $filename)) {
            throw new RuntimeException('Não foi possível salvar "' . htmlspecialchars($name) . '".');
        }
        $salvos[] = $filename;
    }
    return $salvos;
}

function excluirArquivos(array $filenames): void {
    foreach ($filenames as $f) {
        if ($f && file_exists(UPLOAD_DIR . $f)) @unlink(UPLOAD_DIR . $f);
    }
}

function excluirArquivo(?string $filename): void {
    if ($filename) excluirArquivos([$filename]);
}

/* ── decodifica pedido_arquivo (suporta JSON array e string legada) ── */
function decodificarArquivos(?string $valor): array {
    if (!$valor) return [];
    $arr = json_decode($valor, true);
    if (is_array($arr)) return $arr;
    return [$valor]; // legado: string simples
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
            if (!$_podeAutorizar) $status = 'pendente'; // sem permissão: sempre pendente

            $novosArquivos = salvarArquivos();
            $arquivoJson  = $novosArquivos ? json_encode($novosArquivos) : null;

            $stmt = $conn->prepare(
                "INSERT INTO autorizacoes
                    (convenio_id, paciente_nome, paciente_cpf, paciente_telefone,
                     data_agendamento, procedimento_id, pedido_arquivo, status, observacao)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param('issssisss', $convId, $nome, $cpf, $tel, $dtAg, $procId, $arquivoJson, $status, $obs);
            if (!$stmt->execute()) { throw new RuntimeException($conn->error); }
            echo json_encode(['mensagem' => 'Autorização criada.', 'id' => $conn->insert_id]);
            break;

        /* ── ATUALIZAR ────────────────────────────────────────── */
        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['erro' => 'id obrigatório.']); break; }

            // Dados sempre vêm via $_POST (enviado como POST + _method=PUT pelo JS)
            $nome   = trim($_POST['paciente_nome']     ?? '');
            $cpf    = trim($_POST['paciente_cpf']      ?? '');
            $tel    = trim($_POST['paciente_telefone'] ?? '');
            $dtAg   = trim($_POST['data_agendamento']  ?? '');
            $convId = intval($_POST['convenio_id']     ?? 0);
            $procId = intval($_POST['procedimento_id'] ?? 0);
            $status = $_POST['status']                 ?? 'pendente';
            $obs    = trim($_POST['observacao']        ?? '');

            if (!$nome || !$dtAg || !$convId || !$procId) {
                http_response_code(422);
                echo json_encode(['erro' => 'Campos obrigatórios faltando.']);
                break;
            }
            if (!in_array($status, ['pendente','autorizado','negado'], true)) $status = 'pendente';
            if (!$_podeAutorizar) $status = 'pendente'; // sem permissão: sempre pendente

            // Busca arquivos atuais
            $curRow = $conn->query("SELECT pedido_arquivo FROM autorizacoes WHERE id = $id")->fetch_assoc();
            $arquivosAtuais = decodificarArquivos($curRow['pedido_arquivo'] ?? null);

            $novosArquivos = salvarArquivos();
            if ($novosArquivos) {
                // Substitui: exclui os antigos e usa os novos
                excluirArquivos($arquivosAtuais);
                $arquivoFinalJson = json_encode($novosArquivos);
            } else {
                // Mantém os arquivos existentes
                $arquivoFinalJson = $curRow['pedido_arquivo'] ?? null;
            }

            $stmt = $conn->prepare(
                "UPDATE autorizacoes
                 SET convenio_id=?, paciente_nome=?, paciente_cpf=?, paciente_telefone=?,
                     data_agendamento=?, procedimento_id=?, pedido_arquivo=?, status=?, observacao=?
                 WHERE id=?"
            );
            $stmt->bind_param('issssisssi', $convId, $nome, $cpf, $tel, $dtAg, $procId, $arquivoFinalJson, $status, $obs, $id);
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
            if ($stmt->affected_rows > 0) excluirArquivos(decodificarArquivos($curRow['pedido_arquivo'] ?? null));
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
