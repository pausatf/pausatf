<?php
/*
 * Script for submitting scoresheets
 * Written by Jeff Teeters, June 2007.  jeff@teeters.us
 * @version $Id: ScoreSheet.php 17 2013-04-06 23:01:14Z jeff@teeters.us $
 */

// redirect to temporary site until this can be updated
header("Location: http://pausatf.x10host.com/data/ScoreSheet.php");
die();

  error_reporting(E_ALL);    
  require_once ('/var/www/legacy/private/db.php');  // connects to database

  function this_script_name() {
    $full_script_name = $_SERVER['SCRIPT_NAME'];  // e.g. /teeters/show-members.php
    $script_name = substr(strrchr($full_script_name, "/"), 1);  // e.g. show-members.php
    return($script_name);
  }

  function initialize_timezone() {
    global $mysqli;
    putenv("TZ=US/Pacific");  // set php timezone
    $offset = date("O");  // Offset to GMT  like -0800
    $offset = substr($offset, 0, -2) . ":00";  // Convert to -08:00
    $sql = "SET time_zone = '$offset'";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('initialize_timezone', $sql);
  }

  function get_request($key, $default = '') {
    $val = (isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default);
    return ($val);
  }

  function db_select1($sql) {
    global $mysqli;
    // select and return only one value.  fail if not exactly one row in result
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('db_select1', $sql);
    if(($num_rows = $mysqli->num_rows($result)) != 1) {
      fail("db_select1 expected one row, found $num_rows: $sql");
    }
    $row = $mysqli->fetch_row($result);
    return($row[0]);  
  }

  function pass_form_variables($vars) {
    $output = '';
    if($vars != '') {
      $var_list = explode(',', $vars);
      foreach ($var_list as $var) {
        // allow value to be specified.  e.g. rn=4
        if(preg_match('/^(\w+)=(.*)$/', $var, $matches)) {
          $var = $matches[1];
          $val = $matches[2];
        } else {
          $val = get_request($var);
        }
        if($val != '') {
          $output .= "<input type=\"hidden\" name=\"$var\" value=\"$val\">\n";
        }
      }
    }
    return($output);
  }

  function get_race_number($title, $heading, $time_span = 'recent') {  // set to 'any' for any races
    // if specified by a url variable, use that.
    if($rn = get_request('rn')) {
      return($rn);
    }
    $race_select = mk_race_select($time_span);
    if($race_select['single_race']) {
      return($race_select['single_race']);  // only one race, use that
    }
    // more than one possible race, output select form
    $output = mk_header($title, $heading)
        . mk_form_start('cmd,pw')  // pw passed through in case page for viewing scoresheets
        . $race_select['select'] 
        . "<input type=\"submit\" name=\"submit\" value=\"Submit\">\n" 
        . mk_form_end() . mk_footer();
    echo $output;
    exit();
  }

  function get_race_and_club() {
    // used only by the status function
    $rn = get_request('rn');
    $club_no = get_request('club');
    if($rn && $club_no) {
      return(array($rn, $club_no));
    }
    $race_select = mk_race_select('all');
    $club_select = mk_club_select();
    $title = "Scoresheet - View status";
    $heading = "Scoresheet status";
    $output = mk_header($title, $heading) . mk_form_start('cmd')
      . "<div style=\"text-align:center;\"><fieldset style=\"width: 70%;text-align:left;\"><legend>Scoresheet Status Entry Form</legend>\n"
      . "This database is automatically updated when a scoresheet is submitted. "
      . "All submitted scoresheets should be listed. "
      . "If you submitted a scoresheet which is not listed, "
      . "send the scoresheet (along with a description of the problem you had) to "
      . "the scorer.<br />\n"
      . "Road scorer is <A href=\"mailto:tlbernhard2@gmail.com\">Tom Bernhard</A>, "
      . "cross-country scorer is <a href=\"mailto:lesong@tkecapital.com\">Les Ong</a>."
      . "<p style=\"text-align:center;\">\n"
      . $race_select['select'] . "<br />\n"
      . $club_select
      . "</p>\n"
      . "</fieldset></div>\n"
      . '<div align="center"><input type="submit" name="submit" value="List submitted Scoresheets" /></div>' . "\n"
      . mk_form_end() . mk_footer();
      echo $output;
    exit(1);
  }

  function mk_form_start($form_vars = '') {
    $this_script_name = this_script_name();
    $output = '<form name="main" action="' . $this_script_name . '"  method="post">' . "\n"
        . pass_form_variables($form_vars);
    return($output);
  }
  
  function mk_form_end() {
    return("</form>\n");
  }



  function mk_header($title, $heading) {
    $output = 
'<html>
<head>
	<title>' . $title . '</title>
	<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body style="max-width:890px;">
  <table border="0" cellspacing="0" cellpadding="0" width="100%">' .
/**
      <tr>
        <td valign="top" align="center" colspan="3" style="background-color: green; color: white;"><b><<< Script for testing.  Will NOT submit real scoresheets.  Please test this script >>></b></td>
      </tr>
**/ '
      <tr>
        <td valign="top" width="20%">
          <font size="2"><a href="http://www.pausatf.org/index.html"><b>Home</b></a><br>
             <a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
        <td valign="top" style="text-align: center; width: 60%;">
          <h3>' . $heading . '</h3>
        </td>
        <td width="20%" align="right" valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="3" align="center" width="100%">
          <!-- hr width="80%" -->
          <table><tr><td style="text-align: left;">
            <!-- Content goes here -->
';
  return($output);
}

  function mk_footer() {
    $html =
'          </td></tr></table>
        </td>
      </tr>
  </table>
</body>
</html>
';
    return($html);
}

  function add_scoresheet_link() {
    $this_script_name = this_script_name();
    $race_number = get_request("rn");
    $club = get_request("club");
    $parms = array();
    if($race_number) {
      $parms[] = "rn=$race_number";
    }
    if($club) {
      $parms[] = "club=$club";
    }
    if($parms) {
      $parms = '?' . implode('&', $parms);
    } else {
      $parms = '';
    }
    $output = "<a href=\"$this_script_name$parms\">Enter new score sheet</a>.";
    return($output);
  }



/***
  function mk_footer() {
    $output = "<!-- /div --></td></tr></table>\n</body>\n</html>\n";
    return($output);
  }
****/

  function mk_race_select($time_span = 'recent') {  // use any for selecting any race
    global $mysqli;
    // get date of latest race
    $sql = "select max(Race_Date) from tblRACES where Race_Date < now()";
    $last_date = db_select1($sql);
    $interval = $time_span == 'recent' ? "5 DAY" : "360 DAY";
    $sql = "select Race_Number,Race_Name,\n" .
           "Race_Date as sortDate,\n" .
           "date_format(Race_Date, '%b %e, %Y') as raceDate,\n" .
           "Distance,Location,Race_abbreviation\n" .
           "from tblRACES where Race_Date > DATE_SUB('$last_date', INTERVAL $interval)\n" .
           "and Race_Date < now() order by sortDate desc";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('select_race', $sql);
    $num_rows = $mysqli->num_rows($result);
    $race_select = array();
    if($num_rows == 1) {
      // only one race, use that
      $row = $mysqli->fetch_array($result);
      $race_select['single_race'] = $row['Race_Number'];
      $race_select['select'] = "<input type=\"hidden\" name=\"rn\" value=\"$row[Race_Number]\">\n";  // used only for status() command
    } else {
      $race_select['single_race'] = '';
      // more than one possible race, output select form
      $output = "<b>Select race:</b> <select name=\"rn\">\n";
      while($row = $mysqli->fetch_array($result)) {
        $race_number = $row['Race_Number'];
        $race_name = $row['Race_Name'];
        $race_date = $row['raceDate'];
        $output .= "<option value=\"$race_number\">$race_name ($race_date)</option>\n";
      }
      $output .= "</select>\n";
      $race_select['select'] = $output;
    }
    return($race_select);
  }

  function make_lower_case_name($name) {
    // Used to make club names mostly lower case
    $keep_upper = array('AEIOU', 'AFB', 'T&F', 'EOYDC', 'WNLR', 'WWW', 'NAPA', 'PAL', 'VSC', 'TAM', 'TNT', 'L.S.I.');
    $force_lower = array('OF', 'AND', 'AT');
    $words = explode(' ', $name);
    foreach ($words as $word) {
      if(in_array(strtoupper($word), $force_lower)) {
        $word = strtolower($word);
      } else {
        $length = strlen($word);
        if($length > 2 && !in_array($word, $keep_upper)) {
          $word = ucfirst(strtolower($word));
        }
      }
      $new_words[] = $word;
    }
    return(implode(' ', $new_words));
  }

  function mk_club_select() {
    global $mysqli;
    $selected_club = get_request('club', 0);
    $sql = "select club_no, club_name from tblCLUBS where approved = 'Y' order by club_name";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('main_race', $sql);
    $options = array();
    $options[] = array(0, "Select a club");
    while($row = $mysqli->fetch_array($result)) {
      $club_no = $row['club_no'];
      $club_name = make_lower_case_name($row['club_name']);
      $options[] = array($club_no, $club_name);  // count($options)  to display number of clubs
    }
    $output = "<b>Club:</b> " . mk_html_select('club', $selected_club, $options);
    return($output);
  }

  function divisions() {
    $divisions = array(
       array('none', 'Select Division'),
       array('M-Open', "Men's Open"),
       array('F-Open', "Women's Open"),
       array('M-40', "Men's 40+"),
       array('F-40', "Women's 40+"),
       array('M-50', "Men's 50+"),
       array('F-50', "Women's 50+"),
       array('M-60', "Men's 60+"),
       array('F-60', "Women's 60+"),
       array('M-70', "Men's 70+"),
       array('F-70', "Women's 70+")
    );
	return($divisions);
  }

  function get_division_name($division) {
    static $divisions = '';
    if(!$divisions) {
      $divisions = divisions();
    }
    foreach($divisions as $div_info) {
      if($div_info[0] == $division) {
        return($div_info[1]);
      }
    }
    fail('get_division_name', "invalid division: '$division'");
  }

  function mk_division_select() {
    $selected_division = get_request('division', 'none');
    $options = divisions();
    $output = "<b>Division:</b> " . mk_html_select('division', $selected_division, $options);
    return($output);
  }

  function mk_html_select($var_name, $selected_value, &$options) {
    $output = "<select name=\"$var_name\" onchange=\"reloadform()\">";
    foreach($options as $option) {
      list($key, $text) = $option;
      $selected = ($key == $selected_value ? ' selected' : '');
      $output .= "<option$selected value=\"$key\">$text</option>";
    }
    $output .= "</select>\n";
    return($output);
  }

  function mk_team_type_select() {
    $team_type = get_request('team_type', 'A');
    $options = array(
       array('A', 'A Team'), 
       array('B', 'B Team'));
    $output = '<b>Team:</b>&nbsp;' . mk_radio_select('team_type', $team_type, $options) . "<br />\n";
    return($output);
  }

  function mk_radio_select($var_name, $selected_value, &$options, $delim=' ') {
    $output = array();
    foreach($options as $option) {
      list($key, $text) = $option;
      $selected = ($key == $selected_value ? ' checked' : '');
      $output[] = "<input type=\"radio\" name=\"$var_name\" value=\"$key\"$selected>$text";
    }
    $output = implode($delim, $output);
    return($output);
  }

  function mk_email_form() {
  $output =
'<b>Enter your email address:</b><INPUT type="text" size=50 name="Submitter"><br />
<b>Other emails to copy (separate each with a semi-colon):</b><INPUT type="text" size="50" name="Ccs">
<hr />
';
  return($output);
  }

  function mk_main_form($race_number) {
    $club_number = get_request('club', 0);
    $division = get_request('division', 'none');
    $needed = array();
    if(!$club_number) {
      $needed[] = "a club";
    }
    if($division == 'none') {
      $needed[] = "a division";
    }
    if($needed) {
      $output = "<b><font color=\"green\">Please select " . implode(' and ', $needed) . "!</font></b>";
    } else {
      if(!preg_match('/^([MF])-(Open|\d\d)$/', $division, $matches)) {
        die("Was not able to match division '$division'");
      }
      $sex = $matches[1];
      $age = $matches[2];
      if($age == 'Open') {
        $age = $sex == 'M' ? 16 : 14;  // not sure why, age 16 for male, 14 female
      }
      $output = get_roster_list_box($race_number, $club_number, $age, $sex);
    }
    return($output);
  }

  function get_roster_list_box($race_number, $club_number, $age, $sex) {
    global $mysqli;
    $sql = "select Race_Date from tblRACES where race_number = $race_number";
    $race_date = db_select1($sql);
    $race_date = str_replace('00:00:00', '10:00:00', $race_date);  // set time to 10AM if not already set
    $age_on_race = "(YEAR('$race_date')-YEAR(m.birth_date)) - (substr('$race_date',6,5)<RIGHT(m.birth_date,5))";
    $sql = "select trim(concat(m.first_name, ' ', m.middle_initial)) as first_name, trim(concat(m.last_name, ' ', m.suffix)) as last_name,\n" .
           "if(m.membership_number != '', m.membership_number, m.next_years_number) as membership_number,\n" .
           "LEFT(m.birth_date, 10) as birthdate,\n" .
           "$age_on_race as age_on_race\n" .
           "from pa_members m\n" .
           "where m.club_affiliation = $club_number\n" .
           "and m.date_applied < '$race_date'\n" .
           "and $age <= $age_on_race\n" .
           "and m.gender = '$sex'\n" .
           "order by trim(m.last_name), trim(m.first_name)";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('get_roster_list_box', $sql);
    if($mysqli->num_rows($result) == 0) {
      $output = "There are no eligible USATF members for this club and division!";
    } else {
      $output = '<input type="hidden" name="runners" value="">' . "\n" .
                '<i>Select your club, division, and A team or B team above. Then select your team '. 
                'members and click on "Submit this Team". Please read the <a href="#instructions">detailed '.
                'instructions</a> at the bottom of this page for more specifics.</i>';
      $output .= '<table width="80%" border="0" align="center"><tr><td><b>Members to Select From:</b><br />';
      $output .= '<SELECT name="Roster" size="10" ondblclick="AddRunner()">';
      while($row = $mysqli->fetch_array($result)) {
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $membership_number = $row['membership_number'];
        $member_age = $row['age_on_race'];
        $output .= "<option value=\"$membership_number\">$first_name $last_name ($member_age)</option>";
      }
      $output .= '
				</select>
				</td>
				<TD><INPUT type="button" name=Add onclick="AddRunner()" value ="     >>>Add>>>    ">
			<BR><BR><INPUT type="button" name=Remove onclick="RemoveRunner()" value ="<<<Remove<<<">
				</TD>
				<TD><B>Team Members Selected:</B><BR>
				<SELECT name="TeamSheet" size=10 ondblclick="RemoveRunner()">
				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>				<OPTION value="0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</OPTION>
				</SELECT></TD>
				</TR><TR><TD colspan="2">
				<B>Comments or Scorers with "Pending" USATF cards:<B><BR>
				<TEXTAREA name=Comments cols=60 rows=6 wrap="soft"></TEXTAREA></TD>
				<TD valign="top"><BR><INPUT type="button" name=SendEmail onclick="SubmitSheet()" value ="Submit this Team"></td></tr></table>
				<a name="instructions"><B>Detailed Instructions:</B></a><BR>
				&nbsp;&nbsp;&nbsp;The left-hand box above lists the current USATF members on your club who
				are eligible to score for your club at this race - this means that the PA
				USATF office has them on record as being affiliated with this club and having
				valid USATF memberships before the race.  Age on race day is shown (in parenthesis).
                Please select the athletes who scored for your team at
				this race by selecting them the left-hand box and clicking on the "Add" button to move them into
				the right-hand box.  To remove an athlete from the right-hand box (if you make a mistake), select
				the athlete in right hand box and click on the "Remove" button.  Although all athletes in this list
				have USATF numbers, if possible select more athletes than you need to score in case there is a protest.
				Athletes selected for a "B" team will automatically be moved up to the "A" team in the same division
				if there is some problem with the "A" team, so if you do not want this to happen, please mention this in the
				box for "Comments or Scorers with Pending USATF cards" at the bottom.<BR><BR>
				&nbsp;&nbsp;&nbsp;If an athlete
				is not listed for your club (and should be), please note the athlete' . "'" . 's name in the "Comments or Scorers with Pending USATF cards" box
				at the bottom.  Keep in mind that if the athlete has a membership but is not listed for your club in the USATF
				database, he/she may not be allowed to score unless there is an error on the part of the office or the LDR committee.<BR><BR>
				&nbsp;&nbsp;&nbsp;When you are done, click on the "Submit Team button and your team score sheet will be submitted to the grand prix
				scorers.  You may continue and submit score sheets for as many teams as you have.  You should receive a confirmation email with the
				scoresheet information.  If you do not receive this confirmation email within 15 minutes, please try submitting the
				scoresheet again.  If you still don' . "'" . 't receive the confirmation email, please email the scorer directly to confirm that
				the scoresheet was received.';
    }
    return($output);
  }

  function include_java_script() {
    $this_script_name = this_script_name();
    $output = '
<SCRIPT language="javascript">
function reloadform()
//THIS JAVASCRIPT FUNCTION RELOADS THE FORM
{
		document.main.submit();
    // Reload all the time.  Below commented out.
	//IF AT LEAST ONE CORRECT CRITERIA HAS BEEN SELECTED
    //	if (document.main.club.value != 0 && document.main.division.value != "none")
    //	{
    //		document.main.submit();
    //		}
}

function AddRunner()
{
//THIS FUNCTION COPIES THE SELECTED ROW FROM THE LISTBOX ON THE LEFT AND PLACES IT
//IN THE LISTBOX ON THE RIGHT.
var Runner=""

	//IF A ROW IS SELECTED IN THE LEFT LISTBOX
	var St = document.main.Roster.selectedIndex;
	if (St > -1)
	{
		//SET THE VARIABLE EQUAL TO THE VALUE OF THE LEFT LISTBOX
		Runner=document.main.Roster[document.main.Roster.selectedIndex].value;
	}

	//MAKE SURE VALID ROW SELECTED - IT WILL BE 0 IF IT IS EITHER BLANK OR NO ROW SELECTED
	if (Runner.length < 2)
	{
		//GIVE MESSAGE THAT A PROPER ROW MUST BE SELECTED
		alert("You must click on a Runner from the list on the left!");
	}
	else
	{	//LOOP THROUGH THE 10 ROWS ON THE RIGHT LIST BOX TO LOOK FOR A BLANK ONE
		for (var i = 0; i <=9; i++)
		{
			//IF THE VALUE EQUALS "0", IT IS BLANK
			if (document.main.TeamSheet[i].value == "0")
			{
				//SET VALUE AND TEXT OF RIGHT LISTBOX
				document.main.TeamSheet[i].value = Runner;
				document.main.TeamSheet[i].text = document.main.Roster[document.main.Roster.selectedIndex].text;
				break;
			}
		}
	}
}

function RemoveRunner()
{
//THIS FUNCTION REMOVES THE SELECTED ROW FROM THE LISTBOX ON THE RIGHT
	var Runner=""

	var St = document.main.TeamSheet.selectedIndex;
	//IF A ROW IS SELECTED IN THE RIGHT LISTBOX
	if (St > -1)
	{
		//SET THE VARIABLE EQUAL TO THE VALUE OF THE RIGHT LISTBOX
		Runner=document.main.TeamSheet[document.main.TeamSheet.selectedIndex].value;
	}
	else
	{
		Runner = "0"
	}

	//MAKE SURE CRITERIA IS A VALID ROW
	if (Runner == "0")
	{
		//GIVE MESSAGE THAT A PROPER ROW MUST BE SELECTED
		alert("You must click on a Runner from the list on the right!");
	}
	else
	{
		//LOOP THRU ALL BUT THE LAST ROW IN RIGHT LISTBOX,
		//BEGINNING WITH SELECTED ROW
		var Flg = 0
		for (var z = St;z<9;z++)
		{
			//UPDATE ROW WITH VALUE OF NEXT ROW IN THE LISTBOX.
			//IN EFFECT WE ARE REMOVING THE SELECTED ROW AND
			//MOVING THE SUBSEQUENT ROWS UP ONE
			if (document.main.TeamSheet[z].value == Runner)
			{Flg = 1}
			if (Flg == 1)
			{
				document.main.TeamSheet[z].value = document.main.TeamSheet[z+1].value;
				document.main.TeamSheet[z].text = document.main.TeamSheet[z+1].text
			}
		}
		//SET VALUE OF THE LAST ROW IN LEFT LISTBOX TO 0
		document.main.TeamSheet[9].value = "0"
		document.main.TeamSheet[9].text = "";
	}
}

function SubmitSheet()
{
var Runners="";
var Comments = document.main.Comments.value;
var BTeam = ""
var Submitter = document.main.Submitter.value
if (document.main.team_type[1].checked == true)
{
	BTeam = "B Team"
}
else
{
	BTeam = "A Team"
}
// var Header = "Scoresheet for " + document.main.club.options[document.main.club.selectedIndex].text + " " + document.main.division.options[document.main.division.selectedIndex].text + " " + BTeam + "\n\nSubmitted by " + Submitter + "\n\n";

	//LOOP THRU ALL BUT THE LAST ROW IN RIGHT LISTBOX,
	//BEGINNING WITH SELECTED ROW
	for (var z = 0;z<document.main.TeamSheet.length;z++)
	{
		if (document.main.TeamSheet[z].value == "0")
		{
			break;
		}
		else
		{
			Runners = Runners + document.main.TeamSheet[z].value + ":  " + document.main.TeamSheet[z].text + "\n";
		}
	}

	if (Comments=="" && Runners=="")
	{
		alert("You must either select runners from the list or put their names in the comments!");
	}
	else if (Submitter == "")
	{
		alert("You must enter your email address!")
	} 
    else if (!echeck(Submitter))
    {
        alert("Invalid email address.")
    }
    else
	{
		document.main.runners.value = Runners;
		document.main.action = "' . $this_script_name . '";
		document.main.submit();
	}
}

function echeck(str) {
		var at="@"
		var dot="."
		var lat=str.indexOf(at)
		var lstr=str.length
		var ldot=str.indexOf(dot)
		if (str.indexOf(at)==-1){
		   return false
		}

		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
		   return false
		}

		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
		    return false
		}

		 if (str.indexOf(at,(lat+1))!=-1){
		    return false
		 }

		 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
		    return false
		 }

		 if (str.indexOf(dot,(lat+2))==-1){
		    return false
		 }
		
		 if (str.indexOf(" ")!=-1){
		    return false
		 }

 		 return true					
	}
</SCRIPT>
';
    return($output);
  }

/** Table for keeping track of submissions
drop table score_sheets;
;
CREATE TABLE IF NOT EXISTS score_sheet (
  score_sheet_id int(6) unsigned NOT NULL primary_key auto_increment,
  race_number int(6) unsigned NOT NULL,
  submitter varchar(150) NOT NULL default '',
  submitter_ip varchar(50) NOT NULL default '',
  when_submitted datetime NOT NULL default '0000-00-00',
  cc_emails varchar(255) NOT NULL default '',
  club_no int(5) unsigned NOT NULL,
  division varchar(15) NOT NULL default '',
  team_type enum('A', 'B') not null,
  runners mediumtext not null,
  comments mediumtext not null
) comment='For keeping track of submitted score sheets';
****/

  function &get_race_info($race_number) {
    global $mysqli;
    $sql = "select Race_Name, series,\n" .
           "date_format(Race_Date, '%b %e, %Y') as raceDate,\n" .
           "Distance,Location,Race_abbreviation\n" .
           "from tblRACES where race_number = $race_number";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('select_race', $sql);
    $num_rows = $mysqli->num_rows($result);
    if($num_rows != 1) {
      fail("Expected one row, got $num_rows\n", $sql);
    }
    $row = $mysqli->fetch_array($result);
    $r = array();
    $r['race_name'] = $row['Race_Name'];
    $r['series'] = $row['series'];
    $r['race_date'] = $row['raceDate'];
    $r['distance'] = $row['Distance'];
    $r['location'] = $row['Location'];
    $r['race_abbr'] = $row['Race_abbreviation'];
    return($r);
  }

  function get_club_name($club_no) {
    $club_name = db_select1("select club_name from tblCLUBS where club_no = $club_no");
    $club_name = make_lower_case_name($club_name);
    return($club_name);
  }



  function safe_num($num, $append_coma = 1) {
    global $mysqli;
    $safe = ((isset($GLOBALS["___mysql_ston"]) && is_object($GLOBALS["___mysql_ston"])) ? $mysqli->real_escape_string($GLOBALS["___mysql_ston"], $num) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));
    if($append_coma) {
      $safe .= ',';
    }
    return($safe);
  }

  function safe_str($str, $append_coma = 1) {
    global $mysqli;
    $safe = "'" .((isset($GLOBALS["___mysql_ston"]) && is_object($GLOBALS["___mysql_ston"])) ? $mysqli->real_escape_string($GLOBALS["___mysql_ston"], $str) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : "")). "'";
    if($append_coma) {
      $safe .= ',';
    }
    return($safe);
  }

  function get_to_emails($series) {
    // modify the following to change who gets what submitted team sheets
    $to_emails = array(
          // 'all' => 'tlbernhard@att.net;tct3@pge.com;jeff@teeters.us',
`          'all' => '',
          'xc' => 'lesong@tkecapital.com',
          'road' => 'tlbernhard2@gmail.com');
    $series_xlate = array('long' => 'road', 'short' => 'road', 'xc' => 'xc');
    $series_to = $to_emails[$series_xlate[strtolower($series)]];
    $to_list = array();
    if($to_emails['all'] != '') {
     $to_list[] = $to_emails['all'];
    }
    if($series_to != '') {
     $to_list[] = $series_to;
    }
    $to = join(';', $to_list);
    return($to);
  }

  function SendTeamSheet() {
    global $mysqli;
    $this_script_name = this_script_name();
    $race_number = get_request('rn');
    $club = get_request('club');
    $division = get_request('division');
    $submitter = get_request('Submitter');
    $ip = getenv("REMOTE_ADDR");
    $ccs = get_request('Ccs');
    $ccs = str_replace(';', ',', $ccs);  // replace ; with ,
    $team_type = get_request('team_type');
    $runners = get_request('runners');
    $comments = get_request('Comments');
    // die("<pre>found runners=\n$runners\nComments=$comments</pre>");   // for testing magic quotes
    if(!$race_number || !$club || !$division || !$submitter || !$team_type) {
      $title = "Score sheet - Error";
      $heading = "<font color=\"red\">Error</font>";
      $output = mk_header($title, $heading)
           . "There was a problem submitting this score sheet!  Please hit the Back button and try again.<br />\n"
           . "Details: race_number = $race_number, club=$club, division=$division, submitter=$submitter, team_type=$team_type\n"
           . mk_footer();
      return($output);
    }
    $sql = "insert into score_sheet\n" .
        "(race_number, submitter, submitter_ip, when_submitted, cc_emails, club_no, division, team_type, runners, comments) values\n" .
        "(" . safe_num($race_number) . safe_str($submitter) . "'$ip',now()," . safe_str($ccs) . safe_num($club) . safe_str($division) . safe_str($team_type) . safe_str($runners) . safe_str($comments,0) . ")";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql);
    if(!$result) {
      // can't insert into table, log error
      $msg = "Query failed:\n$sql\n" . ((is_object($GLOBALS["___mysql_ston"])) ? $mysqli->error($GLOBALS["___mysql_ston"]) : (($___mysql_res = $mysqli->connect_error()) ? $___mysql_res : false));
      fail($msg);
    }
    $r = &get_race_info($race_number);
    $club_name = get_club_name($club);
    $division_name = get_division_name($division);
    // send email
    $to = get_to_emails($r['series']);
    $from = $submitter;
    $subject = 'Scoresheet for club #' . $club . ' ' . $division_name . ' ' . $team_type . '-team for ' . $r['race_abbr'];
    $time_stamp = date("M j Y, g:i:sA T");    // e.g. Jan 3 2007, 2:34:12PM PST
    $message = "Race: " . $r['race_name'] . " held on " . $r['race_date'] . " in " . $r['location'] . "\n";
    $message .= "Submitter: $submitter\n";
    $message .=  "Submit time: $time_stamp\n";
    $message .= "\nClub#: $club ($club_name)\n";
    $message .= "Divison: $division_name\n";
    $message .= "Team-Type:  $team_type-team\n";
    $message .= "\nRunners:\n$runners\n";
    if($comments) {
      $message .= "Comments:\n$comments\n";
    } else {
      $message .= "Comments: none\n";
    }
    $cc = $ccs ? $submitter . ',' . $ccs : $submitter;
    // $to_all = $to . ',' . $cc;
    $html = false;
    $test = false;  // set to false for normal
//     $mail_log = mail_user($to_all, $subject, $message, $from, false, $test);
    $output = send_mail($to, $subject, $message, $from, $cc, $html, $test);
    //        send_mail($to, $subject, $message, $from, $cc='', $html=false, $test_mode=false) { }    
    if($test) {
      $output = "<pre>Test mode, email sent:<br />\n$output\n</pre>";
    } else {
      $title = "Score sheet submitted";
      $heading = "Score sheet submitted.";
      $output = mk_header($title, $heading)
        . "Your scoresheet was submitted. You can confirm the submission <a href=\"$this_script_name?cmd=status&rn=$race_number&club=$club\">here</a>. "
        . "Also, you should receive an email "
        . "within 30 minutes showing your submitted scoresheet. "
	    . "If you do not receive the confirmation email you can email the scorer directly with your team(s) and names."
        . "<br />Road scorer is <A href=\"mailto:tlbernhard2@gmail.com\">Tom Bernhard</A>, "
        . "cross-country scorer is <a href=\"mailto:lesong@tkecapital.com\">Les Ong</a>.<br /><br />\n"
	    . "To submit a scoresheet for another division or A team or B Team, please return to the <a href=\"$this_script_name\">Score Sheet Page</a>.  Otherwise, you can return to the <a href=\"http://www.pausatf.org\">PA USATF Home Page</a>\n"
        . mk_footer();
    }
    return($output);
  }

  function require_password($pw) {
    $this_script_name = this_script_name();
    $entered_pw = get_request('pw');
    if($entered_pw != '') {
      if($entered_pw != $pw) {
        echo "Invalid password.";
        exit(1);
      }
      return("<input type=\"hidden\" name=\"pw\" value=\"$pw\">\n");
    } else {
      $title = "View score sheets - Enter password";
      $heading = "View score sheets";
      $output = mk_header($title, $heading) 
        . mk_form_start('cmd')
        . "<b>Enter password:</b> <input type=\"text\" name=\"pw\">\n"
        . mk_form_end() . mk_footer();
      echo $output;
      exit(1);
    } 
  }



  function show_score_sheets() {
    global $mysqli;
    require_password('show1');
    $title = "View score sheets - Select Race";
    $heading = "View score sheets";
    $race_number = get_race_number($title, $heading, 'any');  // have user select race
    $r = &get_race_info($race_number);
    $title = "Score sheets for " . $r['race_abbr'];
    $heading = "Score sheets for ". $r['race_name'] . ' held ' . $r['race_date'] . ' in ' . $r['location'];
    $output = mk_header($title,$heading);
    $sql = "select \n" .
        "s.submitter, s.submitter_ip, date_format(s.when_submitted, '%b %e, %Y %l:%i%p') as whenSubmitted,\n" .
        "s.cc_emails, s.club_no, s.division, s.team_type, s.runners, s.comments,\n" .
        "s.when_submitted as submit_time,\n" .
        "c.club_name\n" .
        "from score_sheet s, tblCLUBS c\n" .
        "where s.race_number = $race_number\n" .
        "and c.club_no = s.club_no\n" .
        "order by c.club_name, s.division, s.team_type, s.when_submitted";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('show_score_sheets', $sql);
    $num_rows = $mysqli->num_rows($result);
    if($num_rows == 0) {
      $output .= "<font color=\"red\">No score sheets found</font>\n";
    } else {
      $output_tmp = '';
      $last_submit_time = '0000-00-00';
      while($r = $mysqli->fetch_array($result)) {
        if($r['submit_time'] > $last_submit_time) {
          $last_submit_time = $r['submit_time'];
          $last_whenSubmitted = $r['whenSubmitted'];
        }
        // $comments = $r['comments'] ? $r['comments'] : 'none';
        $comments = preg_replace("#\n\s*#s", "\n", trim($r['comments']));  // remove any blank lines from comments
        $division = get_division_name($r['division']);
        $output_tmp .= $r['club_name'] . ' (club #' . $r['club_no'] . ') ' . $division . ' ' . $r['team_type'] . ' team'. "<br />\n"
          . '  Submitted by ' . $r['submitter'] . ' (ip ' . $r['submitter_ip'] . ') at ' . $r['whenSubmitted'] . "<br />\n"
          . ($r['cc_emails'] != '' ? "  cc'd: " . $r['cc_emails'] . "<br />\n" : '')
          . nl2br($r['runners'])
          . ($comments ? "Comment: " . nl2br($comments) . "<br />\n" : '')
          . "<br />\n";
      }
      $output .= "$num_rows Score sheets total.  Last submitted $last_whenSubmitted.</b><br /><br />\n"
           . $output_tmp;
    }
    $output .= mk_footer();
    return($output);
  }

  function status() {
    global $mysqli;
    // allows submitters to verify that their scoresheets were submitted
    list($race_number, $club) = get_race_and_club();
    $r = &get_race_info($race_number);
    $club_name = get_club_name($club);
    $title = "Score sheet status for club #" . $club . " - " . $r['race_abbr'];
    $heading = "$club_name (#$club)<br />Submitted score sheets for ". $r['race_name'] . ' held ' . $r['race_date'] . ' in ' . $r['location'];
    $output = mk_header($title, $heading);
    $sql = "select \n" .
        "s.submitter, s.submitter_ip, date_format(s.when_submitted, '%b %e, %Y %l:%i%p') as whenSubmitted,\n" .
        "s.cc_emails, s.club_no, s.division, s.team_type, s.runners, s.comments\n" .
        "from score_sheet s\n" .
        "where s.race_number = $race_number\n" .
        "and s.club_no = $club\n" .
        "order by s.division, s.team_type, s.when_submitted";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql) or fail_sql('show_score_sheets', $sql);
    $num_rows = $mysqli->num_rows($result);
    if($num_rows == 0) {
      $output .= "<font color=\"red\">No score sheets found</font>\n";
    } else {
      $output .= "<pre>\n";
      while($r = $mysqli->fetch_array($result)) {
        $division = get_division_name($r['division']);
        $submitter = str_replace('@', '[AT]', $r['submitter']);
        $output .= $division . ' ' . $r['team_type'] . ' team'
          . '  Submitted by ' . $submitter . ' at ' . $r['whenSubmitted'] . "\n";
      }
      $output .= "\n$num_rows Score sheets total.\n</pre>\n";
      $output .= "<center><b>" . add_scoresheet_link() . "</b></center>\n";
    }
    $output .= mk_footer();
    return($output);
  }

/**
    $r['race_name'] = $row['Race_Name'];
    $r['race_date'] = $row['raceDate'];
    $r['distance'] = $row['Distance'];
    $r['location'] = $row['Location'];
    $r['race_abbr'] = $row['Race_abbreviation'];
**/

  function enter_team() {
    // main routine for entering team sheet;
    $title = "Enter score sheet";
    $heading = "Select Race";
    $race_number = get_race_number($title, $heading);
    $r = &get_race_info($race_number);
    $title = 'Score Sheet - Enter team';
    $heading = 'Submit team for ' . $r['race_name'] . ' held ' . $r['race_date'] . ' in ' . $r['location'];
    $output = mk_header($title, $heading) . mk_form_start("rn=$race_number") . mk_club_select() . mk_division_select() 
         . mk_team_type_select() . mk_email_form() . mk_main_form($race_number) . mk_form_end() . mk_footer()
         . include_java_script();
    return($output);
  }

  function main() {
    initialize_timezone();
    $cmd = get_request('cmd');
    if($cmd == 'show') {
      $output = show_score_sheets();
    } elseif($cmd == 'status') {
      $output = status();
    } else {
      if(get_request('runners')) {
        $output = SendTeamSheet();
      } else {
        $output = enter_team();
      }
    }
    echo $output;
  }





/************* Error and logging routines **************/

  function log_event($summary, $details) {
    global $mysqli;
    $summary = ((isset($GLOBALS["___mysql_ston"]) && is_object($GLOBALS["___mysql_ston"])) ? $mysqli->real_escape_string($GLOBALS["___mysql_ston"], $summary) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));
    $details = ((isset($GLOBALS["___mysql_ston"]) && is_object($GLOBALS["___mysql_ston"])) ? $mysqli->real_escape_string($GLOBALS["___mysql_ston"], $details) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));
    $sql = "insert into db_event_log (table_name, event_time, summary, details) values\n" .
      "('score_sheet', now(), '$summary', '$details')";
    $result = $mysqli->query($GLOBALS["___mysql_ston"], $sql);
    if(!$result) {
      // can't insert into log table, save into file and die
      $msg = "log_event query failed:\n$sql\n" . ((is_object($GLOBALS["___mysql_ston"])) ? $mysqli->error($GLOBALS["___mysql_ston"]) : (($___mysql_res = $mysqli->connect_error()) ? $___mysql_res : false)) . "\n";
      log_to_file($msg);
      email_error($msg);
      die($msg);
    }
  }

  function email_error($summary, $details='') {
    // sends email notification about errors
    $to = "jeff@teeters.us,jeffteeters@yahoo.com";
    $full_program_name = $_SERVER['SCRIPT_FILENAME'];
    $program_name = $_SERVER['PHP_SELF'];
    $subject = "pausatf $program_name script failed";
    $time_stamp = date("M j Y, g:i:sA T");    // e.g. Jan 3 2007, 2:34:12PM PST
    $message = "At $time_stamp, script $program_name at the PAUSATF website failed.\n" .
     "Full path to scrips is: $full_program_name\n\n" .
     "Error summary:\n$summary\n" .
     ($details ? "\nDetails: $details\n" : '');
    $from = "admin@pausatf.org";
    send_mail($to, $subject, $message, $from);
  }

  function send_mail($to, $subject, $message, $from, $cc='', $html=false, $test_mode=false) {
    // To send HTML mail, the Content-type header must be set
    $eol = "\r\n";
    $headers = '';
    $headers .= "From: $from" . $eol
         . "Reply-To: $from" . $eol;
    if($cc) {
        $headers .= "Cc: $cc" . $eol;
        // $to .= "," . $cc;
    }
    if($html) {
      $headers .= 'MIME-Version: 1.0' . $eol;
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . $eol;
    }
    $headers .= "X-Mailer: PHP/" . phpversion() . $eol;
    $log = "To: $to\nSubject: $subject\n$headers\n$message";
    if(!$test_mode) {
      // $log = str_replace($eol, "<br />\n", $log);
      // } else {
      $sent = mail($to, $subject, $message, $headers);
      // Following lines commented out by Jeff Teeters, on March 31, 2012
      // Reason: assume sent correctly.  For some reason, new server is flagging
      // error even though mail is sent
      // if(!$sent) {
      //   die("<pre>error sending mail:\n$log\n</pre>");
      // }
    }
    return($log);
  }



function mail_user($to, $subject, $message, $from, $html=false, $test_mode) {
  $from = "From: ${from}\nX-Mailer: PHP/" . phpversion();
  $sep = $test_mode ? "<br />\n" : "\n";
  if($html) {
    $from = 'MIME-Version: 1.0' . "\n" .
       'Content-type: text/html; charset=iso-8859-1' . "\n" .
       $from;
  }
  $log = "To: $to\nSubject: $subject\n$from\n$message\n";
  if($test_mode) {
    $log = str_replace("\n", "<br />\n", $log);
  } else {
    mail($to, $subject, $message, $from);
  }
  return($log);
}



  function log_to_file($msg) {
    // used only if unable to log_event (i.e. save event in database).
    $time_stamp = date("M j Y, g:i:sA T");
    $msg = $time_stamp . ": " . $msg . "\n";
    $program_name = $_SERVER['SCRIPT_FILENAME'];
    if(substr($program_name, -3) != 'php') {
      die("Program name does not have php extension: $program_name\n$msg");
    }
    $log_file = substr($program_name, 0, -3) . 'log';
    if(!($fh = fopen($log_file, 'a'))) {
      die("Unable to open " . $log_file . "\n$msg");
    }
    if(!fwrite($fh, $msg)) {
      die("Unable to write to file $log_file\n$msg");
    }
    fclose($fh);
  }

  function fail_sql($func, $sql) {
    global $mysqli;
    fail("Query failed in '$func'", "$sql\n" . ((is_object($GLOBALS["___mysql_ston"])) ? $mysqli->error($GLOBALS["___mysql_ston"]) : (($___mysql_res = $mysqli->connect_error()) ? $___mysql_res : false)));
  }

  function fail($summary, $details='') {
    log_event($summary, $details);
    email_error($summary, $details);
    die("<pre>\n$summary\n$details</pre>");
  }

main();

?>
