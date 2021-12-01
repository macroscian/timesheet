<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="resources/bootstrap/4.3.1/css/bootstrap.min.css">
    <title>BABS Time Report</title>
    <script src="resources/d3.v6.min.js"></script>
    <script>
     var urlget = <?php echo json_encode($_GET);  ?>;
     if (urlget.length==0) {
	 urlget={'month': "<?php echo date('Y-m'); ?>"};
     }
    </script>
    <script src="report.js"></script>
  </head>
  <body>
    <label for="which_month">Choose month:</label>
    <input type="month" id="which_month" name="start" value="<?php echo date('Y-m'); ?>"  onchange="change_month(this)">
    <label for="which_id">Your ID:</label>
    <input type="password" id="which_ID" name="start" value=""   onchange="change_ID(this)">
    <input type="checkbox" onclick="show_name()">Show Name
    <a id="download" href=".">Download Report</a>
    <div>
      <table>
	<thead>
	  <tr><th>Username</th><th>Date of last entry</th></tr>
	</thead>
	<tbody id="latest">
	</tbody>
      </table>
    </div>
    <div id="grid">
    </div>
    <script>
    </script>
  </body>
</html>
