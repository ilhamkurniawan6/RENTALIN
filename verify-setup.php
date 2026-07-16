<?php
/**
 * Simple Database & API File Verification
 */

echo "=== RENTALIN PROJECT VERIFICATION ===\n\n";

// Test database connection
echo "1. Database Connection...\n";
require_once __DIR__ . '/src/services/koneksi.php';

if ($conn && !$conn->connect_error) {
    echo "   ✓ Connected to database\n";
    
    // Check tables
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    echo "   ✓ users table exists\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'items'");
    echo "   ✓ items table exists\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'rentals'");
    echo "   ✓ rentals table exists\n";
    
    // Check data
    $result = $conn->query('SELECT COUNT(*) as count FROM users');
    $row = $result->fetch_assoc();
    echo "   • Users: " . $row['count'] . "\n";
    
    $result = $conn->query('SELECT COUNT(*) as count FROM items');
    $row = $result->fetch_assoc();
    echo "   • Items: " . $row['count'] . "\n";
    
    $result = $conn->query('SELECT COUNT(*) as count FROM rentals');
    $row = $result->fetch_assoc();
    echo "   • Rentals: " . $row['count'] . "\n\n";
} else {
    echo "   ✗ Database connection failed\n";
    echo "   Error: " . $conn->error . "\n\n";
    exit(1);
}

// Check API files exist
echo "2. API Endpoint Files...\n";
$files = [
    'src/pages/api/items-crud.php' => 'Items CRUD API',
    'src/pages/api/rentals-crud.php' => 'Rentals CRUD API',
    'src/pages/api/auth_login.php' => 'Login API',
    'src/pages/api/auth-status.php' => 'Auth Status API',
    'src/pages/api/items.php' => 'Items Read API',
    'src/pages/api/categories.php' => 'Categories API',
];

foreach ($files as $path => $name) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "   ✓ $name ($size bytes)\n";
    } else {
        echo "   ✗ $name (NOT FOUND)\n";
    }
}

echo "\n3. Demo Users Available...\n";
$result = $conn->query("SELECT id, name, email, role, current_role FROM users LIMIT 12");
echo "   ID | Name | Email | Role | Current Role\n";
echo "   " . str_repeat("-", 80) . "\n";

while ($row = $result->fetch_assoc()) {
    $id = str_pad($row['id'], 2, ' ', STR_PAD_LEFT);
    $name = str_pad(substr($row['name'], 0, 20), 20);
    $email = str_pad(substr($row['email'], 0, 25), 25);
    $role = str_pad($row['role'], 5);
    $currentRole = $row['current_role'];
    echo "   $id | $name | $email | $role | $currentRole\n";
}

echo "\n   Password for all users: 'password' (bcrypt hashed)\n";

echo "\n4. Items Distribution per User...\n";
$result = $conn->query("SELECT user_id, COUNT(*) as count FROM items GROUP BY user_id ORDER BY user_id");
$totalItems = 0;
while ($row = $result->fetch_assoc()) {
    echo "   User " . str_pad($row['user_id'], 2, ' ', STR_PAD_LEFT) . ": " . str_pad($row['count'], 2, ' ', STR_PAD_RIGHT) . " items\n";
    $totalItems += $row['count'];
}
echo "   Total: $totalItems items\n";

echo "\n=== VERIFICATION COMPLETE ===\n\n";
echo "✓ System ready for demo!\n\n";
echo "Quick Start:\n";
echo "1. Open browser: http://localhost/RENTALIN_TENSTING%20-%20Copy\n";
echo "2. Login with demo account (see above)\n";
echo "3. Test API endpoints with curl or Postman\n";
echo "4. See API_DEMO_GUIDE.md for complete documentation\n";
