<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['student', 'parent']);

$userId = $user['user_id'] ?? $user['id'];
$userRole = $user['role'];

$agreementId = isset($_GET['agreement_id']) ? (int)$_GET['agreement_id'] : null;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

try {
    // Determine which field to query based on role
    $userField = ($userRole === 'student') ? 'teacher_id' : 'parent_id';
    
    $sql = "
        SELECT 
            lr.*,
            la.subject_id,
            la.agreed_price,
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
        WHERE lr.{$userField} = ?
    ";
    
    $params = [$userId];
    
    if ($agreementId) {
        $sql .= " AND lr.agreement_id = ?";
        $params[] = $agreementId;
    }
    
    $sql .= " ORDER BY lr.lesson_date DESC, lr.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) 
        FROM lesson_reports lr
        WHERE lr.{$userField} = ?
    ";
    $countParams = [$userId];
    
    if ($agreementId) {
        $countSql .= " AND lr.agreement_id = ?";
        $countParams[] = $agreementId;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $reports,
        'meta' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('List reports error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Raporlar getirilemedi']);
} catch (Exception $e) {
    error_log('List reports error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

