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
        $cnumInt = filter_var($cnum, FILTER_VALIDATE_INT);
        if ($cnumInt === false) {
            echo '<p><b>Club number must be numeric. Please try again.</b></p>';
        } else {
            try {
                $pdo = get_pdo();
                $stmt = $pdo->prepare('SELECT FullName FROM DOBNames WHERE Club = :cnum');
                $stmt->execute([':cnum' => $cnumInt]);
                $names = $stmt->fetchAll();

                if (!$names) {
                    echo '<p><b>Club number \'' . escape_html($cnum) . '\' does not have any athletes listed.</b></p>';
                    exit();
                }

                echo '<p>The following ' . count($names) . ' youth have their birth certificate verified:</p>';
                foreach ($names as $row) {
                    echo '<p><b>' . escape_html((string) $row['FullName']) . '</b></p>';
                }
                exit();
            } catch (PDOException $e) {
                error_log('DOB1 query failed: ' . $e->getMessage());
                echo '<p><b>A database error occurred. Please try again later.</b></p>';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DOB Club Number Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<h1 style="color:blue">PA/USATF Birth Certificate Verification</h1>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>DOB Club Number Entry</legend>
<p><i><b>Athletes are listed by Club or as Unattached.<br>
If you do not know your club number, go back and get it from the listing (Unattached Boys is 600, girls, 800).</b></i></p>
<p><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?= escape_html(get_post_string('cnum')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Information"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
