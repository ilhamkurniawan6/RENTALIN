<?php
// Dev-only log viewer. Returns the last N lines of src/logs/app.log.
// Requires POST with token matching .dev_token and APP_ENV=development.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$env = getenv('APP_ENV');
if (empty($env)) {
    $env = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : '';
}
if (strtolower($env) !== 'development') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Disabled: not development']);
    exit;
}

$tokenFile = __DIR__ . '/../../../.dev_token';
if (!file_exists($tokenFile)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '.dev_token missing']);
    exit;
}

$expected = trim((string)@file_get_contents($tokenFile));
$provided = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$logFile = __DIR__ . '/../../logs/app.log';
if (!is_file($logFile)) {
    echo json_encode(['success' => true, 'lines' => []]);
    exit;
}

$maxLines = isset($_POST['lines']) ? (int)$_POST['lines'] : 200;
$maxLines = max(10, min(1000, $maxLines));

$content = @file_get_contents($logFile);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to read log']);
    exit;
}

$lines = preg_split('/\r\n|\r|\n/', trim($content));
$total = count($lines);
$start = max(0, $total - $maxLines);
$tail = array_slice($lines, $start);

echo json_encode(['success' => true, 'total_lines' => $total, 'lines' => $tail]);
exit;

?>
