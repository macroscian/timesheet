LIVE := "/camp/stp/babs/www/kellyg/public_html/LIVE/tickets"
DEV := "/camp/stp/babs/www/kellyg/public_html/LIVE/tickets_test"
DIR := ${CURDIR}
TSDIR := "/camp/stp/babs/working/time"

deploy:
	BRANCH=`git rev-parse --abbrev-ref HEAD` ;\
	if [ "$${BRANCH}" = "live" ];\
	then \
	git --work-tree=$(LIVE) --git-dir=.git checkout -f $(BRANCH) ;\
	cd $(LIVE) ;\
	mv make_ts $(TS)/makefile ;\
	else \
	GIT_DIR=/camp/stp/babs/working/kellyg/projects/babs/admin/tickets/.git ;\
	git --work-tree=$(DEV) checkout -f $(BRANCH) ;\
	cd $(DEV) ;\
	rm make_ts ;\
	fi ;\
	make groups.json ;\
	rm makefile

tickets.db:
	sqlite tickets.db "CREATE TABLE tickets (\
scientist TEXT NOT NULL,\
lab TEXT NOT NULL,\
hash TEXT NOT NULL,\
title TEXT NOT NULL,\
code INTEGER,\
estimate INTEGER,\
bioinformatician TEXT NOT NULL,\
date TEXT NOT NULL\
);"

timesheets.db:
	sqlite3 timesheets.db "PRAGMA journal_mode = wal;"
	sqlite3 timesheets.db "CREATE TABLE entries ( Project TEXT NOT NULL, Bioinformatician TEXT NOT NULL, Scientist TEXT NOT NULL, Lab TEXT NOT NULL, Code TEXT NOT NULL, Hash  TEXT NOT NULL, Type TEXT NOT NULL, Hours REAL, Date TEXT NOT NULL, Note TEXT);"



cron:
	@echo -e "0 1 * * * sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/daily_backup.db'\" \n\
0 1 * * 0 sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/weekly_backup.db'\" \n\
0 1 1 * * sqlite3 /camp/stp/babs/www/kellyg/public_html/LIVE/tickets/timesheets.db \".backup '/camp/stp/babs/working/time/monthly_backup.db'\" " | crontab -


groups.json:
	echo "{\"stps\":[" > $@
	ls /camp/stp/ | sed 's/\(.*\)/"\1"/g' | tr '\n' ,| head -c -1 >> groups.json
	echo "],\"labs\":[" >> $@
	ls /camp/lab/ | sed 's/\(.*\)/"\1"/g' | tr '\n' ,| head -c -1>> groups.json
	echo "],\"ops\":[" >> $@
	echo '"Funding","Facilities","Legal","Finance","Comms","Library","IT","Infrastructure","Public Engagement","Philanthropy","HR","Sourcing","Operations"' >> $@
	echo "]}">> $@


