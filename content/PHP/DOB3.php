<?php
// ----------------------------------------------------------------------------*
// DOB3.php                                                                    *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This script handles the addition of an athlete on the DOB verification      * 
// database.  It is available only to the administrator (Tony Willliams).      *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) { // This begins an IF that processes the form if entered ('submit'),
                               // but just displays a blank form (bottom of code) if not  

// The form was entered, so this is 2nd pass (1st pass was to display blank screen for entry);
// Edit the entered fields:
    $msg = '';

// Check Club entered  
    if (strlen($_POST['cnumEntered']) > 0) {
	$cnumEntered = $_POST['cnumEntered'];
    } else {
        $msg .= '<p><b>You did not select a club.</b></p>'; 
    } 
// Check athlete Full name entered      
    if (strlen($_POST['nameEntered']) > 0) {
	$nameEntered = $_POST['nameEntered'];
    } else {
        $msg .= '<p><b>You did not enter a Full name.</b></p>'; 
    } 
// Check athlete Last name entered      
    if (strlen($_POST['LnameEntered']) > 0) {
	$LnameEntered = $_POST['LnameEntered'];
    } else {
        $msg .= '<p><b>You did not enter a Last name.</b></p>'; 
    }        
    if ($msg == '') {    
        require_once ('/home/pausat/DOB.php');
        $cnum = $cnumEntered;
        $name = $nameEntered;
        $lname = $LnameEntered; 
        $query = "INSERT INTO DOBNames VALUES ('$cnum', '$name', '$lname', NULL)";
        $result = @mysql_query ($query);
        if (!($result)) { 
            echo "Unable to add record for Club $cnum for $name last name $lname. Contact webmaster";
            exit();
        } else {
            echo "$name in club $cnum successfully added";
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
<title>DOB Add Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet"></head>
</head>
<body>
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>DOB Add Name Form</legend>
<p><b><i>Select the Club and enter the name of the athlete to be added.</i><b>
<p><b>Select Club:</b>
<select name="cnumEntered">
<option value="2">East Bay Heat Track Club</option>
<option value="6">Hilltop Speed</option>
<option value="7">Diablo Valley Club</option>
<option value="8">Godspeed Wings</option>
<option value="9">Palo Alto Lightning</option>
<option value="11">Castro Valley TC</option>
<option value="12">Alex Van Dyke TC</option>
<option value="13">Acorn/Oscar Bailey TC</option>
<option value="14">Los Gatos AA</option>
<option value="15">EOYDC</option>
<option value="18">Napa TC</option>
<option value="19">Flying Jaguars TC</option>
<option value="22">South Bay Express TC</option>
<option value="24">Vallejo PAL Steppers</option>
<option value="28">Pleasanton Heat</option>
<option value="30">Santa Rosa Express TC</option>
<option value="32">Silver State Striders</option>
<option value="33">UMOJA TC</option>
<option value="34">Tony Williams TC</option>
<option value="36">Hampton-Phillips Classic TC</option>
<option value="45">MP Stroders TC</option>
<option value="46">AEIOU</option>
<option value="47">AC TC</option>
<option value="48">City Track</option>
<option value="55">Oakland PAL</option>
<option value="56">3M TC</option>
<option value="60">Nor Cal Pacesetters</option>
<option value="75">San Francisco Senators TC</option>
<option value="76">Saint Mark's School TC</option>
<option value="77">CA Track Club</option>
<option value="79">San Luis Obispo Youth TC</option>
<option value="80">Speed City TC</option>
<option value="84">Mustangs TC</option>
<option value="85">Prospects for Success</option>
<option value="90">V-Town TC</option>
<option value="93">Ephensians TC</option>
<option value="94">Miller TC</option>
<option value="95">Joy's Jack Rabbits TC</option>
<option value="96">Union TC</option>
<option value="97">Panther Track</option>
<option value="99">Relay 2000 TC</option>
<option value="100">Tamalpa Runners</option>
<option value="104">Buffalo Chips RC</option>
<option value="108">B Sharp TC</option>
<option value="112">Northwest Express TC</option>
<option value="126">San Luis Distance Club</option>
<option value="137">Santa Cruz TC</option>
<option value="156">CSTC Cheetahs Storm</option>
<option value="157">Salinas Valley TC</option>
<option value="188">Monterey Bay Jaguars Inc</option>
<option value="193">Sierra Foothill TC</option>
<option value="194">Forward Motion RC</option>
<option value="197">East Bay Field & Track</option>
<option value="212">Tri-Valley Vault</option>
<option value="213">Sky Jumpers VSC</option>
<option value="219">Metro TC</option>
<option value="220">Pacific Striders</option>
<option value="221">Fee Flow Vaulters</option>
<option value="223">Reno Tahoe Athletes</option>
<option value="225">Soul Air</option>
<option value="227">Outlaws</option>
<option value="236">Richmond Half Steppers</option>
<option value="238">Central Valley RR</option>
<option value="241">Super 7 RC</option>
<option value="245">Team Sport & Cycle</option>
<option value="246">Peninsula Striders</option>
<option value="247">Acalnes Vault Club</option>
<option value="251">Mokelumne River TC</option>
<option value="252">Piedmont TF Club</option>
<option value="253">Lodi/Stockton TC</option>
<option value="257">North Coast Vaulting Asso</option>
<option value="258">Tri-Countt RC</option>
<option value="261">Fast Forward TC</option>
<option value="263">Roseville Express TC</option>
<option value="264">Deer Valley TC</option>
<option value="265">Fox Athletics TC</option>
<option value="266">Bay Area Roadrunners TC</option>
<option value="272">Club Respect</option>
<option value="273">Lion TC</option>
<option value="274">Athletes for Athletes</option>
<option value="276">Track Stars</option>
<option value="278">Faultline TC</option>
<option value="280">Corsaire TF</option>
<option value="281">Full Stride TC</option>
<option value="282">Boys/Girls Club Sprinters</option>
<option value="285">South Tahoe XC</option>
<option value="288">TNT TC</option>
<option value="291">Pride TC</option>
<option value="292">Dustkickers T&F Club</option>
<option value="298">Parsons Tigers TC</option>
<option value="600">Unattached Boys</option>
<option value="800">Unattached Girls</option>
</select>
<p>
<b>Enter Full Name:</b> <input type="text" name="nameEntered" size="60" maxlength="60" value="<?php if (isset($_POST['nameEntered'])) {echo $_POST['nameEntered'];} ?>"/> </i></p>
<p><b>Enter Last Name:</b> <input type="text" name="LnameEntered" size="40" maxlength="40" value="<?php if (isset($_POST['LnameEntered'])) {echo $_POST['LnameEntered'];} ?>"/> </i></p>
</fieldset>
<div align="center"><input type="submit" name="submit" value="Update Database" /></div>
</form>
</body>
</html>