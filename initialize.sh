#!/bin/sh
#copy sample.config.xml to sample.xml
#copy samppe.htaccess to .htaccess
#chmod 755 *.pl *.sh sh/*.sh
#mkdir php/temp
#chmod 777 php/temp
#get the path of the script
if [ -L $0 ] ; then
    ME=$(readlink $0)
else
    ME=$0
fi
SDIR=$(dirname $ME)

#check for config.xml file
if [ ! -f $SDIR/config.xml ];
then
	cp $SDIR/sample.config.xml $SDIR/config.xml
fi

#check for .htaccess file
if [ ! -f $SDIR/.htaccess ];
then
	cp $SDIR/sample.htaccess $SDIR/.htaccess
fi
#assign execute permissioins to perl and shell scripts
chmod 755 *.pl *.sh sh/*.sh

#create the php/temp directory and set permissions if it does not exist
if [ ! -d $SDIR/php/temp ];
then
	mkdir $SDIR/php/temp
	chmod 777 $SDIR/php/temp
fi
echo WaSQL is now initialized
