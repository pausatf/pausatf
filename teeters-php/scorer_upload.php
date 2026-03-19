<?php

declare(strict_types=1);

/**
 * PA USATF race result uploader.
 *
 * Receives multipart file uploads from Jeff Teeters' online scoring
 * program and writes them to the year subdirectory under the legacy
 * data tree.  Original by Jeff Teeters, March 2024.  Rewritten for
 * PHP 8.4 idioms, March 2026.
 *
 * External contract (unchanged):
 *   POST /teeters-php/scorer_upload.php
 *   Fields: year (4-digit), hash (client-computed), files[] (multipart)
 *   Success: 200 "success. Destination directory is '2026'.  Results are:\n..."
 *   Failure: 4xx with plain-text reason
 */

final class UploadException extends RuntimeException {}

final class ScorerUpload
{
    private const DATA_DIR = '/var/www/legacy/public_html/data';
    private const SALT_FILE = '/etc/pausatf/scorer-salt';
    private const TOKEN_FILE = '/etc/pausatf/scorer-upload-token';
    private const LOG_FILE = '/var/log/scorer-upload.log';

    private const ALLOWED_EXTENSIONS = [
        'html', 'htm', 'csv', 'txt', 'css', 'js',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico',
        'pdf', 'xls', 'xlsx',
    ];

    private const MAX_FILE_SIZE = 52_428_800; // 50 MiB

    private readonly string $year;
    private readonly string $salt;

    /** @var array<string, string> name => hash */
    private array $uploadedHashes = [];

    /** @var array<string, string> name => hash */
    private array $existingHashes = [];

    /** @var array<string, string> name => result description */
    private array $results = [];

    public function __construct()
    {
        if (!function_exists('md5')) {
            throw new UploadException('md5 unavailable (FIPS mode); upload endpoint cannot operate', 500);
        }
        $this->salt = $this->loadSalt();
    }

    public function process(): string
    {
        $this->authenticate();
        $this->year = $this->parseYear();
        $this->verifyHash();
        $this->snapshotExisting();
        $this->moveFiles();
        $this->log();

        return $this->formatReport();
    }

    private function authenticate(): void
    {
        // Auth is opt-in: skip when token file is absent or empty.
        // Jeff's client does not send tokens yet. Once a token is
        // deployed to TOKEN_FILE, all uploads without it are rejected.
        if (!is_file(self::TOKEN_FILE)) {
            return;
        }

        $raw = file_get_contents(self::TOKEN_FILE);
        if ($raw === false) {
            throw new UploadException('Token file unreadable', 500);
        }
        $expected = trim($raw);
        if ($expected === '') {
            return;
        }

        $provided = $_SERVER['HTTP_X_UPLOAD_TOKEN'] ?? $_POST['token'] ?? '';
        if (!is_string($provided) || $provided === '' || strlen($provided) > 256) {
            throw new UploadException('Invalid upload token', 403);
        }
        if (!hash_equals($expected, $provided)) {
            throw new UploadException('Invalid upload token', 403);
        }
    }

    private function loadSalt(): string
    {
        if (!is_file(self::SALT_FILE)) {
            throw new UploadException('Salt file not configured', 500);
        }

        $salt = trim((string) file_get_contents(self::SALT_FILE));
        if ($salt === '') {
            throw new UploadException('Salt file is empty', 500);
        }

        return $salt;
    }

    private function parseYear(): string
    {
        $year = $_REQUEST['year'] ?? '';
        if (!is_string($year) || !preg_match('/^\d{4}$/', $year)) {
            throw new UploadException('Year not specified or invalid', 400);
        }

        $yearPath = self::DATA_DIR . '/' . $year;
        if (!is_dir($yearPath)) {
            throw new UploadException('Destination directory not found', 404);
        }

        if ((int) $year < (int) date('Y')) {
            throw new UploadException('Destination year is earlier than current year', 400);
        }

        return $year;
    }

    private function computeHash(string $content): string
    {
        return substr(md5($content . $this->salt), 3, 11);
    }

    private function validateFilename(string $name): string
    {
        if (str_contains($name, "\0")) {
            throw new UploadException('Invalid filename', 400);
        }
        $name = basename($name);
        if ($name === '' || $name[0] === '.') {
            throw new UploadException("Invalid filename: '$name'", 400);
        }
        if (preg_match('/[^a-zA-Z0-9._\- ()]/', $name)) {
            throw new UploadException('Filename contains invalid characters', 400);
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new UploadException("File type not allowed: $ext", 400);
        }

        return $name;
    }

    /**
     * Build hashes of uploaded files and verify against client-provided hash.
     */
    private function verifyHash(): void
    {
        $clientHash = $_REQUEST['hash'] ?? '';
        if (!is_string($clientHash) || $clientHash === '') {
            throw new UploadException('Hash not specified', 400);
        }
        if (!preg_match('/^[a-f0-9,]+$/', $clientHash)) {
            throw new UploadException('Invalid hash format', 400);
        }

        if (!isset($_FILES['files']['error']) || !is_array($_FILES['files']['error'])) {
            throw new UploadException('No files uploaded', 400);
        }

        foreach ($_FILES['files']['error'] as $key => $error) {
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            $name = $this->validateFilename($_FILES['files']['name'][$key]);
            $tmpPath = $_FILES['files']['tmp_name'][$key];
            $size = filesize($tmpPath);
            if ($size === false || $size > self::MAX_FILE_SIZE) {
                throw new UploadException("File too large: $name", 400);
            }
            $content = (string) file_get_contents($tmpPath);
            $this->uploadedHashes[$name] = $this->computeHash($content);
        }

        if ($this->uploadedHashes === []) {
            throw new UploadException('No valid files in upload', 400);
        }

        $serverHash = implode(',', array_values($this->uploadedHashes));
        if (!hash_equals($serverHash, $clientHash)) {
            throw new UploadException('Hash mismatch, files not uploaded', 400);
        }
    }

    /**
     * Snapshot hashes of existing files so we can report changed vs new.
     */
    private function snapshotExisting(): void
    {
        foreach (array_keys($this->uploadedHashes) as $name) {
            $path = self::DATA_DIR . '/' . $this->year . '/' . $name;
            if (is_file($path)) {
                $content = (string) file_get_contents($path);
                $this->existingHashes[$name] = $this->computeHash($content);
            }
        }
    }

    private function moveFiles(): void
    {
        foreach ($_FILES['files']['error'] as $key => $error) {
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            $name = $this->validateFilename($_FILES['files']['name'][$key]);
            if (!isset($this->uploadedHashes[$name])) {
                continue;
            }

            if (isset($this->existingHashes[$name])) {
                if ($this->existingHashes[$name] === $this->uploadedHashes[$name]) {
                    $this->results[$name] = 'No change to previous file';
                    continue;
                }
                $this->results[$name] = 'Previous file replaced';
            } else {
                $this->results[$name] = 'New file uploaded (no previous file)';
            }

            $dest = self::DATA_DIR . '/' . $this->year . '/' . $name;
            if (!move_uploaded_file($_FILES['files']['tmp_name'][$key], $dest)) {
                $lastError = error_get_last();
                throw new UploadException("Failed to write $name: " . ($lastError['message'] ?? 'unknown error'), 500);
            }
        }
    }

    private function formatReport(): string
    {
        $lines = [];
        foreach ($this->results as $name => $result) {
            $lines[] = "$name - $result";
        }

        return "success. Destination directory is '$this->year'.  Results are:\n"
            . implode("\n", $lines);
    }

    private function log(): void
    {
        $rawIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip = substr((string) preg_replace('/[^\da-fA-F.:, ]/', '', (string) $rawIp), 0, 256);
        $count = count($this->results);
        $names = (string) preg_replace('/[^\x20-\x7E]/', '', implode(', ', array_keys($this->results)));
        $entry = sprintf(
            "[%s] ip=%s year=%s files=%d names=%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $ip,
            $this->year,
            $count,
            $names,
        );

        file_put_contents(self::LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
    }
}

// Entry point
header('Content-Type: text/plain; charset=UTF-8');

try {
    $uploader = new ScorerUpload();
    echo $uploader->process();
} catch (UploadException $e) {
    http_response_code($e->getCode() ?: 500);
    echo $e->getMessage();
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Internal server error';
    @file_put_contents(
        '/var/log/scorer-upload.log',
        sprintf("[%s] UNCAUGHT: %s in %s:%d\n", gmdate('Y-m-d\TH:i:s\Z'), $e->getMessage(), $e->getFile(), $e->getLine()),
        FILE_APPEND | LOCK_EX,
    );
}
