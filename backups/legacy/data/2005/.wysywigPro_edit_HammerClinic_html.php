<?php ob_start() ?>
<?php 
if ($_GET['randomId'] != "iitFtlrRovz6wHBlc6_H_WaZb7iv8ALyLN31DNxTKJJpKwHxuGI5rCpV04qY9YBSUycUum0tn7eZlzEz48JNw0bwj01hqzynAGn37sKKvYt9QAE_WxV_YdEbZjhkDUJBKd61ORVZsPcQ2SF7l_qGsAjwJJRTac2Tg5HiRKOJMZx7WMzJFgYxRlkouFokQxDfK5ptwhqy1qifprY9s8LrDSoPUGWb_S6pHbw0zeyLalmeOjf4VXy13XueUkEbhkvK") {
	echo "Access Denied";
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing HammerClinic.html</title>
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
	document.write("<html><form METHOD=POST name=mform action='http://63.247.92.41:2082/frontend/rvblue/files/savehtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='HammerClinic.html'>Saving ....<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><textarea name=page rows=1 cols=1></textarea></form></html>");
	document.close();
	document.mform.page.value = code;
	document.mform.submit();
}
function do_abort() {
	var code =  htmlCode.getCode();
	document.open();
	document.write("<html><form METHOD=POST name=mform action='http://63.247.92.41:2082/frontend/rvblue/files/aborthtmlfile.html'><input type=hidden name=dir value='/home/pausat/www/data/2005'><input type=hidden name=file value='HammerClinic.html'>Aborting Edit ....</form></html>");
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
		<meta name="generator" content="Adobe GoLive 5">
		<title>Welcome to Adobe GoLive 5</title>
	</head>

	<body bgcolor="#ffffff">
		<div align="center">
			<p><b><font size="5">

Hammer Throw Clinic
<br>
					</font></b>with World Record holder and multi-Olympic Champion Youri Sedykh Youri <br>
				will be giving demonstrations, discussion and a large amount of direct coaching of participants
		</div>
		<pre>
<b>Location</b>: Stanford University - Angel Field (track and field stadium - Hammer Field)
<b>
Clinic date and times:
</b>        19-Feb    Saturday   1pm - 5:30pm
        20-Feb    Sunday     9am - 5:30pm
        21-Feb    Monday    9am - 5:30pm

<b>Admission fee is (pay upon arrival):
</b>        -  $200 for 3-days
        -  $150 for 2-days
        -  $75 for coaches and non-throwing participants
-  non-coaching parents or guardians  free
 
<b>Bring your own implements</b> - Safety procedures will be strictly enforced

Throwers will be separated into flights depending on the PR of the thrower.  
Each thrower will get 15 throws per flight with direct coaching by Youri and others.  
Throwers will throw one session on Saturday and then twice on both Sunday and Monday.

<b>Schedule: </b>
Saturday  19-Feb-2005
            12:30 pm    - arrivals and introductions, safety instructions
            1:00 pm      - Youri demonstration,  presentations, Questions & Answers
            2:30 pm     - Group#1 throws and coaching
            3:45 pm     - Group photo
            4:00 pm     - Group#2 throws and coaching
            5:00 pm     - Group#3 throws and coaching
            5:45 pm     - Sunset
     
Sunday  20-Feb-2005
            9:00 am    - Arrival and short recap discussion
            9:30 am    - Group#1 throws and coaching
            10:00 am  - Group#4 coaching (Dave Swan & others) for beginners using (non-throwing)
            10:45 am  - Group#2 throws and coaching
            11:45 noon - Group#3 throws and coaching
            12:30 pm    - Lunch break
            12:45 pm    - Group#4 throws with Dave Swan & others. (8 throws each)
            2:00 pm    - Group#1 throws and coaching
            3:15 pm    - Group#2 throws and coaching
            4:30 pm    - Group#3 throws and coaching
            5:45 pm     - Sunset
           
Monday  21-Feb-2005
            9:00 am    - Arrival and short recap discussion
            9:30 am    - Group#1 throws and coaching
            10:00 am  - Group#4 coaching (Dave Swan & others) for beginners using (non-throwing)
            10:45 am  - Group#2 throws and coaching
            11:45 noon - Group#3 throws and coaching
            12:30 pm    - Lunch break
            12:45 pm   - Group#4 throws with Dave Swan & others. (8 throws each)
            2:00 pm    - Group#1 throws and coaching
            3:15 pm    - Group#2 throws and coaching
            4:30 pm    - Group#3 throws and coaching
            5:45 pm     - Departure

<b>Contact:</b> People interested in attending should send me an email Dave Swan at dswan@altera.com, or
(408) 544-8068  (w) (408) 544-6488  (f)


</pre>
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
