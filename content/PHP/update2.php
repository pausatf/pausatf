<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$cnum = (int) (filter_input(INPUT_COOKIE, 'cnum', FILTER_SANITIZE_NUMBER_INT) ?? 0);
$isUpdate = ($_SERVER['REQUEST_METHOD'] === 'POST' && get_post_string('pcontact') !== '');
$cdate = '';

if ($isUpdate) {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cname    = get_post_string('cname');
    $pcontact = get_post_string('pcontact');
    $pphone   = get_post_string('pphone');
    $paidYN   = get_post_string('paidYN');
    $msg = '';

    if ($cname === '') {
        $msg .= '<p><b>Club name is missing.</b></p>';
    }
    if ($pcontact === '') {
        $msg .= '<p><b>Primary Contact name is missing.</b></p>';
    }
    if ($pphone === '') {
        $msg .= '<p><b>Primary Contact phone number is missing.</b></p>';
    }
    if (!in_array($paidYN, ['Y', 'N'], true)) {
        $msg .= '<p><b>Approved Status for current year must be Y or N.</b></p>';
        $paidYN = '';
    }

    if ($msg !== '') {
        die($msg);
    }

    $stmt = $pdo->prepare(
        'UPDATE tblCLUBS SET club_name = :cname, primary_contact = :pcontact,
            primary_phone = :pphone, approved = :paidYN, update_date = CURDATE()
         WHERE club_no = :cnum'
    );
    $stmt->execute([
        ':cname'    => $cname,
        ':pcontact' => $pcontact,
        ':pphone'   => $pphone,
        ':paidYN'   => $paidYN,
        ':cnum'     => $cnum,
    ]);

    if ($stmt->rowCount() === 1) {
        echo '<p>Update successful for ' . escape_html($cname) . '</p>';
    } else {
        echo '<p>Nothing changed. Query only.</p>';
    }

    $cdate = date('Y-m-d');
} else {
    $stmt = $pdo->prepare('SELECT * FROM tblCLUBS WHERE club_no = :cnum');
    $stmt->execute([':cnum' => $cnum]);
    $row = $stmt->fetch(PDO::FETCH_NUM);

    if (!$row) {
        echo '<p><b>No match found for \'' . escape_html((string) $cnum) . '\'</b></p>';
        exit();
    }

    $cname    = $row[1];
    $paidYN   = $row[4];
    $cdate    = $row[5];
    $pcontact = $row[7];
    $pphone   = $row[8];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Update Primary Contact</title>
</head>
<body>
<div style="text-align:center">
<h1 style="color:blue">PA Office Update</h1>
<h2>PA Office, Folsom CA</h2>
</div>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Update PA Office Info</legend>
<p><i><b>All fields are required. When done with entry, click on the Submit Information box at the bottom.</b></i></p>
<p><b>Club Number:</b>&nbsp;<?= escape_html((string) $cnum) ?></p>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" maxlength="50" value="<?= escape_html((string) $cname) ?>"></p>
<p><b>Last Updated:</b>&nbsp;<?= escape_html((string) $cdate) ?></p>
<p><b>Primary Contact:</b> <input type="text" name="pcontact" size="50" maxlength="50" value="<?= escape_html((string) $pcontact) ?>"></p>
<p><b>Primary Phone:</b> <input type="text" name="pphone" size="50" maxlength="50" value="<?= escape_html((string) $pphone) ?>"></p>
<p><b>Approved Status (Y/N):</b> <input type="text" name="paidYN" size="1" maxlength="1" value="<?= escape_html((string) $paidYN) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Submit Update"></div>
</form>
</body>
</html>
