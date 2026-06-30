<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn   = getConnection();

try {
    switch ($method) {

        case 'GET':
            $semana_id = intval($_GET['semana_id'] ?? 0);
            if (!$semana_id) { echo json_encode(null); break; }
            $stmt = $conn->prepare(
                "SELECT id, semana_id, pessimo, ruim, neutro, bom, excelente
                 FROM pesquisa_satisfacao WHERE semana_id = ? LIMIT 1"
            );
            $stmt->bind_param('i', $semana_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            echo json_encode($row ?: null);
            break;

        case 'POST':
            $body      = json_decode(file_get_contents('php://input'), true);
            $semana_id = intval($body['semana_id'] ?? 0);
            $pessimo   = max(0, intval($body['pessimo']   ?? 0));
            $ruim      = max(0, intval($body['ruim']      ?? 0));
            $neutro    = max(0, intval($body['neutro']    ?? 0));
            $bom       = max(0, intval($body['bom']       ?? 0));
            $excelente = max(0, intval($body['excelente'] ?? 0));

            if (!$semana_id) {
                http_response_code(422);
                echo json_encode(['erro' => 'semana_id obrigatório.']);
                break;
            }

            $stmt = $conn->prepare(
                "INSERT INTO pesquisa_satisfacao (semana_id, pessimo, ruim, neutro, bom, excelente)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    pessimo   = VALUES(pessimo),
                    ruim      = VALUES(ruim),
                    neutro    = VALUES(neutro),
                    bom       = VALUES(bom),
                    excelente = VALUES(excelente)"
            );
            $stmt->bind_param('iiiiii', $semana_id, $pessimo, $ruim, $neutro, $bom, $excelente);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Pesquisa salva com sucesso.']);
            break;

        case 'DELETE':
            $semana_id = intval($_GET['semana_id'] ?? 0);
            if (!$semana_id) {
                http_response_code(422);
                echo json_encode(['erro' => 'semana_id obrigatório.']);
                break;
            }
            $stmt = $conn->prepare("DELETE FROM pesquisa_satisfacao WHERE semana_id = ?");
            $stmt->bind_param('i', $semana_id);
            $stmt->execute();
            echo json_encode(['mensagem' => 'Pesquisa removida.']);
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
