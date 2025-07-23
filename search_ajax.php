<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) >= 3) {
    $results = searchItems($query);
    echo json_encode($results);
} else {
    echo json_encode([]);
}
?>
