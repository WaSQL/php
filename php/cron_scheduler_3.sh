#!/bin/sh
#get the path of the script
if [ -L $0 ] ; then
    ME=$(readlink $0)
else
    ME=$0
fi
SDIR=$(dirname $ME)
#start the cron servers
cd $SDIR
#Launch three schedulers every 5 seconds
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &
sleep 5
php cron_scheduler.php once &
php cron_scheduler.php once &
php cron_scheduler.php once &