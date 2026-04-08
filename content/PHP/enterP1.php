<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cnum = get_post_string('cnum');

    if ($cnum === '') {
        echo '<p><b>You did not enter a club number.</b></p>';
        echo '<p><b>Please try again.</b></p>';
    } else {
        $pdo = get_pdo();
        $cnumInt = (int) $cnum;

        $stmt = $pdo->prepare('SELECT approved FROM tblCLUBS WHERE club_no = :cnum');
        $stmt->execute([':cnum' => $cnumInt]);
        $row = $stmt->fetch();

        if (!$row) {
            echo '<p><b>The club number \'' . escape_html($cnum) . '\' is not on the database. Contact the PA Office.</b></p>';
            exit();
        }

        setcookie('cnum', (string) $cnumInt, [
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        header('Location: enterP2.php');
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Club Profile Club Number Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Club Profile Entry</legend>
<p><i><b>Before information describing a club (the profile) can be entered,<br>
 the club must be created and approved by the PA Office, which issues the club number.</b></i></p>
<p><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?= escape_html(get_post_string('cnum')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Information"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
