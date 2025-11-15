<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'includes/config.php';

header('Content-Type: application/json');

if (!defined('ALTCHA_SECRET')) {
    define('ALTCHA_SECRET', '2d63983b6e462a32049ebd87b4ab4a23c57a3881093d0e63ddc5643f26835b86');
}

function generateSalt()
{
    return bin2hex(random_bytes(12));
}

function generateNumber($max = 50000)
{
    return random_int(1, $max);
}

try {
    $algorithm_php  = 'sha256';
    $algorithm_json = 'SHA-256';
    $maxNumber      = 50000;

    $salt = generateSalt();
    $number = generateNumber($maxNumber);

    // Remove spaces
    $salt = str_replace(' ', '', $salt);
    $challenge = str_replace(' ', '', hash($algorithm_php, $salt . $number));

    $expires = time() + 300;
    $saltWithExpiry = $salt . '?expires=' . $expires;

    // JSON for signature must be exact
    $signatureData = json_encode([
        'algorithm' => $algorithm_json,
        'challenge' => $challenge,
        'maxnumber' => $maxNumber,
        'salt' => $saltWithExpiry
    ], JSON_UNESCAPED_SLASHES);

    $signature = hash_hmac('sha256', $signatureData, ALTCHA_SECRET);

    // Return clean JSON for ALTCHA
    echo json_encode([
        'algorithm' => $algorithm_json,
        'challenge' => $challenge,
        'maxnumber' => $maxNumber,
        'salt' => $saltWithExpiry,
        'signature' => $signature
    ]);

    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Challenge generation failed']);
    exit;
}
