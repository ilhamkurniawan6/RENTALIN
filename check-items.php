<?php
require_once 'src/services/koneksi.php';
$stmt = $conn->prepare('SELECT id, user_id, name FROM items WHERE id IN (1,2,3,4,5)');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "Item " . $row['id'] . ": " . $row['name'] . " (owner: user " . $row['user_id'] . ")\n";
}
$stmt->close();
?>
