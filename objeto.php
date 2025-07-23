<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
checkAuth();

$db = new Database();
$pdo = $db->getConnection();

$id = $_GET['id'] ?? 0;

// Obtener objeto con información de ubicación completa
$stmt = $pdo->prepare("
    SELECT o.*, 
           CASE 
               WHEN o.ubicacion_tipo = 'estanteria' THEN e.nombre
               WHEN o.ubicacion_tipo = 'archivador' THEN a.nombre
           END as ubicacion_nombre,
           CASE 
               WHEN o.ubicacion_tipo = 'estanteria' THEN al.nombre
               WHEN o.ubicacion_tipo = 'archivador' THEN al2.nombre
           END as almacen_nombre,
           CASE 
               WHEN o.ubicacion_tipo = 'estanteria' THEN e.id_almacen
               WHEN o.ubicacion_tipo = 'archivador' THEN est.id_almacen
           END as almacen_id,
           CASE 
               WHEN o.ubicacion_tipo = 'estanteria' THEN o.ubicacion_id
               WHEN o.ubicacion_tipo = 'archivador' THEN est.id
           END as estanteria_id,
           CASE 
               WHEN o.ubicacion_tipo = 'estanteria' THEN e.nombre
               WHEN o.ubicacion_tipo = 'archivador' THEN est.nombre
           END as estanteria_nombre,
           CASE 
               WHEN o.ubicacion_tipo = 'archivador' THEN o.ubicacion_id
               ELSE NULL
           END as archivador_id
    FROM objetos o
    LEFT JOIN estanterias e ON o.ubicacion_id = e.id AND o.ubicacion_tipo = 'estanteria'
    LEFT JOIN archivadores a ON o.ubicacion_id = a.id AND o.ubicacion_tipo = 'archivador'
    LEFT JOIN almacenes al ON e.id_almacen = al.id
    LEFT JOIN estanterias est ON a.id_estanteria = est.id
    LEFT JOIN almacenes al2 ON est.id_almacen = al2.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$objeto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$objeto) {
    header('Location: index.php');
    exit();
}

// Verificar si está prestado
$stmt = $pdo->prepare("
    SELECT p.*, am.nombre as amigo_nombre 
    FROM prestamos p 
    JOIN amigos am ON p.id_amigo = am.id 
    WHERE p.id_objeto = ? AND p.fecha_devolucion_real IS NULL
");
$stmt->execute([$id]);
$prestamo_activo = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener historial de préstamos
$stmt = $pdo->prepare("
    SELECT p.*, am.nombre as amigo_nombre 
    FROM prestamos p 
    JOIN amigos am ON p.id_amigo = am.id 
    WHERE p.id_objeto = ? 
    ORDER BY p.fecha_prestamo DESC
");
$stmt->execute([$id]);
$historial_prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($objeto['nombre']) ?> - Organizador de Objetos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-white shadow-sm p-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="javascript:history.back()" class="btn btn-link p-0 me-2">←</a>
                <h4 class="mb-0"><?= htmlspecialchars($objeto['nombre']) ?></h4>
            </div>
            <button class="btn btn-primary" onclick="editObjeto(<?= $objeto['id'] ?>)">
                ✏️ Editar
            </button>
        </div>
        
        <!-- Breadcrumb -->
        <div class="container mt-3">
            <nav class="custom-breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="almacen.php?id=<?= $objeto['almacen_id'] ?>"><?= htmlspecialchars($objeto['almacen_nombre']) ?></a></li>
                    <li class="breadcrumb-item"><a href="estanteria.php?id=<?= $objeto['estanteria_id'] ?>"><?= htmlspecialchars($objeto['estanteria_nombre']) ?></a></li>
                    <?php if ($objeto['archivador_id']): ?>
                        <li class="breadcrumb-item"><a href="archivador.php?id=<?= $objeto['archivador_id'] ?>"><?= htmlspecialchars($objeto['ubicacion_nombre']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($objeto['nombre']) ?></li>
                </ol>
            </nav>
        </div>
        
        <!-- Info del Objeto -->
        <div class="container">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?= $objeto['foto_url'] ?: 'assets/images/default-object.jpg' ?>" 
                                 alt="<?= htmlspecialchars($objeto['nombre']) ?>"
                                 class="img-fluid rounded cursor-pointer"
                                 onclick="viewImage('<?= $objeto['foto_url'] ?: 'assets/images/default-object.jpg' ?>', '<?= htmlspecialchars($objeto['nombre']) ?>')">
                        </div>
                        <div class="col-md-8">
                            <h3><?= htmlspecialchars($objeto['nombre']) ?></h3>
                            <p class="text-muted"><?= htmlspecialchars($objeto['descripcion']) ?></p>
                            
                            <div class="row g-3 mt-3">
                                <div class="col-6">
                                    <strong>Cantidad:</strong><br>
                                    <span class="badge bg-info fs-6"><?= $objeto['cantidad'] ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Estado:</strong><br>
                                    <?php if ($prestamo_activo): ?>
                                        <span class="prestado-badge">Prestado a <?= htmlspecialchars($prestamo_activo['amigo_nombre']) ?></span>
                                    <?php else: ?>
                                        <span class="disponible-badge">Disponible</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <strong>Ubicación:</strong><br>
                                    <div class="text-primary ubicacion-completa">
                                        📍 <?= htmlspecialchars($objeto['almacen_nombre']) ?>
                                        <span class="ubicacion-separador"> → </span><?= htmlspecialchars($objeto['estanteria_nombre']) ?>
                                        <?php if ($objeto['ubicacion_tipo'] === 'archivador'): ?>
                                            <span class="ubicacion-separador"> → </span><?= htmlspecialchars($objeto['ubicacion_nombre']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <strong>Creado:</strong><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($objeto['fecha_creacion'])) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial de Préstamos -->
        <?php if (!empty($historial_prestamos)): ?>
        <div class="container">
            <h5 class="mb-3">Historial de Préstamos</h5>
            
            <div class="row g-3">
                <?php foreach ($historial_prestamos as $prestamo): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Prestado a: <?= htmlspecialchars($prestamo['amigo_nombre']) ?></h6>
                                        <small class="text-muted">
                                            📅 Prestado: <?= date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])) ?>
                                        </small>
                                        <?php if ($prestamo['fecha_devolucion_real']): ?>
                                            <br><small class="text-success">
                                                ✅ Devuelto: <?= date('d/m/Y H:i', strtotime($prestamo['fecha_devolucion_real'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($prestamo['fecha_devolucion_real']): ?>
                                            <span class="badge bg-success">Devuelto</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Activo</span>
                                            <button class="btn btn-sm btn-success ms-2" onclick="devolverObjeto(<?= $prestamo['id'] ?>)">
                                                Devolver
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <span class="nav-icon">🏠</span>
                <span class="nav-label">Inicio</span>
            </a>
            <a href="buscar.php" class="nav-item">
                <span class="nav-icon">🔍</span>
                <span class="nav-label">Buscar</span>
            </a>
            <a href="amigos.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-label">Amigos</span>
            </a>
            <a href="stats.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span class="nav-label">Stats</span>
            </a>
        </nav>
    </div>
    
    <!-- Modal Ver Imagen -->
    <div class="modal fade image-modal" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Ver Imagen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="imageModalImg" src="/placeholder.svg" alt="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Objeto -->
    <div class="modal fade" id="editObjetoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Objeto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editObjetoForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_objeto_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_objeto_nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="edit_objeto_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_objeto_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_objeto_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mostrar_cantidad">
                                <label class="form-check-label" for="mostrar_cantidad">
                                    Mostrar campo cantidad
                                </label>
                            </div>
                        </div>
                        <div class="mb-3" id="cantidad_field" style="display: none;">
                            <label for="edit_cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="edit_cantidad" name="cantidad" value="1" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <div id="edit-objeto-foto-input-container"></div>
                            <div id="current_objeto_image" class="mt-2" style="display: none;">
                                <small class="text-muted">Imagen actual:</small><br>
                                <img id="current_objeto_image_preview" src="/placeholder.svg" alt="" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="deleteObjetoBtn">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/image-capture-v2.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/amigos.js"></script>
    <script>
        // Función para editar objeto
        async function editObjeto(id) {
            try {
                const response = await fetch(`api/objetos.php?id=${id}`)
                const data = await response.json()
                
                if (data.success) {
                    const objeto = data.data
                    document.getElementById('edit_objeto_id').value = objeto.id
                    document.getElementById('edit_objeto_nombre').value = objeto.nombre
                    document.getElementById('edit_objeto_descripcion').value = objeto.descripcion
                    document.getElementById('edit_cantidad').value = objeto.cantidad
                    
                    // Mostrar campo cantidad si es diferente de 1
                    if (objeto.cantidad > 1) {
                        document.getElementById('mostrar_cantidad').checked = true
                        document.getElementById('cantidad_field').style.display = 'block'
                    }
                    
                    if (objeto.foto_url) {
                        document.getElementById('current_objeto_image').style.display = 'block'
                        document.getElementById('current_objeto_image_preview').src = objeto.foto_url
                    } else {
                        document.getElementById('current_objeto_image').style.display = 'none'
                    }
                    
                    const Modal = window.bootstrap.Modal
                    const modal = new Modal(document.getElementById('editObjetoModal'))
                    modal.show()
                    
                    // Event listener para eliminar
                    document.getElementById('deleteObjetoBtn').onclick = () => {
                        if (confirm('¿Estás seguro de eliminar este objeto? Esta acción no se puede deshacer.')) {
                            deleteObjetoFromEdit(objeto.id)
                        }
                    }
                }
            } catch (error) {
                console.error('Error:', error)
                window.app.showToast('Error al cargar datos', 'error')
            }
        }
        
        // Toggle campo cantidad
        document.getElementById('mostrar_cantidad').addEventListener('change', function() {
            const cantidadField = document.getElementById('cantidad_field')
            if (this.checked) {
                cantidadField.style.display = 'block'
            } else {
                cantidadField.style.display = 'none'
                document.getElementById('edit_cantidad').value = 1
            }
        })
        
        // Eliminar objeto desde modal de edición
        async function deleteObjetoFromEdit(id) {
            try {
                const response = await fetch('api/objetos.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id }),
                })

                const data = await response.json()

                if (data.success) {
                    window.app.showToast('Objeto eliminado exitosamente', 'success')
                    const Modal = window.bootstrap.Modal
                    Modal.getInstance(document.getElementById('editObjetoModal')).hide()
                    setTimeout(() => window.location.href = 'index.php', 1000)
                } else {
                    window.app.showToast(data.message || 'Error al eliminar', 'error')
                }
            } catch (error) {
                console.error('Error:', error)
                window.app.showToast('Error de conexión', 'error')
            }
        }
        
        // Inicializar formulario de edición de objeto
        document.getElementById("editObjetoForm").addEventListener("submit", (e) => {
            e.preventDefault()
            window.app.submitEditForm("api/objetos.php", document.getElementById("editObjetoForm"), "editObjetoModal")
        })

        // Cambiar las inicializaciones de imagen para usar la nueva versión
        document.getElementById('editObjetoModal').addEventListener('shown.bs.modal', function() {
            const container = document.getElementById('edit-objeto-foto-input-container');
            container.innerHTML = ''; // Limpiar contenedor
            const imageInput = createImageInputV2('edit_objeto_foto', function(file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('edit_objeto_foto_final_v2').files = dataTransfer.files;
            });
            container.appendChild(imageInput);
        });

        // Función para devolver objeto
        async function devolverObjeto(prestamoId) {
            if (!confirm("¿Confirmar devolución del objeto?")) return

            try {
                const response = await fetch("api/prestamos.php", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ id: prestamoId }),
                })

                const data = await response.json()

                if (data.success) {
                    window.app.showToast("Objeto devuelto exitosamente", "success")
                    setTimeout(() => location.reload(), 1000)
                } else {
                    window.app.showToast(data.message || "Error al devolver objeto", "error")
                }
            } catch (error) {
                console.error("Error:", error)
                window.app.showToast("Error de conexión", "error")
            }
        }

        // Función para editar objeto (función global)
        window.editObjeto = editObjeto;
    </script>
</body>
</html>
