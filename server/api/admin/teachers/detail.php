<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$admin = requireAuth(['admin']);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID required']);
    exit;
}

try {
    // Fetch basic info (admin için tüm bilgiler, is_active kontrolü yok)
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.full_name, u.avatar_url, u.is_verified, u.phone, u.email,
            u.approval_status, u.is_active, u.created_at, u.updated_at,
            tp.university, tp.department, tp.graduation_year, tp.bio,
            tp.city, tp.zip_code, tp.address_detail, tp.hourly_rate, 
            tp.video_intro_url, tp.cv_url, tp.experience_years, 
            tp.rating_avg, tp.review_count, tp.total_students
        FROM users u
        LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
        WHERE u.id = ? AND u.role = 'student'
    ");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Teacher not found']);
        exit;
    }

    // Temizle: null/boş değerleri null yap
    $nullableFields = ['university', 'department', 'bio', 'city', 'zip_code', 'address_detail', 'video_intro_url', 'cv_url', 'email', 'avatar_url'];
    foreach ($nullableFields as $field) {
        if (!isset($teacher[$field]) || $teacher[$field] === '' || $teacher[$field] === null) {
            $teacher[$field] = null;
        }
    }

    if (empty($teacher['graduation_year'])) {
        $teacher['graduation_year'] = null;
    }

    if ($teacher['experience_years'] === '' || $teacher['experience_years'] === null) {
        $teacher['experience_years'] = null;
    }

    // Fetch subjects
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.name_de, s.slug, s.icon, ts.proficiency_level
        FROM teacher_subjects ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.teacher_id = ?
        ORDER BY s.name ASC
    ");
    $stmt->execute([$id]);
    $teacher['subjects'] = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $teacher
    ]);

} catch (PDOException $e) {
    error_log('Admin teacher detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch teacher details']);
}

