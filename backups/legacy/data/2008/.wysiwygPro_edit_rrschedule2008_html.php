<?php header("Content-type","text/html; charset=iso-8859-1"); ?>
<?php ob_start() ?>
<?php
if ($_GET['randomId'] != "ZAf70PHCPoAKhb3HBblcurstMPlWwzI8MGElk8_xzB5yOJN7bOWalRKf1EyskpO_F66aEDS2y_cOhJJH75_gsBpzLEi2hftHBy8rUGTZoWBhqKKPempOJIxt849cQOOAkgxAtoUJDnVmJpzGPiQxkScauGAKCweAldpTznZn_GKn4azlm8mUiRIpdhsuug764CY8Rk_JVYKNXlrrfbMehz62RoYNsZMhyAG4uXsopOgw7dPBnEN2Frqe2NBMMP60") {
    echo "Access Denied";
    exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing rrschedule2008.html</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">body {background-color:threedface; border: 0px 0px; padding: 0px 0px; margin: 0px 0px}</style>
</head>
<body>
<div align="center">
<script language="javascript">
<!--//
// this function updates the code in the textarea and then closes this window
function do_save() {
	var code =  htmlCode.getCode();
	document.open();
	document.write('<html><form METHOD="POST" name=mform action="http://www.pausatf.org:2082/frontend/rvblue/filemanager/savehtmlfile.html"><input type="hidden" name="udir" value="/home/pausat/public_html/data/2008"><input type="hidden" name="ufile" value="rrschedule2008.html"><input type="hidden" name="dir" value="%2fhome%2fpausat%2fpublic_html%2fdata%2f2008"><input type="hidden" name="file" value="rrschedule2008.html"><input type="hidden" name="doubledecode" value="1">Saving&nbsp;....<br /><br ><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><textarea name=page rows=1 cols=1></textarea></form></html>');
	document.close();
	document.mform.page.value = code;
	document.mform.submit();
}
function do_abort() {
	var code =  htmlCode.getCode();
	document.open();
	document.write('<html><form METHOD="POST" name="mform" action="http://www.pausatf.org:2082/frontend/rvblue/filemanager/aborthtmlfile.html"><input type="hidden" name="dir" value="/home/pausat/public_html/data/2008"><input type="hidden" name="file" value="rrschedule2008.html">Aborting Edit&nbsp;....</form></html>');
	document.close();
	document.mform.submit();
}
//-->
</script>
<?php
// make sure these includes point correctly:
include_once ('/usr/local/cpanel/base/3rdparty/WysiwygPro/editor_files/config.php');
include_once ('/usr/local/cpanel/base/3rdparty/WysiwygPro/editor_files/editor_class.php');

// create a new instance of the wysiwygPro class:
$editor = new wysiwygPro();

// add a custom save button:
$editor->addbutton('Save', 'before:print', 'do_save();', WP_WEB_DIRECTORY.'images/save.gif', 22, 22, 'undo');

// add a custom cancel button:
$editor->addbutton('Cancel', 'before:print', 'do_abort();', WP_WEB_DIRECTORY.'images/cancel.gif', 22, 22, 'undo');

$body = '<HTML>
<HEAD>
<TITLE>Tentative 2008 PA/USATF ROAD RACE GRAND PRIX SCHEDULE</TITLE>
<STYLE>
A {
	text-decoration: none;
	color: #00f;
}
A:hover {
	text-decoration: underline;
	color:#c00;
}
</STYLE>
<link rel="stylesheet" href="/PAstylesheet.css" type="text/css">
</HEAD>
<BODY BGCOLOR="#ffffff">
<CENTER>
  <IMG SRC="http://www.pausatf.org/images/HeaderRR.gif" ALIGN="Middle" WIDTH="400" HEIGHT="70"><BR>
  <H1>Tentative</H1>
  <H3>2008 PA/USATF ROAD RACE GRAND PRIX SCHEDULE</H3>
  <HR>
  <I>Click on the Race\'s name to view the race\'s web site</I><BR>
  <TABLE border="20">
    <TR>
      <TH>Date</TH>
      <TH>Race</TH>
      <Th>Location</TH>
      <TH>Points</TH>
      <TH>Short/long</TH>
      <TH>Prize Money</TH>
    </TR>
    <TR>
      <TD>03/01</TD>
      <TD><a href="http://www.sweatrc.com/">NorCal John Frank Memorial 10-Mile</a></TD>
      <TD>Redding</TD>
      <TD>1.0</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2005.asp?Race_Number=1">$2650</a></TD>
    </TR>
    <TR>
      <TD>03/16</TD>
      <TD><a href="http://www.rhodyco.com">Emerald Across the Bay 12K</a></TD>
      <TD>San Francisco </TD>
      <TD>1.5</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=2">$4650</a></TD>
    </TR>
    <TR>
      <TD>04/20</TD>
      <TD><a href="http://www.tkecapital.com/hoys-excelsior/zippy/index.htm">New Balance Excelsior Zippy 5K</a></TD>
      <TD>San Francisco </TD>
      <TD>1.0</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=3">$2650</a>*</TD>
    </TR>
    <TR>
      <TD>04/27</TD>
      <TD><a href="http://www.bsim.org/site3.aspx/">Big Sur 5K</a></TD>
      <TD>Carmel</TD>
      <TD>2.0</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=4">$5300</a></TD>
    </TR>
    <TR>
      <TD>05/26</TD>
      <TD><a href="http://www.tamalparunners.org/">Marin Memorial Day 10K</a></TD>
      <TD>Kentfield</TD>
      <TD>1.0</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=5">$2650</a></TD>
    </TR>
    <TR>
      <TD>06/01 (may move to July)</TD>
      <TD><a href="http://www.fleetfeetsacramento.com/don-bowden-mile">Don Bowden Mile</a></TD>
      <TD>Stockton</TD>
      <TD>1.0</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=6">$2650</a></TD>
    </TR>
    <TR>
      <TD>06/21</TD>
      <TD><a href="

http://www.fleetfeetsacramento.com/shriners-8km-and-5km">Shriners 8K</a></TD>
      <TD>Sacramento</TD>
      <TD>1.0</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=7">$2650</a></TD>
    </TR>
    <TR>
      <TD>09/21</TD>
      <TD><a href="http://www.rhodyco.com">Banana Chase 5K</a></TD>
      <TD>San Francisco</TD>
      <TD>1.5</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=8">$5500</a></TD>
    </TR>
    <TR>
      <TD>10/05</TD>
      <TD><a href="http://www.rnrsj.com/home.html">Rock N Roll Half Marathon</a></TD>
      <TD>San Jose</TD>
      <TD>1.5 or 2.0 (TBD)</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=9">$4000 or $5300 (TBD)</a>*</TD>
    </TR>
    <TR>
      <TD>10/19</TD>
      <TD><a href="http://www.hrm-andhalf.org/">Humboldt Redwoods Half Marathon</a></TD>
      <TD>Weott</TD>
      <TD>1.5 or 2.0 Masters only (TBD)</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=10">$2650 or $1350 (TBD)</a></TD>
    </TR>
    <TR>
      <TD>11/09</TD>
      <TD><a href="http://www.fleetfeetsacramento.com/paul-reese-memorial-clarksburg-country-run">Clarksburg Country Run 30K</a></TD>
      <TD>Clarksburg</TD>
      <TD>2.0</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=11">$2800</a></TD>
    </TR>
    <TR>
      <TD>11/27</TD>
      <TD><a href="http://www.svlg.net/">Seagate Elite 5K</a></TD>
      <TD>San Jose</TD>
      <TD>2.0 (Open only)</TD>
      <TD>Short</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=12">$5000</a>*</TD>
    </TR>
    <TR>
      <TD>TBD</TD>
      <TD><a href="http://www.runcim.org">California International Marathon (TBD)</a></TD>
      <TD>Sacramento</TD>
      <TD>2.0</TD>
      <TD>Long</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=13">TBD</a>*</TD>
    </TR>
    <TR>
      <TD>12/14</TD>
      <TD><a href="http://www.westvalleytc.org/">Christmas Relays</a></TD>
      <TD>San Francisco</TD>
      <TD>1.0 or 2.0 (teams only)</TD>
      <TD>None</TD>
      <TD><a href="RRBreakdown2008.asp?Race_Number=14">$2650</a></TD>
    </TR>
  </TABLE>
</center>

<P>*Additional all-comers prize money and/or bonus money is available</P>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-2651783-1");
pageTracker._initData();
pageTracker._trackPageview();
</script>
</BODY>
</HTML>
';

$editor->set_code($body);

// add a spacer:
$editor->addspacer('', 'after:cancel');

$editor->set_charset('iso-8859-1');

// print the editor to the browser:
$editor->print_editor('100%','450');

?>
</div>
</body>
</html>
<?php ob_end_flush() ?>
