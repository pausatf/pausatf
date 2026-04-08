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
    $stmt = $pdo->query('SELECT * FROM Teams');
    $teams = $stmt->fetchAll(PDO::FETCH_NUM);

    if (!$teams) {
        $results = '<p><b>No records found in Teams table</b></p>';
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO Teams (RaceNumber, ClubNumber, ClubName, MO, MM, MS, MSS, MV, WO, WM, WS, WSS, WV)
             VALUES (:rnum, :cnum, :cname, :mo, :mm, :ms, :mss, :mv, :wo, :wm, :ws, :wss, :wv)'
        );

        foreach ($teams as $row) {
            for ($rnum = 2; $rnum <= 23; $rnum++) {
                $insert->execute([
                    ':rnum' => $rnum, ':cnum' => (int) $row[1], ':cname' => $row[2],
                    ':mo' => $row[3], ':mm' => $row[4], ':ms' => $row[5],
                    ':mss' => $row[6], ':mv' => $row[7], ':wo' => $row[8],
                    ':wm' => $row[9], ':ws' => $row[10], ':wss' => $row[11], ':wv' => $row[12],
                ]);
            }
            $results .= '<p>Entries for ' . escape_html((string) $row[2]) . ' created for races 2-23</p>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Batch Utility - Generate Full Database</title>
</head>
<body>
<h2>Batch Utility: Generate Full Scoresheet Database</h2>
<p><i>Copies all teams from existing entries into races 2 through 23.</i></p>
<?= $results ?>
<?php if ($results === ''): ?>
<form action="" method="post">
<?= csrf_field() ?>
<p>This will insert entries for all existing teams into races 2-23.</p>
<input type="submit" value="Generate Database" onclick="return confirm('This will create many records. Continue?')">
</form>
<?php endif; ?>
</body>
</html>
