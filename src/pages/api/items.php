<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/url-helper.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function rentalin_item_placeholder_url(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#f2efe8"/><stop offset="100%" stop-color="#dde4f2"/></linearGradient></defs><rect width="1200" height="800" fill="url(#g)"/><rect x="120" y="120" width="960" height="560" rx="36" fill="#ffffff" fill-opacity="0.55"/><circle cx="600" cy="360" r="110" fill="#b9c7df" fill-opacity="0.45"/><path d="M470 430l90-110 82 86 52-58 116 122H470z" fill="#9eb2cf" fill-opacity="0.75"/><circle cx="520" cy="290" r="26" fill="#9eb2cf" fill-opacity="0.85"/><text x="600" y="560" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" fill="#5b6780">Foto belum tersedia</text></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function rentalin_get_current_user_context(): array
{
    if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        return [];
    }

    return $_SESSION['auth_user'];
}

function rentalin_is_admin_like_user(array $user): bool
{
    $role = isset($user['role']) ? strtolower((string) $user['role']) : '';
    return $role === 'admin' || $role === 'super_admin';
}

function rentalin_build_item_filters(?int $itemId = null, ?array $currentUser = null, string $scope = 'catalog'): array
{
    $whereClauses = [];
    $params = [];
    $types = '';

    if ($scope === 'dashboard') {
        if (!is_array($currentUser) || empty($currentUser['id'])) {
            return [
                'where' => ['1=0'],
                'params' => [],
                'types' => '',
            ];
        }

        if (!rentalin_is_admin_like_user($currentUser)) {
            $whereClauses[] = 'i.user_id = ?';
            $params[] = (int) $currentUser['id'];
            $types .= 'i';
        }
    }

    if ($itemId !== null) {
        $whereClauses[] = 'i.id = ?';
        $params[] = $itemId;
        $types .= 'i';
    }

    return [
        'where' => $whereClauses,
        'params' => $params,
        'types' => $types,
    ];
}

function rentalin_fetch_items(mysqli $conn, ?int $itemId = null, ?array $currentUser = null, string $scope = 'catalog'): array
{
    $sql = "SELECT i.id, i.user_id, i.name, i.category, i.description, i.price_per_day, i.location, i.image, i.availability, i.created_at, u.name AS owner_name, u.avatar AS owner_avatar, COALESCE(r.avg_rating, 0) AS owner_rating FROM items i LEFT JOIN users u ON u.id = i.user_id LEFT JOIN (SELECT to_user_id, ROUND(AVG(rating), 1) AS avg_rating FROM reviews GROUP BY to_user_id) r ON r.to_user_id = u.id";

    $filters = rentalin_build_item_filters($itemId, $currentUser, $scope);
    if (!empty($filters['where'])) {
        $sql .= ' WHERE ' . implode(' AND ', $filters['where']);
    }

    if ($itemId === null) {
        $sql .= ' ORDER BY i.created_at DESC, i.id DESC';
    } else {
        $sql .= ' LIMIT 1';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if (!empty($filters['params'])) {
        $types = $filters['types'];
        $count = count($filters['params']);
        
        if ($count === 1) {
            $stmt->bind_param($types, $filters['params'][0]);
        } elseif ($count === 2) {
            $stmt->bind_param($types, $filters['params'][0], $filters['params'][1]);
        } elseif ($count > 2) {
            // For more params, use call_user_func_array with proper reference handling
            $params = [$types];
            foreach ($filters['params'] as &$param) {
                $params[] = &$param;
            }
            unset($param);
            call_user_func_array([$stmt, 'bind_param'], $params);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    if ($itemId !== null) {
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ? [$row] : [];
    }

    while ($result && $row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function rentalin_normalize_item_row(array $row): array
{
    $itemId = isset($row['id']) ? (int) $row['id'] : 0;
    $ownerAvatar = rentalin_normalize_avatar_url($row['owner_avatar'] ?? '');

    return [
        'id' => (string) $itemId,
        'name' => (string) ($row['name'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'pricePerDay' => (int) ($row['price_per_day'] ?? 0),
        'image' => rentalin_public_url('src/pages/media/item.php?id=' . $itemId),
        'description' => (string) ($row['description'] ?? ''),
        'owner' => [
            'name' => (string) ($row['owner_name'] ?? 'Rentalin User'),
            'avatar' => $ownerAvatar !== '' ? $ownerAvatar : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop',
            'rating' => (float) ($row['owner_rating'] ?? 0),
        ],
        'location' => (string) ($row['location'] ?? ''),
        'availability' => (bool) ($row['availability'] ?? false),
        'createdAt' => (string) ($row['created_at'] ?? ''),
    ];
}

$itemId = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;
$scope = isset($_GET['scope']) ? strtolower((string) $_GET['scope']) : 'catalog';
$currentUser = rentalin_get_current_user_context();
$rows = rentalin_fetch_items($conn, $itemId, $currentUser, $scope);
$items = array_map('rentalin_normalize_item_row', $rows);

if ($itemId !== null) {
    if (empty($items)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
        exit;
    }

    echo json_encode(['success' => true, 'item' => $items[0]]);
    exit;
}

echo json_encode(['success' => true, 'items' => $items]);
exit;
