# Can change these to reflect new locations (or for temporary changes,  `make app_name=app_test`)
www_base := /camp/stp/babs/www
app_name := timesheets
app_base := $(www_base)/internal/$(app_name)
app_store := $(www_base)/web_data/$(app_name)

# Files that will be published
SRC := config.php get_active_projects.php get_time.php report.php\
project_hours.php index.php submit_entries.php timesheet.js babs_staff.json

################################################################
#### Everything below should be kept as-is
################################################################
PUB := $(patsubst %,$(app_base)/%,$(SRC))

.PHONY: deploy

deploy: $(app_base)/resources $(PUB)
	mkdir -p $(app_base)/yml
	cp yml/babs.js $(app_base)/yml/
	sed -i 's#{{app_store}}#$(app_store)#' $(app_base)/config.php

$(PUB): $(app_base)/%: % | $(app_base)
	cp $< $@

$(app_base) $(app_store):
	mkdir -p $@

$(app_base)/resources: $(app_base)
	mkdir -p $(app_base)/resources/bootstrap/4.3.1/css/
	wget -P $(app_base)/resources/bootstrap/4.3.1/css/ https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css
	wget -P $(app_base)/resources https://d3js.org/d3.v6.min.js
	wget -P $(app_base)/resources https://unpkg.com/@popperjs/core@2
	wget -P $(app_base)/resources https://unpkg.com/tippy.js@6

$(app_store)/timesheets.db: | $(app_store)
	mkdir -p $(app_store)
	sqlite3 $@ "PRAGMA journal_mode = wal;" $(app_store)/timesheets.db
	sqlite3 $@ "CREATE TABLE entries ( Project TEXT NOT NULL, Bioinformatician TEXT NOT NULL, Scientist TEXT NOT NULL, Lab TEXT NOT NULL, Code TEXT NOT NULL, Hash  TEXT NOT NULL, Type TEXT NOT NULL, Hours REAL, Date TEXT NOT NULL, Note TEXT);" $(app_store)/timesheets.db






cron:
	@echo -e "0 1 * * * sqlite3 $(app_store)/timesheets.db \".backup '/camp/stp/babs/working/time/daily_backup.db'\" \n\
0 1 * * 0 sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/weekly_backup.db'\" \n\
0 1 1 * * sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/monthly_backup.db'\" " | crontab -

