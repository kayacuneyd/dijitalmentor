<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

// Sadece veliler iletişim talebi gönderebilir
$currentUser = requireAuth(['parent']);
$parentId = $currentUser['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

$teacherId = (int) ($data['teacher_id'] ?? 0);
$message = trim($data['message'] ?? '');

if (!$teacherId) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Teacher ID required']));
}

try {
    // Öğretmen var mı kontrolü
    $stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND role = 'student' AND is_active = 1
    ");
    $stmt->execute([$teacherId]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Teacher not found']));
    }

    // Daha önce talep gönderilmiş mi?
    $stmt = $pdo->prepare("
        SELECT id FROM unlock_requests 
        WHERE parent_id = ? AND teacher_id = ?
    ");
    $stmt->execute([$parentId, $teacherId]);

    if ($stmt->fetch()) {
        http_response_code(409);
        die(json_encode(['success' => false, 'error' => 'Request already sent']));
    }

    // Yeni talep oluştur
    $stmt = $pdo->prepare("
        INSERT INTO unlock_requests (parent_id, teacher_id, message, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$parentId, $teacherId, $message]);

    // TODO: Email/SMS bildirimi gönder (V2)

    echo json_encode([
        'success' => true,
        'message' => 'Contact request sent successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Request failed']);
}
