<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
checkAuth();

header('Content-Type: application/json');

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['_method'])) {
    try {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        // Verificar si el nombre ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM almacenes WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un almacén con ese nombre');
        }
        
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO almacenes (nombre, descripcion, foto_url) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $foto_url]);
        
        echo json_encode(['success' => true, 'message' => 'Almacén creado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE id = ?");
        $stmt->execute([$id]);
        $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($almacen) {
            echo json_encode(['success' => true, 'data' => $almacen]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Almacén no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    try {
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        
        if (empty($id)) {
            throw new Exception('ID es requerido para actualizar');
        }
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        // Verificar nombre duplicado (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM almacenes WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un almacén con ese nombre');
        }
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
            $stmt = $pdo->prepare("UPDATE almacenes SET nombre = ?, descripcion = ?, foto_url = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $foto_url, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE almacenes SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Almacén actualizado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Verificar si tiene estanterías
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estanterias WHERE id_almacen = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar un almacén que contiene estanterías');
        }
        
        $stmt = $pdo->prepare("DELETE FROM almacenes WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Almacén eliminado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
