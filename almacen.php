<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

$almacen_id = $_GET['id'] ?? 0;
$almacen = getAlmacen($almacen_id);

if (!$almacen) {
    header('Location: index.php');
    exit();
}

// Procesar formulario de nueva estantería
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_estanteria') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $foto = '';
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto = uploadImage($_FILES['foto']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO estanterias (nombre, descripcion, foto, almacen_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nombre, $descripcion, $foto, $almacen_id])) {
        $success = "Estantería creada exitosamente";
    } else {
        $error = "Error al crear la estantería";
    }
}

// Procesar edición de estantería
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'edit_estanteria') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    
    $estanteria = getEstanteria($id);
    $foto = $estanteria['foto'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $new_foto = uploadImage($_FILES['foto']);
        if ($new_foto) {
            deleteImage($foto);
            $foto = $new_foto;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE estanterias SET nombre = ?, descripcion = ?, foto = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $descripcion, $foto, $id])) {
        $success = "Estantería actualizada exitosamente";
    } else {
        $error = "Error al actualizar la estantería";
    }
}

// Procesar eliminación de estantería
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_estanteria') {
    $id = $_POST['id'];
    $estanteria = getEstanteria($id);
    
    if ($estanteria) {
        deleteImage($estanteria['foto']);
        $stmt = $pdo->prepare("DELETE FROM estanterias WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Estantería eliminada exitosamente";
        } else {
            $error = "Error al eliminar la estantería";
        }
    }
}

$estanterias = getEstanterias($almacen_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almacén: <?php echo htmlspecialchars($almacen['nombre']); ?></title>
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
            <a href="index.php" class="breadcrumb-item">Inicio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active"><?php echo htmlspecialchars($almacen['nombre']); ?></span>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-th-large"></i> Estanterías de <?php echo htmlspecialchars($almacen['nombre']); ?></h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addEstanteriaModal')">
                <i class="fas fa-plus"></i> Añadir Estantería
            </button>
        </div>

        <div class="items-grid">
            <?php foreach ($estanterias as $estanteria): ?>
                <div class="item-card-bg" 
                     style="background-image: url('<?php echo $estanteria['foto'] ? UPLOAD_URL . $estanteria['foto'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI0OCIgZmlsbD0iI2FkYjViZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPjxpIGNsYXNzPSJmYXMgZmEtdGgtbGFyZ2UiPjwvaT48L3RleHQ+PC9zdmc+'; ?>')"
                     onclick="location.href='estanteria.php?id=<?php echo $estanteria['id']; ?>'">
                    <div class="card-overlay">
                        <div class="card-actions">
                            <button class="btn btn-icon btn-secondary" onclick="event.stopPropagation(); editEstanteria(<?php echo $estanteria['id']; ?>, '<?php echo htmlspecialchars($estanteria['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($estanteria['descripcion'], ENT_QUOTES); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($estanteria['nombre']); ?></h3>
                            <?php if ($estanteria['descripcion']): ?>
                                <p><?php echo htmlspecialchars($estanteria['descripcion']); ?></p>
                            <?php endif; ?>
                            <small>Creada: <?php echo date('d/m/Y', strtotime($estanteria['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($estanterias)): ?>
            <div class="empty-state">
                <i class="fas fa-th-large"></i>
                <h3>No hay estanterías en este almacén</h3>
                <p>Crea tu primera estantería para organizar tus archivadores y objetos</p>
                <button class="btn btn-primary" onclick="openModal('addEstanteriaModal')">
                    <i class="fas fa-plus"></i> Crear Estantería
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para añadir estantería -->
    <div id="addEstanteriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nueva Estantería</h3>
                <span class="close" onclick="closeModal('addEstanteriaModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_estanteria">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre de la Estantería:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="foto">Foto de la Estantería:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addEstanteriaModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Estantería</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar estantería -->
    <div id="editEstanteriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Estantería</h3>
                <span class="close" onclick="closeModal('editEstanteriaModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_estanteria">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_nombre">Nombre de la Estantería:</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_foto">Foto de la Estantería:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editEstanteriaModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="deleteEstanteria(document.getElementById('edit_id').value)">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/image-capture-v2.js"></script>
    <script>
        function editEstanteria(id, nombre, descripcion) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            openModal('editEstanteriaModal');
        }

        function deleteEstanteria(id) {
            if (confirm('¿Estás seguro de que quieres eliminar esta estantería? Se eliminarán también todos sus archivadores y objetos.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_estanteria">
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
