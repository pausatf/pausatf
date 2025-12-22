<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Batch Update</title>
</head>
<body>
<?php
// ----------------------------------------------------------------------------*
// batchlist                                                                   *
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script creates a listing of clubs with primary contact and paid status *
// information.                                                                *
// ----------------------------------------------------------------------------*
    require_once ('db.php');
    
    $query = "SELECT club_no, club_name, approved, primary_contact, primary_phone FROM tblCLUBS 
              ORDER BY club_no";

    $result2 = @mysql_query ($query);  // execute the SELECT

    if (!($result2)) {  // was SELECT successful
       echo "<p>Error in batchlist on SELECT</p><p>" . mysql_error() . '</p>';
       exit();
    } else {         
        $num = mysql_num_rows($result2);   // see if a row was returned
        if ($num > 0) {
            echo "<p><h2><font color='red'>List of Active Clubs</font></h2></p>";
            echo "<p><h3><i>with current year's approved status</i></h3></p>";    
            echo "<p>Club#, Club Name, Contact, Phone, Approved Status (Y/N)</p>"; 
                while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
                    $cnum = $row[0];
                    $cname = $row[1];
                    $paid = $row[2];
                    $pcontact = $row[3];
                    $pphone = $row[4];
                    switch ($paid) {
                        case 'B';
                            $paid = 'Y';
                            break;
                        case 'E';        // this "case" goes in Nov 1
                            $paid = 'Y';  // and comes out Jan 1
                            break;       // (paying "Early" in Nov makes
                        case '';         // them paid for current year too) 
                            $paid = 'N'; 
                    }  
                    if (!($cnum == 0)) {
                        echo "<p>$cnum &nbsp;&nbsp; $cname<br>$pcontact &nbsp; $pphone &nbsp; $paid<br></p>";
                    }
                } // end of while loop
            echo '<p><b><hr></b></p>'; 
            exit();
        } else {  
            echo '<p><b>No records met WHERE condition</b></p>';
        }  // end of if ($num)
    }  // end of was SELECT successful
?>
</body>
</html>
