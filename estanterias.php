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
        $id_almacen = $_POST['id_almacen'] ?? 0;
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        if (empty($id_almacen)) {
            throw new Exception('El almacén es requerido');
        }
        
        // Verificar si el nombre ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estanterias WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe una estantería con ese nombre');
        }
        
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO estanterias (nombre, descripcion, foto_url, id_almacen) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $foto_url, $id_almacen]);
        
        echo json_encode(['success' => true, 'message' => 'Estantería creada exitosamente']);
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
        
        // Obtener el id_almacen actual
        $stmt = $pdo->prepare("SELECT id_almacen FROM estanterias WHERE id = ?");
        $stmt->execute([$id]);
        $estanteria_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$estanteria_actual) {
            throw new Exception('Estantería no encontrada');
        }
        
        // Verificar nombre duplicado (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estanterias WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe una estantería con ese nombre');
        }
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
            $stmt = $pdo->prepare("UPDATE estanterias SET nombre = ?, descripcion = ?, foto_url = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $foto_url, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE estanterias SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Estantería actualizada exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM estanterias WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Estantería eliminada exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM estanterias WHERE id = ?");
        $stmt->execute([$id]);
        $estanteria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($estanteria) {
            echo json_encode(['success' => true, 'data' => $estanteria]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Estantería no encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
