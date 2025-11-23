<?php
/**
 * Delete Podcast Episode
 * Admin-only endpoint
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Admin-only endpoint
$admin = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$episodeId = isset($data['id']) ? (int) $data['id'] : 0;

if ($episodeId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Episode ID gerekli']);
    exit;
}

try {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id, slug, title FROM podcast_episodes WHERE id = ?");
    $stmt->execute([$episodeId]);
    $episode = $stmt->fetch();

    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Episode bulunamadı']);
        exit;
    }

    // Delete episode
    $stmt = $pdo->prepare("DELETE FROM podcast_episodes WHERE id = ?");
    $stmt->execute([$episodeId]);

    error_log("Podcast episode #{$episodeId} ('{$episode['title']}') deleted by admin #{$admin['id']}");

    echo json_encode([
        'success' => true,
        'message' => 'Episode silindi'
    ]);

} catch (Throwable $e) {
    error_log('Podcast delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Episode silinemedi: ' . $e->getMessage()
    ]);
}
