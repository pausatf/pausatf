<?php

declare(strict_types=1);

/**
 * Database connection and shared utilities for PAUSATF membership system.
 * Provides PDO connection, CSRF protection, and output escaping.
 */

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('PAUSATF_DB_HOST') ?: 'localhost';
    $port = getenv('PAUSATF_DB_PORT') ?: '3306';
    $name = getenv('PAUSATF_DB_NAME') ?: 'pausatf';
    $user = getenv('PAUSATF_DB_USER') ?: '';
    $pass = getenv('PAUSATF_DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . escape_html(csrf_token()) . '">';
}

function get_post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get_post_int(string $key, int $default = 0): int
{
    $val = $_POST[$key] ?? $default;
    return (int) $val;
}
