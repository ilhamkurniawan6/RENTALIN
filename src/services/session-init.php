<?php
// Centralized session initialization with secure default cookie params.
// Include this file instead of calling session_start() directly.

// Determine if connection appears secure
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// Default cookie params
$httpHost = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
$cookieDomain = $httpHost !== '' ? preg_replace('/:\d+$/', '', $httpHost) : '';

$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $cookieDomain,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
];

// PHP < 7.3 doesn't support array form for session_set_cookie_params with samesite
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    // fallback: set without samesite
    session_set_cookie_params(0, '/', $cookieDomain, $secure, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
