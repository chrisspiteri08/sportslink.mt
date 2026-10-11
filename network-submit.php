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
    header('Location: /network/?network=' . rawurlencode($status) . '#network-form', true, 303);
    exit;
}

function field(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

function clean_line(string $value): string
{
    return str_replace(["\r", "\n"], ' ', $value);
}

function valid_length(string $value, int $min, int $max): bool
{
    $length = mb_strlen($value);
    return $length >= $min && $length <= $max;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish('invalid');
}

if (field('website') !== '') {
    finish('sent');
}

$now = time();
$lastSubmission = (int) ($_SESSION['last_network_submission'] ?? 0);
if ($lastSubmission > 0 && ($now - $lastSubmission) < 30) {
    finish('slow');
}

$captcha = field('captcha');
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

if (field('privacy') !== 'yes') {
    finish('invalid');
}

$route = field('route');
$subject = '';
$replyTo = '';
$body = '';

if ($route === 'athlete') {
    $name = field('athlete_name');
    $email = field('athlete_email');
    $phone = field('athlete_phone');
    $sport = field('sport');
    $level = field('current_level');
    $achievements = field('achievements');
    $goals = field('goals');
    $links = field('athlete_links');
    $sponsors = field('current_sponsors');
    $support = field('support_sought');
    $about = field('athlete_about');
    $levels = ['Development / Youth', 'Club', 'National', 'International', 'Professional', 'Other'];

    $valid = valid_length($name, 2, 100)
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && valid_length($email, 5, 150)
        && valid_length($phone, 5, 30)
        && valid_length($sport, 2, 100)
        && in_array($level, $levels, true)
        && valid_length($achievements, 2, 2000)
        && valid_length($goals, 2, 2000)
        && mb_strlen($links) <= 1000
        && mb_strlen($sponsors) <= 1500
        && valid_length($support, 5, 2000)
        && valid_length($about, 10, 3000);

    if (!$valid) {
        finish('invalid');
    }

    $replyTo = clean_line($email);
    $subject = '[SportsLink Network] Athlete: ' . clean_line($name) . ' — ' . clean_line($sport);
    $body = "New athlete network submission\n\n"
        . "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nSport: {$sport}\nCurrent level: {$level}\n\n"
        . "Key achievements:\n{$achievements}\n\nUpcoming goals / competitions:\n{$goals}\n\n"
        . "Social media / website links:\n{$links}\n\nCurrent sponsors:\n{$sponsors}\n\n"
        . "Support / partnership sought:\n{$support}\n\nAbout the athlete:\n{$about}\n";
} elseif ($route === 'business') {
    $company = field('company');
    $contact = field('business_contact');
    $role = field('business_role');
    $email = field('business_email');
    $phone = field('business_phone');
    $industry = field('industry');
    $sports = field('sports_interest');
    $budget = field('budget');
    $objectives = field('objectives');
    $additional = field('business_additional');
    $allowedInterests = ['Athlete Sponsorship', 'Brand Ambassador', 'Content Partnership', 'Events', 'CSR / Community', 'Product / Service Support', 'Not Sure Yet'];
    $submittedInterests = $_POST['interests'] ?? [];
    $interests = is_array($submittedInterests)
        ? array_values(array_intersect($allowedInterests, array_map('strval', $submittedInterests)))
        : [];

    $valid = valid_length($company, 2, 150)
        && valid_length($contact, 2, 100)
        && valid_length($role, 2, 100)
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && valid_length($email, 5, 150)
        && valid_length($phone, 5, 30)
        && valid_length($industry, 2, 120)
        && count($interests) > 0
        && valid_length($sports, 2, 300)
        && mb_strlen($budget) <= 200
        && valid_length($objectives, 10, 2500)
        && mb_strlen($additional) <= 2500;

    if (!$valid) {
        finish('invalid');
    }

    $replyTo = clean_line($email);
    $interestText = implode(', ', $interests);
    $subject = '[SportsLink Network] Business: ' . clean_line($company);
    $body = "New business network submission\n\n"
        . "Company: {$company}\nContact: {$contact}\nRole: {$role}\nEmail: {$email}\nPhone: {$phone}\nIndustry: {$industry}\n\n"
        . "Partnership interests: {$interestText}\nSports of interest: {$sports}\nBudget / support level: {$budget}\n\n"
        . "Partnership objectives:\n{$objectives}\n\nAdditional information:\n{$additional}\n";
} else {
    finish('invalid');
}

$headers = [
    'From: SportsLink Network <website@sportslink.mt>',
    'Reply-To: ' . $replyTo,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

$_SESSION['last_network_submission'] = $now;
$sent = mail('hello@sportslink.mt', $subject, $body, implode("\r\n", $headers));
finish($sent ? 'sent' : 'failed');
