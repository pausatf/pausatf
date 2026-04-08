<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$stmt = $pdo->query('SELECT * FROM DOBClubs');
$clubs = $stmt->fetchAll(PDO::FETCH_NUM);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DOB Club Listing</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<h2 style="color:blue">PA/USATF Youth Clubs</h2>
<?php if ($clubs): ?>
<?php foreach ($clubs as $row): ?>
<p><b><?= escape_html((string) $row[0]) ?> &nbsp;&nbsp;&nbsp;&nbsp;<?= escape_html((string) $row[1]) ?></b></p>
<?php endforeach; ?>
<?php else: ?>
<p><b>No data returned. Contact webmaster.</b></p>
<?php endif; ?>
</td></tr></table>
</div>
</body>
</html>
