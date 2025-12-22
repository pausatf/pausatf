<?php
// ----------------------------------------------------------------------------*
// update1.php                                                                 *
// Created Sept 2005                                                           *
// Created by Dan Preston                                                      *
// This script handles the deletion a club number                              *
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
        $query = "SELECT club_no FROM tblCLUBS 
                 WHERE club_no = '$cnum'"; 
         
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error in enter pgm on SELECT $cnum</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, club exists
            echo "<p><b>The club number '$cnum' is not on the database.  See Club Listing.";
            exit();
        } else {
             while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                 $cnum = $row[0];
             } // end of while 
             $query = "DELETE FROM tblCLUBS WHERE club_no ='$cnum'";
             $result = @mysql_query ($query); 
             if (!($result)) {  // was DELETE successful
                 echo "<p>Database error on delete for club number $cnum</p><p>" . mysql_error() . '</p>';
                 exit();
             } else {         
                 if (!(mysql_affected_rows() == 1)) {
                    echo "<p>Database error (aff rows) on delete for club number $cnum</p><p>" . mysql_error() . '</p>';
                    exit();
                 } else {
                     echo "<p>Club number $cnum successfully deleted.</p>";
                     exit();
                 } 
            }
        } 
    } else {  // (did not enter a club number)
        echo "<p><b> $msg </b></p>";
        echo '<p><b>Not a valid number. Please try again.</b></p>';
    } // end of edits if

}  // end of if at top of page to see if the form is already entered or not

// end of PHP script.  HTML form follows. Displays blank form if not already entered, sticky form for values if already entered.
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Club Number Deletion for PA Office</title>
</head>
<body>   
<center>
<h1><font color="blue">PA Office Deletion of a Club</font></h1>
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Club Number Entry for Deletion</legend>
<p><i><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?php if (isset($_POST['cnum'])) {echo $_POST['cnum'];} ?>"/> </i></p>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit Information" /></div>
</form>
</body>
</html>
