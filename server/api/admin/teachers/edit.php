<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Teacher not found']);
        exit;
    }

    $pdo->beginTransaction();

    // Users tablosunu güncelle
    $userUpdates = [];
    $userParams = [];

    $userFields = ['full_name', 'email', 'phone', 'approval_status', 'is_active', 'is_verified'];
    foreach ($userFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'approval_status') {
                $allowedStatuses = ['pending', 'approved', 'rejected'];
                if (in_array($data[$field], $allowedStatuses, true)) {
                    $userUpdates[] = "$field = ?";
                    $userParams[] = $data[$field];
                }
            } elseif ($field === 'is_active' || $field === 'is_verified') {
                $userUpdates[] = "$field = ?";
                $userParams[] = $data[$field] ? 1 : 0;
            } else {
                $userUpdates[] = "$field = ?";
                $userParams[] = trim($data[$field]);
            }
        }
    }

    if (!empty($userUpdates)) {
        $userUpdates[] = "updated_at = NOW()";
        $userParams[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $userUpdates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($userParams);
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
        'experience_years',
        'rating_avg',
        'review_count'
    ];

    foreach ($profileFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'graduation_year' && $data[$field] === '') {
                $profileUpdates[] = "$field = NULL";
            } elseif ($field === 'experience_years' && $data[$field] === '') {
                $profileUpdates[] = "$field = NULL";
            } elseif ($field === 'hourly_rate' || $field === 'rating_avg') {
                $profileUpdates[] = "$field = ?";
                $profileParams[] = (float) $data[$field];
            } elseif ($field === 'review_count' || $field === 'experience_years') {
                $profileUpdates[] = "$field = ?";
                $profileParams[] = (int) $data[$field];
            } else {
                $profileUpdates[] = "$field = ?";
                $profileParams[] = $data[$field] === '' ? null : trim($data[$field]);
            }
        }
    }

    if (!empty($profileUpdates)) {
        // Teacher profile var mı kontrol et
        $stmt = $pdo->prepare("SELECT user_id FROM teacher_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetch();

        if ($profileExists) {
            $profileParams[] = $userId;
            $sql = "UPDATE teacher_profiles SET " . implode(', ', $profileUpdates) . " WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($profileParams);
        } else {
            // Profile yoksa oluştur
            $fields = [];
            $values = [];
            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field;
                    if ($field === 'hourly_rate' || $field === 'rating_avg') {
                        $values[] = (float) $data[$field];
                    } elseif ($field === 'review_count' || $field === 'experience_years') {
                        $values[] = (int) $data[$field];
                    } elseif ($field === 'graduation_year' && $data[$field] === '') {
                        $values[] = null;
                    } else {
                        $values[] = $data[$field] === '' ? null : trim($data[$field]);
                    }
                }
            }
            if (!empty($fields)) {
                $fields[] = 'user_id';
                $values[] = $userId;
                $placeholders = str_repeat('?, ', count($values) - 1) . '?';
                $sql = "INSERT INTO teacher_profiles (" . implode(', ', $fields) . ") VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
            }
        }
    }

    // Subjects güncelleme (varsa)
    if (isset($data['subjects']) && is_array($data['subjects'])) {
        // Önce mevcut dersleri sil
        $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
        $stmt->execute([$userId]);

        // Yeni dersleri ekle
        if (!empty($data['subjects'])) {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_subjects (teacher_id, subject_id, proficiency_level)
                VALUES (?, ?, ?)
            ");

            foreach ($data['subjects'] as $subject) {
                $subjectId = isset($subject['id']) ? (int) $subject['id'] : (int) $subject;
                $proficiency = isset($subject['proficiency_level']) ? $subject['proficiency_level'] : 'intermediate';
                
                $allowedLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
                if (!in_array($proficiency, $allowedLevels, true)) {
                    $proficiency = 'intermediate';
                }
                
                $stmt->execute([$userId, $subjectId, $proficiency]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Öğretmen bilgileri başarıyla güncellendi'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Admin teacher edit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Güncelleme başarısız: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Admin teacher edit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Güncelleme başarısız: ' . $e->getMessage()]);
}

