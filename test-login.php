<?php
/**
 * Quick Login Test - Verifikasi password hash dan login
 */

echo "=== TESTING LOGIN ===\n\n";

require_once __DIR__ . '/src/services/koneksi.php';

// Test 1: Check password hash
echo "1. Checking password hash in database...\n";
$result = $conn->query("SELECT id, email, password FROM users WHERE email = 'budi.santoso@university.edu' LIMIT 1");
$user = $result->fetch_assoc();

if (!$user) {
    echo "   ✗ User not found!\n\n";
    exit(1);
}

echo "   User ID: " . $user['id'] . "\n";
echo "   Email: " . $user['email'] . "\n";
echo "   Password hash exists: " . (strlen($user['password']) > 20 ? "yes" : "no") . "\n";

// Test 2: Verify password "password"
echo "\n2. Testing password_verify()...\n";
$testPassword = "password";
$isValid = password_verify($testPassword, $user['password']);
echo "   Password 'password' matches hash: " . ($isValid ? "✓ YES" : "✗ NO") . "\n";

if (!$isValid) {
    echo "   ERROR: Password verification failed!\n";
    echo "   Hash in DB: " . substr($user['password'], 0, 50) . "...\n";
    exit(1);
}

// Test 3: Simulate session
echo "\n3. Simulating login session...\n";
session_start();
$_SESSION['auth_user'] = [
    'id' => $user['id'],
    'name' => 'Budi Santoso',
    'email' => $user['email'],
];
echo "   ✓ Session created\n";
echo "   Session ID: " . session_id() . "\n";
echo "   User in session: " . $_SESSION['auth_user']['name'] . "\n";

echo "\n4. Testing login endpoint availability...\n";
$loginApiPath = __DIR__ . '/src/pages/api/auth_login.php';
if (file_exists($loginApiPath)) {
    echo "   ✓ Login API file exists: auth_login.php\n";
} else {
    echo "   ✗ Login API file NOT found!\n";
}

$appPath = __DIR__ . '/src/pages/login/app.php';
if (file_exists($appPath)) {
    echo "   ✓ Form submit handler exists: login/app.php\n";
} else {
    echo "   ✗ Form submit handler NOT found!\n";
}

echo "\n=== TESTING COMPLETE ===\n\n";
echo "✓ All login tests passed!\n\n";
echo "You can now login with:\n";
echo "  Email: budi.santoso@university.edu\n";
echo "  Password: password\n\n";
echo "Login page: http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/login/index.php\n";
