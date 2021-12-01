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
	    if (!urlget.hasOwnProperty("Bioinformatician")) {
		var latest_rows = d3.select('#latest').selectAll('tr').data(dbase.latest).join(
		    enter => enter.append('tr'),
		    exit => exit.remove()
		).html(d => "<td>" + d.Bioinformatician + "</td><td>" + d.Date + "</td>")
		return;
	    }
	    staffXweek  = d3.rollup(
		dbase.entries,
		v =>({total:d3.sum(v, d => d.Hours) ,
		     babs:d3.sum(v.filter(d => d.Lab=="babs"), d => d.Hours),
		     Bioinformatician: urlget.hasOwnProperty("Bioinformatician")?"Me":"All",
		     week: v[0].week
		    }),
		d => urlget.hasOwnProperty("Bioinformatician")?"Me":"All",
		d => d.week
	    );
	    var staff_scale = d3.scaleBand([...staffXweek.keys()].sort(),
					   [50,150]);
	    var week_scale = d3.scaleBand([...new Set([...staffXweek.values()].map(d => [...d.keys()]).flat())].sort(),
					  [200,750]);
	    var grid = d3.select("#grid")
		.insert("svg")
		.attr("width","800px")
		.attr("height","200px").lower();
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
function show_name() {
    var x = document.getElementById("which_ID");
    if (x.type === "password") {
	x.type = "text";
    } else {
	x.type = "password";
    }
} 
