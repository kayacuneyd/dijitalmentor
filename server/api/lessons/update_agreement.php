<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = requireAuth(['student', 'parent']);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$userId = $user['user_id'] ?? $user['id'];
$userRole = $user['role'];

$agreementId = isset($data['id']) ? (int)$data['id'] : 0;

if (!$agreementId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Anlaşma ID gereklidir']);
    exit;
}

try {
    // Verify agreement exists and user has permission
    $stmt = $pdo->prepare("
        SELECT * FROM lesson_agreements 
        WHERE id = ? AND (teacher_id = ? OR parent_id = ?)
    ");
    $stmt->execute([$agreementId, $userId, $userId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agreement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Anlaşma bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    
    $allowedFields = [
        'lesson_date', 'lesson_time', 'location', 'address_detail',
        'agreed_price', 'agreed_duration', 'status'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'status') {
                if (in_array($data[$field], ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            } elseif ($field === 'location') {
                if (in_array($data[$field], ['online', 'in_person', 'address'], true)) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            } elseif ($field === 'lesson_date') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            } elseif ($field === 'agreed_price' || $field === 'agreed_duration') {
                $updates[] = "$field = ?";
                $params[] = (float)$data[$field];
            } else {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Güncellenecek alan yok']);
        exit;
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $agreementId;
    
    $sql = "UPDATE lesson_agreements SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Fetch updated agreement
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
    $updatedAgreement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Anlaşma güncellendi',
        'data' => $updatedAgreement
    ]);
    
} catch (PDOException $e) {
    error_log('Update agreement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Anlaşma güncellenemedi']);
} catch (Exception $e) {
    error_log('Update agreement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

