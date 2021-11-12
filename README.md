### Timesheets

Timesheets rely on a `.babs` file in your
`working/me/projects/lab/scientist/proj` folder which contains the
metadata about your project (primarily a cost-code, project title, lab
and scientist). Functionality to interact with the ticketing system
(to get that metadata from a scientist-provided ticket) or the
file-system (to get metadata from folder-location) is provided by the
makefile above. Most of the other files in the repo are to implement
the interface where a bioinformatician records their time on the
webpage.

[index.php](record.php) is the main front-page, which reads
bioinformaticians' active projects from the `yml` folder (which are
auto-generated by the makefile script whenever a bioinformatician
amends their list of projects). It uses the _d3_ and _knockout_
frameworks to fill out a table where rows correspond to a
bioinformatician's active projects (it uses the _id_ property from the
URL to determine who). Any existing hours are pulled from the database
using the [get_time.php](get_time.php)
script. [project_hours.php](project_hours.php) is used to retrieve the
number of hours already stored in the database, and
[submit_entries.php](submit_entries.php) is responsible for adding the
time entries into the database once the user presses 'submit'.


