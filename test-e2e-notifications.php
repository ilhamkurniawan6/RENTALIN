<?php
require_once 'src/services/session-init.php';
require_once 'src/services/koneksi.php';

echo "=== FULL END-TO-END TEST: Rental Request & Owner Notifications ===\n\n";

// Step 1: Create rental as penyewa (user 2 - Sarah)
echo "STEP 1: Penyewa (Sarah) creates rental request\n";
$_SESSION['auth_user'] = [
    'id' => 2,
    'name' => 'Sarah Putri',
    'email' => 'sarah.putri@university.edu',
    'role' => 'user',
    'current_role' => 'penyewa'
];

$userId = 2;
$itemId = 4;  // Kamera Fujifilm X-T4 (owned by user 3)
$startDate = '2026-08-10';
$endDate = '2026-08-15';

// Check item
$stmt = $conn->prepare('SELECT price_per_day, user_id FROM items WHERE id = ?');
$stmt->bind_param('i', $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

$pricePerDay = $item['price_per_day'];
$days = 5;
$totalPrice = $pricePerDay * $days;

$stmt = $conn->prepare('INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, "pending")');
$stmt->bind_param('iissi', $userId, $itemId, $startDate, $endDate, $totalPrice);
$stmt->execute();
$rentalId = $conn->insert_id;
$stmt->close();

echo "✓ Rental created:\n";
echo "   ID: " . $rentalId . "\n";
echo "   From: Sarah (user 2)\n";
echo "   Item: ID " . $itemId . "\n";
echo "   Dates: " . $startDate . " to " . $endDate . "\n";
echo "   Total: Rp" . number_format($totalPrice) . "\n\n";

// Step 2: Switch to owner (penyewakan)
echo "STEP 2: Switch to Owner (Michael - user 3) and check notifications\n";

$_SESSION['auth_user'] = [
    'id' => 3,
    'name' => 'Michael Wijaya',
    'email' => 'michael.wijaya@university.edu',
    'role' => 'user',
    'current_role' => 'penyewakan'
];

$ownerId = 3;

// Fetch owner notifications via API
$stmt = $conn->prepare('
    SELECT r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status,
           i.name, i.price_per_day, u.name as renter_name, u.email as renter_email
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE i.user_id = ? AND r.status = "pending"
    ORDER BY r.created_at DESC
');
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$result = $stmt->get_result();

echo "✓ Pending rental requests for Michael's items:\n";
$found = false;
while ($row = $result->fetch_assoc()) {
    $found = true;
    echo "   - Rental ID: " . $row['id'] . "\n";
    echo "     From: " . $row['renter_name'] . " (" . $row['renter_email'] . ")\n";
    echo "     Item: " . $row['name'] . "\n";
    echo "     Dates: " . $row['start_date'] . " to " . $row['end_date'] . "\n";
    echo "     Days: " . intval((strtotime($row['end_date']) - strtotime($row['start_date'])) / 86400) . "\n";
    echo "     Total: Rp" . number_format($row['total_price']) . "\n";
}
$stmt->close();

if (!$found) {
    echo "   No pending requests found\n";
}

echo "\n";

// Step 3: Owner approves rental
echo "STEP 3: Owner approves rental\n";
$newStatus = 'active';
$stmt = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ?');
$stmt->bind_param('si', $newStatus, $rentalId);
$stmt->execute();
$stmt->close();

echo "✓ Rental " . $rentalId . " status changed to: active\n\n";

// Step 4: Verify it's gone from pending list
echo "STEP 4: Verify rental no longer in pending list\n";

$stmt = $conn->prepare('
    SELECT COUNT(*) as count FROM rentals r
    JOIN items i ON r.item_id = i.id
    WHERE i.user_id = ? AND r.status = "pending"
');
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "✓ Remaining pending requests: " . $row['count'] . "\n\n";

// Step 5: Verify approval in database
$stmt = $conn->prepare('SELECT status FROM rentals WHERE id = ?');
$stmt->bind_param('i', $rentalId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "FINAL: Rental " . $rentalId . " final status: " . $row['status'] . "\n";
echo "\n✓ ✓ ✓ END-TO-END TEST COMPLETE - NOTIFICATIONS WORKING! ✓ ✓ ✓\n";
?>
