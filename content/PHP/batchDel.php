<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

$results = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Teams WHERE RaceNumber = :rnum');

    for ($rnum = 2; $rnum <= 23; $rnum++) {
        $stmt->execute([':rnum' => $rnum]);
        $results .= '<p>Record for race ' . $rnum . ' deleted</p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Batch Delete Scoresheets</title>
</head>
<body>
<h2>Batch Delete Scoresheet Records</h2>
<?= $results ?>
<?php if ($results === ''): ?>
<form action="" method="post">
<?= csrf_field() ?>
<p>This will delete all Teams records for races 2 through 23.</p>
<input type="submit" value="Confirm Delete" onclick="return confirm('Are you sure?')">
</form>
<?php endif; ?>
</body>
</html>
