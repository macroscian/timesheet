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
    <script src="https://d3js.org/d3.v5.min.js"></script>
    <script src="https://d3js.org/d3-array.v2.min.js"></script>
    <script src="https://d3js.org/d3-time-format.v3.min.js"></script>
    <script>
     var unsaved;
     var active_projects=<?php 
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
			 ?>;
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
     const base_projects = JSON.parse(JSON.stringify(active_projects)); 
    </script>
  </head>
  <body>
    <div class="container">
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
	<div class="col" style="text-align:right;">
	  Add project hashed: <input type "text" id="hash" onchange="handle_hash(this.value)"></p>
	</div>
      </div>
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
    <script>
      var hours_per_day = 36.0/5;
      var db_state={};
      var detail_week=""; // slot to remember which week we want to split into days
      get_timesheet({});
      d3.select("#generic").on("change", toggle_generic);
      function get_timesheet(range) {
      range.Bioinformatician = "<?php echo $_GET["id"]; ?>";
      d3.json('get_time.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json'},
      body: JSON.stringify(range)
      })
      .then(hours_so_far);
      }
      function hours_so_far(data) {
      var hashes={hashes:active_projects.map(x => x.Hash), id:"<?php echo $_GET["id"]; ?>"};
      d3.json('project_hours.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json'},
      body: JSON.stringify(hashes)
      }).then(d => update_view(data, d));
      }
      function update_view(data, hours) {
      db_state = data;
      detail_week = (db_state.hasOwnProperty('day'))?d3.timeFormat('%GW%V')(new Date(db_state.start)):detail_week;
      let db_entries = Array.from(d3.rollup(data.entries, aggregate_projs, d => d.Hash), ([key, value]) => value);
      delete db_state.entries;
      d3.select("#catchup").html(parse_time_label(db_state));
      d3.select("#catchuphours").text(db_state.hours_needed);
      active_projects = JSON.parse(JSON.stringify(base_projects)); 
      db_entries.forEach(proj => {
      let ind = active_projects.findIndex(p => p.Hash==proj.Hash);
      if (ind != -1) {
      let my_hours = proj.Hours;
      let my_note = proj.Note;
      Object.assign(proj, active_projects[ind]);
      proj.Hours = my_hours;
      proj.Note = my_note;
      active_projects.splice(ind, 1);
      }
      proj.activated=true;
      proj.orig_activated = proj.activated;
      proj.orig_hours = proj.Hours;
      proj.orig_note = proj.Note;
      proj.fixed=true;
      proj.default_time=proj.Hours;
      });
      active_projects=db_entries.concat(active_projects);
      active_projects.forEach(proj => {
      let ind = hours.findIndex(h => h.Hash==proj.Hash);
      proj.spent = (ind == -1)?0:(hours[ind].hours);
      });
      update_rows(active_projects);
      toggle_generic();
      }
      function update_rows(data) {
      var rows = d3.select("#projects").selectAll("tr").data(active_projects, d => d.Hash);
      rows.enter().append("tr")
      .html(generate_row_from_data);
      rows.exit().remove();
      rows.classed("text-muted", d => !d.activated);
      d3.selectAll(".hour_select").on("change", handle_select);
      d3.selectAll(".hour_input").on("change", handle_input);
      d3.selectAll(".hour_increment").on("click", handle_increment);
      d3.selectAll(".hour_note").on("change", handle_note);
      recalc();
      d3.select("#nav").classed("bg-warning", false);
      }
      function toggle_generic() {
      d3.select("#projects").selectAll("tr")
      .style("display", d => (d3.select(this).property("checked") || d.activated || ! d.generic)?null:"none");
      }
      function handle_hash(hash) {
      d3.json('get_time.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json'},
      body: JSON.stringify({Hash: hash})
      })
      .then(function(hash_row) {
      if (hash_row) {
      hash_row.Hours = 0;
	  hash_row.Note = "";
	  hash_row.spent=0;
      active_projects.push(hash_row);
      update_rows(active_projects);
      }
      });
      }
      function submit() {
      db_state.entries = d3.select("#projects").selectAll("tr").data()
      .filter(d => d.activated)
      .map(proj =>  {
      proj.Date=db_state.recorddate;
      return(proj);});
      fetch('submit_entries.php', {
      method: 'POST', // or 'PUT'
      headers: {
      'Content-Type': 'application/json',
      },
      body: JSON.stringify(db_state)
      });
      get_timesheet({});
      }
      function aggregate_projs(v) {
      res = Object.assign({}, v[0]);
      res.Hours = d3.sum(v, d=> d.Hours);
      res.orig_hours = res.Hours;
      return(res);
      }

      function parse_time_label(php_response) {
      if (php_response.hasOwnProperty('day')) {
      let d = d3.timeParse("%Y-%m-%d")(php_response.day);
      db_state.hours_needed=hours_per_day;
      return(d3.timeFormat("%a %e %b %Y")(d));
      }
      if (php_response.hasOwnProperty('week')) {
      let d = d3.timeParse("%GW%V")(php_response.week);
      db_state.hours_needed=hours_per_day * 5;
      return(d3.timeFormat("week beginning %a %e %b %Y")(d));
      }
      if (php_response.hasOwnProperty('month')) {
      let d = d3.timeParse("%GW%V")(php_response.month);
      db_state.hours_needed=hours_per_day * 0;
      return(d3.timeFormat("%B %Y")(d));
      }
      
      }
      function movetime(forward) {
      var cur = new Date(db_state.start);
      var nxt;
      db_state.move = (forward)?"forward":"reverse";
      if (!unsaved || confirm("You're navigating away from unsaved changes")) {
      get_timesheet(db_state);
      }
      }
      function generate_row_from_data(d,i) {
      var html = '<td><input type="checkbox" class="form-check-input hour hour_select" autocomplete="off" ' + (d.activated?"checked":"") + '>' + 
      '<span class="hour_increment">' + d.Project + (d.codekey?(" ("+d.codekey+")"):"") + '</span>' +
      "</td><td>" + d.Scientist +
      "</td><td>" + d.Lab +
      '</td><td><input size="5" type="number" min="0" max="168" step="' + (0.1) +'" class="hour hour_input" value="' + d.Hours.toFixed(1) + '"' + (d.activated?"":"disabled") +  '></input> ' +  d.spent.toFixed(1) +
      '<span class="fixed" style="visibility:' + ((d.fixed & d.activated)?"visible":"hidden") + '">&#128274</span>' + 
      '</td><td><input class="hour_note" type="textarea" ' + (d.activated?"":"disabled") + 'value="' +d.Note + '"></td>';
      return(html);
      }
      function handle_select(dat, ind) {
      dat.activated = d3.select(this).property("checked");
      dat.fixed = !!dat.default_time;
      dat.Hours = dat.default_time || 0;
      recalc();
      }
      function handle_input(dat, ind) {
      dat.fixed = true;
      dat.Hours = +this.value;
      recalc();
      }
      function handle_increment(dat, ind) {
      dati=d3.select("#projects").selectAll("tr").data()[ind]
      dati.Hours = dati.Hours + (dati.default_time || 1) * (d3.event.shiftKey?-1:1);
      dati.fixed = true;
      recalc();
      }
      function handle_note(dat, ind) {
      dat.Note = this.value;
      recalc()
      }
      function recalc() {
      var rows = d3.select("#projects").selectAll("tr");
      var used = rows.data().filter(proj => proj.activated & proj.fixed ).reduce((h,proj)  => h+proj.Hours, 0);
      var n_freefloat = rows.data().filter(proj => proj.activated & !proj.fixed).length;
      var prop = Math.max(0, (n_freefloat > 0)?(db_state.hours_needed - used)/n_freefloat:0);
      rows.each(proj => proj.Hours = proj.activated?(proj.fixed?proj.Hours:prop):(proj.default_time || 0));
      rows.classed("text-muted", d => !d.activated);
      rows.select("td input.hour_select")
      .property('checked', d => d.activated);
      rows.select("td input.hour_input")
      .property("value", d => d.Hours.toFixed(1))
      .attr("disabled", d => d.activated?null:"true");
      rows.select("td input.hour_note")
      .property("value", d => d.Note)
      .attr("disabled", d => d.activated?null:"true");
      rows.select("td span.fixed").style("visibility", d => (d.fixed & d.activated)?"visible":"hidden");
      let total_hours = rows.data().reduce((tot,cur) => tot + (cur.activated?cur.Hours:0),0)
      d3.select("#totalhours").text(total_hours.toFixed(1));
      d3.selectAll("button")
      .classed('btn-success', Math.abs(db_state.hours_needed - total_hours) <= 0.01)
      .classed('btn-danger', db_state.hours_needed - total_hours > .01)
      .classed('btn-warning', db_state.hours_needed - total_hours < -.01);
      rows.classed("bg-warning", proj => proj.Hours.toFixed(1)!=proj.orig_hours.toFixed(1) || proj.Note!=proj.orig_note || proj.activated != proj.orig_activated); 
      unsaved = d3.selectAll("tr.bg-warning").size()!=0;
      }
      function unloadPage(){
      if(unsaved){
      return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
      }
      }
      
      window.onbeforeunload = unloadPage;
    </script>
  </body>
</html>
