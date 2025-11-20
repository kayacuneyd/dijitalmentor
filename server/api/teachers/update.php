<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

// Sadece öğretmenler kendi profilini güncelleyebilir
$currentUser = requireAuth(['student']);
$userId = $currentUser['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();

    // User tablosunu güncelle
    if (isset($data['full_name']) || isset($data['email'])) {
        $updates = [];
        $params = [];

        if (isset($data['full_name'])) {
            $updates[] = "full_name = ?";
            $params[] = trim($data['full_name']);
        }

        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = trim($data['email']);
        }

        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    }

    // Teacher profile güncelle
    $profileUpdates = [];
    $profileParams = [];

    $profileFields = [
        'university',
        'department',
        'graduation_year',
        'bio',
        'city',
        'zip_code',
        'address_detail',
        'hourly_rate',
        'video_intro_url',
        'experience_years'
    ];

    foreach ($profileFields as $field) {
        if (isset($data[$field])) {
            $profileUpdates[] = "$field = ?";
            $profileParams[] = $data[$field];
        }
    }

    if (!empty($profileUpdates)) {
        $profileParams[] = $userId;
        $sql = "UPDATE teacher_profiles SET " . implode(', ', $profileUpdates) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($profileParams);
    }

    // Subjects güncelleme (varsa)
    if (isset($data['subjects']) && is_array($data['subjects'])) {
        // Önce mevcut dersleri sil
        $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
        $stmt->execute([$userId]);

        // Yeni dersleri ekle
        $stmt = $pdo->prepare("
            INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level)
            VALUES (?, ?, ?)
        ");

        foreach ($data['subjects'] as $subject) {
            $subjectId = $subject['id'] ?? $subject;
            $proficiency = $subject['proficiency_level'] ?? 'intermediate';
            $stmt->execute([$userId, $subjectId, $proficiency]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}
