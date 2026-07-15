<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login/index.php');
    exit;
}

$userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($userId <= 0 || $itemId <= 0) {
    $_SESSION['dashboard_error'] = 'Parameter barang tidak valid.';
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $itemId, $userId);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['dashboard_message'] = 'Barang berhasil dihapus.';
    header('Location: index.php');
    exit;
}

$stmt->close();
$_SESSION['dashboard_error'] = 'Gagal menghapus barang.';
header('Location: index.php');
exit;
