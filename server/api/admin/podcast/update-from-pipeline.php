<?php
/**
 * Update Podcast Episode from GitHub Actions Pipeline
 * Called by automation workflow to update episode with generated content
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Webhook authentication via secret token
$providedToken = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? $_GET['secret'] ?? '';
$expectedToken = getenv('WEBHOOK_SECRET') ?: getenv('JWT_SECRET'); // Fallback to JWT secret

if ($providedToken !== $expectedToken) {
    error_log('Podcast webhook: Invalid secret token');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid webhook secret']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$episodeId = isset($data['episode_id']) ? (int) $data['episode_id'] : 0;
$audioUrl = trim($data['audio_url'] ?? '');
$coverImageUrl = trim($data['cover_image_url'] ?? '');
$durationSeconds = isset($data['duration_seconds']) ? (int) $data['duration_seconds'] : null;
$transcript = $data['transcript'] ?? null;
$scriptMarkdown = $data['script_markdown'] ?? null;
$youtubeVideoId = trim($data['youtube_video_id'] ?? '');
$spotifyEpisodeId = trim($data['spotify_episode_id'] ?? '');
$status = trim($data['status'] ?? 'completed');
$errorMessage = trim($data['error_message'] ?? '');
$githubRunId = trim($data['github_run_id'] ?? '');

if ($episodeId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'episode_id gerekli']);
    exit;
}

try {
    // Check if episode exists
    $stmt = $pdo->prepare("SELECT id, slug, title FROM podcast_episodes WHERE id = ?");
    $stmt->execute([$episodeId]);
    $episode = $stmt->fetch();

    if (!$episode) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Episode bulunamadı']);
        exit;
    }

    // Build update query dynamically
    $updateFields = [];
    $updateParams = [];

    if ($audioUrl) {
        $updateFields[] = 'audio_url = ?';
        $updateParams[] = $audioUrl;
    }

    if ($coverImageUrl) {
        $updateFields[] = 'cover_image_url = ?';
        $updateParams[] = $coverImageUrl;
    }

    if ($durationSeconds !== null) {
        $updateFields[] = 'duration_seconds = ?';
        $updateParams[] = $durationSeconds;
    }

    if ($transcript !== null) {
        $updateFields[] = 'transcript = ?';
        $updateParams[] = $transcript;
    }

    if ($scriptMarkdown !== null) {
        $updateFields[] = 'script_markdown = ?';
        $updateParams[] = $scriptMarkdown;
    }

    if ($youtubeVideoId) {
        $updateFields[] = 'youtube_video_id = ?';
        $updateParams[] = $youtubeVideoId;
    }

    if ($spotifyEpisodeId) {
        $updateFields[] = 'spotify_episode_id = ?';
        $updateParams[] = $spotifyEpisodeId;
    }

    if ($githubRunId) {
        $updateFields[] = 'github_run_id = ?';
        $updateParams[] = $githubRunId;
    }

    // Always update status and error message
    $updateFields[] = 'processing_status = ?';
    $updateParams[] = $status;

    if ($errorMessage) {
        $updateFields[] = 'error_message = ?';
        $updateParams[] = $errorMessage;
    }

    $updateFields[] = 'updated_at = NOW()';

    // If completed and title is empty, use topic_prompt
    if ($status === 'completed' && empty($episode['title'])) {
        $updateFields[] = 'title = COALESCE(title, topic_prompt)';
    }

    // Execute update
    $updateParams[] = $episodeId;
    $sql = "UPDATE podcast_episodes SET " . implode(', ', $updateFields) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateParams);

    // Log successful update
    error_log("Podcast episode #{$episodeId} updated: status={$status}, audio=" . ($audioUrl ? 'yes' : 'no') . ", youtube={$youtubeVideoId}");

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $episodeId,
            'slug' => $episode['slug'],
            'status' => $status
        ]
    ]);

} catch (Throwable $e) {
    error_log('Podcast webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Episode güncellenemedi: ' . $e->getMessage()
    ]);
}
