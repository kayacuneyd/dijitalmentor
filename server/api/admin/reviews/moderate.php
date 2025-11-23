<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$admin = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($data['id']) ? (int)$data['id'] : 0;
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['approved', 'rejected'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz parametre']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE teacher_reviews SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('Review moderate error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Güncellenemedi']);
}
