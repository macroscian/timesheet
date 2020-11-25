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
	       var report = d3.rollup(
		   data.entries,
		   v => ({
		       Hours: d3.sum(v, d=>d.Hours).toFixed(2),
		       "Free Hour": d3.max(v, d=>d.isNew),
		       Project: [...new Set(v.map(d => d.Project))].join(","),
		       Scientist: [...new Set(v.map(d => d.Scientist))].join(","),
		       Lab: [...new Set(v.map(d => d.Lab))].join(","),
		       Code: v[0].Code
		   }),
		   d => d.Code + "_" + d.Hash 
	       );
	       report = Array.from(report, p => p[1]);
	       var tsv = d3.tsvFormat(report);
	       d3.select('#download')
		 .attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(tsv))
		 .attr('download', range.month);
	       data.entries.forEach(proj => {
		   let dat = d3.timeParse("%Y-%m-%d")(proj.Date);
		   proj.week = d3.timeFormat("%GW%V")(dat);
	       });
	       db_entries  = d3.rollups(
		   data.entries,
		   v =>({total:d3.sum(v, d => d.Hours) ,
			babs:d3.sum(v.filter(d => d.Lab=="babs"), d => d.Hours),
			Bioinformatician: v[0].Bioinformatician,
			week: v[0].week
		   }),
		   d => d.Bioinformatician,
		   d => d.week
	       );
	       var staff_scale = d3.scaleBand(db_entries.map(d => d[0]).sort(),
					      [50,750]);
	       var week_scale = d3.scaleBand([...new Set(db_entries.map(d => d[1].map( e => e[0])).flat())].sort(),
					     [200,750]);
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
			       .data(d => d[1]);
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d[1].week))
		   .attr("y", d => staff_scale(d[1].Bioinformatician))
		   .attr("width", week_scale.step())
		   .attr("height", staff_scale.step())
		   .style("fill", "red")
		   .style("stroke", "#222");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d[1].week))
		   .attr("y", d => staff_scale(d[1].Bioinformatician))
		   .attr("width", d => d[1].total * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "white")
		   .style("stroke", "white");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d[1].week))
		   .attr("y", d => staff_scale(d[1].Bioinformatician))
		   .attr("width", d => (d[1].total-d[1].babs) * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "green")
		   .style("stroke", "green");
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d[1].week))
		   .attr("y", d => staff_scale(d[1].Bioinformatician))
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
