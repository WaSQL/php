#!/bin/sh

echo php5-install.sh
echo - This script compiles and installs PHP 5 and all prerequisites
echo - Run php5-install-prep.sh before running this script
echo
read -p  "(Press any key to continue)" temp;
echo
date
# Version 0.7, 2008-08-20
#
# - Updated 2008-08-20 by Beau - (tesseract@hush.com)
#   -Updated pretty much every package.
# - Updated 2008-04-05 by Tom Ransom (transom@well.com)
#   - Added missing continuation slash to line #81 (after enable-memory-limit)
# - updated 2008-01-16 by osha (osha@iqresearch.com)
#   - fixed some typos and made more explicit what the domain variable is
# - Updated 2007-12-20 by David Szpunar (infotech.lakeviewchurch.org)
#   - Update versions of freetype, curl, php5 to match php5-install-prep.sh
# - Updated 2007-01-15 by Charles Wiltgen (charles@wiltgen.net)
#   - Make "nicer" to help keep it from getting killed by DreamHost
#   - Make less verbose to keep signal-to-noise level high
# - Updated 2006-12-25 by Carl McDade (hiveminds.co.uk)
#   - Allow memory limit and freetype
# - Updated 2009-01-08 by Eddie Webb (edwardawebb.com)
#   - shh'd openssl install (minor edit)

# Abort on any errors
set -e

##################################
# User-editable settings (start) #
##################################

# The domain in which to install the PHP CGI script
# this is the name of the folder that your domain reads files from
# (no "www" unless it is in the folder name)
#export DOMAIN="your-domain-here.com"

# Where do you want all this stuff built? I'd recommend picking a local
# filesystem.
# ***Don't pick a directory that already exists!***  We clean up after
# ourselves at the end!
SRCDIR=${HOME}/phpsource

# And where should it be installed?
INSTALLDIR=${HOME}/php5
export PATH=${INSTALLDIR}/bin:$PATH

# Set DISTDIR to somewhere persistent, if you plan to muck around with this
# script and run it several times!
DISTDIR=${HOME}/dist

#include the php_versions.sh file to bring in version variables
if [! -f php_versions.sh ];
then
	echo php_versions file is missing!
	exit 1
fi
source php_versions.sh

# What PHP features do you want enabled?
PHPFEATURES="
  --prefix=${INSTALLDIR} --datadir=${INSTALLDIR}/share \
  --localstatedir=${INSTALLDIR}/var --enable-sockets \
  --enable-fastcgi --enable-bcmath --with-pear=${INSTALLDIR}/lib/pear \
  --with-mysql=/usr --enable-calendar --with-mhash=/usr --with-kerberos \
  --enable-force-cgi-redirect --with-config-file-path=${INSTALLDIR}/etc/php5 \
  --with-imap --with-imap-ssl --with-gd \
  --with-xsl --with-ttf=/usr --with-freetype-dir=/usr --enable-exif \
  --with-jpeg-dir=/usr --with-png-dir=/usr --with-zlib-dir=/usr \
  --with-pdo-mysql --enable-ftp --with-curl=/usr --with-pspell=/usr \
  --enable-mbstring --with-mcrypt --with-mysqli --with-openssl=/usr \
  --with-gettext --enable-soap"

################################
# User-editable settings (end) #
################################

echo ------------------------------------------------------
echo -- Compiling and installing PHP 5 and prerequisites --
echo ------------------------------------------------------

# Pre-prep cleanup
rm -rf $INSTALLDIR
mkdir -p "${INSTALLDIR}/logs"
# Build packages in the required order to satisfy dependencies.

#
# libiconv
#

echo
echo --- Building libiconv
echo
cd ${SRCDIR}/${LIBICONV}
echo "    Configuring..."
./configure --enable-extra-encodings --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/libiconv_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/libiconv_make.log
./libtool --finish ${INSTALLDIR}

echo "    Installing..."
make install > ${INSTALLDIR}/logs/libiconv_install.log
echo "    Done!"

#
# libxml2
#

echo
echo --- Building libxml2
echo
cd ${SRCDIR}/${LIBXML2}
echo "    Configuring..."
./configure --with-iconv=${INSTALLDIR} --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/libxml2_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/libxml2_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/libxml2_install.log
echo "    Done!"

#
# libxslt
#

echo
echo --- Building libxslt
echo
cd ${SRCDIR}/${LIBXSLT}
echo "    Configuring..."
./configure --prefix=${INSTALLDIR} \
	--with-libxml-prefix=${INSTALLDIR} \
	--with-libxml-include-prefix=${INSTALLDIR}/include/ \
	--with-libxml-libs-prefix=${INSTALLDIR}/lib/
	> ${INSTALLDIR}/logs/libxslt_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/libxslt_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/libxslt_install.log
echo "    Done!"

#
# zlib
#

echo
echo --- Building: zlib
echo
cd ${SRCDIR}/${ZLIB}
echo "    Configuring..."
./configure --shared --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/zlib_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/zlib_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/zlib_install.log
echo "    Done!"

#
# libmcrypt
#

echo
echo --- Building: libmcrypt
echo
cd ${SRCDIR}/${LIBMCRYPT}
echo "    Configuring..."
./configure --disable-posix-threads --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/libmcrypt_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/libmcrypt_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/libmcrypt_install.log
echo "    Done!"

#libmcrypt lltdl issue!!
cd  ${SRCDIR}/${LIBMCRYPT}/libltdl
echo "    Configuring..."
./configure --prefix=${INSTALLDIR} --enable-ltdl-install \
	> /dev/null 2>&1
echo "    Making..."
nice -n 19 make > /dev/null 2>&1
echo "    Installing..."
make install > /dev/null 2>&1
echo "    Done!"

#
# mhash
#

echo
echo --- Building: mhash
echo
cd ${SRCDIR}/${MHASH}
echo "    Configuring..."
./configure --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/mhash_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/mhash_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/mhash_install.log
echo "    Done!"

#
# freetype
#
# 
# echo
# echo --- Building: freetype
# echo
# cd ${SRCDIR}/${FREETYPE}
# echo "    Configuring..."
# ./configure --prefix=${INSTALLDIR} \
# 	> /dev/null 2>&1
# echo "    Making..."
# nice -n 19 make > /dev/null 2>&1
# echo "    Installing..."
# make install > /dev/null 2>&1
# echo "    Done!"

#
# libidn
#

echo
echo --- Building: libidn
echo
cd ${SRCDIR}/${LIBIDN}
echo "    Configuring..."
./configure --with-iconv-prefix=${INSTALLDIR} --prefix=${INSTALLDIR} \
	> ${INSTALLDIR}/logs/libidn_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/libidn_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/libidn_install.log
echo "    Done!"

#
# OpenSSL
#

# echo
# echo --- Building: OPENSSL
# echo
# cd ${SRCDIR}/${OPENSSL}
# echo "    Configuring..."
# ./config --prefix=${INSTALLDIR} \
# 	> /dev/null 2>&1
# echo "    Making..."
# nice -n 19 make > /dev/null 2>&1
# echo "    Installing..."
# make install > /dev/null 2>&1
# echo "    Done!"

#
# cURL
#

# echo
# echo --- Building: cURL
# echo
# cd ${SRCDIR}/${CURL}
# echo "    Configuring..."
# CPPFLAGS="-I${SRCDIR}/${OPENSSL}/include" LDFLAGS="-L${INSTALLDIR}/ssl" \
# ./configure --prefix=${INSTALLDIR}/lib --with-ssl=${INSTALLDIR}/lib --with-zlib=${INSTALLDIR}/lib \
# 	--with-libidn=${INSTALLDIR} --enable-ipv6 --enable-cookies \
# 	--enable-crypto-auth
# #	> /dev/null 2>&1
# echo "    Making..."
# nice -n 19 make > /dev/null 2>&1
# echo "    Installing..."
# make install > /dev/null 2>&1
# echo "    Done!"

#
# PHP 5
#
#export OPENSSL_LIBDIR=${INSTALLDIR}/ssl/lib
echo
echo --- Building PHP 5 ---
echo
cd ${SRCDIR}/${PHP5}
echo "    Configuring..."
./configure ${PHPFEATURES} \
	> ${INSTALLDIR}/logs/php5_configure.log
echo "    Making..."
nice -n 19 make > ${INSTALLDIR}/logs/php5_make.log
echo "    Installing..."
make install > ${INSTALLDIR}/logs/php5_install.log
echo "    Copying configuration file (PHP.INI)"
mkdir -p ${INSTALLDIR}/etc/php5
cp ${SRCDIR}/${PHP5}/php.ini-dist ${INSTALLDIR}/etc/php5/php.ini
#echo "    Copying PHP CGI"
#mkdir -p ${HOME}/${DOMAIN}/cgi-bin
#chmod 0755 ${HOME}/${DOMAIN}/cgi-bin

#cp ${INSTALLDIR}/bin/php-cgi ${HOME}/${DOMAIN}/cgi-bin/php.cgi
# uncomment the line below for *older* versions of php5 (<5.23)
# cp ${INSTALLDIR}/bin/php ${HOME}/${DOMAIN}/cgi-bin/php.cgi

echo
echo --- Cleaning up
echo

rm -rf $SRCDIR $DISTDIR

echo ---------------------------------------
echo ---------- INSTALL COMPLETE! ----------
echo ---------------------------------------

# Finally, you need to add this to your site's .htaccess file to use
# the version of PHP that you've just compiled:
#
# AddHandler phpFive .php
# Action phpFive /cgi-bin/php.cgi
