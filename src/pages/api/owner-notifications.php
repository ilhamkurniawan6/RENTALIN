<?php
/**
 * Owner/Penyewakan Notifications API
 * GET    /api/owner-notifications.php?action=list       - List pending rental requests for owner's items
 * POST   /api/owner-notifications.php                   - Approve/reject rental request
 * 
 * Response format: { success: true/false, message: "", data: {...} }
 */

require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

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

// ============================================================================
// GET - LIST PENDING RENTAL REQUESTS FOR OWNER'S ITEMS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireLogin();
    $ownerId = (int) $user['id'];
    $action = strtolower(trim($_GET['action'] ?? 'list'));
    
    if ($action === 'list') {
        // Get all pending rentals for items owned by this user
        $stmt = $conn->prepare('
            SELECT 
                r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status, r.created_at,
                i.name AS item_name, i.price_per_day,
                u.name AS renter_name, u.email AS renter_email
            FROM rentals r
            JOIN items i ON r.item_id = i.id
            JOIN users u ON r.user_id = u.id
            WHERE i.user_id = ? AND r.status = "pending"
            ORDER BY r.created_at DESC
            LIMIT 100
        ');
        
        if (!$stmt) {
            respond(false, 'Database error', null, 500);
        }
        
        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $start = strtotime($row['start_date']);
            $end = strtotime($row['end_date']);
            $days = ($end - $start) / 86400;
            
            $notifications[] = [
                'id' => (int) $row['id'],
                'rentalId' => (int) $row['id'],
                'itemId' => (int) $row['item_id'],
                'itemName' => $row['item_name'],
                'renterId' => (int) $row['user_id'],
                'renterName' => $row['renter_name'],
                'renterEmail' => $row['renter_email'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'days' => (int) $days,
                'pricePerDay' => (int) $row['price_per_day'],
                'totalPrice' => (int) $row['total_price'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
                'type' => 'pending_rental',
            ];
        }
        
        $stmt->close();
        respond(true, 'Notifikasi rental request Anda', [
            'notifications' => $notifications,
            'unreadCount' => count($notifications)
        ]);
    }
    
    respond(false, 'Action tidak dikenali', null, 400);
}

// ============================================================================
// POST - APPROVE/REJECT RENTAL REQUEST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireLogin();
    $ownerId = (int) $user['id'];
    
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $data = [];
    
    if (stripos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $decoded = json_decode($rawInput ?: '', true);
        $data = is_array($decoded) ? $decoded : [];
    } else {
        $data = $_POST;
    }
    
    $rentalId = (int) ($data['rental_id'] ?? 0);
    $action = strtolower(trim($data['action'] ?? ''));
    
    if ($rentalId <= 0) {
        respond(false, 'ID rental tidak valid', null, 400);
    }
    
    if (!in_array($action, ['approve', 'reject'], true)) {
        respond(false, 'Action harus "approve" atau "reject"', null, 400);
    }
    
    // Verify this rental is for an item owned by this user
    // (nambahin renter_id, item_id, item_name buat keperluan notif nanti)
    $stmt = $conn->prepare('
        SELECT r.id, r.status, r.user_id AS renter_id, r.item_id,
               i.user_id AS owner_id, i.name AS item_name
        FROM rentals r
        JOIN items i ON r.item_id = i.id
        WHERE r.id = ?
        LIMIT 1
    ');
    
    if (!$stmt) {
        respond(false, 'Database error', null, 500);
    }
    
    $stmt->bind_param('i', $rentalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rental = $result->fetch_assoc();
    $stmt->close();
    
    if (!$rental) {
        respond(false, 'Rental request tidak ditemukan', null, 404);
    }
    
    if ((int) $rental['owner_id'] !== $ownerId) {
        respond(false, 'Anda tidak punya akses untuk merubah request ini', null, 403);
    }
    
    if ($rental['status'] !== 'pending') {
        respond(false, 'Request ini sudah tidak berstatus pending', null, 409);
    }
    
    // Update status
    $newStatus = ($action === 'approve') ? 'active' : 'cancelled';
    $stmt = $conn->prepare('
        UPDATE rentals SET status = ? WHERE id = ? LIMIT 1
    ');
    
    if (!$stmt) {
        respond(false, 'Database error', null, 500);
    }
    
    $stmt->bind_param('si', $newStatus, $rentalId);
    
    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Gagal mengubah status rental', null, 500);
    }
    
    $stmt->close();

     if ($action === 'approve') {
        $itemUpdateStmt = $conn->prepare('UPDATE items SET availability = 0 WHERE id = ? LIMIT 1');
        if ($itemUpdateStmt) {
            $itemId = (int) $rental['item_id'];
            $itemUpdateStmt->bind_param('i', $itemId);
            $itemUpdateStmt->execute();
            $itemUpdateStmt->close();
        }
    }

    // ------------------------------------------------------------------
    // Kirim notif ke penyewa (renter) soal hasil approve/reject
    // ------------------------------------------------------------------
    $renterId = (int) $rental['renter_id'];
    $itemId = (int) $rental['item_id'];
    $itemName = (string) $rental['item_name'];
    $notifType = 'rental_status_changed';
    $notifTitle = ($action === 'approve') ? 'Booking Disetujui' : 'Booking Ditolak';
    $notifMsg = ($action === 'approve')
        ? "Permintaan sewa \"$itemName\" kamu telah disetujui pemilik."
        : "Permintaan sewa \"$itemName\" kamu ditolak pemilik.";

    $notifStmt = $conn->prepare('INSERT INTO notifications (user_id, rental_id, item_id, type, title, message) VALUES (?, ?, ?, ?, ?, ?)');
        if ($notifStmt) {
            $notifStmt->bind_param('iiisss', $renterId, $rentalId, $itemId, $notifType, $notifTitle, $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
        }
    
    $message = ($action === 'approve') 
        ? 'Request penyewaan berhasil disetujui!' 
        : 'Request penyewaan berhasil ditolak.';
    
    respond(true, $message, [
        'rentalId' => $rentalId,
        'newStatus' => $newStatus,
        'action' => $action,
    ]);
}

// Default: method not allowed
respond(false, 'Metode HTTP tidak diizinkan', null, 405);
?>