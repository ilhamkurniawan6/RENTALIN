<?php
require_once __DIR__ . '/../../services/session-init.php';
header('Content-Type: application/json');

require_once '../../services/koneksi.php';
global $conn;

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal. Periksa konfigurasi DB.']);
    exit;
}

// Check authentication
if (!isset($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kamu harus login terlebih dahulu.']);
    exit;
}

// CSRF protection: require header X-CSRF-Token
// Read CSRF token from header or POST body as fallback
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

$user_id = $_SESSION['auth_user']['id'];
$new_role = isset($_POST['role']) ? trim($_POST['role']) : '';

// Validate role
if (!in_array($new_role, ['penyewa', 'penyewakan'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Role tidak valid.']);
    exit;
}

try {
    // Update current_role in database
    $stmt = $conn->prepare("UPDATE users SET current_role = ? WHERE id = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("si", $new_role, $user_id);

    if ($stmt->execute()) {
        // Update session
        $_SESSION['auth_user']['current_role'] = $new_role;
        // Info log: role changed
        require_once __DIR__ . '/../../services/logger.php';
        rentalin_log_info('User role changed', ['user_id' => $user_id, 'new_role' => $new_role]);

        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Role berhasil diubah ke ' . $new_role,
            'current_role' => $new_role
        ]);
        exit;
    } else {
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah role. ' . $err]);
        exit;
    }

} catch (Exception $e) {
    require_once __DIR__ . '/../../services/logger.php';
    rentalin_log_error('Exception in switch-role', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: terjadi kesalahan server.']);
}
?>
