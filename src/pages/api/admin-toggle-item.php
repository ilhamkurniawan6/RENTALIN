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

$itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
$availabilityRaw = isset($_POST['availability']) ? trim((string) $_POST['availability']) : '';

if ($itemId <= 0 || !in_array($availabilityRaw, ['0', '1'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Payload tidak valid.']);
    exit;
}

$availability = $availabilityRaw === '1' ? 1 : 0;

$currentStmt = $conn->prepare('SELECT availability, name FROM items WHERE id = ? LIMIT 1');
if (!$currentStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query status barang.']);
    exit;
}

$currentStmt->bind_param('i', $itemId);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentRow = $currentResult ? $currentResult->fetch_assoc() : null;
$currentStmt->close();

if (!$currentRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
    exit;
}

$previousAvailability = (int) ($currentRow['availability'] ?? 0);
$itemName = (string) ($currentRow['name'] ?? '');

$stmt = $conn->prepare('UPDATE items SET availability = ? WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query.']);
    exit;
}

$stmt->bind_param('ii', $availability, $itemId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected < 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status barang.']);
    exit;
}

rentalin_log_info('Admin toggled item availability', [
    'admin_id' => (int) ($_SESSION['auth_user']['id'] ?? 0),
    'admin_email' => (string) ($_SESSION['auth_user']['email'] ?? ''),
    'item_id' => $itemId,
    'item_name' => $itemName,
    'previous_availability' => (bool) $previousAvailability,
    'new_availability' => (bool) $availability,
]);

echo json_encode([
    'success' => true,
    'item_id' => $itemId,
    'availability' => (bool) $availability,
]);
exit;
