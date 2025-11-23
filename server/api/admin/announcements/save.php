<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../utils/blog_tables.php'; // reuse helper style (no-op here)

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$admin = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$id = isset($data['id']) ? (int)$data['id'] : 0;
$slug = trim($data['slug'] ?? '');
$title = trim($data['title'] ?? '');
$body = trim($data['body'] ?? '');
$awardMonth = isset($data['award_month']) && $data['award_month'] !== '' ? $data['award_month'] : null;
$awardName = trim($data['award_name'] ?? '');
$isPublished = isset($data['is_published']) ? (int)!!$data['is_published'] : 0;
$publishedAt = $isPublished ? date('Y-m-d H:i:s') : null;
$winners = is_array($data['winners'] ?? null) ? $data['winners'] : [];

if ($slug === '' || $title === '' || $body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Slug, başlık ve içerik zorunludur']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE announcements
            SET slug = ?, title = ?, body = ?, award_month = ?, award_name = ?, is_published = ?, published_at = IF(? IS NOT NULL, ?, published_at), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$slug, $title, $body, $awardMonth, $awardName, $isPublished, $publishedAt, $publishedAt, $id]);
        $announcementId = $id;

        // Clear existing winners
        $pdo->prepare("DELETE FROM award_winners WHERE announcement_id = ?")->execute([$announcementId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (slug, title, body, award_month, award_name, is_published, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$slug, $title, $body, $awardMonth, $awardName, $isPublished, $publishedAt]);
        $announcementId = (int)$pdo->lastInsertId();
    }

    // Insert winners (optional)
    if (!empty($winners)) {
        $winStmt = $pdo->prepare("
            INSERT INTO award_winners (announcement_id, user_id, rank, rationale)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($winners as $win) {
            $winStmt->execute([
                $announcementId,
                (int)$win['user_id'],
                isset($win['rank']) ? (int)$win['rank'] : 1,
                $win['rationale'] ?? null
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'data' => ['id' => $announcementId]]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Admin announcement save error: ' . $e->getMessage());
    http_response_code(500);
    $message = 'Duyuru kaydedilemedi (' . $e->getMessage() . ')';
    echo json_encode(['success' => false, 'error' => $message]);
}
