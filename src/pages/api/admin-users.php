<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';
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
    $searchSql = " WHERE (u.name LIKE '%{$escapedSearch}%' OR u.email LIKE '%{$escapedSearch}%')";
}

$offset = ($page - 1) * $limit;

$countResult = $conn->query('SELECT COUNT(*) AS total_users FROM users u' . $searchSql);
if (!$countResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat jumlah pengguna.']);
    exit;
}
$countRow = $countResult->fetch_assoc();
$totalUsers = (int) ($countRow['total_users'] ?? 0);
$totalPages = $totalUsers > 0 ? (int) ceil($totalUsers / $limit) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$activeWhere = $searchSql !== ''
    ? $searchSql . ' AND (EXISTS (SELECT 1 FROM items i WHERE i.user_id = u.id) OR EXISTS (SELECT 1 FROM rentals r WHERE r.user_id = u.id))'
    : ' WHERE (EXISTS (SELECT 1 FROM items i WHERE i.user_id = u.id) OR EXISTS (SELECT 1 FROM rentals r WHERE r.user_id = u.id))';

$activeResult = $conn->query('SELECT COUNT(*) AS active_users FROM users u' . $activeWhere);
if (!$activeResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat statistik pengguna aktif.']);
    exit;
}
$activeRow = $activeResult->fetch_assoc();
$activeUsers = (int) ($activeRow['active_users'] ?? 0);

$sql = "SELECT u.id, u.name, u.email, u.role, u.current_role, COUNT(DISTINCT i.id) AS items_count, COUNT(DISTINCT r.id) AS rentals_count FROM users u LEFT JOIN items i ON i.user_id = u.id LEFT JOIN rentals r ON r.user_id = u.id" . $searchSql . " GROUP BY u.id, u.name, u.email, u.role, u.current_role ORDER BY u.created_at DESC, u.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query pengguna.']);
    exit;
}

$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat data pengguna.']);
    exit;
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $itemsCount = (int) ($row['items_count'] ?? 0);
    $rentalsCount = (int) ($row['rentals_count'] ?? 0);
    $status = ($itemsCount > 0 || $rentalsCount > 0) ? 'active' : 'inactive';

    $users[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'role' => (string) ($row['role'] ?? 'user'),
        'current_role' => (string) ($row['current_role'] ?? 'penyewa'),
        'status' => $status,
        'items' => $itemsCount,
        'rentals' => $rentalsCount,
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'users' => $users,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalUsers,
        'total_pages' => $totalPages,
    ],
    'stats' => [
        'active_users' => $activeUsers,
    ],
]);
exit;
