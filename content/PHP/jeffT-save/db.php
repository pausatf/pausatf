<?php
// ----------------------------------------------------------------------------*
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script has the database access information.  It is stored outside      * 
// public_html so it is not available to the public.                           *
// ----------------------------------------------------------------------------*
        define ('DB_USER', 'YOUR_DB_USERNAME');
        define ('DB_PASSWORD', 'YOUR_DB_PASSWORD');
        define ('DB_HOST', 'localhost');
        define ('DB_NAME', 'YOUR_DB_NAME');

        $dbh = mysql_connect (DB_HOST, DB_USER, DB_PASSWORD) OR die ('Could not connect to MySQL: '. mysql_error() );
        mysql_select_db (DB_NAME) OR die ('Could not select the database: '. mysql_error() );
?>
