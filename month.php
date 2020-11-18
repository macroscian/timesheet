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
    <script src="https://d3js.org/d3-scale.v3.min.js"></script>
    <script src="https://d3js.org/d3-axis.v2.min.js"></script>
    <script>
     var urlget = <?php echo json_encode($_GET);  ?>;
    </script>
  </head>
  <body>
    <a id="download" href=".">Download Report</a>
    <div id="grid">
    </div>
    <script>
     var hours_per_day = 36.0/5;
     var db_entries;
     get_timesheet(urlget);
     function get_timesheet(range) {
	 d3.json('get_time.php', {
	     method: 'POST',
	     headers: { 'Content-Type': 'application/json'},
	     body: JSON.stringify(range)
	 })
	   .then(function(data) {
	       var report = d3.nest().key(d => d.Code).key(d => d.Hash).rollup(v => ({
		   Hours: d3.sum(v, d=>d.Hours).toFixed(2),
		   "Free Hour": d3.max(v, d=>d.isNew),
		   Project: d3.set(v.map(d => d.Project)).values().join(","),
		   Scientist: d3.set(v.map(d => d.Scientist)).values().join(","),
		   Lab: d3.set(v.map(d => d.Lab)).values().join(",")
	       })).entries(data.entries);
	       report = report.map(c => c.values.map(p => Object.assign({Code:c.key, Project:p.key}, p.value))).flat();
	       var tsv = d3.tsvFormat(report);
	       d3.select('#download')
		 .attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(tsv))
		 .attr('download', range.month);
	       data.entries.forEach(proj => {
		   let dat = d3.timeParse("%Y-%m-%d")(proj.Date);
		   proj.week = d3.timeFormat("%GW%V")(dat);
	       });
	       db_entries = d3.nest()
			      .key(d=>d.Bioinformatician)
			      .key(d=>d.week)
			      .rollup(v => ({total:d3.sum(v, d => d.Hours) ,
					    babs:d3.sum(v.filter(d => d.Lab=="babs"), d => d.Hours)  })
			      )
			      .entries(data.entries);
	       var staff_scale = d3.scaleBand(db_entries.map(d => d.key), [50,750]);
	       var week_scale = d3.scaleBand(d3.set(db_entries.map(r => r.values.map(c => c.key)).flat()).values().sort(), [200,750]);
	       var grid = d3.select("#grid")
			    .append("svg")
			    .attr("width","800px")
			    .attr("height","800px");
	       var row = grid.selectAll(".row")
			     .data(db_entries)
			     .enter().append("g")
			     .attr("class", "row");
	       grid.append("g")
		   .attr("transform", "translate(0,40)")
		   .call(d3.axisTop()
			   .scale(week_scale));
	       
	       grid.append("g")
		   .attr("transform", "translate(190,0)")
		   .call(d3.axisLeft()
			   .scale(staff_scale));

	       var column = row.selectAll(".square")
			       .data(d => d.values.map(e => Object.assign(e, {staff:d.key})));
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d.key))
		   .attr("y", d => staff_scale(d.staff))
		   .attr("width", week_scale.step())
		   .attr("height", staff_scale.step())
		   .style("fill", "red")
		   .style("stroke", "#222");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d.key))
		   .attr("y", d => staff_scale(d.staff))
		   .attr("width", d => d.value.total * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "white")
		   .style("stroke", "white");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d.key))
		   .attr("y", d => staff_scale(d.staff))
		   .attr("width", d => (d.value.total-d.value.babs) * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "green")
		   .style("stroke", "green");
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d.key))
		   .attr("y", d => staff_scale(d.staff))
		   .attr("width", week_scale.step())
		   .attr("height", staff_scale.step())
		   .style("fill", "transparent")
		   .style("stroke-width", "2px")
		   .style("stroke", "black");
	       
	   })
     }
    </script>
  </body>
</html>
