<?php
// Simple application logger for development/debugging.
function app_log(string $tag, string $line): void
{
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }

    $logFile = $logsDir . '/app.log';
    $ts = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'cli';
    $entry = "[{$ts}] [{$tag}] [{$ip}] " . trim($line) . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function rentalin_log_path(): string
{
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir . '/app.log';
}

function rentalin_log(string $level, string $message, array $context = []): void
{
    $time = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $ctx = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $line = "[$time] [$level] $message" . ($ctx && $ctx !== '[]' ? " | $ctx" : '') . PHP_EOL;
    $path = rentalin_log_path();

    // Try to append, fallback to error_log.
    if (false === @file_put_contents($path, $line, FILE_APPEND | LOCK_EX)) {
        error_log($line);
    }
}

function rentalin_log_error(string $message, array $context = []): void
{
    rentalin_log('ERROR', $message, $context);
}

function rentalin_log_info(string $message, array $context = []): void
{
    rentalin_log('INFO', $message, $context);
}

