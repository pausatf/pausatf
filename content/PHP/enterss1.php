<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$results = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $rnumEntered = get_post_string('rnumEntered');
    $cnumEntered = get_post_string('cnumEntered');
    $msg = '';

    if ($rnumEntered === '') {
        $msg .= '<p><b>You did not select a race.</b></p>';
    }
    if ($cnumEntered === '') {
        $msg .= '<p><b>You did not select a club.</b></p>';
    }

    if ($msg === '') {
        $pdo = get_pdo();
        $rnum = (int) $rnumEntered;
        $cnum = (int) $cnumEntered;

        $stmt = $pdo->prepare('SELECT * FROM RaceName WHERE RaceNumber = :rnum');
        $stmt->execute([':rnum' => $rnum]);
        $race = $stmt->fetch(PDO::FETCH_NUM);

        if (!$race) {
            $results = '<p><b>The race number \'' . escape_html($rnumEntered) . '\' is not on the database. Contact webmaster.</b></p>';
        } else {
            $results .= '<h3>Scoresheet status for ' . escape_html((string) $race[1]) . '</h3>';
            $results .= '<i>scheduled ' . escape_html((string) $race[2]) . '</i><br>';

            $stmt2 = $pdo->prepare('SELECT * FROM Teams WHERE RaceNumber = :rnum AND ClubNumber = :cnum');
            $stmt2->execute([':rnum' => $rnum, ':cnum' => $cnum]);
            $team = $stmt2->fetch(PDO::FETCH_NUM);

            if (!$team) {
                $results .= '<p><b>Race ' . escape_html($rnumEntered) . ' Team ' . escape_html($cnumEntered) . ' is not on database. Contact webmaster.</b></p>';
            } else {
                $results .= '<h3>For ' . escape_html((string) $team[2]) . '</h3>';
                $categories = [
                    ['Open Men', $team[3], $team[13]],
                    ['Master Men', $team[4], $team[14]],
                    ['Senior Men', $team[5], $team[15]],
                    ['Super Senior Men', $team[6], $team[16]],
                    ['Veteran Men', $team[7], $team[17]],
                    ['Open Women', $team[8], $team[18]],
                    ['Master Women', $team[9], $team[19]],
                    ['Senior Women', $team[10], $team[20]],
                    ['Super Senior Women', $team[11], $team[21]],
                    ['Veteran Women', $team[12], $team[22]],
                ];
                foreach ($categories as [$label, $a, $b]) {
                    $results .= '<p>' . escape_html($label) . ' &nbsp; ' . escape_html((string) $a) . ' &nbsp; B Team &nbsp;' . escape_html((string) $b) . '</p>';
                }
                $results .= '<hr>';
            }
        }
    } else {
        $results = $msg . '<p><b>Please try again.</b></p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Scoresheet Status Entry Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<?= $results ?>
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Scoresheet Status Entry Form</legend>
<p><i><b>The Road or XC Scorer updates this database when he receives a scoresheet.<br>
If your status is N (for No) and you submitted a scoresheet, but did not receive your confirmation copy,<br>
then send the scoresheet directly to the scorer.</b></i></p>
<p><b>Select Race:</b> <select name="rnumEntered">
<option value="01">NorCal</option>
<option value="02">Stanford 8K</option>
<option value="03">Across the Bay 12K</option>
<option value="04">Zippy 5K</option>
<option value="05">Marin 10K</option>
<option value="06">Shriners 8K</option>
<option value="07">FF Davis Mile</option>
<option value="08">FF Davis Mile</option>
<option value="09">Susan 8K</option>
<option value="10">Santa Cruz XC</option>
<option value="11">Empire XC</option>
<option value="12">Golden Gate XC</option>
<option value="13">Jamba Juice 5K</option>
<option value="14">Paso Robles 10K</option>
<option value="15">Presidio XC</option>
<option value="16">Folsom (Aggies) XC</option>
<option value="17">Humboldt Half</option>
<option value="18">Shoreline XC</option>
<option value="19">Tamalpa XC</option>
<option value="20">Clarksburg</option>
<option value="21">PA Champs XC</option>
<option value="22">Seagate 5K</option>
<option value="23">CIM</option>
<option value="24">Xmas Relays</option>
</select></p>
<p><b>Select Club:</b>
<select name="cnumEntered">
<option value="178">adidas Transports</option>
<option value="111">ASCIS Aggies</option>
<option value="104">Buffalo Chips</option>
<option value="269">Cal Triathlon</option>
<option value="143">Empire Runners</option>
<option value="195">Fleet Feet Sacramento</option>
<option value="135">Golden Valley Harriers</option>
<option value="124">Humboldt TC</option>
<option value="196">Iguanas</option>
<option value="115">Impalas</option>
<option value="113">New Balance Excelsior</option>
<option value="132">Nike Farm Team</option>
<option value="220">Pacific Striders</option>
<option value="116">River City Rebels</option>
<option value="233">Running Zone/Mizuno</option>
<option value="126">San Luis Distance Club</option>
<option value="137">Santa Cruz TC</option>
<option value="32">Silver State Striders</option>
<option value="154">San Jose Spartans</option>
<option value="100">Tamalpa</option>
<option value="177">UCSC Slugs</option>
<option value="133">Wed Night Laundry</option>
<option value="117">West Valley J&amp;S</option>
<option value="110">West Valley TC</option>
<option value="119">Wolfpack Intrnl</option>
</select></p>
</fieldset>
<div style="text-align:center"><input type="submit" name="submit" value="Get Scoresheet Status"></div>
</form>
</td></tr></table>
</div>
</body>
</html>
