<?php
require_once __DIR__ . '/../config.php';

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

function searchObjects($query) {
    global $pdo;
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT o.*, 
               CASE 
                   WHEN o.ubicacion_tipo = 'archivador' THEN ar.nombre
                   WHEN o.ubicacion_tipo = 'estanteria' THEN e.nombre
               END as ubicacion_nombre,
               CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as prestado,
               am.nombre as amigo_nombre
        FROM objetos o
        LEFT JOIN archivadores ar ON o.ubicacion_id = ar.id AND o.ubicacion_tipo = 'archivador'
        LEFT JOIN estanterias e ON o.ubicacion_id = e.id AND o.ubicacion_tipo = 'estanteria'
        LEFT JOIN prestamos p ON o.id = p.id_objeto AND p.fecha_devolucion_real IS NULL
        LEFT JOIN amigos am ON p.id_amigo = am.id
        WHERE o.nombre LIKE ? OR o.descripcion LIKE ?
        ORDER BY o.nombre
    ");
    
    $stmt->execute([$searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>