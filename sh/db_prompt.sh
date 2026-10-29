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
	#pass the password via the environment so it never appears on the command line, in ps output, or in echoed commands
	export MYSQL_PWD="$dbpass"
	dbname=$1
	mysql -h $dbhost --user=$dbuser $dbname
else
	echo db_settings file is missing!
	exit 1
fi



