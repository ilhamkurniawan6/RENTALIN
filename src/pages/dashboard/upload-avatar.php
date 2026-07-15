<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/url-helper.php';
header('Content-Type: application/json; charset=utf-8');
// logger
if (file_exists(__DIR__ . '/../../services/logger.php')) {
    require_once __DIR__ . '/../../services/logger.php';
}

if (!isset($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi.']);
    exit;
}

$user = $_SESSION['auth_user'];
$userIdInt = (int) ($user['id'] ?? 0);

if ($userIdInt <= 0) {
    http_response_code(500);
    if (function_exists('app_log')) app_log('upload-avatar', 'Missing valid user id in session when uploading avatar');
    echo json_encode(['success' => false, 'message' => 'Sesi pengguna tidak valid.']);
    exit;
}

// CSRF token
$csrfHeader = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
$csrfPost = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$csrf = $csrfHeader ?: $csrfPost;
if (!empty($_SESSION['csrf_token']) && $csrf !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    http_response_code(400);
    if (function_exists('app_log')) app_log('upload-avatar', 'No avatar file uploaded for user ' . ($user['id'] ?? 'unknown'));
    echo json_encode(['success' => false, 'message' => 'File avatar tidak ditemukan.']);
    exit;
}

$file = $_FILES['avatar'];
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
    exit;
}

$info = getimagesize($file['tmp_name']);
if ($info === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File bukan gambar yang valid.']);
    exit;
}

$mime = $info['mime'];
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hanya format JPG/PNG yang diizinkan.']);
    exit;
}

$ext = $allowed[$mime];
// Store avatars outside the webroot for safety. Storage path is
// <project_root>/storage/avatars. From this file (`src/pages/dashboard`),
// go up three levels to project root.
$storageDir = __DIR__ . '/../../../storage/avatars';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$filename = sprintf('avatar_%d_%s.%s', $userIdInt, time(), $ext);
$filepath = $storageDir . DIRECTORY_SEPARATOR . $filename;

// Resize to max 400px square while keeping aspect ratio
$maxDim = 400;
try {
    $sourceData = file_get_contents($file['tmp_name']);
    if ($sourceData === false) {
        throw new Exception('Gagal membaca file avatar.');
    }

    $srcImg = imagecreatefromstring($sourceData);
    if ($srcImg === false) {
        throw new Exception('Gagal memproses gambar avatar.');
    }

    $w = imagesx($srcImg);
    $h = imagesy($srcImg);
    $scale = min($maxDim / $w, $maxDim / $h, 1);
    $nw = (int)($w * $scale);
    $nh = (int)($h * $scale);

    $dstImg = imagecreatetruecolor($nw, $nh);
    // preserve PNG transparency
    if ($mime === 'image/png') {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);

    if ($mime === 'image/jpeg') {
        $ok = imagejpeg($dstImg, $filepath, 85);
    } else {
        $ok = imagepng($dstImg, $filepath);
    }

    // Free image resources by clearing references and running GC for GdImage objects
    $srcImg = null;
    $dstImg = null;
    $sourceData = null;
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }

    if (empty($ok) || !file_exists($filepath)) {
        // attempt fallback move
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            if (function_exists('app_log')) app_log('upload-avatar', 'Failed to save avatar file for user ' . ($userIdInt ?? 'unknown'));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan avatar ke server.']);
            exit;
        }
    }

    // ensure file permissions are safe
    @chmod($filepath, 0644);
} catch (Exception $e) {
    // fallback: move uploaded file and validate
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        if (function_exists('app_log')) app_log('upload-avatar', 'Exception saving avatar for user ' . ($userIdInt ?? 'unknown') . ': ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan avatar (fallback).']);
        exit;
    }
    @chmod($filepath, 0644);
}

// update DB if available
$dbUpdated = false;
try {
    if (file_exists(__DIR__ . '/../../services/koneksi.php')) {
        require_once __DIR__ . '/../../services/koneksi.php';
        if (isset($conn) && $conn) {
            // Store only the filename in DB; the public URL is derived when rendering.
            $avatarValue = $filename;
            $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $avatarValue, $userIdInt);
                $executeOk = mysqli_stmt_execute($stmt);
                $dbUpdated = $executeOk && mysqli_stmt_affected_rows($stmt) > 0;
                mysqli_stmt_close($stmt);
            } else {
                $dbUpdated = false;
                if (function_exists('app_log')) app_log('upload-avatar', 'DB prepare failed when updating avatar for user ' . $userIdInt);
            }
        }
    }
} catch (Exception $e) {
    // ignore
}

// update session (public facing URL served via `src/pages/media/avatar.php`)
$publicUrl = rentalin_avatar_url_from_filename($filename);
$_SESSION['auth_user']['avatar'] = $publicUrl;

if (function_exists('app_log')) app_log('upload-avatar', 'Avatar uploaded for user ' . $userIdInt . ', file=' . $filename . ', db=' . ($dbUpdated ? 'yes' : 'no'));

echo json_encode(['success' => true, 'message' => 'Avatar berhasil diunggah.', 'avatar' => $publicUrl, 'db' => $dbUpdated]);
exit;

?>
