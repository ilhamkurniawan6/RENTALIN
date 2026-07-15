<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/url-helper.php';
header('Content-Type: application/json');

function ensureCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    return $_SESSION['csrf_token'];
}

function hydrateUserFromDatabase(array $user): array
{
    if (empty($user['id'])) {
        return $user;
    }

    global $conn;
    if (!isset($conn) || !$conn) {
        return $user;
    }

    $userId = (int) $user['id'];
    $stmt = $conn->prepare('SELECT id, name, email, phone, avatar, role, current_role, preferred_role FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return $user;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($row)) {
        return $user;
    }

    $normalizedAvatar = rentalin_normalize_avatar_url($row['avatar'] ?? '');

    $user['id'] = (int) ($row['id'] ?? $user['id']);
    $user['name'] = (string) ($row['name'] ?? ($user['name'] ?? ''));
    $user['email'] = (string) ($row['email'] ?? ($user['email'] ?? ''));
    $user['phone'] = (string) ($row['phone'] ?? ($user['phone'] ?? ''));
    $user['avatar'] = $normalizedAvatar;
    $user['role'] = (string) ($row['role'] ?? ($user['role'] ?? 'user'));
    $user['current_role'] = (string) ($row['current_role'] ?? ($user['current_role'] ?? 'penyewa'));
    $user['preferred_role'] = (string) ($row['preferred_role'] ?? ($user['preferred_role'] ?? 'penyewa'));

    $_SESSION['auth_user'] = array_merge($_SESSION['auth_user'], $user);

    return $user;
}

// Simple auth status endpoint — returns current session user if logged in
if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
    $user = hydrateUserFromDatabase($_SESSION['auth_user']);

    echo json_encode([
        'success' => true,
        'user' => $user,
        'csrf_token' => ensureCsrfToken(),
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'user' => null,
    'csrf_token' => ensureCsrfToken(),
]);
exit;

