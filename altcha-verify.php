<?php
define('ALTCHA_SECRET', '2d63983b6e462a32049ebd87b4ab4a23c57a3881093d0e63ddc5643f26835b86');

function verifyAltcha($payload) {
    if (empty($payload)) {
        return false;
    }

    // Decode base64 payload
    $decoded = base64_decode($payload);
    if (!$decoded) {
        return false;
    }

    $data = json_decode($decoded, true);
    if (!$data) {
        return false;
    }

    // Required fields
    $required = ['algorithm', 'challenge', 'number', 'salt', 'signature'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            return false;
        }
    }

    // Extract salt without expiration parameter
    $saltParts = explode('?expires=', $data['salt']);
    $cleanSalt = $saltParts[0];

    // Verify signature
    $signatureData = json_encode([
        'algorithm' => $data['algorithm'],
        'challenge' => $data['challenge'],
        'maxnumber' => $data['maxnumber'] ?? 50000,
        'salt' => $data['salt']
    ]);
    
    $expectedSignature = hash_hmac('sha256', $signatureData, ALTCHA_SECRET);
    
    if (!hash_equals($expectedSignature, $data['signature'])) {
        return false;
    }

    // Check expiration if present
    if (count($saltParts) === 2) {
        $expires = (int)$saltParts[1];
        if (time() > $expires) {
            return false; // Challenge expired
        }
    }

    // Verify solution - use clean salt for hash computation
    $computedHash = hash($data['algorithm'], $cleanSalt . $data['number']);
    
    return hash_equals($computedHash, $data['challenge']);
}
?>