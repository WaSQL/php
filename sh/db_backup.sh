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
MyPASS=$dbpass     # PASSWORD
MyHOST=$dbhost     # Hostname
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

db=$1
# Get data in dd-mm-yyyy format
NOW="$(date +"%m-%d-%Y")"
DASH="__"
FILE="$BackupDir/$db$DASH$NOW.sql.gz"
echo -e "$MYSQLDUMP --user=$MyUSER --host=$MyHOST --password=$MyPASS --max_allowed_packet=128M $db > $FILE\n"
"$MYSQLDUMP" --user=$MyUSER --host=$MyHOST --password=$MyPASS --max_allowed_packet=128M $db | gzip -9 > $FILE

