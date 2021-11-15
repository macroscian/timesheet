app_base := /camp/stp/babs/www
app_name := timesheets
timesheet_base := $(app_base)/internal/$(app_name)
timesheet_store := $(app_base)/web_data/$(app_name)

.PHONY: deploy

deploy: $(timesheet_base)/config.php $(timesheet_base)/resources
	cp {get_active_projects,get_time,month,project_hours,index,submit_entries}.php $(timesheet_base)/
	cp {timesheet.js,babs_staff.json} $(timesheet_base)/
	mkdir -p $(timesheet_base)/yml
	cp yml/babs.js $(timesheet_base)/yml/

$(timesheet_base)/config.php: config.php
	mkdir -p $(timesheet_base)
	sed 's#{{timesheet_store}}#$(timesheet_store)#' $< > $@



$(timesheet_base)/resources:
	mkdir -p $(timesheet_base)/resources/bootstrap/4.3.1/css/
	wget -P $(timesheet_base)/resources/bootstrap/4.3.1/css/ https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css
	wget -P $(timesheet_base)/resources https://d3js.org/d3.v6.min.js
	wget -P $(timesheet_base)/resources https://unpkg.com/@popperjs/core@2
	wget -P $(timesheet_base)/resources https://unpkg.com/tippy.js@6

$(timesheet_store)/timesheets.db:
	mkdir -p $(timesheet_store)
	sqlite3 $@ "PRAGMA journal_mode = wal;" $(timesheet_store)/timesheets.db
	sqlite3 $@ "CREATE TABLE entries ( Project TEXT NOT NULL, Bioinformatician TEXT NOT NULL, Scientist TEXT NOT NULL, Lab TEXT NOT NULL, Code TEXT NOT NULL, Hash  TEXT NOT NULL, Type TEXT NOT NULL, Hours REAL, Date TEXT NOT NULL, Note TEXT);" $(timesheet_store)/timesheets.db






cron:
	@echo -e "0 1 * * * sqlite3 $(timesheet_store)/timesheets.db \".backup '/camp/stp/babs/working/time/daily_backup.db'\" \n\
0 1 * * 0 sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/weekly_backup.db'\" \n\
0 1 1 * * sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/monthly_backup.db'\" " | crontab -

