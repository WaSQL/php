#!/bin/sh

# Script updated 2009-08-01 by Ben Turley to add LDAP support
# Script updated 2009-05-24 by ksmoe to correct copying of correct PHP cgi file (php-cgi instead of php)
# Script updated 2006-12-25 by Carl McDade (hiveminds.co.uk) to allow memory limit and freetype
#
# Script updated 2007-11-24 by Andrew (ajmconsulting.net) to allow 3rd wget line to pass 
# LIBMCRYPT version information (was set as static download file name previously.)
#
# Script updated 2009-4-25 by Daniel (whathuhstudios.com) for latest source versions
# Save the code to a file as *.sh
# Abort on any errors
#
set -e


# Where do you want all this stuff built? I'd recommend picking a local
# filesystem.
# ***Don't pick a directory that already exists!***  We clean up after
# ourselves at the end!
SRCDIR=${HOME}/phpsource

# And where should it be installed?
INSTALLDIR=${HOME}/php5

# Set DISTDIR to somewhere persistent, if you plan to muck around with this
# script and run it several times!
DISTDIR=${HOME}/dist

# Pre-download clean up!!!!
rm -rf $SRCDIR $DISTDIR

# Update version information here.
PHP5="php-5.2.13"
LIBICONV="libiconv-1.13.1"
LIBMCRYPT="libmcrypt-2.5.8"
LIBXML2="libxml2-2.7.7"
LIBXSLT="libxslt-1.1.24"
MHASH="mhash-0.9.9.9"
ZLIB="zlib-1.2.3"
CURL="curl-7.18.2"
LIBIDN="libidn-0.6.14"
CCLIENT="imap-2004g"
CCLIENT_DIR="imap-2004g" # Another pest!
OPENSSL="openssl-0.9.8k"
OPENSSL_DIR="openssl-0.9.8k"
FREETYPE="freetype-2.3.9"
LDAP="openldap-2.4.17"

# What PHP features do you want enabled?
PHPFEATURES="--prefix=${INSTALLDIR} \
 --with-config-file-path=${INSTALLDIR}/etc/php5 \
 --enable-fastcgi \
 --enable-force-cgi-redirect \
 --with-xml \
 --with-libxml-dir=${INSTALLDIR} \
 --with-freetype-dir=${INSTALLDIR} \
 --enable-soap \
 --with-openssl=/usr \
 --with-mhash=${INSTALLDIR} \
 --with-mcrypt=${INSTALLDIR} \
 --with-zlib-dir=${INSTALLDIR} \
 --with-jpeg-dir=/usr \
 --with-png-dir=/usr \
 --with-gd \
 --enable-gd-native-ttf \
 --enable-memory-limit \
 --enable-ftp \
 --enable-exif \
 --enable-sockets \
 --enable-wddx \
 --with-iconv=${INSTALLDIR} \
 --enable-sqlite-utf8 \
 --enable-calendar \
 --with-curl=${INSTALLDIR} \
 --enable-mbstring \
 --enable-mbregex \
 --enable-bcmath \
 --with-mysql=/usr \
 --with-mysqli \
 --without-pear \
 --with-gettext \
 --with-openssl=${INSTALLDIR}"

# ---- end of user-editable bits. Hopefully! ----

# Push the install dir's bin directory into the path
export PATH=${INSTALLDIR}/bin:$PATH

# set up directories
mkdir -p ${SRCDIR}
mkdir -p ${INSTALLDIR}
mkdir -p ${DISTDIR}
cd ${DISTDIR}

# Get all the required packages
wget -c http://ca3.php.net/get/${PHP5}.tar.gz/from/us2.php.net/mirror
wget -c http://mirrors.usc.edu/pub/gnu/libiconv/${LIBICONV}.tar.gz
wget -c http://easynews.dl.sourceforge.net/sourceforge/mcrypt/${LIBMCRYPT}.tar.gz
wget -c ftp://xmlsoft.org/libxml2/${LIBXML2}.tar.gz
wget -c ftp://xmlsoft.org/libxml2/${LIBXSLT}.tar.gz
wget -c http://sourceforge.net/projects/mhash/files/mhash/0.9.9.9/${MHASH}.tar.gz/download
wget -c http://www.sfr-fresh.com/unix/misc/${ZLIB}.tar.gz
wget -c http://curl.haxx.se/download/${CURL}.tar.gz
wget -c http://kent.dl.sourceforge.net/sourceforge/freetype/${FREETYPE}.tar.gz
wget -c ftp://alpha.gnu.org/pub/gnu/libidn/${LIBIDN}.tar.gz
wget -c ftp://ftp.cac.washington.edu/imap/old/${CCLIENT}.tar.Z
wget -c http://www.openssl.org/source/${OPENSSL}.tar.gz
wget -c ftp://ftp.openldap.org/pub/OpenLDAP/openldap-release/${LDAP}.tgz



echo ---------- Unpacking downloaded archives. This process may take several minutes! ----------

cd ${SRCDIR}
# Unpack them all
echo Extracting ${PHP5}...
tar xzf ${DISTDIR}/${PHP5}.tar.gz
echo Done.
echo Extracting ${LIBICONV}...
tar xzf ${DISTDIR}/${LIBICONV}.tar.gz
echo Done.
echo Extracting ${LIBMCRYPT}...
tar xzf ${DISTDIR}/${LIBMCRYPT}.tar.gz
echo Done.
echo Extracting ${LIBXML2}...
tar xzf ${DISTDIR}/${LIBXML2}.tar.gz
echo Done.
echo Extracting ${LIBXSLT}...
tar xzf ${DISTDIR}/${LIBXSLT}.tar.gz
echo Done.
echo Extracting ${MHASH}...
tar xzf ${DISTDIR}/${MHASH}.tar.gz
echo Done.
echo Extracting ${ZLIB}...
tar xzf ${DISTDIR}/${ZLIB}.tar.gz
echo Done.
echo Extracting ${CURL}...
tar xzf ${DISTDIR}/${CURL}.tar.gz
echo Done.
echo Extracting ${LIBIDN}...
tar xzf ${DISTDIR}/${LIBIDN}.tar.gz
echo Done.
echo Extracting ${CCLIENT}...
uncompress -cd ${DISTDIR}/${CCLIENT}.tar.Z |tar x
echo Done.
echo Extracting ${OPENSSL}...
uncompress -cd ${DISTDIR}/${OPENSSL}.tar.gz |tar x
echo Done.
echo Extracting ${FREETYPE}...
tar xzf ${DISTDIR}/${FREETYPE}.tar.gz
echo Done.
echo Extracting ${LDAP}...
tar xzf ${DISTDIR}/${LDAP}.tgz > /dev/null
echo Done.

# Build them in the required order to satisfy dependencies.

#libiconv
cd ${SRCDIR}/${LIBICONV}
./configure --enable-extra-encodings --prefix=${INSTALLDIR}
# make clean
make
make install

#libxml2
cd ${SRCDIR}/${LIBXML2}
./configure --with-iconv=${INSTALLDIR} --prefix=${INSTALLDIR}
# make clean
make
make install

#libxslt
cd ${SRCDIR}/${LIBXSLT}
./configure --prefix=${INSTALLDIR} \
 --with-libxml-prefix=${INSTALLDIR} \
 --with-libxml-include-prefix=${INSTALLDIR}/include/ \
 --with-libxml-libs-prefix=${INSTALLDIR}/lib/
# make clean
make
make install

#zlib
cd ${SRCDIR}/${ZLIB}
./configure --shared --prefix=${INSTALLDIR}
# make clean
make
make install

#libmcrypt
cd ${SRCDIR}/${LIBMCRYPT}
./configure --disable-posix-threads --prefix=${INSTALLDIR}
# make clean
make
make install

#libmcrypt lltdl issue!!
cd  ${SRCDIR}/${LIBMCRYPT}/libltdl
./configure --prefix=${INSTALLDIR} --enable-ltdl-install
# make clean
make
make install

#mhash
cd ${SRCDIR}/${MHASH}
./configure --prefix=${INSTALLDIR}
# make clean
make
make install

#freetype
cd ${SRCDIR}/${FREETYPE}
./configure --prefix=${INSTALLDIR}
# make clean
make
make install

#libidn
cd ${SRCDIR}/${LIBIDN}
./configure --with-libiconv-prefix=${INSTALLDIR} --prefix=${INSTALLDIR}
# make clean
make
make install

# c-client
cd ${SRCDIR}/${CCLIENT_DIR}
make ldb
# Install targets are for wusses!
cp c-client/c-client.a ${INSTALLDIR}/lib/libc-client.a
cp c-client/*.h ${INSTALLDIR}/include

# openssl
cd ${SRCDIR}/${OPENSSL_DIR}
# use shared objects for cURL and ldap
./config --prefix=${INSTALLDIR} shared
make
make install

#cURL
cd ${SRCDIR}/${CURL}
./configure --with-ssl=${INSTALLDIR} --with-zlib=${INSTALLDIR} \
  --with-libidn=${INSTALLDIR} --enable-ipv6 --enable-cookies \
  --enable-crypto-auth --prefix=${INSTALLDIR}
# make clean
make
make install

# ldap
cd ${SRCDIR}/${LDAP}
./configure --prefix=${INSTALLDIR} --disable-slapd --disable-slurpd --with-tls
make
make install

#PHP 5
cd ${SRCDIR}/${PHP5}
./configure ${PHPFEATURES}
# make clean
make
make install

#copy config file
mkdir -p ${INSTALLDIR}/etc/php5
cp ${SRCDIR}/${PHP5}/php.ini-dist ${INSTALLDIR}/etc/php5/php.ini

#cleanup
rm -rf $SRCDIR $DISTDIR
echo ---------- INSTALL COMPLETE! ----------
