<?php
require_once '../config/cors.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$user = getCurrentUser();

if ($user) {
    echo json_encode(['success' => true, 'valid' => true, 'user' => $user]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'valid' => false]);
}
