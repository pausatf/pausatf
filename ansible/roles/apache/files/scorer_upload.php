<?php

// This script is used by the PA online race scoring program to upload result files
// to the proper year directory (e.g. 2024, 2025, ...).  It was written by Jeff Teeters
// on March 1, 2024.
//
// Relocated from /var/www/legacy/public_html/data/ to /var/www/html/teeters-php/
// on 2026-02-28 because PHP 8.4 open_basedir restricts execution to /var/www/html.
// Uses absolute path to the legacy data directory instead of getcwd().

class Scorer_upload {

	// Base directory where year subdirectories live
	private $data_dir = "/var/www/legacy/public_html/data";

	private $md5_hashes = [];
	private $year;
	public $report;
	private $uploaded_file_hashes;
	private $existing_file_hashes;
	private $upload_results;

	// Allowed file extensions for upload
	private $allowed_extensions = ['html', 'htm', 'csv', 'txt', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'pdf', 'xls', 'xlsx'];

	function __construct() {
		$this->check_auth();
		$this->year = $this->get_year();
		$form_hash = $this->get_form_hash();
		$files_hash = $this->get_files_hash();
		if(!hash_equals($form_hash, $files_hash)) {
			die("Hash mismatch, files not uploaded");
		}
		$this->get_existing_file_hashes();
		$this->move_files();
		// make report
		$results = [];
		foreach ($this->upload_results as $file_name => $result) {
			$results[] = "$file_name - $result";
		}
		$msg = "success. Destination directory is '$this->year'.  Results are:\n"
			. implode("\n", $results);
		$this->report = $msg;
	}

	private function check_auth() {
		$token_file = '/etc/pausatf/scorer-upload-token';
		if (!is_file($token_file)) {
			// No token file deployed; auth disabled (first-run compatibility)
			return;
		}
		$expected = trim(file_get_contents($token_file));
		if (empty($expected)) {
			return;
		}
		$provided = $_SERVER['HTTP_X_UPLOAD_TOKEN'] ?? $_REQUEST['token'] ?? '';
		if (!hash_equals($expected, $provided)) {
			http_response_code(403);
			die('Forbidden: invalid upload token');
		}
	}

	private function get_salt() {
		$salt_file = '/etc/pausatf/scorer-salt';
		if (is_file($salt_file)) {
			return trim(file_get_contents($salt_file));
		}
		// Fallback for backwards compatibility during migration
		return "ju75f34M9";
	}

	private function get_year() {
		if(!isset($_REQUEST["year"])) {
			die("Year not specified");
		}
		$year = $_REQUEST["year"];
		if(!preg_match("/^\d{4}$/", $year)) {
			die("Invalid year");
		}
		$year_path = $this->data_dir . "/" . $year;
		if(!is_dir($year_path)) {
			die("Directory not found");
		}
		$current_year = (int) date("Y");
		if((int) $year < $current_year) {
			die("Destination directory year is earlier than current year");
		}
		return $year;
	}

	private function get_form_hash() {
		if(!isset($_REQUEST["hash"])) {
			die("Hash not specified");
		}
		return $_REQUEST["hash"];
	}

	private function compute_hash($content) {
		$salt = $this->get_salt();
		return substr(hash_hmac('sha256', $content, $salt), 0, 16);
	}

	private function validate_filename($name) {
		$name = basename($name);
		if ($name === '' || $name[0] === '.') {
			die("Invalid filename");
		}
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (!in_array($ext, $this->allowed_extensions, true)) {
			die("File type not allowed: $ext");
		}
		return $name;
	}

	private function get_files_hash(){
		$this->uploaded_file_hashes = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES["files"]["tmp_name"][$key];
				$name = $this->validate_filename($_FILES["files"]["name"][$key]);
				$content = file_get_contents($tmp_name);
				$this->uploaded_file_hashes[$name] = $this->compute_hash($content);
			}
		}
		$files_hash = implode(",", array_values($this->uploaded_file_hashes));
		return $files_hash;
	}

	private function get_existing_file_hashes(){
		$this->existing_file_hashes = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$name = $this->validate_filename($_FILES["files"]["name"][$key]);
				$path = $this->data_dir . "/" . $this->year . "/" . $name;
				if(is_file($path)) {
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
				if(isset($this->existing_file_hashes[$name])) {
					if ($this->existing_file_hashes[$name] == $this->uploaded_file_hashes[$name]) {
						$this->upload_results[$name] = "No change to previous file";
						continue;
					} else {
						$this->upload_results[$name] = "Previous file replaced";
					}
				} else {
					$this->upload_results[$name] =  "New file uploaded (no previous file)";
				}
				$tmp_name = $_FILES["files"]["tmp_name"][$key];
				$dest = $this->data_dir . "/" . $this->year . "/" . $name;
				move_uploaded_file($tmp_name, $dest);
			}
		}
	}
}


$su = new Scorer_upload();
echo $su->report;

?>
