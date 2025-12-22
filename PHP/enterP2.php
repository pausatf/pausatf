<?php
// ----------------------------------------------------------------------------*
// enterP2                                                                     *
// Created August 2005                                                         *
// Created by Dan Preston                                                      *
// This script handles the display and update of club profile information.     *
// It is called by enterP1 which captures and passes the club number.          *
// ----------------------------------------------------------------------------*
require_once ('db.php');
if (!(strlen($_POST['savepaid'])) > 0) {  // Has screen been updated and returned yet?
    $cnum = $_COOKIE[cnum];             // if not, read database with fid passed, fill screen
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
               
               $clogo = ($row[3]);                  // move database fields that cannot
               $savepaid = ($row[4]);               // be updated to variables for 
               $udate = $row[5];                    // display on the screen  
               $pcontact = $row[7];
               $pphone = $row[8];
               switch ($savepaid) {
               case 'B':
                   $savepaid = 'Y';
                   break;
               case 'E':
                   $savepaid = 'Y';
                   break;
               } 
               $_POST['cname'] = $row[1];
               $_POST['savepaid'] = $savepaid;            // move database fields that can
               $_POST['mcontact'] = $row[9];            // be updated to the screen variables ($_POST[]) 
               $_POST['mphone'] = $row[10];             // and to normal variables for display  
               $_POST['email'] = $row[15];
               $_POST['hotline'] = $row[16];  
               $_POST['website'] = $row[17]; 
               $_POST['totmem'] = $row[18];
               $_POST['dues'] = $row[19];
               $_POST['yrestb'] = $row[20];
               $_POST['loc'] = $row[21]; 
               $_POST['spon'] = $row[22];
               $_POST['twkouts'] = $row[23];
               $_POST['rwkouts'] = $row[24];
               $_POST['nwsfreq'] = $row[25];
               $_POST['savecfocus'] = $row[26];
               $_POST['pfocus'] = $row[27];
               $_POST['events'] = $row[28];
               $_POST['comments'] = $row[29];
               $cname = $row[1];
               $mcontact = $row[9];             
               $mphone = $row[10];               
               $email = $row[15];
               $hotline = $row[16];  
               $website = $row[17]; 
               $totmem = $row[18];
               $dues = $row[19];
               $yrestb = $row[20];
               $loc = $row[21]; 
               $spon = $row[22];
               $twkouts = $row[23];
               $rwkouts = $row[24];
               $nwsfreq = $row[25];
               $savecfocus = $row[26];
               $_POST['cfocus'] = explode (' ', $savecfocus);
               $pfocus = $row[27];
               $events = $row[28];
               $comments = $row[29]; 
               $cdate = $row[30];   
             } // end of while loop
        } else {  
            echo "<p><b>No match found for '$cnum'</b></p>";
            exit();
        }  // end of if ($num)
        if ($savepaid == '') {                      // paid status unknown. (At initial database conversion)
            $_POST['savepaid'] = 'U';
            $savepaid = 'U'; 
        }
    }  // end of was SELECT successful
} else {
    $cnum = $_COOKIE[cnum];            // if screen has something on it

    // protect $_POST variables
    // from http://stackoverflow.com/questions/4223028/
    // mysql-real-escape-string-for-entire-request-array-or-need-to-loop-through-i

    $s_POST = array_map('mysql_real_escape_string', $_POST);
    $s_POST['cfocus'] = $_POST['cfocus'];  // just copy these values to preserve the checkbox array

    $cname = $s_POST['cname'];
    $mcontact = $s_POST['mcontact'];    // get variables from screen
    $mphone = $s_POST['mphone'];        // which has been updated by user   
    $email = $s_POST['email'];
    $hotline = $s_POST['hotline'];  
    $website = $s_POST['website']; 
    $totmem = $s_POST['totmem'];
    $dues = $s_POST['dues'];
    $yrestb = $s_POST['yrestb'];
    $loc = $s_POST['loc']; 
    $spon = $s_POST['spon'];
    $twkouts = $s_POST['twkouts'];
    $rwkouts = $s_POST['rwkouts'];
    $nwsfreq = $s_POST['nwsfreq'];
    $savecfocus = $s_POST['savecfocus']; 
    $pfocus = $s_POST['pfocus'];
    $events = $s_POST['events'];
    $comments = $s_POST['comments'];
    $savepaid = $s_POST['savepaid'];
    if (isset($s_POST['cfocus'])) {
        $cfocus = implode (' ', $s_POST['cfocus']);
        $s_POST['savecfocus'] = $cfocus;
        $savecfocus = $cfocus; 
    } else {
        if (isset($s_POST['savecfocus'])) {  
            $cfocus = $savecfocus;
        } else {
            $cfocus = "Not selected";
        } 
    }
    
//   None of these fields are required entry, and there are no edits.

    $cnum = 0 + $cnum;
    require_once ('db.php');

// do the update. 
    $query = "UPDATE tblCLUBS SET club_name='$cname', update_date=CURDATE(), 
                  membership_contact='$mcontact', membership_phone='$mphone', email='$email', hotline='$hotline', website='$website',
                  total_members='$totmem', annual_dues='$dues', year_established='$yrestb', locations='$loc', 
                  sponsors='$spon', track_workouts='$twkouts', road_trail_workouts='$rwkouts', newsletter_frequency='$nwsfreq', focus='$cfocus',
                  primary_focus='$pfocus', annual_club_events = '$events', comments = '$comments' 
                  WHERE club_no='$cnum'";
        
    $result = @mysql_query ($query);

    if (mysql_affected_rows() == 1) {
        echo "Update successful for $cname";
    } else {
        if (mysql_affected_rows() == 0) { 
            echo "Nothing changed.  Query only.";
        } else {
            echo "<p>Database error in enterP2 on UPDATE $cnum</p><p>" . mysql_error() . '</p>';
            mysql_close();
            exit();
        } 
    } 
          
}  // end of first if at top of page

// end of PHP script.  HTML form follows. 
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
<font size=5><b>Club Profile</b></font>
</center>
<br>
<hr>
<p><i><b>Once you click on the Submit Information box below, your changes will be stored.<br>
When done, just exit the screen (clicking on Submit Information box brings the screen back)<br>
You can just query the information (you do not have to make changes).</b></i></p>  
<hr>
<i><b>In order to change your primary club contact either the club president or the currently<br>
listed primary contact must notify the PA office that the information has changed.  This notification<br>
must be done by emailing the PA office at <a href="mailto:pausatf@aol.com">pausatf@aol.com</a> or by<br>
sending a written notification to the office at:<br> 
120 Ponderosa Ct, Folsom, CA 95630</b></i> 
<p><b>Club Number:</b>&nbsp;<?php echo $cnum; ?></p>
<p><b>&nbsp;&nbsp;Primary Contact:</b>&nbsp;<?php echo $pcontact; ?></p>
<p><b>Phone Number:</b><?php echo $pphone; ?>
<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Approved Status:</b>&nbsp;<?php echo $savepaid; ?>
<hr>
<!-- nm_Form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset><legend>Update Club Profile</legend>
<h2>Club Contacts</h2>
<?php echo $_POST['cname']; ?>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?php echo $cname; ?>" />
<p><b>Membership Contact:</b> <input type="text" name="mcontact" size="50" maxlength="50" value="<?php echo $mcontact; ?>" /> 
<p><b>Phone Number:</b> <input type="text" name="mphone" size="75" maxlength="75" value="<?php echo $mphone; ?>" /> </p><br>
<p><b>Club Email Address:</b> <input type="text" name="email" size="80" maxlength="80" value="<?php echo $email; ?>" />  
<p><b>Club 24-Hr Hotline: </b> <input type="text" name="hotline" size="50" maxlength="50" value="<?php echo $hotline; ?>" />
<p><b>Club Website URL: </b> <input type="text" name="website" size="100" maxlength="100" value="<?php echo $website; ?>" /> <br>  
<hr>
<h2>Club Details</h2>
<b>Total Members:</b> <input type="text" name="totmem" size="50" maxlength="50" value="<?php echo $totmem; ?>" /> 
<p><b>Annual Dues:</b> <input type="text" name="dues" size="50" maxlength="50" value="<?php echo $dues; ?>" />
<p><b>Counties of Membership (top 3): <input type="text" name="loc" size="30" maxlength="30" value="<?php echo $loc; ?>" />
<p><b>Year Established: <input type="text" name="yrestb" size="9" maxlength="9" value="<?php echo $yrestb; ?>" />
<p><b>Locations: <input type="text" name="loc" size="30" maxlength="30" value="<?php echo $loc; ?>" />
<p><b>Sponsors: <input type="text" name="spon" size="255" maxlength="255" value="<?php echo $spon; ?>" /> 
<h2>Club Attributes</h2>
<b>Track Workout Location/Time/Days: <input type="text" name="twkouts" size="255" maxlength="255" value="<?php echo $twkouts; ?>" /> 
<p><b>Road/Trail Locations/Time/Days: <input type="text" name="rwkouts" size="255" maxlength="255" value="<?php echo $rwkouts; ?>" />
<p><b>Newsletter Frequency: <input type="text" name="nwsfreq" size="30" maxlength="30" value="<?php echo $nwsfreq; ?>" />
<p><b>Our club participates in the following USATF disciplines: (Currently: <?php echo $savecfocus; ?> )</b><br>
<input type="checkbox" name="cfocus[]" value="Road" /> Road<br>
<input type="checkbox" name="cfocus[]" value="Cross Country" /> Cross Country<br>
<input type="checkbox" name="cfocus[]" value="Ultras" /> Ultras<br>
<input type="checkbox" name="cfocus[]" value="Track and Field" /> Track and Field<br>
<input type="checkbox" name="cfocus[]" value="Youth" /> Youth<br>
<input type="checkbox" name="cfocus[]" value="Race Walking" /> Race Walking<br></p>
<p><b>The <u><big>Primary</big></u> focus of our club (e.g. Training and competition) is: <input type="text" name="pfocus" size="35" maxlength="35" value="<?php echo $pfocus; ?>" />      
<p><b>Annual Club Events:</b> <input type="text" name="events" size="255" maxlength="255" value="<?php echo $events; ?>" /> </p>
<p><b>General Comments:</b> <input type="text" name="comments" size="255" maxlength="255" value="<?php echo $comments; ?>" /> </p>
<p><b>Last updated:</b>&nbsp;<?php echo "$udate"; ?></p>
<input type="hidden" name="savepaid" size="1" value="<?php echo $_POST['savepaid']; ?>" />
<input type="hidden" name="savecfocus" size="25" value="<?php echo $_POST['savecfocus']; ?>" />  
</fieldset>
<p>
<div align="left"><input type="submit" name="submit" value="Submit Profile for processing" /></div>
</form><!-- End of Form -->
</td></tr></table></div>
</body>
</html>
