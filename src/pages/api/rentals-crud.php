<?php
/**
 * Rentals CRUD API Endpoint
 * GET    /api/rentals-crud.php?action=list             - List user rentals
 * GET    /api/rentals-crud.php?action=detail&id=X      - Get rental detail
 * POST   /api/rentals-crud.php                         - Create new rental
 * GET    /api/rentals-crud.php?action=history          - Rental history (with pagination)
 * 
 * Response format: { success: true/false, message: "", data: {...} }
 */

require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

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

function validateRentalInput(array $data): array
{
    $errors = [];
    
    $itemId = (int) ($data['item_id'] ?? 0);
    if ($itemId <= 0) {
        $errors[] = 'ID barang tidak valid';
    }
    
    $startDate = trim($data['start_date'] ?? '');
    if (empty($startDate)) {
        $errors[] = 'Tanggal mulai harus diisi';
    } else {
        if (!strtotime($startDate)) {
            $errors[] = 'Format tanggal mulai tidak valid (gunakan YYYY-MM-DD)';
        }
    }
    
    $endDate = trim($data['end_date'] ?? '');
    if (empty($endDate)) {
        $errors[] = 'Tanggal kembali harus diisi';
    } else {
        if (!strtotime($endDate)) {
            $errors[] = 'Format tanggal kembali tidak valid (gunakan YYYY-MM-DD)';
        }
    }
    
    if (!empty($startDate) && !empty($endDate)) {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        
        if ($end <= $start) {
            $errors[] = 'Tanggal kembali harus setelah tanggal mulai';
        }
        
        if (($end - $start) / 86400 > 365) {
            $errors[] = 'Durasi penyewaan maksimal 1 tahun';
        }
    }
    
    return $errors;
}

// ============================================================================
// GET - LIST RENTALS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $action = strtolower(trim($_GET['action'] ?? 'list'));
    
    // List user rentals
    if ($action === 'list') {
        $stmt = $conn->prepare('
            SELECT r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status, r.created_at,
                   i.name AS item_name, i.price_per_day, i.user_id AS owner_id, u.name AS owner_name
            FROM rentals r
            JOIN items i ON r.item_id = i.id
            JOIN users u ON i.user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 100
        ');
        
        if (!$stmt) {
            respond(false, 'Database error', null, 500);
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rentals = [];
        
        while ($row = $result->fetch_assoc()) {
            $rentals[] = [
                'id' => (int) $row['id'],
                'itemId' => (int) $row['item_id'],
                'itemName' => $row['item_name'],
                'ownerName' => $row['owner_name'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'totalPrice' => (int) $row['total_price'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
            ];
        }
        
        $stmt->close();
        respond(true, 'Daftar penyewaan Anda', ['rentals' => $rentals, 'count' => count($rentals)]);
    }
    
    // Get rental detail
    if ($action === 'detail') {
        $rentalId = (int) ($_GET['id'] ?? 0);
        
        if ($rentalId <= 0) {
            respond(false, 'ID penyewaan tidak valid', null, 400);
        }
        
        $stmt = $conn->prepare('
            SELECT r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status, r.created_at,
                   i.name AS item_name, i.price_per_day, i.user_id AS owner_id, u.name AS owner_name
            FROM rentals r
            JOIN items i ON r.item_id = i.id
            JOIN users u ON i.user_id = u.id
            WHERE r.id = ? AND r.user_id = ?
            LIMIT 1
        ');
        
        if (!$stmt) {
            respond(false, 'Database error', null, 500);
        }
        
        $stmt->bind_param('ii', $rentalId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            respond(false, 'Penyewaan tidak ditemukan', null, 404);
        }
        
        respond(true, 'Detail penyewaan', [
            'rental' => [
                'id' => (int) $row['id'],
                'itemId' => (int) $row['item_id'],
                'itemName' => $row['item_name'],
                'ownerName' => $row['owner_name'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'totalPrice' => (int) $row['total_price'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
            ]
        ]);
    }
    
    // Rental history with pagination
    if ($action === 'history') {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        // Count total
        $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM rentals WHERE user_id = ?');
        if (!$countStmt) {
            respond(false, 'Database error', null, 500);
        }
        
        $countStmt->bind_param('i', $userId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = (int) $countRow['total'];
        $countStmt->close();
        
        // Fetch paginated data
        $stmt = $conn->prepare('
            SELECT r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status, r.created_at,
                   i.name AS item_name
            FROM rentals r
            JOIN items i ON r.item_id = i.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ');
        
        if (!$stmt) {
            respond(false, 'Database error', null, 500);
        }
        
        $stmt->bind_param('iii', $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rentals = [];
        
        while ($row = $result->fetch_assoc()) {
            $rentals[] = [
                'id' => (int) $row['id'],
                'itemId' => (int) $row['item_id'],
                'itemName' => $row['item_name'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'totalPrice' => (int) $row['total_price'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
            ];
        }
        
        $stmt->close();
        
        respond(true, 'Riwayat penyewaan', [
            'rentals' => $rentals,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit),
            ]
        ]);
    }
    
    respond(false, 'Action tidak dikenali', null, 400);
}

// ============================================================================
// POST - CREATE RENTAL
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireLogin();
    $userId = (int) $user['id'];
    $data = getRequestData();
    
    // Validate input
    $errors = validateRentalInput($data);
    if (!empty($errors)) {
        respond(false, implode(', ', $errors), null, 422);
    }
    
    $itemId = (int) $data['item_id'];
    $startDate = trim($data['start_date']);
    $endDate = trim($data['end_date']);
    
    // Verify item exists and get price
    $stmt = $conn->prepare('
        SELECT id, price_per_day, availability, user_id 
        FROM items 
        WHERE id = ? 
        LIMIT 1
    ');
    
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
    
    if (!$item['availability']) {
        respond(false, 'Barang sedang tidak tersedia untuk disewakan', null, 409);
    }
    
    if ((int) $item['user_id'] === $userId) {
        respond(false, 'Anda tidak bisa menyewa barang milik sendiri', null, 400);
    }
    
    // Calculate days and total price
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    $days = ($end - $start) / 86400;
    $totalPrice = (int) $item['price_per_day'] * $days;
    
    // Create rental
    $status = 'pending';
    $stmt = $conn->prepare('
        INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error, null, 500);
    }
    
    $stmt->bind_param('iissss', $userId, $itemId, $startDate, $endDate, $totalPrice, $status);
    
    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Gagal membuat penyewaan: ' . $conn->error, null, 500);
    }
    
    $rentalId = $conn->insert_id;
    $stmt->close();
    
    respond(true, 'Penyewaan berhasil dibuat', [
        'rentalId' => $rentalId,
        'itemId' => $itemId,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'days' => $days,
        'totalPrice' => $totalPrice,
        'status' => $status,
    ], 201);
}

// Default: method not allowed
respond(false, 'Metode HTTP tidak diizinkan', null, 405);
