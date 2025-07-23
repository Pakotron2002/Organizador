<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    $estado = $input['estado'] ?? '';
    $ubicacion = $input['ubicacion'] ?? '';
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    $sql = "
        SELECT o.*, 
               CASE 
                   WHEN o.ubicacion_tipo = 'estanteria' THEN e.nombre
                   WHEN o.ubicacion_tipo = 'archivador' THEN a.nombre
               END as ubicacion_nombre,
               CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as prestado,
               am.nombre as amigo_nombre
        FROM objetos o
        LEFT JOIN estanterias e ON o.ubicacion_id = e.id AND o.ubicacion_tipo = 'estanteria'
        LEFT JOIN archivadores a ON o.ubicacion_id = a.id AND o.ubicacion_tipo = 'archivador'
        LEFT JOIN prestamos p ON o.id = p.id_objeto AND p.fecha_devolucion_real IS NULL
        LEFT JOIN amigos am ON p.id_amigo = am.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filtro por texto
    if (!empty($query) && strlen($query) >= 3) {
        $sql .= " AND (o.nombre LIKE ? OR o.descripcion LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Filtro por estado
    if ($estado === 'disponible') {
        $sql .= " AND p.id IS NULL";
    } elseif ($estado === 'prestado') {
        $sql .= " AND p.id IS NOT NULL";
    }
    
    // Filtro por ubicación
    if (!empty($ubicacion)) {
        $sql .= " AND o.ubicacion_tipo = ?";
        $params[] = $ubicacion;
    }
    
    $sql .= " ORDER BY o.nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
