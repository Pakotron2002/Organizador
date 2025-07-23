<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT o.id, o.nombre, o.descripcion, o.foto_url
        FROM objetos o
        LEFT JOIN prestamos p ON o.id = p.id_objeto AND p.fecha_devolucion_real IS NULL
        WHERE p.id IS NULL 
        AND (o.nombre LIKE ? OR o.descripcion LIKE ?)
        ORDER BY o.nombre
        LIMIT 10
    ");
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
