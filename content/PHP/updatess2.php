<?php
// ----------------------------------------------------------------------------*
// updatess2.php                                                               *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script handles the update of the status of submitted scoresheets.      *
// ----------------------------------------------------------------------------*
$rnumCookie = $_COOKIE[rnumCookie];
$cnumCookie = $_COOKIE[cnumCookie];
$rnumCookie = 0 + $rnumCookie;  // make numeric
$cnumCookie = 0 + $cnumCookie;
if (!(strlen($_POST['MOEntered'])) > 0 )  {  // Has screen been updated and returned yet?
    require_once ('/home/pausat/dbss.php');  // if not, read database with rnumCookie passed, fill screen
// First get RaceName Table entry for this race
    $query = "SELECT RaceNumber, Name, RaceDate FROM RaceName WHERE RaceNumber = $rnumCookie"; 
    $result1 = @mysql_query ($query);  //Execute the SELECT
       
    if (!($result1)) {  // was SELECT succcessful
        echo "<p>Error in enterss2 on SELECT RaceName $rnumCookie</p><p>" . mysql_error() . '</p>';
        exit();
    } else {  
        $num = mysql_num_rows($result1);   // if successful, should be one row 
    }
    if ($num == 0) {  // if num rows returned 0, Race Number not there
        echo "<p><b>The race number $rnumCookie is not on the database.  Consult the Listing of Races.";
        exit();
    } else {
        while ($row = mysql_fetch_array($result1, MYSQL_NUM)) {
            $name = $row[1];
            $date = $row[2];
            echo "<h3>Scoresheet status for $name</h3>
                  <i>scheduled $date</i><br>";
            $query = "SELECT * FROM Teams WHERE RaceNumber = $rnumCookie AND ClubNumber = $cnumCookie"; 
            $result2 = @mysql_query ($query);  //Execute the SELECT
            if (!($result2)) {  // was SELECT succcessful
                echo "<p>Error in updatess2 on SELECT Teams for Race $rnumCookie Club $cnumCookie</p><p>" . mysql_error() . '</p>';
                exit();
            } else {  
                $num = mysql_num_rows($result2);   // if successful, get the number of rows 
            }
            if ($num == 0) {  // if num rows returned 1 or more, club entries exist for ths race
                echo "<p><b>Database Error. Nothing found for Race Number $rnumCookie Club Number $cnumCookie.  Contact webmaster.";
                exit();
            } else {
                while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
                    $cnum = $row[1];                // populate variables with database values
                    $savecname = $row[2];
                    $MO = $row[3];    
                    $MM = $row[4];
                    $MS = $row[5];    
                    $MSS = $row[6];
                    $MV = $row[7];
                    $WO = $row[8];    
                    $WM = $row[9];
                    $WS = $row[10];    
                    $WSS = $row[11];
                    $WV = $row[12];
                    $MOB = $row[13];    
                    $MMB = $row[14];
                    $MSB = $row[15];    
                    $MSSB = $row[16];
                    $MVB = $row[17];
                    $WOB = $row[18];    
                    $WMB = $row[19];
                    $WSB = $row[20];    
                    $WSSB = $row[21];
                    $WVB = $row[22];
                    $cnum = 0 + $cnum;   // make numeric
                    echo "<h3>For $cname</h3>";
                    $_POST['MOEntered'] = $MO;      // populate screen with database values
                    $_POST['MMEntered'] = $MM;
                    $_POST['MSEntered'] = $MS;
                    $_POST['MSSEntered'] = $MSS;
                    $_POST['MVEntered'] = $MV;
                    $_POST['WOEntered'] = $WO;
                    $_POST['WMEntered'] = $WM;
                    $_POST['WSEntered'] = $WS;
                    $_POST['WSSEntered'] = $WSS;
                    $_POST['WVEntered'] = $WV;
                    $_POST['MOBEntered'] = $MOB;      
                    $_POST['MMBEntered'] = $MMB;
                    $_POST['MSBEntered'] = $MSB;
                    $_POST['MSSBEntered'] = $MSSB;
                    $_POST['MVBEntered'] = $MVB;
                    $_POST['WOBEntered'] = $WOB;
                    $_POST['WMBEntered'] = $WMB;
                    $_POST['WSBEntered'] = $WSB;
                    $_POST['WSSBEntered'] = $WSSB;
                    $_POST['WVBEntered'] = $WVB;
                    $_POST['savecname'] = $savecname; 
                }  // end of while 
            }  // race number not in Teams Table (exited)
        } // end of while
    } // race number not in RaceName Table (exited)
} else {   // second pass, screen was displayed and updated
    $MO = $_POST['MOEntered'];               // reset the variables from the screen
    $MM = $_POST['MMEntered'];           // which has been updated by the user 
    $MS = $_POST['MSEntered'];
    $MSS = $_POST['MSSEntered'];
    $MV = $_POST['MVEntered'];
    $WO = $_POST['WOEntered'];
    $WM = $_POST['WMSEntered'];
    $WS = $_POST['WSEntered']; 
    $WSS = $_POST['WSSEntered'];
    $WV = $_POST['WVEntered'];
    $MOB = $_POST['MOBEntered'];               
    $MMB = $_POST['MMBEntered'];           
    $MSB = $_POST['MSBEntered'];
    $MSSB = $_POST['MSSBEntered'];
    $MVB = $_POST['MVBEntered'];
    $WOB = $_POST['WOBEntered'];
    $WMB = $_POST['WMSBEntered'];
    $WSB = $_POST['WSBEntered']; 
    $WSSB = $_POST['WSSBEntered'];
    $WVB = $_POST['WVBEntered'];
    $cname = $_POST['savecname'];
// do the update:
    require_once ('/home/pausat/dbss.php');
    $query = "UPDATE Teams SET ClubName = '$cname', MO='$MO', MM='$MM', MS='$MS', MSS='$MSS', MV='$MV', 
              WO='$WO', WM='$WO', WS='$WS', WSS='$WSS', WV='$WV', MOB='$MOB', MMB='$MMB', MSB='$MSB', 
              MSSB='$MSSB', MVB='$MVB', WOB='$WOB', WMB='$WOB', WSB='$WSB', WSSB='$WSSB', WVB='$WVB' WHERE RaceNumber=$rnumCookie AND ClubNumber=$cnumCookie";
    $result = @mysql_query ($query);
    $return = mysql_affected_rows();
    if ($return == 0) {
        echo "<p>No update occurred because nothing changed.";
    } else {
        if ($return < 0) {   // if nothing changed, return is 0; if anything changes, it's 1; -1 for error
            echo "<p>Return code $return error in updatess2 for Race $rnumCookie and club $cnumCookie on UPDATE</p><p>" . mysql_error() . '</p>';
            mysql_close();
            exit();
        } else {
            echo "<p><h3>Update of Club $cnumCookie for Race $rnumCookie was successful</h3></p>"; 
        }
    } 
}  // end of if at top of page 
// end of PHP script.  HTML form follows. Displays blank form if not already entered, sticky form for values if already entered.
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>Scoresheet Status Update Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<div align="left">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><font size="2"><a href="/"><b>Home</b></a><br>
<a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
<td valign="top">
</center> 
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Scoresheet Status Update Form</legend>
<p><h2>Update of Scoresheet Submittal Status</h2>
<p><b>Race Number:&nbsp;</b><?php echo $rnumCookie; ?> 
<p><b>Club Number:&nbsp;</b><?php echo $cnumCookie; ?> 
<p><b>Mens Open Scoresheet Received:&nbsp;</b><input type="text" name="MOEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MOEntered'])) {echo $_POST['MOEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="MOBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MOBEntered'])) {echo $_POST['MOBEntered'];} ?>"/> </i></p>
<p><b>Mens Master Scoresheet Received:&nbsp;</b><input type="text" name="MMEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MMEntered'])) {echo $_POST['MMEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="MMBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MMBEntered'])) {echo $_POST['MMBEntered'];} ?>"/> </i></p>
<p><b>Mens Senior Scoresheet Received:&nbsp;</b><input type="text" name="MSEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MSEntered'])) {echo $_POST['MSEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="MSBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MSBEntered'])) {echo $_POST['MSBEntered'];} ?>"/> </i></p>
<p><b>Mens Super Senior Scoresheet Received:&nbsp;</b><input type="text" name="MSSEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MSSEntered'])) {echo $_POST['MSSEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="MSSBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MSSBEntered'])) {echo $_POST['MSSBEntered'];} ?>"/> </i></p>
<p><b>Mens Veteran Scoresheet Received:&nbsp;</b><input type="text" name="MVEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MVEntered'])) {echo $_POST['MVEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="MVBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['MVBEntered'])) {echo $_POST['MVBEntered'];} ?>"/> </i><p>
<p><b>Womens Open Scoresheet Received:&nbsp;</b><input type="text" name="WOEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WOEntered'])) {echo $_POST['WOEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="WOBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WOBEntered'])) {echo $_POST['WOBEntered'];} ?>"/> </i></p>
<p><b>Womens Master Scoresheet Received:&nbsp;</b><input type="text" name="WMEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WMEntered'])) {echo $_POST['WMEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="WMBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WMBEntered'])) {echo $_POST['WMBEntered'];} ?>"/> </i></p>
<p><b>Womens Senior Scoresheet Received:&nbsp;</b><input type="text" name="WSEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WSEntered'])) {echo $_POST['WSEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="WSBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WSBEntered'])) {echo $_POST['WSBEntered'];} ?>"/> </i></p>
<p><b>Womens Super Senior Scoresheet Received:&nbsp;</b><input type="text" name="WSSEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WSSEntered'])) {echo $_POST['WSSEntered'];} ?>"/> </i>
<b>B Team:&nbsp;</b><input type="text" name="WSSBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WSSBEntered'])) {echo $_POST['WSSBEntered'];} ?>"/> </i></p>
<p><b>Womens Veteran Scoresheet Received:</b><input type="text" name="WVEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WVEntered'])) {echo $_POST['WVEntered'];} ?>"/> </i>
<b>B Team:</b><input type="text" name="WVBEntered" size="1" maxlength="1" value="<?php if (isset($_POST['WVBEntered'])) {echo $_POST['WVBEntered'];} ?>"/> </i></p>                                
<input type="hidden" name="savecname" size="25" value="<?php echo $_POST['savecname']; ?>" />
<hr>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit Update" /></div>
</form>
</td></tr></table><div>
</body>
</html>