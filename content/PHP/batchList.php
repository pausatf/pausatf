<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$stmt = $pdo->query('SELECT club_no, club_name, approved, primary_contact, primary_phone FROM tblCLUBS ORDER BY club_no');
$rows = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Club Listing</title>
</head>
<body>
<?php if ($rows): ?>
<h2 style="color:red">List of Active Clubs</h2>
<h3><i>with current year's approved status</i></h3>
<p>Club#, Club Name, Contact, Phone, Approved Status (Y/N)</p>
<?php foreach ($rows as $row):
    $paid = match ($row['approved']) { 'B', 'E' => 'Y', '' => 'N', default => $row['approved'] };
    if ((int) $row['club_no'] === 0) continue;
?>
<p><?= escape_html((string) $row['club_no']) ?> &nbsp;&nbsp; <?= escape_html((string) $row['club_name']) ?><br>
<?= escape_html((string) $row['primary_contact']) ?> &nbsp; <?= escape_html((string) $row['primary_phone']) ?> &nbsp; <?= escape_html($paid) ?></p>
<?php endforeach; ?>
<hr>
<?php else: ?>
<p><b>No records found</b></p>
<?php endif; ?>
</body>
</html>
