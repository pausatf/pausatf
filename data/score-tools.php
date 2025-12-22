<?php
  /* $Id: members.php,v 1.1 2007/03/07 17:52:37 Owner Exp $ */
  /* Script to help with scoring races.  Written by Jeff Teeters, January 2007 */

// redirect to temporary site until this can be updated
header("Location: http://pausatf.x10host.com/data/score-tools.php");
die();

  error_reporting(E_ALL);    
  // define('SECURE_DIR', '/var/www/html/pausatf/private/');
  // require_once (SECURE_DIR . 'db.php');  // makes $dbh database handle
  require_once ('/var/www/legacy/private/db.php');  // connects to database

  function this_script_name() {
    $full_script_name = $_SERVER['SCRIPT_NAME'];  // e.g. /teeters/show-members.php
    $script_name = substr(strrchr($full_script_name, "/"), 1);  // e.g. show-members.php
    return($script_name);
  }

  function get_request($key) {
    return (isset($_REQUEST[$key]) ? $_REQUEST[$key] : '');
  }

  function display_options() {
     $script_name = this_script_name();
     $output = <<<EOT
<html>
<head><title>Scoring tools</title></head>
<body>
<h2>PAUSATF race scoring utilities</h2>
<p>
Click on one of the following to run that utility.
</p>
<ul>
<li><a href="$script_name?cmd=download_members">Download members list</a></li>
</ul>
</body>
</html>
EOT;
    echo $output;
  }


function get_score_members() {
    $output = "ID	Membership #	First Name	MI	Last Name	Suffix	Ci" .
              "ty 	State	Gender	Birth Date	DOB Verified	Club Affiliation	Date Applied\n";

    $sql = "select m.id,\n" .
           "if(m.membership_number != '', m.membership_number, m.next_years_number) as membership_number,\n" .
           "m.first_name, m.middle_initial, m.last_name, m.suffix,\n" .
           "m.city,\n" .
           "m.state,\n" .
           "m.gender,\n" .
           "date_format(m.birth_date, '%m/%d/%Y') as birth_date,\n" .
	   "DOB_Verified,\n" .
           "m.club_affiliation,\n" .
           "date_format(m.date_applied, '%m/%d/%Y %h:%i:%s %p') as date_applied\n" .
           "from pa_members m\n" .
           "order by m.last_name, m.first_name, m.middle_initial";
    $result = mysql_query($sql) or die("<pre>\nquery failed: " . mysql_error() . " \n" . $sql . "\n</pre>\n");
    while($r = mysql_fetch_array($result)) {
      $output .= "$r[id]\t$r[membership_number]\t$r[first_name]\t$r[middle_initial]\t$r[last_name]\t$r[suffix]\t" .
           "$r[city]\t$r[state]\t$r[gender]\t$r[birth_date]\t$r[DOB_Verified]\t$r[club_affiliation]\t$r[date_applied]\n";
    }
    return $output;
}



function download_members() {
  $members = get_score_members();
  $mem_len = strlen($members);
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header('Content-disposition: attachment; filename=onlinedownload.txt');
  header("Content-Type: text/plain");
  // header("Content-Transfer-Encoding: binary");
  header('Content-Length: '. $mem_len);
  echo $members;
}


  function fail_sql($func, $sql) {
    die("<pre>Query failed in '$func'\n$sql\n" . mysql_error(). "</pre>");
  }


  function main() {
    // if cmd specified, run it.  Otherwise show default screen
    $cmd = get_request('cmd');
    switch ($cmd) {
        case "download_members":
            download_members();
            break;
        case "":
            display_options();
            break;
        default:
            die("<strong>Unknown command: '$cmd'</strong>");
    }
  }

  // call the main function to start everything
  main();
?>
