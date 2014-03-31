#!/bin/bash
#update script to get the latest geolitecity.dat file 
#http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
rm GeoLiteCity.dat
gzip -d GeoLiteCity.dat.gz