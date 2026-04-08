<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(get_post_string('csrf_token'))) {
        die('Invalid request');
    }

    $rnumEntered = get_post_string('rnumEntered');
    $cnumEntered = get_post_string('cnumEntered');
    $msg = '';

    if ($rnumEntered === '') {
        $msg .= '<p><b>You did not enter a race number.</b></p>';
    }
    if ($cnumEntered === '') {
        $msg .= '<p><b>You did not enter a club number.</b></p>';
    }

    $rnum = (int) $rnumEntered;
    $cnum = (int) $cnumEntered;

    if ($rnum > 23) {
        $msg .= '<p><b>Race number cannot exceed 23.</b></p>';
    }

    if ($msg === '') {
        $pdo = get_pdo();

        $stmt = $pdo->prepare('SELECT * FROM RaceName WHERE RaceNumber = :rnum');
        $stmt->execute([':rnum' => $rnum]);

        if (!$stmt->fetch()) {
            echo '<p><b>The race number \'' . escape_html($rnumEntered) . '\' is not on the database. Consult the Listing of Races.</b></p>';
            exit();
        }

        $stmt2 = $pdo->prepare('SELECT * FROM Teams WHERE RaceNumber = :rnum AND ClubNumber = :cnum');
        $stmt2->execute([':rnum' => $rnum, ':cnum' => $cnum]);

        if (!$stmt2->fetch()) {
            echo '<p><b>Race ' . escape_html($rnumEntered) . ' Club ' . escape_html($cnumEntered) . ' is not on Teams database.</b></p>';
            exit();
        }

        setcookie('rnumCookie', (string) $rnum, ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        setcookie('cnumCookie', (string) $cnum, ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        header('Location: updatess2.php');
        exit();
    } else {
        echo $msg;
        echo '<p><b>Please try again.</b></p>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Scoresheet Status Update Form</title>
<link href="/PAstylesheetpg2.css" type="text/css" rel="stylesheet">
</head>
<body>
<div>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="60"><a href="/"><b>Home</b></a><br>
<a href="/data/pacontacts.html"><b>Contacts</b></a></td>
<td valign="top">
<form action="" method="post">
<?= csrf_field() ?>
<fieldset><legend>Scoresheet Status Update Form</legend>
<p><b>Select Race:</b> <select name="rnumEntered">
<option value="01">NorCal</option>
<option value="02">Stanford 8K</option>
<option value="03">Across the Bay 12K</option>
<option value="04">Zippy 5K</option>
<option value="05">Marin 10K</option>
<option value="06">Shriners 8K</option>
<option value="07">FF Davis Mile</option>
<option value="08">HP 10K</option>
<option value="09">Empire XC</option>
<option value="10">Santa Cruz XC</option>
<option value="11">Golden Gate XC</option>
<option value="12">Jamba Juice 5K XC</option>
<option value="13">Paso Robles 10K</option>
<option value="14">Garrin XC</option>
<option value="15">Presidio XC</option>
<option value="16">Humboldt Half</option>
<option value="17">Shoreline XC</option>
<option value="18">Tamalpa XC</option>
<option value="19">Fleet Feet XC</option>
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
