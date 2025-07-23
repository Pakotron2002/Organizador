<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

$archivador_id = $_GET['id'] ?? 0;
$archivador = getArchivador($archivador_id);

if (!$archivador) {
    header('Location: index.php');
    exit();
}

$estanteria = getEstanteria($archivador['estanteria_id']);
$almacen = getAlmacen($estanteria['almacen_id']);

// Procesar formulario de nuevo objeto
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_objeto') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $foto = '';
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto = uploadImage($_FILES['foto']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO objetos (nombre, descripcion, foto, ubicacion_tipo, ubicacion_id, archivador_id, estanteria_id) VALUES (?, ?, ?, 'archivador', ?, ?, ?)");
    if ($stmt->execute([$nombre, $descripcion, $foto, $archivador_id, $archivador_id, $estanteria['id']])) {
        $success = "Objeto creado exitosamente";
    } else {
        $error = "Error al crear el objeto";
    }
}

// Procesar edición de objeto
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'edit_objeto') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    
    $objeto = getObjeto($id);
    $foto = $objeto['foto'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $new_foto = uploadImage($_FILES['foto']);
        if ($new_foto) {
            deleteImage($foto);
            $foto = $new_foto;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE objetos SET nombre = ?, descripcion = ?, foto = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $descripcion, $foto, $id])) {
        $success = "Objeto actualizado exitosamente";
    } else {
        $error = "Error al actualizar el objeto";
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

$objetos = getObjetos($archivador_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivador: <?php echo htmlspecialchars($archivador['nombre']); ?></title>
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
            <a href="estanteria.php?id=<?php echo $estanteria['id']; ?>" class="breadcrumb-item"><?php echo htmlspecialchars($estanteria['nombre']); ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active"><?php echo htmlspecialchars($archivador['nombre']); ?></span>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="category-header">
            <div class="category-header-content-centered">
                <h2><i class="fas fa-box"></i> Objetos en <?php echo htmlspecialchars($archivador['nombre']); ?></h2>
            </div>
            <button class="btn btn-primary" onclick="openModal('addObjetoModal')">
                <i class="fas fa-plus"></i> Añadir Objeto
            </button>
        </div>

        <div class="items-grid">
            <?php foreach ($objetos as $objeto): ?>
                <div class="item-card-light">
                    <div class="item-image">
                        <?php if ($objeto['foto']): ?>
                            <img src="<?php echo UPLOAD_URL . $objeto['foto']; ?>" alt="<?php echo htmlspecialchars($objeto['nombre']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-box"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($objeto['nombre']); ?></h3>
                        <?php if ($objeto['descripcion']): ?>
                            <p><?php echo htmlspecialchars($objeto['descripcion']); ?></p>
                        <?php endif; ?>
                        <small>Añadido: <?php echo date('d/m/Y', strtotime($objeto['created_at'])); ?></small>
                        <div class="item-actions">
                            <button class="btn btn-secondary" onclick="editObjeto(<?php echo $objeto['id']; ?>, '<?php echo htmlspecialchars($objeto['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($objeto['descripcion'], ENT_QUOTES); ?>')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger" onclick="deleteObjeto(<?php echo $objeto['id']; ?>)">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($objetos)): ?>
            <div class="empty-state">
                <i class="fas fa-box"></i>
                <h3>No hay objetos en este archivador</h3>
                <p>Añade tu primer objeto para comenzar a organizar</p>
                <button class="btn btn-primary" onclick="openModal('addObjetoModal')">
                    <i class="fas fa-plus"></i> Añadir Objeto
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para añadir objeto -->
    <div id="addObjetoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nuevo Objeto</h3>
                <span class="close" onclick="closeModal('addObjetoModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_objeto">
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del Objeto:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="foto">Foto del Objeto:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addObjetoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Objeto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar objeto -->
    <div id="editObjetoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Objeto</h3>
                <span class="close" onclick="closeModal('editObjetoModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_objeto">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_nombre">Nombre del Objeto:</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_foto">Foto del Objeto:</label>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editObjetoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="deleteObjeto(document.getElementById('edit_id').value)">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/image-capture-v2.js"></script>
    <script>
        function editObjeto(id, nombre, descripcion) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            openModal('editObjetoModal');
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
