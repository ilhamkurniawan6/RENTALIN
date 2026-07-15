<?php
// Serves avatar images stored outside webroot at <project_root>/storage/avatars
// Usage: /src/pages/media/avatar.php?f=avatar_123_1600000000.jpg

// Basic validation: filename must match expected pattern and extension.
$file = isset($_GET['f']) ? (string) $_GET['f'] : '';
if (!preg_match('/^avatar_\d+_\d+\.(jpg|png)$/i', $file)) {
    http_response_code(400);
    echo 'Invalid file';
    exit;
}

$storagePath = __DIR__ . '/../../../storage/avatars/' . $file;
if (!is_file($storagePath)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($storagePath);

if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png'], true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Send proper caching headers for avatars
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($storagePath));
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Stream the file
readfile($storagePath);
exit;

