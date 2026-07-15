<?php

require_once __DIR__ . '/session-init.php';

require_once __DIR__ . '/koneksi.php';

require_once __DIR__ . '/sanitizer.php';

header('X-Content-Type-Options: nosniff');

function wantsJsonResponse(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    return stripos($accept, 'application/json') !== false || stripos($contentType, 'application/json') !== false;
}

function sendResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    if (wantsJsonResponse()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!empty($payload['redirect'])) {
        header('Location: ' . $payload['redirect']);
        exit;
    }

    echo $payload['message'] ?? '';
    exit;
}

function getRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $decoded = json_decode($rawInput ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse([
        'message' => 'Metode tidak diizinkan.',
    ], 405);
}

// CSRF protection: accept header or payload field (for JSON or form posts)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$csrfHeader = '';
if (is_array($headers)) {
    $csrfHeader = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : (isset($headers['x-csrf-token']) ? $headers['x-csrf-token'] : '');
}
$postData = getRequestData();
$csrfPost = isset($postData['csrf_token']) ? trim($postData['csrf_token']) : '';
$csrfToken = $csrfHeader ?: $csrfPost;
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    sendResponse(['message' => 'Token CSRF tidak valid.'], 403);
}

$data = getRequestData();

$name = sanitize_text((string) ($data['name'] ?? ''), 255);
$email = strtolower(trim((string) ($data['email'] ?? '')));
$phone = sanitize_phone((string) ($data['phone'] ?? ''), 30);
$password = (string) ($data['password'] ?? '');
$confirmPassword = (string) ($data['confirmPassword'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
    sendResponse([
        'message' => 'Semua field wajib diisi.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse([
        'message' => 'Format email tidak valid.',
    ], 422);
}

if (strlen($password) < 6) {
    sendResponse([
        'message' => 'Kata sandi minimal 6 karakter.',
    ], 422);
}

if ($password !== $confirmPassword) {
    sendResponse([
        'message' => 'Kata sandi tidak cocok.',
    ], 422);
}

$checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
if (!$checkStmt) {
    sendResponse([
        'message' => 'Gagal menyiapkan pengecekan user.',
    ], 500);
}

$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $checkStmt->close();
    sendResponse([
        'message' => 'Email sudah terdaftar.',
    ], 409);
}

$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
if ($hashedPassword === false) {
    sendResponse([
        'message' => 'Gagal memproses kata sandi.',
    ], 500);
}

$insertStmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role, current_role, preferred_role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
if (!$insertStmt) {
    sendResponse([
        'message' => 'Gagal menyiapkan simpan user.',
    ], 500);
}

$role = 'user';
$currentRole = 'penyewa';
$preferredRole = 'penyewa';
$insertStmt->bind_param('sssssss', $name, $email, $phone, $hashedPassword, $role, $currentRole, $preferredRole);

if (!$insertStmt->execute()) {
    $insertStmt->close();
    sendResponse([
        'message' => 'Registrasi gagal.',
    ], 500);
}

$newUserId = $insertStmt->insert_id;
$insertStmt->close();

$_SESSION['auth_user'] = [
    'id' => $newUserId,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'avatar' => null,
    'role' => $role,
    'current_role' => $currentRole,
    'preferred_role' => $preferredRole,
];

// Prevent session fixation after registration
if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

// Info log for API register
require_once __DIR__ . '/logger.php';
rentalin_log_info('User registered (API)', ['user_id' => $newUserId, 'email' => $email]);

if (wantsJsonResponse()) {
    sendResponse([
        'message' => 'Registrasi berhasil.',
        'user' => $_SESSION['auth_user'],
        'redirect' => '../pages/login/index.php?registered=1',
    ], 201);
}

sendResponse([
    'redirect' => '../pages/login/index.php?registered=1',
], 302);
