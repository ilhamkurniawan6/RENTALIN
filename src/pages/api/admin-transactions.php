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
    $searchSql = " WHERE (CAST(r.id AS CHAR) LIKE '%{$escapedSearch}%' OR u.name LIKE '%{$escapedSearch}%' OR u.email LIKE '%{$escapedSearch}%' OR i.name LIKE '%{$escapedSearch}%' OR r.status LIKE '%{$escapedSearch}%')";
}

$offset = ($page - 1) * $limit;

$countResult = $conn->query('SELECT COUNT(*) AS total_transactions FROM rentals r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN items i ON i.id = r.item_id' . $searchSql);
if (!$countResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat jumlah transaksi.']);
    exit;
}
$countRow = $countResult->fetch_assoc();
$totalTransactions = (int) ($countRow['total_transactions'] ?? 0);
$totalPages = $totalTransactions > 0 ? (int) ceil($totalTransactions / $limit) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$revenueWhere = $searchSql !== ''
    ? $searchSql . " AND r.status = 'completed'"
    : " WHERE r.status = 'completed'";

$revenueResult = $conn->query("SELECT COALESCE(SUM(r.total_price), 0) AS completed_revenue FROM rentals r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN items i ON i.id = r.item_id" . $revenueWhere);
if (!$revenueResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat statistik pendapatan.']);
    exit;
}
$revenueRow = $revenueResult->fetch_assoc();
$completedRevenue = (int) ($revenueRow['completed_revenue'] ?? 0);

$sql = "SELECT r.id, r.total_price, r.start_date, r.end_date, r.status, r.created_at, u.id AS user_id, u.name AS user_name, u.email AS user_email, i.id AS item_id, i.name AS item_name FROM rentals r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN items i ON i.id = r.item_id" . $searchSql . " ORDER BY r.created_at DESC, r.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query transaksi admin.']);
    exit;
}

$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memuat data transaksi admin.']);
    exit;
}

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'id' => (int) ($row['id'] ?? 0),
        'amount' => (int) ($row['total_price'] ?? 0),
        'start_date' => (string) ($row['start_date'] ?? ''),
        'end_date' => (string) ($row['end_date'] ?? ''),
        'status' => (string) ($row['status'] ?? 'pending'),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'user' => [
            'id' => (int) ($row['user_id'] ?? 0),
            'name' => (string) ($row['user_name'] ?? 'User'),
            'email' => (string) ($row['user_email'] ?? ''),
        ],
        'item' => [
            'id' => (int) ($row['item_id'] ?? 0),
            'name' => (string) ($row['item_name'] ?? 'Barang'),
        ],
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalTransactions,
        'total_pages' => $totalPages,
    ],
    'stats' => [
        'completed_revenue' => $completedRevenue,
    ],
]);
exit;
