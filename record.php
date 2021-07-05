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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>BABS Timesheet</title>
    <script src="https://d3js.org/d3.v6.min.js"></script>
    <script>
     var unsaved;
     var active_projects=<?php include 'get_active_projects.php'; ?>;
     active_projects = active_projects.flatMap(function(p) {
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
     babs_projects = <?php echo file_get_contents("yml/babs.js") ?>;
     babs_projects = babs_projects.map(proj => {
	 proj.Bioinformatician='<?php echo $_GET["id"]; ?>';
	 proj.Code=proj.Project;
	 proj.Lab="babs";
	 proj.Hash="babs_" + proj.Project;
	 proj.generic=true;
	 return(proj);});
     active_projects = active_projects.concat(babs_projects);
     active_projects = active_projects.filter(proj => proj.Active=="True");
     active_projects = active_projects.map(proj =>  {
	 proj.Hours=proj.default_time || 0;
	 proj.orig_hours=proj.default_time || 0;
	 proj.fixed=!!proj.default_time;
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
	  $staff= json_decode(file_get_contents("babs_staff.json"), true);
	  echo $staff[$_GET['id']]["first"];
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
    
    
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
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
