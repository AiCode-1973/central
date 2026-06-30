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
    return $conn;
}
