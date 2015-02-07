#!/bin/sh
#cron_start.sh {start=10} {delay=2}
#get the path of the script
if [ -L $0 ] ; then
    ME=$(readlink $0)
else
    ME=$0
fi
SDIR=$(dirname $ME)
START=$1
DELAY=$2
: ${START:=10}
: ${DELAY:=2}
#start the cron servers
cd $SDIR
i=0
while [ $i -lt $START ]
do
	echo "starting cron number $i"
   ./cron.pl &
	sleep $DELAY
	i=`expr $i + 1`
done