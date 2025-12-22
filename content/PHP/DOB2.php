<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>DOB Club Listing</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<div align="left">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><font size="2"><a href="/"><b>Home</b></a><br>
<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
<td valign="top">
<h2><font color="blue">PA/USATF Youth Clubs</font></h2>
</center>
<?php
// ----------------------------------------------------------------------------*
// DOB2.php                                                                    *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This script handles the display of club names and numbers for DOB database. *
// ----------------------------------------------------------------------------*
        require_once ('/home/pausat/DOB.php');

        $query = "SELECT * FROM DOBClubs"; 
                
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error in DOB2 pgm on SELECT</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if ($num == 0) {  
            echo '<p><b>No Data returned. Contact webmaster';
            exit();
        } else {
             while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                 $club = $row[0];
                 $name = $row[1];
                 echo "<p><b>$club &nbsp;&nbsp&nbsp;&nbsp;$name</b></p>";
             } // end of while
        } 
?>
</td></tr></table></div>
</body>
</html>