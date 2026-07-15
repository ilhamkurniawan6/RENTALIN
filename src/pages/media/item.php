<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($itemId <= 0) {
    http_response_code(400);
    echo 'Invalid item';
    exit;
}

$stmt = $conn->prepare('SELECT image FROM items WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo 'Server error';
    exit;
}

$stmt->bind_param('i', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row || empty($row['image'])) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#f2efe8"/><stop offset="100%" stop-color="#dde4f2"/></linearGradient></defs><rect width="1200" height="800" fill="url(#g)"/><rect x="120" y="120" width="960" height="560" rx="36" fill="#ffffff" fill-opacity="0.55"/><circle cx="600" cy="360" r="110" fill="#b9c7df" fill-opacity="0.45"/><path d="M470 430l90-110 82 86 52-58 116 122H470z" fill="#9eb2cf" fill-opacity="0.75"/><circle cx="520" cy="290" r="26" fill="#9eb2cf" fill-opacity="0.85"/><text x="600" y="560" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" fill="#5b6780">Foto belum tersedia</text></svg>';
    exit;
}

$blob = $row['image'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = is_string($blob) ? $finfo->buffer($blob) : false;

if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    $mime = 'image/jpeg';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($blob));
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
echo $blob;
exit;
