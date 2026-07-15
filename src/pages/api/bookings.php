<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/logger.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user']) || empty($_SESSION['auth_user']['id'])) {
    sendJson(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
}

$rawBody = file_get_contents('php://input') ?: '{}';
$data = json_decode($rawBody, true);
if (!is_array($data)) {
    sendJson(['success' => false, 'message' => 'Payload tidak valid.'], 422);
}

$itemId = isset($data['item_id']) ? (int) $data['item_id'] : 0;
$startDate = isset($data['start_date']) ? trim((string) $data['start_date']) : '';
$endDate = isset($data['end_date']) ? trim((string) $data['end_date']) : '';

if ($itemId <= 0 || $startDate === '' || $endDate === '') {
    sendJson(['success' => false, 'message' => 'item_id, start_date, dan end_date wajib diisi.'], 422);
}

$startTs = strtotime($startDate);
$endTs = strtotime($endDate);
if ($startTs === false || $endTs === false || $endTs < $startTs) {
    sendJson(['success' => false, 'message' => 'Tanggal tidak valid.'], 422);
}

$itemStmt = $conn->prepare('SELECT id, price_per_day FROM items WHERE id = ? LIMIT 1');
if (!$itemStmt) {
    sendJson(['success' => false, 'message' => 'Gagal memuat data barang.'], 500);
}

$itemStmt->bind_param('i', $itemId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();
$itemRow = $itemResult ? $itemResult->fetch_assoc() : null;
$itemStmt->close();

if (!$itemRow) {
    sendJson(['success' => false, 'message' => 'Barang tidak ditemukan.'], 404);
}

$pricePerDay = (int) ($itemRow['price_per_day'] ?? 0);
$dayCount = max(1, (int) ceil(($endTs - $startTs) / 86400));
$totalPrice = $pricePerDay * $dayCount;
$userId = (int) ($_SESSION['auth_user']['id'] ?? 0);

$insertStmt = $conn->prepare('INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, "pending")');
if (!$insertStmt) {
    sendJson(['success' => false, 'message' => 'Gagal membuat booking.'], 500);
}

$insertStmt->bind_param('iisss', $userId, $itemId, $startDate, $endDate, $totalPrice);
$insertOk = $insertStmt->execute();
$bookingId = $insertOk ? (int) $insertStmt->insert_id : 0;
$insertStmt->close();

if (!$insertOk) {
    sendJson(['success' => false, 'message' => 'Gagal menyimpan booking.'], 500);
}

rentalin_log_info('Booking created', [
    'user_id' => $userId,
    'item_id' => $itemId,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'total_price' => $totalPrice,
]);

sendJson([
    'success' => true,
    'message' => 'Booking berhasil dikirim. Menunggu persetujuan.',
    'rental_id' => $bookingId,
], 201);
