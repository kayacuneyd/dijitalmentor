<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Simple heuristic scorer: lessons_count + hours_total*0.5 + avg_rating*10 + reviews_count*0.2
// In absence of a dedicated metrics table, we derive from existing data quickly.

$admin = requireAuth(['admin']);

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m-01');

try {
    // lessons_count & hours_total from lesson_hours_tracking (if available)
    $stmt = $pdo->prepare("
        SELECT 
            t.id as teacher_id,
            t.full_name,
            COALESCE(SUM(lht.hours_completed),0) as hours_total,
            COUNT(lht.id) as lessons_count
        FROM users t
        LEFT JOIN teacher_profiles tp ON tp.user_id = t.id
        LEFT JOIN lesson_agreements la ON la.teacher_id = t.id AND la.status = 'accepted'
        LEFT JOIN lesson_hours_tracking lht ON lht.agreement_id = la.id AND DATE_FORMAT(lht.logged_at, '%Y-%m-01') = DATE_FORMAT(?, '%Y-%m-01')
        WHERE t.role = 'student'
        GROUP BY t.id, t.full_name
    ");
    $stmt->execute([$month]);
    $base = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // reviews aggregates
    $revStmt = $pdo->prepare("
        SELECT teacher_id, COUNT(*) as reviews_count, AVG(rating) as avg_rating
        FROM teacher_reviews
        WHERE status = 'approved'
          AND DATE_FORMAT(created_at, '%Y-%m-01') = DATE_FORMAT(?, '%Y-%m-01')
        GROUP BY teacher_id
    ");
    $revStmt->execute([$month]);
    $revMap = [];
    foreach ($revStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $revMap[$r['teacher_id']] = $r;
    }

    $results = [];
    foreach ($base as $row) {
        $tid = (int)$row['teacher_id'];
        $hours = (float)$row['hours_total'];
        $lessons = (int)$row['lessons_count'];
        $reviews = $revMap[$tid]['reviews_count'] ?? 0;
        $avgRating = $revMap[$tid]['avg_rating'] ?? 0;
        $score = $lessons + ($hours * 0.5) + ($avgRating * 10) + ($reviews * 0.2);
        $results[] = [
            'teacher_id' => $tid,
            'name' => $row['full_name'],
            'hours_total' => $hours,
            'lessons_count' => $lessons,
            'reviews_count' => (int)$reviews,
            'avg_rating' => $avgRating ? round((float)$avgRating, 2) : null,
            'score' => round($score, 3)
        ];
    }

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    $top = array_slice($results, 0, 10);

    echo json_encode(['success' => true, 'data' => ['month' => $month, 'leaders' => $top]]);
} catch (Throwable $e) {
    error_log('Award compute error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ödül hesaplanamadı']);
}
