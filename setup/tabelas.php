<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');

$conn = getConnection();

// Desativa verificação de FK para garantir criação sem dependências
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tabelas = [
    'semanas' =>
        "CREATE TABLE IF NOT EXISTS semanas (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            data_inicio DATE        NOT NULL,
            data_fim    DATE        NOT NULL,
            descricao   VARCHAR(100),
            criado_em   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_semana_inicio (data_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'motivos_fechamento' =>
        "CREATE TABLE IF NOT EXISTS motivos_fechamento (
            id        INT AUTO_INCREMENT PRIMARY KEY,
            descricao VARCHAR(255) NOT NULL,
            ativo     TINYINT(1)  DEFAULT 1,
            criado_em TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'fechamentos' =>
        "CREATE TABLE IF NOT EXISTS fechamentos (
            id        INT AUTO_INCREMENT PRIMARY KEY,
            semana_id INT  NOT NULL,
            data      DATE NOT NULL,
            motivo_id INT  NOT NULL,
            observacao TEXT,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_fech_semana  FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE,
            CONSTRAINT fk_fech_motivo  FOREIGN KEY (motivo_id) REFERENCES motivos_fechamento(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'atendimentos' =>
        "CREATE TABLE IF NOT EXISTS atendimentos (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            semana_id        INT          NOT NULL,
            data             DATE         NOT NULL,
            total_agendados  INT UNSIGNED DEFAULT 0,
            total_atendidos  INT UNSIGNED DEFAULT 0,
            total_cancelados INT UNSIGNED DEFAULT 0,
            total_faltas     INT UNSIGNED DEFAULT 0,
            observacao       TEXT,
            criado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_atend_semana_data (semana_id, data),
            CONSTRAINT fk_atend_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'horarios_pico' =>
        "CREATE TABLE IF NOT EXISTS horarios_pico (
            id                 INT AUTO_INCREMENT PRIMARY KEY,
            semana_id          INT          NOT NULL,
            data               DATE         NOT NULL,
            hora               CHAR(5)      NOT NULL,
            total_atendimentos INT UNSIGNED DEFAULT 0,
            criado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_pico (semana_id, data, hora),
            CONSTRAINT fk_pico_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$resultados = [];
foreach ($tabelas as $nome => $sql) {
    // Tenta com utf8mb4; se falhar, tenta com utf8 simples
    if ($conn->query($sql)) {
        $resultados[$nome] = ['status' => 'OK', 'msg' => 'Criada / verificada com sucesso'];
    } else {
        $erro1 = $conn->error;
        $sqlFallback = str_replace(
            'utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'utf8 COLLATE=utf8_general_ci',
            $sql
        );
        if ($conn->query($sqlFallback)) {
            $resultados[$nome] = ['status' => 'OK', 'msg' => 'Criada com charset utf8 (fallback)'];
        } else {
            $resultados[$nome] = ['status' => 'ERRO', 'msg' => $erro1];
        }
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
$conn->close();
$tudo_ok = !in_array('ERRO', array_column($resultados, 'status'));
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — Central de Agendamento</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:680px;">
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center gap-2" style="background:#003366;">
            <i class="fas fa-hospital-alt text-white fa-lg"></i>
            <span class="text-white fw-bold">Hospital Santo Expedito — Setup do Banco de Dados</span>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-server me-1"></i>
                Host: <code>186.209.113.107</code> &nbsp;|&nbsp;
                Banco: <code>dema5738_central</code>
            </p>
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr><th>Tabela</th><th>Status</th><th>Mensagem</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $nome => $r): ?>
                    <tr class="<?= $r['status'] === 'OK' ? 'table-success' : 'table-danger' ?>">
                        <td><code><?= htmlspecialchars($nome) ?></code></td>
                        <td>
                            <?php if ($r['status'] === 'OK'): ?>
                                <i class="fas fa-check-circle text-success"></i> OK
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> ERRO
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($r['msg']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($tudo_ok): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <strong>Todas as tabelas foram criadas com sucesso!</strong>
                </div>
                <a href="../index.php" class="btn btn-success w-100">
                    <i class="fas fa-chart-line me-2"></i>Ir para o Dashboard
                </a>
            <?php else: ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                    Verifique os erros acima e tente novamente.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
