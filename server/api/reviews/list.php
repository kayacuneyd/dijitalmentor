<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

if ($teacherId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'teacher_id gereklidir']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, u.full_name AS reviewer_name
        FROM teacher_reviews r
        LEFT JOIN users u ON u.id = r.reviewer_id
        WHERE r.teacher_id = ? AND r.status = 'approved' AND r.is_public = 1
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $teacherId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Average and counts
    $avgStmt = $pdo->prepare("
        SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating
        FROM teacher_reviews
        WHERE teacher_id = ? AND status = 'approved' AND is_public = 1
    ");
    $avgStmt->execute([$teacherId]);
    $meta = $avgStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'meta' => [
            'total_reviews' => (int)($meta['total_reviews'] ?? 0),
            'avg_rating' => $meta['avg_rating'] ? round((float)$meta['avg_rating'], 2) : null
        ]
    ]);
} catch (Throwable $e) {
    error_log('Review list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Yorumlar getirilemedi']);
}
