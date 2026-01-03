<?php
/**
 * JWT Helper Functions
 * Simple JWT implementation for password reset tokens
 */

/**
 * Generate JWT token
 * 
 * @param array $payload Data to encode in token
 * @param string $secret Secret key for signing
 * @param int $expiresIn Expiration time in seconds (default 1 hour)
 * @return string JWT token
 */
function generateJWT($payload, $secret, $expiresIn = 3600) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresIn;
    $payload = json_encode($payload);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Verify and decode JWT token
 * 
 * @param string $token JWT token to verify
 * @param string $secret Secret key for verification
 * @return array|null Decoded payload or null if invalid
 */
function verifyJWT($token, $secret) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return null;
    }
    
    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
    
    // Verify signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignatureCheck = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $base64UrlSignatureCheck) {
        return null;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
    
    if (!$payload) {
        return null;
    }
    
    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }
    
    return $payload;
}
