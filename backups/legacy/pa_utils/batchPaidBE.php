<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Batch Update Paid Status</title>
</head>
<body>
<?php
// ----------------------------------------------------------------------------*
// batchPaid                                                                   *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This is a batch program that updates paid status codes "B" and "E" to "Y".  *
// "B" is for Both, "E" is for Early for payments made in November.  They both *
// become "Y' in January.                                                      *
// 1/3/2007 changed to simply change all paid to 'N'.  PAOffice will update to *
//          "Y" as payments come in.  No more codes B and E.                   *  
// ----------------------------------------------------------------------------*
  
    require_once ('/home/pausat/db.php');

    $query = "SELECT * FROM tblCLUBS"; 

    $result = @mysql_query ($query);  // execute the SELECT

    if (!($result)) {  // was SELECT successful
       echo "<p>Database error in batchPaidBE on SELECT </p><p>" . mysql_error() . '</p>';
       exit();
    } else {         
        $num = mysql_num_rows($result);   
        if ($num > 0) {
            echo "number of tblCLUB records = $num";
            while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                $cnum = ($row[0]);
                if ($cnum > 0) {
                    $savepaid = ($row[4]);                
                    $query2 = "UPDATE tblCLUBS SET paid='N' WHERE club_no='$row[0]'";
                    $result2 = @mysql_query ($query2);

                    if (mysql_affected_rows() == 1) {
                        echo "<p>Update successful for Club Number $cnum</p>";
                    } else {
                        echo "<p>$row[0] is already a N</p><p>" . mysql_error() . '</p>';
                    }
                } 
            } // end of while loop
        }  else {  
            echo "<p><b>No rows returned</b></p>";
            exit();
        }  // end of if ($num)
       
    }  // end of was SELECT successful
 
?> 
</body>
</html>