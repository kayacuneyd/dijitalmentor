<?php
/**
 * JWT Authentication Helper
 * Basit JWT implementasyonu (composer-free)
 */

function generateToken($userId, $role)
{
    $secret = getenv('JWT_SECRET') ?: 'CHANGE_THIS_IN_PRODUCTION_12345678901234567890';

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60) // 7 gün
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyToken($token)
{
    $secret = getenv('JWT_SECRET') ?: 'CHANGE_THIS_IN_PRODUCTION_12345678901234567890';

    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return false;
    }

    list($base64UrlHeader, $base64UrlPayload, $signatureProvided) = $tokenParts;

    // Signature verify
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    if ($base64UrlSignature !== $signatureProvided) {
        return false;
    }

    // Decode payload
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload));
    $payloadData = json_decode($payload, true);

    // Expiry check
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return false;
    }

    return $payloadData;
}

function getCurrentUser()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader)) {
        return null;
    }

    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }

    $token = $matches[1];
    return verifyToken($token);
}

function requireAuth($allowedRoles = [])
{
    $user = getCurrentUser();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }

    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit();
    }

    return $user;
}

function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}
