<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

// Procesar formulario de nuevo amigo
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_amigo') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("INSERT INTO amigos (nombre, telefono, email) VALUES (?, ?, ?)");
    if ($stmt->execute([$nombre, $telefono, $email])) {
        $success = "Amigo añadido exitosamente";
    } else {
        $error = "Error al añadir el amigo";
    }
}

// Procesar nuevo préstamo
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_prestamo') {
    $objeto_id = $_POST['objeto_id'];
    $amigo_id = $_POST['amigo_id'];
    $fecha_devolucion = $_POST['fecha_devolucion'] ?: null;
    $notas = trim($_POST['notas']);
    
    $stmt = $pdo->prepare("INSERT INTO prestamos (id_objeto, id_amigo, fecha_prestamo, fecha_devolucion_esperada, notas) VALUES (?, ?, CURRENT_DATE, ?, ?)");
    if ($stmt->execute([$objeto_id, $amigo_id, $fecha_devolucion, $notas])) {
        $success = "Préstamo registrado exitosamente";
    } else {
        $error = "Error al registrar el préstamo";
    }
}

// Procesar devolución
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'devolver_objeto') {
    $prestamo_id = $_POST['prestamo_id'];
    
    $stmt = $pdo->prepare("UPDATE prestamos SET fecha_devolucion_real = CURRENT_DATE WHERE id = ?");
    if ($stmt->execute([$prestamo_id])) {
        $success = "Objeto devuelto exitosamente";
    } else {
        $error = "Error al registrar la devolución";
    }
}

// Obtener amigos con estadísticas
$stmt = $pdo->query("
    SELECT a.*, 
           COUNT(CASE WHEN p.fecha_devolucion_real IS NULL THEN 1 END) as prestamos_activos,
           COUNT(p.id) as prestamos_totales
    FROM amigos a
    LEFT JOIN prestamos p ON a.id = p.id_amigo
    GROUP BY a.id, a.nombre, a.telefono, a.email
    ORDER BY a.nombre
");
$amigos = $stmt->fetchAll();

// Obtener préstamos activos
$stmt = $pdo->query("
    SELECT p.*, o.nombre as objeto_nombre, o.foto as objeto_foto, 
           a.nombre as amigo_nombre
    FROM prestamos p
    JOIN objetos o ON p.id_objeto = o.id
    JOIN amigos a ON p.id_amigo = a.id
    WHERE p.fecha_devolucion_real IS NULL
    ORDER BY p.fecha_prestamo DESC
");
$prestamos_activos = $stmt->fetchAll();

// Obtener historial de préstamos
$stmt = $pdo->query("
    SELECT p.*, o.nombre as objeto_nombre, o.foto as objeto_foto, 
           a.nombre as amigo_nombre
    FROM prestamos p
    JOIN objetos o ON p.id_objeto = o.id
    JOIN amigos a ON p.id_amigo = a.id
    WHERE p.fecha_devolucion_real IS NOT NULL
    ORDER BY p.fecha_devolucion_real DESC
    LIMIT 20
");
$historial_prestamos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos y Préstamos - Organizador de Objetos</title>
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
                <a href="stats.php" class="btn btn-secondary"><i class="fas fa-chart-bar"></i> Stats</a>
                <a href="?logout=1" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </header>

        <nav class="breadcrumb">
            <a href="index.php" class="breadcrumb-item">Inicio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active">Amigos y Préstamos</span>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Estadísticas de préstamos -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($amigos); ?></h3>
                    <p>Amigos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($prestamos_activos); ?></h3>
                    <p>Préstamos Activos</p>
                </div>
            </div>
        </div>

        <!-- Lista de amigos -->
        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-users"></i> Mis Amigos</h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addAmigoModal')">
                <i class="fas fa-plus"></i> Añadir Amigo
            </button>
        </div>

        <div class="friends-list">
            <?php foreach ($amigos as $amigo): ?>
                <div class="friend-card">
                    <div class="friend-avatar">
                        <?php echo strtoupper(substr($amigo['nombre'], 0, 1)); ?>
                    </div>
                    <div class="friend-info">
                        <h4><?php echo htmlspecialchars($amigo['nombre']); ?></h4>
                        <?php if ($amigo['telefono']): ?>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($amigo['telefono']); ?></p>
                        <?php endif; ?>
                        <?php if ($amigo['email']): ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($amigo['email']); ?></p>
                        <?php endif; ?>
                        <div class="friend-stats">
                            <?php if ($amigo['prestamos_activos'] > 0): ?>
                                <span class="badge badge-warning"><?php echo $amigo['prestamos_activos']; ?> préstamos activos</span>
                            <?php endif; ?>
                            <span class="badge badge-info">Total: <?php echo $amigo['prestamos_totales']; ?></span>
                        </div>
                    </div>
                    <div class="friend-actions">
                        <button class="btn btn-primary btn-sm" onclick="prestarObjeto(<?php echo $amigo['id']; ?>, '<?php echo htmlspecialchars($amigo['nombre'], ENT_QUOTES); ?>')">
                            <i class="fas fa-handshake"></i> Prestar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($amigos)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No tienes amigos registrados</h3>
                <p>Añade amigos para poder prestarles objetos</p>
                <button class="btn btn-primary" onclick="openModal('addAmigoModal')">
                    <i class="fas fa-plus"></i> Añadir Primer Amigo
                </button>
            </div>
        <?php endif; ?>

        <!-- Préstamos activos -->
        <?php if (!empty($prestamos_activos)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-handshake"></i> Préstamos Activos</h2>
                </div>
            </div>

            <div class="prestamos-list">
                <?php foreach ($prestamos_activos as $prestamo): ?>
                    <div class="prestamo-card">
                        <div class="prestamo-image">
                            <?php if ($prestamo['objeto_foto']): ?>
                                <img src="<?php echo UPLOAD_URL . $prestamo['objeto_foto']; ?>" alt="<?php echo htmlspecialchars($prestamo['objeto_nombre']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image-small">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-info">
                            <h4><?php echo htmlspecialchars($prestamo['objeto_nombre']); ?></h4>
                            <p><strong>Prestado a:</strong> <?php echo htmlspecialchars($prestamo['amigo_nombre']); ?></p>
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></p>
                            <?php if ($prestamo['fecha_devolucion_esperada']): ?>
                                <p><strong>Devolución esperada:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></p>
                            <?php endif; ?>
                            <?php if ($prestamo['notas']): ?>
                                <p><strong>Notas:</strong> <?php echo htmlspecialchars($prestamo['notas']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-actions">
                            <button class="btn btn-success btn-sm" onclick="devolverObjeto(<?php echo $prestamo['id']; ?>)">
                                <i class="fas fa-check"></i> Devolver
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Historial de préstamos -->
        <?php if (!empty($historial_prestamos)): ?>
            <div class="category-header">
                <div class="category-header-content-centered">
                    <h2><i class="fas fa-history"></i> Historial de Préstamos</h2>
                </div>
            </div>

            <div class="prestamos-list">
                <?php foreach ($historial_prestamos as $prestamo): ?>
                    <div class="prestamo-card prestamo-devuelto">
                        <div class="prestamo-image">
                            <?php if ($prestamo['objeto_foto']): ?>
                                <img src="<?php echo UPLOAD_URL . $prestamo['objeto_foto']; ?>" alt="<?php echo htmlspecialchars($prestamo['objeto_nombre']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image-small">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-info">
                            <h4><?php echo htmlspecialchars($prestamo['objeto_nombre']); ?></h4>
                            <p><strong>Prestado a:</strong> <?php echo htmlspecialchars($prestamo['amigo_nombre']); ?></p>
                            <p><strong>Prestado:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></p>
                            <p><strong>Devuelto:</strong> <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])); ?></p>
                        </div>
                        <div class="prestamo-actions">
                            <span class="badge badge-success">Devuelto</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para añadir amigo -->
    <div id="addAmigoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nuevo Amigo</h3>
                <span class="close" onclick="closeModal('addAmigoModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_amigo">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="telefono">Teléfono:</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addAmigoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Añadir Amigo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para prestar objeto -->
    <div id="prestarObjetoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Prestar Objeto a <span id="amigo_nombre_modal"></span></h3>
                <span class="close" onclick="closeModal('prestarObjetoModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_prestamo">
                <input type="hidden" id="prestamo_amigo_id" name="amigo_id">
                
                <div class="form-group">
                    <label class="form-label" for="buscar_objeto">Buscar Objeto:</label>
                    <input type="text" id="buscar_objeto" class="form-control" placeholder="Escribe el nombre del objeto..." onkeyup="buscarObjetos(this.value)">
                    <div id="objetos_encontrados" class="objetos-list"></div>
                    <input type="hidden" id="objeto_seleccionado_id" name="objeto_id" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="fecha_devolucion">Fecha de devolución esperada (opcional):</label>
                    <input type="date" id="fecha_devolucion" name="fecha_devolucion" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="notas">Notas:</label>
                    <textarea id="notas" name="notas" class="form-control" rows="3" placeholder="Notas adicionales sobre el préstamo..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('prestarObjetoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_prestar" disabled>Prestar Objeto</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function prestarObjeto(amigoId, amigoNombre) {
            document.getElementById('prestamo_amigo_id').value = amigoId;
            document.getElementById('amigo_nombre_modal').textContent = amigoNombre;
            document.getElementById('buscar_objeto').value = '';
            document.getElementById('objetos_encontrados').innerHTML = '';
            document.getElementById('objeto_seleccionado_id').value = '';
            document.getElementById('btn_prestar').disabled = true;
            openModal('prestarObjetoModal');
        }

        function devolverObjeto(prestamoId) {
            if (confirm('¿Confirmar que el objeto ha sido devuelto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="devolver_objeto">
                    <input type="hidden" name="prestamo_id" value="${prestamoId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function buscarObjetos(query) {
            if (query.length < 2) {
                document.getElementById('objetos_encontrados').innerHTML = '';
                return;
            }

            fetch(`buscar_objetos_ajax.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(objetos => {
                    const container = document.getElementById('objetos_encontrados');
                    
                    if (objetos.length === 0) {
                        container.innerHTML = '<p class="text-muted">No se encontraron objetos</p>';
                        return;
                    }

                    let html = '';
                    objetos.forEach(objeto => {
                        html += `
                            <div class="objeto-item" onclick="seleccionarObjeto(${objeto.id}, '${objeto.nombre}')">
                                <div class="objeto-imagen">
                                    ${objeto.foto ? 
                                        `<img src="uploads/${objeto.foto}" alt="${objeto.nombre}">` :
                                        '<div class="placeholder-image-tiny"><i class="fas fa-box"></i></div>'
                                    }
                                </div>
                                <div class="objeto-info">
                                    <strong>${objeto.nombre}</strong>
                                    <small>${objeto.ubicacion}</small>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error al buscar objetos:', error);
                });
        }

        function seleccionarObjeto(id, nombre) {
            document.getElementById('objeto_seleccionado_id').value = id;
            document.getElementById('buscar_objeto').value = nombre;
            document.getElementById('objetos_encontrados').innerHTML = '';
            document.getElementById('btn_prestar').disabled = false;
        }
    </script>
</body>
</html>
