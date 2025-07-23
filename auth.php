<?php
require_once 'config.php';

function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

function checkAuth() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function login($password) {
    // Contraseña simple para demo - en producción usar hash
    if ($password === 'admin123') {
        $_SESSION['logged_in'] = true;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Procesar logout
if (isset($_GET['logout'])) {
    logout();
}
?>
