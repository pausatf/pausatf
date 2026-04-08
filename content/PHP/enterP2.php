<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$cnum = (int) (filter_input(INPUT_COOKIE, 'cnum', FILTER_SANITIZE_NUMBER_INT) ?? 0);
$isUpdate = ($_SERVER['REQUEST_METHOD'] === 'POST' && get_post_string('savepaid') !== '');

if ($isUpdate) {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cname     = get_post_string('cname');
    $mcontact  = get_post_string('mcontact');
    $mphone    = get_post_string('mphone');
    $email     = get_post_string('email');
    $hotline   = get_post_string('hotline');
    $website   = get_post_string('website');
    $totmem    = get_post_string('totmem');
    $dues      = get_post_string('dues');
    $yrestb    = get_post_string('yrestb');
    $loc       = get_post_string('loc');
    $spon      = get_post_string('spon');
    $twkouts   = get_post_string('twkouts');
    $rwkouts   = get_post_string('rwkouts');
    $nwsfreq   = get_post_string('nwsfreq');
    $pfocus    = get_post_string('pfocus');
    $events    = get_post_string('events');
    $comments  = get_post_string('comments');
    $savepaid  = get_post_string('savepaid');

    $cfocusArr = $_POST['cfocus'] ?? [];
    $cfocus = is_array($cfocusArr) ? implode(' ', array_map('trim', $cfocusArr)) : get_post_string('savecfocus');
    if ($cfocus === '') {
        $cfocus = get_post_string('savecfocus', 'Not selected');
    }
    $savecfocus = $cfocus;

    $stmt = $pdo->prepare(
        'UPDATE tblCLUBS SET club_name = :cname, update_date = CURDATE(),
            membership_contact = :mcontact, membership_phone = :mphone,
            email = :email, hotline = :hotline, website = :website,
            total_members = :totmem, annual_dues = :dues, year_established = :yrestb,
            locations = :loc, sponsors = :spon, track_workouts = :twkouts,
            road_trail_workouts = :rwkouts, newsletter_frequency = :nwsfreq,
            focus = :cfocus, primary_focus = :pfocus,
            annual_club_events = :events, comments = :comments
         WHERE club_no = :cnum'
    );
    $stmt->execute([
        ':cname'    => $cname,    ':mcontact' => $mcontact, ':mphone'   => $mphone,
        ':email'    => $email,    ':hotline'  => $hotline,  ':website'  => $website,
        ':totmem'   => $totmem,   ':dues'     => $dues,     ':yrestb'   => $yrestb,
        ':loc'      => $loc,      ':spon'     => $spon,     ':twkouts'  => $twkouts,
        ':rwkouts'  => $rwkouts,  ':nwsfreq'  => $nwsfreq,  ':cfocus'   => $cfocus,
        ':pfocus'   => $pfocus,   ':events'   => $events,   ':comments' => $comments,
        ':cnum'     => $cnum,
    ]);

    if ($stmt->rowCount() === 1) {
        echo '<p>Update successful for ' . escape_html($cname) . '</p>';
    } else {
        echo '<p>Nothing changed. Query only.</p>';
    }

    $pcontact = '';
    $pphone   = '';
    $udate    = date('Y-m-d');
} else {
    $stmt = $pdo->prepare('SELECT * FROM tblCLUBS WHERE club_no = :cnum');
    $stmt->execute([':cnum' => $cnum]);
    $row = $stmt->fetch(PDO::FETCH_NUM);

    if (!$row) {
        echo '<p><b>No match found for \'' . escape_html((string) $cnum) . '\'</b></p>';
        exit();
    }

    $cname     = $row[1];
    $savepaid  = match ($row[4]) { 'B', 'E' => 'Y', default => $row[4] };
    if ($savepaid === '') {
        $savepaid = 'U';
    }
    $udate     = $row[5];
    $pcontact  = $row[7];
    $pphone    = $row[8];
    $mcontact  = $row[9];
    $mphone    = $row[10];
    $email     = $row[15];
    $hotline   = $row[16];
    $website   = $row[17];
    $totmem    = $row[18];
    $dues      = $row[19];
    $yrestb    = $row[20];
    $loc       = $row[21];
    $spon      = $row[22];
    $twkouts   = $row[23];
    $rwkouts   = $row[24];
    $nwsfreq   = $row[25];
    $savecfocus = $row[26];
    $cfocus    = $savecfocus;
    $pfocus    = $row[27];
    $events    = $row[28];
    $comments  = $row[29];
}

$focusOptions = ['Road', 'Cross Country', 'Ultras', 'Track and Field', 'Youth', 'Race Walking'];
$selectedFocus = explode(' ', $savecfocus ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Club Profile Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<h2>Club Profile</h2>
<hr>
<p><i><b>Once you click on the Submit Information box below, your changes will be stored.<br>
When done, just exit the screen (clicking on Submit Information box brings the screen back)<br>
You can just query the information (you do not have to make changes).</b></i></p>
<hr>
<i><b>In order to change your primary club contact either the club president or the currently<br>
listed primary contact must notify the PA office that the information has changed.</b></i>
<p><b>Club Number:</b>&nbsp;<?= escape_html((string) $cnum) ?></p>
<p><b>&nbsp;&nbsp;Primary Contact:</b>&nbsp;<?= escape_html((string) $pcontact) ?></p>
<p><b>Phone Number:</b>&nbsp;<?= escape_html((string) $pphone) ?>
<b>&nbsp;&nbsp;&nbsp;&nbsp;Approved Status:</b>&nbsp;<?= escape_html((string) $savepaid) ?></p>
<hr>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Update Club Profile</legend>
<h2>Club Contacts</h2>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?= escape_html((string) $cname) ?>"></p>
<p><b>Membership Contact:</b> <input type="text" name="mcontact" size="50" maxlength="50" value="<?= escape_html((string) $mcontact) ?>"></p>
<p><b>Phone Number:</b> <input type="text" name="mphone" size="75" maxlength="75" value="<?= escape_html((string) $mphone) ?>"></p>
<p><b>Club Email Address:</b> <input type="text" name="email" size="80" maxlength="80" value="<?= escape_html((string) $email) ?>"></p>
<p><b>Club 24-Hr Hotline:</b> <input type="text" name="hotline" size="50" maxlength="50" value="<?= escape_html((string) $hotline) ?>"></p>
<p><b>Club Website URL:</b> <input type="text" name="website" size="100" maxlength="100" value="<?= escape_html((string) $website) ?>"></p>
<hr>
<h2>Club Details</h2>
<p><b>Total Members:</b> <input type="text" name="totmem" size="50" maxlength="50" value="<?= escape_html((string) $totmem) ?>"></p>
<p><b>Annual Dues:</b> <input type="text" name="dues" size="50" maxlength="50" value="<?= escape_html((string) $dues) ?>"></p>
<p><b>Year Established:</b> <input type="text" name="yrestb" size="9" maxlength="9" value="<?= escape_html((string) $yrestb) ?>"></p>
<p><b>Locations:</b> <input type="text" name="loc" size="30" maxlength="30" value="<?= escape_html((string) $loc) ?>"></p>
<p><b>Sponsors:</b> <input type="text" name="spon" size="255" maxlength="255" value="<?= escape_html((string) $spon) ?>"></p>
<h2>Club Attributes</h2>
<p><b>Track Workout Location/Time/Days:</b> <input type="text" name="twkouts" size="255" maxlength="255" value="<?= escape_html((string) $twkouts) ?>"></p>
<p><b>Road/Trail Locations/Time/Days:</b> <input type="text" name="rwkouts" size="255" maxlength="255" value="<?= escape_html((string) $rwkouts) ?>"></p>
<p><b>Newsletter Frequency:</b> <input type="text" name="nwsfreq" size="30" maxlength="30" value="<?= escape_html((string) $nwsfreq) ?>"></p>
<p><b>Our club participates in the following USATF disciplines: (Currently: <?= escape_html((string) $savecfocus) ?>)</b><br>
<?php foreach ($focusOptions as $opt): ?>
<input type="checkbox" name="cfocus[]" value="<?= escape_html($opt) ?>" <?= in_array($opt, $selectedFocus, true) ? 'checked' : '' ?>> <?= escape_html($opt) ?><br>
<?php endforeach; ?>
</p>
<p><b>The <u>Primary</u> focus of our club is:</b> <input type="text" name="pfocus" size="35" maxlength="35" value="<?= escape_html((string) $pfocus) ?>"></p>
<p><b>Annual Club Events:</b> <input type="text" name="events" size="255" maxlength="255" value="<?= escape_html((string) $events) ?>"></p>
<p><b>General Comments:</b> <input type="text" name="comments" size="255" maxlength="255" value="<?= escape_html((string) $comments) ?>"></p>
<p><b>Last updated:</b>&nbsp;<?= escape_html((string) ($udate ?? '')) ?></p>
<input type="hidden" name="savepaid" value="<?= escape_html((string) $savepaid) ?>">
<input type="hidden" name="savecfocus" value="<?= escape_html((string) $savecfocus) ?>">
</fieldset>
<div><input type="submit" name="submit" value="Submit Profile for processing"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
