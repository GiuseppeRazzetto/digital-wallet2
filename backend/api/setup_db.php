<?php
require_once '../config/database.prod.php';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username_db, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Crear tabla currencies
    $pdo->exec("CREATE TABLE IF NOT EXISTS currencies (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        codigo varchar(10) NOT NULL,
        nombre varchar(100) NOT NULL,
        simbolo varchar(10) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Crear tabla users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        correo_electronico varchar(191) NOT NULL UNIQUE,
        contrasena_hash varchar(191) NOT NULL,
        token_personal CHAR(4) DEFAULT NULL,
        session_token varchar(64) DEFAULT NULL,
        nombre varchar(100) NOT NULL,
        apellido varchar(100) NOT NULL,
        telefono varchar(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Crear tabla sessions
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token varchar(64) NOT NULL,
        expiration DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        KEY token (token),
        KEY expiration (expiration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Crear tabla wallets
    $pdo->exec("CREATE TABLE IF NOT EXISTS wallets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0.00,
        ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Crear tabla transactions
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wallet_id INT NOT NULL,
        tipo ENUM('deposito','retiro','transferencia') NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        descripcion TEXT,
        fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        wallet_from_id INT,
        wallet_to_id INT,
        FOREIGN KEY (wallet_id) REFERENCES wallets(id),
        FOREIGN KEY (wallet_from_id) REFERENCES wallets(id),
        FOREIGN KEY (wallet_to_id) REFERENCES wallets(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Crear tabla login_attempts
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email varchar(191) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX (email, attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Insertar monedas por defecto si no existen
    $currencies = [
        ['USD', 'US Dollar', '$'],
        ['EUR', 'Euro', '€'],
        ['MXN', 'Peso Mexicano', '$']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO currencies (codigo, nombre, simbolo) VALUES (?, ?, ?)");
    foreach ($currencies as $currency) {
        $stmt->execute($currency);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Base de datos configurada correctamente'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'details' => [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username_db
        ]
    ]);
}
