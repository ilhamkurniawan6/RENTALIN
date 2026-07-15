<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
require_once __DIR__ . '/../../services/url-helper.php';
require_once __DIR__ . '/../../services/role-helper.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user']) || !rentalin_is_admin_like($_SESSION['auth_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
if ($limit < 1) {
    $limit = 10;
}
if ($limit > 100) {
    $limit = 100;
}
$search = trim((string) ($_GET['search'] ?? ''));
$searchSql = '';
if ($search !== '') {
    $escapedSearch = $conn->real_escape_string($search);
    $searchSql = " WHERE (i.name LIKE '%{$escapedSearch}%' OR i.category LIKE '%{$escapedSearch}%' OR i.location LIKE '%{$escapedSearch}%' OR u.name LIKE '%{$escapedSearch}%' OR u.email LIKE '%{$escapedSearch}%')";
}

$offset = ($page - 1) * $limit;

$countResult = $conn->query('SELECT COUNT(*) AS total_items FROM items i LEFT JOIN users u ON u.id = i.user_id' . $searchSql);
if (!$countResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat jumlah barang.']);
    exit;
}
$countRow = $countResult->fetch_assoc();
$totalItems = (int) ($countRow['total_items'] ?? 0);
$totalPages = $totalItems > 0 ? (int) ceil($totalItems / $limit) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$activeWhere = $searchSql !== ''
    ? $searchSql . ' AND i.availability = 1'
    : ' WHERE i.availability = 1';

$activeResult = $conn->query('SELECT COUNT(*) AS active_items FROM items i LEFT JOIN users u ON u.id = i.user_id' . $activeWhere);
if (!$activeResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat statistik barang aktif.']);
    exit;
}
$activeRow = $activeResult->fetch_assoc();
$activeItems = (int) ($activeRow['active_items'] ?? 0);

$sql = "SELECT i.id, i.name, i.category, i.price_per_day, i.location, i.availability, i.created_at, u.id AS owner_id, u.name AS owner_name, u.email AS owner_email, u.avatar AS owner_avatar FROM items i LEFT JOIN users u ON u.id = i.user_id" . $searchSql . " ORDER BY i.created_at DESC, i.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query barang admin.']);
    exit;
}

$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat data barang admin.']);
    exit;
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $ownerAvatar = rentalin_normalize_avatar_url($row['owner_avatar'] ?? '');

    $items[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'pricePerDay' => (int) ($row['price_per_day'] ?? 0),
        'location' => (string) ($row['location'] ?? ''),
        'availability' => (bool) ($row['availability'] ?? false),
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'owner' => [
            'id' => (int) ($row['owner_id'] ?? 0),
            'name' => (string) ($row['owner_name'] ?? 'User'),
            'email' => (string) ($row['owner_email'] ?? ''),
            'avatar' => $ownerAvatar !== ''
                ? $ownerAvatar
                : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop',
        ],
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'items' => $items,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalItems,
        'total_pages' => $totalPages,
    ],
    'stats' => [
        'active_items' => $activeItems,
    ],
]);
exit;
