<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$currentUser = requireAuth();
$userId = $currentUser['user_id'];

// File upload kontrolü
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'No file uploaded']));
}

$file = $_FILES['avatar'];

// Dosya tipi kontrolü
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed']));
}

// Dosya boyutu kontrolü (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'File too large. Max 2MB allowed']));
}

try {
    // Benzersiz dosya adı oluştur
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../../uploads/avatars/';

    // Klasör yoksa oluştur
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $filename;

    // Dosyayı taşı
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Database'de URL'i güncelle
    $avatarUrl = '/uploads/avatars/' . $filename;

    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $userId]);

    echo json_encode([
        'success' => true,
        'data' => [
            'avatar_url' => $avatarUrl
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
}
