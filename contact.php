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

function finish(string $status): void
{
    header('Location: /?contact=' . rawurlencode($status) . '#contact', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish('invalid');
}

// Honeypot: bots commonly complete every field, while people never see this one.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    finish('sent');
}

$now = time();
$lastSubmission = (int) ($_SESSION['last_contact_submission'] ?? 0);
if ($lastSubmission > 0 && ($now - $lastSubmission) < 30) {
    finish('slow');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$service = trim((string) ($_POST['service'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$privacy = (string) ($_POST['privacy'] ?? '');
$captcha = trim((string) ($_POST['captcha'] ?? ''));
$captchaAnswer = $_SESSION['captcha_answer'] ?? null;
$captchaIssued = (int) ($_SESSION['captcha_issued'] ?? 0);
unset($_SESSION['captcha_answer'], $_SESSION['captcha_issued']);

$captchaValid = is_int($captchaAnswer)
    && ctype_digit($captcha)
    && (int) $captcha === $captchaAnswer
    && $captchaIssued > 0
    && ($now - $captchaIssued) <= 900;

if (!$captchaValid) {
    finish('captcha');
}

$services = [
    'Web development',
    'Social media management',
    'Content writing',
    'Digital strategy',
    'Player social media management',
    'Athlete development and mentoring',
    'Something else',
];

$valid = mb_strlen($name) >= 2
    && mb_strlen($name) <= 80
    && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
    && mb_strlen($email) <= 120
    && in_array($service, $services, true)
    && mb_strlen($message) >= 10
    && mb_strlen($message) <= 2000
    && $privacy === 'yes';

if (!$valid) {
    finish('invalid');
}

$safeName = str_replace(["\r", "\n"], ' ', $name);
$safeEmail = str_replace(["\r", "\n"], '', $email);
$subject = '[SportsLink website] ' . $service . ' enquiry from ' . $safeName;
$body = "New SportsLink website enquiry\n\n"
    . "Name: {$safeName}\n"
    . "Email: {$safeEmail}\n"
    . "Service: {$service}\n\n"
    . "Message:\n{$message}\n";

$headers = [
    'From: SportsLink Website <website@sportslink.mt>',
    'Reply-To: ' . $safeEmail,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

$_SESSION['last_contact_submission'] = $now;
$sent = mail('hello@sportslink.mt', $subject, $body, implode("\r\n", $headers));
finish($sent ? 'sent' : 'failed');
