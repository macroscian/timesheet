<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    
    <title>Crick Biostatistics ticketing system</title>
  </head>
  <body>
    <div class="container">
      <h1>Biostats Ticketing</h1>
      <?php
      $url = preg_replace('/process.php$/', '', 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
      $safe_title = preg_replace("/[^a-zA-Z0-9]/", "_", $_POST["project"]);
      $proj=(is_dir("/camp/stp/babs/working/{$_POST['bioinformatician']}/Projects"))?"Projects":"projects";
      $path = "/camp/stp/babs/working/{$_POST['bioinformatician']}/$proj/${_POST['lab']}/"
	    . explode("@", $_POST["scientist"])[0]
	    . "/$safe_title";
      // $hash = md5($path);
      $sci_initials = strtolower(preg_replace("/(\b[a-zA-Z])[a-zA-Z]*[^a-zA-Z]*/", "$1",
				   preg_replace("/@.*/", "", $_POST["scientist"])));
      $db = new SQLite3('/camp/stp/babs/www/kellyg/timesheets.db');
      $insert = $db->prepare('INSERT INTO projects (Project, Bioinformatician, Scientist, Lab)
VALUES (:Project, :Bioinformatician, :Scientist, :Lab);');
      $insert->bindValue(':Project', $_POST["project"], SQLITE3_TEXT);
      $insert->bindValue(':Bioinformatician', $_POST["bioinformatician"], SQLITE3_TEXT);
      $insert->bindValue(':Scientist', $_POST["scientist"], SQLITE3_TEXT);
      $insert->bindValue(':Lab', $_POST["lab"], SQLITE3_TEXT);
      $insert->execute();
      $hash = $sci_initials . $db->lastInsertRowID() ;
      $db->close();
      unset($db);
      $sci_name =  ucwords(str_replace(".", " ",strtolower(explode("@", $_POST["scientist"])[0])));
      date_default_timezone_set("Europe/London");
      $date = date("Y-m-d");
      $lab_email = $_POST["lab"];
      if (strlen($lab_email) > 7) {
	  $lab_email=substr_replace($lab_email, "",  6, strlen($lab_email)-7);
      }
      $record =  file_get_contents("template.txt");
      $record = str_replace("{{project}}", $_POST["project"], $record);
      $record = str_replace("{{bioinformatician}}", $_POST["bioinformatician"], $record);
      $record = str_replace("{{scientist}}", $_POST["scientist"], $record);
      $record = str_replace("{{lab}}", $_POST["lab"], $record);
      $record = str_replace("{{date}}", $date, $record);
      $record = str_replace("{{path}}", $path, $record);
      $record = str_replace("{{hash}}", $hash, $record);
      if (array_key_exists("code", $_POST)) {
	  $record = str_replace("{{code}}", $_POST["code"], $record);
	  $record = str_replace("{{estimate}}", $_POST["estimate"], $record);
      }
      $record = str_replace("{{type}}", $_POST["type"], $record);
      $fname = "/camp/stp/babs/www/kellyg/tickets/{$_POST['bioinformatician']}_$hash.yml";
      file_put_contents($fname, $record);
      echo "<p>Your ticket has been submitted with ID $hash</p>";
      $headers = array(
	  'From' => 'bioinformatics@crick.ac.uk',
	  'Reply-To' => 'bioinformatics@crick.ac.uk',
	  'X-Mailer' => 'PHP/' . phpversion()
      );
      $people = json_decode(file_get_contents("babs_staff.json"),true);
      if (array_key_exists($_POST["bioinformatician"], $people)){
	  $person = $people[$_POST["bioinformatician"]]['first'];
      } else {
	  $person = $_POST["bioinformatician"];
      }
      $slackurl = 'https://hooks.slack.com/services/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';
      $data = array("text" => "New ticket for {$person} from {$_POST['scientist']}\n {$_POST['project']}");
      $postdata = json_encode($data);
      $ch = curl_init($slackurl);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

      switch ($_POST["who"]) {
	  case "sci": 
	      echo "<p>{$lab_email}@crick.ac.uk has been emailed to assign a cost-code and initial amount of time that BABS can spend on this project.
 You will receive an email when it has been approved  - please follow up with them if you don't receive this. If that email is incorrect please inform BABS.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk, {$lab_email}@crick.ac.uk",
		   "Approval required: {$_POST['project']}" ,
		   "{$_POST['scientist']} has requested we work on a project {$_POST['project']}. It is necessary for us to seek PI approval before we start work on this, so please visit the following page to allocate a cost-code and initial estimate of time you permit us to spend working on the project.\n\n {$url}approval.php?project=$hash \n\nIf you are not the PI, please reply to this email to let us know, and accept our apologies.",
		   $headers);
	      $result = curl_exec($ch);
	      break;
	  case "pi": 
	      echo "<p>Thank you for completing this - an email has been sent to the scientist and the allocated member of BABS, and you will receive a copy.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk,  {$lab_email}@crick.ac.uk,  {$_POST['scientist']}",
		   "Approval received:  {$_POST['project']}",
		   "We have received PI approval for {$_POST['estimate']} hours to be spent using code {$_POST['code']} on {$_POST['project']}.\n",
		   $headers);
	      break;
	  case "scipi":
	      echo "<p>Thank you for completing this - an email has been sent to you, your PI ({$lab_email}) and the allocated member of BABS.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk,  {$lab_email}@crick.ac.uk, {$_POST['scientist']}",
		   "BABS Project requested: {$_POST['project']}",
		   "Bioinformatics and Biostatistics STP have received a work request for {$_POST['estimate']} hours to be spent using code {$_POST['code']} on {$_POST['project']}. If this is incorrect or unapproved please inform us immediately. \n",
		   $headers);
	      $result = curl_exec($ch);
	      break;
      }
      ?>
      </p>
    </div>
  </body>
</html>

