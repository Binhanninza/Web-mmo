<?php
function load_env_file(string $path): void {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_value(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_request_secure(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    $forwarded_proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
    if ($forwarded_proto === 'https') {
        return true;
    }

    return false;
}

function set_cookie_secure(string $name, string $value, int $expires, string $same_site = 'Lax'): void {
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => is_request_secure(),
        'httponly' => true,
        'samesite' => $same_site,
    ]);
}

function set_remember_cookie(string $token, int $expires): void {
    set_cookie_secure('site_remember', $token, $expires, 'Lax');
}

function clear_remember_cookie(): void {
    set_remember_cookie('', time() - 3600);
    unset($_COOKIE['site_remember']);
}
?>
