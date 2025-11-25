<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['student', 'parent']);

$userId = $user['user_id'] ?? $user['id'];
$userRole = $user['role'];

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reportId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rapor ID gereklidir']);
    exit;
}

try {
    // Determine which field to check based on role
    $userField = ($userRole === 'student') ? 'teacher_id' : 'parent_id';
    
    $stmt = $pdo->prepare("
        SELECT 
            lr.*,
            la.subject_id,
            la.agreed_price,
            la.agreed_duration,
            s.name as subject_name,
            s.icon as subject_icon,
            t.full_name as teacher_name,
            t.avatar_url as teacher_avatar,
            p.full_name as parent_name,
            p.avatar_url as parent_avatar
        FROM lesson_reports lr
        JOIN lesson_agreements la ON la.id = lr.agreement_id
        JOIN subjects s ON s.id = la.subject_id
        JOIN users t ON t.id = lr.teacher_id
        JOIN users p ON p.id = lr.parent_id
        WHERE lr.id = ? AND lr.{$userField} = ?
    ");
    
    $stmt->execute([$reportId, $userId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rapor bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
    
} catch (PDOException $e) {
    error_log('Report detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Rapor getirilemedi']);
} catch (Exception $e) {
    error_log('Report detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

