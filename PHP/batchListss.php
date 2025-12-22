<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Batch Update Scoresheet</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<?php
// ----------------------------------------------------------------------------*
// batchListss                                                                   *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script creates a listing of the scoresheets daabase                    *
// ----------------------------------------------------------------------------*
    require_once ('/home/pausat/dbss.php');
    
    $query = "SELECT * FROM RaceName 
              ORDER BY RaceNumber";

    $result1 = @mysql_query ($query);  // execute the SELECT

    if (!($result1)) {  // was SELECT successful
       echo "<p>Error in batchListss on SELECT RaceName</p><p>" . mysql_error() . '</p>';
       exit();
    } else {         
        $num = mysql_num_rows($result1);   // see if a row was returned
        if ($num > 0) {
            echo "<p><h2><font color='red'>List of 2006 Road & XC Races</font></h2></p>";
            echo '<p><i>(for scoresheet status)</i></p>';
            echo "<table><tr><td align = 'top'>";    // this <td> sets up left half of screen
            echo '<table>';             // this table is on left side 
            echo '<tr><h3>Race names and numbers</h3></tr>';
                while ($row = mysql_fetch_array($result1, MYSQL_NUM)) {
                    $rnum = $row[0];
                    $rname = $row[1];
                    $rdate = $row[2];
                    echo "<tr><td><font color='red'>$rnum</font></td><td><b>$rname</td><td>$rdate</td></b></tr>";
                } // end of while loop
            echo '</table></td>';      // end of table on left side
            echo "<td>&nbsp;&nbsp;</td>";   // This put space between the two sides
            $query = "SELECT * FROM Teams WHERE RaceNumber = '1'
              ORDER BY ClubName";

            $result2 = @mysql_query ($query);  // execute the SELECT

            if (!($result2)) {  // was SELECT successful
                echo "<p>Error in batchListss on SELECT Teams</p><p>" . mysql_error() . '</p>';
                exit();
            } else {         
                $num = mysql_num_rows($result2);   // see if a row was returned
            } 
            if ($num > 0) {
                echo "<td align='top'><table>";    // this <td> defines table on right side of screen
                echo '<tr><h3>Club names and numbers</h3></tr>';
                while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
                    $cnum = $row[1];
                    $cname = $row[2];
                    echo "<tr><td><font color='red'>$cnum</font></td><td><b>$cname</b></tr>";
                } // end of while loop
            } else {
                echo '<p>Database error in batchListss.  No rows returned in Teams table';
                exit();
            }  
            echo '</table></td></table>';               // this ends table on right side and the second <td> of 1st table
            echo '<p><b><hr></b></p>'; 
            exit();
        } else {  
            echo '<p><b>No records met WHERE condition</b></p>';
        }  // end of if ($num)
    }  // end of was SELECT successful
?>
</body>
</html>