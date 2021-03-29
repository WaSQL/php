#!/bin/bash
if [ -d "c:/AppServ/www" ]; then 
	basepath="c:/AppServ/www"
else
	full_path=$(realpath "$0")
	dir_path=$(dirname "$full_path")
	wasqlpath=$(dirname "$dir_path" )
	basepath=$(dirname "$wasqlpath" )
fi

echo "Basepath: $basepath"

if [ -d "$basepath/w_min" ] 
then
    echo "cleaning $basepath/w_min"
	rm -f "$basepath/w_min/*.*" 
fi
basefiles="$basepath/*"
for sdir in $basefiles ; do
	echo "checking for $basepath/$sdir/w_min"
	if [ -d "$basepath/$sdir/w_min" ] 
	then
	    echo " -- cleaning $basepath/$sdir/w_min"
    	rm -f "$basepath/$sdir/w_min/*.*"
    elif [ -d "$sdir/w_min" ] 
	then
	    echo " -- cleaning $sdir/w_min"
    	rm -f "$sdir/w_min/*.*" 
	fi
done
