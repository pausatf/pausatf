<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

$pdo = get_pdo();
$maxRecords = 6;
$records = [];
$messages = '';
$searchDone = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $hasKeys = get_post_int('key1') > 0;

    if ($hasKeys) {
        // 3rd pass: process updates/deletes
        $validKeys = $_SESSION['dob5_valid_keys'] ?? [];

        try {
            for ($i = 1; $i <= $maxRecords; $i++) {
                $key = get_post_int("key{$i}");
                $display = get_post_string("Display{$i}");
                $action = get_post_string("UD{$i}");

                if ($key <= 0) {
                    continue;
                }

                if (!in_array($key, $validKeys, true)) {
                    $messages .= '<p>Invalid record key submitted. Operation rejected.</p>';
                    continue;
                }

                try {
                    if ($action === 'U') {
                        $stmt = $pdo->prepare('UPDATE DOBNames SET FullName = :name WHERE UKey = :key');
                        $stmt->execute([':name' => $display, ':key' => $key]);
                        $messages .= $stmt->rowCount() === 1
                            ? '<p>' . escape_html($display) . ' successfully Updated</p>'
                            : '<p>' . escape_html($display) . ' was not changed</p>';
                    } elseif ($action === 'D') {
                        $stmt = $pdo->prepare('DELETE FROM DOBNames WHERE UKey = :key');
                        $stmt->execute([':key' => $key]);
                        $messages .= $stmt->rowCount() === 1
                            ? '<p>' . escape_html($display) . ' successfully Deleted</p>'
                            : '<p>' . escape_html($display) . ' was not Deleted</p>';
                    }
                } catch (PDOException $e) {
                    error_log('DOB5 operation failed for key ' . $key . ': ' . $e->getMessage());
                    $messages .= '<p><b>A database error occurred for ' . escape_html($display) . '.</b></p>';
                }
            }
        } finally {
            unset($_SESSION['dob5_valid_keys']);
        }
        $messages .= '<p><a href="DOB5.php">Return</a> to search screen.</p>';
    } else {
        // 2nd pass: search and display results for editing
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
            try {
                $stmt = $pdo->prepare('SELECT * FROM DOBNames WHERE LastName = :lname AND Club = :cnum');
                $stmt->execute([':lname' => $nameEntered, ':cnum' => (int) $cnumEntered]);
                $rows = $stmt->fetchAll(PDO::FETCH_NUM);

                if (!$rows) {
                    $messages = '<p><b>Last Name ' . escape_html($nameEntered) . ' for club ' . escape_html($cnumEntered) . ' is not on the database.</b></p>';
                    $messages .= '<p><a href="DOB5.php">Return</a> to search screen.</p>';
                } elseif (count($rows) > $maxRecords) {
                    $messages = '<p>' . count($rows) . ' names is too many to work with. Limited to ' . $maxRecords . ' names.</p>';
                    $messages .= '<p><a href="DOB5.php">Return</a> to search screen.</p>';
                } else {
                    $searchDone = true;
                    $sessionKeys = [];
                    foreach ($rows as $idx => $row) {
                        $records[$idx + 1] = ['key' => (int) $row[3], 'name' => (string) $row[1]];
                        $sessionKeys[] = (int) $row[3];
                    }
                    $_SESSION['dob5_valid_keys'] = $sessionKeys;
                }
            } catch (PDOException $e) {
                error_log('DOB5 search failed: ' . $e->getMessage());
                $messages = '<p><b>A database error occurred. Please try again later.</b></p>';
            }
        } else {
            $messages = $msg . '<p><b>Please try again.</b></p>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DOB Update/Delete Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<?= $messages ?>

<?php if ($searchDone): ?>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Update or Delete Athletes</legend>
<p><b>For each name, select U (Update), D (Delete), or leave blank to skip. You can edit the name before updating.</b></p>
<?php foreach ($records as $i => $rec): ?>
<p>
<input type="hidden" name="key<?= $i ?>" value="<?= $rec['key'] ?>">
<select name="UD<?= $i ?>"><option value="">--</option><option value="U">Update</option><option value="D">Delete</option></select>
<input type="text" name="Display<?= $i ?>" size="50" value="<?= escape_html($rec['name']) ?>">
</p>
<?php endforeach; ?>
<?php for ($j = count($records) + 1; $j <= $maxRecords; $j++): ?>
<input type="hidden" name="key<?= $j ?>" value="0">
<input type="hidden" name="Display<?= $j ?>" value="">
<input type="hidden" name="UD<?= $j ?>" value="">
<?php endfor; ?>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Process Changes"></div>
</form>

<?php elseif ($messages === ''): ?>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>DOB Update/Delete Search</legend>
<p><b><i>Select the Club and enter the last name of the athlete to update or delete.</i></b></p>
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
<p><b>Last Name:</b> <input type="text" name="nameEntered" size="50" maxlength="50"></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Search"></div>
</form>
<?php endif; ?>
</body>
</html>
