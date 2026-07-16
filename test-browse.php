<?php
require_once 'src/services/session-init.php';
require_once 'src/services/koneksi.php';
require_once 'src/services/url-helper.php';

echo "=== TEST Browse Items API Response ===\n\n";

// Simulate browse endpoint
$sql = "SELECT i.id, i.user_id, i.name, i.category, i.description, i.price_per_day, i.location, i.image, i.availability, i.created_at, u.name AS owner_name, u.avatar AS owner_avatar, COALESCE(r.avg_rating, 0) AS owner_rating FROM items i LEFT JOIN users u ON u.id = i.user_id LEFT JOIN (SELECT to_user_id, ROUND(AVG(rating), 1) AS avg_rating FROM reviews GROUP BY to_user_id) r ON r.to_user_id = u.id ORDER BY i.created_at DESC, i.id DESC LIMIT 3";

$result = $conn->query($sql);
$items = [];

while ($row = $result->fetch_assoc()) {
    $itemId = (int) $row['id'];
    $item = [
        'id' => (string) $itemId,
        'name' => (string) ($row['name'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'pricePerDay' => (int) ($row['price_per_day'] ?? 0),
        'image' => rentalin_public_url('src/pages/media/item.php?id=' . $itemId),
        'description' => (string) ($row['description'] ?? ''),
        'owner' => [
            'name' => (string) ($row['owner_name'] ?? 'Rentalin User'),
            'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop',
            'rating' => (float) ($row['owner_rating'] ?? 0),
        ],
        'location' => (string) ($row['location'] ?? ''),
        'availability' => (bool) ($row['availability'] ?? false),
        'createdAt' => (string) ($row['created_at'] ?? ''),
    ];
    $items[] = $item;
}

echo "✓ " . count($items) . " items loaded\n\n";
echo json_encode(['success' => true, 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
?>
