<?php
require_once 'src/services/session-init.php';
require_once 'src/services/koneksi.php';
require_once 'src/services/url-helper.php';

// Simulate getting item detail
$_GET['id'] = '1';
$itemId = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;

echo "=== TEST Item Detail API ===\n";
echo "Testing item ID: " . $itemId . "\n\n";

// Test getItem
$sql = "SELECT i.id, i.user_id, i.name, i.category, i.description, i.price_per_day, i.location, i.image, i.availability, i.created_at, u.name AS owner_name, u.avatar AS owner_avatar FROM items i LEFT JOIN users u ON u.id = i.user_id WHERE i.id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        echo "✓ Item found!\n";
        echo "  - ID: " . $row['id'] . "\n";
        echo "  - Name: " . $row['name'] . "\n";
        echo "  - Owner: " . $row['owner_name'] . "\n";
        echo "  - Availability: " . ($row['availability'] ? 'Available' : 'Not Available') . "\n";
        echo "\n✓ JSON Response would be:\n";
        echo json_encode(['success' => true, 'item' => $row], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "✗ Item NOT found\n";
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "✗ SQL Prepare Error: " . $conn->error . "\n";
}

// Also test browse to see what items are available
echo "\n\n=== TEST Browse Items (First 5) ===\n";
$sql2 = "SELECT i.id, i.name, i.availability FROM items i ORDER BY i.id LIMIT 5";
$result2 = $conn->query($sql2);
if ($result2) {
    while ($row2 = $result2->fetch_assoc()) {
        echo "  - ID " . $row2['id'] . ": " . $row2['name'] . " (Available: " . ($row2['availability'] ? 'YES' : 'NO') . ")\n";
    }
} else {
    echo "✗ Error: " . $conn->error . "\n";
}
?>
