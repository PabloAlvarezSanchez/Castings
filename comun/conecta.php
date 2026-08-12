<?php
// comun/conecta.php
require_once __DIR__ . '/config.php';

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Error de conexión: ' . mysqli_connect_error());
}
mysqli_set_charset($link, 'utf8mb4');
