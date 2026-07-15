<?php

require_once __DIR__ . '/session-init.php';

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/url-helper.php';

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

$data = getRequestData();

$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    sendResponse([
        'message' => 'Email dan kata sandi wajib diisi.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse([
        'message' => 'Format email tidak valid.',
    ], 422);
}

$stmt = $conn->prepare('SELECT id, name, email, phone, avatar, password, role, current_role, preferred_role FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    sendResponse([
        'message' => 'Gagal menyiapkan query login.',
    ], 500);
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user || !password_verify($password, (string) $user['password'])) {
    sendResponse([
        'message' => 'Email atau kata sandi salah.',
    ], 401);
}

session_regenerate_id(true);

$_SESSION['auth_user'] = [
    'id' => (int) $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'avatar' => rentalin_normalize_avatar_url($user['avatar'] ?? ''),
    'role' => $user['role'],
    'current_role' => $user['current_role'] ?? 'penyewa',
    'preferred_role' => $user['preferred_role'] ?? 'penyewa',
];

if (wantsJsonResponse()) {
    sendResponse([
        'message' => 'Login berhasil.',
        'user' => $_SESSION['auth_user'],
    ]);
}

sendResponse([
    'redirect' => '../pages/dashboard/index.php',
], 302);
