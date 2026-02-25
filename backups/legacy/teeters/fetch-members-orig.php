<?php
  /* $Id: fetch-members.php,v 1.6 2007/12/20 10:54:19 Owner Exp $ */

  /* Program to fetch usatf membership info from national USATF website
     and load it into local database table (pa_members).
     Written by Jeff Teeters, January 2007 */
  
  set_time_limit(10);  // limit to 10 seconds execution time.  Should be enough.
  error_reporting(E_ALL);


  /****
   *   To setup, must specify SECURE_DIR in a secure location (outside of webroot).
   *   It must contains the following include files:
   *   'db.php'  -  makes the data base handle ($dbh)
   *   'usatf-national-account.php  -- defines: USATF_ASSOCIATION, USAFT_NATIONAL_USER, USAFT_NATIONAL_PASSWORD
   **/
  define('SECURE_DIR', '/home/pausat/private/');
  require_once (SECURE_DIR . 'db.php');  // makes $dbh database handle
  require_once (SECURE_DIR . 'usatf-national-account.php');

  /****
   * Done with required customization
   ***/
  

  function fetch_page($url, $method='GET', $form_data = '') {
    $agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9";
    static $referer = '';
    static $cookies = array();
    if($form_data && $method=='GET') {    
      $url .= "?$form_data";
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    if($cookies) {
      curl_setopt($ch, CURLOPT_COOKIE,  implode(';', $cookies));
    }
    if($referer) {
      curl_setopt($ch, CURLOPT_REFERER, $referer);
    }
    if($method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, true);
      if($form_data) {    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $form_data);
      }
    }
    curl_setopt($ch, CURLOPT_HEADER, 1);  // needed to get cookies
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // inhibits output of returned page
    $content = curl_exec($ch); // fetch page
    if (curl_errno($ch)) {
      fail("fetch_page failed getting '$url'", "method = '$method'\nform_data='$form_data'\nError = " . 
        curl_error($ch));
    }
    curl_close ($ch);
    $referer = $url;  // save for next call
    $new_cookies = array();
    if(preg_match_all('|Set-Cookie: (.*);|U', $content, $results)) {
      $found_cookies = $results[1];
      foreach ($found_cookies as $found_cookie) {
        if(!in_array($found_cookie, $cookies)) {
          $new_cookies[] = $found_cookie;
          $cookies[] = $found_cookie;
        }
      }
    }
    // if($new_cookies) {  // for debugging cookies
    //   echo "<pre>page $url returned new cookies:\n" . implode('; ', $new_cookies) . "\n</pre>";
    // }
    return($content);
  }

  function advanced_search_form_data() {
    $membershipYears = get_membershipYears();
    $desired_years = join('&membershipYear=', $membershipYears);  // include more than one year if after november
    $form_data = "membershipNumber=&show_year=ON&" .
                 "membershipYear=$desired_years&clubNumber=&memberID=&militaryAffiliation=" .
                 "&firstName=&gender=&lastName=&citizenship=&birthDate_fromMonth=&birthDate_fromDay=" .
                 "&birthDate_fromYear=&birthDate_toMonth=&birthDate_toDay=&birthDate_toYear=&birthDateVerified=" .
                 "&ageReferenceDate=1%2F13%2F2007&city=&state=&zipcode=&email=&mailings=&paymentStatus=" .
                 "&registeredBy=&expiration_fromYear=&expiration_toYear=&dateApplied_fromMonth=" .
                 "&dateApplied_fromDay=&dateApplied_fromYear=&dateApplied_toMonth=&dateApplied_toDay=" .
                 "&dateApplied_toYear=&dateEntered_fromMonth=&dateEntered_fromDay=&dateEntered_fromYear=" .
                 "&dateEntered_toMonth=&dateEntered_toDay=&dateEntered_toYear=&show_ID=ON&show_membershipNumber=ON" .
                 "&show_firstName=ON&show_middleInitial=ON&show_lastName=ON&show_city=ON&show_state=ON&show_gender=ON" .
                 "&show_birthDate=ON&show_birthDateVerified=ON&show_club=ON&show_membershipCodes=ON" .
                 "&show_dateApplied=ON&sortOrder1=lastName" .
                 "&sortOrderDirection1=ASC&sortOrder2=firstName&sortOrderDirection2=ASC&sortOrder3=" .
                 "&sortOrderDirection3=ASC&submit=Download+Tab-Delimited+Text+File";
    return($form_data);
  }

  function field_names() {
    $field_names = 
              //   "ID	Year	Membership Number	First Name	MI	Last Name	Suffix	City	State	" .
                   "ID	Year	Membership #	First Name	MI	Last Name	Suffix	City	State	" .
                   "Gender	Birth Date	DOB Verified	Club Affiliation	Mem. Categories	Date Applied";
    return($field_names);
  }


  function strip_header(&$page) {
    if(false === ($start_location = strpos($page, field_names()))) {
      fail("Could not find field names in returned membership file.", "Returned file is:\n" . substr($page, 0, 5000) . "\n");
    }
    $page = substr($page, $start_location);
  }

  function update_db_param($param, $value) {
    $sql = "update db_status set value = '$value' where table_name = 'pa_members' and param = '$param'";
    $result = mysql_query($sql) or fail_sql('update_db_param', $sql);
  }

  function save_db_status() {
    // save membershipYear and current time in table db_status so can be displayed to user
    $membershipYear = get_membershipYear();
    update_db_param('membershipYear', $membershipYear);
    $current_time = date("M j Y, g:iA T");  //Jan 3 2007, 2:34PM PST
    update_db_param('last_update', $current_time);
  }

  function initialize_timezone() {
    putenv("TZ=US/Pacific");  // set php timezone
    $offset = date("O");  // Offset to GMT  like -0800
    $offset = substr($offset, 0, -2) . ":00";  // Convert to -08:00
    $sql = "SET time_zone = '$offset'";
    $result = mysql_query($sql) or fail_sql('initialize_timezone', $sql);
  }

  function get_membershipYear() {
    return(date('Y'));
  }

  function get_membershipYears() {
    static $membershipYears = array();
    if(!$membershipYears) {
      list($year, $month) = explode(' ', date('Y n'));
      $membershipYears[] = $year;
      if($month >= 11) {
        // if november or after, include next years members.  They are also valid for this year.
        $membershipYears[] = $year+1;
      }
    }
    return($membershipYears);
  }
   
  function &fetch_membrRpt_txt() {
    // the main function for getting the "membrRpt.txt" file from the national usatf website
    fetch_page('https://www.usatf.org/mgmt/assoc/');
    $form_data = 'associationNumber=' . USATF_ASSOCIATION
               . '&userID=' . USAFT_NATIONAL_USER
               . '&userPassword=' . USAFT_NATIONAL_PASSWORD
               . '&submit=I+agree+-+Log+in';
    fetch_page('https://www.usatf.org/mgmt/assoc/login.asp', 'POST', $form_data);  // logs in user
    // Following page not needed.  It would be fetched by a human following the links
    // fetch_page('https://www.usatf.org/mgmt/assoc/menu.asp');
    $page = fetch_page('https://www.usatf.org/membership/reports/index_advanced.asp'); // Advanced select form
    // get_membershipYear($page);  // called with page to parse out membership years selected
    $page = fetch_page('https://www.usatf.org/membership/reports/doReport.asp', 'GET', advanced_search_form_data());
    // $page = fetch_page('http://www.pausatf.org/teeters/echo-request.php', 'GET', advanced_search_form_data()); // for testing
    strip_header($page);
    $page_ref = &$page;
    return($page_ref);
  }

  function log_event($summary, $details) {
    $summary = mysql_real_escape_string($summary);
    $details = mysql_real_escape_string($details);
    $sql = "insert into db_event_log (table_name, event_time, summary, details) values\n" .
      "('pa_members', now(), '$summary', '$details')";
    $result = mysql_query($sql);
    if(!$result) {
      // can't insert into log table, save into file and die
      $msg = "log_event query failed:\n$sql\n" . mysql_error() . "\n";
      log_to_file($msg);
      email_notify($msg);
      die($msg);
    }
  }

  function email_notify($summary, $details='') {
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

  function send_mail($to, $subject, $message, $from, $html=false, $test_mode=false) {
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
    fail("Query failed in '$func'", "$sql\n" . mysql_error());
  }

  function fail($summary, $details='') {
    log_event($summary, $details);
    email_notify($summary, $details);
    die("<pre>\n$summary\n$details</pre>");
  }

  function two_digit($num) {
    // add leading zero if num is only one digit
    if(strlen($num) == 1) {
      $num = '0' . $num;
    }
    return($num);
  }

  function format_date(&$date, &$record) {
    // convert mm/dd/yyyy to yyyy-mm-dd
    if(!preg_match('#(\d+)/(\d+)/(\d\d\d\d)#', $date, $matches)) {
      fail("Invalid date ($date) in record.", "Record is:\n$record");
    }
    list($month, $day, $year) = array_slice($matches, 1);
    $date = $year . '-' . two_digit($month) . '-' . two_digit($day);
  }

  function format_time(&$time, &$record) {
    // convert "12/1/2006 1:38:01 PM" to "yyyy-mm-dd hh:mm:ss" format
    if(!preg_match('#(\d+)/(\d+)/(\d\d\d\d) (\d+):(\d+):(\d+) (AM|PM)#', $time, $matches)) {
      // invalid time format, maybe just has date
      if(!preg_match('#(\d+)/(\d+)/(\d\d\d\d)#', $time, $matches)) {
        fail("Invalid time ($time) in record.", "Record is:\n$record");
      }
      list($month, $day, $year) = array_slice($matches, 1);
      $time = $year . '-' . two_digit($month) . '-' . two_digit($day) . " 00:00:00";
    } else {
      list($month, $day, $year, $hour, $minute, $second, $ampm) = array_slice($matches, 1);
      if($ampm == 'PM') {
        $hour =+ 12;
      }
      $time = $year . '-' . two_digit($month) . '-' . two_digit($day) . ' ' . two_digit($hour) . ':' .
         two_digit($minute) . ':' . two_digit($second);
    }
  }

  function db_select1($sql) {
    // select and return only one value.  fail if not exactly one row in result
    $result = mysql_query($sql) or fail_sql('db_select1', $sql);
    if(($num_rows = mysql_num_rows($result)) != 1) {
      fail("db_select1 expected one row, found $num_rows: $sql");
    }
    $row = mysql_fetch_row($result);
    return($row[0]);  
  }


  function &get_national_recs() {
    // gets records from national database
    $testing = 0;
    if($testing) {
      $page =& fetch_test_members();
      strip_header($page);
    } else {
      $page =& fetch_membrRpt_txt();
    }
    $membershipYears = get_membershipYears();
    // used to make index to records
    $fields = array('id', 'membership_number', 'next_years_number', 'first_name', 'middle_initial', 'last_name',
        'suffix', 'city', 'state', 'gender', 'birth_date', 'DOB_Verified', 'club_affiliation', 'mem_categories', 'date_applied');
    $idx = array_flip($fields);
    $lines = explode("\n", $page);
    $records = array();
    for ($i = 1; $i < count($lines); $i++) {
      $line = $lines[$i];
      if(strlen($line) == 0) {
        continue;
      }
      list($id, $year, $membership_number, $first_name, $middle_initial, $last_name, $suffix, $city,
        $state, $gender, $birth_date, $DOB_Verified, $club_affiliation, $mem_categories, $date_applied) = explode("\t", $line);
      if($gender != 'M' && $gender != 'F') {
        fail("Invalid gender ($gender) in get_national_recs.", "Record is:\n$record");
      }
      format_date($birth_date, $record);
      format_time($date_applied, $record);
      $is_current_year = $year == $membershipYears[0];  // if false, then this record for next year's membership
      if(isset($records[$id])) {
        $saved_rec =&$records[$id];
        if($saved_rec[$idx['birth_date']] != $birth_date) {
          fail("Save record birth_date mismatch", "saved_rec=" . join("\t", $saved_rec) . "\nNew_rec: $line");
        }
        if($is_current_year) {
          // saved_rec should be next year
          if($saved_rec[$idx['next_years_number']] == '' || $saved_rec[$idx['membership_number']] != '') {
            fail("Save record not next year pattern", "saved_rec=" . join("\t", $saved_rec) . "\nNew_rec=%line");
          }
          $saved_rec[$idx['membership_number']] = $membership_number;
        } else {
          // saved_rec should be this year
          if($saved_rec[$idx['next_years_number']] != '' || $saved_rec[$idx['membership_number']] == '') {
            fail("Save record not this year pattern", "saved_rec=" . join("\t", $saved_rec) . "\nNew_rec=%line");
          }
          $saved_rec[$idx['next_years_number']] = $membership_number;
          // update some other fields in case they have changed
          $refresh_fields = array('first_name', 'middle_initial', 'last_name', 'suffix', 'city', 'state',
            'gender', 'birth_date', 'DOB_Verified', 'club_affiliation', 'mem_categories');
          foreach($refresh_fields as $field) {
            if($saved_rec[$idx[$field]] != $$field) {
              $saved_rec[$idx[$field]] = $$field;
            }
          }
        }
      } else {
        // New record
        if($is_current_year) {
          $next_years_number = '';
        } else {
          $next_years_number = $membership_number;
          $membership_number = '';
        }
        $records[$id] = array($id, $membership_number, $next_years_number, $first_name, $middle_initial, $last_name,
        $suffix, $city, $state, $gender, $birth_date, $DOB_Verified, $club_affiliation, $mem_categories, $date_applied);
      }
    }
    // computer roster counts here since have index to club_affiliation
    $roster_counts = array();
    foreach(array_keys($records) as $id) {
      $club_affiliation = $records[$id][$idx['club_affiliation']];
      $club_no = $club_affiliation == '' ? 0 : $club_affiliation;
      if(isset($roster_counts[$club_no])) {
        $roster_counts[$club_no]++;
      } else {
        $roster_counts[$club_no] = 1;
      }
    }
    update_roster_counts($roster_counts);
    $records_ref = &$records;
    return($records_ref);
  }

  function update_roster_counts(&$roster_counts) {
    $sql = 'delete from roster_counts';
    $result = mysql_query($sql) or fail_sql('update_roster_counts', $sql);
    $sql = 'insert into roster_counts (club_no) select club_no from tblCLUBS';
    $result = mysql_query($sql) or fail_sql('update_roster_counts', $sql);
    foreach ($roster_counts as $club_no => $roster_count) {
      $sql = "update roster_counts set roster_count = $roster_count where club_no = $club_no";
      $result = mysql_query($sql) or fail_sql('update_roster_counts', $sql);
    }
  }

  function &get_pa_recs() {
    // gets records in pausatf database
    $sql = "select id, membership_number, next_years_number, first_name, middle_initial, last_name, suffix, city,\n" .
        "state, gender, birth_date, DOB_Verified, club_affiliation, mem_categories, date_applied\n" .
        "from pa_members order by id";
    $result = mysql_query($sql) or fail_sql('get_pa_recs', $sql);
    $pa_recs = array();
    while($row = mysql_fetch_row($result)) {
      $id = $row['0'];
      $pa_recs[$id] = $row;
    }
    $pa_recs_ref =&$pa_recs; 
    return($pa_recs_ref);
  }


  function show_recs(&$ids, &$recs, &$recs2) {
    $output = '';
    foreach ($ids as $id) {
      $rec = implode("\t", $recs[$id]);
      if($recs2) {
        $rec2 = implode("\t", $recs2[$id]);
        $output .= "old: $rec\nnew: $rec2\n";
      } else {
        $output .= "$rec\n";
      }
    }
    return($output);
  }

  function make_actions_msg(&$removes, &$adds, &$changes, &$pa_recs, &$national_recs) {
    // make message describing any changes to data for storing in log
    $num_removes = count($removes);
    $num_adds = count($adds);
    $num_changes = count($changes);
    $empty = array();  // needed for pass by reference, third parameter of show_recs
    $actions = array();
    $details = array();
    if($num_removes > 0) {
      $actions[] = "Removed $num_removes";
      $details[] = "Removed:\n" . show_recs($removes, $pa_recs, $empty);
    }
    if($num_adds > 0) {
      $actions[] = "Added $num_adds";
      $details[] = "Added:\n" . show_recs($adds, $national_recs, $empty);
    }
    if($num_changes > 0) {
      $actions[] = "Changed $num_changes";
      $details[] = "Changed:\n" . show_recs($changes, $pa_recs, $national_recs);
    }
    $actions = implode(", ", $actions);
    $details = implode("\n", $details);
    return(array($actions, $details));
  }

  function update_db(&$national_recs, &$pa_recs) {
    $removes = array_keys(array_diff_assoc($pa_recs, $national_recs));
    $adds = array_keys(array_diff_assoc($national_recs, $pa_recs));
    $maybe_change = array_keys(array_intersect_assoc($national_recs, $pa_recs));
    $changes = array();
    foreach ($maybe_change as $id) {
      if(implode("\t", $national_recs[$id]) != implode("\t", $pa_recs[$id])) {
        $changes[] = $id;
      }
    }
    $all_removes = array_merge($removes, $changes);
    if($all_removes) {
      $sql = "delete from pa_members where id in ('" . implode("', '", $all_removes) . "')";
      $result = mysql_query($sql) or fail_sql('update_db', $sql);
    }
    $all_adds = array_merge($adds, $changes);
    if($all_adds) {
      $inserts = array();
      foreach ($all_adds as $id) {
        $rec = $national_recs[$id];
        $safe_rec = array_map("mysql_real_escape_string", $rec);
        $inserts[] = "(" . $safe_rec[0] . ", '" . implode("', '", array_slice($safe_rec, 1)) . "')";
      }
      $sql = "insert into pa_members\n" .
        "(id, membership_number, next_years_number, first_name, middle_initial, last_name, suffix, city, " .
        "state, gender, birth_date, DOB_Verified, club_affiliation, mem_categories, date_applied) values\n" .
        implode(",\n", $inserts);
      $result = mysql_query($sql) or fail_sql('update_db', $sql);
    }
    list($actions, $details) = make_actions_msg($removes, $adds, $changes, $pa_recs, $national_recs);
    $before_count = count($pa_recs);
    $expected_num_recs = count($national_recs);
    $found_num_recs = db_select1("select count(*) from pa_members");
    if($expected_num_recs != $found_num_recs) {
      fail("*** Count mismatch.  $before_count records before.  Did $actions. " .
          " Expected $expected_num_recs records after.  Found $found_num_recs.", $details);
    }
    // Everything seems ok.  Log event
    $summary = "$before_count records" .
          ($actions ? " before. $actions. $expected_num_recs records after." : ".  No changes.");
    log_event($summary, $details);
    save_db_status();  // update time for display on page
    echo "<pre>\n$summary\n$details\n</pre>\n";
  }

  function main() {
     initialize_timezone();
     $national_recs =& get_national_recs();
     $pa_recs =& get_pa_recs();
     update_db($national_recs, $pa_recs);
  }

  main();  // starts everything

  function &fetch_test_members() {
    // used to test parsing, without requiring fetch from national website
    // see function "get_national_recs" for how testing is done
    $test_members = "
page https://www.usatf.org/mgmt/assoc/ returned new cookies:
ASPSESSIONIDSAARBQTA=JBMPIJKAFIEJGGDMGMLAINLI

Got following page:

HTTP/1.1 200 OK
Server: Microsoft-IIS/5.0
Date: Sun, 14 Jan 2007 02:48:21 GMT
X-Powered-By: ASP.NET
content-disposition: attachment; filename=membrRpt.txt
Content-Type: attachment/download
Cache-control: private
Transfer-Encoding: chunked

ID	Membership Number	First Name	MI	Last Name	Suffix	City	State	Gender	Birth Date	DOB Verified	Club Affiliation	Date Applied
361764	4710951738	Carl	H	Aamodt		Fair Oaks	CA	M	09/04/1956		116	12/1/2006 1:38:01 PM
351428	4710559838	Joseph	A	Abbott		Reno	NV	M	03/03/1990	verified	32	6/26/2006 10:21:16 AM
34243	4710145638	Tyler	A	Abbott		Sf	CA	M	02/17/1961		113	1/19/2006
362869	4711212338	Samia		Adam		Santa Clara	CA	F	04/19/1996		321	12/30/2006 12:34:30 AM
319294	4711138038	Joseph	D	Adams	jr	Eureka	CA	M	04/18/1993		272	12/27/2006 10:53:22 AM
361809	4710959038	Sherilyn	L	Adams		San Francisco	CA	F	10/05/1965		196	12/2/2006 7:42:24 PM
314059	4710514338	Jeffrey	D	Adkins		Granite Bay	CA	M	01/15/1961	verified	195	10/20/2006 5:47:41 PM
14206	4710041738	Don		Adolf		Oroville	CA	M	12/30/1936			2/14/2006
357953	4710468238	Ole		Agesen		Palo Alto	CA	M	10/01/1965		117	11/1/2006 3:17:33 PM
300421	4710849338	Sydne	R	Aguilar		San Luis Obispo	CA	F	04/12/1996		126	11/19/2006 9:44:21 PM
357098	4710608338	Amihan	B	Agustin		Union City	CA	F	09/15/1996			10/26/2006 6:04:50 PM
27917	4710054038	David	K	Ahn		Sunnyvale	CA	M	01/28/1953		111	12/21/2005 4:47:17 PM
190994	4710308038	Jose	p	Aispuro		Freedom	CA	M	10/12/1961		111	1/17/2006
298399	4710457538	Berklee		Akutagawa		Manteca	CA	F	09/27/1977		195	10/26/2006 8:07:12 PM
358086	4710623238	Katherine	M	Albrecht		South Lake Tahoe	CA	F	04/16/1995		34	11/1/2006 8:36:29 PM
247723	4710387438	Roswitha	E	Albrecht		Emerald Hills	CA	F	10/06/1938		209	10/19/2006 11:29:46 AM
";

    return($test_members);
  }

?>
