<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = requireAuth(['student', 'parent']);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$userId = $user['user_id'] ?? $user['id'];
$userRole = $user['role'];

// Determine teacher_id and parent_id
$teacherId = isset($data['teacher_id']) ? (int)$data['teacher_id'] : 0;
$parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
$conversationId = isset($data['conversation_id']) ? (int)$data['conversation_id'] : null;

// Validate roles
if ($userRole === 'student') {
    $teacherId = $userId;
    $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
} else {
    $parentId = $userId;
    $teacherId = isset($data['teacher_id']) ? (int)$data['teacher_id'] : 0;
}

if (!$teacherId || !$parentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Öğretmen ve veli ID gereklidir']);
    exit;
}

// Validate required fields
$required = ['subject_id', 'lesson_date', 'agreed_price', 'agreed_duration'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "$field gereklidir"]);
        exit;
    }
}

$subjectId = (int)$data['subject_id'];
$lessonDate = $data['lesson_date'];
$lessonTime = $data['lesson_time'] ?? null;
$location = $data['location'] ?? 'online';
$addressDetail = $data['address_detail'] ?? null;
$agreedPrice = (float)$data['agreed_price'];
$agreedDuration = (float)$data['agreed_duration'];
$status = $data['status'] ?? 'pending';

// Validate location
if (!in_array($location, ['online', 'in_person', 'address'], true)) {
    $location = 'online';
}

// Validate status
if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $status = 'pending';
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lessonDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz tarih formatı (YYYY-MM-DD)']);
    exit;
}

try {
    // Verify users exist and have correct roles
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id IN (?, ?)");
    $stmt->execute([$teacherId, $parentId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) !== 2) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    $roles = array_column($users, 'role', 'id');
    if ($roles[$teacherId] !== 'student' || $roles[$parentId] !== 'parent') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Geçersiz kullanıcı rolleri']);
        exit;
    }
    
    // Verify conversation exists if provided
    if ($conversationId) {
        $stmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE id = ? AND teacher_id = ? AND parent_id = ?
        ");
        $stmt->execute([$conversationId, $teacherId, $parentId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Geçersiz konuşma']);
            exit;
        }
    }
    
    // Verify subject exists
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Geçersiz ders konusu']);
        exit;
    }
    
    // Insert agreement
    $stmt = $pdo->prepare("
        INSERT INTO lesson_agreements (
            teacher_id, parent_id, conversation_id, subject_id,
            lesson_date, lesson_time, location, address_detail,
            agreed_price, agreed_duration, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $teacherId, $parentId, $conversationId, $subjectId,
        $lessonDate, $lessonTime, $location, $addressDetail,
        $agreedPrice, $agreedDuration, $status
    ]);
    
    $agreementId = $pdo->lastInsertId();
    
    // Fetch created agreement with related data
    $stmt = $pdo->prepare("
        SELECT 
            la.*,
            s.name as subject_name,
            s.icon as subject_icon,
            t.full_name as teacher_name,
            p.full_name as parent_name
        FROM lesson_agreements la
        JOIN subjects s ON s.id = la.subject_id
        JOIN users t ON t.id = la.teacher_id
        JOIN users p ON p.id = la.parent_id
        WHERE la.id = ?
    ");
    $stmt->execute([$agreementId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Anlaşma başarıyla oluşturuldu',
        'data' => $agreement
    ]);
    
} catch (PDOException $e) {
    error_log('Create agreement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Anlaşma oluşturulamadı']);
} catch (Exception $e) {
    error_log('Create agreement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

