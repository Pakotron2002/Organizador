<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';
require_once 'functions.php';

requireLogin();

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 3) {
    $results = searchItems($query);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar - Organizador de Objetos</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-warehouse"></i> Organizador de Objetos</h1>
            <div class="user-info">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Inicio</a>
                <a href="amigos.php" class="btn btn-secondary"><i class="fas fa-users"></i> Amigos</a>
                <a href="stats.php" class="btn btn-secondary"><i class="fas fa-chart-bar"></i> Stats</a>
                <a href="?logout=1" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </header>

        <nav class="breadcrumb">
            <a href="index.php" class="breadcrumb-item">Inicio</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active">Buscar</span>
        </nav>

        <div class="search-container">
            <form method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                           placeholder="Buscar almacenes, estanterías, archivadores u objetos..." 
                           class="search-input" id="searchInput" autofocus>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <small class="text-muted">Escribe al menos 3 caracteres para buscar</small>
            </form>
        </div>

        <div id="searchResults">
            <?php if ($query && strlen($query) >= 3): ?>
                <?php if (!empty($results)): ?>
                    <div class="search-results">
                        <h3>Resultados para "<?php echo htmlspecialchars($query); ?>" (<?php echo count($results); ?> encontrados)</h3>
                        
                        <div class="items-grid">
                            <?php foreach ($results as $item): ?>
                                <div class="item-card-light">
                                    <div class="item-image">
                                        <?php if ($item['foto']): ?>
                                            <img src="<?php echo UPLOAD_URL . $item['foto']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                        <?php else: ?>
                                            <div class="placeholder-image">
                                                <?php
                                                $icon = 'fas fa-box';
                                                switch($item['tipo']) {
                                                    case 'almacen': $icon = 'fas fa-warehouse'; break;
                                                    case 'estanteria': $icon = 'fas fa-th-large'; break;
                                                    case 'archivador': $icon = 'fas fa-archive'; break;
                                                    case 'objeto': $icon = 'fas fa-box'; break;
                                                }
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-info">
                                        <div class="item-type">
                                            <span class="badge badge-<?php echo $item['tipo']; ?>">
                                                <?php echo ucfirst($item['tipo']); ?>
                                            </span>
                                        </div>
                                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                                        <?php if ($item['descripcion']): ?>
                                            <p><?php echo htmlspecialchars($item['descripcion']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['tipo'] !== 'almacen'): ?>
                                            <div class="item-location">
                                                <small>
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php if ($item['tipo'] === 'estanteria'): ?>
                                                        En: <?php echo htmlspecialchars($item['almacen_nombre']); ?>
                                                    <?php elseif ($item['tipo'] === 'archivador'): ?>
                                                        En: <?php echo htmlspecialchars($item['almacen_nombre']); ?> > <?php echo htmlspecialchars($item['estanteria_nombre']); ?>
                                                    <?php elseif ($item['tipo'] === 'objeto'): ?>
                                                        En: <?php echo htmlspecialchars($item['almacen_nombre']); ?> > <?php echo htmlspecialchars($item['estanteria_nombre']); ?>
                                                        <?php if ($item['ubicacion_tipo'] === 'archivador'): ?>
                                                            > <?php echo htmlspecialchars($item['ubicacion_nombre']); ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="item-actions">
                                            <?php
                                            $url = '';
                                            switch($item['tipo']) {
                                                case 'almacen': $url = "almacen.php?id={$item['id']}"; break;
                                                case 'estanteria': $url = "estanteria.php?id={$item['id']}"; break;
                                                case 'archivador': $url = "archivador.php?id={$item['id']}"; break;
                                                case 'objeto': 
                                                    if ($item['ubicacion_tipo'] === 'archivador') {
                                                        $url = "archivador.php?id={$item['ubicacion_id']}";
                                                    } else {
                                                        $url = "estanteria.php?id={$item['estanteria_id']}";
                                                    }
                                                    break;
                                            }
                                            ?>
                                            <a href="<?php echo $url; ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No se encontraron resultados</h3>
                        <p>No hay elementos que coincidan con "<?php echo htmlspecialchars($query); ?>"</p>
                        <p>Intenta con otros términos de búsqueda</p>
                    </div>
                <?php endif; ?>
            <?php elseif ($query && strlen($query) < 3): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Escribe al menos 3 caracteres</h3>
                    <p>Para realizar la búsqueda necesitas escribir al menos 3 caracteres</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Buscar en tu inventario</h3>
                    <p>Introduce un término de búsqueda para encontrar almacenes, estanterías, archivadores u objetos</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Búsqueda en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length >= 3) {
                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                } else if (query.length === 0) {
                    searchResults.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Buscar en tu inventario</h3>
                            <p>Introduce un término de búsqueda para encontrar almacenes, estanterías, archivadores u objetos</p>
                        </div>
                    `;
                } else {
                    searchResults.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Escribe al menos 3 caracteres</h3>
                            <p>Para realizar la búsqueda necesitas escribir al menos 3 caracteres</p>
                        </div>
                    `;
                }
            });

            function performSearch(query) {
                fetch(`search_ajax.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        displayResults(data, query);
                    })
                    .catch(error => {
                        console.error('Error en la búsqueda:', error);
                    });
            }

            function displayResults(results, query) {
                if (results.length === 0) {
                    searchResults.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No se encontraron resultados</h3>
                            <p>No hay elementos que coincidan con "${query}"</p>
                            <p>Intenta con otros términos de búsqueda</p>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="search-results">
                        <h3>Resultados para "${query}" (${results.length} encontrados)</h3>
                        <div class="items-grid">
                `;

                results.forEach(item => {
                    const icon = getIcon(item.tipo);
                    const badge = getBadge(item.tipo);
                    const location = getLocation(item);
                    const url = getUrl(item);
                    const imageUrl = item.foto ? `uploads/${item.foto}` : '';

                    html += `
                        <div class="item-card-light">
                            <div class="item-image">
                                ${imageUrl ? 
                                    `<img src="${imageUrl}" alt="${item.nombre}">` :
                                    `<div class="placeholder-image"><i class="${icon}"></i></div>`
                                }
                            </div>
                            <div class="item-info">
                                <div class="item-type">
                                    <span class="badge badge-${item.tipo}">${item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1)}</span>
                                </div>
                                <h3>${item.nombre}</h3>
                                ${item.descripcion ? `<p>${item.descripcion}</p>` : ''}
                                ${location ? `<div class="item-location"><small><i class="fas fa-map-marker-alt"></i> ${location}</small></div>` : ''}
                                <div class="item-actions">
                                    <a href="${url}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div></div>';
                searchResults.innerHTML = html;
            }

            function getIcon(tipo) {
                const icons = {
                    'almacen': 'fas fa-warehouse',
                    'estanteria': 'fas fa-th-large',
                    'archivador': 'fas fa-archive',
                    'objeto': 'fas fa-box'
                };
                return icons[tipo] || 'fas fa-box';
            }

            function getBadge(tipo) {
                return tipo.charAt(0).toUpperCase() + tipo.slice(1);
            }

            function getLocation(item) {
                if (item.tipo === 'almacen') return '';
                if (item.tipo === 'estanteria') return `En: ${item.almacen_nombre}`;
                if (item.tipo === 'archivador') return `En: ${item.almacen_nombre} > ${item.estanteria_nombre}`;
                if (item.tipo === 'objeto') {
                    let location = `En: ${item.almacen_nombre} > ${item.estanteria_nombre}`;
                    if (item.ubicacion_tipo === 'archivador') {
                        location += ` > ${item.ubicacion_nombre}`;
                    }
                    return location;
                }
                return '';
            }

            function getUrl(item) {
                const urls = {
                    'almacen': `almacen.php?id=${item.id}`,
                    'estanteria': `estanteria.php?id=${item.id}`,
                    'archivador': `archivador.php?id=${item.id}`,
                    'objeto': item.ubicacion_tipo === 'archivador' ? 
                        `archivador.php?id=${item.ubicacion_id}` : 
                        `estanteria.php?id=${item.estanteria_id}`
                };
                return urls[item.tipo] || '#';
            }
        });
    </script>
</body>
</html>
