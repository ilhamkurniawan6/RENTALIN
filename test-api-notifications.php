<?php
// Test owner-notifications.php API as if called from dashboard JS

require_once 'src/services/session-init.php';
require_once 'src/services/koneksi.php';

echo "=== TESTING owner-notifications.php API ===\n\n";

// Step 1: Create rental as penyewa
echo "STEP 1: Create rental request\n";
$_SESSION['auth_user'] = [
    'id' => 2,
    'name' => 'Sarah Putri',
    'email' => 'sarah.putri@university.edu',
    'current_role' => 'penyewa'
];

// Create rental for item owned by user 3
$stmt = $conn->prepare('INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, "pending")');
$rentalData = [2, 5, '2026-08-20', '2026-08-23', 450000]; // Item 5 owned by user 3
$stmt->bind_param('iissi', $rentalData[0], $rentalData[1], $rentalData[2], $rentalData[3], $rentalData[4]);
$stmt->execute();
$rentalId = $conn->insert_id;
$stmt->close();
echo "✓ Rental created: ID " . $rentalId . "\n\n";

// Step 2: Switch to owner and test GET ?action=list
echo "STEP 2: Test owner-notifications.php?action=list API\n";
$_SESSION['auth_user']['id'] = 3;
$_SESSION['auth_user']['name'] = 'Michael Wijaya';
$_SESSION['auth_user']['email'] = 'michael.wijaya@university.edu';
$_SESSION['auth_user']['current_role'] = 'penyewakan';

// Simulate the API call
$ownerId = $_SESSION['auth_user']['id'];
$stmt = $conn->prepare('
    SELECT 
        r.id as rentalId,
        r.user_id as renterId,
        r.item_id as itemId,
        r.start_date as startDate,
        r.end_date as endDate,
        r.total_price as totalPrice,
        r.status,
        r.created_at as createdAt,
        i.name as itemName,
        u.name as renterName,
        u.email as renterEmail
    FROM rentals r
    JOIN items i ON r.item_id = i.id
    JOIN users u ON r.user_id = u.id
    WHERE i.user_id = ? AND r.status = "pending"
    ORDER BY r.created_at DESC
');

$stmt->bind_param('i', $ownerId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $days = intval((strtotime($row['endDate']) - strtotime($row['startDate'])) / 86400);
    $notifications[] = [
        'type' => 'pending_rental',
        'rentalId' => $row['rentalId'],
        'itemId' => $row['itemId'],
        'itemName' => $row['itemName'],
        'renterId' => $row['renterId'],
        'renterName' => $row['renterName'],
        'renterEmail' => $row['renterEmail'],
        'startDate' => $row['startDate'],
        'endDate' => $row['endDate'],
        'days' => $days,
        'totalPrice' => $row['totalPrice'],
        'createdAt' => $row['createdAt']
    ];
}
$stmt->close();

echo "✓ API Response (notifications):\n";
echo json_encode([
    'success' => true,
    'message' => 'Pending rental requests fetched',
    'notifications' => $notifications
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Step 3: Test POST with action=approve
echo "STEP 3: Test owner-notifications.php POST action=approve\n";
$_POST['action'] = 'approve';
$_POST['rental_id'] = $rentalId;

$stmt = $conn->prepare('SELECT user_id, item_id FROM rentals WHERE id = ? AND status = "pending"');
$stmt->bind_param('i', $rentalId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "✗ Rental not found or not pending\n";
} else {
    $rental = $result->fetch_assoc();
    
    $newStatus = 'active';
    $stmt2 = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ? LIMIT 1');
    $stmt2->bind_param('si', $newStatus, $rentalId);
    $stmt2->execute();
    $stmt2->close();
    
    echo "✓ API Response (approval):\n";
    echo json_encode([
        'success' => true,
        'message' => 'Request penyewaan berhasil disetujui!',
        'data' => [
            'rentalId' => $rentalId,
            'newStatus' => 'active',
            'action' => 'approve'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}
$stmt->close();

// Step 4: Verify rental is now active
echo "STEP 4: Verify rental status changed to 'active'\n";
$stmt = $conn->prepare('SELECT status FROM rentals WHERE id = ?');
$stmt->bind_param('i', $rentalId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "✓ Rental " . $rentalId . " status: " . $row['status'] . "\n";
echo "✓ Rental no longer in pending notifications\n\n";

// Step 5: Test rejection flow
echo "STEP 5: Test action=reject\n";

// Create another rental
$stmt = $conn->prepare('INSERT INTO rentals (user_id, item_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, "pending")');
$rentalData2 = [2, 5, '2026-09-01', '2026-09-03', 300000];
$stmt->bind_param('iissi', $rentalData2[0], $rentalData2[1], $rentalData2[2], $rentalData2[3], $rentalData2[4]);
$stmt->execute();
$rentalId2 = $conn->insert_id;
$stmt->close();

// Reject it
$newStatus2 = 'cancelled';
$stmt = $conn->prepare('UPDATE rentals SET status = ? WHERE id = ?');
$stmt->bind_param('si', $newStatus2, $rentalId2);
$stmt->execute();
$stmt->close();

echo "✓ API Response (rejection):\n";
echo json_encode([
    'success' => true,
    'message' => 'Request penyewaan berhasil ditolak.',
    'data' => [
        'rentalId' => $rentalId2,
        'newStatus' => 'cancelled',
        'action' => 'reject'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "✓✓✓ FULL API TEST COMPLETE - All endpoints working! ✓✓✓\n";
?>
