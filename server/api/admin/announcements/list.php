<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$admin = requireAuth(['admin']);

try {
    $stmt = $pdo->query("
        SELECT id, slug, title, body, award_month, award_name, is_published, published_at, created_at
        FROM announcements
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log('Admin announcement list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Duyurular getirilemedi']);
}
