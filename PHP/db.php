<?php
// ----------------------------------------------------------------------------*
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script has the database access information.  It is stored outside      * 
// public_html so it is not available to the public.                           *
// ----------------------------------------------------------------------------*
$servername = "localhost";
$username = "YOUR_DB_USERNAME";
$password = "YOUR_DB_PASSWORD";

$mysqli = new mysqli($servername, $username, $password);;
  if ($mysqli->connect_errno) {
     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  }


?>
