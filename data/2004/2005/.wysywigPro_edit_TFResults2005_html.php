<?php ob_start() ?>
<?php 
if ($_GET['randomId'] != "fbsuLPtrVo6kKEZ_wgKqGdO92i7cujWVrD2XW36GLZqV3FVNBQnAlwps4eilColjtdcqqwkmv_rku3ZLGlykBkFl56_eDsr4M6yDQTVHy_rEJ2GoutIFUpU2K28Oowt_3NBczVfq99wSOUyYlYSD6xi2aiMQv29nQ3M1OZZc0tuclYIjnix7jBQoI3mPvoEKQhiAuPCe4ebhmayGdLfMpyx1ogZiN98IJSBsmbUuwwLo9o3piw2gu92A8dVhBEgZ") {
	echo "Access Denied";
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing TFResults2005.html</title>
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
	document.write("<html><form METHOD=POST name=mform action='http://pausatf.org:2082/frontend/rvblue/files/savehtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='TFResults2005.html'>Saving ....<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><textarea name=page rows=1 cols=1></textarea></form></html>");
	document.close();
	document.mform.page.value = code;
	document.mform.submit();
}
function do_abort() {
	var code =  htmlCode.getCode();
	document.open();
	document.write("<html><form METHOD=POST name=mform action='http://pausatf.org:2082/frontend/rvblue/files/aborthtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='TFResults2005.html'>Aborting Edit ....</form></html>");
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

$body = '<HTML>

<HEAD>
<TITLE>Track &amp; Field Results 2003</TITLE>
<link rel="stylesheet" href="/PAstylesheet.css" type="text/css">
</HEAD>

<BODY>
		<CENTER>
<IMG ALIGN="Middle" SRC="http://www.pausatf.org/images/HeaderTF.gif">
<P>
<h1>RESULTS</h1>

<HR>
</CENTER>
		<div align="center">
			<h2>2005 Results</h2>
			<table border="0" cellpadding="0" cellspacing="2" width="403">
				<tr>
				   <td>
                                   <p>Mar 06 - <a href="http://www.pausatf.org/data/2005/TrkMeetMar06.html">Stanford Open, Stanford University</a><br>
                                   <p>Mar 12 - <a href="http://www.pausatf.org/data/2005/TrkMeetMar12.html">Aggie Classic, UC Davis</a><br>
                                   </td>
				</tr>
			</table>
		</div>
		<div align="left">
			
			
		</div>
		<p align=Center><u>ARCHIVED&nbsp;RESULTS<br>
                        </u><a href="http://www.pausatf.org/data/2004/TFResults2004.html">2004 Results<br> 
                        </a><a href="http://www.pausatf.org/data/2003/TFResults2003.html">2003 Results<br>
			</a><a href="http://www.pausatf.org/data/2002/TFResults2002.html">2002 Results<br>
			</a><a href="http://www.pausatf.org/data/2001/TFResults01.html">2001 Results<br>
			</a><a href="http://www.pausatf.org/data/2000/TFResults.html">2000 Results</a></p>
	</body>
</html>';

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
