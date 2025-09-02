<?php
/**
 * Database Configuration
 * Configuración para Hostinger
 */

// Configuración para Hostinger
$host = 'localhost';
$port = '3306';
$dbname = 'raffles';
$username = 'root';
$password = 'root';

// Configuración de DSN
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

// Opciones de PDO para mayor seguridad
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    // Conexión exitosa - mensaje removido para producción
} catch (PDOException $e) {
    // En producción, no mostrar detalles del error
    die('Error de conexión a la base de datos');
}

/**
 * Función para obtener la conexión PDO
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Función para ejecutar consultas preparadas
 */
function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Función para obtener un registro
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Función para obtener múltiples registros
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}
?>