<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$user = requireAuth(['student', 'parent']);
$userId = isset($user['user_id']) ? (int) $user['user_id'] : (int) ($user['id'] ?? 0);
$userRole = $user['role'] ?? null;

try {
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(t.hours_completed), 0) as total_hours
        FROM lesson_hours_tracking t
        JOIN lesson_agreements la ON la.id = t.agreement_id
        JOIN conversations c ON c.id = la.conversation_id
        WHERE la.status = 'accepted' AND (c.teacher_id = ? OR c.parent_id = ?)
    ");
    $totalStmt->execute([$userId, $userId]);
    $totalHours = (float) $totalStmt->fetchColumn();

    $rewardsStmt = $pdo->prepare("
        SELECT id, reward_title, reward_description, reward_value, hours_milestone, is_claimed, claimed_at, awarded_at, reward_type
        FROM rewards
        WHERE user_id = ?
        ORDER BY awarded_at DESC
    ");
    $rewardsStmt->execute([$userId]);
    $rewards = $rewardsStmt->fetchAll(PDO::FETCH_ASSOC);

    $roleForMilestones = $userRole === 'student' ? 'student' : 'parent';
    $nextStmt = $pdo->prepare("
        SELECT *
        FROM reward_milestones
        WHERE role = ? AND is_active = 1 AND hours_required > ?
        ORDER BY hours_required ASC
        LIMIT 1
    ");
    $nextStmt->execute([$roleForMilestones, $totalHours]);
    $next = $nextStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_hours' => $totalHours,
            'rewards' => array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'reward_title' => $row['reward_title'],
                    'reward_description' => $row['reward_description'],
                    'reward_value' => $row['reward_value'] !== null ? (float) $row['reward_value'] : null,
                    'hours_milestone' => (int) $row['hours_milestone'],
                    'is_claimed' => (bool) $row['is_claimed'],
                    'claimed_at' => $row['claimed_at'],
                    'awarded_at' => $row['awarded_at'],
                    'reward_type' => $row['reward_type']
                ];
            }, $rewards),
            'next_milestone' => $next ? [
                'hours_required' => (int) $next['hours_required'],
                'reward_title' => $next['reward_title'],
                'reward_description' => $next['reward_description'],
                'reward_value' => $next['reward_value'] !== null ? (float) $next['reward_value'] : null
            ] : null
        ]
    ]);
} catch (Exception $e) {
    error_log("Rewards list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ödüller getirilemedi']);
}
