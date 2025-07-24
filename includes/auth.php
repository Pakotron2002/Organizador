<?php
session_start();

function checkAuth() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit();
    }
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