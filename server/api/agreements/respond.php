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
    $agreementId = (int) ($input['agreement_id'] ?? 0);
    $newStatus = $input['status'] ?? '';

    $allowedStatuses = ['accepted', 'rejected', 'cancelled'];
    if (!$agreementId || !in_array($newStatus, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'agreement_id ve geçerli status gereklidir']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT la.*, c.teacher_id, c.parent_id
        FROM lesson_agreements la
        JOIN conversations c ON c.id = la.conversation_id
        WHERE la.id = ?
    ");
    $stmt->execute([$agreementId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agreement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Onay formu bulunamadı']);
        exit();
    }

    $isParticipant = ($userId === (int) $agreement['teacher_id']) || ($userId === (int) $agreement['parent_id']);
    if (!$isParticipant) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Erişim yok']);
        exit();
    }

    if ($agreement['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Bu form için işlem tamamlanmış']);
        exit();
    }

    $isSender = $userId === (int) $agreement['sender_id'];
    $isRecipient = $userId === (int) $agreement['recipient_id'];

    if (in_array($newStatus, ['accepted', 'rejected'], true) && !$isRecipient) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Yalnızca alıcı kabul/red yapabilir']);
        exit();
    }

    if ($newStatus === 'cancelled' && !$isSender && !$isRecipient) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Bu formu iptal etme yetkiniz yok']);
        exit();
    }

    $update = $pdo->prepare("
        UPDATE lesson_agreements
        SET status = ?, responded_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$newStatus, $agreementId]);

    echo json_encode([
        'success' => true,
        'message' => 'Durum güncellendi',
        'data' => [
            'id' => $agreementId,
            'status' => $newStatus
        ]
    ]);
} catch (Exception $e) {
    error_log("Agreement respond error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'İşlem tamamlanamadı']);
}
