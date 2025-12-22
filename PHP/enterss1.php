<?php
// ----------------------------------------------------------------------------*
// enterss1.php                                                                *
// Created December 2005                                                       *
// Created by Dan Preston                                                      *
// This script handles the entry of a race number and a club number for the    * 
// display of the status of submitted scoresheets.                             *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) { // This begins an IF that processes the form if entered ('submit'),
                               // but just displays a blank form (bottom of code) if not  

// The form was entered, so this is 2nd pass (1st pass was to display blank screen for entry);
// Edit the entered fields:
    $msg = '';

// Check Race entered  
    if (strlen($_POST['rnumEntered']) > 0) {
	$rnumEntered = $_POST['rnumEntered'];
    } else {
        $msg .= '<p><b>You did not select a race.</b></p>'; 
    } 
// Check Club entered  
    if (strlen($_POST['cnumEntered']) > 0) {
	$cnumEntered = $_POST['cnumEntered'];
    } else {
        $msg .= '<p><b>You did not select a club.</b></p>'; 
    } 
      
    if ($rnumEntered & $cnumEntered) { 
        $rnumEntered = 0 + $rnumEntered;    // make numeric
        $cnumEntered = 0 + $cnumEntered; 
    }

    if ($msg == '') {    
        require_once ('/home/pausat/dbss.php');

// Successful connection.  Check to see if the club number exists.
        
        $query = "SELECT * FROM RaceName   
                 WHERE RaceNumber = '$rnumEntered'"; 
         
        $result1 = @mysql_query ($query);  //Execute the SELECT
       
        if (!($result1)) {  // was SELECT succcessful
            echo "<p>Error in enterss1 on SELECT Scoresheets $rnumEntered</p><p>" . mysql_error() . '</p>';
            exit();
        } else {  
            $num = mysql_num_rows($result1);   // if successful, get the number of rows 
        }
        if ($num == 0) {  // if num rows returned 1 or more, Race exists
            echo "<p><b>The race number '$rnumEntered' is not on the database. Contact webmaster.";
            exit();
        } else {
            while ($row = mysql_fetch_array($result1, MYSQL_NUM)) {
                $name = $row[1];
                $date = $row[2];
                echo "<h3>Scoresheet status for $name</h3>
                      <i>scheduled $date</i><br>";
                $query = "SELECT * FROM Teams   
                WHERE RaceNumber = '$rnumEntered' AND ClubNumber = '$cnumEntered'"; 
                $result2 = @mysql_query ($query);  //Execute the SELECT
                if (!($result2)) {  // was SELECT succcessful
                    echo "<p>Error in enterss1 on SELECT Teams for Race $rnumEntered Team $cnumEntered</p><p>" . mysql_error() . '</p>';
                    exit();
                } else {  
                    $num = mysql_num_rows($result2);   // if successful, get the number of rows 
                }
                if ($num == 0) {  // if num rows returned 1 or more, race exists
                    echo "<p><b>Error in enterss1. Race $rnumEntered Team $cnumEntered is not on database.  Contact webmaster.";
                    exit();
                } else {
                    while ($row = mysql_fetch_array($result2, MYSQL_NUM)) {
                         $cnum = $row[1];
                         $cname = $row[2];
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
                         echo "<h3>For $cname</h3>";
                         echo "<p>Open Men &nbsp; $MO &nbsp; B Team &nbsp;$MOB<br>";
                         echo "<p>Master Men &nbsp; $MM &nbsp; B Team &nbsp;$MMB<br>";
                         echo "<p>Senior Men &nbsp; $MS &nbsp; B Team &nbsp;$MSB<br>";
                         echo "<p>Super Senior Men &nbsp; $MSS &nbsp; B Team &nbsp;$MSSB<br>";
                         echo "<p>Veteran Men &nbsp; $MV &nbsp; B Team &nbsp;$MVB<br>";
                         echo "<p>Open Women &nbsp; $WO &nbsp; B Team &nbsp;$WOB<br>";
                         echo "<p>Master Women &nbsp; $WM &nbsp; B Team &nbsp;$WMB<br>";
                         echo "<p>Senior Women &nbsp; $WS &nbsp; B Team &nbsp;$WSB<br>";
                         echo "<p>Super Senior Women &nbsp; $WSS &nbsp; B Team &nbsp;$WSSB<br>";
                         echo "<p>Veteran Women &nbsp; $WV &nbsp; B Team &nbsp;$WVB<br><hr><p>";
                    }  // end of while 
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
<title>Scoresheet Status Entry Form</title>
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
<fieldset><legend>Scoresheet Status Entry Form</legend>
<p><i><b>The Road or XC Scorer updates this database when he receives a scoresheet.<br>
If your status is N (for No) and you submitted a scoresheet, but did not receive your confirmation copy,<br>
(and you have allowed time for the Scorer to do the update) then send the scoresheet <b>directly</b> to the scorer:<br>
<a href="mailto:tlbernhard@comcast.net">Road Scorer&nbsp;&nbsp;&nbsp;<a href="mailto:djpreston@comcast.net">XC Scorer<br>
<p>
<p><b>Select Race:</b> <select name="rnumEntered">
<option value="01">NorCal</option>
<option value="02">Standford 8K</option>
<option value="03">Across the Bay 12K</option>
<option value="04">Zippy 5K</option>
<option value="05">Marin 10K</option>
<option value="06">Shriners 8K</option>
<option value="07">FF Davis Mile</option>
<option value="08">FF Davis Mile</option>
<option value="09">Susan 8K</option>
<option value="10">Santa Cruz XC</option>
<option value="11">Empire XC</option>
<option value="12">Golden Gate XC</option>
<option value="13">Jamba Juice 5K</option>
<option value="14">Paso Robles 10K</option>
<option value="15">Presidio XC</option>
<option value="16">Folsom (Aggies) XC</option>
<option value="17">Humboldt Half</option>
<option value="18">Shoreline XC</option>
<option value="19">Tamalpa XC</option>
<option value="20">Clarksburg</option>
<option value="21">PA Champs XC</option>
<option value="22">Seagate 5K</option>
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