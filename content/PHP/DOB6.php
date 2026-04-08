<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $nameEntered = get_post_string('nameEntered');

    if ($nameEntered === '') {
        echo '<p><b>You did not enter a name.</b></p>';
        echo '<p><b>Please try again.</b></p>';
    } else {
        $pdo = get_pdo();

        $stmt = $pdo->prepare('SELECT * FROM DOBNames WHERE LastName = :lname ORDER BY FullName');
        $stmt->execute([':lname' => $nameEntered]);
        $results = $stmt->fetchAll(PDO::FETCH_NUM);

        if (!$results) {
            echo '<p><b>Last Name:</b></p><h2><i>' . escape_html($nameEntered) . '</i></h2><p>is not on file as verified.</p>';
            echo '<p><b>Check with Tony Williams by email or phone: 510-206-5403</b></p>';
            echo '<p><b>When contacting the Youth Membership Chair please provide:</b></p>';
            echo '<p>1. Name of youth athlete.<br>2. Name of club or unattached.<br>3. Current USATF membership number.<br>4. Describe proof of birth submitted.</p>';
            echo '<p>Allow 1-2 weeks for athlete to be verified and show up on the web page.</p>';
            echo '<p><a href="DOB6.php">Return</a> to search screen.</p>';
            exit();
        }

        // Look up club names for each result
        $clubStmt = $pdo->prepare('SELECT * FROM DOBClubs WHERE club = :cnum');

        echo '<table>';
        foreach ($results as $row) {
            $clubno = (int) $row[0];
            $fullname = (string) $row[1];

            $clubStmt->execute([':cnum' => $clubno]);
            $club = $clubStmt->fetch(PDO::FETCH_NUM);
            $clubname = $club ? (string) $club[1] : 'club number not found';

            echo '<tr><td><i>' . escape_html($fullname) . '</i></td><td> - </td><td>' . escape_html($clubname) . '</td></tr>';
        }
        echo '</table>';
        echo '<p><a href="DOB6.php">Return</a> to search screen.</p>';
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DOB Search by Last Name</title>
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
<fieldset><legend>DOB Search by Last Name</legend>
<p><b><i>Enter the last name only to search across all clubs.</i></b></p>
<p><b>Last Name:</b> <input type="text" name="nameEntered" size="50" maxlength="50" value="<?= escape_html(get_post_string('nameEntered')) ?>"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Search"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
