<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireAuth(['student', 'parent']);

$userId = $user['user_id'] ?? $user['id'];
$userRole = $user['role'];

$status = $_GET['status'] ?? null;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

try {
    // Determine which field to query based on role
    $userField = ($userRole === 'student') ? 'teacher_id' : 'parent_id';
    
    $sql = "
        SELECT 
            la.*,
            s.name as subject_name,
            s.icon as subject_icon,
            s.slug as subject_slug,
            t.full_name as teacher_name,
            t.avatar_url as teacher_avatar,
            p.full_name as parent_name,
            p.avatar_url as parent_avatar
        FROM lesson_agreements la
        JOIN subjects s ON s.id = la.subject_id
        JOIN users t ON t.id = la.teacher_id
        JOIN users p ON p.id = la.parent_id
        WHERE la.{$userField} = ?
    ";
    
    $params = [$userId];
    
    if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $sql .= " AND la.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY la.lesson_date DESC, la.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) 
        FROM lesson_agreements la
        WHERE la.{$userField} = ?
    ";
    $countParams = [$userId];
    
    if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $countSql .= " AND la.status = ?";
        $countParams[] = $status;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $agreements,
        'meta' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('List agreements error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Anlaşmalar getirilemedi']);
} catch (Exception $e) {
    error_log('List agreements error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

