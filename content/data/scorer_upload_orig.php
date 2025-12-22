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

	function __construct() {
		$this->year = $this->get_year();
		$form_hash = $this->get_form_hash();
		$files_hash = $this->get_files_hash();
		if($form_hash != $files_hash) {
			die("Hash mismatch, files not uploaded:<br>form_hash='$form_hash'<br>files_hash='$files_hash'");
		}
		$files_uploaded =  $this->move_files();
		$msg = "success. Files were uploaded to directory $this->year:\n'"
			. implode("', '", $files_uploaded) . "'\n"
			. "form_hash ='$form_hash'\nfiles_hash='$files_hash'";
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
		$cyear = date("Y");
		if($year < $cyear) {
			die("Year ($year) earlier than current year ($cyear)");
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

	private function make_file_hash($file_path) {
		$content = file_get_contents($file_path);
		substr(md5($content . $this->salt), 3, 11);
	}

	private function get_files_hash(){
		$hashes = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES["files"]["tmp_name"][$key];
				$content = file_get_contents($tmp_name);
				$hashes[] = substr(md5($content . $this->salt), 3, 11);
			}
		}
		$files_hash = implode(",", $hashes);
		return $files_hash;
	}

	private function move_files() {
		$uploads_dir = getcwd();  // '/uploads';
		$files_uploaded = [];
		foreach ($_FILES["files"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES["files"]["tmp_name"][$key];
				// basename() may prevent filesystem traversal attacks;
				// further validation/sanitation of the filename may be appropriate
				$name = basename($_FILES["files"]["name"][$key]);
				move_uploaded_file($tmp_name, "$uploads_dir/$this->year/$name");
				$files_uploaded[] = $name;
			}
		}
		return $files_uploaded;
	}

}


$su = new Scorer_upload();
echo $su->report;

?>
