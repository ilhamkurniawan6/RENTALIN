<?php
function rentalin_db_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        $value = (string) $value;
    }

    if (!is_string($value)) {
        $value = '';
    }

    $value = trim($value);
    return $value !== '' ? $value : $default;
}

$host = rentalin_db_env('DB_HOST', 'localhost');
$user = rentalin_db_env('DB_USER', 'root');
$db   = rentalin_db_env('DB_NAME', 'tensting_db');

$passCandidates = [];
$envPass = rentalin_db_env('DB_PASS', '');
if ($envPass !== '') {
    $passCandidates[] = $envPass;
}
$passCandidates[] = 'root';
$passCandidates[] = 'root';

$conn = null;
$connectionError = '';

foreach ($passCandidates as $pass) {
    $conn = @mysqli_connect($host, $user, $pass, $db);
    if ($conn) {
        break;
    }
    $connectionError = mysqli_connect_error();
}

if (!$conn) {
    error_log('Database connection failed: ' . $connectionError);
}
?>