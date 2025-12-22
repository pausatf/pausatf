<?php
// ----------------------------------------------------------------------------*
// DOB1.php                                                                    *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This script handles the entry of a youth club number for the display of     *
// names of athletes for that club who have had their birth certificate        *
// verified.                                                                   *
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
        require_once ('/home/pausat/DOB.php');
// Successful connection.  Check to see if the club number exists.
        $cnum = 0 + $cnum;               // Make $cnum numeric
        $query = "SELECT FullName FROM DOBNames 
                 WHERE Club = '$cnum'"; 
         
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error in enter pgm on SELECT $cnum</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, club exists
            echo "<p><b>Club number '$cnum' does not have any athletes listed.";
            exit();
        } else {
             echo "The following&nbsp; $num &nbsp; youth have their birth certificate verified:";
             while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                 $name = $row[0];
                 echo "<p><b>$name</b></p>";
             } // end of while
             exit(); 
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
<title>DOB Club Number Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<div align="left">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><font size="2"><a href="/"><b>Home</b></a><br>
<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
<td valign="top">
<h1><font color="blue">PA/USATF Birth Certificate Verification</font></h1>
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>DOB Club Number Entry</legend>
<p><i><b>Athletes are listed by Club or as Unattached<br>
If you do not know your club number, go back and get it from the listing (Unattached Boys is 600, girls, 800).</b></i><br>
<p><i><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?php if (isset($_POST['cnum'])) {echo $_POST['cnum'];} ?>"/> </i></p>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit Information" /></div>
</form>
</td></tr></table></div>
</body>
</html>