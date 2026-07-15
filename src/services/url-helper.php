<?php

if (!function_exists('rentalin_project_base_path')) {
    function rentalin_project_base_path(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', $scriptName);

        if ($scriptName === '') {
            return '';
        }

        $basePath = dirname(dirname(dirname(dirname($scriptName))));
        return $basePath === '/' ? '' : rtrim($basePath, '/');
    }
}

if (!function_exists('rentalin_public_url')) {
    function rentalin_encode_path(string $path): string
    {
        $segments = array_filter(explode('/', trim(str_replace('\\', '/', $path), '/')), static fn ($segment) => $segment !== '');
        $encoded = array_map('rawurlencode', $segments);

        return '/' . implode('/', $encoded);
    }

    function rentalin_split_url(string $url): array
    {
        $parts = explode('?', $url, 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    function rentalin_public_url(string $path): string
    {
        $basePath = rentalin_project_base_path();
        [$pathOnly, $query] = rentalin_split_url($path);
        $normalizedPath = rentalin_encode_path($pathOnly);
        $encodedBasePath = $basePath !== '' ? rentalin_encode_path($basePath) : '';

        return $encodedBasePath . $normalizedPath . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('rentalin_avatar_url_from_filename')) {
    function rentalin_avatar_url_from_filename(string $filename): string
    {
        return rentalin_public_url('src/pages/media/avatar.php?f=' . rawurlencode($filename));
    }
}

if (!function_exists('rentalin_normalize_avatar_url')) {
    function rentalin_normalize_avatar_url(?string $avatarUrl): string
    {
        $avatarUrl = trim((string) $avatarUrl);
        if ($avatarUrl === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $avatarUrl) || str_starts_with($avatarUrl, 'data:')) {
            return $avatarUrl;
        }

        if (str_starts_with($avatarUrl, '/src/pages/media/avatar.php')) {
            return rentalin_public_url(ltrim($avatarUrl, '/'));
        }

        if (str_starts_with($avatarUrl, 'src/pages/media/avatar.php')) {
            return rentalin_public_url($avatarUrl);
        }

        if (preg_match('/^avatar_\d+_.*\.(jpg|jpeg|png)$/i', $avatarUrl)) {
            return rentalin_avatar_url_from_filename($avatarUrl);
        }

        return $avatarUrl;
    }
}
