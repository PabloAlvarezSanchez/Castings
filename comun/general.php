<?php
// comun/general.php

function comprueba($usuario_id, $zona) {
    global $link;
    $res = mysqli_query($link, "SELECT usu_derechos FROM usuario WHERE id = " . (int)$usuario_id);
    $row = mysqli_fetch_assoc($res);
    if (!$row || $row['usu_derechos'][$zona] !== '1') {
        header('Location: denegado.php');
        exit;
    }
}

function alerta($titulo, $mensaje, $tipo) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['alerta'] = ['titulo' => $titulo, 'mensaje' => $mensaje, 'tipo' => $tipo];
}

function calcular_edad($fecha_nacimiento) {
    if (!$fecha_nacimiento) return null;
    return (new DateTime($fecha_nacimiento))->diff(new DateTime())->y;
}

// Convierte una fecha/datetime de MySQL (YYYY-MM-DD o YYYY-MM-DD HH:MM:SS) a formato español.
function fecha_es($fecha_mysql, $con_hora = false) {
    if (!$fecha_mysql) return '';
    $formato = $con_hora ? 'd/m/Y H:i' : 'd/m/Y';
    return (new DateTime($fecha_mysql))->format($formato);
}

// Enlace wa.me a partir de un telefono guardado en cualquier formato con espacios/guiones.
// ponytail: asume prefijo España (34) si el numero no trae ya un prefijo internacional; ajustar si se opera fuera de España.
function whatsapp_link($telefono) {
    $limpio = preg_replace('/[^0-9+]/', '', $telefono);
    $limpio = ltrim($limpio, '+');
    if (strlen($limpio) <= 9) {
        $limpio = '34' . $limpio;
    }
    return 'https://wa.me/' . $limpio;
}

function subir_fichero($file, $destino_dir, $mimes_permitidos, $max_bytes) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > $max_bytes) {
        return false;
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $mimes_permitidos, true)) {
        return false;
    }
    if (!is_dir($destino_dir)) {
        mkdir($destino_dir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_final = uniqid('m_', true) . '.' . $ext;
    $ruta = rtrim($destino_dir, '/') . '/' . $nombre_final;
    if (!move_uploaded_file($file['tmp_name'], $ruta)) {
        return false;
    }
    return $ruta;
}
