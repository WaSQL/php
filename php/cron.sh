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

php cron.php &
sleep 6
php cron.php &
sleep 7
php cron.php &
sleep 6
php cron.php &
sleep 7
php cron.php &
sleep 6
php cron.php &
sleep 7
php cron.php &
sleep 6
php cron.php &





