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
	db_name=$1
	mysql -h $dbhost --user=$dbuser -p$dbpass --execute="DROP DATABASE $db_name; CREATE DATABASE $db_name CHARACTER SET utf8 COLLATE utf8_general_ci;"
else
	echo db_settings file is missing!
	exit 1
fi


