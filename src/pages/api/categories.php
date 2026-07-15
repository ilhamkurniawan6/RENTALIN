<?php
require_once __DIR__ . '/../../services/session-init.php';
require_once __DIR__ . '/../../services/koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$sql = "SELECT c.name, c.icon, COUNT(i.id) AS count FROM categories c LEFT JOIN items i ON i.category = c.name GROUP BY c.name, c.icon ORDER BY c.name ASC";
$result = $conn->query($sql);

$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'name' => (string) ($row['name'] ?? ''),
            'icon' => (string) ($row['icon'] ?? 'Package'),
            'count' => (int) ($row['count'] ?? 0),
        ];
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
exit;
