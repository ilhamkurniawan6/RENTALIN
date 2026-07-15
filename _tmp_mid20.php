<?php
require __DIR__ . '/src/services/koneksi.php';
$result = $conn->query('SELECT id, name FROM users WHERE id BETWEEN 1 AND 20 ORDER BY id ASC');
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . ' | ' . $row['name'] . PHP_EOL;
}
