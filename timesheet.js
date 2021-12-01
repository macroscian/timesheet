function get_timesheet(range) {
    // Main entry point
    // `range` will tell the database-retrieval what to get
    // if it has keys 'day', 'week' or 'month' it will return the relevant entries
    //
    range.Bioinformatician = getid;
    d3.json('get_time.php', {
	method: 'POST',
	headers: { 'Content-Type': 'application/json'},
	body: JSON.stringify(range)
    })
	.then(update_projects);
}

function update_projects(data) { // process database entries of recorded time
    db_state = data;
    detail_week = (db_state.hasOwnProperty('day'))?d3.timeFormat('%GW%V')(new Date(db_state.start)):detail_week;
    // aggregate hours per project as defined by its unique hash
    let db_entries = [...d3.rollup(data.entries, aggregate_projs, d => d.Hash).values()];
    delete db_state.entries;
    d3.select("#catchup").html(parse_time_label(db_state));
    d3.select("#catchuphours").text(db_state.hours_needed);
    active_projects = JSON.parse(JSON.stringify(base_projects)); // clone 
    db_entries.forEach(proj => { // mutate in place
	let ind = active_projects.findIndex(p => p.Hash==proj.Hash);
	if (ind != -1) { //if previous timerecording is a default project
	    let my_hours = proj.Hours; // don't overwrite these fields
	    let my_note = proj.Note;
	    Object.assign(proj, active_projects[ind]); // pull in info from the default
	    proj.Hours = my_hours;
	    proj.Note = my_note;
	    active_projects.splice(ind, 1); // and remove the default entry
	} else {
	    proj.Estimate="X";
	}
	proj.activated=true;
	proj.orig_activated = proj.activated;
	proj.orig_hours = proj.Hours;
	proj.orig_note = proj.Note;
	proj.fixed=true;
	proj.default_time=proj.Hours;
    });
    active_projects=db_entries.concat(active_projects);
    // Get each project's history of hours spent
    var hashes={hashes:active_projects.map(x => x.Hash), id:getid};
    d3.json('project_hours.php', {
	method: 'POST',
	headers: { 'Content-Type': 'application/json'},
	body: JSON.stringify(hashes)
    }).then(update_view)
}

function update_view(hours) {
    // Add historic hours into the active_projects object
    active_projects.forEach(proj => {
	let hr = hours.filter(h => h.Hash==proj.Hash);
	if (hr.length == 0) {
	    proj.spent = 0;
	    proj.tooltip = "---";
	} else {
	    let tbl = Object.fromEntries(hr.map(d => [d.range, d.hours.toFixed(1)]));
	    tbl = Object.assign({"This week":0, "Last week":0, "This month":0, "Last month":0, "This year":0, "Last year":0}, tbl);
	    proj.spent = tbl.All;
	    proj.tooltip = `Week=${tbl["This week"]} (${tbl["Last week"]})<br>Month=${tbl["This month"]} (${tbl["Last month"]})<br>Year=${tbl["This year"]} (${tbl["Last year"]})`;
	}
    });
    update_rows();
    toggle_generic();
}

function update_rows() {
    // Sync rows in table with the entries in the active_projects object
    // using d3 enter/update/delete paradigm
    var rows = d3.select("#projects").selectAll("tr").data(active_projects, d => d.Hash);
    rows.enter().append("tr")
	.html(generate_row_from_data)
	.each(function(d,i) {
	    tips[d.Hash] = tippy(d3.select(this).select(".spent").node(), {content: d.tooltip,allowHTML: true});
	});
    rows.each(function(d,i) {tips[d.Hash].setContent(d.tooltip);});
    rows.exit().remove(); // TODO - will leave orphaned elements in 'tips'.  Not serious as they may get reused anyway.
    rows.classed("text-muted", d => !d.activated);
    d3.selectAll(".hour_select").on("change", handle_select);
    d3.selectAll(".hour_input").on("change", handle_input);
    //	 d3.selectAll(".hour_increment").on("click", handle_increment); TODO as v6 of d3 doesn't pass ind
    d3.selectAll(".hour_note").on("change", handle_note);
    recalc();
    d3.select("#nav").classed("bg-warning", false);
}

function toggle_generic() {
    d3.select("#projects").selectAll("tr")
	.style("display", d => (d3.select(this).property("checked") || d.activated || ! d.generic)?null:"none");
}



function submit() {
    // Upload entered data into the database
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
	'</td><td><input size="5" type="number" min="0" max="168" step="' + (0.1) +'" class="hour hour_input" value="' + d.Hours.toFixed(1) + '"' + (d.activated?"":"disabled") +  '></input> ' +
	'<span class="spent" >' + d.spent + ' (' + d.Estimate +  ')</span>' + 
	'<span class="fixed" style="visibility:' + ((d.fixed & d.activated)?"visible":"hidden") + '">&#128274</span>' + 
	'</td><td><input class="hour_note" type="textarea" " ' + (d.activated?"":"disabled") + 'value="' +d.Note +
	'"></td><td><span class="clip_toggle"  onclick=clip_toggle("' + d.Path + '") title="Click to copy toggle command">📋</span></td>';
    return(html);
}

function handle_select(ev,dat) {
    dat.activated = d3.select(this).property("checked");
    dat.fixed = !!dat.default_time;
    dat.Hours = dat.default_time || 0;
    recalc();
}

function handle_input(ev,dat) {
    dat.fixed = true;
    dat.Hours = +this.value;
    recalc();
}

function handle_increment(ev, dat) {
    const ind = ev.indexOf(this);
    dati=d3.select("#projects").selectAll("tr").data()[ind];
    dati.Hours = dati.Hours + (dati.default_time || 1) * (d3.event.shiftKey?-1:1);
    dati.fixed = true;
    recalc();
}

function handle_note(ev, dat) {
    dat.Note = this.value;
    recalc()
}

function clip_toggle(path) {
    navigator.clipboard.writeText("(cd " + path.replace(/.babs$/, "")  + "; ts toggle )");
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
    rows.select("td span.spent")
	.text(d => d.spent + ' (' + d.Estimate + ')');
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


