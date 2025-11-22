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

    $conversationId = (int) ($input['conversation_id'] ?? 0);
    $subjectId = (int) ($input['subject_id'] ?? 0);
    $lessonLocation = $input['lesson_location'] ?? '';
    $hourlyRate = isset($input['hourly_rate']) ? (float) $input['hourly_rate'] : null;
    $hoursPerWeek = isset($input['hours_per_week']) ? max(1, (int) $input['hours_per_week']) : 1;
    $startDate = !empty($input['start_date']) ? $input['start_date'] : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    $lessonAddress = isset($input['lesson_address']) ? trim($input['lesson_address']) : null;
    $turkishCenterId = isset($input['turkish_center_id']) ? (int)$input['turkish_center_id'] : null;

    if (!$conversationId || !$subjectId || !$lessonLocation || $hourlyRate === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'conversation_id, subject_id, lesson_location ve hourly_rate zorunludur']);
        exit();
    }

    $allowedLocations = ['student_home', 'turkish_center', 'online'];
    if (!in_array($lessonLocation, $allowedLocations, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Geçersiz ders lokasyonu']);
        exit();
    }

    if ($lessonLocation === 'student_home' && empty($lessonAddress)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Öğrenci evinde ders için adres gereklidir']);
        exit();
    }

    if ($lessonLocation === 'turkish_center' && !$turkishCenterId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Türk kurumunda ders için kurum seçimi gereklidir']);
        exit();
    }

    // If Turkish center is selected, get its address
    if ($lessonLocation === 'turkish_center' && $turkishCenterId) {
        $stmt = $pdo->prepare("SELECT name, address, city, zip_code FROM turkish_centers WHERE id = ? AND is_active = 1");
        $stmt->execute([$turkishCenterId]);
        $center = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$center) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Seçilen Türk kurumu bulunamadı']);
            exit();
        }

        // Store center info in lesson_address
        $lessonAddress = "{$center['name']}, {$center['address']}, {$center['zip_code']} {$center['city']}";
    }

    // Konuşma doğrulama
    $userField = ($userRole === 'student') ? 'teacher_id' : 'parent_id';
    $otherField = ($userRole === 'student') ? 'parent_id' : 'teacher_id';

    $stmt = $pdo->prepare("
        SELECT id, teacher_id, parent_id
        FROM conversations
        WHERE id = ? AND {$userField} = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Konuşma bulunamadı veya erişim yok']);
        exit();
    }

    $recipientId = (int) $conversation[$otherField];

    // Konu doğrulaması
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? LIMIT 1");
    $stmt->execute([$subjectId]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Geçersiz ders seçimi']);
        exit();
    }

    $meetingLink = null;
    $meetingPlatform = $lessonLocation === 'online' ? 'jitsi' : null;

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO lesson_agreements (
            conversation_id,
            sender_id,
            recipient_id,
            subject_id,
            lesson_location,
            lesson_address,
            turkish_center_id,
            meeting_platform,
            meeting_link,
            hourly_rate,
            hours_per_week,
            start_date,
            notes,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $insert->execute([
        $conversationId,
        $userId,
        $recipientId,
        $subjectId,
        $lessonLocation,
        $lessonAddress ?: null,
        $turkishCenterId,
        $meetingPlatform,
        $meetingLink,
        $hourlyRate,
        $hoursPerWeek,
        $startDate,
        $notes ?: null
    ]);

    $agreementId = (int) $pdo->lastInsertId();

    if ($lessonLocation === 'online') {
        $roomId = 'dm-' . $agreementId . '-' . bin2hex(random_bytes(4));
        $meetingLink = "https://meet.jit.si/{$roomId}";

        $update = $pdo->prepare("UPDATE lesson_agreements SET meeting_link = ? WHERE id = ?");
        $update->execute([$meetingLink, $agreementId]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $agreementId,
            'meeting_link' => $meetingLink,
            'meeting_platform' => $meetingPlatform ?? null
        ],
        'message' => 'Onay formu oluşturuldu'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Agreement create error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Onay formu oluşturulamadı']);
}
