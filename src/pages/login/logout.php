<?php
require_once __DIR__ . '/../../services/session-init.php';
header('Content-Type: application/json; charset=utf-8');

// Only allow POST to prevent logout CSRF via GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

// Validate CSRF token from header or POST body
$csrfHeader = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
$csrfPost = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$csrf = $csrfHeader ?: $csrfPost;

if (empty($csrf) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
	exit;
}

// perform logout
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
exit;
?>
