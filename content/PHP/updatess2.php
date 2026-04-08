<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$rnumCookie = (int) (filter_input(INPUT_COOKIE, 'rnumCookie', FILTER_SANITIZE_NUMBER_INT) ?? 0);
$cnumCookie = (int) (filter_input(INPUT_COOKIE, 'cnumCookie', FILTER_SANITIZE_NUMBER_INT) ?? 0);

$fields = [
    'MO', 'MM', 'MS', 'MSS', 'MV',
    'WO', 'WM', 'WS', 'WSS', 'WV',
    'MOB', 'MMB', 'MSB', 'MSSB', 'MVB',
    'WOB', 'WMB', 'WSB', 'WSSB', 'WVB',
];

$isUpdate = ($_SERVER['REQUEST_METHOD'] === 'POST' && get_post_string('MOEntered') !== '');
$savecname = '';
$values = [];

if ($isUpdate) {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    foreach ($fields as $f) {
        $values[$f] = get_post_string($f . 'Entered');
    }
    $savecname = get_post_string('savecname');

    $setClauses = ['ClubName = :savecname'];
    $params = [':savecname' => $savecname, ':rnum' => $rnumCookie, ':cnum' => $cnumCookie];
    foreach ($fields as $f) {
        $setClauses[] = "$f = :$f";
        $params[":$f"] = $values[$f];
    }

    $sql = 'UPDATE Teams SET ' . implode(', ', $setClauses) . ' WHERE RaceNumber = :rnum AND ClubNumber = :cnum';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        echo '<p>No update occurred because nothing changed.</p>';
    } else {
        echo '<p><h3>Update of Club ' . escape_html((string) $cnumCookie) . ' for Race ' . escape_html((string) $rnumCookie) . ' was successful</h3></p>';
    }
} else {
    $stmt = $pdo->prepare('SELECT RaceNumber, Name, RaceDate FROM RaceName WHERE RaceNumber = :rnum');
    $stmt->execute([':rnum' => $rnumCookie]);
    $race = $stmt->fetch();

    if (!$race) {
        echo '<p><b>The race number ' . escape_html((string) $rnumCookie) . ' is not on the database.</b></p>';
        exit();
    }

    echo '<h3>Scoresheet status for ' . escape_html((string) $race['Name']) . '</h3>';
    echo '<i>scheduled ' . escape_html((string) $race['RaceDate']) . '</i><br>';

    $stmt2 = $pdo->prepare('SELECT * FROM Teams WHERE RaceNumber = :rnum AND ClubNumber = :cnum');
    $stmt2->execute([':rnum' => $rnumCookie, ':cnum' => $cnumCookie]);
    $team = $stmt2->fetch(PDO::FETCH_NUM);

    if (!$team) {
        echo '<p><b>Nothing found for Race ' . escape_html((string) $rnumCookie) . ' Club ' . escape_html((string) $cnumCookie) . '. Contact webmaster.</b></p>';
        exit();
    }

    $savecname = (string) $team[2];
    $colIndex = 3;
    foreach ($fields as $f) {
        $values[$f] = (string) $team[$colIndex];
        $colIndex++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Scoresheet Status Update Form</title>
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
<fieldset><legend>Scoresheet Status Update Form</legend>
<h2>Update of Scoresheet Submittal Status</h2>
<p><b>Race Number:</b>&nbsp;<?= escape_html((string) $rnumCookie) ?></p>
<p><b>Club Number:</b>&nbsp;<?= escape_html((string) $cnumCookie) ?></p>
<?php
$labels = [
    'MO' => "Mens Open", 'MM' => "Mens Master", 'MS' => "Mens Senior",
    'MSS' => "Mens Super Senior", 'MV' => "Mens Veteran",
    'WO' => "Womens Open", 'WM' => "Womens Master", 'WS' => "Womens Senior",
    'WSS' => "Womens Super Senior", 'WV' => "Womens Veteran",
];
foreach ($labels as $key => $label):
    $bKey = $key . 'B';
?>
<p><b><?= escape_html($label) ?> Scoresheet Received:</b>&nbsp;<input type="text" name="<?= $key ?>Entered" size="1" maxlength="1" value="<?= escape_html($values[$key] ?? '') ?>">
<b>B Team:</b>&nbsp;<input type="text" name="<?= $bKey ?>Entered" size="1" maxlength="1" value="<?= escape_html($values[$bKey] ?? '') ?>"></p>
<?php endforeach; ?>
<input type="hidden" name="savecname" value="<?= escape_html($savecname) ?>">
<hr>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Update"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
