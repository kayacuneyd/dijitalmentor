<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'slug gereklidir']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, slug, title, body, award_month, award_name, published_at, created_at
        FROM announcements
        WHERE slug = ? AND is_published = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $ann = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ann) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Duyuru bulunamadı']);
        exit;
    }

    // Award winners (if any)
    $winnersStmt = $pdo->prepare("
        SELECT aw.id, aw.user_id, aw.rank, aw.rationale, u.full_name
        FROM award_winners aw
        JOIN users u ON u.id = aw.user_id
        WHERE aw.announcement_id = ?
        ORDER BY aw.rank ASC
    ");
    $winnersStmt->execute([$ann['id']]);
    $winners = $winnersStmt->fetchAll(PDO::FETCH_ASSOC);

    $ann['winners'] = array_map(function ($w) {
        return [
            'id' => (int) $w['id'],
            'user_id' => (int) $w['user_id'],
            'name' => $w['full_name'],
            'rank' => (int) $w['rank'],
            'rationale' => $w['rationale']
        ];
    }, $winners);

    echo json_encode(['success' => true, 'data' => $ann]);
} catch (Throwable $e) {
    error_log('Announcement detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Duyuru getirilemedi']);
}
