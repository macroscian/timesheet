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
    <title>BABS Time Report</title>
    <script src="resources/d3.v6.min.js"></script>
    <script>
     var urlget = <?php echo json_encode($_GET);  ?>;
     if (urlget.length==0) {
	 urlget={'month': "<?php echo date('Y-m'); ?>"};
     }
    </script>
  </head>
  <body>
    <label for="which_month">Choose month:</label>
    <input type="month" id="which_month" name="start" value="<?php echo date('Y-m'); ?>"  onchange="change_month(this)">
    <label for="which_id">Your ID:</label>
    <input type="password" id="which_ID" name="start" value=""   onchange="change_ID(this)">
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
	   .then(function(dbase) {
	       var report = d3.rollup(
		   dbase.entries,
		   v => ({
		       Hours: d3.sum(v, d=>d.Hours).toFixed(2),
		       Project: [...new Set(v.map(d => d.Project))].join(","),
		       Scientist: [...new Set(v.map(d => d.Scientist))].join(","),
		       Lab: [...new Set(v.map(d => d.Lab))].join(","),
		       Code: v[0].Code
		   }),
		   d => d.Code + "_" + d.Hash 
	       );
	       report = [...report.values()];
	       var tsv = d3.tsvFormat(report);
	       d3.select('#download')
		 .attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(tsv))
		 .attr('download', range.month);
	       dbase.entries.forEach(proj => {
		   let dat = d3.timeParse("%Y-%m-%d")(proj.Date);
		   proj.week = d3.timeFormat("%GW%V")(dat);
	       });
	       staffXweek  = d3.rollup(
		   dbase.entries,
		   v =>({total:d3.sum(v, d => d.Hours) ,
			 babs:d3.sum(v.filter(d => d.Lab=="babs"), d => d.Hours),
			 Bioinformatician: "Me",
			 week: v[0].week
		   }),
		   d => "Me",
		   d => d.week
	       );
	       var staff_scale = d3.scaleBand([...staffXweek.keys()].sort(),
					      [50,750]);
	       var week_scale = d3.scaleBand([...new Set([...staffXweek.values()].map(d => [...d.keys()]).flat())].sort(),
					     [200,750]);
	       var grid = d3.select("#grid")
			    .append("svg")
			    .attr("width","800px")
			    .attr("height","800px");
	       var row = grid.selectAll(".row")
			     .data([...staffXweek.values()].map(d => [...d.values()]))
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
			       .data(d => d);
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d.week))
		   .attr("y", d => staff_scale(d.Bioinformatician))
		   .attr("width", week_scale.step())
		   .attr("height", staff_scale.step())
		   .style("fill", "red")
		   .style("stroke", "#222");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d.week))
		   .attr("y", d => staff_scale(d.Bioinformatician))
		   .attr("width", d => d.total * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "white")
		   .style("stroke", "white");
	       column
		   .enter().append("rect")
		   .attr("class","project")
		   .attr("x", d => week_scale(d.week))
		   .attr("y", d => staff_scale(d.Bioinformatician))
		   .attr("width", d => (d.total-d.babs) * week_scale.step()/36)
		   .attr("height", staff_scale.step())
		   .style("fill", "green")
		   .style("stroke", "green");
	       column
		   .enter().append("rect")
		   .attr("class","square")
		   .attr("x", d => week_scale(d.week))
		   .attr("y", d => staff_scale(d.Bioinformatician))
		   .attr("width", week_scale.step())
		   .attr("height", staff_scale.step())
		   .style("fill", "transparent")
		   .style("stroke-width", "2px")
		   .style("stroke", "black");
	       
	   })
     }
     function change_month(x) {
	 d3.select('#download').attr('href', "#");
	 urlget.month=x.value;
	 get_timesheet(urlget);
     }
     function change_ID(x) {
	 d3.select('#download').attr('href', "#");
	 if (x.value=="") {
	     delete urlget.Bioinformatician;
	 } else {
	     urlget.Bioinformatician=x.value;
	 }
	 get_timesheet(urlget);
     }
    </script>
  </body>
</html>
