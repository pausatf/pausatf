<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<head>
<title>PA Youth Page</title>
<link href="/pastylesheetyouth.css" type="text/css" rel="stylesheet">
</head>

<body>
<div align="center">
 <?php
  $current_flash = 3;  // assign a unique number to each flash content
  $cookie_name = 'SAW_FLASH';
  if($_COOKIE[$cookie_name] == $current_flash) {
    // User has seen flash, display flash replacement
    ?><!------flash replacement here -----><img src="/images/youthweba.jpg">
<table width="800" border="1" color="white" cellspacing="0" cellpadding="0" bgcolor="#f8f8ff">
<tr>
<td bgcolor="#ff0033">
<div align="center">
<table width="800" border="2" cellspacing="2" cellpadding="2" bgcolor="black">
<tr>
<td colspan="3">
<div align="center">
 <?php
  } else {
    setcookie($cookie_name, $current_flash, time()+60*60*24*30);  // set cookie to expire in 30 days
    // now display flash
    ?><!------flash here -----><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" height="271" width="671">
<param name="movie" value="/Flash/YouthFlash2.swf">
<param name="quality" value="best">
<param name="play" value="true">
<embed height="271" pluginspage="http://www.macromedia.com/go/getflashplayer" src="/Flash/YouthFlash2.swf" type="application/x-shockwave-flash" width="671" quality="best" play="true"> 
</object><?php

  }  // close out php if
    ?></div>
</td>
</tr>
<tr>
<td colspan="3">
<div align="center">
<table width="800" bgcolor="black" height="30">
<tr>
<td valign="top">
<div align="center">
<a href="/data/2007/ytfresults2007.html"><b><font size="1" color="white">T&amp;F Results</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/ytfrecords.html"><b><font size="1" color="white">T&amp;F Records</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/2006/yxcschedule2006.html"><b><font size="1" color="white">XC Schedule</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/members.php?age=youth"><b><font size="1" color="white">Members/Clubs</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/ytfminutes.html"><b><font size="1" color="white">Meetings/Mins.</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/yrules.html"><b><font size="1" color="white">Rules</font></b></a></div>
</td>
</tr>
<tr>
<td valign="top">
<div align="center">
<a href="/data/2007/ytfschedule2007.html"><b><font size="1" color="white">T&amp;F Schedule</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/ytfagegroups.html"><b><font size="1" color="white">Age Groups</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/2006/yxcresults2006.html"><b><font size="1" color="white">XC Results</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/YTF4_letter_codes.html"><b><font size="1" color="white">Club Manager</font></b></a></div>
</td>
<td valign="top">
<div align="center">
<a href="/data/2007/ybckchks.html"><b><font size="1" color="white">Background Checks</font></b></a></div>
</td>
<td valign="top"></td>
</tr>
</table>
</div>
</td>
</tr>
<tr>
<td>
<div align="center">
<img src="/images/Ybpic4.jpg" alt="" border="0"></div>
</td>
<td width="270">
<table width="260" border="1" cellspacing="2" cellpadding="9" bgcolor="#fff0f5">
<tr>
<td width="260">
<div align="center">
<b><font size="4" color="red">LATEST YOUTH NEWS </font></b></div>
</td>
</tr>
<tr height="270">
<td valign="top" bgcolor="#cccccc" width="260" height="270">
<ul>
<li><a href="/data/2007/pacommIntern.pdf">PA Communication Intern Opps</a>
<li><a href="/data/2007/ytfschedule2007.html">Tom Moore Classic adds PV</a>
<li><a href="/data/2007/ytfschedule2007.html">NV Trackfest adds Subbantams</a>
<li><a href="http://www.modestorelays.org/events/07youth.htm">Modesto Relays Youth Results</a>
<li><a href="/data/2007/ytflivescanupdate.html">Final Livescan Opp - May 19</a>
<li><a href="/data/2007/ytfusatfgrants.html">USATF Youth Grants!</a>
<li><a href="/data/meetings.html">PA BofA Meeting 5/22</a>
<li><a href="/data/2007/ybckchks.html">Cleared Background Check List</a><br>
<br>
<br>
<br>
<br>
<br>

</ul>
</td>
</tr>
</table>
</td>
<td>
<div align="center">
<img src="/images/tori-anim.gif" alt="" border="0"></div>
</td>
</tr>
</table>
</div>
</td>
</tr>
</table>
<p><font size="2" color="white">copyright PAUSATF @ 2007</font></p>
</div>
<p></p>
</body>

</html>