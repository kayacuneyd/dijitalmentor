<?php
/**
 * Public Podcast Episode List
 * Returns only published episodes
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 12;
$offset = ($page - 1) * $limit;

try {
    // Get total count of published episodes
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM podcast_episodes
        WHERE is_published = 1 AND processing_status = 'completed'
    ");
    $countStmt->execute();
    $totalCount = (int) $countStmt->fetchColumn();

    // Get published episodes
    $stmt = $pdo->prepare("
        SELECT
            id,
            slug,
            title,
            description,
            audio_url,
            cover_image_url,
            duration_seconds,
            youtube_video_id,
            publish_date,
            created_at
        FROM podcast_episodes
        WHERE is_published = 1 AND processing_status = 'completed'
        ORDER BY publish_date DESC, created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute();
    $episodes = $stmt->fetchAll();

    // Format episodes
    foreach ($episodes as &$episode) {
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
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'episodes' => $episodes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]
    ]);

} catch (Throwable $e) {
    error_log('Public podcast list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Episodes yüklenemedi'
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
