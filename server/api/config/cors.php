<?php
// Allowed origins
$allowedOrigins = [
    'https://dijitalmentor.de',
    'https://www.dijitalmentor.de'
];

// Optional: allow Vercel preview deployments when this env flag is true
$allowVercelPreview = filter_var(getenv('ALLOW_VERCEL_PREVIEW_ORIGINS'), FILTER_VALIDATE_BOOLEAN);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isAllowed = in_array($origin, $allowedOrigins, true);

if (!$isAllowed && $allowVercelPreview && preg_match('~^https://[a-z0-9-]+\\.vercel\\.app$~i', $origin)) {
    $isAllowed = true;
}

if ($isAllowed && $origin) {
    header("Access-Control-Allow-Origin: {$origin}");
    header("Vary: Origin");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code($isAllowed ? 200 : 403);
    exit();
}

if (!$isAllowed) {
    http_response_code(403);
    echo json_encode(['error' => 'CORS not allowed for this origin']);
    exit();
}
