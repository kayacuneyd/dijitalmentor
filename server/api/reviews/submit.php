<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = requireAuth(['parent', 'student']);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$teacherId = isset($data['teacher_id']) ? (int)$data['teacher_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;
$comment = trim($data['comment'] ?? '');
$isPublic = isset($data['is_public']) ? (int)!!$data['is_public'] : 1;

if ($teacherId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz öğretmen veya puan']);
    exit;
}

try {
    // Basit rate limit: aynı kullanıcı + öğretmen için son 24 saatte bir
    $chk = $pdo->prepare("
        SELECT COUNT(*) FROM teacher_reviews
        WHERE teacher_id = ? AND reviewer_id = ? AND created_at >= (NOW() - INTERVAL 1 DAY)
    ");
    $chk->execute([$teacherId, $user['user_id'] ?? $user['id']]);
    if ((int)$chk->fetchColumn() > 0) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Son 24 saat içinde yorum eklediniz']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO teacher_reviews (teacher_id, reviewer_id, rating, comment, is_public, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$teacherId, $user['user_id'] ?? $user['id'], $rating, $comment, $isPublic]);

    echo json_encode(['success' => true, 'message' => 'Yorumunuz incelenmek üzere alındı']);
} catch (Throwable $e) {
    error_log('Review submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Yorum kaydedilemedi']);
}
