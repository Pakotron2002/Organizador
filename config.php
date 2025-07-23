<?php
// Configuración de la base de datos
define('DB_PATH', __DIR__ . '/database/objects.db');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Crear directorio de base de datos si no existe
if (!file_exists(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

// Configuración de sesión
session_start();

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');
?>
