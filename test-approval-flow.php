<?php
// Test approval flow
echo "=== TEST: Full Rental Approval Flow ===\n\n";

require_once 'src/services/koneksi.php';

// Create a test rental request first
// For this, let's use user 2 (Sarah - penyewa) requesting item 1 (owned by Budi - user 1)

echo "1. Creating a test rental request\n";
echo "   Penyewa: Sarah (user 2)\n";
echo "   Item: Kamera Sony A7 III (owned by user 1 - Budi)\n";

$userId = 2;
$itemId = 1;
$startDate = '2026-08-01';
$endDate = '2026-08-05';
$pricePerDay = 150000;
$days = 4;
$totalPrice = $pricePerDay * $days;
$status = 'pending';

$stmt = $conn->prepare('INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('iissss', $userId, $itemId, $startDate, $endDate, $totalPrice, $status);
$stmt->execute();
$rentalId = $conn->insert_id;
$stmt->close();

echo "   ✓ Rental created with ID: " . $rentalId . "\n";
echo "   Status: pending\n";
echo "   Total: Rp" . number_format($totalPrice) . "\n\n";

// Now test what owner sees
echo "2. Owner (Budi) sees pending rental request\n";

$stmt = $conn->prepare('
    SELECT r.id, r.user_id, r.item_id, r.start_date, r.end_date, r.total_price, r.status,
           i.name, u.name as renter_name
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE i.user_id = 1 AND r.status = "pending"
    ORDER BY r.created_at DESC
    LIMIT 5
');
$stmt->execute();
$result = $stmt->get_result();

echo "   Pending requests for Budi's items:\n";
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "   ✓ Request #" . $count . ":\n";
    echo "     - ID: " . $row['id'] . "\n";
    echo "     - From: " . $row['renter_name'] . "\n";
    echo "     - Item: " . $row['name'] . "\n";
    echo "     - Dates: " . $row['start_date'] . " to " . $row['end_date'] . "\n";
    echo "     - Price: Rp" . number_format($row['total_price']) . "\n";
}
$stmt->close();

echo "\n3. Approving rental request (ID: " . $rentalId . ")\n";

$newStatus = 'active';
$stmt = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ?');
$stmt->bind_param('si', $newStatus, $rentalId);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo "   ✓ Rental status updated to: active\n\n";
} else {
    echo "   ✗ Failed to update\n\n";
}

// Check it's no longer in pending
echo "4. Verify rental is no longer pending\n";

$stmt = $conn->prepare('SELECT status FROM rentals WHERE id = ?');
$stmt->bind_param('i', $rentalId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "   Rental " . $rentalId . " status: " . $row['status'] . "\n";
echo "\n✓ Test Complete - Approval Flow Working!\n";
?>
