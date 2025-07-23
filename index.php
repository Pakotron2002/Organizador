<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

// Procesar formulario de nuevo almacén
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_almacen') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $foto = '';
    
    // Procesar imagen si se subió
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto = uploadImage($_FILES['foto']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO almacenes (nombre, descripcion, foto) VALUES (?, ?, ?)");
    if ($stmt->execute([$nombre, $descripcion, $foto])) {
        $success = "Almacén creado exitosamente";
    } else {
        $error = "Error al crear el almacén";
    }
}

// Procesar edición de almacén
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'edit_almacen') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    
    $almacen = getAlmacen($id);
    $foto = $almacen['foto'];
    
    // Procesar nueva imagen si se subió
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $new_foto = uploadImage($_FILES['foto']);
        if ($new_foto) {
            deleteImage($foto); // Eliminar imagen anterior
            $foto = $new_foto;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE almacenes SET nombre = ?, descripcion = ?, foto = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $descripcion, $foto, $id])) {
        $success = "Almacén actualizado exitosamente";
    } else {
        $error = "Error al actualizar el almacén";
    }
}

// Procesar eliminación de almacén
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_almacen') {
    $id = $_POST['id'];
    $almacen = getAlmacen($id);
    
    if ($almacen) {
        deleteImage($almacen['foto']);
        $stmt = $pdo->prepare("DELETE FROM almacenes WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Almacén eliminado exitosamente";
        } else {
            $error = "Error al eliminar el almacén";
        }
    }
}

// Obtener almacenes
$almacenes = getAlmacenes();

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM almacenes");
$total_almacenes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM estanterias");
$total_estanterias = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM archivadores");
$total_archivadores = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM objetos");
$total_objetos = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizador de Objetos</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-warehouse"></i> Organizador de Objetos</h1>
            <div class="user-info">
                <a href="buscar.php" class="btn btn-secondary"><i class="fas fa-search"></i> Buscar</a>
                <a href="amigos.php" class="btn btn-secondary"><i class="fas fa-users"></i> Amigos</a>
                <a href="stats.php" class="btn btn-secondary"><i class="fas fa-chart-bar"></i> Stats</a>
                <a href="?logout=1" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </header>

        <nav class="breadcrumb">
            <span class="breadcrumb-item active">Inicio</span>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_almacenes; ?></h3>
                    <p>Almacenes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_estanterias; ?></h3>
                    <p>Estanterías</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_archivadores; ?></h3>
                    <p>Archivadores</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_objetos; ?></h3>
                    <p>Objetos</p>
                </div>
            </div>
        </div>

        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-warehouse"></i> Mis Almacenes</h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addAlmacenModal')">
                <i class="fas fa-plus"></i> Añadir Almacén
            </button>
        </div>

        <div class="items-grid">
            <?php foreach ($almacenes as $almacen): ?>
                <div class="item-card-bg" 
                     style="background-image: url('<?php echo $almacen['foto'] ? UPLOAD_URL . $almacen['foto'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI0OCIgZmlsbD0iI2FkYjViZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPjxpIGNsYXNzPSJmYXMgZmEtd2FyZWhvdXNlIj48L2k+PC90ZXh0Pjwvc3ZnPg=='; ?>')"
                     onclick="location.href='almacen.php?id=<?php echo $almacen['id']; ?>'">
                    <div class="card-overlay">
                        <div class="card-actions">
                            <button class="btn btn-icon btn-secondary" onclick="event.stopPropagation(); editAlmacen(<?php echo $almacen['id']; ?>, '<?php echo htmlspecialchars($almacen['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($almacen['descripcion'], ENT_QUOTES); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($almacen['nombre']); ?></h3>
                            <?php if ($almacen['descripcion']): ?>
                                <p><?php echo htmlspecialchars($almacen['descripcion']); ?></p>
                            <?php endif; ?>
                            <small>Creado: <?php echo date('d/m/Y', strtotime($almacen['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($almacenes)): ?>
            <div class="empty-state">
                <i class="fas fa-warehouse"></i>
                <h3>No tienes almacenes</h3>
                <p>Crea tu primer almacén para comenzar a organizar tus objetos</p>
                <button class="btn btn-primary" onclick="openModal('addAlmacenModal')">
                    <i class="fas fa-plus"></i> Crear Almacén
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para añadir almacén -->
    <div id="addAlmacenModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nuevo Almacén</h3>
                <span class="close" onclick="closeModal('addAlmacenModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_almacen">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del Almacén:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="foto">Foto del Almacén:</label>
                    <div class="image-capture-container">
                        <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                        <div class="capture-buttons">
                            <button type="button" class="btn btn-secondary" onclick="captureImage('foto')">
                                <i class="fas fa-camera"></i> Usar Cámara
                            </button>
                        </div>
                    </div>
                    <div id="foto-preview" class="image-preview"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addAlmacenModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Almacén</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar almacén -->
    <div id="editAlmacenModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Almacén</h3>
                <span class="close" onclick="closeModal('editAlmacenModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_almacen">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_nombre">Nombre del Almacén:</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_foto">Foto del Almacén:</label>
                    <div class="image-capture-container">
                        <input type="file" id="edit_foto" name="foto" class="form-control" accept="image/*">
                        <div class="capture-buttons">
                            <button type="button" class="btn btn-secondary" onclick="captureImage('edit_foto')">
                                <i class="fas fa-camera"></i> Usar Cámara
                            </button>
                        </div>
                    </div>
                    <div id="edit_foto-preview" class="image-preview"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editAlmacenModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="deleteAlmacen(document.getElementById('edit_id').value)">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/image-capture-v2.js"></script>
    <script>
        function editAlmacen(id, nombre, descripcion) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            openModal('editAlmacenModal');
        }

        function deleteAlmacen(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este almacén? Se eliminarán también todas sus estanterías, archivadores y objetos.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_almacen">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Previsualización de imágenes
        document.addEventListener('DOMContentLoaded', function() {
            const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
            imageInputs.forEach(input => {
                input.addEventListener('change', function() {
                    previewImage(this, this.id + '-preview');
                });
            });
        });

        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>
