<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include 'config.php';
date_default_timezone_set('Europe/London');

function get_first_monday($ym) {
    $date = new DateTime($ym . "-01");
    $dow_of_first = $date->format("N");
    $next_mon_offset = (1 - $dow_of_first);
    if ($next_mon_offset < 0) {
	$next_mon_offset += 7;
    }
    $date->add(new DateInterval("P" . $next_mon_offset . "D"));
    return $date->format('Y-m-d');
}

function inc_weekday(&$date) { # change parameter to next working day, and return flag of whether we've traversed a weekend
    $oneday = new DateInterval('P1D');
    $new_week = $date->format("N")>=5;
    while ($date->add($oneday)->format("N") >=6) {
    };
    return $new_week;
}
function dec_weekday(&$date) {
    $oneday = new DateInterval('P1D');
    $new_week = $date->format("N")==1;
    while ($date->sub($oneday)->format("N") >=6) {
    };
    return $new_week;
}


$input = json_decode(file_get_contents('php://input'),true);
$db = new SQLite3($config["db"]);
$n = isset($input['n'])?$input['n']:'1';
$fday = "Y-m-d";
$fweek = "o\WW";

$filters = array_intersect_key( # only allow filters to be built with these fields:
    $input,
				array(
				    'Bioinformatician' => true,
				    'Project' => true,
				    'Scientist' => true,
				    'Lab' => true,
				    'Code' => true,
				    'Hash' => true,
				    'Type' => true
				)
);

$where = ""; # build prepared filter. OK to finish with AND as we always add another condition on
foreach ( $filters as $key => $value ) {
    $where .= $key . "=:" . $key . " AND ";
}


# If we didn't specify a timescale, work out from last filled-entered date
if (count(array_intersect_key($input, array('day'  => 1, 'week'  => 2, 'month'  => 3, 'todate' => 3)))==0) {
    $today = new DateTime();
    $statement = $db->prepare('SELECT MAX(Date) FROM entries WHERE ' . $where . 'Date <= :now;');
    foreach ( $filters as $key => $value ) {
	$statement->bindValue(':' . $key, $input[$key], SQLITE3_TEXT);
    }
    $statement->bindValue(':now', $today->format($fday), SQLITE3_TEXT);
    $result = $statement->execute()->fetchArray(SQLITE3_NUM)[0];
    if (!$result) { # no entries, so let's start from monday
	$dow = $today->format("N") + 0; # monday=1..., so days since sunday
	$today->sub(new DateInterval("P" . $dow . "D")); # will take us back to sunday, but 'move' increments
	$input['end'] = $today->format($fday);
	$input['move'] = "static";
	#$input['week'] = "2020W32";
    } else {
	$input['end'] = $result;
	$input['move'] = "static";
    }
}

$oneday = new DateInterval('P1D');
$today = new DateTime();

if (array_key_exists('move', $input)) {
    if ($input['move']=="forward" or $input['move']=="static") {
	$newstart = new DateTime($input['end']);
	if ($input['move']=="forward") {# 'static' moves don't need to be adjusted for
	    $newstart->sub($oneday); # end was open interval, not included, in original. newstart now last day of old range
	}
	$switched_week = inc_weekday($newstart); # ie first workday after old range
	$weekrange = "0 and 6"; # if case of newly entered week, we're now pointing at monday. So week extends from now to 6 days hence incl.
	$delta_day = 7; #  and if 7 days time is less than or equal to today then the whole week is past
    }  else { # so we're going backwards
	$newstart = new DateTime($input['start']);
	$switched_week = dec_weekday($newstart);
	$weekrange = "-4 and 2";# in case of newly entered week, we're now pointing at friday.  Monday is 4 days past, Sunday 2 days hence.
	$delta_day = 3; # and if 3 days time is less than or equal to today then the whole week is past
    }
    if ($switched_week) {
	# logic should be - if our new week already has day/week mode entries, stick with that
	# otherwise historical whole weeks are week mode, present and future are day mode
	#	    $statement = $db->prepare('SELECT MIN(Date) FROM entries WHERE ' . $where . 'Date >= :start AND Date < :end;');
	$statement = $db->prepare('SELECT MIN(Date) FROM entries WHERE ' . $where . 'julianday(Date)-julianday(:start) BETWEEN ' . $weekrange . ';');
	foreach ( $filters as $key => $value ) {
	    $statement->bindValue(':' . $key, $input[$key], SQLITE3_TEXT);
	}
	$statement->bindValue(':start', $newstart->format($fday), SQLITE3_TEXT);
	$result = $statement->execute()->fetchArray(SQLITE3_NUM)[0];
	if (!$result) { # an empty week
	    $interval = date_diff($newstart, $today)->format("%r%a");
	    if ($interval >= $delta_day) {# true if week is fully in the past.
		$input['week']=$newstart->format($fweek);
		unset($input['day']);
		unset($input['month']);
	    } else {
		$input['day']=$newstart->format($fday);
		unset($input['week']);
		unset($input['month']);
	    }
	} else { # there's entries sometime this week
	    $first = new DateTime($result);
	    if ($first->format("N") > 5) { #it it was week-mode
		$input['week']=$newstart->format($fweek);
		unset($input['day']);
		unset($input['month']);
	    } else {
		$input['day']=$newstart->format($fday);
		unset($input['week']);
		unset($input['month']);
	    }
	}
    } else {
	$input['day']=$newstart->format($fday);
	unset($input['week']);
	unset($input['month']);
    }
} 


if (array_key_exists('day',$input)) {
    $date = new DateTime($input['day']);
    $start = $date->format($fday);
    $end = $date->add(new DateInterval("P" . $n . "D"))->format($fday);
}

if (array_key_exists('week',$input)) {
    $date = new DateTime();
    $date->setISODate(substr($input['week'], 0, 4),
		      substr($input['week'], 5,2));
    $start = $date->format($fday);
    $end = $date->add(new DateInterval("P" . $n . "W"))->format($fday);
}

if (array_key_exists('month',$input)) {
    // Monday of the week that includes the month's 1st friday
    $start = get_first_monday($input['month']);
    // Similar monday, for n months after (ok as 'end' is a strict inequality)
    $date = new DateTime($input['month'] . "-01");
    $date->add(new DateInterval("P" . $n ."M"));
    $end = get_first_monday($date->format("Y-m"));
}

if (array_key_exists('year',$input)) {
    // Monday of the week that includes the month's 1st friday
    $start = new DateTime($input['year'] . "01-01");
    $end = new DateTime(($input['year'] + 1) . "01-01");
}

if (array_key_exists('todate', $input)) {
    $start = new DateTime("2000-01-01");
    $start = $start->format($fday);
    $end = new DateTime("tomorrow");
    $end = $end->format($fday);
}


$statement = $db->prepare('SELECT * FROM  entries WHERE ' . $where . 'Date >= :start AND Date < :end;');
//$statement = $db ->prepare('select * FROM (SELECT * FROM  entries WHERE ' . $where . 'Date >= :start AND Date < :end) AS range LEFT JOIN (select Hash, Min(Date)>= :start as isNew from entries group by Hash)  AS first ON range.Hash=first.Hash;');
foreach ( $filters as $key => $value ) {
    $statement->bindValue(':' . $key, $input[$key], SQLITE3_TEXT);
}

$statement->bindValue(':start', $start, SQLITE3_TEXT);
$statement->bindValue(':end', $end, SQLITE3_TEXT);
$result = $statement->execute();

$input['start'] = $start;
$input['end']=$end;
$end = new DateTime($end);
$end->sub(new DateInterval("P1D")); # return the last day of the current range, for future reporting purposes
$input['recorddate'] = $end->format($fday);


$entries = array();
while($row = $result->fetchArray(SQLITE3_ASSOC)){
    $entries[] = $row;
} 
$db->close();
unset($db);
$input['entries'] = $entries;
echo json_encode($input);
?>
