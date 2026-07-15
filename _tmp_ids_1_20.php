<?php
require __DIR__ . '/src/services/koneksi.php';
$result = $conn->query('SELECT id, name FROM users ORDER BY id ASC');
$ids = [];
while ($row = $result->fetch_assoc()) {
    if ((int)$row['id'] <= 20) {
        $ids[] = $row['id'] . ':' . $row['name'];
    }
}
echo implode(PHP_EOL, $ids) . PHP_EOL;
