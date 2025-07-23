<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    
    if (strlen($query) >= 3) {
        $results = searchObjects($query);
        echo json_encode($results);
    } else {
        echo json_encode([]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
