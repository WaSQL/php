#!/bin/bash
cd /var/www
for d in * ; do
	if [ -d "/var/www/$d/w_min" ] 
	then
	    echo "cleaning /var/www/$d/w_min"
    	rm -f /var/www/$d/w_min/*.* 
	fi
done
