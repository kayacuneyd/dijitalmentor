<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['parent']); // Only parents can review

$userId = $user['user_id'] ?? $user['id'];
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if (!$teacherId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Öğretmen ID gereklidir']);
    exit;
}

try {
    $eligibility = [
        'can_review' => false,
        'requirements' => [
            'is_member' => true, // Already checked by requireAuth
            'has_messaged' => false,
            'has_agreement' => false
        ],
        'agreements' => []
    ];
    
    // Check if user has messaged with teacher
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE teacher_id = ? AND parent_id = ?
    ");
    $stmt->execute([$teacherId, $userId]);
    $conversation = $stmt->fetch();
    
    $eligibility['requirements']['has_messaged'] = (bool)$conversation;
    
    // Check if user has agreements with teacher
    $stmt = $pdo->prepare("
        SELECT 
            id, subject_id, lesson_date, agreed_price, agreed_duration, status,
            s.name as subject_name, s.icon as subject_icon
        FROM lesson_agreements la
        JOIN subjects s ON s.id = la.subject_id
        WHERE teacher_id = ? AND parent_id = ? 
        AND status IN ('confirmed', 'completed')
        ORDER BY lesson_date DESC
    ");
    $stmt->execute([$teacherId, $userId]);
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eligibility['requirements']['has_agreement'] = count($agreements) > 0;
    $eligibility['agreements'] = $agreements;
    
    // Can review if all requirements are met
    $eligibility['can_review'] = 
        $eligibility['requirements']['is_member'] &&
        $eligibility['requirements']['has_messaged'] &&
        $eligibility['requirements']['has_agreement'];
    
    echo json_encode([
        'success' => true,
        'data' => $eligibility
    ]);
    
} catch (PDOException $e) {
    error_log('Check eligibility error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Kontrol yapılamadı']);
} catch (Exception $e) {
    error_log('Check eligibility error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

