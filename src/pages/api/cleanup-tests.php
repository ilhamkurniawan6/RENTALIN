<?php
// Dev-only cleanup endpoint. Removes test users/items created by E2E tests (emails starting with 'e2e+').
// Safety: requires a secret token stored in project root file `.dev_token` and must be called via POST.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Simple file logger for dev cleanup actions
function _cleanup_log($line)
{
    $logsDir = __DIR__ . '/../../logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    $logFile = $logsDir . '/app.log';
    $ts = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
    $entry = "[{$ts}] [cleanup-tests] [{$ip}] " . trim($line) . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// Log the attempt
_cleanup_log('Attempted cleanup; method=POST');

// Only allow execution in development environment. Protect against accidental
// invocation in staging/production. Require APP_ENV=development.
$env = getenv('APP_ENV');
if (empty($env)) {
    $env = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : '';
}

$remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$httpHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$isLocalRequest = in_array($remoteAddr, ['127.0.0.1', '::1'], true) || strpos($httpHost, 'localhost') !== false;

if (strtolower($env) !== 'development' && !$isLocalRequest) {
    _cleanup_log('Blocked cleanup: APP_ENV != development and request is not local (' . ($env ?: 'empty') . ', ' . ($remoteAddr ?: 'no-ip') . ')');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cleanup endpoint disabled: APP_ENV is not development.']);
    exit;
}

// locate token file at project root
$tokenFile = __DIR__ . '/../../../.dev_token';
if (!file_exists($tokenFile)) {
    _cleanup_log('Blocked cleanup: .dev_token missing');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '.dev_token missing — cleanup disabled']);
    exit;
}

$expected = trim((string)@file_get_contents($tokenFile));
$provided = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    _cleanup_log('Blocked cleanup: invalid token provided');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

require_once __DIR__ . '/../../services/koneksi.php';
global $conn;

    try {
    // Find test users (use prepared statement for consistency)
    $ids = [];
    $sel = $conn->prepare("SELECT id FROM users WHERE email LIKE ?");
    if ($sel) {
        $like = 'e2e+%';
        $sel->bind_param('s', $like);
        $sel->execute();
        $result = $sel->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['id'];
        }
        $sel->close();
    }

    $deletedItems = 0;
    $deletedUsers = 0;

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        // Attempt single-statement deletes using IN(...) with placeholders
        $delItemsSql = "DELETE FROM items WHERE user_id IN ($placeholders)";
        $delUsersSql = "DELETE FROM users WHERE id IN ($placeholders)";

        $deletedItems = 0;
        $deletedUsers = 0;

        $delItems = $conn->prepare($delItemsSql);
        if ($delItems) {
            $params = array_merge([$types], $ids);
            $refs = [];
            foreach ($params as $k => $v) {
                $refs[$k] = &$params[$k];
            }
            call_user_func_array([$delItems, 'bind_param'], $refs);
            $delItems->execute();
            $deletedItems = $delItems->affected_rows;
            $delItems->close();
        } else {
            // fallback to per-id prepared deletes
            $delItemsSingle = $conn->prepare("DELETE FROM items WHERE user_id = ?");
            if ($delItemsSingle) {
                foreach ($ids as $uid) {
                    $delItemsSingle->bind_param('i', $uid);
                    $delItemsSingle->execute();
                    $deletedItems += $delItemsSingle->affected_rows;
                }
                $delItemsSingle->close();
            }
        }

        $delUsers = $conn->prepare($delUsersSql);
        if ($delUsers) {
            $params = array_merge([$types], $ids);
            $refs = [];
            foreach ($params as $k => $v) {
                $refs[$k] = &$params[$k];
            }
            call_user_func_array([$delUsers, 'bind_param'], $refs);
            $delUsers->execute();
            $deletedUsers = $delUsers->affected_rows;
            $delUsers->close();
        } else {
            $delUsersSingle = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($delUsersSingle) {
                foreach ($ids as $uid) {
                    $delUsersSingle->bind_param('i', $uid);
                    $delUsersSingle->execute();
                    $deletedUsers += $delUsersSingle->affected_rows;
                }
                $delUsersSingle->close();
            }
        }
    }

    _cleanup_log('Cleanup completed: deleted_users=' . $deletedUsers . ' deleted_items=' . $deletedItems);
    echo json_encode([
        'success' => true,
        'deleted_users' => $deletedUsers,
        'deleted_items' => $deletedItems,
    ]);
    exit;
} catch (Exception $e) {
    _cleanup_log('Cleanup exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    exit;
}

?>
