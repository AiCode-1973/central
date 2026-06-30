<?php
require_once __DIR__ . '/config/auth.php';
logoutUsuario();
header('Location: login.php');
exit;
