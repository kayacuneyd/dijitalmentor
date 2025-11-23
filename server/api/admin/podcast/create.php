<?php
/**
 * Admin Podcast Episode Create/Update
 * Triggers GitHub Actions workflow for automated podcast generation
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Admin-only endpoint
$admin = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$id = isset($data['id']) ? (int) $data['id'] : 0;
$slug = trim($data['slug'] ?? '');
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$topicPrompt = trim($data['topic_prompt'] ?? '');
$publishDate = trim($data['publish_date'] ?? date('Y-m-d'));
$isPublished = isset($data['is_published']) ? (int) !!$data['is_published'] : 0;
$triggerGeneration = isset($data['trigger_generation']) ? (bool) $data['trigger_generation'] : false;

// Validation
if ($topicPrompt === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Konu başlığı (topic_prompt) zorunludur']);
    exit;
}

// Auto-generate slug if not provided
if ($slug === '') {
    $slug = generateSlug($title ?: $topicPrompt);
}

try {
    if ($id > 0) {
        // Update existing episode
        $stmt = $pdo->prepare("
            UPDATE podcast_episodes
            SET slug = ?, title = ?, description = ?, topic_prompt = ?,
                publish_date = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$slug, $title, $description, $topicPrompt, $publishDate, $isPublished, $id]);
    } else {
        // Create new episode
        $stmt = $pdo->prepare("
            INSERT INTO podcast_episodes (slug, title, description, topic_prompt, publish_date, is_published, processing_status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$slug, $title, $description, $topicPrompt, $publishDate, $isPublished]);
        $id = (int) $pdo->lastInsertId();
    }

    $response = [
        'success' => true,
        'data' => [
            'id' => $id,
            'slug' => $slug,
            'status' => 'pending'
        ]
    ];

    // Trigger GitHub Actions workflow if requested
    if ($triggerGeneration) {
        $githubToken = getenv('GITHUB_TOKEN');
        $githubRepo = getenv('GITHUB_REPO') ?: 'kayacuneyd/dijitalmentor';

        if ($githubToken) {
            $workflowDispatch = triggerGithubWorkflow($githubToken, $githubRepo, $id, $topicPrompt, $title, $description);

            if ($workflowDispatch['success']) {
                // Update status to 'generating'
                $stmt = $pdo->prepare("UPDATE podcast_episodes SET processing_status = 'generating' WHERE id = ?");
                $stmt->execute([$id]);

                $response['data']['status'] = 'generating';
                $response['data']['message'] = 'Podcast oluşturma işlemi başlatıldı';
            } else {
                $response['data']['warning'] = 'Episode kaydedildi ama GitHub workflow tetiklenemedi: ' . $workflowDispatch['error'];
            }
        } else {
            $response['data']['warning'] = 'GITHUB_TOKEN bulunamadı, manuel tetikleme gerekli';
        }
    }

    echo json_encode($response);

} catch (Throwable $e) {
    error_log('Admin podcast create error: ' . $e->getMessage());
    http_response_code(500);

    $message = 'Episode kaydedilemedi';
    if ($e instanceof PDOException) {
        $driverCode = $e->errorInfo[1] ?? null;
        if ($driverCode === 1062) {
            $message = 'Aynı slug ile bir episode zaten mevcut';
        } elseif ($driverCode === 1146) {
            $message = 'podcast_episodes tablosu bulunamadı (migration çalıştırılmalı)';
        } else {
            $message .= " ({$e->getMessage()})";
        }
    } else {
        $message .= ' (' . $e->getMessage() . ')';
    }

    echo json_encode(['success' => false, 'error' => $message]);
}

// Helper functions
function generateSlug($text)
{
    // Turkish character normalization
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(
        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
        ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
        $text
    );

    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');

    return $text;
}

function triggerGithubWorkflow($token, $repo, $episodeId, $topic, $title, $description)
{
    $url = "https://api.github.com/repos/{$repo}/actions/workflows/podcast-pipeline.yml/dispatches";

    $payload = [
        'ref' => 'master',
        'inputs' => [
            'episode_id' => (string) $episodeId,
            'topic_prompt' => $topic,
            'title' => $title,
            'description' => $description
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json',
        'User-Agent: DijitalMentor-PodcastBot'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 204) {
        error_log("GitHub workflow trigger failed: HTTP {$httpCode}, Error: {$error}, Response: {$response}");
        return [
            'success' => false,
            'error' => "GitHub API error (HTTP {$httpCode})"
        ];
    }

    return ['success' => true];
}
