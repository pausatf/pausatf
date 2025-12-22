<?php ob_start() ?>
<?php 
if ($_GET['randomId'] != "GjF1NFtLMqJ_tcqjsfu3dJa2Z_4RieHqm0_1X3DAytFlonKFify3KZdrxPeu_tqDLa3yhWWatrXP0dXUrQxM7bOvZW9T2ClbJujvKnHJi6smR8CjRHtJzIrCsZAArmXOCSJOTOhIGLEeXoHtia6qX6sPmfDpxQwsTJWIV9ES5jbU93zWT7v__CeqNe_GJDMoKcVEtgLRoAdyf0BU2TAPzU_WnkusVpX7NipIRwYYbgYx80BdyT9H4BWoTghCLjsn") {
	echo "Access Denied";
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing TFRulesGP2005.html</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
	document.write("<html><form METHOD=POST name=mform action='http://pausatf.org:2082/frontend/rvblue/files/savehtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='TFRulesGP2005.html'>Saving ....<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><textarea name=page rows=1 cols=1></textarea></form></html>");
	document.close();
	document.mform.page.value = code;
	document.mform.submit();
}
function do_abort() {
	var code =  htmlCode.getCode();
	document.open();
	document.write("<html><form METHOD=POST name=mform action='http://pausatf.org:2082/frontend/rvblue/files/aborthtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='TFRulesGP2005.html'>Aborting Edit ....</form></html>");
	document.close();
	document.mform.submit();
}
//-->
</script>
<?php
// make sure these includes point correctly:
include_once ('/home/pausat/public_html/WysiwygPro/editor_files/config.php');
include_once ('/home/pausat/public_html/WysiwygPro/editor_files/editor_class.php');

// create a new instance of the wysiwygPro class:
$editor = new wysiwygPro();

// add a custom save button:
$editor->addbutton('Save', 'before:print', 'do_save();', WP_WEB_DIRECTORY.'images/save.gif', 22, 22, 'undo');

// add a custom cancel button:
$editor->addbutton('Cancel', 'before:print', 'do_abort();', WP_WEB_DIRECTORY.'images/cancel.gif', 22, 22, 'undo');

$body = '<html>

	<head>
		<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
		<title>Grand Prix Rules 2005</title>
 		<link rel="stylesheet" href="/PAstylesheet.css" type="text/css">
<style>
<!--
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin:0in;
	margin-bottom:.0001pt;
	text-autospace:none;
	font-size:12.0pt;
	font-family:"Times New Roman";}
h1
	{margin:0in;
	margin-bottom:.0001pt;
	text-autospace:none;
	font-size:12.0pt;
	font-family:"Times New Roman";
	font-weight:normal;}
p.MsoBodyText, li.MsoBodyText, div.MsoBodyText
	{margin-top:0in;
	margin-right:-.5in;
	margin-bottom:0in;
	margin-left:0in;
	margin-bottom:.0001pt;
	text-autospace:none;
	font-size:10.0pt;
	font-family:"Times New Roman";}
 /* Page Definitions */
 @page Section1
	{size:8.5in 11.0in;
	margin:1.0in 1.25in 1.0in 1.25in;}
div.Section1
	{page:Section1;}
-->
</style>

</head>
<body bgcolor="#ffffff" lang=EN-US style=\'text-justify-trim:punctuation\'>
		<div align="center">
			<p><IMG ALIGN="Middle" WIDTH="400" HEIGHT="70" SRC="http://www.pausatf.org/images/HeaderTF.gif"></p>
			<h3>2005 PACIFIC ASSOCIATION USATF TRACK AND FIELD GRAND PRIX</h3>
		</div>
<div class=Section1>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>In 2005, The PA USATF Grand
Prix will include all Olympic contested track events, and all Olympic contested
field events.  There is no event specific competition, and the best scores take
the spoils.  This years prize money is $15,000, with equal prize monies going
to both the top ten men and women.</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>                                                </span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>There will be a combination of 
five (5) meets used  in the scoring and the <b><i><u>IAAF Scoring Table of
Athletics </u></i></b>will be used to equate scoring.  <b><u>An athlete must
attain at least 900 points in an individual performance to score</u></b>.  Only
one (1) scoring event per meet is allowed.  The rationale for this is that The
Pacific Association Grand Prix is to develop athletes competitive on the
national level.</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>&nbsp;</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>Three or more(to the total of
five) meets within The Pacific Association can score points and a <b><i>maximum</i></b>
of <b><i>two</i></b> meet outside The Pacific Association can also score
points.  In addition, points will be doubled at The Pacific Association USATF
Championships to be held Saturday, May 28, 2005 at Sacramento City College
(Hughes Stadium).  A total of five meets will be used to determine the
standings of the athletes in The 2005 Pacific Association Track &amp;Field
Grand Prix.  A score in a multi event competition counts as two meets as long
as the score is at, or above, the 900  point level. The IAAF Scoring Table
(2001 edition) will be used for calculations and determination of prizewinners.
A bonus of points scored at the USATF Olympic Team Trials will be added to your
score to determine total for prize money distribution.</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>&nbsp;</span></p>

<p class=MsoBodyText>Meets that will qualify you for entry into The USATF T/F
Championships June 23-26 at Carson, CA (Home Depot Center) will be counted
towards your Grand Prix total from the dates of  March 1, 2005 until June 19 ,
2005.</p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>&nbsp;</span></p>

<p class=MsoNormal><b><i><u><span style=\'font-size:10.0pt\'><font size="4">IAAF 2001 Scoring
Table -  900  point total breakdown<br>
									<br>
								</font></span></u></i></b></p>
			<p class=MsoNormal></p>
			<h1 style=\'page-break-after:avoid\'><i><u><span style=\'font-size:10.0pt\'>Event                      Men                        Women                                   Event                      Men                        Women  
</span></u></i></h1><br>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>100m                       11.07       
               12.80                                       200m                       22.30                       26.36</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>400m                       49.61                       59.42                                       800m                       1:54.76    
               2:15.17    </span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>1500m                     3:56.22                    4:41.38                                    Mile                        4:14.89                    5:06.02</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>5,000m                    14:37.66                  17.30.88                                  10,000m                  30:48.51
 37:07.01  </span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>3,000m SC              9:30.75                    11:42.70                                  110/100m
H            15.30                       15.19</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>400mH                    55.56                       1:05.85                                    20km/10km
Walk1:35:58                      55:06</span></p>

<p class=MsoNormal style=\'margin-right:-76.5pt\'><span style=\'font-size:10.0pt\'>Long
Jump            6.92 (2208.50)     5.71m(1808.75)   Pole Vault              4.80m(1509)
       3.76m(12\'04&quot;)</span></p>

<p class=MsoNormal style=\'margin-right:-1.25in\'><span style=\'font-size:10.0pt\'>Triple
Jump           14.69m(4802.50)12.17m(3911.25   Shot Put                 16.34m(5307.50)15.80(5110)</span></p>

<p class=MsoNormal style=\'margin-right:-81.0pt\'><span style=\'font-size:10.0pt\'>Discus                    52.03m(17008)    52.52m(17204)                    Javelin                    68.34m(22402)    52.07(170\'9&quot;</span></p>

<p class=MsoNormal style=\'margin-right:-1.25in\'><span style=\'font-size:10.0pt\'>Hammer                  62.27m(20403)    56.20m(18404)                    High
Jump             2.03m(608)          1.71(57.25)</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>Multi events         7764 pts                 5949
pts </span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>&nbsp;</span></p>

<p class=MsoNormal><span style=\'font-size:10.0pt\'>All athletes who wish to
participate in The 2005 Pacific Association Grand Prix must: 1. Be a 2005
member of USATF  2.Register with Jerry Colman(Chair of Men\'s &amp; Women\'s
T/F)  <u><span style=\'color:blue\'>sactc@aol,com</span></u>, with name, mailing
address , 2005 USATF number and e-mail address.    <b><i>This<u> </u>has  to be
completed prior to earning any points towards your Grand Prix total!</i></b></span></p>

</div>

</body>

</html>
';

$editor->set_code($body);

// add a spacer:
$editor->addspacer('', 'after:cancel');

// print the editor to the browser:
$editor->print_editor('100%',450);

?>
</div>
</body>
</html>
<?php ob_end_flush() ?>
