<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$first = random_int(2, 9);
$second = random_int(1, 9);

$_SESSION['captcha_answer'] = $first + $second;
$_SESSION['captcha_issued'] = time();

echo json_encode([
    'question' => "What is {$first} + {$second}?",
], JSON_THROW_ON_ERROR);
