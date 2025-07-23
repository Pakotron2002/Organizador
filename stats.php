<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

// Estadísticas generales
$stats = [];

// Contar elementos
$stmt = $pdo->query("SELECT COUNT(*) FROM almacenes");
$stats['almacenes'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM estanterias");
$stats['estanterias'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM archivadores");
$stats['archivadores'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM objetos");
$stats['objetos'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM amigos");
$stats['amigos'] = $stmt->fetchColumn();

// Préstamos
$stmt = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE fecha_devolucion_real IS NULL");
$stats['prestamos_activos'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM prestamos");
$stats['prestamos_totales'] = $stmt->fetchColumn();

// Objetos por ubicación
$stmt = $pdo->query("
    SELECT ubicacion_tipo, COUNT(*) as cantidad 
    FROM objetos 
    GROUP BY ubicacion_tipo
");
$objetos_por_ubicacion = $stmt->fetchAll();

// Top 5 amigos con más préstamos
$stmt = $pdo->query("
    SELECT a.nombre, COUNT(p.id) as total_prestamos
    FROM amigos a
    LEFT JOIN prestamos p ON a.id = p.id_amigo
    GROUP BY a.id, a.nombre
    HAVING total_prestamos > 0
    ORDER BY total_prestamos DESC
    LIMIT 5
");
$top_amigos = $stmt->fetchAll();

// Objetos más prestados
$stmt = $pdo->query("
    SELECT o.nombre, COUNT(p.id) as veces_prestado
    FROM objetos o
    JOIN prestamos p ON o.id = p.id_objeto
    GROUP BY o.id, o.nombre
    ORDER BY veces_prestado DESC
    LIMIT 5
");
$objetos_mas_prestados = $stmt->fetchAll();

// Actividad reciente (últimos 10 préstamos)
$stmt = $pdo->query("
    SELECT p.fecha_prestamo, p.fecha_devolucion_real, 
           o.nombre as objeto_nombre, a.nombre as amigo_nombre
    FROM prestamos p
           o.nombre as objeto_nombre, a.nombre as amigo_nombre
    FROM prestamos p
    JOIN objetos o ON p.id_objeto = o.id
    JOIN amigos a ON p.id_amigo = a.id
    ORDER BY p.fecha_prestamo DESC
    LIMIT 10
");
$actividad_reciente = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Organizador de Objetos</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-warehouse"></i> Organizador de Objetos</h1>
            <div class="user-info">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Inicio</a>
                <a href="buscar.php" class="btn btn-secondary"><i class="fas fa-search"></i> Buscar</a>
                <a href="amigos.php" class="btn btn-secondary"><i class="fas fa-users"></i> Amigos</a>
                <a href="?logout=1" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </header>

        <nav class="breadcrumb">
            <a href="index.php" class="breadcrumb-item">Inicio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active">Estadísticas</span>
        </nav>

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['almacenes']; ?></h3>
                    <p>Almacenes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['estanterias']; ?></h3>
                    <p>Estanterías</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['archivadores']; ?></h3>
                    <p>Archivadores</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['objetos']; ?></h3>
                    <p>Objetos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['amigos']; ?></h3>
                    <p>Amigos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prestamos_activos']; ?></h3>
                    <p>Préstamos Activos</p>
                </div>
            </div>
        </div>

        <!-- Distribución de objetos -->
        <?php if (!empty($objetos_por_ubicacion)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-chart-pie"></i> Distribución de Objetos</h2>
                </div>
            </div>

            <div class="distribution-grid">
                <?php foreach ($objetos_por_ubicacion as $ubicacion): ?>
                    <div class="distribution-card">
                        <div class="distribution-icon">
                            <i class="fas <?php echo $ubicacion['ubicacion_tipo'] === 'archivador' ? 'fa-archive' : 'fa-th-large'; ?>"></i>
                        </div>
                        <div class="distribution-info">
                            <h3><?php echo $ubicacion['cantidad']; ?></h3>
                            <p>En <?php echo ucfirst($ubicacion['ubicacion_tipo']); ?>s</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Top amigos -->
        <?php if (!empty($top_amigos)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-trophy"></i> Amigos que más piden prestado</h2>
                </div>
            </div>

            <div class="ranking-list">
                <?php foreach ($top_amigos as $index => $amigo): ?>
                    <div class="ranking-item">
                        <div class="ranking-position">
                            <span class="position-number"><?php echo $index + 1; ?></span>
                        </div>
                        <div class="ranking-info">
                            <h4><?php echo htmlspecialchars($amigo['nombre']); ?></h4>
                            <p><?php echo $amigo['total_prestamos']; ?> préstamos</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Objetos más prestados -->
        <?php if (!empty($objetos_mas_prestados)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-star"></i> Objetos más prestados</h2>
                </div>
            </div>

            <div class="ranking-list">
                <?php foreach ($objetos_mas_prestados as $index => $objeto): ?>
                    <div class="ranking-item">
                        <div class="ranking-position">
                            <span class="position-number"><?php echo $index + 1; ?></span>
                        </div>
                        <div class="ranking-info">
                            <h4><?php echo htmlspecialchars($objeto['nombre']); ?></h4>
                            <p><?php echo $objeto['veces_prestado']; ?> veces prestado</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Actividad reciente -->
        <?php if (!empty($actividad_reciente)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-clock"></i> Actividad Reciente</h2>
                </div>
            </div>

            <div class="activity-list">
                <?php foreach ($actividad_reciente as $actividad): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas <?php echo $actividad['fecha_devolucion_real'] ? 'fa-check-circle text-success' : 'fa-handshake text-warning'; ?>"></i>
                        </div>
                        <div class="activity-info">
                            <h4><?php echo htmlspecialchars($actividad['objeto_nombre']); ?></h4>
                            <p>
                                <?php if ($actividad['fecha_devolucion_real']): ?>
                                    Devuelto por <?php echo htmlspecialchars($actividad['amigo_nombre']); ?>
                                    el <?php echo date('d/m/Y', strtotime($actividad['fecha_devolucion_real'])); ?>
                                <?php else: ?>
                                    Prestado a <?php echo htmlspecialchars($actividad['amigo_nombre']); ?>
                                    el <?php echo date('d/m/Y', strtotime($actividad['fecha_prestamo'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="activity-status">
                            <?php if ($actividad['fecha_devolucion_real']): ?>
                                <span class="badge badge-success">Devuelto</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Activo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Resumen de préstamos -->
        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-chart-bar"></i> Resumen de Préstamos</h2>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prestamos_activos']; ?></h3>
                    <p>Préstamos Activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prestamos_totales'] - $stats['prestamos_activos']; ?></h3>
                    <p>Préstamos Devueltos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prestamos_totales']; ?></h3>
                    <p>Total Préstamos</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
