<?php
require_once __DIR__ . '/../../services/session-init.php';

function is_json_request() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return stripos($accept, 'application/json') !== false || strtolower($xrw) === 'xmlhttprequest';
}

require_once '../../services/koneksi.php';
require_once '../../services/sanitizer.php';
require_once __DIR__ . '/../../services/logger.php';
global $conn;

// Check authentication
if (!isset($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kamu harus login terlebih dahulu.']);
    exit;
}

// CSRF protection: accept header or POST field
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

// Permission check: only users in 'penyewakan' mode can add items
if (!isset($_SESSION['auth_user']['current_role']) || $_SESSION['auth_user']['current_role'] !== 'penyewakan') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Hanya pengguna dengan mode Penyewakan dapat menambah barang.']);
    exit;
}

$user_id = $_SESSION['auth_user']['id'];
$name = isset($_POST['name']) ? sanitize_text($_POST['name'], 255) : '';
$category = isset($_POST['category']) ? sanitize_text($_POST['category'], 100) : '';
$pricePerDay = isset($_POST['pricePerDay']) ? sanitize_int($_POST['pricePerDay'], 0, 100000000) : 0;
$location = isset($_POST['location']) ? sanitize_text($_POST['location'], 150) : '';
$description = isset($_POST['description']) ? sanitize_textarea($_POST['description'], 2000) : '';

$errors = [];

// Validation
if (empty($name) || empty($category) || empty($location) || empty($description)) {
    $errors[] = "Semua field wajib diisi.";
}

// Length checks
if ($name !== '' && (mb_strlen($name) < 3 || mb_strlen($name) > 255)) {
    $errors[] = "Nama barang harus antara 3 sampai 255 karakter.";
}

if ($category !== '' && mb_strlen($category) > 100) {
    $errors[] = "Kategori terlalu panjang.";
}

if ($location !== '' && mb_strlen($location) > 150) {
    $errors[] = "Lokasi terlalu panjang.";
}

if ($description !== '' && (mb_strlen($description) < 10 || mb_strlen($description) > 2000)) {
    $errors[] = "Deskripsi harus antara 10 sampai 2000 karakter.";
}

// Price validation
if (!is_numeric($_POST['pricePerDay'] ?? null) || $pricePerDay < 1000 || $pricePerDay > 100000000) {
    $errors[] = "Harga per hari tidak valid.";
}

// Validate image
$image_data = null;
if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
    $tmp_file = $_FILES['imageFile']['tmp_name'];
    $file_size = $_FILES['imageFile']['size'];
    $file_type = mime_content_type($tmp_file);

    if (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        $errors[] = "Format gambar harus JPEG, PNG, GIF, atau WebP.";
    }

    if ($file_size > 10 * 1024 * 1024) {
        $errors[] = "Ukuran file terlalu besar (maksimal 10MB).";
    }

    if (empty($errors)) {
        $image_data = file_get_contents($tmp_file);
        if ($image_data === false) {
            $errors[] = "Gagal membaca file gambar.";
        }
    }
} else {
    $errors[] = "File gambar wajib diupload.";
}

if (!empty($errors)) {
    $msg = implode(' ', $errors);
    if (is_json_request()) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    $_SESSION['add_item_error'] = $msg;
    header('Location: index.php?error=1');
    exit;
}

try {
    // Insert item
    $stmt = $conn->prepare("INSERT INTO items (user_id, name, category, description, price_per_day, location, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssiss", $user_id, $name, $category, $description, $pricePerDay, $location, $image_data);
    
    if (!$stmt->execute()) {
        $errMsg = 'Gagal menyimpan barang. Coba lagi.';
        $stmt->close();
        if (is_json_request()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errMsg]);
            exit;
        }
        $_SESSION['add_item_error'] = $errMsg;
        header('Location: index.php?error=1');
        exit;
    }

    $item_id = $stmt->insert_id;
    $stmt->close();

    // Info log: item created
    rentalin_log_info('Item created', ['item_id' => $item_id, 'user_id' => $user_id]);

    if (is_json_request()) {
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Barang berhasil dipublikasikan.',
            'item_id' => $item_id,
            'redirect' => '../browse/index.php'
        ]);
        exit;
    }

    $_SESSION['add_item_success'] = 'Barang berhasil dipublikasikan.';
    header('Location: ../browse/index.php');
    exit;

} catch (Exception $e) {
    require_once __DIR__ . '/../../services/logger.php';
    rentalin_log_error('Exception in add-item/app.php', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    $errMsg = 'Terjadi kesalahan saat menyimpan barang.';
    if (is_json_request()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }
    $_SESSION['add_item_error'] = $errMsg;
    header('Location: index.php?error=1');
    exit;
}
?>
