<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

$results = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $cnum = get_post_int('cnum', 999);
    $cname = get_post_string('cname', 'Name of new Team');

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO Teams (RaceNumber, ClubNumber, ClubName, MO, MM, MS, MSS, MV, WO, WM, WS, WSS, WV)
         VALUES (:rnum, :cnum, :cname, :mo, :mm, :ms, :mss, :mv, :wo, :wm, :ws, :wss, :wv)'
    );

    for ($rnum = 1; $rnum <= 23; $rnum++) {
        $stmt->execute([
            ':rnum' => $rnum, ':cnum' => $cnum, ':cname' => $cname,
            ':mo' => 'N', ':mm' => 'N', ':ms' => 'N', ':mss' => 'N', ':mv' => 'N',
            ':wo' => 'N', ':wm' => 'N', ':ws' => 'N', ':wss' => 'N', ':wv' => 'N',
        ]);
        $results .= '<p>Record for race ' . $rnum . ' &nbsp; ' . escape_html($cname) . ' created</p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Batch Create Club Scoresheets</title>
</head>
<body>
<h2>Batch Create Club Scoresheet Entries</h2>
<?= $results ?>
<?php if ($results === ''): ?>
<form action="" method="post">
<?= csrf_field() ?>
<p><b>Club Number:</b> <input type="number" name="cnum" value="999"></p>
<p><b>Club Name:</b> <input type="text" name="cname" size="50" value="Name of new Team"></p>
<input type="submit" value="Create Entries for All Races">
</form>
<?php endif; ?>
</body>
</html>
