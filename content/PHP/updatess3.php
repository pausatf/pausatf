<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();

$rnum = get_post_int('rnum', 0);
$cname = get_post_string('cname');
$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    if ($rnum <= 0 || $cname === '') {
        $result = '<p><b>Race number and club name are required.</b></p>';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM RaceName WHERE RaceNumber = :rnum');
        $stmt->execute([':rnum' => $rnum]);

        if (!$stmt->fetch()) {
            $result = '<p><b>The race number \'' . escape_html((string) $rnum) . '\' is not on the database.</b></p>';
        } else {
            $stmt2 = $pdo->prepare('UPDATE RaceName SET ClubName = :cname WHERE RaceNumber = :rnum');
            $stmt2->execute([':cname' => $cname, ':rnum' => $rnum]);

            if ($stmt2->rowCount() === 0) {
                $result = '<p>No update occurred because nothing changed.</p>';
            } else {
                $result = '<p><h3>Update of Race ' . escape_html((string) $rnum) . ' as ' . escape_html($cname) . ' was successful</h3></p>';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Scoresheet Maintenance</title>
</head>
<body>
<h1>Scoresheet Maintenance</h1>
<?= $result ?>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Update Race Name</legend>
<p><b>Race Number:</b> <input type="number" name="rnum" size="5" value="<?= escape_html((string) $rnum) ?>"></p>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?= escape_html($cname) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" value="Update"></div>
</form>
</body>
</html>
