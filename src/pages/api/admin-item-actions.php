<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/role-helper.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user']) || !rentalin_is_admin_like($_SESSION['auth_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Validate CSRF token
$csrfToken = trim((string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? ''));
if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid.']);
    exit;
}

// Handle DELETE operation
if ($method === 'DELETE' || (isset($input['action']) && $input['action'] === 'delete')) {
    $itemId = isset($input['item_id']) ? (int) $input['item_id'] : 0;
    
    if ($itemId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID barang tidak valid.']);
        exit;
    }

    // Get item details before deletion
    $getItemSql = 'SELECT id, name FROM items WHERE id = ?';
    $getItemStmt = $conn->prepare($getItemSql);
    if (!$getItemStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query item.']);
        exit;
    }

    $getItemStmt->bind_param('i', $itemId);
    $getItemStmt->execute();
    $itemResult = $getItemStmt->get_result();
    $item = $itemResult->fetch_assoc();
    $getItemStmt->close();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
        exit;
    }

    // Delete the item
    $deleteSql = 'DELETE FROM items WHERE id = ?';
    $deleteStmt = $conn->prepare($deleteSql);
    if (!$deleteStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query delete.']);
        exit;
    }

    $deleteStmt->bind_param('i', $itemId);
    if (!$deleteStmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus barang.']);
        $deleteStmt->close();
        exit;
    }
    $deleteStmt->close();

    echo json_encode(['success' => true, 'message' => 'Barang "' . htmlspecialchars($item['name']) . '" berhasil dihapus.']);
    exit;
}

// Handle UPDATE operation
if ($method === 'PUT' || (isset($input['action']) && $input['action'] === 'update')) {
    $itemId = isset($input['item_id']) ? (int) $input['item_id'] : 0;
    $name = trim((string) ($input['name'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $pricePerDay = isset($input['price_per_day']) ? (int) $input['price_per_day'] : 0;
    $location = trim((string) ($input['location'] ?? ''));
    $availability = isset($input['availability']) ? (bool) $input['availability'] : true;

    if ($itemId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID barang tidak valid.']);
        exit;
    }

    if (empty($name) || strlen($name) > 150) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nama barang harus diisi dan maksimal 150 karakter.']);
        exit;
    }

    if (empty($category)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kategori barang harus diisi.']);
        exit;
    }

    if ($pricePerDay <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Harga per hari harus lebih dari 0.']);
        exit;
    }

    if (empty($location)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lokasi barang harus diisi.']);
        exit;
    }

    // Check if item exists
    $checkSql = 'SELECT id FROM items WHERE id = ?';
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query cek barang.']);
        exit;
    }

    $checkStmt->bind_param('i', $itemId);
    $checkStmt->execute();
    if (!$checkStmt->get_result()->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Update item
    $updateSql = 'UPDATE items SET name = ?, category = ?, description = ?, price_per_day = ?, location = ?, availability = ? WHERE id = ?';
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query update.']);
        exit;
    }

    $availabilityInt = $availability ? 1 : 0;
    $updateStmt->bind_param('sssiiii', $name, $category, $description, $pricePerDay, $location, $availabilityInt, $itemId);
    if (!$updateStmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui barang.']);
        $updateStmt->close();
        exit;
    }
    $updateStmt->close();

    echo json_encode(['success' => true, 'message' => 'Barang "' . htmlspecialchars($name) . '" berhasil diperbarui.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method tidak didukung.']);
exit;
