<?php
/**
 * Public Podcast Episode Detail
 * Returns single episode by slug
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Slug gerekli']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            slug,
            title,
            description,
            audio_url,
            cover_image_url,
            duration_seconds,
            transcript,
            youtube_video_id,
            publish_date,
            created_at,
            updated_at
        FROM podcast_episodes
        WHERE slug = ? AND is_published = 1 AND processing_status = 'completed'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $episode = $stmt->fetch();

    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Episode bulunamadı']);
        exit;
    }

    // Format duration
    if ($episode['duration_seconds']) {
        $episode['duration_formatted'] = formatDuration($episode['duration_seconds']);
    }

    // Add full URLs if needed
    if ($episode['audio_url'] && !str_starts_with($episode['audio_url'], 'http')) {
        $episode['audio_url'] = getenv('CLOUDFLARE_R2_PUBLIC_URL') . '/' . $episode['audio_url'];
    }

    if ($episode['cover_image_url'] && !str_starts_with($episode['cover_image_url'], 'http')) {
        $episode['cover_image_url'] = getenv('CLOUDFLARE_R2_PUBLIC_URL') . '/' . $episode['cover_image_url'];
    }

    // Get related episodes (same publish_date or latest)
    $relatedStmt = $pdo->prepare("
        SELECT id, slug, title, cover_image_url, duration_seconds
        FROM podcast_episodes
        WHERE slug != ? AND is_published = 1 AND processing_status = 'completed'
        ORDER BY publish_date DESC, created_at DESC
        LIMIT 3
    ");
    $relatedStmt->execute([$slug]);
    $related = $relatedStmt->fetchAll();

    foreach ($related as &$rel) {
        if ($rel['duration_seconds']) {
            $rel['duration_formatted'] = formatDuration($rel['duration_seconds']);
        }
        if ($rel['cover_image_url'] && !str_starts_with($rel['cover_image_url'], 'http')) {
            $rel['cover_image_url'] = getenv('CLOUDFLARE_R2_PUBLIC_URL') . '/' . $rel['cover_image_url'];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'episode' => $episode,
            'related' => $related
        ]
    ]);

} catch (Throwable $e) {
    error_log('Public podcast detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Episode yüklenemedi'
    ]);
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%d:%02d', $minutes, $secs);
}
