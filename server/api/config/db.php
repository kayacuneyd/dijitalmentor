<?php
// Prefer environment variables, but fall back to known production defaults for dijitalmentor.de
$envHost = getenv('DB_HOST');
$envName = getenv('DB_NAME');
$envUser = getenv('DB_USER');
$envPass = getenv('DB_PASS');

$isProdHost = isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'dijitalmentor.de') !== false;

if ($envHost && $envName && $envUser !== false) {
    $host = $envHost;
    $dbname = $envName;
    $username = $envUser;
    $password = $envPass ?: '';
} elseif ($isProdHost) {
    // Hostinger prod defaults (previous hardcoded values)
    $host = 'localhost';
    $dbname = 'u553245641_dijitalmentor';
    $username = 'u553245641_dijitalmentor';
    $password = 'Dijitalmentor1453!';
} else {
    // Local dev fallback
    $host = 'localhost';
    $dbname = 'dijitalmentor_db';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}
