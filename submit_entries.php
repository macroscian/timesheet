<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include 'config.php';
$db = new SQLite3($config["db"]);
$input = json_decode(file_get_contents('php://input'),true);

$delete = $db->prepare('DELETE FROM entries WHERE Date = :start AND Bioinformatician=:Bioinformatician;');
$delete->bindValue(':Bioinformatician', $input['Bioinformatician'], SQLITE3_TEXT);
$delete->bindValue(':start', $input['recorddate'], SQLITE3_TEXT);
$delete->execute();

$insert = $db->prepare('INSERT INTO entries (Project, Bioinformatician, Scientist, Lab, Code, Hash, Type, Hours, Date, Note)
VALUES (:Project, :Bioinformatician, :Scientist, :Lab, :Code, :Hash, :Type, :Hours, :Date, :Note);');

$insert->bindValue(':Bioinformatician', $input['Bioinformatician'], SQLITE3_TEXT);
$insert->bindValue(':Date', $input['recorddate'], SQLITE3_TEXT);

foreach($input['entries'] as $php_row) {
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
