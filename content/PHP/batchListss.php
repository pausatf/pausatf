<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();

$races = $pdo->query('SELECT * FROM RaceName ORDER BY RaceNumber')->fetchAll(PDO::FETCH_NUM);

$stmt = $pdo->prepare('SELECT * FROM Teams WHERE RaceNumber = :rnum ORDER BY ClubName');
$stmt->execute([':rnum' => 1]);
$clubs = $stmt->fetchAll(PDO::FETCH_NUM);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Scoresheet Listing</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<?php if ($races): ?>
<h2 style="color:red">List of Road &amp; XC Races</h2>
<p><i>(for scoresheet status)</i></p>
<table><tr>
<td valign="top">
<table>
<tr><th colspan="3"><h3>Race names and numbers</h3></th></tr>
<?php foreach ($races as $row): ?>
<tr>
<td style="color:red"><?= escape_html((string) $row[0]) ?></td>
<td><b><?= escape_html((string) $row[1]) ?></b></td>
<td><?= escape_html((string) $row[2]) ?></td>
</tr>
<?php endforeach; ?>
</table>
</td>
<td>&nbsp;&nbsp;</td>
<td valign="top">
<table>
<tr><th colspan="2"><h3>Club names and numbers</h3></th></tr>
<?php foreach ($clubs as $row): ?>
<tr>
<td style="color:red"><?= escape_html((string) $row[1]) ?></td>
<td><b><?= escape_html((string) $row[2]) ?></b></td>
</tr>
<?php endforeach; ?>
</table>
</td>
</tr></table>
<hr>
<?php else: ?>
<p><b>No records found</b></p>
<?php endif; ?>
</body>
</html>
