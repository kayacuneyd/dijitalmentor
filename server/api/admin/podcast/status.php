<?php
/**
 * Get Podcast Episode Status
 * Used for polling from admin UI
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Admin-only endpoint
$admin = requireAuth(['admin']);

$episodeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($episodeId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Episode ID gerekli']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            slug,
            title,
            processing_status,
            github_run_id,
            error_message,
            audio_url,
            youtube_video_id,
            updated_at
        FROM podcast_episodes
        WHERE id = ?
    ");
    $stmt->execute([$episodeId]);
    $episode = $stmt->fetch();

    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Episode bulunamadı']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $episode
    ]);

} catch (Throwable $e) {
    error_log('Podcast status check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Status sorgulanamadı: ' . $e->getMessage()
    ]);
}
