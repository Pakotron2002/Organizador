<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
checkAuth();

header('Content-Type: application/json');

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $objeto_id = $_POST['objeto_id'] ?? 0;
        $amigo_id = $_POST['amigo_id'] ?? 0;
        
        if (empty($objeto_id) || empty($amigo_id)) {
            throw new Exception('Objeto y amigo son requeridos');
        }
        
        // Verificar que el objeto no esté prestado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_objeto = ? AND fecha_devolucion_real IS NULL");
        $stmt->execute([$objeto_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('El objeto ya está prestado');
        }
        
        $stmt = $pdo->prepare("INSERT INTO prestamos (id_objeto, id_amigo) VALUES (?, ?)");
        $stmt->execute([$objeto_id, $amigo_id]);
        
        echo json_encode(['success' => true, 'message' => 'Objeto prestado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $prestamo_id = $input['id'] ?? 0;
        
        if (empty($prestamo_id)) {
            throw new Exception('ID de préstamo requerido');
        }
        
        $stmt = $pdo->prepare("UPDATE prestamos SET fecha_devolucion_real = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$prestamo_id]);
        
        echo json_encode(['success' => true, 'message' => 'Objeto devuelto exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
