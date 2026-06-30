<?php

define('DB_HOST', '186.209.113.107');
define('DB_USER', 'dema5738_central');
define('DB_PASS', 'Dema@1973');
define('DB_NAME', 'dema5738_central');
define('DB_PORT', 3306);

function getConnection(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
    }

    $conn->set_charset('utf8mb4');
    _criarTabelas($conn);
    return $conn;
}

function _criarTabelas(mysqli $conn): void {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $conn->query("CREATE TABLE IF NOT EXISTS semanas (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        data_inicio DATE        NOT NULL,
        data_fim    DATE        NOT NULL,
        descricao   VARCHAR(100),
        criado_em   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_semana_inicio (data_inicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS motivos_fechamento (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(255) NOT NULL,
        ativo     TINYINT(1)  DEFAULT 1,
        criado_em TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS fechamentos (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        semana_id INT           NOT NULL,
        motivo_id INT           NOT NULL,
        total     INT UNSIGNED  NOT NULL DEFAULT 1,
        observacao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_fech_semana_motivo (semana_id, motivo_id),
        CONSTRAINT fk_fech_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE,
        CONSTRAINT fk_fech_motivo FOREIGN KEY (motivo_id) REFERENCES motivos_fechamento(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migração: adiciona coluna total se a tabela já existia sem ela
    $conn->query("ALTER TABLE fechamentos ADD COLUMN IF NOT EXISTS total INT UNSIGNED NOT NULL DEFAULT 1");
    // Migração: remove coluna data se ainda existir (estrutura antiga)
    $cols = $conn->query("SHOW COLUMNS FROM fechamentos LIKE 'data'");
    if ($cols && $cols->num_rows > 0) {
        // Remove FK e coluna data da estrutura antiga
        $conn->query("ALTER TABLE fechamentos DROP FOREIGN KEY fk_fech_semana");
        $conn->query("ALTER TABLE fechamentos DROP FOREIGN KEY fk_fech_motivo");
        $conn->query("ALTER TABLE fechamentos DROP COLUMN data");
        $conn->query("ALTER TABLE fechamentos DROP INDEX IF EXISTS uk_fech_semana_motivo");
        $conn->query("ALTER TABLE fechamentos ADD UNIQUE KEY uk_fech_semana_motivo (semana_id, motivo_id)");
        $conn->query("ALTER TABLE fechamentos ADD CONSTRAINT fk_fech_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE");
        $conn->query("ALTER TABLE fechamentos ADD CONSTRAINT fk_fech_motivo FOREIGN KEY (motivo_id) REFERENCES motivos_fechamento(id) ON DELETE RESTRICT");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS atendimentos (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS horarios_pico (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        semana_id          INT          NOT NULL,
        data               DATE         NOT NULL,
        hora               CHAR(5)      NOT NULL,
        total_atendimentos INT UNSIGNED DEFAULT 0,
        criado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_pico (semana_id, data, hora),
        CONSTRAINT fk_pico_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS pesquisa_satisfacao (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        semana_id INT          NOT NULL,
        pessimo   INT UNSIGNED DEFAULT 0,
        ruim      INT UNSIGNED DEFAULT 0,
        neutro    INT UNSIGNED DEFAULT 0,
        bom       INT UNSIGNED DEFAULT 0,
        excelente INT UNSIGNED DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_pesquisa_semana (semana_id),
        CONSTRAINT fk_pesquisa_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS usuarios (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        nome           VARCHAR(100) NOT NULL,
        email          VARCHAR(150) NOT NULL,
        senha          VARCHAR(255) NOT NULL,
        perfil         ENUM('admin','operador','visualizador') NOT NULL DEFAULT 'operador',
        ativo          TINYINT(1)   DEFAULT 1,
        ultimo_acesso  DATETIME     DEFAULT NULL,
        criado_em      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_usuario_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Cria admin padrão se não houver nenhum usuário
    $chk = $conn->query("SELECT COUNT(*) AS c FROM usuarios");
    if ($chk && $chk->fetch_assoc()['c'] == 0) {
        $hash  = password_hash('Admin@123', PASSWORD_DEFAULT);
        $nome  = 'Administrador';
        $email = 'admin@hospital.com';
        $stmtAdmin = $conn->prepare(
            "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, 'admin')"
        );
        $stmtAdmin->bind_param('sss', $nome, $email, $hash);
        $stmtAdmin->execute();
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}
