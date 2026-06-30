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
        semana_id INT  NOT NULL,
        data      DATE NOT NULL,
        motivo_id INT  NOT NULL,
        observacao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_fech_semana FOREIGN KEY (semana_id) REFERENCES semanas(id) ON DELETE CASCADE,
        CONSTRAINT fk_fech_motivo FOREIGN KEY (motivo_id) REFERENCES motivos_fechamento(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}
