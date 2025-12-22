<?php


// This script is used by the new PA online race scoring program to upload result files
// to the proper year directory (e.g. 2024, 2025, ...).  It was written by Jeff Teeters
// on March 1, 2024.  This is used so the scorers do not have to upload files using the
// wordpress file manager.  It processes forms that are submitted by code in file
// "publisher.php" in the online scoring program.

class Scorer_upload {

	private $md5_hashes = [];   // md5 hash of each file
	private $salt = "ju75f34M9";  // salt for md5
	private $year;  // directory for storing uploaded files
	public $report;  // message to display on successful upload.  Starts with "success"
	private $uploaded_file_hashes;
	private $existing_file_hashes;
	private $upload_results;


	function __construct() {
		$this->year = $this->get_year();
		$form_hash = $this->get_form_hash();
		$files_hash = $this->get_files_hash();
		if($form_hash != $files_hash) {
			die("Hash mismatch, files not uploaded:<br>form_hash='$form_hash'<br>files_hash='$files_hash'");
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
			// . print_r($this->upload_results, true) . "</pre>\n";
			// . "form_hash='$form_hash'\nfiles_hash='$files_hash'</pre>'";
		$this->report = $msg;
	}

	private function get_year() {
		if(!isset($_REQUEST["year"])) {
			die("Year not specified");
		}
		$year = $_REQUEST["year"];
		if(!preg_match("/^\d\d\d\d$/", $year)) {
			die("Invalid year: '$year'");
		}
		if(!is_dir($year)) {
			die("Directory '$year' not found");
		}
		$current_year = date("Y");
		if($year < $current_year) {
			die("Destination directory year ($year) is earlier than curret year ($current_year)");
		}
		return $year;
	}

	private function get_form_hash() {
		if(!isset($_REQUEST["hash"])) {
			die("Hash not specified");
		}
		$form_hash = $_REQUEST["hash"];
		return $form_hash;
	}

	private function compute_hash($content) {
		return substr(md5($content . $this->salt), 3, 11);
	}

	// private function make_file_hash($file_path) {
	//  	$content = file_get_contents($file_path);
	//  	substr(md5($content . $this->salt), 3, 11);
	// }

	private function get_files_hash(){
		$this->uploaded_file_hashes = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES["files"]["tmp_name"][$key];
				$name = basename($_FILES["files"]["name"][$key]);
				$content = file_get_contents($tmp_name);
				$this->uploaded_file_hashes[$name] = $this->compute_hash($content);
			}
		}
		$files_hash = implode(",", array_values($this->uploaded_file_hashes));
		return $files_hash;
	}

	private function get_existing_file_hashes(){
		$uploads_dir = getcwd();  // directory script is in
		$this->existing_file_hashes = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$name = basename($_FILES["files"]["name"][$key]);
				$path = "$uploads_dir/$this->year/$name";
				if(is_file($path)) {
					$content = file_get_contents($path);
					$this->existing_file_hashes[$name] = $this->compute_hash($content);
				}
			}
		}
	}

	private function move_files() {
		// possible upload_results for each file:
		// "No previous file"
		// "Previous file replaced"
		// "No change to previous file"
		$uploads_dir = getcwd();  // directory script is in
		$this->upload_results = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$name = basename($_FILES["files"]["name"][$key]);
				if(isset($this->existing_file_hashes[$name])) {
					if ($this->existing_file_hashes[$name] == $this->uploaded_file_hashes[$name]) {
						// file didn't change
						$this->upload_results[$name] = "No change to previous file";
						continue;
					} else {
						$this->upload_results[$name] = "Previous file replaced";
					}
				} else {
					$this->upload_results[$name] =  "New file uploaded (no previous file)";
				}
				$tmp_name = $_FILES["files"]["tmp_name"][$key];	
				move_uploaded_file($tmp_name, "$uploads_dir/$this->year/$name");
			}
		}
	}
}


$su = new Scorer_upload();
echo $su->report;

?>
