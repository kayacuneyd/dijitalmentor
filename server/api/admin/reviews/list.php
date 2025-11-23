<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$admin = requireAuth(['admin']);

$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$limit = isset($_GET['limit']) ? min(200, max(1, (int)$_GET['limit'])) : 100;

$where = '';
$params = [];
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where = 'WHERE r.status = ?';
    $params[] = $status;
}

try {
    $sql = "
        SELECT r.id, r.teacher_id, r.reviewer_id, r.rating, r.comment, r.status, r.created_at,
               t.full_name AS teacher_name,
               rv.full_name AS reviewer_name
        FROM teacher_reviews r
        LEFT JOIN users t ON t.id = r.teacher_id
        LEFT JOIN users rv ON rv.id = r.reviewer_id
        $where
        ORDER BY r.created_at DESC
        LIMIT ?
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $p) {
        $stmt->bindValue($i + 1, $p);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log('Admin review list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Yorumlar getirilemedi']);
}
