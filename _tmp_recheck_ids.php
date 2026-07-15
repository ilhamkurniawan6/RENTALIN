<?php
require __DIR__ . '/src/services/koneksi.php';
foreach ([13,14,17] as $id) {
    $stmt = $conn->prepare('SELECT id, name, email, avatar FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo 'ID ' . $id . ': ' . ($row ? json_encode($row, JSON_UNESCAPED_SLASHES) : 'MISSING') . PHP_EOL;
    $stmt->close();
}
