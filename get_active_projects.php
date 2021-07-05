<?php
$buffered_line = "[";
$fname = "yml/" . $_GET["id"] . ".yml";
if (!file_exists($fname)) {
    echo "[]";
} else {
    $fh = fopen($fname, "r");
    $first=true;
    while (($line = fgets($fh)) !== false) {
	if ($line == "\n") {continue;}
	if (substr($line,0,1)=="-") {
	    if ($buffered_line=="[") {
		$line="{";
	    } else {
		$line = "},{";
	    }
	    $first=true;
	} elseif (substr($line, 0,2)=="  ")  {
	    $line = preg_replace('/  ([^:]+): (.*)/', '${1}: "${2}"', $line);
	    if (!($first)) {
		$line = "," . $line;
	    }
	    $first=false;
	}
	echo $buffered_line;
	$buffered_line = $line;
    }
    echo $buffered_line;
    echo "}];";
    fclose($fh);
}
?>
