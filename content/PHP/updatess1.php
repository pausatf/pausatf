<?php
// ----------------------------------------------------------------------------*
// updatess1.php                                                               *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script handles the entry of a race number and a club number for the    * 
// update of the status of submitted scoresheets.                              *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) { // This begins an IF that processes the form if entered ('submit'),
                               // but just displays a blank form (bottom of code) if not  

// The form was entered, so this is 2nd pass (1st pass was to display blank screen for entry);
// Edit the entered fields:
    $msg = '';

// Check Race Number entered  
    if (strlen($_POST['rnumEntered']) > 0) {
	$rnumEntered = $_POST['rnumEntered'];
    } else {
        $msg .= '<p><b>You did not enter a race number.</b></p>'; 
    } 
// Check Club Number entered  
    if (strlen($_POST['cnumEntered']) > 0) {
	$cnumEntered = $_POST['cnumEntered'];
    } else {
        $msg .= '<p><b>You did not enter a club number.</b></p>'; 
    } 
      
    if ($rnumEntered & $cnumEntered) { 
        $rnumEntered = 0 + $rnumEntered;    // make numeric 
        $cnumEntered = 0 + $cnumEntered;   // make numeric
        if ($rnumEntered > 23) { 
            $msg .= '<p><b>Race number cannot exceed 23.</b></p>';
        }
    }

    if ($msg == '') {    
        require_once ('/home/pausat/dbss.php');

// Successful connection.  Check to see if the club number exists.
        
        $query = "SELECT * FROM RaceName   
                 WHERE RaceNumber = '$rnumEntered'"; 
         
        $result1 = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result1)) {  // was SELECT succcessful
            echo "<p>Error in updatess1 on SELECT Race $rnumEntered</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result1);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, Race exists
            echo "<p><b>The race number '$rnumEntered' is not on the database.  Consult the Listing of Races.";
            exit();
        } else {
            while ($row = mysql_fetch_array($result1, MYSQL_NUM)) {
                $query = "SELECT * FROM Teams   
                WHERE RaceNumber = '$rnumEntered' AND ClubNumber = '$cnumEntered'"; 
                $result2 = @mysql_query ($query);  //Execute the SELECT
                if (!($result2)) {  // was SELECT succcessful
                    echo "<p>Error in updatess1 on SELECT Teams for Race $rnumEntered and Club $cnumEntered</p><p>" . mysql_error() . '</p>';
                    exit();
                } else {  
                    $num = mysql_num_rows($result2);   // if successful, get the number of rows 
                }
                if ($num == 0) {  // if num rows returned 1 or more, race exists
                    echo "<p><b>Race $rnumEntered Club $cnumEntered is not on Teams database.";
                    exit();
                } else {
                    setcookie (rnumCookie, $rnumEntered);      // set cookies 
                    setcookie (cnumCookie, $cnumEntered);
                    header ("Location: http://www.pausatf.org/PHP/updatess2.php");  
                    exit();
                }  // race number not on Teams database (exited)
            } // end of while
        } // race number not on scoresheet database (exited)
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
<p>
<p><b>Select Race:</b> <select name="rnumEntered">
<option value="01">NorCal</option>
<option value="02">Standford 8K</option>
<option value="03">Across the Bay 12K</option>
<option value="04">Zippy 5K</option>
<option value="05">Marin 10K</option>
<option value="06">Shriners 8K</option>
<option value="07">FF Davis Mile</option>
<option value="08"">HP 10K</option>
<option value="09">Empire XC</option>
<option value="10">Santa Cruz XC</option>
<option value="11">Golden Gate XC</option>
<option value="12">Jamba Juice 5K XC</option>
<option value="13">Paso Robles 10K</option>
<option value="14">Garrin XC</option>
<option value="15">Presidio XC</option>
<option value="16">Humboldt Half</option>
<option value="17">Shoreline XC</option>
<option value="18">Tamalpa XC</option>
<option value="19">Fleet Feet XC</option>
<option value="20">Clarksburg</option>
<option value="21">PA Champs XC</option>
<option value="22"">Seagate 5K</option>
<option value="23">CIM</option>
<option value="24">Xmas Relays</option>
</select><p><b>Select Club:</b>
<select name="cnumEntered">
<option value="178">adidas Transports</option>
<option value="111">ASCIS Aggies</option>
<option value="104">Buffalo Chips</option>
<option value="269">Cal Triathlon</option>
<option value="143">Empire Runners</option>
<option value="195">Fleet Feet Sacramento</option>
<option value="135">Golden Valley Harriers</option>
<option value="124">Humboldt TC</option>
<option value="196">Iguanas</option>
<option value="115">Impalas</option>
<option value="113">New Balance Excelsior</option>
<option value="132">Nike Farm Team</option>
<option value="220">Pacific Striders</option>
<option value="116">River City Rebels</option>
<option value="233">Runniing Zone/Mizuno</option>
<option value="126">San Luis Distance Club</option>
<option value="137">Santa Cruz TC</option>
<option value="32">Silver State Striders</option>
<option value="154">San Jose Spartans</option>
<option value="100">Tamalpa</option>
<option value="177">UCSC Slugs</option>
<option value="133">Wed Night Laundry</option>
<option value="117">West Valley J&S</option>
<option value="110">West Valley TC</option>
<option value="119">Wolfpack Intrnl</option>
</select>
<p>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Get Scoresheet Status" /></div>
</form>
</td></tr></table><div>
</body>
</html>