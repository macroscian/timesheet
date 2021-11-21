<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include 'config.php';
$db = new SQLite3($config["db"]);
$input = json_decode(file_get_contents('php://input'),true);

$delete = $db->prepare('DELETE FROM entries WHERE Date = :start AND Bioinformatician=:Bioinformatician;');
$delete->bindValue(':start', $input['recorddate'], SQLITE3_TEXT);

$my_id=explode( '_', $input['Bioinformatician'] )[0];

$on_date = $db->prepare('SELECT * FROM entries WHERE Date = :start;');
$on_date->bindValue(':start', $input['recorddate'], SQLITE3_TEXT);
$sqlite = $on_date->execute();
while($row = $sqlite->fetchArray(SQLITE3_ASSOC)){
    if (sha1($input['Bioinformatician'] . $row['Hash'] . $row['Date'])==$row['Bioinformatician'] or
	$row['Bioinformatician']==$input['Bioinformatician']) {
	$delete->bindValue(":Bioinformatician", $row['Bioinformatician']);
	$delete->execute();
    }
}

$latest = $db->prepare('SELECT Date FROM latest WHERE Bioinformatician=:Bioinformatician;');
$latest->bindValue(':Bioinformatician', $my_id, SQLITE3_TEXT);
$result = $latest->execute();
if ($result) {
    $result = $result->fetchArray(SQLITE3_NUM);
}

if (!$result) {
    $latest =$db->prepare('INSERT INTO latest ( Date, Bioinformatician) VALUES (:Date, :Bioinformatician);');
    $latest->bindValue(':Bioinformatician', $my_id, SQLITE3_TEXT);
    $latest->bindValue(':Date', $input['recorddate'], SQLITE3_TEXT);
    $latest->execute();
}

if ($result &&  $result[0] < $input['recorddate']) {
    $latest =$db->prepare('UPDATE latest SET Date=:Date WHERE Bioinformatician=:Bioinformatician;');
    $latest->bindValue(':Bioinformatician', $my_id, SQLITE3_TEXT);
    $latest->bindValue(':Date', $input['recorddate'], SQLITE3_TEXT);
    $latest->execute();
}



$insert = $db->prepare('INSERT INTO entries (Project, Bioinformatician, Scientist, Lab, Code, Hash, Type, Hours, Date, Note)
    VALUES (:Project, :Bioinformatician, :Scientist, :Lab, :Code, :Hash, :Type, :Hours, :Date, :Note);');

//$insert->bindValue(':Bioinformatician', $input['Bioinformatician'], SQLITE3_TEXT);

$insert->bindValue(':Date', $input['recorddate'], SQLITE3_TEXT);

foreach($input['entries'] as $php_row) {
    $insert->bindValue(':Bioinformatician', sha1($input['Bioinformatician'] . $php_row['Hash'] . $input['recorddate']), SQLITE3_TEXT);
    $insert->bindValue(':Project', $php_row['Project'], SQLITE3_TEXT);
    $insert->bindValue(':Scientist', $php_row['Scientist'], SQLITE3_TEXT);
    $insert->bindValue(':Lab', $php_row['Lab'], SQLITE3_TEXT);
    $insert->bindValue(':Code', $php_row['Code'], SQLITE3_INTEGER);
    $insert->bindValue(':Hash', $php_row['Hash'], SQLITE3_TEXT);
    if ($php_row['Type']=="scrnaeq") { # fix old typo
	$insert->bindValue(':Type', "scrnaseq", SQLITE3_TEXT);
    } else {
	$insert->bindValue(':Type', $php_row['Type'], SQLITE3_TEXT);
    }
    $insert->bindValue(':Hours', $php_row['Hours'], SQLITE3_FLOAT);
    $insert->bindValue(':Note', $php_row['Note'], SQLITE3_TEXT);
    $insert->execute();
}

$db->close();
unset($db);
?>
