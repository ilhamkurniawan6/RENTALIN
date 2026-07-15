<?php
require __DIR__ . '/src/services/koneksi.php';
$result = $conn->query('SELECT id, name, email, avatar FROM users ORDER BY id');
if (!$result) {
    fwrite(STDERR, $conn->error . PHP_EOL);
    exit(1);
}
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . ' | ' . $row['name'] . ' | ' . $row['email'] . ' | ' . ($row['avatar'] ?? '') . PHP_EOL;
}
