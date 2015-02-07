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
# check for databases to skip in the db_settings file
IGGY=$skipdbs
# Linux bin paths, change this if it can't be autodetected via which command
MYSQL="$(which mysql)"


mysql -u $MyUSER -h $MyHOST -p$MyPASS -NBe "SHOW DATABASES;" | grep -v 'lost+found' | while read database ; do
	#skip databases found in skipdbs
	skipdb=-1
    if [ "$IGGY" != "" ]; then
		for i in $IGGY; do
	    	[ "$database" == "$i" ] && skipdb=1 || :
		done
    fi
    if [ "$skipdb" != "-1" ] ; then
    	continue
    fi
	mysql -u $MyUSER -h $MyHOST -p$MyPASS -NBe "SHOW TABLE STATUS;" $database | while read name engine version rowformat rows avgrowlength datalength maxdatalength indexlength datafree autoincrement createtime updatetime checktime collation checksum createoptions comment ; do
		#skip views
        if [ "$datafree" = "NULL" ] ; then
            continue
        fi
		if [ "$datafree" -gt 0 ] ; then
   			fragmentation=$(($datafree * 100 / $datalength))
   			echo "$database.$name is $fragmentation% fragmented."
   			mysql -u $MyUSER -h $MyHOST -p$MyPASS -NBe "OPTIMIZE TABLE $name;" "$database"
  		fi
	done
done