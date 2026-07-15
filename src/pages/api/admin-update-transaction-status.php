<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/logger.php';
require_once __DIR__ . '/../../services/role-helper.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user']) || !rentalin_is_admin_like($_SESSION['auth_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$csrfHeader = '';
if (is_array($headers)) {
    $csrfHeader = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : (isset($headers['x-csrf-token']) ? $headers['x-csrf-token'] : '');
}
$csrfPost = isset($_POST['csrf_token']) ? trim((string) $_POST['csrf_token']) : '';
$csrfToken = $csrfHeader ?: $csrfPost;

if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit;
}

$transactionId = isset($_POST['transaction_id']) ? (int) $_POST['transaction_id'] : 0;
$nextStatus = isset($_POST['status']) ? trim((string) $_POST['status']) : '';

if ($transactionId <= 0 || !in_array($nextStatus, ['pending', 'active', 'completed', 'cancelled'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Payload tidak valid.']);
    exit;
}

$currentStmt = $conn->prepare('SELECT status, total_price FROM rentals WHERE id = ? LIMIT 1');
if (!$currentStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query status transaksi.']);
    exit;
}

$currentStmt->bind_param('i', $transactionId);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentRow = $currentResult ? $currentResult->fetch_assoc() : null;
$currentStmt->close();

if (!$currentRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
    exit;
}

$previousStatus = (string) ($currentRow['status'] ?? 'pending');
$transactionAmount = (int) ($currentRow['total_price'] ?? 0);

$stmt = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query update transaksi.']);
    exit;
}

$stmt->bind_param('si', $nextStatus, $transactionId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status transaksi.']);
    exit;
}

rentalin_log_info('Admin updated transaction status', [
    'admin_id' => (int) ($_SESSION['auth_user']['id'] ?? 0),
    'admin_email' => (string) ($_SESSION['auth_user']['email'] ?? ''),
    'transaction_id' => $transactionId,
    'previous_status' => $previousStatus,
    'new_status' => $nextStatus,
    'amount' => $transactionAmount,
]);

echo json_encode([
    'success' => true,
    'transaction_id' => $transactionId,
    'status' => $nextStatus,
]);
exit;
