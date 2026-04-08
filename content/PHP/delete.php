<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cnum = get_post_string('cnum');

    if ($cnum === '') {
        echo '<p><b>You did not enter a club number.</b></p>';
        echo '<p><b>Not a valid number. Please try again.</b></p>';
    } else {
        $pdo = get_pdo();
        $cnumInt = (int) $cnum;

        $stmt = $pdo->prepare('SELECT club_no FROM tblCLUBS WHERE club_no = :cnum');
        $stmt->execute([':cnum' => $cnumInt]);

        if (!$stmt->fetch()) {
            echo '<p><b>The club number \'' . escape_html($cnum) . '\' is not on the database. See Club Listing.</b></p>';
            exit();
        }

        $stmt = $pdo->prepare('DELETE FROM tblCLUBS WHERE club_no = :cnum');
        $stmt->execute([':cnum' => $cnumInt]);

        if ($stmt->rowCount() === 1) {
            echo '<p>Club number ' . escape_html($cnum) . ' successfully deleted.</p>';
        } else {
            echo '<p>Unexpected result deleting club ' . escape_html($cnum) . '.</p>';
        }
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Club Number Deletion for PA Office</title>
</head>
<body>
<div style="text-align:center">
<h1 style="color:blue">PA Office Deletion of a Club</h1>
</div>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Club Number Entry for Deletion</legend>
<p><b>Enter the Club Number:</b> <input type="text" name="cnum" size="10" maxlength="10" value="<?= escape_html(get_post_string('cnum')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Information" onclick="return confirm('Are you sure you want to delete this club?')"></div>
</form>
</body>
</html>
