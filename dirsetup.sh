#!/bin/sh
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

#setup soft links to  php, and wfiles
ln -s $SDIR/php/ php
ln -s $SDIR/wfiles/ wfiles

#softlink to .htaccess file
ln -s $SDIR/.htaccess .htaccess

#create an images directory and set permissions if it does not exist
if [ ! -d images ];
then
	mkdir images
	chmod 755 images
	mkdir w_min
	chmod 777 w_min
fi
