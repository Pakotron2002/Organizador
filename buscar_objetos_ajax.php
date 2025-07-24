<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) >= 2) {
    $searchTerm = '%' . $query . '%';
    
    // Buscar objetos que no estén prestados
    $stmt = $pdo->prepare("
        SELECT o.id, o.nombre, o.foto_url as foto, o.ubicacion_tipo,
               al.nombre || ' > ' || e.nombre || 
               CASE WHEN o.ubicacion_tipo = 'archivador' THEN ' > ' || ar.nombre ELSE '' END as ubicacion
        FROM objetos o
        LEFT JOIN archivadores ar ON o.ubicacion_id = ar.id AND o.ubicacion_tipo = 'archivador'
        LEFT JOIN estanterias e ON (o.ubicacion_id = e.id AND o.ubicacion_tipo = 'estanteria') OR ar.id_estanteria = e.id
        LEFT JOIN almacenes al ON e.id_almacen = al.id
        LEFT JOIN prestamos p ON o.id = p.id_objeto AND p.fecha_devolucion_real IS NULL
        WHERE (o.nombre LIKE ? OR o.descripcion LIKE ?) 
        AND p.id IS NULL
        ORDER BY o.nombre
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $objetos = $stmt->fetchAll();
    
    echo json_encode($objetos);
} else {
    echo json_encode([]);
}
?>
