<?php
require __DIR__ . '/src/services/koneksi.php';
$result = $conn->query('SELECT COUNT(*) AS c, MIN(id) AS min_id, MAX(id) AS max_id FROM users');
$row = $result->fetch_assoc();
echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
