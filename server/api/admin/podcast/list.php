<?php
/**
 * Admin Podcast Episode List
 * Returns all episodes (including drafts) with status info
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

$status = $_GET['status'] ?? null;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;

try {
    // Build query
    $whereClause = '';
    $params = [];

    if ($status && in_array($status, ['pending', 'generating', 'completed', 'failed'])) {
        $whereClause = 'WHERE processing_status = ?';
        $params[] = $status;
    }

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM podcast_episodes {$whereClause}");
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetchColumn();

    // Get episodes
    $stmt = $pdo->prepare("
        SELECT
            id,
            slug,
            title,
            description,
            topic_prompt,
            audio_url,
            cover_image_url,
            duration_seconds,
            youtube_video_id,
            spotify_episode_id,
            processing_status,
            github_run_id,
            error_message,
            publish_date,
            is_published,
            created_at,
            updated_at
        FROM podcast_episodes
        {$whereClause}
        ORDER BY created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $episodes = $stmt->fetchAll();

    // Format durations
    foreach ($episodes as &$episode) {
        if ($episode['duration_seconds']) {
            $episode['duration_formatted'] = formatDuration($episode['duration_seconds']);
        }
        // Convert boolean fields
        $episode['is_published'] = (bool) $episode['is_published'];
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
    error_log('Admin podcast list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Episodes yüklenemedi: ' . $e->getMessage()
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
