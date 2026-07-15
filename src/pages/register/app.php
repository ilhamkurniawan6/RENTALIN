<?php
require_once __DIR__ . '/../../services/session-init.php';

// Helper: apakah request ini AJAX (fetch/XHR) yang mengharapkan JSON?
function is_json_request() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    return false;
}

require_once '../../services/koneksi.php';
global $conn;

require_once '../../services/sanitizer.php';

// Get POST data
$name = isset($_POST['name']) ? sanitize_text($_POST['name'], 255) : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$phone = isset($_POST['phone']) ? sanitize_phone($_POST['phone'], 30) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';
$terms = isset($_POST['terms']) ? $_POST['terms'] : false;

// CSRF protection: header or POST field
$headers = function_exists('getallheaders') ? getallheaders() : [];
$csrfHeader = '';
if (is_array($headers)) {
    $csrfHeader = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : (isset($headers['x-csrf-token']) ? $headers['x-csrf-token'] : '');
}
$csrfPost = isset($_POST['csrf_token']) ? trim($_POST['csrf_token']) : '';
$csrfToken = $csrfHeader ?: $csrfPost;
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

$errors = [];

// Validation
if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
    $errors[] = "Semua field wajib diisi.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format email tidak valid.";
}

if (strlen($password) < 6) {
    $errors[] = "Kata sandi minimal 6 karakter.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Kata sandi tidak cocok.";
}

if (!$terms) {
    $errors[] = "Kamu harus menyetujui syarat layanan.";
}

// If validation fails, return errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // Check if email already exists
    /* @var $conn mysqli */
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
        $checkEmail->close();
        exit;
    }

    $checkEmail->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $role = 'user';
    $stmt->bind_param("sssss", $name, $email, $phone, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Auto-login
        $_SESSION['auth_user'] = [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'current_role' => 'penyewa',
            'preferred_role' => 'penyewa',
        ];

        // Info log: user registered
        require_once '../../services/logger.php';
        rentalin_log_info('User registered', ['user_id' => $user_id, 'email' => $email]);

        // Tentukan target redirect setelah auto-login
        $redirectUrl = ($role === 'admin') ? '../admin-dashboard/index.php' : '../dashboard/index.php';

        if (is_json_request()) {
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Registrasi berhasil!',
                'redirect' => $redirectUrl
            ]);
        } else {
            header('Location: ' . $redirectUrl);
            exit;
        }
    } else {
        $msg = 'Terjadi kesalahan saat registrasi. Coba lagi.';
        if (is_json_request()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            $_SESSION['register_error'] = $msg;
            header('Location: index.php?error=1');
            exit;
        }
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
        require_once '../../services/logger.php';
        rentalin_log_error('Exception in register/app.php', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: terjadi kesalahan server.']);
        exit;
}
