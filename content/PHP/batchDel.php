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
// batchlist                                                                   *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script is a batch utility program for batch updates to the database    *
// ----------------------------------------------------------------------------*
    require_once ('/home/pausat/dbss.php');
    
    for ($rnum = 2; $rnum <= 23; $rnum++) { 
       $query = "DELETE FROM Teams WHERE RaceNumber = $rnum";
       $result = @mysql_query ($query);
       if ($result) { 
           echo "<p>record for $rnum deleted";
       } else {
           echo "<p>Error in batchDelss on DELETE</p><p>" . mysql_error() . '</p>'; 
       } 
   }  // end of For loop  
?>
</body>
</html>