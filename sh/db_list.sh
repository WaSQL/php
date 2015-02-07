#!/bin/bash
#get the real path of the script
if [ -L $0 ] ; then
    ME=$(readlink $0)
else
    ME=$0
fi
DIR=$(dirname $ME)

#include the settings to bring in authentication variables
if [ -f $DIR/db_settings.sh ];
then
	source $DIR/db_settings.sh
else
	echo db_settings file is missing!
	exit 1
fi

MyUSER=$dbuser     # USERNAME
MyPASS=$dbpass       # PASSWORD
MyHOST=$dbhost          # Hostname

# Linux bin paths, change this if it can't be autodetected via which command
MYSQL="$(which mysql)"

# Backup Dest directory, change this if you have some other location
BackupDir="$DIR/backups"


# Get hostname
HOST="$(hostname)"


# check for databases to skip in the db_settings file
IGGY=$skipdbs

[ ! -d $BackupDir ] && mkdir -p $BackupDir || :

# Get all database list first
DBS="$($MYSQL -u $MyUSER -h $MyHOST -p$MyPASS -Bse 'show databases')"
 
for db in $DBS
do
    skipdb=-1
    if [ "$IGGY" != "" ];
    then
	for i in $IGGY
	do
	    [ "$db" == "$i" ] && skipdb=1 || :
	done
    fi
 
    if [ "$skipdb" == "-1" ] ; then
        echo -e "$db"
    fi
done
