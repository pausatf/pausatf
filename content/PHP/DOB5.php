<?php
// ----------------------------------------------------------------------------*
// DOB5.php                                                                    *
// Created January 2006                                                        *
// Created by Dan Preston                                                      *
// This script updates/deletes on Club and Last Name on the DOB verification   *  
// database.                                                                   *
// ----------------------------------------------------------------------------*
if (isset($_POST['submit'])) {  //has the screen even been displayed for entry yet?

    $key1 = $_POST['key1'];   // if 3rd pass, key was saved on screen in 2nd pass
    $key1 = 0 + $key1;
    $Display1 = $_POST['Display1'];
   
    if ($key1 > 0) {  // Has a key been saved off in the 2nd pass (is this 3rd pass?)

        require_once ('/home/pausat/DOB.php');
        
        if ($_POST['UD1'] == 'U') {                  //  **** This is 3rd pass logic ***
            $query = "UPDATE DOBNames SET FullName='$Display1' WHERE UKey='$key1'";
            $result1 = @mysql_query ($query);
            if (mysql_affected_rows() == 1) {
                echo "<p>$Display1 successfully Updated<br>";
                echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
            } else {
                echo "<p>$Display1 was not changed<br>";
            }    
        } else {
            if ($_POST['UD1'] == 'D') {
                $query = "DELETE FROM DOBNames WHERE Ukey = '$key1'";
                $result1 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display1 successfully Deleted<br>";
                    echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";  
                } else {
                    echo "<p>$Display1 was not Deleted<br>";
                }
            } 
        }
        $key2 = $_POST['key2'];
        $key2 = 0 + $key2;
        $Display2 = $_POST['Display2'];
        if ($key2 > 0) {
            if ($_POST['UD2'] == 'U') {
                $query = "UPDATE DOBNames SET FullName='$Display2' WHERE UKey='$key2'";
                $result2 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display2 successfully Updated<br>";
                    echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
                } else {
                    echo "<p>$Display2 was not changed<br>";
                }
             } else {   
                if ($_POST['UD2'] == 'D') {
                    $query = "DELETE FROM DOBNames WHERE Ukey = '$key2'";
                    $result2 = @mysql_query ($query);
                    if (mysql_affected_rows() == 1) {
                        echo "<p>$Display2 successfully Deleted<br>";
                        echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
                    } else {
                        echo "<p>$Display2 was not Deleted<br>";
                    }
                }
            }       
        }
        $key3 = $_POST['key3'];
        $key3 = 0 + $key3;
        $Display3 = $_POST['Display3']; 
        if ($key3 > 0) {
            if ($_POST['UD3'] == 'U') {
                $query = "UPDATE DOBNames SET FullName='$Display3' WHERE UKey='$key3'";
                $result3 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display3 successfully Updated<br>";
                    echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
                } else {
                    echo "<p>$Display3 was not changed<br>";
                }    
            } else {
                if ($_POST['UD3'] == 'D') {
                    $query = "DELETE FROM DOBNames WHERE Ukey = '$key3'";
                    $result3 = @mysql_query ($query);
                    if (mysql_affected_rows() == 1) {
                        echo "<p>$Display3 successfully Deleted<br>";
                    } else {
                        echo "<p>$Display3 was not Deleted<br>";
                    }
                }
            }        
        }
        $key4 = $_POST['key4'];
        $key4 = 0 + $key4;
        $Display4 = $_POST['Display4'];
        if ($key4 > 0) {
            if ($_POST['UD4'] == 'U') {
                $query = "UPDATE DOBNames SET FullName='$Display4' WHERE UKey='$key4'";
                $result4 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display4 successfully Updated";
                } else {
                    echo "<p>$Display4 was not changed";
                }    
            } else {
                if ($_POST['UD4'] == 'D') {
                    $query = "DELETE FROM DOBNames WHERE Ukey = '$key4'";
                    $result4 = @mysql_query ($query);
                    if (mysql_affected_rows() == 1) {
                        echo "<p>$Display4 successfully Deleted";
                    } else {
                        echo "<p>$Display4 was not Deleted";
                    }  
                }      
            }
        }
        $key5 = $_POST['key5'];
        $key5 = 0 + $key5;
        $Display5 = $_POST['Display5']; 
        if ($key5 > 0) {
            if ($_POST['UD5'] == 'U') {
                $query = "UPDATE DOBNames SET FullName='$Display5' WHERE UKey='$key5'";
                $result5 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display5 successfully Updated";
                } else {
                    echo "<p>$Display5 was not changed";
                }    
            } else {
                if ($_POST['UD5'] == 'D') {
                    $query = "DELETE FROM DOBNames WHERE Ukey = '$key5'";
                    $result5 = @mysql_query ($query);
                    if (mysql_affected_rows() == 1) {
                        echo "<p>$Display5 successfully Deleted";
                    } else {
                        echo "<p>$Display5 was not Deleted";
                    }
                }
            }
        }
        $key6 = $_POST['key6'];
        $key6 = 0 + $key6;
        $Display6 = $_POST['Display6'];
        if ($key6 > 0) {
            if ($_POST['UD6'] == 'U') {
                $query = "UPDATE DOBNames SET FullName='$Display6' WHERE UKey='$key6'";
                $result6 = @mysql_query ($query);
                if (mysql_affected_rows() == 1) {
                    echo "<p>$Display6 successfully Updated";
                } else {
                    echo "<p>$Display6 was not changed";
                }    
            } else {
                if ($_POST['UD6'] == 'D') {
                    $query = "DELETE FROM DOBNames WHERE Ukey = '$key6'";
                    $result6 = @mysql_query ($query);
                    if (mysql_affected_rows() == 1) {
                        echo "<p>$Display6 successfully Deleted";
                    } else {
                        echo "<p>$Display6 was not Deleted";
                    }
                }      
            }
           
        }
        // reinitialize 3rd pass fields for next session:
        $_POST['UD1'] = ' ';
        $_POST['key1'] = '0';
        $_POST['Display1'] = ' ';
        $_POST['UD2'] = ' ';
        $_POST['key2'] = '0';
        $_POST['Display2'] = ' ';
        $_POST['UD3'] = ' ';
        $_POST['key3'] = '0';
        $_POST['Display3'] = ' '; 
        $_POST['UD4'] = ' ';
        $_POST['key4'] = '0';
        $_POST['Display4'] = ' ';
        $_POST['UD5'] = ' ';
        $_POST['key5'] = '0';
        $_POST['Display5'] = ' ';
        $_POST['UD6'] = ' ';
        $_POST['key6'] = '0';
        $_POST['Display6'] = ' ';
        echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
  
    } else {   // not 3rd pass, so this is 2nd pass

        $msg = '';                                        //   *** 2nd Pass Logic ****                                                
// Check Club entered  
        if (strlen($_POST['cnumEntered']) > 0) {
            $cnumEntered = $_POST['cnumEntered'];
        } else {
            $msg .= '<p><b>You did not select a club.</b></p>'; 
        } 
// Check athlete name entered      
        if (strlen($_POST['nameEntered']) > 0) {
            $nameEntered = $_POST['nameEntered'];
        } else {
            $msg .= '<p><b>You did not enter a name.</b></p>'; 
        }  
        if ($msg == '') {    
            require_once ('/home/pausat/DOB.php');
            $cnum = $cnumEntered;
            $name = $nameEntered;
            $query = "SELECT * FROM DOBNames WHERE LastName = '$nameEntered' and Club = '$cnumEntered'"; 
            $result = @mysql_query ($query);  // Execute the SELECT
            if (!($result)) {  // was SELECT succcessful
                echo "<p>Error on Database. Contact Webmaster.</p><p>" . mysql_error() . '</p>';
                exit();
            } else {  
                $num = mysql_num_rows($result);   // if successful, get the number of rows 
            }
            if ($num == 0) {  
                echo "<p><b>Last Name $nameEntered for club $cnumEntered is not on the database.<br>";
                echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
                exit();
            } else {
                 $x = 0;      
                 if ($num > 6) {
                    echo "$num names is too many to work with.  Limited to 6 names<br>";
                    echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
                    exit();
                 } else {   
                     while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
                         $x = 1 + $x;   
                         switch ($x) {
                             case 1:
                                 $_POST['Display1'] = $row[1];   // put full name on screen
                                 $_POST['key1'] = $row[3];       // put unique key on screen
                                 $key1 = $_POST['key1'];  
                                 break;
                            case 2:
                                 $_POST['Display2'] = $row[1];
                                 $_POST['key2'] = $row[3];
                                 break;
                            case 3:
                                 $_POST['Display3'] = $row[1];
                                 $_POST['key3'] = $row[3];
                                 break;
                            case 4:
                                 $_POST['Display4'] = $row[1];
                                 $_POST['key4'] = $row[3];
                                 break;
                            case 5:
                                 $_POST['Display5'] = $row[1];
                                 $_POST['key5'] = $row[3];
                                 break;
                            case 6:
                                 $_POST['Display6'] = $row[1];   
                                 $_POST['key6'] = $row[3];
                                 break;
                         }  // end of switch
                     }  // end of while loop
                 }  // end of if ($num > 6)   
            } // end of if ($num == 0)
        } else {  // (form did not pass edits--end of if $msg = ' ')
            echo "<p><b> $msg </b></p>";      
            echo '<p><b>Please try again.</b></p>';
            echo "<a href='http://www.pausatf.org/PHP/DOB5.php'><p>Return</a> to search screen.";
        } // end of edits (if msg == '')
        mysql_close(); 
    }  // end of test for 3rd pass, if ($key1 > 0)
 
}  // if not 2nd or 3rd pass, it is 1st pass; just drop through to display form (as all passes do):
                                                             // **** 1st pass just displays screen (below) ****
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
<fieldset><legend>DOB Update/Delete Form</legend>
<p><b><i>Select the Club and enter the last name of the athlete to be queried.</i><b></p>
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
<p><b><i>Note: the</i> LAST name <i>where the full name is 3 or 4 names long is the last one in order (but not 'JR' or III, etc.)</i></b></p>  
<p><b>Enter Last Name:</b><input type="text" name="nameEntered" size="40" maxlength="40" value="<?php if (isset($_POST['nameEntered'])) {echo $_POST['nameEntered'];} ?>"/></p> 
<hr>
<p><b>Action:</b><input type="radio" name="UD1" value="U" /> Update <input type="radio" name="UD1" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display1" size="60" maxlength="60" value="<?php if (isset($_POST['Display1'])) {echo $_POST['Display1'];} ?>" /> 
<p><b>Action:</b><input type="radio" name="UD2" value="U" /> Update <input type="radio" name="UD2" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display2" size="60" maxlength="60" value="<?php if (isset($_POST['Display2'])) {echo $_POST['Display2'];} ?>" /> 
<p><b>Action:</b><input type="radio" name="UD3" value="U" /> Update <input type="radio" name="UD3" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display3" size="60" maxlength="60" value="<?php if (isset($_POST['Display3'])) {echo $_POST['Display3'];} ?>" />   
<p><b>Action:</b><input type="radio" name="UD4" value="U" /> Update <input type="radio" name="UD4" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display4" size="60" maxlength="60" value="<?php if (isset($_POST['Display4'])) {echo $_POST['Display4'];} ?>" /> 
<p><b>Action:</b><input type="radio" name="UD5" value="U" /> Update <input type="radio" name="UD5" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display5" size="60" maxlength="60" value="<?php if (isset($_POST['Display5'])) {echo $_POST['Display5'];} ?>" /> 
<p><b>Action:</b><input type="radio" name="UD6" value="U" /> Update <input type="radio" name="UD6" value="D" /> Delete
&nbsp;&nbsp;<input type="text" name="Display6" size="60" maxlength="60" value="<?php if (isset($_POST['Display6'])) {echo $_POST['Display6'];} ?>" /> 
<input type="hidden" name="key1" size="11" maxlength="11" value="<?php if (isset($_POST['key1'])) {echo $_POST['key1'];} ?>" /> 
<input type="hidden" name="key2" size="11" maxlength="11" value="<?php if (isset($_POST['key2'])) {echo $_POST['key2'];} ?>" /> 
<input type="hidden" name="key3" size="11" maxlength="11" value="<?php if (isset($_POST['key3'])) {echo $_POST['key3'];} ?>" /> 
<input type="hidden" name="key4" size="11" maxlength="11" value="<?php if (isset($_POST['key4'])) {echo $_POST['key4'];} ?>" /> 
<input type="hidden" name="key5" size="11" maxlength="11" value="<?php if (isset($_POST['key5'])) {echo $_POST['key5'];} ?>" /> 
<input type="hidden" name="key6" size="11" maxlength="11" value="<?php if (isset($_POST['key6'])) {echo $_POST['key6'];} ?>" /> 
</fieldset>
<div align="center"><input type="submit" name="submit" value="Submit the Query" /></div>
</form>
</body>
</html>