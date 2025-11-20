<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Check users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'table' => 'users',
        'columns' => $columns
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>