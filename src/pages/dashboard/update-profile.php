<?php
require_once __DIR__ . '/../../services/session-init.php';
header('Content-Type: application/json; charset=utf-8');

// logger
if (file_exists(__DIR__ . '/../../services/logger.php')) {
    require_once __DIR__ . '/../../services/logger.php';
}

// Require authenticated
if (!isset($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi.']);
    exit;
}

$user = $_SESSION['auth_user'];

// CSRF check (token can be in header or post)
$csrfHeader = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
$csrfPost = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$csrf = $csrfHeader ?: $csrfPost;
if (!empty($_SESSION['csrf_token']) && $csrf !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Basic validation
if ($name === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nama dan email wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email tidak valid.']);
    exit;
}

// normalize values for storage; escape only when rendering to HTML
$name = trim($name);
$email = strtolower(trim($email));
$phone = trim($phone);

$updated = false;

// Try update DB if koneksi available
$dbUpdated = false;
try {
    if (file_exists(__DIR__ . '/../../services/koneksi.php')) {
        require_once __DIR__ . '/../../services/koneksi.php';
        if (isset($conn) && $conn) {
            // Use prepared statement with proper types
            $userIdInt = (int)($user['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $phone, $userIdInt);
                $executed = mysqli_stmt_execute($stmt);
                if ($executed) {
                    $dbUpdated = true;
                } else {
                    $errno = mysqli_stmt_errno($stmt);
                    if (function_exists('app_log')) app_log('update-profile', 'DB execute failed for user ' . $userIdInt . ', errno=' . $errno . ', error=' . mysqli_stmt_error($stmt));
                    if ($errno === 1062) {
                        http_response_code(409);
                        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh akun lain.']);
                        mysqli_stmt_close($stmt);
                        exit;
                    }
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan ke database.']);
                    mysqli_stmt_close($stmt);
                    exit;
                }
                mysqli_stmt_close($stmt);
            } else {
                if (function_exists('app_log')) app_log('update-profile', 'DB prepare failed when updating user ' . $userIdInt);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query update profil.']);
                exit;
            }
        }
    }
} catch (Exception $e) {
    if (function_exists('app_log')) app_log('update-profile', 'Exception updating profile for user ' . ($user['id'] ?? 'unknown') . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server saat menyimpan profil.']);
    exit;
}

// Update session data
$_SESSION['auth_user']['name'] = $name;
$_SESSION['auth_user']['email'] = $email;
$_SESSION['auth_user']['phone'] = $phone;

if (function_exists('app_log')) app_log('update-profile', 'Profile updated for user ' . ($user['id'] ?? 'unknown') . ', db=' . ($dbUpdated ? 'yes' : 'no'));

echo json_encode(['success' => true, 'message' => 'Profil berhasil disimpan.', 'db' => $dbUpdated, 'user' => $_SESSION['auth_user']]);
exit;

?>
