<?php
/**
 * Fix Password Hashes - Update all users with correct bcrypt hash for "password"
 */

require_once __DIR__ . '/src/services/koneksi.php';

echo "=== FIXING PASSWORD HASHES ===\n\n";

// Generate correct bcrypt hash for "password"
$testPassword = "password";
$correctHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 10]);

echo "1. Generating bcrypt hash for 'password'...\n";
echo "   Hash: " . $correctHash . "\n\n";

// Verify the hash works
$verifyTest = password_verify($testPassword, $correctHash);
echo "2. Testing new hash with password_verify()...\n";
echo "   Result: " . ($verifyTest ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Update all users with correct hash
echo "3. Updating all users in database...\n";
$updateStmt = $conn->prepare("UPDATE users SET password = ?");
if (!$updateStmt) {
    echo "   ✗ Error: " . $conn->error . "\n";
    exit(1);
}

$updateStmt->bind_param("s", $correctHash);
if (!$updateStmt->execute()) {
    echo "   ✗ Error: " . $conn->error . "\n";
    exit(1);
}

$affectedRows = $updateStmt->affected_rows;
$updateStmt->close();

echo "   ✓ Updated $affectedRows users\n\n";

// Verify update
echo "4. Verifying update...\n";
$result = $conn->query("SELECT id, email, password FROM users LIMIT 3");
$verified = 0;
while ($row = $result->fetch_assoc()) {
    $isValid = password_verify($testPassword, $row['password']);
    if ($isValid) {
        $verified++;
        echo "   ✓ User " . $row['id'] . " (" . $row['email'] . "): hash verified\n";
    } else {
        echo "   ✗ User " . $row['id'] . ": verification FAILED\n";
    }
}

echo "\n=== UPDATE COMPLETE ===\n\n";
echo "✓ All users can now login with password: 'password'\n\n";
echo "Demo accounts:\n";
echo "  1. budi.santoso@university.edu / password\n";
echo "  2. sarah.putri@university.edu / password\n";
echo "  3. admin@university.edu / password\n";
