<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$admin = requireAuth(['admin']);

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

$userId = (int) $data['user_id'];

try {
    // Öğretmenin var olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$userId]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Teacher not found']);
        exit;
    }

    $pdo->beginTransaction();

    // CASCADE DELETE kullanıldığı için sadece users tablosundan silmek yeterli
    // teacher_profiles, teacher_subjects, reviews vb. otomatik silinecek
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$userId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Öğretmen başarıyla silindi'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Admin teacher delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Admin teacher delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
}

