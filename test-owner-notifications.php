<?php
require_once 'src/services/session-init.php';
require_once 'src/services/koneksi.php';

echo "=== TEST Owner Notifications API ===\n\n";

// Simulate session for user ID 1 (Budi - owner of item 48)
$_SESSION['auth_user'] = [
    'id' => 1,
    'name' => 'Budi Santoso',
    'email' => 'budi.santoso@university.edu',
    'role' => 'user',
    'current_role' => 'penyewakan'
];

echo "1. Testing GET - List pending rentals for owner\n";
echo "   User ID: 1 (Budi - owner)\n\n";

// Check what rentals exist for item 48 (owned by user 1)
$sql = "SELECT r.id, r.item_id, i.name, r.user_id, u.name as renter FROM rentals r 
        JOIN items i ON r.item_id = i.id 
        JOIN users u ON r.user_id = u.id 
        WHERE i.user_id = 1 AND r.status = 'pending'";
$result = $conn->query($sql);

echo "   Pending rentals for user 1's items:\n";
while ($row = $result->fetch_assoc()) {
    echo "   - Rental ID: " . $row['id'] . "\n";
    echo "     Item: " . $row['name'] . " (ID: " . $row['item_id'] . ")\n";
    echo "     Renter: " . $row['renter'] . " (ID: " . $row['user_id'] . ")\n\n";
}

echo "\n2. Testing API response structure\n";
// Load the API file and execute it
$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require 'src/pages/api/owner-notifications.php';
$output = ob_get_clean();

echo "   API Response:\n";
$data = json_decode($output, true);
if ($data && isset($data['data'])) {
    echo "   ✓ " . count($data['data']['notifications']) . " notifications found\n";
    if (!empty($data['data']['notifications'])) {
        foreach ($data['data']['notifications'] as $n) {
            echo "     - Rental ID: " . $n['rentalId'] . "\n";
            echo "       Renter: " . $n['renterName'] . "\n";
            echo "       Item: " . $n['itemName'] . "\n";
            echo "       Price: Rp" . number_format($n['totalPrice']) . "\n";
        }
    }
}

echo "\n✓ Test Complete\n";
?>
