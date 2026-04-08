<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cnumEntered = get_post_string('cnumEntered');
    $nameEntered = get_post_string('nameEntered');
    $LnameEntered = get_post_string('LnameEntered');
    $msg = '';

    if ($cnumEntered === '') {
        $msg .= '<p><b>You did not select a club.</b></p>';
    }
    if ($nameEntered === '') {
        $msg .= '<p><b>You did not enter a Full name.</b></p>';
    }
    if ($LnameEntered === '') {
        $msg .= '<p><b>You did not enter a Last name.</b></p>';
    }

    if ($msg === '') {
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('INSERT INTO DOBNames (Club, FullName, LastName) VALUES (:cnum, :name, :lname)');
            $stmt->execute([':cnum' => (int) $cnumEntered, ':name' => $nameEntered, ':lname' => $LnameEntered]);
            echo '<p>' . escape_html($nameEntered) . ' in club ' . escape_html($cnumEntered) . ' successfully added</p>';
        } catch (PDOException $e) {
            error_log('DOB3 insert failed: ' . $e->getMessage());
            echo '<p><b>A database error occurred. The record was not added.</b></p>';
        }
    } else {
        echo $msg;
        echo '<p><b>Please try again.</b></p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DOB Add Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>DOB Add Name Form</legend>
<p><b><i>Select the Club and enter the name of the athlete to be added.</i></b></p>
<p><b>Select Club:</b>
<select name="cnumEntered">
<option value="2">East Bay Heat Track Club</option>
<option value="6">Hilltop Speed</option>
<option value="7">Diablo Valley Club</option>
<option value="8">Godspeed Wings</option>
<option value="9">Palo Alto Lightning</option>
<option value="11">Castro Valley TC</option>
<option value="12">Alex Van Dyke TC</option>
<option value="13">Acorn/Oscar Bailey TC</option>
<option value="14">Los Gatos AA</option>
<option value="15">EOYDC</option>
<option value="18">Napa TC</option>
<option value="600">Unattached Boys</option>
<option value="800">Unattached Girls</option>
</select></p>
<p><b>Full Name:</b> <input type="text" name="nameEntered" size="50" maxlength="50" value="<?= escape_html(get_post_string('nameEntered')) ?>"></p>
<p><b>Last Name:</b> <input type="text" name="LnameEntered" size="50" maxlength="50" value="<?= escape_html(get_post_string('LnameEntered')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Add Athlete"></div>
</form>
</body>
</html>
