<?php
require_once '../config/database.php';

try {
    $conn = new PDO(
        "mysql:host=$host",
        $username_db,
        $password_db,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Crear la base de datos si no existe
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->exec("USE $dbname");

    // Crear la tabla users
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        correo_electronico VARCHAR(255) NOT NULL UNIQUE,
        contrasena_hash VARCHAR(255) NOT NULL,
        token_personal CHAR(4) DEFAULT NULL,
        session_token VARCHAR(64) DEFAULT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        telefono VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Crear la tabla wallets
    $conn->exec("CREATE TABLE IF NOT EXISTS wallets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0.00,
        ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Crear la tabla de transacciones
    $conn->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wallet_id INT NOT NULL,
        tipo ENUM('deposito', 'retiro', 'transferencia') NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        descripcion TEXT,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        wallet_from_id INT,
        wallet_to_id INT,
        FOREIGN KEY (wallet_id) REFERENCES wallets(id),
        FOREIGN KEY (wallet_from_id) REFERENCES wallets(id),
        FOREIGN KEY (wallet_to_id) REFERENCES wallets(id)
    )");

    // Insertar usuario de prueba si no existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE correo_electronico = ?");
    $email = 'gi@gmail.com';
    $stmt->execute([$email]);

    if (!$stmt->fetch()) {
        $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $token_personal = '1234';
        
        $stmt = $conn->prepare("INSERT INTO users (correo_electronico, contrasena_hash, token_personal, nombre, apellido) VALUES (?, ?, ?, 'Usuario', 'Prueba')");
        $stmt->execute([$email, $password_hash, $token_personal]);
        
        // Crear billetera para el usuario de prueba
        $user_id = $conn->lastInsertId();
        $stmt = $conn->prepare("INSERT INTO wallets (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
    }

    // Crear tabla de intentos de inicio de sesión
    $conn->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX (email, attempt_time)
    )");

    echo "Base de datos configurada correctamente\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
