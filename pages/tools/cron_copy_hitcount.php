<?php
include_once dirname(__FILE__) . "/../../include/db.php";
include_once dirname(__FILE__) . "/../../include/general.php";
include_once dirname(__FILE__) . "/../../include/reporting_functions.php";
include_once dirname(__FILE__) . "/../../include/resource_functions.php";
include_once dirname(__FILE__) . "/../../include/search_functions.php";
include_once dirname(__FILE__) . "/../../include/action_functions.php";
include_once dirname(__FILE__) . "/../../include/request_functions.php";

set_time_limit($cron_job_time_limit);

# All scheduled tasks are here for now, as older installations still call this file directly instead of batch/cron.php.

// Run any non-urgent tasks required by an upgrade

# Check that resource_nodes has been populated
if(!isset($sysvars['resource_node_migration_state']) || $sysvars['resource_node_migration_state'] != "COMPLETE")
    {
    echo "Populating resource_node and node_keyword tables\r\n";
    populate_resource_nodes(((isset($sysvars['resource_node_migration_state']))?$sysvars['resource_node_migration_state']:0));
    }

copy_hitcount_to_live();
if ($send_statistics) {send_statistics();}

# Send periodic reports also
send_periodic_report_emails();

# Update cron date
sql_query("delete from sysvars where name='last_cron'");
sql_query("insert into sysvars(name,value) values ('last_cron',now())");

?>
Relevance matching hitcount: copy done - <?php echo date("d M Y")?>

<?php include "geo_setcoords_from_country.php";

# Update disk quota column on resource table.
update_disk_usage_cron();

# Send daily digest of notifications
message_send_unread_emails();

if($offline_job_queue)
    {
    // May be required if there has not yet been a cron task set up for the offfline_jobs.php
    include dirname(__FILE__) . "/offline_jobs.php";
    }
    
#Perform any plugin cron tasks
hook("addplugincronjob");