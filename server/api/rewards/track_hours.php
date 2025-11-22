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
$userRole = $user['role'] ?? null;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $agreementId = (int) ($input['agreement_id'] ?? 0);
    $hoursCompleted = isset($input['hours_completed']) ? (float) $input['hours_completed'] : 0;
    $notes = isset($input['notes']) ? trim($input['notes']) : null;

    if (!$agreementId || $hoursCompleted <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'agreement_id ve hours_completed (>0) gereklidir']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT la.id, la.status, la.conversation_id, c.teacher_id, c.parent_id
        FROM lesson_agreements la
        JOIN conversations c ON c.id = la.conversation_id
        WHERE la.id = ?
    ");
    $stmt->execute([$agreementId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agreement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Anlaşma bulunamadı']);
        exit();
    }

    if ($agreement['status'] !== 'accepted') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Sadece kabul edilmiş anlaşmalar için saat eklenebilir']);
        exit();
    }

    $isParticipant = ($userId === (int) $agreement['teacher_id']) || ($userId === (int) $agreement['parent_id']);
    if (!$isParticipant) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Bu anlaşmaya erişiminiz yok']);
        exit();
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO lesson_hours_tracking (user_id, agreement_id, hours_completed, notes, completed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insert->execute([$userId, $agreementId, $hoursCompleted, $notes ?: null]);

    // Toplam saat (kullanıcıya bağlı, accepted anlaşmalar)
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(t.hours_completed), 0) as total_hours
        FROM lesson_hours_tracking t
        JOIN lesson_agreements la ON la.id = t.agreement_id
        JOIN conversations c ON c.id = la.conversation_id
        WHERE la.status = 'accepted' AND (c.teacher_id = ? OR c.parent_id = ?)
    ");
    $totalStmt->execute([$userId, $userId]);
    $totalHours = (float) $totalStmt->fetchColumn();

    $roleForMilestones = $userRole === 'student' ? 'student' : 'parent';
    $milestonesStmt = $pdo->prepare("
        SELECT *
        FROM reward_milestones
        WHERE role = ? AND is_active = 1 AND hours_required <= ?
        ORDER BY hours_required ASC
    ");
    $milestonesStmt->execute([$roleForMilestones, $totalHours]);
    $milestones = $milestonesStmt->fetchAll(PDO::FETCH_ASSOC);

    $newRewards = [];
    $existingStmt = $pdo->prepare("
        SELECT id FROM rewards WHERE user_id = ? AND reward_type = ? AND hours_milestone = ? LIMIT 1
    ");
    $insertReward = $pdo->prepare("
        INSERT INTO rewards (user_id, reward_type, reward_title, reward_description, reward_value, hours_milestone, awarded_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($milestones as $milestone) {
        $existingStmt->execute([$userId, $milestone['reward_type'], $milestone['hours_required']]);
        if ($existingStmt->fetch()) {
            continue;
        }

        $insertReward->execute([
            $userId,
            $milestone['reward_type'],
            $milestone['reward_title'],
            $milestone['reward_description'],
            $milestone['reward_value'],
            $milestone['hours_required']
        ]);

        $newRewards[] = [
            'id' => (int) $pdo->lastInsertId(),
            'title' => $milestone['reward_title'],
            'description' => $milestone['reward_description'],
            'value' => $milestone['reward_value'],
            'hours_milestone' => (int) $milestone['hours_required']
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_hours' => $totalHours,
            'new_rewards' => $newRewards
        ],
        'message' => 'Saat kaydedildi'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Track hours error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Saat kaydedilemedi']);
}
