<?php
require_once 'includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$code = '';
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
for ($i = 0; $i < 6; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha_text'] = $code;

if (function_exists('imagecreatetruecolor')) {
    header('Content-Type: image/png');
    $width = 160;
    $height = 50;
    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, 245, 247, 250);
    $fg = imagecolorallocate($img, 33, 37, 41);
    $noise1 = imagecolorallocate($img, 67, 97, 238);
    $noise2 = imagecolorallocate($img, 72, 149, 239);
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);
    for ($i = 0; $i < 8; $i++) {
        imageline($img, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), ($i % 2) ? $noise1 : $noise2);
    }
    $x = 10;
    for ($i = 0; $i < strlen($code); $i++) {
        $y = random_int(10, 25);
        imagestring($img, 5, $x, $y, $code[$i], $fg);
        $x += 22;
    }
    imagepng($img);
    imagedestroy($img);
} else {
    header('Content-Type: image/svg+xml');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="50">'
         . '<rect width="160" height="50" fill="#f5f7fa"/>';
    for ($i = 0; $i < 8; $i++) {
        $x1 = random_int(0, 160); $y1 = random_int(0, 50);
        $x2 = random_int(0, 160); $y2 = random_int(0, 50);
        $color = ($i % 2) ? '#4361ee' : '#4895ef';
        $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="1"/>';
    }
    $svg .= '<text x="10" y="32" font-family="monospace" font-size="24" fill="#212529">'.$code.'</text>'
          . '</svg>';
    echo $svg;
}
