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
#set some variables
MyUSER=$dbuser     # USERNAME
MyPASS=$dbpass     # PASSWORD
MyHOST=$dbhost     # Hostname
#get the OS name
OS=$(uname -s)
echo -e "OS=$OS and OSTYPE=$OSTYPE"
 
# Linux bin paths, change this if it can't be autodetected via which command
if [ $OS = "WindowsNT" ]; then
	echo -e "Running on  Windows"
    MYSQL="$(which mysql.exe)"
	MYSQLDUMP="$(which mysqldump.exe)"
	CHOWN="$(which chown.exe)"
	CHMOD="$(which chmod.exe)"
	GZIP="$(which gzip.exe)"
else
	MYSQL="$(which mysql)"
	MYSQLDUMP="$(which mysqldump)"
	CHOWN="$(which chown)"
	CHMOD="$(which chmod)"
	GZIP="$(which gzip)"
fi
echo -e "MYSQL=$MYSQL"

# Backup Dest directory, change this if you have some other location
BackupDir="$DIR/backups"


# Get hostname
HOST="$(hostname)"
 
# Get data in dd-mm-yyyy format
NOW="$(date +"%m-%d-%Y")"
 
# File to store current backup file
FILE=""
# Store list of databases
DBS=""

# check for databases to skip in the db_settings file
IGGY=$skipdbs

[ ! -d $BackupDir ] && mkdir -p $BackupDir || :

# Get all database list first
DBS="$("$MYSQL" -u $MyUSER -h $MyHOST -p$MyPASS -Bse 'show databases')"
DASH="__"
for db in $DBS
do
    skipdb=-1
    if [ "$IGGY" != "" ];
    then
	for i in $IGGY
	do
	    [ "$db" = "$i" ] && skipdb=1 || :
	done
    fi
 
    if [ "$skipdb" = "-1" ] ; then
	FILE="$BackupDir/$db$DASH$NOW.sql.gz"
	# do all inone job in pipe,
	# connect to mysql using mysqldump for select mysql database
	# and pipe it out to gz file in backup dir :)
        echo -e "mysqldump -u $MyUSER -h $MyHOST -p$MyPASS --max_allowed_packet=128M $db | gzip -9 > $FILE\n"
        mysqldump -u $MyUSER -h $MyHOST -p$MyPASS --max_allowed_packet=128M $db | gzip -9 > $FILE
    fi
done
#remove backups older than 90 days
find $BackupDir -ctime +90 | xargs rm -rf
