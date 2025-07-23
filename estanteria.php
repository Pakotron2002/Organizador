<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

$estanteria_id = $_GET['id'] ?? 0;
$estanteria = getEstanteria($estanteria_id);

if (!$estanteria) {
    header('Location: index.php');
    exit();
}

$almacen = getAlmacen($estanteria['almacen_id']);

// Procesar formulario de nuevo archivador
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_archivador') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $foto = '';
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto = uploadImage($_FILES['foto']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO archivadores (nombre, descripcion, foto, estanteria_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nombre, $descripcion, $foto, $estanteria_id])) {
        $success = "Archivador creado exitosamente";
    } else {
        $error = "Error al crear el archivador";
    }
}

// Procesar formulario de nuevo objeto en estantería
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_objeto_estanteria') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $foto = '';
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto = uploadImage($_FILES['foto']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO objetos (nombre, descripcion, foto, ubicacion_tipo, ubicacion_id, estanteria_id) VALUES (?, ?, ?, 'estanteria', ?, ?)");
    if ($stmt->execute([$nombre, $descripcion, $foto, $estanteria_id, $estanteria_id])) {
        $success = "Objeto añadido a la estantería exitosamente";
    } else {
        $error = "Error al añadir el objeto";
    }
}

// Procesar edición de archivador
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'edit_archivador') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    
    $archivador = getArchivador($id);
    $foto = $archivador['foto'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $new_foto = uploadImage($_FILES['foto']);
        if ($new_foto) {
            deleteImage($foto);
            $foto = $new_foto;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE archivadores SET nombre = ?, descripcion = ?, foto = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $descripcion, $foto, $id])) {
        $success = "Archivador actualizado exitosamente";
    } else {
        $error = "Error al actualizar el archivador";
    }
}

// Procesar eliminación de archivador
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_archivador') {
    $id = $_POST['id'];
    $archivador = getArchivador($id);
    
    if ($archivador) {
        deleteImage($archivador['foto']);
        $stmt = $pdo->prepare("DELETE FROM archivadores WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Archivador eliminado exitosamente";
        } else {
            $error = "Error al eliminar el archivador";
        }
    }
}

// Procesar eliminación de objeto
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_objeto') {
    $id = $_POST['id'];
    $objeto = getObjeto($id);
    
    if ($objeto) {
        deleteImage($objeto['foto']);
        $stmt = $pdo->prepare("DELETE FROM objetos WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Objeto eliminado exitosamente";
        } else {
            $error = "Error al eliminar el objeto";
        }
    }
}

$archivadores = getArchivadores($estanteria_id);
$objetos_estanteria = getObjetosEnEstanteria($estanteria_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estantería: <?php echo htmlspecialchars($estanteria['nombre']); ?></title>
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
            <a href="almacen.php?id=<?php echo $almacen['id']; ?>" class="breadcrumb-item"><?php echo htmlspecialchars($almacen['nombre']); ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active"><?php echo htmlspecialchars($estanteria['nombre']); ?></span>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Archivadores -->
        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-archive"></i> Archivadores</h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addArchivadorModal')">
                <i class="fas fa-plus"></i> Añadir Archivador
            </button>
        </div>

        <div class="items-grid">
            <?php foreach ($archivadores as $archivador): ?>
                <div class="item-card-bg" 
                     style="background-image: url('<?php echo $archivador['foto'] ? UPLOAD_URL . $archivador['foto'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI0OCIgZmlsbD0iI2FkYjViZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPjxpIGNsYXNzPSJmYXMgZmEtYXJjaGl2ZSI+PC9pPjwvdGV4dD48L3N2Zz4='; ?>')"
                     onclick="location.href='archivador.php?id=<?php echo $archivador['id']; ?>'">
                    <div class="card-overlay">
                        <div class="card-actions">
                            <button class="btn btn-icon btn-secondary" onclick="event.stopPropagation(); editArchivador(<?php echo $archivador['id']; ?>, '<?php echo htmlspecialchars($archivador['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($archivador['descripcion'], ENT_QUOTES); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($archivador['nombre']); ?></h3>
                            <?php if ($archivador['descripcion']): ?>
                                <p><?php echo htmlspecialchars($archivador['descripcion']); ?></p>
                            <?php endif; ?>
                            <small>Creado: <?php echo date('d/m/Y', strtotime($archivador['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($archivadores)): ?>
            <div class="empty-state-small">
                <i class="fas fa-archive"></i>
                <p>No hay archivadores en esta estantería</p>
                <button class="btn btn-primary btn-sm" onclick="openModal('addArchivadorModal')">
                    <i class="fas fa-plus"></i> Crear Archivador
                </button>
            </div>
        <?php endif; ?>

        <!-- Objetos en la estantería -->
        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-box"></i> Objetos en la Estantería</h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addObjetoEstanteriaModal')">
                <i class="fas fa-plus"></i> Añadir Objeto
            </button>
        </div>

        <?php if (!empty($objetos_estanteria)): ?>
            <div class="objects-horizontal-list">
                <?php foreach ($objetos_estanteria as $objeto): ?>
                    <div class="object-card-horizontal">
                        <div class="object-image">
                            <?php if ($objeto['foto']): ?>
                                <img src="<?php echo UPLOAD_URL . $objeto['foto']; ?>" alt="<?php echo htmlspecialchars($objeto['nombre']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image-small">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="object-info">
                            <h4><?php echo htmlspecialchars($objeto['nombre']); ?></h4>
                            <?php if ($objeto['descripcion']): ?>
                                <p><?php echo htmlspecialchars($objeto['descripcion']); ?></p>
                            <?php endif; ?>
                            <small>Añadido: <?php echo date('d/m/Y', strtotime($objeto['created_at'])); ?></small>
                        </div>
                        <div class="object-actions">
                            <button class="btn btn-sm btn-danger" onclick="deleteObjeto(<?php echo $objeto['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-small">
                <i class="fas fa-box"></i>
                <p>No hay objetos directamente en esta estantería</p>
                <button class="btn btn-primary btn-sm" onclick="openModal('addObjetoEstanteriaModal')">
                    <i class="fas fa-plus"></i> Añadir Objeto
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para añadir archivador -->
    <div id="addArchivadorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nuevo Archivador</h3>
                <span class="close" onclick="closeModal('addArchivadorModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_archivador">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del Archivador:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="foto">Foto del Archivador:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addArchivadorModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Archivador</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para añadir objeto en estantería -->
    <div id="addObjetoEstanteriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Objeto a la Estantería</h3>
                <span class="close" onclick="closeModal('addObjetoEstanteriaModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_objeto_estanteria">
                
                <div class="form-group">
                    <label class="form-label" for="objeto_nombre">Nombre del Objeto:</label>
                    <input type="text" id="objeto_nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="objeto_descripcion">Descripción:</label>
                    <textarea id="objeto_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="objeto_foto">Foto del Objeto:</label>
                    <div class="image-capture-container">
                        <input type="file" id="objeto_foto" name="foto" class="form-control" accept="image/*">
                        <div class="capture-buttons">
                            <button type="button" class="btn btn-secondary" onclick="captureImage('objeto_foto')">
                                <i class="fas fa-camera"></i> Usar Cámara
                            </button>
                        </div>
                    </div>
                    <div id="objeto_foto-preview" class="image-preview"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addObjetoEstanteriaModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Añadir Objeto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar archivador -->
    <div id="editArchivadorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Archivador</h3>
                <span class="close" onclick="closeModal('editArchivadorModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_archivador">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_nombre">Nombre del Archivador:</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_foto">Foto del Archivador:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editArchivadorModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="deleteArchivador(document.getElementById('edit_id').value)">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/image-capture-v2.js"></script>
    <script>
        function editArchivador(id, nombre, descripcion) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            openModal('editArchivadorModal');
        }

        function deleteArchivador(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este archivador? Se eliminarán también todos sus objetos.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_archivador">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteObjeto(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este objeto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_objeto">
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
