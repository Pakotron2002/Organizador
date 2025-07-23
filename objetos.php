<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
checkAuth();

header('Content-Type: application/json');

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $cantidad = $_POST['cantidad'] ?? 1;
        $ubicacion_id = $_POST['ubicacion_id'] ?? 0;
        $ubicacion_tipo = $_POST['ubicacion_tipo'] ?? '';
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        if (empty($ubicacion_id) || empty($ubicacion_tipo)) {
            throw new Exception('La ubicación es requerida');
        }
        
        // Verificar si el nombre ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM objetos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un objeto con ese nombre');
        }
        
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO objetos (nombre, descripcion, cantidad, ubicacion_id, ubicacion_tipo, foto_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $cantidad, $ubicacion_id, $ubicacion_tipo, $foto_url]);
        
        echo json_encode(['success' => true, 'message' => 'Objeto creado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM objetos WHERE id = ?");
        $stmt->execute([$id]);
        $objeto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($objeto) {
            echo json_encode(['success' => true, 'data' => $objeto]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Objeto no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
    try {
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $cantidad = $_POST['cantidad'] ?? 1;
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        // Verificar nombre duplicado (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM objetos WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un objeto con ese nombre');
        }
        
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
            $stmt = $pdo->prepare("UPDATE objetos SET nombre = ?, descripcion = ?, cantidad = ?, foto_url = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $cantidad, $foto_url, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE objetos SET nombre = ?, descripcion = ?, cantidad = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $cantidad, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Objeto actualizado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Verificar si tiene préstamos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_objeto = ? AND fecha_devolucion_real IS NULL");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar un objeto que está prestado');
        }
        
        $stmt = $pdo->prepare("DELETE FROM objetos WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Objeto eliminado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
