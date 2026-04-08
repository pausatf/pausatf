<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $msg = '';
    $cnum = get_post_string('cnum');
    $cname = get_post_string('cname');
    $csname = get_post_string('csname');
    $pcontact = get_post_string('pcontact');
    $pphone = get_post_string('pphone');
    $paidYN = get_post_string('paidYN');

    if ($cnum === '') {
        $msg .= '<p><b>You did not enter a club number.</b></p>';
    }
    if ($cname === '') {
        $msg .= '<p><b>You did not enter a club name.</b></p>';
    }
    if ($csname === '') {
        $msg .= '<p><b>You did not enter a club short name.</b></p>';
    }
    if ($pcontact === '') {
        $msg .= '<p><b>You did not enter a primary contact name.</b></p>';
    }
    if ($pphone === '') {
        $msg .= '<p><b>You did not enter a primary contact phone number.</b></p>';
    }
    if (!in_array($paidYN, ['Y', 'N'], true)) {
        $msg .= '<p><b>You did not enter a Y or N for the Approved Status.</b></p>';
        $paidYN = '';
    }

    if ($msg === '') {
        $pdo = get_pdo();
        $cnumInt = (int) $cnum;

        $stmt = $pdo->prepare('SELECT club_no FROM tblCLUBS WHERE club_no = :cnum');
        $stmt->execute([':cnum' => $cnumInt]);

        if ($stmt->fetch()) {
            echo '<p><b>The club number \'' . escape_html($cnum) . '\' already exists on the database.</b></p>';
            exit();
        }

        $stmt = $pdo->prepare(
            'INSERT INTO tblCLUBS (club_no, club_name, short_name, approved, primary_contact, primary_phone)
             VALUES (:cnum, :cname, :csname, :paidYN, :pcontact, :pphone)'
        );
        $stmt->execute([
            ':cnum'     => $cnumInt,
            ':cname'    => $cname,
            ':csname'   => $csname,
            ':paidYN'   => $paidYN,
            ':pcontact' => $pcontact,
            ':pphone'   => $pphone,
        ]);

        echo '<p><b>New Club ' . escape_html($csname) . ' has been added to the database.<br>Profile data needs to be added by the Club</b></p>';
        exit();
    } else {
        echo '<p><b>' . $msg . '</b></p>';
        echo '<p><b>Please try again.</b></p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>New Club Entry Form</title>
</head>
<body>
<div style="text-align:center">
<h1 style="color:blue">PA/USATF Club Entry</h1>
<h2>PA Office, Folsom CA</h2>
</div>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>New Club Entry</legend>
<p><i><b>All fields are required. When done with entry, click on the Submit Information box at the bottom.</b></i></p>
<p><b>Enter Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?= escape_html(get_post_string('cnum')) ?>"></p>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?= escape_html(get_post_string('cname')) ?>"></p>
<p><b>Club Short Name (used for displays on website):</b> <input type="text" name="csname" size="20" maxlength="20" value="<?= escape_html(get_post_string('csname')) ?>"></p>
<p><b>Has club been approved? (Y/N):</b>
  <input type="radio" name="paidYN" value="Y" <?= get_post_string('paidYN') === 'Y' ? 'checked' : '' ?>> YES
  <input type="radio" name="paidYN" value="N" <?= get_post_string('paidYN') === 'N' ? 'checked' : '' ?>> NO
</p>
<p><b>Primary Contact:</b> <input type="text" name="pcontact" size="50" maxlength="50" value="<?= escape_html(get_post_string('pcontact')) ?>"></p>
<p><b>Primary Phone:</b> <input type="text" name="pphone" size="50" maxlength="50" value="<?= escape_html(get_post_string('pphone')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Information"></div>
</form>
</body>
</html>
