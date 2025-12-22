<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Batch Update Scoresheet</title>
</head>
<body>
<?php
// ----------------------------------------------------------------------------*
// batchUtillss                                                                   *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script is a batch utility program for batch updates to the database    *
// This utility was used to generate orginal database                          *
// See batchRacess and batchClubss to add a new Race or Club to the database   *
// ----------------------------------------------------------------------------*
    require_once ('/home/pausat/dbss.php');
    
   $query = "SELECT * FROM Teams";

    $result1 = @mysql_query ($query);  // execute the SELECT

    if (!($result1)) {  // was SELECT successful
       echo "<p>Error in batchUtilss on SELECT</p><p>" . mysql_error() . '</p>';
       exit();
    } else {         
        $num = mysql_num_rows($result1);   // see if a row was returned
        if ($num > 0) {
            while ($row = mysql_fetch_array($result1, MYSQL_NUM)) {
                $cnum = $row[1];
                $cname = $row[2];
                $MO = $row[3];
                $MM = $row[4];
                $MS = $row[5];
                $MSS = $row[6];
                $MV = $row[7];
                $WO = $row[8];
                $WM = $row[9];
                $WS = $row[10];
                $WSS = $row[11];
                $WV = $row[12];
                $cnum = 0 + $cnum;   // make numeric
                for ($rnum = 2; $rnum <= 23; $rnum++) { 
                    $query = "INSERT INTO Teams (RaceNumber, ClubNumber, ClubName, MO, MM, MS, MSS, MV,
                       WO, WM, WS, WSS, WV)
                       VALUES ('$rnum', '$cnum', '$cname', '$MO', '$MM', '$MS', '$MSS', '$MV', 
                       '$WO', '$WM', '$WS', '$WSS', '$WV')";
                    $result2 = @mysql_query ($query);
                    if ($result2) { 
                        echo "<p>record for $rnum &nbsp; $cname created";
                    } else {
                        echo "<p>Error in batchUtilss on INSERT</p><p>" . mysql_error() . '</p>'; 
                    }  
                } // end of for loop
            }  // end of while loop 
        } else {
            echo '<p><b>No records met WHERE condition</b></p>';
        }  // end of if ($num)
    }  // end of was SELECT successful
?>
</body>
</html>