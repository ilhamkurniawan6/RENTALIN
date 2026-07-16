<?php
/**
 * Quick Test Script untuk memverifikasi semua endpoint
 * Jalankan: php test-api.php
 */

$baseUrl = 'http://localhost/RENTALIN_TENSTING - Copy';

echo "=== RENTALIN API TEST ===\n\n";

// Test 1: Auth Status (tanpa login)
echo "1. Testing auth-status (tanpa login)...\n";
$ch = curl_init($baseUrl . '/src/pages/api/auth-status.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
echo "   Status: $httpCode\n";
echo "   Success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "   Has CSRF Token: " . (!empty($data['csrf_token']) ? 'yes' : 'no') . "\n\n";

// Test 2: Login
echo "2. Testing login (email: budi.santoso@university.edu)...\n";
$loginData = json_encode([
    'email' => 'budi.santoso@university.edu',
    'password' => 'password'
]);

$ch = curl_init($baseUrl . '/src/pages/api/auth_login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
echo "   Status: $httpCode\n";
echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";
echo "   User Name: " . ($data['user']['name'] ?? 'N/A') . "\n";
echo "   User ID: " . ($data['user']['id'] ?? 'N/A') . "\n\n";

// Test 3: Check DB connection
echo "3. Checking database connection...\n";
require_once __DIR__ . '/src/services/koneksi.php';
if ($conn && !$conn->connect_error) {
    $result = $conn->query('SELECT COUNT(*) as count FROM users');
    $row = $result->fetch_assoc();
    echo "   ✓ Database connected\n";
    echo "   Users in DB: " . $row['count'] . "\n\n";
} else {
    echo "   ✗ Database connection failed\n\n";
}

// Test 4: Check Items table
echo "4. Checking items table...\n";
$result = $conn->query('SELECT COUNT(*) as count FROM items');
$row = $result->fetch_assoc();
echo "   Total items: " . $row['count'] . "\n";

$result = $conn->query('SELECT user_id, COUNT(*) as count FROM items GROUP BY user_id ORDER BY user_id LIMIT 5');
echo "   Items per user:\n";
while ($row = $result->fetch_assoc()) {
    echo "      User " . $row['user_id'] . ": " . $row['count'] . " items\n";
}
echo "\n";

// Test 5: Check API endpoints exist
echo "5. Checking API endpoint files...\n";
$files = [
    'src/pages/api/items-crud.php' => 'Items CRUD',
    'src/pages/api/rentals-crud.php' => 'Rentals CRUD',
    'src/pages/api/auth_login.php' => 'Login',
    'src/pages/api/auth-status.php' => 'Auth Status',
];

foreach ($files as $path => $name) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = file_exists($fullPath) ? '✓' : '✗';
    echo "   $exists $name ($path)\n";
}

echo "\n=== API TEST COMPLETE ===\n\n";
echo "API Documentation: see API_DEMO_GUIDE.md\n";
echo "Base URL: $baseUrl\n";
echo "\nNext steps:\n";
echo "1. Open http://localhost/RENTALIN_TENSTING - Copy in browser\n";
echo "2. Login with demo accounts (see API_DEMO_GUIDE.md)\n";
echo "3. Test endpoints with curl or Postman\n";
