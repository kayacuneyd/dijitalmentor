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

$conversationId = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;

if (!$conversationId) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'conversation_id parametresi zorunludur']));
}

try {
    $userField = ($userRole === 'student') ? 'teacher_id' : 'parent_id';

    $stmt = $pdo->prepare("
        SELECT id, teacher_id, parent_id
        FROM conversations
        WHERE id = ? AND $userField = ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Konuşma bulunamadı veya erişim yok']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT
            la.*,
            s.name as subject_name,
            s.icon as subject_icon,
            sender.full_name as sender_name,
            recipient.full_name as recipient_name,
            tc.name as turkish_center_name,
            tc.city as turkish_center_city,
            tc.address as turkish_center_address
        FROM lesson_agreements la
        JOIN subjects s ON s.id = la.subject_id
        JOIN users sender ON sender.id = la.sender_id
        JOIN users recipient ON recipient.id = la.recipient_id
        LEFT JOIN turkish_centers tc ON tc.id = la.turkish_center_id
        WHERE la.conversation_id = ?
        ORDER BY la.created_at DESC
    ");
    $stmt->execute([$conversationId]);
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'sender_id' => (int) $row['sender_id'],
            'recipient_id' => (int) $row['recipient_id'],
            'subject_id' => (int) $row['subject_id'],
            'subject_name' => $row['subject_name'],
            'subject_icon' => $row['subject_icon'],
            'lesson_location' => $row['lesson_location'],
            'lesson_address' => $row['lesson_address'],
            'turkish_center_id' => $row['turkish_center_id'] ? (int) $row['turkish_center_id'] : null,
            'turkish_center_name' => $row['turkish_center_name'],
            'turkish_center_city' => $row['turkish_center_city'],
            'turkish_center_address' => $row['turkish_center_address'],
            'meeting_platform' => $row['meeting_platform'],
            'meeting_link' => $row['meeting_link'],
            'hourly_rate' => $row['hourly_rate'] !== null ? (float) $row['hourly_rate'] : null,
            'hours_per_week' => (int) $row['hours_per_week'],
            'start_date' => $row['start_date'],
            'notes' => $row['notes'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'responded_at' => $row['responded_at'],
            'sender_name' => $row['sender_name'],
            'recipient_name' => $row['recipient_name']
        ];
    }, $agreements);

    echo json_encode([
        'success' => true,
        'data' => $formatted
    ]);
} catch (PDOException $e) {
    error_log("Agreement list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Onay formları getirilemedi']);
}
