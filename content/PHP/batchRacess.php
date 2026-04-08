<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

require_admin();

$results = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $rnum = get_post_int('rnum');

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM Teams WHERE RaceNumber = 1');
    $stmt->execute();
    $teams = $stmt->fetchAll(PDO::FETCH_NUM);

    if (!$teams) {
        $results = '<p><b>No teams found in Race 1 to copy</b></p>';
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO Teams (RaceNumber, ClubNumber, ClubName, MO, MM, MS, MSS, MV, WO, WM, WS, WSS, WV)
             VALUES (:rnum, :cnum, :cname, :mo, :mm, :ms, :mss, :mv, :wo, :wm, :ws, :wss, :wv)'
        );

        foreach ($teams as $row) {
            $insert->execute([
                ':rnum' => $rnum, ':cnum' => (int) $row[1], ':cname' => $row[2],
                ':mo' => $row[3], ':mm' => $row[4], ':ms' => $row[5],
                ':mss' => $row[6], ':mv' => $row[7], ':wo' => $row[8],
                ':wm' => $row[9], ':ws' => $row[10], ':wss' => $row[11], ':wv' => $row[12],
            ]);
            $results .= '<p>Record for race ' . $rnum . ' &nbsp; ' . escape_html((string) $row[2]) . ' created</p>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Batch Create Race Entries</title>
</head>
<body>
<h2>Batch Create Race Entries from Race 1 Template</h2>
<?= $results ?>
<?php if ($results === ''): ?>
<form action="" method="post">
<?= csrf_field() ?>
<p><b>Target Race Number:</b> <input type="number" name="rnum" min="1" max="24" required></p>
<p>This will copy all team entries from Race 1 into the specified race number.</p>
<input type="submit" value="Create Entries">
</form>
<?php endif; ?>
</body>
</html>
