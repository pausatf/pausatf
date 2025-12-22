<?php
// ----------------------------------------------------------------------------*
// enterP1.php                                                                 *
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script handles the entry of a club number for the entry of club        *
// profile information by a club into the PAUSATF clubs database.  If the club *
// number is found on the database, control is transferred to enterP2.         *
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
      
    if ($cnum) {   
        require_once ('db.php');
// Successful connection.  Check to see if the club number exists.
        $cnum = 0 + $cnum;               // Make $cnum numeric
        $query = "SELECT approved FROM tblCLUBS 
                 WHERE club_no = '$cnum'"; 
         
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error in enter pgm on SELECT $cnum</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, club exists
            echo "<p><b>The club number '$cnum' is not on the database.  Contact the PA Office.";
            exit();
        } else {
             while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                 $paid = $row[0];
             } // end of while 
 //          if ($paid == 'Y') {
           setcookie (cnum, $cnum); 
           header ("Location: http://www.pausatf.org/PHP/enterP2.php");  
           exit();
//           } else {
//               echo "<p><b>The club fees for number '$cnum' have NOT been marked as paid for this year. Contact the PA Office.";
//               exit();     
//           }
        }
    } else {  // (form did not pass edits--end of long string of nested if's)
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
<title>Club Profile Club Number Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<div align="left">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><font size="2"><a href="/"><b>Home</b></a><br>
<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
<td valign="top">
<h1><font color="blue">PA/USATF Club Profile Entry</font></h1>
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Club Profile Entry</legend>
<p><i><b>Before information describing a club (the profile) can be entered,<br>
 the club must be created and approved by the PA Office, which issues the club number.</b></i><br>
<p><i><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?php if (isset($_POST['cnum'])) {echo $_POST['cnum'];} ?>"/> </i></p>
</fieldset>
<div align="left"><input type="submit" name="submit" value="Submit Information" /></div>
</form>
</td></tr></table></div>
</body>
</html>
