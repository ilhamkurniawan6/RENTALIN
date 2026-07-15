<?php
// Simple sanitization helpers used by server-side handlers

function sanitize_text(string $input, int $maxLen = 255): string {
    $s = trim($input);
    $s = strip_tags($s);
    // Normalize whitespace
    $s = preg_replace('/\s+/u', ' ', $s);
    if (mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}

function sanitize_textarea(string $input, int $maxLen = 2000): string {
    $s = trim($input);
    // Remove script/style tags but allow basic punctuation
    $s = strip_tags($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    if (mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}

function sanitize_phone(string $input, int $maxLen = 30): string {
    // Keep digits, plus, spaces, dashes
    $s = trim($input);
    $s = preg_replace('/[^0-9+ \-()]/', '', $s);
    if (mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}

/**
 * Sanitize a value into an integer within optional bounds.
 * Accepts numeric strings or numbers. Returns 0 when input is not numeric.
 *
 * @param mixed $value
 * @param int $min
 * @param int $max
 * @return int
 */
function sanitize_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int {
    if (!is_numeric($value)) {
        return 0;
    }

    $n = (int)$value;
    if ($n < $min) {
        return $min;
    }
    if ($n > $max) {
        return $max;
    }
    return $n;
}

?>
