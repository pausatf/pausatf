<?php
// script to get location of IP address for conditional display of members list

// first, get IP address when using cloudflare

function get_ip() {
	$msg = "<pre>\n";
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		$msg .= "HTTP_CF_CONNECTING_IP=" . $_SERVER["HTTP_CF_CONNECTING_IP"] . "\n";
	} else {
		$msg .= "HTTP_CF_CONNECTING_IP not set" . "\n";
	}
	$msg .= "REMOTE_ADDR=". $_SERVER['REMOTE_ADDR'] . "\n";
	$msg .= "</pre>\n";
	die($msg);
}

get_ip();
?>

