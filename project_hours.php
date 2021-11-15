<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include 'config.php';
$input = json_decode(file_get_contents('php://input'),true);
$hashes = $input['hashes'];

$db = new SQLite3($config["db"]);

$ranges = array(
    "All" => array("start" => "first day of january 2000",
		  "end" => "tomorrow"),
    "This week" => array("start" => "this monday",
			"end" => "next monday"),
    "Last week" => array("start" => "last monday",
			"end" => "this monday"),
    "This month" => array("start" => "first day of this month",
			 "end" => "first day of next month"),
    "Last month" => array("start" => "first day of last month",
			 "end" => "first day of this month"),
    "This year" => array("start" => "first day of january this year",
			"end" => "first day of january next year"),
    "Last year" => array("start" => "first day of january last year",
			"end" => "first day of january this year")
);
$entries = array();

function my_anon_rows($sqlite, $id, $my_range) {
    $range_entries=array();
    while($row = $sqlite->fetchArray(SQLITE3_ASSOC)){
	if (sha1($id . $row['Hash'] . $row['Date'])==$row['Bioinformatician'] or $row['Bioinformatician']==$id) {
	    if (array_key_exists($row['Hash'], $range_entries)) {
		$range_entries[$row['Hash']]['hours'] += $row['Hours'] ;
	    } else {
		$range_entries[$row['Hash']] = Array('Hash' => $row['Hash'],
						     'hours' => $row['Hours'],
						     'range' => $my_range);
	    }
	}
    }
    return $range_entries;
}

foreach($ranges as $key => $range) {
    $qMarks = str_repeat('?,', count($hashes) - 1) . '?';
    $sth = $db->prepare("SELECT Bioinformatician, Date, Hash, Hours FROM entries WHERE  Date>=:start AND date<:end AND Hash IN ($qMarks) ORDER BY Hash;");
    $sth->bindValue(1, date('Y-m-d', strtotime($range['start'])), SQLITE3_TEXT);
    $sth->bindValue(2, date('Y-m-d', strtotime($range['end'])), SQLITE3_TEXT);
    $i = 3;
    foreach($hashes as $hash) {
	$sth->bindValue($i, $hash, SQLITE3_TEXT);
	$i += 1;
    }
    $result = $sth->execute();
    $agg = my_anon_rows($result, $input['id'], $key);
    foreach ($agg as $key => $ent) {
	$entries[] = $ent;
    }
}

$db->close();
unset($db);
echo json_encode($entries);
?>
