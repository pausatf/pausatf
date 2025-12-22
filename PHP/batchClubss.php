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
// batchClubss                                                                  *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script is a batch utility program for batch updates to the database    *
// Can be used to generate database entries for a new club                     *
// ----------------------------------------------------------------------------*
    require_once ('/home/pausat/dbss.php');

   $cnum = 999;          // initialize new club variables (club number and name, etc.)
   $cname = "Name of new Team";
   $MO = "N";
   $MM = "N";
   $MS = "N";
   $MSS = "N";
   $MV = "N";
   $WO = "N";
   $WM = "N";
   $WS = "N";
   $WSS = "N";
   $WV = "N";
    
   for ($rnum = 1; $rnum <= 23; $rnum++) { 
       $query = "INSERT INTO Teams (RaceNumber, ClubNumber, ClubName, MO, MM, MS, MSS, MV,
             WO, WM, WS, WSS, WV)
             VALUES ('$rnum', '$cnum', '$cname', '$MO', '$MM', '$MS', '$MSS', '$MV', 
                     '$WO', '$WM', '$WS', '$WSS', '$WV')";
       $result = @mysql_query ($query);
       if ($result) { 
           echo "<p>record for $rnum &nbsp; $cname created";
       } else {
           echo "<p>Error in batchClubss on INSERT Race $rnum Club $cnum</p><p>" . mysql_error() . '</p>'; 
       }  
   }   // end of for loop
?>
</body>
</html>