<?php
/**
 * Items CRUD API Endpoint
 * GET    /api/items-crud.php?action=list              - List items by current user
 * GET    /api/items-crud.php?action=detail&id=X       - Get single item detail
 * POST   /api/items-crud.php                          - Create new item
 * PUT    /api/items-crud.php                          - Update item
 * DELETE /api/items-crud.php?action=delete&id=X       - Delete item
 * 
 * All responses: { success: true/false, message: "", data: {...} }
 */

require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/sanitizer.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function respond(bool $success, string $message = '', $data = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireLogin(): array
{
    if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        respond(false, 'Anda harus login terlebih dahulu', null, 401);
    }
    return $_SESSION['auth_user'];
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

function validateItemInput(array $data): array
{
    $errors = [];
    
    $name = trim($data['name'] ?? '');
    if (empty($name) || strlen($name) < 3) {
        $errors[] = 'Nama barang minimal 3 karakter';
    }
    
    $category = trim($data['category'] ?? '');
    if (empty($category)) {
        $errors[] = 'Kategori harus dipilih';
    }
    
    $description = trim($data['description'] ?? '');
    if (empty($description) || strlen($description) < 10) {
        $errors[] = 'Deskripsi minimal 10 karakter';
    }
    
    $price = (int) ($data['price_per_day'] ?? 0);
    if ($price <= 0) {
        $errors[] = 'Harga per hari harus lebih dari 0';
    }
    
    $location = trim($data['location'] ?? '');
    if (empty($location)) {
        $errors[] = 'Lokasi harus diisi';
    }
    
    return $errors;
}

// ============================================================================
// GET ACTIONS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $action = strtolower(trim($_GET['action'] ?? ''));
    
    // List user items
    if ($action === 'list' || $action === '') {
        $stmt = $conn->prepare('
            SELECT id, user_id, name, category, description, price_per_day, location, 
                   availability, created_at, updated_at 
            FROM items 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 100
        ');
        
        if (!$stmt) {
            respond(false, 'Database error: ' . $conn->error, null, 500);
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'description' => $row['description'],
                'pricePerDay' => (int) $row['price_per_day'],
                'location' => $row['location'],
                'availability' => (bool) $row['availability'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ];
        }
        
        $stmt->close();
        respond(true, 'Daftar barang Anda', ['items' => $items]);
    }
    
    // Get single item detail
    if ($action === 'detail') {
        $itemId = (int) ($_GET['id'] ?? 0);
        
        if ($itemId <= 0) {
            respond(false, 'ID barang tidak valid', null, 400);
        }
        
        $stmt = $conn->prepare('
            SELECT id, user_id, name, category, description, price_per_day, location, 
                   availability, created_at, updated_at 
            FROM items 
            WHERE id = ? AND user_id = ? 
            LIMIT 1
        ');
        
        if (!$stmt) {
            respond(false, 'Database error: ' . $conn->error, null, 500);
        }
        
        $stmt->bind_param('ii', $itemId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if (!$item) {
            respond(false, 'Barang tidak ditemukan atau bukan milik Anda', null, 404);
        }
        
        respond(true, 'Detail barang', [
            'item' => [
                'id' => (int) $item['id'],
                'name' => $item['name'],
                'category' => $item['category'],
                'description' => $item['description'],
                'pricePerDay' => (int) $item['price_per_day'],
                'location' => $item['location'],
                'availability' => (bool) $item['availability'],
                'createdAt' => $item['created_at'],
                'updatedAt' => $item['updated_at'],
            ]
        ]);
    }
    
    respond(false, 'Action tidak dikenali', null, 400);
}

// ============================================================================
// POST - CREATE ITEM
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $data = getRequestData();
    
    // Validate input
    $errors = validateItemInput($data);
    if (!empty($errors)) {
        respond(false, implode(', ', $errors), null, 422);
    }
    
    $name = trim($data['name']);
    $category = trim($data['category']);
    $description = trim($data['description']);
    $price = (int) $data['price_per_day'];
    $location = trim($data['location']);
    $availability = isset($data['availability']) ? (bool) $data['availability'] : true;
    
    $stmt = $conn->prepare('
        INSERT INTO items (user_id, name, category, description, price_per_day, location, availability)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error, null, 500);
    }
    
    $availabilityInt = $availability ? 1 : 0;
    $stmt->bind_param('issisii', $userId, $name, $category, $description, $price, $location, $availabilityInt);
    
    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Gagal membuat barang: ' . $conn->error, null, 500);
    }
    
    $newItemId = $conn->insert_id;
    $stmt->close();
    
    respond(true, 'Barang berhasil dibuat', [
        'itemId' => $newItemId,
        'item' => [
            'id' => $newItemId,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'pricePerDay' => $price,
            'location' => $location,
            'availability' => $availability,
        ]
    ], 201);
}

// ============================================================================
// PUT - UPDATE ITEM
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $data = getRequestData();
    
    $itemId = (int) ($data['id'] ?? 0);
    if ($itemId <= 0) {
        respond(false, 'ID barang tidak valid', null, 400);
    }
    
    // Verify ownership
    $stmt = $conn->prepare('SELECT user_id FROM items WHERE id = ? LIMIT 1');
    if (!$stmt) {
        respond(false, 'Database error', null, 500);
    }
    
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if (!$item) {
        respond(false, 'Barang tidak ditemukan', null, 404);
    }
    
    if ((int) $item['user_id'] !== $userId) {
        respond(false, 'Anda tidak memiliki hak akses', null, 403);
    }
    
    // Validate input
    $errors = validateItemInput($data);
    if (!empty($errors)) {
        respond(false, implode(', ', $errors), null, 422);
    }
    
    $name = trim($data['name']);
    $category = trim($data['category']);
    $description = trim($data['description']);
    $price = (int) $data['price_per_day'];
    $location = trim($data['location']);
    $availability = isset($data['availability']) ? (bool) $data['availability'] : true;
    
    $stmt = $conn->prepare('
        UPDATE items 
        SET name = ?, category = ?, description = ?, price_per_day = ?, location = ?, availability = ?
        WHERE id = ?
    ');
    
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error, null, 500);
    }
    
    $availabilityInt = $availability ? 1 : 0;
    $stmt->bind_param('sssisii', $name, $category, $description, $price, $location, $availabilityInt, $itemId);
    
    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Gagal update barang: ' . $conn->error, null, 500);
    }
    
    $stmt->close();
    
    respond(true, 'Barang berhasil diupdate', [
        'itemId' => $itemId,
        'item' => [
            'id' => $itemId,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'pricePerDay' => $price,
            'location' => $location,
            'availability' => $availability,
        ]
    ]);
}

// ============================================================================
// DELETE - REMOVE ITEM
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $itemId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    
    if ($itemId <= 0) {
        respond(false, 'ID barang tidak valid', null, 400);
    }
    
    // Verify ownership
    $stmt = $conn->prepare('SELECT user_id FROM items WHERE id = ? LIMIT 1');
    if (!$stmt) {
        respond(false, 'Database error', null, 500);
    }
    
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if (!$item) {
        respond(false, 'Barang tidak ditemukan', null, 404);
    }
    
    if ((int) $item['user_id'] !== $userId) {
        respond(false, 'Anda tidak memiliki hak akses untuk menghapus barang ini', null, 403);
    }
    
    // Delete item
    $stmt = $conn->prepare('DELETE FROM items WHERE id = ?');
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error, null, 500);
    }
    
    $stmt->bind_param('i', $itemId);
    
    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Gagal menghapus barang: ' . $conn->error, null, 500);
    }
    
    $stmt->close();
    
    respond(true, 'Barang berhasil dihapus', ['itemId' => $itemId]);
}

// Default: method not allowed
respond(false, 'Metode HTTP tidak diizinkan', null, 405);
