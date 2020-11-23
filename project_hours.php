<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'),true);
$hashes = $input['hashes'];

$db = new SQLite3('/camp/stp/babs/www/kellyg/timesheets.db');

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
foreach($ranges as $range) {
    $qMarks = str_repeat('?,', count($hashes) - 1) . '?';
    $sth = $db->prepare("SELECT Hash, SUM(hours) as hours FROM entries WHERE (Bioinformatician=?) AND and Date>=:start AND date<:end AND Hash IN ($qMarks) GROUP BY Hash;");
    $sth->bindValue(1, $input['id'], SQLITE3_TEXT);
    $sth->bindValue(2, date('Y-m-d', strtotime($range['start'])), SQLITE3_TEXT);
    $sth->bindValue(3, date('Y-m-d', strtotime($range['end'])), SQLITE3_TEXT);
    $i = 4;
    foreach($hashes as $hash) {
	$sth->bindValue($i, $hash, SQLITE3_TEXT);
	$i += 1;
    }
    $result = $sth->execute();

    while($row = $result->fetchArray(SQLITE3_ASSOC)){
	$entries[] = $row;
    } 
}




$db->close();
unset($db);
echo json_encode($entries);
?>
