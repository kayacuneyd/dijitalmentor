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

$user = requireAuth(['student']); // Only teachers can create reports

$userId = $user['user_id'] ?? $user['id'];
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$agreementId = isset($data['agreement_id']) ? (int)$data['agreement_id'] : 0;

if (!$agreementId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Anlaşma ID gereklidir']);
    exit;
}

// Validate required fields
$required = ['lesson_date', 'attendance'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "$field gereklidir"]);
        exit;
    }
}

$lessonDate = $data['lesson_date'];
$attendance = $data['attendance'];
$topicsCovered = $data['topics_covered'] ?? null;
$homeworkAssigned = $data['homework_assigned'] ?? null;
$teacherNotes = $data['teacher_notes'] ?? null;
$studentProgress = $data['student_progress'] ?? null;
$nextLessonDate = $data['next_lesson_date'] ?? null;

// Validate attendance
if (!in_array($attendance, ['present', 'absent', 'late'], true)) {
    $attendance = 'present';
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lessonDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz tarih formatı (YYYY-MM-DD)']);
    exit;
}

if ($nextLessonDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextLessonDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz sonraki ders tarihi formatı']);
    exit;
}

try {
    // Verify agreement exists and belongs to this teacher
    $stmt = $pdo->prepare("
        SELECT * FROM lesson_agreements 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$agreementId, $userId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agreement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Anlaşma bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    // Check if report already exists for this agreement and date
    $stmt = $pdo->prepare("
        SELECT id FROM lesson_reports 
        WHERE agreement_id = ? AND lesson_date = ?
    ");
    $stmt->execute([$agreementId, $lessonDate]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Bu tarih için zaten bir rapor mevcut']);
        exit;
    }
    
    // Insert report
    $stmt = $pdo->prepare("
        INSERT INTO lesson_reports (
            agreement_id, teacher_id, parent_id,
            lesson_date, attendance, topics_covered,
            homework_assigned, teacher_notes, student_progress,
            next_lesson_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $agreementId, $userId, $agreement['parent_id'],
        $lessonDate, $attendance, $topicsCovered,
        $homeworkAssigned, $teacherNotes, $studentProgress,
        $nextLessonDate
    ]);
    
    $reportId = $pdo->lastInsertId();
    
    // Update agreement status to completed if this was the last lesson
    // (This is optional, can be handled separately)
    
    // Fetch created report with related data
    $stmt = $pdo->prepare("
        SELECT 
            lr.*,
            la.subject_id,
            s.name as subject_name,
            s.icon as subject_icon,
            t.full_name as teacher_name,
            p.full_name as parent_name
        FROM lesson_reports lr
        JOIN lesson_agreements la ON la.id = lr.agreement_id
        JOIN subjects s ON s.id = la.subject_id
        JOIN users t ON t.id = lr.teacher_id
        JOIN users p ON p.id = lr.parent_id
        WHERE lr.id = ?
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ders raporu başarıyla oluşturuldu',
        'data' => $report
    ]);
    
} catch (PDOException $e) {
    error_log('Create report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Rapor oluşturulamadı']);
} catch (Exception $e) {
    error_log('Create report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bir hata oluştu']);
}

