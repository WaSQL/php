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
	dbname=$1
	sql=$2
	if [ -z "$dbname" ] || [ -z "$sql" ]; then
		echo "usage: $ME dbname /path/to/dump.sql[.gz]"
		exit 1
	fi
	if [ ! -f "$sql" ]; then
		echo "dump file not found: $sql"
		exit 1
	fi
	#pass the password in the environment instead of on the command line - keeps it out
	#of ps output, silences the "Using a password on the command line" warning, and
	#survives passwords containing $ or other characters the shell would mangle
	export MYSQL_PWD="$dbpass"
	MYSQL="mysql -h $dbhost --user=$dbuser"

	#the WaSQL admin backup gzips its dumps on linux, so accept .gz directly
	case "$sql" in
		*.gz) CAT=zcat;;
		*)    CAT=cat;;
	esac

	#A dump taken from MySQL 8 carries collation names that only exist in 8.0
	#(utf8mb4_0900_ai_ci and friends) plus the renamed utf8mb3 charset.  Feeding that
	#to 5.7 or MariaDB dies on the first CREATE TABLE with:
	#	ERROR 1273 (HY000) at line 25: Unknown collation: 'utf8mb4_0900_ai_ci'
	#mysqldump has no flag that downgrades those names, so ask THIS server what it
	#actually supports and rewrite only what it does not know.
	collationExists(){
		local n=$($MYSQL -NBe "SELECT COUNT(*) FROM information_schema.collations WHERE collation_name='$1';" 2>/dev/null)
		[ "$n" = "1" ]
	}
	SEDARGS=(-E)
	charset=utf8mb4
	collate=utf8mb4_unicode_ci
	if collationExists 'utf8mb4_0900_ai_ci'; then
		collate=utf8mb4_0900_ai_ci
	else
		echo "target does not support utf8mb4_0900_* - rewriting those collations to utf8mb4_unicode_ci"
		SEDARGS+=(-e 's/utf8mb4_0900_[a-z0-9_]+/utf8mb4_unicode_ci/g')
	fi
	if ! collationExists 'utf8mb4_unicode_ci'; then
		#very old servers (pre 5.5.3) have no utf8mb4 at all
		echo "target does not support utf8mb4 - rewriting to utf8"
		charset=utf8
		collate=utf8_general_ci
		SEDARGS+=(-e 's/utf8mb4_[a-z0-9_]+/utf8_general_ci/g' -e 's/utf8mb4/utf8/g')
	fi
	if ! collationExists 'utf8mb3_general_ci'; then
		#utf8mb3 is the 8.0 name for what older servers call plain utf8
		SEDARGS+=(-e 's/utf8mb3/utf8/g')
	fi

	#sed with no expressions is a usage error, so fall back to a plain pass-through
	FILTER=(cat)
	if [ ${#SEDARGS[@]} -gt 1 ]; then
		FILTER=(sed "${SEDARGS[@]}")
	fi

	echo "importing $sql into $dbname ($charset / $collate)"
	$MYSQL --execute="DROP DATABASE IF EXISTS \`$dbname\`;CREATE DATABASE \`$dbname\` CHARACTER SET $charset COLLATE $collate;" || exit 1
	$CAT "$sql" | "${FILTER[@]}" | $MYSQL --max_allowed_packet=128M --default-character-set=$charset "$dbname"
	rtn=${PIPESTATUS[2]}
	if [ "$rtn" = "0" ]; then
		echo "import complete"
	else
		echo "import FAILED (mysql exit $rtn)"
	fi
	exit $rtn
else
	echo db_settings file is missing!
	exit 1
fi
