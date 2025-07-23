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
        $id_estanteria = $_POST['id_estanteria'] ?? 0;
        
        if (empty($nombre)) {
            throw new Exception('El nombre es requerido');
        }
        
        if (empty($id_estanteria)) {
            throw new Exception('La estantería es requerida');
        }
        
        // Verificar si el nombre ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM archivadores WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un archivador con ese nombre');
        }
        
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO archivadores (nombre, descripcion, foto_url, id_estanteria) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $foto_url, $id_estanteria]);
        
        echo json_encode(['success' => true, 'message' => 'Archivador creado exitosamente']);
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
        
        // Obtener el id_estanteria actual
        $stmt = $pdo->prepare("SELECT id_estanteria FROM archivadores WHERE id = ?");
        $stmt->execute([$id]);
        $archivador_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$archivador_actual) {
            throw new Exception('Archivador no encontrado');
        }
        
        // Verificar nombre duplicado (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM archivadores WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Ya existe un archivador con ese nombre');
        }
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadAndProcessImage($_FILES['foto']);
            $stmt = $pdo->prepare("UPDATE archivadores SET nombre = ?, descripcion = ?, foto_url = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $foto_url, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE archivadores SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Archivador actualizado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM archivadores WHERE id = ?");
        $stmt->execute([$id]);
        $archivador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archivador) {
            echo json_encode(['success' => true, 'data' => $archivador]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Archivador no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM archivadores WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Archivador eliminado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
