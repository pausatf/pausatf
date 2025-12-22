<?php
  /* $Id: members.php,v 1.1 2007/03/07 17:52:37 Owner Exp $ */
  /* Script for showing PA members.  Written by Jeff Teeters, January 2007 */

// redirect to temporary site until this can be updated
header("Location: http://pausatf.x10host.com/data/members.php");
die();

  error_reporting(E_ALL);    
  require_once ('/var/www/legacy/private/db.php');  // connects to database

  function this_script_name() {
    $full_script_name = $_SERVER['SCRIPT_NAME'];  // e.g. /teeters/show-members.php
    $script_name = substr(strrchr($full_script_name, "/"), 1);  // e.g. show-members.php
    return($script_name);
  }

  function club_script_name() {
    // Name of companion script displaying club information
    return('clubs.php');
  }

  function get_db_param($param) {
    static $cache = array();
    if(isset($cache[$param])) {
      return $cache[$param];
    }
    $sql = "select value from db_status where table_name = 'pa_members' and param = '$param'";
    $result = mysql_query($sql) or fail_sql('get_db_param', $sql);
    $row = mysql_fetch_row($result);
    $value = $row[0];
    $cache[$param] = $value;
    return($value);  
  }

  function fail_sql($func, $sql) {
    die("<pre>Query failed in '$func'\n$sql\n" . mysql_error(). "</pre>");
  }

/***
                Ensure proper Proof of Birth has been received for youth athletes by checking for their name 
                in the PA/USATF Youth <a href="http://www.pausatf.org/PHP/DOB6.php">Membership Proof of Birth</a> database.
<font size=+1><B>YOUTH ROSTER for 2006</B></font><HR><a href="#Boys">Boys</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#Girls">Girls</a></CENTER><HR><font size=-1>
	
***/
  define('AGE_EXPLAIN_CSS', 'age_explain');
  define('CATEGORY_EXPLAIN_CSS', 'category_explain');

  function page_header($title, $sub_title='') {
    $last_update =  get_db_param('last_update');
    list($date, $time) = explode(', ', $last_update);
    $current_month = date('n');  // 11 or 12 for nov and dec
    $showing_renewals = $current_month >= 11;
    $html = 
'<html>
<head>
  <title>PA ' . $title . '</title>
  <link href="PAstylesheetpg2.css" type="text/css" rel="stylesheet">' . "\n" .
  popup_javascript() . make_category_css() . make_age_css() .
  ($showing_renewals ? custom_css('popupbox') : '') .
  // custom_css(AGE_EXPLAIN_CSS, -70, -40) .
  // custom_css(CATEGORY_EXPLAIN_CSS, 50, -90) . 
  // popup_css() .
    //    <script type='text/javascript' src='common.js'></script>
    //    <script type='text/javascript' src='css.js'></script>
    //    <script type='text/javascript' src='standardista-table-sorting.js'></script>" .
'</head>
<body style="max-width:890px;">
  <table border="0" cellspacing="0" cellpadding="0" width="100%">
      <tr>
        <td valign="top" width="20%">
          <font size="2"><a href="http://www.pausatf.org"><b>Home</b></a><br>
             <a href="http://www.pausatf.org/data/pacontacts.html"><b>Contacts</b></a></font></td>
        <td valign="top" style="text-align: center; width: 60%;">' . "\n" .
//'          <div style="font-weight: bold;color: blue;font-size: 170%;">' . $title . '</div>' . $sub_title . "\n" .
'          <h2>' . $title . '</h2>' . $sub_title . "\n" .
'        </td>
        <td width="20%" align="right" valign="top">As of ' . $time . '<br />' . $date . '</td>
      </tr>
      <tr>
        <td colspan="3" align="center" width="100%">
          <!-- hr width="80%" -->
          <table><tr><td style="text-align: left;">
          <!-- div style="text-align: left;"-->
            <!-- Content goes here -->
';  //      <div style="background-color: #cceeff; border: 1px solid black">

  return($html);
}

  function page_footer() {
    $html =
//'           </div>
'          </td></tr></table>
          <!-- /div -->
        </td>
      </tr>
  </table>
</body>
</html>
';
    return($html);
}

  function popup_javascript() {
  $str = <<<STRING
<script language="javascript" type="text/javascript">
<!--
function close_window(div_id) {
	 var el = document.getElementById(div_id);
         el.style.display = 'none';}
function open_window(div_id) {
	 var el = document.getElementById(div_id);
         el.style.display = 'block';}
function toggle(div_id) {
	 var el = document.getElementById(div_id);
	 if ( el.style.display == 'none' ) { el.style.display = 'block';}
	 else {el.style.display = 'none';}
}
// -->
</script>
STRING;
return $str;
}

  function make_css($class, $attributes) {
    $str = '<style type="text/css">' . "\n#$class {\n$attributes\n}\n</style>";
    return $str;
  }

  function make_category_css() {
     $attributes = "position:fixed;right:100px;top:150px;"
                 . "background-color:#dee2ed;padding: 10px;color:black;border:8px solid #667db3;";
     return make_css("categoryDiv", $attributes);
  }

  function make_age_css() {
     $attributes = "position:fixed;left:70px;top:150px;"
                 . "background-color:#dee2ed;padding: 10px;color:black;border:8px solid #667db3;";
     return make_css("ageDiv", $attributes);
  }


     
function make_popup_div($id, $text) {
  $html = "
<div id=\"$id\" style=\"display:none;\">
$text
<div style=\"text-align: center;\">
<a href=\"#\"  onclick=\"close_window('$id')\">Close</a>
</div>
</div>";
return($html);
}


  function custom_css($id, $x_offset=-40, $y_offset=-65) {
    // used to make hoover description of "08" heading
    // copied from: http://www.pmob.co.uk/temp/popupmessagecss3.htm#
    // http://www.pmob.co.uk/pob/disjointed1.htm
    $css = '
<style type="text/css">
.' . $id . ' a{position:relative;}/* set stacking context*/
.' . $id . ' a span{
visibility:hidden;/* hide message initially*/
position:absolute;
top:' . $y_offset . 'px;
left:' . $x_offset . 'px;
width:140px;
padding:8px;
background:#dee2ed;
color:black;
border:4px solid #667db3;
}
.' . $id . ' a:hover{visibility:visible}/* ie bug needed to make span show*/
.' . $id . ' a:hover span{visibility:visible;}/* reveal image*/
</style>
';
  return($css);
  }

  function member_header(&$oi) {
    $select_options = make_menu_select($oi);
    $html = "<CENTER>$select_options</CENTER><HR>\n";
    return($html);
  }

  function member_footer() {
    $membershipYear = get_db_param('membershipYear');
    $html = '<a name="page_bottom"></a>
* - Memberships shown above are valid for ' . $membershipYear . '.  Some of the application<br />
dates may be a year or more in the past. This is because these<br />
members renewed early or signed up for multi-year memberships.';
    return($html);
  }

  function get_club_info($club_no) {
    $sql = "select * FROM tblCLUBS where club_no = $club_no";
    $result = mysql_query($sql) or fail_sql('show_club', $sql);
    if(!($row = mysql_fetch_array($result))) {
      die("Could not find a club with number $club_no in the database.");
    }
    return($row);
  }

class Option_information {
  var $form_values = array();
  var $club_name = '';  // used for roster display
  var $page_title;
  var $number_found;  // number of records found
  // var $category_description;   // text describing category selected.  e.g. youth male
  // var $search_description;    // describes search (if a search was done)
  // used for selecting age from mysql birth_date
  var $mysql_age = "(YEAR(CURDATE())-YEAR(m.birth_date)) - (RIGHT(CURDATE(),5)<RIGHT(m.birth_date,5))";

  // List form vars and valid values for each.  First value is the default.
  // Last value may be a regular expression specifying a class of valid values
  var $form_vars = array(
      'age' => array('both', 'adult', 'youth'),
      'sex' => array('both', 'male', 'female'),
      'club' => array('none', 'unat', 'invalid', '#^[1-9]\d*$#'),  // last element is regex pattern,  e.g. a club_number for 'club' var
      'sort' => array('lname', 'fname', 'age', 'city', 'sex', 'club', 'usatf_no', 'reg_date'),
      'sdir' => array('up', 'down'),  // up == asc, down = desc
      'search_method' => array('no_search', 'like', 'exact'),
      'search_fname' => array('none', '#.+#'),  // allow any characters for names
      'search_lname' => array('none', '#.+#'),
      'search_city' => array('none', '#.+#'),
      'search_sex' => array('both', 'male', 'female'),
      'search_age_from' => array('none', '#\d+#'),  // must be digits
      'search_age_to' => array('none', '#\d+#'),
      'search_club_name' => array('none', '#.+#'),
      'search_unattached' => array('all', 'member', 'unattached', 'invalid'),
      'page' => array('1', 'all', '#\d+#'),
      'items_per_page' => array(200, '#\d+#')
   );

   function default_value($key) {
     // returns true if form value is the default value
     return($this->form_values[$key] == $this->form_vars[$key][0]);
   }

  function make_search_description() {
    $fv = &$this->form_values;
    if($fv['search_method'] == 'no_search') {
      return;
    }
    $found = array();
    if(!$this->default_value('search_fname')) {
      $found[] = "Fname=" . $fv['search_fname'];
    }
    if(!$this->default_value('search_lname')) {
      $found[] = "Lname=" . $fv['search_lname'];
    }
    if(!$this->default_value('search_city')) {
      $found[] = "city=" . $fv['search_city'];
    }
    if(!$this->default_value('search_club_name')) {
      $found[] = "club=" . $fv['search_club_name'];
    }
    if(!$this->default_value('search_sex')) {
      $found[] = $fv['search_sex']; // 'Male' : 'Female';
    }
    if(!$this->default_value('search_age_from') || !$this->default_value('search_age_to')) {
      if(!$this->default_value('search_age_from') && !$this->default_value('search_age_to')) {
        // from and too specified
        $found[] = "age " . $fv['search_age_from'] . " to " . $fv['search_age_to'];
      } elseif(!$this->default_value('search_age_from')) {
        // age_from only specified
        $found[] = "age >= " . $fv['search_age_from'];
      } else {
        // age_to only specified
        $found[] = "age <= " . $fv['search_age_to'];
      }
    }
    if(!$this->default_value('search_unattached')) {
      $found[] = $fv['search_unattached'] == 'member' ? 'club member' : $fv['search_unattached'];
    }
    $search_description = "Member search: " . implode(', ', $found);
    return($search_description);
  }


  function &get_search_filters() {
    $fv = &$this->form_values;
    $filters = array();
    if($fv['search_method'] != 'no_search') {
      if($fv['search_method'] == 'like') {
        $equal = 'like';
        $like_char = '%';
      } else {
        $equal = '=';
        $like_char = '';
      }
      if($fv['search_fname'] != 'none') {
        $filters[] = "m.first_name $equal '" . $fv['search_fname'] . "$like_char'";
      }
      if($fv['search_lname'] != 'none') {
        $filters[] = "m.last_name $equal '" . $fv['search_lname'] . "$like_char'";
      }
      if($fv['search_city'] != 'none') {
        $filters[] = "m.city $equal '" . $fv['search_city'] . "$like_char'";
      }
      if($fv['search_club_name'] != 'none') {
        $filters[] = "c.club_name $equal '" . $fv['search_club_name'] . "$like_char'";
               //     . " and c.paid = 'Y'";  // include if don't allow searching non-paid clubs
      }
      if($fv['search_sex'] != 'both') {
        $desired_sex = $fv['search_sex'] == 'male' ? 'M' : 'F';
        $filters[] = "m.gender = '$desired_sex'";
      }
      if($fv['search_age_from'] != 'none' && $fv['search_age_from'] != 0) {
        $filters[] = $this->mysql_age . " >= " . $fv['search_age_from'];
      }
      if($fv['search_age_to'] != 'none') {
        $filters[] = $this->mysql_age . " <= " . $fv['search_age_to'];
      }
      if($fv['search_unattached'] != 'all') {
        if($fv['search_unattached'] == 'invalid') {
          $filters[] = "c.club_no is NULL";
        } else {
          $desired_club_op = $fv['search_unattached'] == 'member' ? '<>' : '=';
          $filters[] = "m.club_affiliation $desired_club_op 0";  // field is alpha, mysql should convert '' to 0
        }
      }
    }
    return($filters);
  }

  function Option_information() {
    $this->get_form_values();
    if($this->form_values['club'] != 'none') {
      $this->get_club_name();
    }
  }

  function get_form_values() {
    foreach ($this->form_vars as $form_var => $possible_values) {
      if(isset($_REQUEST[$form_var]) && (($val = $_REQUEST[$form_var]) != '')) {
        $val = mysql_real_escape_string($val);  // only needed for real expressions, do for all anyway
        if(!in_array($val, $possible_values)  // not in possible_values
           // check for last possible_value is a regex pattern.  e.g. a club_number for 'club' var
           && !(preg_match('/^#.+\#/', $pattern = $possible_values[count($possible_values)-1])  // last possible_values is regex pattern
                 && preg_match($pattern, $val))) {  // matches regex pattern
           $first_pm = preg_match('/^#.+\#/', $pattern = $possible_values[count($possible_values)-1]);
           $second_pm = preg_match($pattern, $val);
           $debug = "pattern=$pattern\n" .
             "first pm=$first_pm, second_pm=$second_pm\n" .
             '!($first_pm && $second_pm) = ' . !($first_pm && $second_pm);
           die("<pre>Invalid value for $form_var ($val).  Must be one of: " . join(', ', $possible_values) . "\n$debug</pre>");
        }
      } else {
        $val = $possible_values[0];  // default value.
      }
      $this->form_values[$form_var] = $val;
    }
  }

  function &get_select_filters() {
    $age = &$this->mysql_age;
    $fv = &$this->form_values;
    $filters = $this->get_search_filters();
    if($fv['age'] != 'both') {
      $filters[] = $fv['age'] == 'youth' ? "$age < 18" : "$age > 17";
    }
    if($fv['sex'] != 'both') {
      $desired_sex = $fv['sex'] == 'male' ? 'M' : 'F';
      $filters[] = "m.gender = '$desired_sex'";
    }
    $club_specified = $fv['club'] != 'none';
    if($club_specified) {
      if($fv['club'] == 'invalid') {
        $filters[] = "c.club_no is NULL";
      } elseif($fv['club'] == 'unat') {
        $filters[] = "m.club_affiliation = 0";
      } else {
        $filters[] = "m.club_affiliation = " . $fv['club'];
      }
    }
    $filters = $filters ? "and " . implode("\nand ", $filters) : '';
    return($filters);
  }

  function get_page_number() {
    $page =  $this->form_values['page'];
    $items_per_page = $this->form_values['items_per_page'];
    if($page != 'all' && ($page - 1) * $items_per_page > $this->number_found) {
      // somehow page got too big, return smaller value
      $page = floor($this->number_found / $items_per_page) + 1;
    }
    return($page);
  }

  function &get_page_clause(&$filters) {
    $sql = "select count(*)\n" .
           "from pa_members m\n" .
           "left join roster_counts r on m.club_affiliation = r.club_no\n" .
           "left join tblCLUBS c on m.club_affiliation = c.club_no\n" .
           "where 1=1\n" .
        // join to clubs and roster tables removed so will count those that have invalid club number
        // "where m.club_affiliation = c.club_no AND m.club_affiliation = r.club_no\n" .
        // "from pa_members m, tblCLUBS c, roster_counts r\n" .
        // "where m.club_affiliation = c.club_no AND m.club_affiliation = r.club_no\n" .
           $filters;
    $result = mysql_query($sql) or fail_sql('get_page_clause', $sql);
    $row = mysql_fetch_row($result);
    $this->number_found = $row[0];
    $items_per_page = $this->form_values['items_per_page'];
    $page =  $this->get_page_number();
    if($this->number_found <= $items_per_page || $page == 'all') {
      $page_clause = ''; // will all fit on one page
    } else {
      $start_item = ($page - 1) * $items_per_page;
      $page_clause = "limit $start_item, $items_per_page";
    }
    return($page_clause);
  }

  function make_pagination() {
    $items_per_page = $this->form_values['items_per_page'];
    $number_found = &$this->number_found;
    if($number_found <= $items_per_page) {
      return('');  // will all fit on one page.  So no pagination
    }
    $page =  $this->get_page_number();
    if($page == 'all') {
      $page_link = $this->make_option_link('page', 1, "paginate", "Paginate");
      $pagination = " Displaying all ($page_link).";
    } else {
      $start_item = ($page - 1) * $items_per_page + 1;
      $end_item = min($start_item + $items_per_page - 1, $number_found);
      $number_of_pages = ceil($number_found / $items_per_page);
      $links = array();
      if($page > 2) {
        // first link
        $links[] = $this->make_option_link('page', 1, "<b>&lt;</b>", "First page");
      }
      if($page > 1) {
        // prev link
        $links[] = $this->make_option_link('page', $page - 1, "&lt;", "Previous page");
      }
      $links[] = "page $page of $number_of_pages";
      if($page < $number_of_pages) {
        // next link
        $links[] = $this->make_option_link('page', $page + 1, "&gt;", "Next page");
      }
      if($page < $number_of_pages - 1) {
        // last link
        $links[] = $this->make_option_link('page', $number_of_pages, "<b>&gt;</b>", "Last page");
      }
      $links[] = $this->make_option_link('page', 'all', 'all', "Show all");
      $links = implode(' ', $links);
      $pagination = " Displaying $start_item to $end_item ($links)";
    }
    return($pagination);
  }

  function &get_order_clause() {
    $fv = &$this->form_values;
    switch ($fv['sort']) {
      case 'lname':
        $order_fields = array('m.last_name', 'm.first_name');
        break;
      case 'fname':
        $order_fields = array('m.first_name', 'm.last_name');
        break;
      case 'age':
        // $order_fields = array($this->mysql_age, 'm.last_name', 'm.first_name');
        $order_fields = array('(now() - m.birth_date)');
        break;
      case 'sex':
        $order_fields = array('m.gender', 'm.last_name', 'm.first_name');
        break;
      case 'city':
        $order_fields = array('m.city', 'm.last_name', 'm.first_name');
        break;
      case 'usatf_no':
        $order_fields = array('membership_number');
        // $order_fields = array('m.membership_number');
        break;
      case 'reg_date':
        $order_fields = array('(now() - m.date_applied)');
        break;
      case 'club':
        $order_fields = array('c.club_name', 'm.last_name', 'm.first_name');
        break;
      default:
        die('Invalid sort specification: "' . $fv['sort'] . '"');
    }
    if ($fv['sdir'] == 'down') {
      $order_fields[0] .= ' desc';
    }
    $order_clause = "order by " . implode(', ', $order_fields);
    return($order_clause);
  }

  function get_club_name() {
    $club = $this->form_values['club'];
    if($club == 'unat') {
      $this->club_name = "Unattached";
    } elseif($club == 'invalid') {
      $this->club_name = "Invalid";
    } else {
      $row = get_club_info($club);
      $this->club_name = make_lower_case_name($row['club_name']);
    }
  }

  function make_self_link($tag, $display_text, $title='') {
    // used for making link to footnote
    $parms = array();
    foreach ($this->form_values as $form_var => $val) {
      if($this->form_vars[$form_var][0] != $val) {
        // not default, include it
        $parms[] = "$form_var=$val";
      }
    }
    $plist = implode('&', $parms);
    if($plist) {
      $plist = "?$plist";  // put ? at front if present
    }
    $title_attribute = $title ? " title=\"$title\"" : '';
    $tag_part = $tag ? "#$tag" : '';
    $this_script_name = this_script_name();
    $link = "<a href=\"$this_script_name$plist$tag_part\"$title_attribute>$display_text</a>";
    return($link);
  }

  function make_option_link($changing_var, $new_value, $fixed_title='', $hover_description='') {
    $current_value = $this->form_values[$changing_var];
    $title = $fixed_title ? $fixed_title : ucfirst($new_value);
    if($new_value == $current_value && $changing_var != 'sort') {
      $link = "<strong>$title</strong>";
    } else {
      $parms = array();
      if($new_value == $current_value && $changing_var == 'sort') {
        if($this->form_values['sdir'] == 'up') {
          $title .= '&nbsp;&uarr;';
          $parms[] = "sdir=down";
        } else {
          $title .= '&nbsp;&darr;';
          // $parms[] = "sdir=up";  // not needed, is default
        }
      }
      foreach ($this->form_values as $form_var => $val) {
        if($form_var == 'sdir') {
          continue;  // do nothing since sdir values set above
        }
        if($form_var == $changing_var) {
          $val = $new_value;
        }
        if($this->form_vars[$form_var][0] != $val) {
          // is not default, must include it
          $parms[] = "$form_var=$val";
        }
      }
      $plist = implode('&', $parms);
      if($plist) {
        $plist = "?$plist";  // put ? at front if present
      }
      $desc = $hover_description ? " title=\"$hover_description\"" : '';
      $this_script_name = this_script_name();
      $link = "<a href=\"$this_script_name$plist\"$desc>$title</a>";
    }
    return($link);
  }

  function make_category_description() {
    // returns selected categories.  e.g. adult, male.  empty if none selected.
    $cats = array();
    foreach (array('age', 'sex') as $key) {
      if(!$this->default_value($key)) {
        $cats[] = $this->form_values[$key];
      }
    }
    $category_description = implode(' ', $cats);
    return($category_description);
  }

  function make_category_title($category_description) {
    // makes title corresponding to category and/or club selected.  Cases are:
    // 1. no options, no club (everything)
    //    All members
    //    ^^^ -- returned if everything selected.
    // 2. options selected, no club.
    //   [adult female] members
    //    ^^ == category_description
    // 3. club selected, no options
    //   [Pacific Striders] roster
    //    ^^ == club        
    // 4. club and options selected.
    //   [Pacific Striders] ([adult male]) roster
    //    ^^ == club          ^^ == category_description
    // $membershipYear = get_db_param('membershipYear');  // not used anymore.  Assume == update year
    $category_title_case = ($this->club_name != '') * 2 + ($category_description != '') + 1;
    switch ($category_title_case) {
      case 1:    // 1. no options, no club (everything)
        $category_title = "All members";
        break;
      case 2:    // 2. options selected, no club.
        $category_title = $category_description . " members";
        break;
      case 3:    // 3. club selected, no options
        $category_title = $this->club_name . " roster";
        break;
      case 4:    // 4. club and options selected.
        $category_title = $this->club_name . " (" . $category_description . ") roster";
        break;
      default:
        die("Invalid category_title_case $category_title_case");
    }
    // previously appended ", year $membershipYear";  Not needed.
    return($category_title);
  }

  function make_page_title() {
    $search_description = $this->make_search_description();
    $category_description = $this->make_category_description();
    if($search_description) {
       $title = $search_description;
       if($category_description) {
         $title .= "; $category_description";
       }
    } else {
       $title = ucfirst($this->make_category_title($category_description));
    }
    return($title);
  }
}  // end of class Option_information

  function make_search_select() {
    // menu options displayed at top of search page
    $club_script_name = club_script_name();
    $this_script_name = this_script_name();
    $club_list= "<a href=\"$club_script_name\">Club list</a>";
    $all_adult = "<a href=\"$this_script_name?age=adult\">All adult</a>";
    $all_youth = "<a href=\"$this_script_name?age=youth\">All youth</a>";
    $search_select = "[ $club_list | $all_adult | $all_youth ]";
    return($search_select);
  }

  function make_menu_select(&$oi) {
    // menu options displayed at top of list of members
    $op_adult = $oi->make_option_link('age', 'adult');
    $op_youth = $oi->make_option_link('age', 'youth');
    $op_all_ages = $oi->make_option_link('age', 'both');
    $op_male = $oi->make_option_link('sex', 'male');
    $op_female = $oi->make_option_link('sex', 'female');
    $op_all_sexes = $oi->make_option_link('sex', 'both');
    $age_select = "[ $op_adult | $op_youth | $op_all_ages ]";
    $sex_select = "[ $op_male | $op_female | $op_all_sexes ]";
    $spacer = " &nbsp; &nbsp; ";
    $menu_select = "$sex_select$spacer$age_select";
    $this_script_name = this_script_name();
    $club_script_name = club_script_name();
    $search = "<a href=\"$this_script_name?cmd=search\">Search</a>";
    if(($club_no = $oi->form_values['club']) != 'none' && $club_no != 'unat') {
      // menu displayed when club specified (showing club roster)
      $club_info = "<a href=\"$club_script_name?club_no=$club_no\">Club info</a>";
      $club_list= "<a href=\"$club_script_name\">Club list</a>";
      $all_adult = "<a href=\"$this_script_name?age=adult\">All adult</a>";
      $all_youth = "<a href=\"$this_script_name?age=youth\">All youth</a>";
      // $clubs_select = "[ $club_info | $club_list | $all_adult | $all_youth ]";
      $clubs_select = "[$club_info|$club_list|$all_adult|$all_youth|$search]";
    } elseif($oi->form_values['search_method'] != 'no_search' || $club_no == 'unat') {
      // menu displayed when search results shown, or unattached roster
      $club_list= "<a href=\"$club_script_name\">Club list</a>";
      $all_adult = "<a href=\"$this_script_name?age=adult\">All adult</a>";
      $all_youth = "<a href=\"$this_script_name?age=youth\">All youth</a>";
      $clubs_select = "[$club_list|$all_adult|$all_youth|$search]";
    } else {
      // menu displayed when normal list of members shown
      $club_list= "<a href=\"$club_script_name\">Club list</a>";
      $clubs_select = "[$club_list|$search]";
    }
    $menu_select .= "$spacer$clubs_select";
    return($menu_select);
  }

  function make_lower_case_name($name) {
    return $name; // 2011-03-09: Just leave as in database tables.  Mod by Jeff Teeters.
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

  function get_lower_case_club_name($club_no, $club_name) {
    static $cache=array();
    if(!isset($cache[$club_no])) {
      $cache[$club_no] = make_lower_case_name($club_name);
    }
    return($cache[$club_no]);
  }

  function get_category_defs() {
     $str = '
     <b style="color: blue;">USAFT Membership codes:</b><br />
     AT - Athlete<br />
     DA - Disabled Athlete<br />
     CH - Coach, Uncertified<br />
     CD - Coach, Developmental certified<br />
     C1 - Coach, Level 1 certified<br />
     C2 - Coach, Level 2 certified<br />
     C3 - Coach, Level 3 certified<br />
     PA - Parent<br />
     OF - Official, Uncertified<br />
     OA - Official, Association<br />
     ON - Official, National<br />
     OM - Official, Master<br />
     AD - Administrator<br />
     FN - FAN<br />
     <!-- a href="www.usatf.org/MEMBERSHIP/application/forms/USATFMembershipApplication.pdf">Reference</a -->
     ';
     return($str);
  }

  function get_age_explain() {
     $html = "<div style=\"text-align:center;\"><b style=\"color: blue;\">Age Column:</b></div>\n"
            . "If the age is followed by a 'v'<br />\n"
            . "then the date of birth has been<br />\n"
            . " verified, otherwise it has not.<br />\n"
	    . "Examples:\n<br />\n"
            . "&nbsp;&nbsp;27v&nbsp;-- DOB Verified.<br />\n"
            . "&nbsp;&nbsp;34&nbsp;&nbsp;-- DOB not verified.<br />\n";
     return($html);
  }


  function cmd_members() {
    // the main routine for displaying list of members
    $this_script_name = this_script_name();
    $club_script_name = club_script_name();
    $oi = new Option_information();
    $age = "(YEAR(CURDATE())-YEAR(m.birth_date)) - (RIGHT(CURDATE(),5)<RIGHT(m.birth_date,5))";
    $applied_over_year_ago = "DATE_ADD(m.date_applied, INTERVAL 1 YEAR) < CURDATE()";
    $fv = &$oi->form_values;
    $filters = &$oi->get_select_filters();
    $order_clause = &$oi->get_order_clause();
    $page_clause = &$oi->get_page_clause($filters);
    list($year, $month) = explode(' ', date('Y n'));  // returns format like: '2009' '11'
    $showing_renewals = $month >= 11;
    if($showing_renewals) {
      $renewed_clause = "if(m.next_years_number != '', 'Y', '') as renewed,\n";
      $next_year = $year + 1;
      $next_year_dd = $next_year % 100;         // e.g. 9 (for 2009) or 12 (for 2012)
      $next_year_dd = $next_year_dd < 10 ? '0' . $next_year_dd : $next_year_dd;  // add leading zero if necessary
      $renewal_explain = "'Y' after USATF# means<br />has " . $next_year . " membership";
      $next_year_header = "$next_year_dd<a href=\"#\"><sup>?</sup><span>$renewal_explain</span></a>"; 
    } else {
      $renewed_clause = '';
    }
    $sql = // "select concat(m.first_name, ' ', m.middle_initial, ' ', m.last_name, ' ', m.suffix) as name,\n" .
           "select trim(concat(m.first_name, ' ', m.middle_initial)) as first_name, trim(concat(m.last_name, ' ', m.suffix)) as last_name,\n" .
           $oi->mysql_age . "  as age,\n" .
	   "DOB_Verified,\n" .
           "if(m.membership_number != '', m.membership_number, m.next_years_number) as membership_number,\n" .
           $renewed_clause .
           "m.club_affiliation as club_no, c.club_name,\n" .
           "c.approved, r.roster_count, m.city,\n" .
           "m.gender, m.mem_categories,\n" .
           "date_format(m.date_applied, '%m/%d/%y') as date_applied,\n" .
           "$applied_over_year_ago as applied_over_year_ago\n" .
           "from pa_members m\n" .
           "left join roster_counts r on m.club_affiliation = r.club_no\n" .
           "left join tblCLUBS c on m.club_affiliation = c.club_no\n" .
           "where 1=1 " . $filters . "\n" .
           $order_clause ."\n" .
           $page_clause;
    $result = mysql_query($sql) or die("<pre>\nquery failed: " . mysql_error() . " \n" . $sql . "\n</pre>\n");
    $club_specified = $fv['club'] != 'none' && $fv['club'] != 'invalid';
    if($club_specified) {
      $club_heading = '';  // if club specified, showing club roster.  Don't display club names
    } else {
      $sort_club = $oi->make_option_link('sort', 'club', 'Club');
      $club_heading = "<td>$sort_club</td>\n";
    }
    $sort_fname = $oi->make_option_link('sort', 'fname', 'First');
    $sort_lname = $oi->make_option_link('sort', 'lname', 'Last');
    $sort_sex = $oi->make_option_link('sort', 'sex', 'Sex');
    $sort_age = $oi->make_option_link('sort', 'age', 'Age');
    $sort_city = $oi->make_option_link('sort', 'city', 'City');
    $sort_usatf_no = $oi->make_option_link('sort', 'usatf_no', 'USATF#');
    $sort_reg_date = $oi->make_option_link('sort', 'reg_date', 'Registered');
    $need_footnote = 0;
    $table_body = '';
    $number_found = $oi->number_found;  // total number of records found
    while($row = mysql_fetch_array($result)) {
      // echo "<pre>\n" . print_r($row, true) . "</pre>\n";
      if($row['applied_over_year_ago']) {
        $need_footnote = 1;
      }
      if($club_specified) {
        if(strtoupper($row['approved']) != 'Y' && $row['club_no'] != 0) {
          // user is attempting to view roster of club that is not paid.  Show no records
          $number_found = 0;
          break;
        }
        $club_column = "";
      } else {
        $club_no = $row['club_no'];
        if(!$club_no) {
          $club_field = '&nbsp;';
        } else {
          // if club_name is empty, then it's an invalid club.  Just show the number.
          $club_name = $row['club_name'] != '' ? get_lower_case_club_name($club_no, $row['club_name']) : $club_no;
          if(strtoupper($row['approved']) != 'Y') {
            // is member of a non-paid club
            $club_field = "$club_name<a href=\"$club_script_name?club_no=$club_no\">?</a>";
          } else {
            $club_name_link = "<a href=\"$club_script_name?club_no=" . $club_no . '">' . $club_name . "</a>";
            $roster_link = "<a href=\"$this_script_name?club=" . $club_no . '">' . $row['roster_count'] . "</a>";
            $club_field = "$club_name_link&nbsp;($roster_link)";
          }
        }
        $club_column = "<td>" . $club_field . "</td>";
      }
      $renewal_column = $showing_renewals ? "<td>$row[renewed]</td>" : '';
      // old style:
      // $age = $row['DOB_Verified'] == 'verified' ? "<b><u>$row[age]</u></b>" : $row['age'];  // underline those with DOB verified.
      $age = $row['DOB_Verified'] == 'verified' ? "$row[age]v" : $row['age'];  // append v if DOB verified.
      $table_body .= 
        "<tr>" .
		"<td>" . $row['first_name']. "</td>" .
		"<td>" . $row['last_name']. "</td>" .
		"<td>" . $row['gender']. "</td>" .
		"<td>" . $row['city'] . "</td>" .
		"<td>" . $age . "</td>" .
		"<td>" . $row['membership_number'] . "</td>" .
        $renewal_column .
        "<td>" . $row['mem_categories'] . "</td>" .
        $club_column .
		"<td>" . $row['date_applied'] . "</td>" .
		"</tr>\n";
    }
    $footnote = $need_footnote ? "&nbsp;" . $oi->make_self_link('page_bottom', '*', 'See note at bottom of page.') : '';
//    $age_explain = "<span class=\"" . AGE_EXPLAIN_CSS . "\"><a href=\"#\"><sup>?</sup><span><b><u>Bold</u></b> means date of birth verified.</span></a></span>"; 
//    $category_explain = "<span class=\"" . CATEGORY_EXPLAIN_CSS . "\"><a href=\"#\"><sup>?</sup><span>" . get_category_defs() .
//         "</span></a></span>";

    $age_explain = "<a href=\"#\" onMouseOver=\"open_window('ageDiv')\"><sup>?</sup></a>";
    $category_explain = "<a href=\"#\" onMouseOver=\"open_window('categoryDiv')\"><sup>?</sup></a>";

    $sort_age .= $age_explain;
    $renewal_heading = $showing_renewals ? "<td class=\"popupbox\">$next_year_header</td>\n" : '';
    $member_list = make_popup_div("categoryDiv", get_category_defs()) .
                   make_popup_div("ageDiv", get_age_explain()) .
		"<center>\n" .
		"<table border=0 cellspacing=1 cellpadding=1>\n" .
		"<thead>\n" .
		"<tr>\n" .
		"<td>$sort_fname</td>\n" .
		"<td>$sort_lname</td>\n" .
		"<td>$sort_sex</td>\n" .
		"<td>$sort_city</td>\n" .
		"<td>$sort_age</td>\n" .
		"<td style=\"text-align:center;\">$sort_usatf_no</td>\n" .
        $renewal_heading .
        "<td>Category$category_explain</td>\n" .
        $club_heading .
		"<td>$sort_reg_date$footnote</td>\n" .
		"</tr>\n" .
		"</thead>\n" .
		"<tbody>$table_body" .
		"</tbody></table>\n" . ($need_footnote ? member_footer() : '') .
		"</center>\n";
    $page_title = $oi->make_page_title();
    $sub_title = "$number_found found." . $oi->make_pagination();
    $output = page_header($page_title, $sub_title) .
              member_header($oi) .
              ($number_found > 0 ?  $member_list : '<center><strong>No records found</strong></center>') . 
              // "<pre>\ndebug sql=\n" . $sql . "\n</pre>\n" .
			  page_footer();
    return($output);
  }

  function cmd_search() {
    // displays search form page
    $this_script_name = this_script_name();
    $search_select = make_search_select();
    $form = '
<center>
' . $search_select . '<br />
<form method="post" action="' . $this_script_name. '" onsubmit="return search_form_validator(this)" name="search_form" language="JavaScript">
<table>
   <tr><td>First name</td><td>Last name</td><td>City</td><td>Club</td></tr>
   <tr><td><input size="8" name="search_fname"></td><td><input size="8" name="search_lname"></td><td><input size="8" name="search_city"></td><td><input size="8" name="search_club_name"></td></tr>
   <tr><td colspan=4>Search method: <input type="radio" name="search_method" value="like" checked>Starts with | <input type="radio" name="search_method" value="exact">Exact match</td></tr>
   <tr><td colspan=4>Sex: <input type="radio" name="search_sex" value="male">Male | <input type="radio" name="search_sex" value="female">Female | <input type="radio" name="search_sex" value="both" checked>Both</td></tr>
   <tr><td colspan=4>Age: <input size=2 name="search_age_from" value=""> to <input size=2 name="search_age_to" value=""></td></tr>
   <tr><td colspan=4>Club: <input type="radio" name="search_unattached" value="member">Member | <input type="radio" name="search_unattached" value="unattached">Unattached | <input type="radio" name="search_unattached" value="invalid">Invalid | <input type="radio" name="search_unattached" value="all" checked>All</td></tr>
   <tr><td colspan=4 align="center"><input type="SUBMIT" value="Search" style="font-family: Arial; font-size: 8pt"> &nbsp; <input type="reset" value="Reset" name="B1" style="font-family: Arial; font-size: 8pt"></td></tr>
</table>
</form>
</center>
';
    $output = page_header("Search members") .
              $form .
              page_footer();
    return($output);
  }

  function main() {
    // if cmd=search, do search, otherwise display members
    $cmd = isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'search' 
           ? 'cmd_search' 
           : 'cmd_members';
    $output = $cmd();
    echo $output;
  }

  // call the main function to start everything
  main();
?>
