<?php
// ----------------------------------------------------------------------------*
// update2.php                                                                 *
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script handles the update of primary contact and paid status by the    *
// PA Office.  It is called by update1 which captures and passes club number.  *                                                                      *
// ----------------------------------------------------------------------------*
require_once ('db.php');

if (!(strlen($_POST['pcontact'])) > 0) {  // Has screen been updated and returned yet?
    $cnum = $_COOKIE[cnum];             // if not, read database with club number passed, fill screen
    $cnum = 0 + $cnum;
    
    $query = "SELECT * FROM tblCLUBS WHERE club_no = '$cnum'";

    $result = @mysql_query ($query);  // execute the SELECT

    if (!($result)) {  // was SELECT successful
       echo "<p>Database error in enterP2 on SELECT $cnum</p><p>" . mysql_error() . '</p>';
       exit();
    } else {         
        $num = mysql_num_rows($result);   // see if a row was returned
        if ($num > 0) {
            while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
               $_POST['cname'] = $row[1];
               $_POST['paidYN'] = $row[4];
               $_POST['pcontact'] = $row[7];
               $_POST['pphone'] = $row[8];       // Fill screen with data from the database
               $cname = $row[1];
               $paidYN = $row[4];
               $cdate = $row[5]; 
               $pcontact = $row[7];                
               $pphone = $row[8];
           } // end of while loop
        } else {  
            echo "<p><b>No match found for '$cnum'</b></p>";  // this should not happen. See update1.
            exit();
        }  // end of if ($num)
    }  // end of was SELECT successful
} else {
    $cnum = $_COOKIE[cnum];            // if screen has something on it

    // protect $_POST variables
    // from http://stackoverflow.com/questions/4223028/
    // mysql-real-escape-string-for-entire-request-array-or-need-to-loop-through-i
               
    $s_POST = array_map('mysql_real_escape_string', $_POST);
               

    $pcontact = $s_POST['pcontact'];    // get variables from screen
    $pphone = $s_POST['pphone'];        // which has been updated by user   
    $paidYN = $s_POST['paidYN'];
    $cname = $s_POST['cname']; 
    
// Edit the entered fields:
    $msg = '';
// Check Club Name:
    if (strlen($s_POST['cname']) > 0) {
	$s_POST['cname'] = stripslashes($s_POST['cname']);
        $cname = $s_POST['cname']; 
    } else { 
	$cname = FALSE;
	$msg .= '<p><b>Club name is missing.</b></p>';
    }	
// Check Primary Contact Name:
    if (strlen($s_POST['pcontact']) > 0) {
	$s_POST['pcontact'] = stripslashes($s_POST['pcontact']);
        $pcontact = $s_POST['pcontact']; 
    } else { 
	$pcontact = FALSE;
	$msg .= '<p><b>Primary Contact name is missing.</b></p>';
    }	
// Check Primary Contact Phone:
    if (strlen($s_POST['pphone']) > 0) {
	$s_POST['pphone'] = stripslashes($s_POST['pphone']);
        $pphone = $s_POST['pphone']; 
    } else { 
	$pphone = FALSE;
	$msg .= '<p><b>Primary Contact phone number is missing.</b></p>';
    }	
// Check Paid Indicator (Y/N)
    if (strlen($s_POST['paidYN']) > 0) {
        $paidYN = $s_POST['paidYN'];
        switch ($paidYN) {
        case 'Y':
        break;
        case 'N':
        break;
        default:
        $paidYN=FALSE;
        $msg .= '<p><b>Approved Status for current year must be Y or N</b></p>'; 
        break;
        }  // end of switch
    } else {  
        $paidYN=FALSE;
        $msg .= '<p><b>Approved Status for current year is missing.</b></p>';
    } 
    if ($cname && $pcontact && $pphone && $paidYN) { 
//      If all fields passed edits, update database:
        $cnum = 0 + $cnum;
        require_once ('db.php');

// do the update. 
        $query = "UPDATE tblCLUBS SET club_name='$cname', primary_contact='$pcontact', primary_phone='$pphone', 
                  approved = '$paidYN', update_date=CURDATE() 
                  WHERE club_no='$cnum'";
        
        $result = @mysql_query ($query);

        if (mysql_affected_rows() == 1) {
            echo "Update successful for $cname";
            exit();
        } else {
            if (mysql_affected_rows() == 0) { 
                echo "Nothing changed.  Query only.";
            } else {
                echo "<p>Database error in enterP2 on UPDATE $cnum</p><p>" . mysql_error() . '</p>';
                mysql_close();
                exit();
            } 
        } 
    } else {
      die($msg);
    }          
}  // end of first if at top of page

// end of PHP script.  HTML form follows. 
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Update Primary Contact</title>
</head>
<body>   
<center>
<h1><font color="blue">PA Office Update</font></h1>
<h2>PA Office, Folsom CA</h2>
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Update PA Office Info</legend>
<p><i><b>All fields are required.  When done with entry, click on the Submit Information box at the bottom.</b></i></p><br>
<p><b>Club Number:</b>&nbsp; <?php echo $cnum; ?></p> 
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?php if (isset($_POST['cname'])) {echo $_POST['cname'];} ?>" /> </p> 
<p><b>Last Updated:</b>&nbsp; <?php echo $cdate; ?> </p> 
<p><b>Primary Contact:</b> <input type="text" name="pcontact" size="50" maxlength="50" value="<?php if (isset($_POST['pcontact'])) {echo $_POST['pcontact'];} ?>" /> </p>
<p><b>Primary Phone: <input type="text" name="pphone" size="50" maxlength="50" value="<?php if (isset($_POST['pphone'])) {echo $_POST['pphone'];} ?>" /> </p>
<p><b>Approved Status (Y/N): <input type="text" name="paidYN" size="1" maxlength="1" value="<?php if (isset($_POST['paidYN'])) {echo 
$_POST['paidYN'];} ?>" /> </p> 
</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit Update" /></div>
</form>
</body>
</html>
