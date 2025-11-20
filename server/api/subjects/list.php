<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    $stmt = $pdo->query("
        SELECT id, name, name_de, slug, icon, sort_order
        FROM subjects
        ORDER BY sort_order ASC, name ASC
    ");

    $subjects = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $subjects
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch subjects']);
}
