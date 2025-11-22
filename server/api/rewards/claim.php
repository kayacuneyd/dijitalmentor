<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$user = requireAuth(['student', 'parent']);
$userId = isset($user['user_id']) ? (int) $user['user_id'] : (int) ($user['id'] ?? 0);

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $rewardId = (int) ($input['reward_id'] ?? 0);

    if (!$rewardId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'reward_id gereklidir']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT id, is_claimed
        FROM rewards
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$rewardId, $userId]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reward) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ödül bulunamadı']);
        exit();
    }

    if ((int) $reward['is_claimed'] === 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ödül zaten talep edilmiş']);
        exit();
    }

    $update = $pdo->prepare("
        UPDATE rewards
        SET is_claimed = 1, claimed_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$rewardId]);

    echo json_encode([
        'success' => true,
        'message' => 'Ödül talep edildi'
    ]);
} catch (Exception $e) {
    error_log("Reward claim error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ödül talep edilemedi']);
}
