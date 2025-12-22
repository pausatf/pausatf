<?php
// ----------------------------------------------------------------------------*
// DOB6.php                                                                    *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This script browses on Last Name only on the DOB verification database.     *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) { // This begins an IF that processes the form if entered ('submit'),
                               // but just displays a blank form (bottom of code) if not  

// The form was entered, so this is 2nd pass (1st pass was to display blank screen for entry);
// Edit the entered fields:
    $msg = '';

// Check athlete name entered      
    if (strlen($_POST['nameEntered']) > 0) {
	$nameEntered = $_POST['nameEntered'];
    } else {
        $msg .= '<p><b>You did not enter a name.</b></p>'; 
    }  
    if ($msg == '') {    
        require_once ('/home/pausat/DOB.php');
        $name = $nameEntered;
        $query = "SELECT * FROM DOBNames 
                 WHERE LastName = '$nameEntered' ORDER BY FullName"; 
         
        $result = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result)) {  // was SELECT succcessful
            echo "<p>Error on Database. Contact Webmaster.</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result);   // if successful, get the number of rows 
        }
        if ($num == 0) {  
            echo "<p><b>Last Name:<h2><i> $nameEntered</i></h2> is not on file as verified.";
            echo '<p><h4>Check with Tony Williams by email <a href=mailto:"judgetonytracknfield@yahoo.com"><u>judgetonytracknfield@yahoo.com</u></a> or phone: 510-206-5403</h4>';
            echo '<p><b>When contacting the Youth Membership Chair please provide the following:';
            echo '<p>1. Name of youth athlete.<br>2. Name of club or unattached.<br>3. Current USATF membership number.<br>4. Describe proof of birth submitted.';
            echo '<p>Allow 1-2 weeks for athlete to be verified and show up on the web page.</b>';
            echo '<p>If you have additional membership related questions (i.e., how do<br>';
            echo "I join a different club or compete unattached) review the PA/USATF <a href='http://www.pausatf.org/data/membershipinfo.html'>Membership Information</a> document.";
            echo "<a href='http://www.pausatf.org/PHP/DOB6.php'><p>Return</a> to search screen.";
            exit();
        } else {  
            while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                 $clubno = $row[0];
                 $clubno = 0 + $clubno; 
                 $fullname = $row[1];

                 $query = "SELECT * FROM DOBClubs WHERE club=$clubno"; 
                 $result2 = @mysql_query ($query);  //Execute the SELECT
       
                 if (!($result2)) {  // was SELECT succcessful
                     echo "<p>Error in DOB6 pgm on SELECT</p><p>" . mysql_error() . '</p>';
                     exit();
                 } else {  
                     $num2 = mysql_num_rows($result2);   // if successful, get the number of rows 
                 }
                 if ($num2 == 0) {
                     $clubname = "club number not found";  
                 } else {
                     echo '<table>';
                     while ($row2 = mysql_fetch_array($result2, MYSQL_NUM)) {
                         $clubname = $row2[1];
                     } // end  2nd while 
                 }
                 echo "<h5><tr><td><i>$fullname</i></td><td>-</td><td>$clubname</td></tr></h5>";
             } // end of 1st while
             echo '</table>';
             echo "<a href='http://www.pausatf.org/PHP/DOB6.php'><p>Return</a> to search screen.";
             exit();
         } // end of if ($num = 0) 
    } else {  // form did not pass edits--end of if ($msg = '')
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
<title>DOB Add Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Youth Proof of Birth database by Last Name</legend>
<a href="/"><b>Home</b></a><br>
<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a>
<p><h2>Youth Proof of Birth Verified</h2>
<p><b><big><big>Use this search feature to confirm athlete's proof of birth has been received<br>
by the Pacific Association USATF Membership Office or Youth Membership Chair Tony Williams, SR.</big></big></b>
<p>
<p><b>Type in the last name of youth athlete.<br> 
Only youth athletes whose proof of birth meets the requirement rule by USATF,<br> 
and who are verified by the Youth Membership Chair will be displayed on the results list.<br>
If your athlete's name does not appear in Search Results contact Tony Williams at 510-206-5403<br>
or email at <a href=mailto:"judgetonytracknfield@yahoo.com"><u>judgetonytracknfield@yahoo.com</u></a></b>
<p><b><i>Note: the</i> LAST name <i>where the full name is 3 or 4 names long is the last one in order<br>
(e.g. John Smith Murphy-Brown Jr is searched under Murphy-Brown, but John Smith Murphy Brown Jr is searched under Brown)</i></b></p> 
<b>Enter Last Name:</b> <input type="text" name="nameEntered" size="60" maxlength="60" value="<?php if (isset($_POST['nameEntered'])) {echo $_POST['nameEntered'];} ?>"/> </i></p>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Validate Proof of Birth" /></div>
</form>
</body>
</html>