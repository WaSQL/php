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
#0 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#12 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#24 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#36 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#48 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#51 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
sleep 3
#54 second
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &
php cron_worker.php &