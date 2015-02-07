#!/bin/sh

# Version 0.7, 2008-08-20
#
# - Updated 2008-08-20 by Beau -
#   -Updated pretty much every package.
# - Updated 2007-12-16 by AskApache 
#   - Implemented functions to fetch the URI and decompress it
#   - Added a couple more error-checks
#   - Replaced wget with cURL
#   - Added more to help keep it from getting killed
#   - Updated to php-5.2.3, curl-7.17.1, freetype-2.3.5 
# - Updated 2007-01-15 by Charles Wiltgen (charles@wiltgen.net)
#   - Make "nicer" to help keep it from getting killed by DreamHost
#   - Make less verbose to keep signal-to-noise level high
# - Updated 2006-12-25 by Carl McDade (hiveminds.co.uk)
#   - Allow memory limit and freetype

# Abort on any errors
set -e


# Where do you want all this stuff built? I'd recommend picking a local filesystem.
# ***Don't pick a directory that already exists!***
SRCDIR=${HOME}/phpsource

# And where should it be installed?
INSTALLDIR=${HOME}/php5

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

# Push the install dir's bin directory into the path
export PATH=${INSTALLDIR}/bin:$PATH


function aa_unpack () {
	# compressed, tar and gzip files to DISTDIR
	if [ -f $DISTDIR/$1* ] ; then
		echo Extracting "$1";
		zcat ${DISTDIR}/$1* | tar -xvf - &>/dev/null; 
		echo Done.;	echo; wait
	fi
}


function aa_grab () {
	#saves file to SRCDIR
    echo `basename $1`
	curl -L --retry 20 --max-time 1800 --retry-delay 30 -# -f --max-redirs 4 --remote-name "$1"
}


echo
echo --------------------------------------------------
echo --   Run this script before php5-install.sh     --
echo --------------------------------------------------
echo
echo - Downloads and unpacks all prerequisite packages
echo - **SRCDIR and DISTDIR will be deleted**
echo
read -p  "        (Press any key to continue)" temp;
echo;echo

# cleanup to remove source and dist directories if present
if [ -d "$SRCDIR" ] || [ -d "$DISTDIR" ];then
	echo
	echo --- Cleaning up any previous attempts ---
	rm -rf $SRCDIR $DISTDIR &>/dev/null
	echo Done.
	echo
	wait
fi


# set up directories
mkdir -p ${SRCDIR} ${INSTALLDIR} ${DISTDIR} &>/dev/null

# Get all the required packages
echo;echo
echo --- Downloading all required packages ---
echo

cd ${DISTDIR}
aa_grab http://us.php.net/distributions/${PHP5}.tar.gz
aa_grab http://mirrors.usc.edu/pub/gnu/libiconv/${LIBICONV}.tar.gz
aa_grab http://internap.dl.sourceforge.net/sourceforge/mcrypt/${LIBMCRYPT}.tar.gz
aa_grab ftp://xmlsoft.org/libxml2/${LIBXML2}.tar.gz
aa_grab ftp://xmlsoft.org/libxml2/${LIBXSLT}.tar.gz
aa_grab http://internap.dl.sourceforge.net/sourceforge/mhash/${MHASH}.tar.gz
aa_grab http://www.zlib.net/${ZLIB}.tar.gz
aa_grab http://curl.haxx.se/download/${CURL}.tar.gz
aa_grab http://internap.dl.sourceforge.net/sourceforge/freetype/${FREETYPE}.tar.gz
aa_grab ftp://alpha.gnu.org/pub/gnu/libidn/${LIBIDN}.tar.gz
aa_grab http://www.openssl.org/source/${OPENSSL}.tar.gz
#aa_grab ftp://ftp.cac.washington.edu/imap/${CCLIENT}.tar.Z
wait
echo Done.


# Extract the files from the required packages.
echo;echo;echo
echo --- Unpacking downloaded archives. This process may take several minutes! ---
echo

cd ${SRCDIR}
aa_unpack ${PHP5}
aa_unpack ${LIBICONV}
aa_unpack ${LIBMCRYPT}
aa_unpack ${LIBXML2}
aa_unpack ${LIBXSLT}
aa_unpack ${MHASH}
aa_unpack ${ZLIB}
aa_unpack ${CURL}
aa_unpack ${LIBIDN}
aa_unpack ${OPENSSL}
aa_unpack ${CCLIENT}
aa_unpack ${FREETYPE}
wait

echo --------------------------------------------------
echo -- Done downloading and unpacking prerequisites --
echo --------------------------------------------------

exit 0;
