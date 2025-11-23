<?php
// Test script using the ACTUAL app configuration
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

echo "Testing actual db.php inclusion...\n";

try {
    // Attempt to include the actual db config
    require_once __DIR__ . '/config/db.php';

    if (isset($pdo)) {
        echo "PDO object exists.\n";
        $stmt = $pdo->query("SELECT 'Connection working via db.php' as msg");
        $result = $stmt->fetch();
        echo json_encode([
            'success' => true,
            'message' => $result['msg'],
            'env_vars' => [
                'host' => getenv('DB_HOST'),
                'user' => getenv('DB_USER')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '$pdo variable not found after include']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>