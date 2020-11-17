<?php
?>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    
    <title>Crick Biostatistics ticketing system</title>
  </head>
  <body>
    <div class="container">
      <h1>Biostats Ticketing</h1>
      <?php
      $url = preg_replace('/process.php$/', '', 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
      $safe_title = preg_replace("/[^a-zA-Z0-9]/", "_", $_POST["title"]);
      $proj=(is_dir("/camp/stp/babs/working/{$_POST['bioinformatician']}/Projects"))?"Projects":"projects";
      $path = "/camp/stp/babs/working/{$_POST['bioinformatician']}/$proj/${_POST['lab']/"
	    . explode("@", $_POST["scientist"])[0]
	    . "/$safe_title";
      $hash = md5($path);
      $sci_name =  ucwords(str_replace(".", " ",strtolower(explode("@", $_POST["scientist"])[0])));
      date_default_timezone_set("Europe/London");
      $date = date("Y-m-d");
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
      $fname = "/camp/stp/babs/www/kellyg/tickets/test_{$_POST['bioinformatician']}_$hash.yml";
      file_put_contents($fname, $record);
      echo "<p>Your ticket has been submitted with ID $hash</p>";
      switch ($_POST["who"]) {
	  case "sci": 
	      echo "<p>{$_POST['lab']}@crick.ac.uk has been emailed to assign a cost-code and initial amount of time that BABS can spend on this project.
 You will receive an email when it has been approved  - please follow up with them if you don't receive this.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk",
		   "Approval required: {$_POST['project']}" ,
		   "{$_POST['scientist']} has requested we work on a project {$_POST['project']}. It is necessary for us to seek PI approval before we start work on this, so please visit the following page to allocate a cost-code and initial estimate of time you permit us to spend working on the project.\n\n $url/approval.php?project=$hash \n\n");
	      break;
	  case "pi": 
	      echo "<p>Thank you for completing this - an email has been sent to the scientist and the allocated member of BABS - you will receive a copy.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk",
		   "Approval received:  {$_POST['project']}",
		   "We have received PI approval for {$_POST['estimate']} hours to be spent using code {$_POST['code']} on {$_POST['project']}.\n");
	      break;
	  case "scipi":
	      echo "<p>Thank you for completing this - an email has been sent to you, your PI and the allocated member of BABS.</p>";
	      mail("{$_POST['bioinformatician']}@crick.ac.uk",
		   "BABS Project requested: {$_POST['project']}",
		   "Bioinformatics and Biostatistics STP have received a work request for {$_POST['estimate']} hours to be spent using code {$_POST['code']} on {$_POST['project']}. If this is incorrect or unapproved please inform us immediately. \n");
	      break;
      }
      ?>
      </p>
    </div>
  </body>
</html>

