<?php
/**
 * Authentication Login Endpoint
 * POST /src/pages/api/auth_login.php
 * 
 * Body: { email, password }
 * Response: { success, message, user, csrf_token }
 */

require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/url-helper.php';

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

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

// ============================================================================
// CSRF TOKEN HELPER
// ============================================================================

function ensureCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

// ============================================================================
// MAIN LOGIN LOGIC
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse([
        'success' => false,
        'message' => 'Metode tidak diizinkan.',
    ], 405);
}

$data = getRequestData();

$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    sendResponse([
        'success' => false,
        'message' => 'Email dan kata sandi wajib diisi.',
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse([
        'success' => false,
        'message' => 'Format email tidak valid.',
    ], 422);
}

// Query user
$stmt = $conn->prepare('SELECT id, name, email, phone, avatar, password, role, current_role, preferred_role FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    sendResponse([
        'success' => false,
        'message' => 'Database error: ' . $conn->error,
    ], 500);
}

$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
    $stmt->close();
    sendResponse([
        'success' => false,
        'message' => 'Database error: ' . $conn->error,
    ], 500);
}

$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    sendResponse([
        'success' => false,
        'message' => 'Email atau kata sandi salah.',
    ], 401);
}

// Verify password
if (!password_verify($password, (string) $user['password'])) {
    sendResponse([
        'success' => false,
        'message' => 'Email atau kata sandi salah.',
    ], 401);
}

// Regenerate session ID for security
session_regenerate_id(true);

// Set session user
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

// Get CSRF token
$csrfToken = ensureCsrfToken();

// Send success response
if (wantsJsonResponse()) {
    sendResponse([
        'success' => true,
        'message' => 'Login berhasil.',
        'user' => $_SESSION['auth_user'],
        'csrf_token' => $csrfToken,
    ]);
}

sendResponse([
    'success' => true,
    'redirect' => '../dashboard/index.php',
], 302);
