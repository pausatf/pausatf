<?php
// Database connection configuration.
// Created August 2005 by Dan Preston. Rewritten February 2026.

define("DB_USER", "dbuser");
define("DB_PASSWORD", "9*ku&^hH54%");
define("DB_HOST", "localhost");
define("DB_NAME", "pausatf_php");

// PDO singleton for new code. Returns null if DB is unavailable.
function get_pdo(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }
    $attempted = true;

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }

    return $pdo;
}

// Backward compat: $con as mysqli for legacy scripts (clubs.php, score-tools.php).
$con = @(new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME));
if ($con->connect_errno) {
    $con = null;
}
