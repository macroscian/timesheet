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
    <link rel="stylesheet" href="resources/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>BABS Timesheet</title>
    <script src="resources/d3.v6.min.js"></script>

    <!-- Build the data from which the timesheet rows will be made -->

    <script>
     var unsaved;
     var active_projects=<?php include 'get_active_projects.php'; ?>; // convert ts's yaml (of all my projects) to json
     var getid='<?php echo $_GET["id"]; ?>';
     active_projects = active_projects.flatMap(function(p) {  // Handle multi-code projects. Expand them to duplicates
	 var codes = p.Code.split(",");
	 var ps; 
	 if (codes.length==1) {
	     ps=p;
	 } else {
	     ps = codes.map(function(x) {
		 var s = Object.assign({},p);
		 var keyval = x.split("=");
		 s.Code=(keyval.length==1)?x:keyval[1];
		 s.codekey = keyval[0];
		 return(s);});
	 }
	 return(ps);
     });
     babs_projects = <?php echo file_get_contents("yml/babs.js") ?>; // Generic projects
     babs_projects = babs_projects.map(proj => {
	 proj.Bioinformatician=getid;
	 proj.Code=proj.Project;
	 proj.Lab="babs";
	 proj.Hash="babs_" + proj.Project;
	 proj.Estimate="?";
	 proj.generic=true;
	 return(proj);});
     active_projects = active_projects.concat(babs_projects);
     active_projects = active_projects.filter(proj => proj.Active=="True");
     active_projects = active_projects.map(proj =>  { // Create more fields to enable timesheet entries
	 proj.Hours=proj.default_time || 0;
	 proj.orig_hours=proj.default_time || 0;
	 proj.fixed=!!proj.default_time;
	 proj.Estimate=isNaN(proj.Estimate)?"?":proj.Estimate;
	 proj.Type=proj.Type || "{{type}}";
	 proj.Type=(proj.Type=="{{type}}"?"Not Specified":proj.Type);
	 proj.activated=false;
	 proj.orig_activated=false;
	 proj.Note=proj.Note || "";
	 proj.orig_note=proj.Note || "";
	 return(proj);});
     const base_projects = JSON.parse(JSON.stringify(active_projects)); //clone
    </script>
  </head>
  <body>
    <div class="container">
      <!-- Header Row -->
      <div class="row h3 p-3 border" id="nav">
	<div class="col">
	  <button id="timeback" type="button" class="btn btn-primary btn-sm" onClick="movetime(false)">&laquo;</button>
	</div>
	<div class="col col-8" style="text-align:center;">
	  <?php
	  include('config.php');
	  $staff= json_decode(file_get_contents($config["babs_staff.json"]), true);
	  $my_id=explode( '_', $_GET['id'] )[0];
	  if (array_key_exists($my_id, $staff)) {
	      echo $staff[$my_id]["first"];
	  } else {
	      echo $_GET['id'];
	  }
	  ?>'s time for <span id="catchup"></span>
	</div>
	<div class="col" style="text-align:right;">
	  <button id="timeforward" type="button" class="btn btn-primary btn-sm" onClick="movetime(true)">&raquo;</button>
	</div>
      </div>
      <div class="row p-1">
	<div class="col">
	  <span id="totalhours"></span> out of <span id="catchuphours"></span> hours accounted for.
	</div>
      </div>
      <div class="row p-1">
	<div class="col">
	  <input type="checkbox" id="generic" autocomplete="off"> Show generic projects.
	</div>
      </div>

      <!-- Main body of timesheet -->
      <div  class="form-group row">
	<table class="table">
	  <thead >
	    <tr>
	      <th scope="col">Project</th>
	      <th scope="col">Scientist</th>
	      <th scope="col">Lab</th>
	      <th scope="col">Hours</th>
	      <th scope="col">Notes</th>
	    </tr>
	  </thead>
	  <tbody id="projects">
	  </tbody>
	</table>
      </div>
      
      <button type="button" class="btn btn-primary" onClick="submit()">Submit</button>
    </div>
    <!-- End  body of timesheet -->
    
    <script src="resources/core@2"></script>
    <script src="resources/tippy.js@6"></script>

    
    <!-- Populate timesheet via javascript -->
    <script src="timesheet.js"></script>
    <script>
     var tips=[];
     var hours_per_day = 36.0/5;
     var db_state={}; // global var containing response to database query
     var detail_week=""; // slot to remember which week we want to split into days
     get_timesheet({});
     d3.select("#generic").on("change", toggle_generic);
     window.onbeforeunload = unloadPage;
    </script>
  </body>
</html>
