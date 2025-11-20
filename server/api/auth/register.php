<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';
$fullName = trim($data['full_name'] ?? '');
$role = $data['role'] ?? 'student'; // student or parent

// Validation
if (empty($phone) || empty($password) || empty($fullName)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'All fields are required']));
}

if (!in_array($role, ['student', 'parent'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid role']));
}

// Check if user exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);

    if ($stmt->fetch()) {
        http_response_code(409);
        die(json_encode(['success' => false, 'error' => 'Phone number already registered']));
    }

    $pdo->beginTransaction();

    // Create user
    $passwordHash = hashPassword($password);
    $stmt = $pdo->prepare("
        INSERT INTO users (phone, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->execute([$phone, $passwordHash, $fullName, $role]);
    $userId = $pdo->lastInsertId();

    // If student, create profile
    if ($role === 'student') {
        $stmt = $pdo->prepare("INSERT INTO teacher_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    $pdo->commit();

    // Generate token
    $token = generateToken($userId, $role);

    echo json_encode([
        'success' => true,
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $userId,
                'full_name' => $fullName,
                'role' => $role,
                'phone' => $phone
            ]
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
}
