<?php
// ----------------------------------------------------------------------------*
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script has the database access information.  It is stored outside      * 
// public_html so it is not available to the public.                           *
// ----------------------------------------------------------------------------*
$servername = "localhost";
$username = "dbuser";
$password = "9*ku&^hH54%";

$mysqli = new mysqli($servername, $username, $password);;
  if ($mysqli->connect_errno) {
     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  }


?>
