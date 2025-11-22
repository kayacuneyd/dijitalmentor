<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

// Öğretmen + premium kontrolü için authenticate kullan
$currentUser = authenticate(['student']);
$userId = (int) $currentUser['id'];
$premiumExpiresAt = $currentUser['premium_expires_at'] ?? null;

// Premium kontrolü
if (empty($currentUser['is_premium'])) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Premium membership required',
        'message' => 'CV yükleme özelliği sadece premium üyeler içindir. Yıllık 10€ ile premium üye olabilirsiniz.'
    ]));
}

if ($premiumExpiresAt && strtotime($premiumExpiresAt) < time()) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Premium membership expired',
        'message' => 'Premium üyeliğinizin süresi dolmuş. Yenilemek için hediye@dijitalmentor.de adresine mesaj gönderin.'
    ]));
}

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'CV dosyası yüklenmedi']));
}

$file = $_FILES['cv'];

// Boyut kontrolü (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Dosya çok büyük. Maksimum 5MB olmalı']));
}

// MIME tipi kontrolü (sadece PDF)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mimeType !== 'application/pdf') {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Sadece PDF formatı kabul edilir']));
}

try {
    $uploadDir = __DIR__ . '/../../uploads/cvs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'cv_' . $userId . '_' . time() . '.pdf';
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Dosya taşınamadı');
    }

    $cvUrl = '/uploads/cvs/' . $filename;
    $stmt = $pdo->prepare("UPDATE teacher_profiles SET cv_url = ? WHERE user_id = ?");
    $stmt->execute([$cvUrl, $userId]);

    echo json_encode([
        'success' => true,
        'data' => ['cv_url' => $cvUrl],
        'message' => 'CV başarıyla yüklendi'
    ]);
} catch (Exception $e) {
    error_log("CV upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'CV yüklenemedi']);
}
