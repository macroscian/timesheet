<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'),true);
$hashes = $input['hashes'];

$db = new SQLite3('/camp/stp/babs/www/kellyg/timesheets.db');


$qMarks = str_repeat('?,', count($hashes) - 1) . '?';
$sth = $db->prepare("SELECT Hash, SUM(hours) as hours FROM entries WHERE (Bioinformatician=?) AND Hash IN ($qMarks) GROUP BY Hash;");
$sth->bindValue(1, $input['id'], SQLITE3_TEXT);
$i = 2;
foreach($hashes as $hash) {
    $sth->bindValue($i, $hash, SQLITE3_TEXT);
    $i += 1;
}
$result = $sth->execute();


$entries = array();
while($row = $result->fetchArray(SQLITE3_ASSOC)){
    $entries[] = $row;
} 
$db->close();
unset($db);
echo json_encode($entries);
?>
