<?php

// This script is used by the PA online race scoring program to upload result files
// to the proper year directory (e.g. 2024, 2025, ...).  It was written by Jeff Teeters
// on March 1, 2024.
//
// Relocated from /var/www/legacy/public_html/data/ to /var/www/html/teeters-php/
// on 2026-02-28 because PHP 8.4 open_basedir restricts execution to /var/www/html.
// Uses absolute path to the legacy data directory instead of getcwd().

class Scorer_upload {
    private $data_dir = "/var/www/legacy/public_html/data";
    private $allowed_extensions = ['html', 'htm', 'csv', 'txt', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'pdf', 'xls', 'xlsx'];
    private $max_file_size = 52428800; // 50 MiB
    public $report;
    private $uploaded_file_hashes;
    private $existing_file_hashes;
    private $upload_results;
    private $year;

    function __construct() {
        $this->year = $this->get_year();
        $form_hash = $this->get_form_hash();
        $files_hash = $this->get_files_hash();
        if (!hash_equals($form_hash, $files_hash)) {
            die("Hash mismatch, files not uploaded");
        }
        $this->get_existing_file_hashes();
        $this->move_files();
        $results = [];
        foreach ($this->upload_results as $file_name => $result) {
            $results[] = "$file_name - $result";
        }
        $msg = "success. Destination directory is '$this->year'.  Results are:\n"
            . implode("\n", $results);
        $this->report = $msg;
    }

    private function get_year() {
        if (!isset($_REQUEST["year"])) die("Year not specified");
        $year = $_REQUEST["year"];
        if (!preg_match("/^\d{4}$/", $year)) die("Invalid year");
        $year_path = $this->data_dir . "/" . $year;
        if (!is_dir($year_path)) die("Directory not found");
        $current_year = (int)date("Y");
        if ((int)$year < $current_year) die("Destination directory year is earlier than current year");
        return $year;
    }

    private function get_form_hash() {
        if (!isset($_REQUEST["hash"])) die("Hash not specified");
        return $_REQUEST["hash"];
    }

    private function get_salt() {
        $salt_file = '/etc/pausatf/scorer-salt';
        if (is_file($salt_file)) {
            $salt = trim(file_get_contents($salt_file));
            if ($salt !== '') {
                return $salt;
            }
        }
        die("Salt file missing or empty");
    }

    private function compute_hash($content) {
        return substr(md5($content . $this->get_salt()), 3, 11);
    }

    private function validate_filename($name) {
        if (strpos($name, "\0") !== false) die("Invalid filename");
        $name = basename($name);
        if (strpos($name, "..") !== false) die("Invalid filename");
        if ($name === '' || $name[0] === '.') die("Invalid filename");
        if (preg_match('/[^a-zA-Z0-9._\- ()]/', $name)) die("Filename contains invalid characters");
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions, true)) die("File type not allowed: $ext");
        return $name;
    }

    private function get_files_hash() {
        $this->uploaded_file_hashes = [];
        foreach ($_FILES["files"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["files"]["tmp_name"][$key];
                $name = $this->validate_filename($_FILES["files"]["name"][$key]);
                $size = filesize($tmp_name);
                if ($size === false || $size > $this->max_file_size) die("File too large: $name");
                $content = file_get_contents($tmp_name);
                $this->uploaded_file_hashes[$name] = $this->compute_hash($content);
            }
        }
        $files_hash = implode(",", array_values($this->uploaded_file_hashes));
        return $files_hash;
    }

    private function get_existing_file_hashes() {
        $this->existing_file_hashes = [];
        foreach ($_FILES["files"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $name = $this->validate_filename($_FILES["files"]["name"][$key]);
                $path = $this->data_dir . "/" . $this->year . "/" . $name;
                if (is_file($path)) {
                    $content = file_get_contents($path);
                    $this->existing_file_hashes[$name] = $this->compute_hash($content);
                }
            }
        }
    }

    private function move_files() {
        $this->upload_results = [];
        foreach ($_FILES["files"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $name = $this->validate_filename($_FILES["files"]["name"][$key]);
                $tmp_name = $_FILES["files"]["tmp_name"][$key];
                if (isset($this->existing_file_hashes[$name])) {
                    if ($this->existing_file_hashes[$name] == $this->uploaded_file_hashes[$name]) {
                        $this->upload_results[$name] = "No change to previous file";
                        continue;
                    } else {
                        $this->upload_results[$name] = "Previous file replaced";
                    }
                } else {
                    $this->upload_results[$name] = "New file uploaded (no previous file)";
                }
                $dest = $this->data_dir . "/" . $this->year . "/" . $name;
                move_uploaded_file($tmp_name, $dest);
            }
        }
    }
}

$su = new Scorer_upload();
echo $su->report;

?>
