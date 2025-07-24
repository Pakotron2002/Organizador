<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
checkAuth();

header('Content-Type: application/json');

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = $_POST['nombre'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        $stmt = $pdo->prepare("INSERT INTO amigos (nombre, telefono, email) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $telefono, $email]);
        
        echo json_encode(['success' => true, 'message' => 'Amigo agregado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Verificar si tiene préstamos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_amigo = ? AND fecha_devolucion_real IS NULL");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar un amigo con préstamos activos');
        }
        
        $stmt = $pdo->prepare("DELETE FROM amigos WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Amigo eliminado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>