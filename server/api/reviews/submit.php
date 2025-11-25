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

$user = requireAuth(['parent']); // Only parents can review teachers
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$userId = $user['user_id'] ?? $user['id'];
$teacherId = isset($data['teacher_id']) ? (int)$data['teacher_id'] : 0;
$agreementId = isset($data['agreement_id']) ? (int)$data['agreement_id'] : null;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;
$comment = trim($data['comment'] ?? '');
$isPublic = isset($data['is_public']) ? (int)!!$data['is_public'] : 1;

if ($teacherId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz öğretmen veya puan']);
    exit;
}

try {
    // ŞART 1: Siteye üye olmak (zaten kontrol edildi - requireAuth)
    
    // ŞART 2: Öğretmenle mesajlaşmış olmak
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE teacher_id = ? AND parent_id = ?
    ");
    $stmt->execute([$teacherId, $userId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => 'Yorum yapabilmek için önce bu öğretmenle mesajlaşmış olmanız gerekir'
        ]);
        exit;
    }
    
    // ŞART 3: Eğitim konusunda anlaşmış olmak (confirmed veya completed status)
    $stmt = $pdo->prepare("
        SELECT id FROM lesson_agreements 
        WHERE teacher_id = ? AND parent_id = ? 
        AND status IN ('confirmed', 'completed')
    ");
    $stmt->execute([$teacherId, $userId]);
    $agreement = $stmt->fetch();
    
    if (!$agreement) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => 'Yorum yapabilmek için bu öğretmenle eğitim konusunda anlaşmış olmanız gerekir (yer, saat, fiyat)'
        ]);
        exit;
    }
    
    // Use the agreement_id from the found agreement if not provided
    if (!$agreementId) {
        $agreementId = $agreement['id'];
    } else {
        // Verify the provided agreement_id belongs to this teacher-parent pair
        $stmt = $pdo->prepare("
            SELECT id FROM lesson_agreements 
            WHERE id = ? AND teacher_id = ? AND parent_id = ?
        ");
        $stmt->execute([$agreementId, $teacherId, $userId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Geçersiz anlaşma ID']);
            exit;
        }
    }
    
    // Basit rate limit: aynı kullanıcı + öğretmen için son 24 saatte bir
    $chk = $pdo->prepare("
        SELECT COUNT(*) FROM teacher_reviews
        WHERE teacher_id = ? AND reviewer_id = ? AND created_at >= (NOW() - INTERVAL 1 DAY)
    ");
    $chk->execute([$teacherId, $userId]);
    if ((int)$chk->fetchColumn() > 0) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Son 24 saat içinde yorum eklediniz']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO teacher_reviews (teacher_id, reviewer_id, agreement_id, rating, comment, is_public, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$teacherId, $userId, $agreementId, $rating, $comment, $isPublic]);

    echo json_encode(['success' => true, 'message' => 'Yorumunuz incelenmek üzere alındı']);
} catch (Throwable $e) {
    error_log('Review submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Yorum kaydedilemedi']);
}
