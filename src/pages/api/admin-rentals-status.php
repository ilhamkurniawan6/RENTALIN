<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/role-helper.php';

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

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user']) || !rentalin_is_admin_like($_SESSION['auth_user'])) {
    sendJson(['success' => false, 'message' => 'Akses ditolak.'], 403);
}

$rawBody = file_get_contents('php://input') ?: '{}';
$data = json_decode($rawBody, true);
if (!is_array($data)) {
    sendJson(['success' => false, 'message' => 'Payload tidak valid.'], 422);
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
$status = isset($data['status']) ? trim((string) $data['status']) : '';

if ($id <= 0 || $status === '') {
    sendJson(['success' => false, 'message' => 'id dan status wajib diisi.'], 422);
}

$allowed = ['pending', 'approved', 'completed', 'cancelled'];
if (!in_array($status, $allowed, true)) {
    sendJson(['success' => false, 'message' => 'Status tidak valid.'], 422);
}

$stmt = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ? LIMIT 1');
if (!$stmt) {
    sendJson(['success' => false, 'message' => 'Gagal menyiapkan query update.'], 500);
}

$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    sendJson(['success' => false, 'message' => 'Gagal mengubah status rental.'], 500);
}

sendJson([
    'success' => true,
    'message' => 'Status rental berhasil diperbarui.',
    'id' => $id,
    'status' => $status,
]);
