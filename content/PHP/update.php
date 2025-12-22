<?php
// ----------------------------------------------------------------------------*
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script handles the entry of a new club into the PAUSATF clubs database *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) { // This begins an IF that processes the form if entered ('submit'),
                               // but just displays a blank form (bottom of code) if not  

// The form was entered, so this is 2nd pass (1st pass was to display blank screen for entry);
// Edit the entered fields:
    $msg = '';

// Check Club PAUSATF Number  
    if (strlen($_POST['cnum']) > 0) {
	$cnum = $_POST['cnum'];
    } else {
        $cnum = FALSE;
        $msg .= '<p><b>You did not enter a club number.</b></p>'; 
    } 
// Check Club Name:
    if (strlen($_POST['cname']) > 0) {
	$_POST['cname'] = stripslashes($_POST['cname']);
        $cname = $_POST['cname']; 
    } else { 
	$cname = FALSE;
	$msg .= '<p><b>You did not enter a club name.</b></p>';
    }	
// Check Club Short Name:
    if (strlen($_POST['csname']) > 0) {
	$_POST['csname'] = stripslashes($_POST['csname']);
        $csname = $_POST['csname']; 
    } else {
        $csname = FALSE;
        $msg .= '<p><b>You did not enter a club short name.</b></p>'; 
    }  
// Check Primary Contact Name:
    if (strlen($_POST['pcontact']) > 0) {
	$_POST['pcontact'] = stripslashes($_POST['pcontact']);
        $pcontact = $_POST['pcontact']; 
    } else { 
	$pcontact = FALSE;
	$msg .= '<p><b>You did not enter a primary contact name.</b></p>';
    }	
// Check Primary Contact Phone:
    if (strlen($_POST['pphone']) > 0) {
	$_POST['pphone'] = stripslashes($_POST['pphone']);
        $pphone = $_POST['pphone']; 
    } else { 
	$pphone = FALSE;
	$msg .= '<p><b>You did not enter a primary contact phone number.</b></p>';
    }	
// Check Paid Indicator (Y/N)
    if (strlen($_POST['paidYN']) > 0) {
        $paidYN = $_POST['paidYN'];
        switch ($paidYN) {
        case 'Y':
        break;
        case 'N':
        break;
        default:
        $paidYN=FALSE;
        $msg .= '<p><b>You did not enter a Y or N for the Approved Status</b></p>'; 
        break;
        } 
    } else {  
        $paidYN=FALSE;
        $msg .= '<p><b>You did not enter a Y or N for the Approved Status.</b></p>';
    }    


//            If all fields passed edits, update database:
//           (start of 2nd string of nested if's, 
//            still within first string of nested ifs)

    
      
    if ($cnum && $cname && $csname && $pcontact && $pphone && $paidYN) {   //if any of these set to false, fails if
        require_once ('db.php');
// Successful connection.  Check to see if club number already exists.
        $cnum = 0 + $cnum;               // Make $cnum numeric
        $query = "SELECT * FROM tblCLUBS 
                 WHERE club_no = '$cnum'"; 
         
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error in enter pgm on SELECT $cnum</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if (!($num == 0)) {  // if num rows returned 1 or more, club already exists
            echo "<p><b>The club number '$cnum' already exists on the database.";
            exit();
        } else { 
//       insert club record  
            $query = "INSERT INTO tblCLUBS (club_no, club_name, short_name, approved, primary_contact, primary_phone)
                     VALUES ('$cnum', '$cname', '$csname', '$paidYN', '$pcontact', '$pphone')";
            $result = @mysql_query ($query);
        }
        if (!($result)) { 
            echo "<p>Database error on Insert $cnum</p><p>" . mysql_error() . '</p>';
            exit();
        } 
        if ($result) {
            echo "<p><b>New Club $csname has been added to the database.<br>Profile data needs to be added by the Club</b></p>";
             exit();   
        } else {  
            echo '<p>System database error on insert member</p><p>' . mysql_error() . '</p>';
            exit();
        } 
    } else {  // (form did not pass edits)
        echo "<p><b> $msg </b></p>";
        echo '<p><b>Please try again.</b></p>';
    } // end of edits if

}  // end of if at top of page to see if the form is already entered or not

// end of PHP script.  HTML form follows. Displays blank form if not already entered, sticky form for values if already entered.
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>New Club Entry Form</title>
</head>
<body>   
<center>
<h1><font color="blue">PA/USATF Club Entry</font></h1>
<h2>PA Office, Folsom CA</h2>
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>New Club Entry</legend>
<p><i><b>All fields are required.  When done with entry, click on the Submit Information box at the bottom.</b></i><br>
<p><i><b>Enter Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?php if (isset($_POST['cnum'])) {echo $_POST['cnum'];} ?>"/> </i></p>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?php if (isset($_POST['cname'])) {echo $_POST['cname'];} ?>"/> </i></p>
<p><b>Club Short Name (used for displays on website):</b> <input type="text" name="csname" size="20" maxlength="20" value="<?php if (isset($_POST['csname'])) {echo $_POST['csname'];} ?>"/> </i> </p>
<p><b>Has club been approved? (Y/N): </b> <input type="radio" name="paidYN" value="Y" /> YES
<input type = "radio" name="paidYN" value="N" /> NO 
<p><b>Primary Contact: <input type="text" name="pcontact" size="50" maxlength="50" value="<?php if (isset($_POST['pcontact'])) {echo $_POST['pcontact'];} ?>"/> </i></p>
<p><b>Primary Phone: <input type="text" name="pphone" size="50" maxlength="50" value="<?php if (isset($_POST['pphone'])) {echo $_POST['pphone'];} ?>"/> </i></p>

</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit Information" /></div>
</form>
</body>
</html>
