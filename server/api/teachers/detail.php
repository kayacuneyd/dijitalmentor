<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'ID required']));
}

try {
    // Fetch basic info
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.full_name, u.avatar_url, u.is_verified, u.phone, u.approval_status,
            tp.university, tp.department, tp.graduation_year, tp.bio,
            tp.city, tp.zip_code, tp.hourly_rate, tp.video_intro_url,
            tp.experience_years, tp.rating_avg, tp.review_count, tp.cv_url
        FROM users u
        JOIN teacher_profiles tp ON u.id = tp.user_id
        WHERE u.id = ? AND u.role = 'student' AND u.is_active = 1
    ");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Teacher not found']));
    }

    // Temizle: null/boş değerleri null yap
    $nullableFields = ['university', 'department', 'bio', 'city', 'zip_code', 'video_intro_url', 'cv_url'];
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
        SELECT s.id, s.name, s.icon, ts.proficiency_level
        FROM teacher_subjects ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.teacher_id = ?
    ");
    $stmt->execute([$id]);
    $teacher['subjects'] = $stmt->fetchAll();

    // Fetch recent reviews
    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at, u.full_name as parent_name
        FROM reviews r
        JOIN users u ON r.parent_id = u.id
        WHERE r.teacher_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$id]);
    $teacher['reviews'] = $stmt->fetchAll();

    $coords = getCityCoordinates($teacher['city'] ?? '', $teacher['zip_code'] ?? '');
    $teacher['lat'] = $coords['lat'];
    $teacher['lng'] = $coords['lng'];

    echo json_encode([
        'success' => true,
        'data' => $teacher
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch teacher details']);
}
