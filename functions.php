<?php
require_once 'config.php';

function uploadAndProcessImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen');
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    throw new Exception('Error al guardar la imagen');
}

function uploadImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

function deleteImage($filename) {
    if ($filename && file_exists(UPLOAD_PATH . $filename)) {
        unlink(UPLOAD_PATH . $filename);
    }
}

function getAlmacenes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM almacenes ORDER BY nombre");
    return $stmt->fetchAll();
}

function getEstanterias($almacen_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM estanterias WHERE id_almacen = ? ORDER BY nombre");
    $stmt->execute([$almacen_id]);
    return $stmt->fetchAll();
}

function getArchivadores($estanteria_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM archivadores WHERE id_estanteria = ? ORDER BY nombre");
    $stmt->execute([$estanteria_id]);
    return $stmt->fetchAll();
}

function getObjetosEnArchivador($archivador_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM objetos WHERE ubicacion_id = ? AND ubicacion_tipo = 'archivador' ORDER BY nombre");
    $stmt->execute([$archivador_id]);
    return $stmt->fetchAll();
}

function getObjetosEnEstanteria($estanteria_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM objetos WHERE ubicacion_id = ? AND ubicacion_tipo = 'estanteria' ORDER BY nombre");
    $stmt->execute([$estanteria_id]);
    return $stmt->fetchAll();
}

function getAlmacen($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEstanteria($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM estanterias WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getArchivador($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM archivadores WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getObjeto($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM objetos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAmigo($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM amigos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function searchItems($query) {
    global $pdo;
    $searchTerm = '%' . $query . '%';
    
    $results = [];
    
    // Buscar en almacenes
    $stmt = $pdo->prepare("SELECT 'almacen' as tipo, id, nombre, descripcion, foto FROM almacenes WHERE nombre LIKE ? OR descripcion LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());
    
    // Buscar en estanterías
    $stmt = $pdo->prepare("
        SELECT 'estanteria' as tipo, e.id, e.nombre, e.descripcion, e.foto, a.nombre as almacen_nombre, a.id as almacen_id
        FROM estanterias e 
        JOIN almacenes a ON e.id_almacen = a.id 
        WHERE e.nombre LIKE ? OR e.descripcion LIKE ?
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());
    
    // Buscar en archivadores
    $stmt = $pdo->prepare("
        SELECT 'archivador' as tipo, ar.id, ar.nombre, ar.descripcion, ar.foto, 
               e.nombre as estanteria_nombre, e.id as estanteria_id,
               a.nombre as almacen_nombre, a.id as almacen_id
        FROM archivadores ar 
        JOIN estanterias e ON ar.id_estanteria = e.id
        JOIN almacenes a ON e.id_almacen = a.id 
        WHERE ar.nombre LIKE ? OR ar.descripcion LIKE ?
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());
    
    // Buscar en objetos
    $stmt = $pdo->prepare("
        SELECT 'objeto' as tipo, o.id, o.nombre, o.descripcion, o.foto, o.ubicacion_tipo,
               ar.nombre as ubicacion_nombre, ar.id as ubicacion_id,
               e.nombre as estanteria_nombre, e.id as estanteria_id,
               al.nombre as almacen_nombre, al.id as almacen_id
        FROM objetos o 
        LEFT JOIN archivadores ar ON o.ubicacion_id = ar.id AND o.ubicacion_tipo = 'archivador'
        LEFT JOIN estanterias e ON (o.ubicacion_id = e.id AND o.ubicacion_tipo = 'estanteria') OR ar.id_estanteria = e.id
        LEFT JOIN almacenes al ON e.id_almacen = al.id 
        WHERE o.nombre LIKE ? OR o.descripcion LIKE ?
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = array_merge($results, $stmt->fetchAll());
    
    return $results;
}
?>
