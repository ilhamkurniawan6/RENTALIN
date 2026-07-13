<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/url-helper.php';
header('Content-Type: application/json');

require_once '../../services/koneksi.php';
global $conn;
require_once '../../services/sanitizer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$csrfHeader = '';
if (is_array($headers)) {
    $csrfHeader = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : (isset($headers['x-csrf-token']) ? $headers['x-csrf-token'] : '');
}

$csrfPost = isset($_POST['csrf_token']) ? trim((string) $_POST['csrf_token']) : '';
$csrfToken = $csrfHeader ?: $csrfPost;
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
// sanitize email local part/whitespace
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']);

$errors = [];

// Validation
if (empty($email) || empty($password)) {
    $errors[] = "Email dan kata sandi wajib diisi.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format email tidak valid.";
}

if (strlen($password) < 6) {
    $errors[] = "Kata sandi minimal 6 karakter.";
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // Find user by email
    $stmt = $conn->prepare("SELECT id, name, email, phone, avatar, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email atau kata sandi salah.']);
        $stmt->close();
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email atau kata sandi salah.']);
        exit;
    }

    // Get current_role from database
    $roleStmt = $conn->prepare("SELECT current_role, preferred_role FROM users WHERE id = ? LIMIT 1");
    $roleStmt->bind_param("i", $user['id']);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $userRole = $roleResult->fetch_assoc();
    $roleStmt->close();

    // Create session
    $_SESSION['auth_user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'avatar' => rentalin_normalize_avatar_url($user['avatar'] ?? ''),
        'role' => $user['role'],
        'current_role' => $userRole['current_role'] ?? 'penyewa',
        'preferred_role' => $userRole['preferred_role'] ?? 'penyewa',
    ];

    // Set remember me cookie (30 days)
    if ($remember) {
        setcookie('rentalin_remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
    }

    // Info log: user logged in
    require_once __DIR__ . '/../../services/logger.php';
    rentalin_log_info('User logged in', ['user_id' => $user['id'], 'email' => $user['email']]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil.',
        'user' => $_SESSION['auth_user'],
        'redirect' => '../dashboard/index.php'
    ]);
    exit;

} catch (Exception $e) {
    require_once __DIR__ . '/../../services/logger.php';
    rentalin_log_error('Exception in login/app.php', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: terjadi kesalahan server.']);
    exit;
}
?>
