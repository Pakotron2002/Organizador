<?php
require_once 'config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->createTables();
        } catch (PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS almacenes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            foto TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS estanterias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            foto TEXT,
            almacen_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (almacen_id) REFERENCES almacenes(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS archivadores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            foto TEXT,
            estanteria_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (estanteria_id) REFERENCES estanterias(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS objetos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            foto TEXT,
            ubicacion_tipo TEXT NOT NULL DEFAULT 'archivador',
            ubicacion_id INTEGER NOT NULL,
            archivador_id INTEGER,
            estanteria_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (archivador_id) REFERENCES archivadores(id) ON DELETE CASCADE,
            FOREIGN KEY (estanteria_id) REFERENCES estanterias(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS amigos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            telefono TEXT,
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS prestamos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_objeto INTEGER NOT NULL,
            id_amigo INTEGER NOT NULL,
            fecha_prestamo DATE NOT NULL,
            fecha_devolucion_esperada DATE,
            fecha_devolucion_real DATE,
            notas TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_objeto) REFERENCES objetos(id) ON DELETE CASCADE,
            FOREIGN KEY (id_amigo) REFERENCES amigos(id) ON DELETE CASCADE
        );
        ";
        
        $this->pdo->exec($sql);
    }
}

// Crear instancia global
$db = new Database();
$pdo = $db->getConnection();
?>
