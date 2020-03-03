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
	dbname=$1
	sql=$2
	mysql -h $dbhost --user=$dbuser -p$dbpass --execute="DROP DATABASE IF EXISTS $dbname;CREATE DATABASE $dbname CHARACTER SET utf8 COLLATE utf8_general_ci;"
	mysql -h $dbhost --user=$dbuser -p$dbpass --max_allowed_packet=128M --default-character-set=utf8 $dbname < $sql
else
	echo db_settings file is missing!
	exit 1
fi



