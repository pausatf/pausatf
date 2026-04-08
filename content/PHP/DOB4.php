<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cnumEntered = get_post_string('cnumEntered');
    $nameEntered = get_post_string('nameEntered');
    $msg = '';

    if ($cnumEntered === '') {
        $msg .= '<p><b>You did not select a club.</b></p>';
    }
    if ($nameEntered === '') {
        $msg .= '<p><b>You did not enter a name.</b></p>';
    }

    if ($msg === '') {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT * FROM DOBNames WHERE LastName = :lname AND Club = :cnum');
        $stmt->execute([':lname' => $nameEntered, ':cnum' => $cnumEntered]);
        $results = $stmt->fetchAll(PDO::FETCH_NUM);

        if (!$results) {
            echo '<p><b>Last Name \'' . escape_html($nameEntered) . '\' for club ' . escape_html($cnumEntered) . ' is not on the database.</b></p>';
            exit();
        }

        foreach ($results as $row) {
            echo '<p><b>' . escape_html((string) $row[1]) . '</b></p>';
        }
        exit();
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
<title>DOB Search Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>DOB Search by Last Name</legend>
<p><b><i>Select the Club and enter the last name of the athlete to be queried.</i></b></p>
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
<p><b>Last Name:</b> <input type="text" name="nameEntered" size="50" maxlength="50" value="<?= escape_html(get_post_string('nameEntered')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Search"></div>
</form>
</body>
</html>
