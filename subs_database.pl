# subs_database.pl
#################################################################
#  WaSQL - Copyright 2004 - 2006 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
##########################################
# ToDo List
#Support Oracle via ODBC
# Change main DB calls to:
#	single record calls: %list=getDBRecord, editDBRecord, addDBRecord, delDBRecord
#	multi record calls:	 getDBRecords, editDBRecords, delDBRecords, truncateDBRecords  (with a -where options)
#Database Routines
###############
sub addDBAccess(){
	#print "Content-type: text/plain\n\n";
	#add a record to the _access page
    if(!isDBTable("_access")){
		return false;
		}
	my $access_days=60;
	if($Config{access_log}){
		if($Config{access_log}=~/^(false|0|off)$/i){return false;}
		elsif(isNum($Config{access_log})){$access_days=$Config{access_log};}
		}
	my @fields=getDBFields("_access");
	my %opts=();
	foreach my $field (@fields){
		$ufield=uc($field);
		if($input{$field}){$opts{$field}=$input{$field};}
		elsif($ENV{$ufield}){$opts{$field}=$ENV{$ufield};}
        }
    $opts{'page'}=$PAGE{'name'};
    $opts{'session_id'}=$ENV{GUID};
    $opts{'xml'}=request2XML(\%input);
    $opts{'-table'}="_access";
    #abort("Opts:" . hashValues(\%opts));
	$id=addDBRecord("_access",%opts);
	if(!isNum($id)){
		#abort($id);
		$ENV{'wasql_process_error'} .= "addDBAccess Error: {$id}\n";
		}
	if(isDBTable("_access_summary")){
		my $finfo=getDBFieldInfo("_access_summary");
		my @parts=();
		foreach my $field (@fields){
			if($finfo->{"$field\_unique"}){
				push(@parts,"count(distinct($field)) as $field\_unique");
        		}
			}
        $query="select http_host,count(_id) as visits,count(distinct(guid)) as visits_unique,".join(',',@parts)." from _access where YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW()) group by http_host";
		my %recs=getDBRecords('-query'=>$query);
		if($recs{-error}){
			return $recs{-error};
			}
		for(my $x=0;$x<$recs{count};$x++){
			my %opts=();
			foreach my $key (keys(%{$recs{$x}})){
				$opts{$key}=$recs{$x}{$key};
				}
			my %rec=getDBRecord('-table'=>"_access_summary",'-where'=>"YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW())");
			if($rec{-error}){
				#print $rec{-error};
				return $rec{-error};
				}
			if($rec{_id}){
				$id=$rec{_id};
				$ok=editDBData("_access_summary","_id=$id",%opts);
				}
			else{
				$opts{'adate'}=getDate("YYYY-NM-ND");
				$id=addDBRecord("_access_summary",%opts);
				if(!isNum($id)){
					$ENV{'wasql_process_error'}.= "addDBAccess Summary Error: $id";
					}
				}
			}
		#remove _access records older than 3 months
		$seconds_old=int(time()-int(60*60*24*$access_days));
		$age=getDate("YYYY-NM-ND",$seconds_old);
		#abort("_cdate < '$age'");
		$ok=deleteDBData("_access","_cdate < '$age'");
	    }
    return 1;
	}
###############
sub addDBColumn{
	#internal usage: $ck=addDBColumn($tablename,name=>'varchar(255)');
	#internal info:  adds column table
	$DBI::query='';
	if($dbt=~/^sqlite$/i){return "SQLite does not support modifying a column";}
	my $tablename=lc(shift) || return "No Table in addDBColumn";
	my %params=@_;
	my $query = qq|alter table $tablename |;
	my @tmp=();
	foreach my $field (keys(%params)){
		my $type=$params{$field};
		push(@tmp,"add $field $type");
		}
	my $tcnt=@tmp;
	if($tcnt==0){return 'No Fields passed to add';}
	$query .= join(",",@tmp);
	$DBI::query=$query;
	my $sth = $dbh->prepare($query);
	if(length($DBI::err)){
		if($sth){
			$sth->finish;
			undef $sth;
			}
		my $error=qq|<div class="w_border w_padding w_margin"><b class="w_red w_bold">addDBColumn Prepare Error</b>\n|;
		$error .= qq|	<div class="w_red w_indent"><b>Error Msg:</b> | . $DBI::errstr . "</div>\n";
		$error .= qq|	<div class="w_indent"><b>Query:</b> $query</div>\n|;
		$error .= qq|</div>\n|;
		return $error;
		}
	$sth->execute();
	if(length($DBI::err)){
		my $error=qq|<div class="w_border w_padding w_margin"><b class="w_red w_bold">addDBColumn Execute Error</b>\n|;
		$error .= qq|	<div class="w_red w_indent"><b>Error Msg:</b> | . $DBI::errstr . "</div>\n";
		$error .= qq|	<div class="w_indent"><b>Query:</b> $query</div>\n|;
		$error .= qq|</div>\n|;
		return $error;
    	}
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
#################
sub addDBData {
	#usage: $newid=addDBData($tablename,name=>'Bob',age=>25);
	#info:  adds a record to table with data passed in. returns the id of the new record
	#tags: database
	$DBI::query='';
	my $tablename=lc(shift) || return "No Table in AddDBData";
	#Get the field types so we know whether or not to qoute the values
	my %Ftype=();
	getDBFieldTypes(\%Ftype,$tablename);
	my %sets=@_;
	if(!length($sets{_cdate}) && defined($Ftype{_cdate})){$sets{_cdate}=getDate("YYYY-NM-ND MH:MM:SS");}
	if(!length($sets{_cuser}) && defined($Ftype{_cuser}) && isNum($USER{_id})){$sets{_cuser}=$USER{_id};}
	elsif(defined($Ftype{_cuser})){$sets{_cuser}=0;}
	my @fields=keys(%sets);
	my @vals=();
	my @addfields=();
	foreach my $field (@fields){
		next if !length($Ftype{$field});
		push(@addfields,$field);
		my $val=strip($sets{$field});
		if($val=~/^\'/s && $val=~/\'$/s){}
		elsif(length($Ftype{$field}) && $Ftype{$field}=~/^date$/is && $val=~/^([0-9]{2,2})[\-\/]([0-9]{2,2})[\-\/]([0-9]{4,4})/s){
			$val=$3 . '-' . $1 . '-' . $2;
			$val=prepDBString($val);
			}
		elsif(length($Ftype{$field}) && $Ftype{$field}=~/^time$/is){
			my ($hr,$min,$sec);
			if($val=~/^([0-9]{1,2})\:([0-9]{1,2})(AM|PM)$/is){
	               ($hr,$min)=($1,$2);
	               if($3=~/^PM$/is){$hr=$hr+12;}
		          }
			elsif($val=~/^([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})(.*)/is){
                    ($hr,$min,$sec)=($1,$2,$3);
               	}
			my $newval=$hr . ':' . $min . ':' . $sec;
			#abort("val=$val -- newval=$newval");
			$val=prepDBString($newval);
			}
		elsif(length($Ftype{$field}) && $Ftype{$field}!~/^(bit|tinyint|bigint|decimal|integer|smallint|float|real|number)$/is){$val=prepDBString($val);}

		elsif($val=~/[^0-9\.]/s || $val=~/[a-z]/is || $val=~/\..*?\./s){$val=prepDBString($val);}
		if($val=~/^\.[0-9]+$/s){$val="0".$val;}
		if(length($val)==0){$val="\'\'";}
		elsif($val=~/^\.$/is){$val="\'\.\'";}
		push(@vals,$val);
		}
	my $fieldstr=lc(join(",",@addfields));
	my $valstr=join(",",@vals);
	my $query = 'insert into ' . $tablename . ' (' . $fieldstr . ') values (' . $valstr .')';
	#print "$query\n" if $tablename=~/^\_users$/is;
	#$sth = $dbh->do($query) || print $DBI::err;
	#print "HERE<br>\n";
	$DBI::query=$query;
	#print "$query\n";
	my $sth = $dbh->prepare($query);
	#print "Prepared<br>$DBI::err<br>\n";
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return "Prepare Failed:<br>\nError: [$DBI::errstr][$DBI::err]";}
	#print "<br> executing<br>\n";
	$sth->execute();
	#print "Executed<br>$DBI::err<br>\n";
	if(length($DBI::err)){
		my $err=$DBI::err;
		my $errstr=$DBI::errstr;
		if($sth){$sth->finish;undef $sth;}return "Execute Failed:<br>\nErrors: [$errstr][$err]";}
	#return the new_id
	if($dbt=~/^mysql/i){
		$new_id = $sth->{mysql_insertid};
		if($sth){$sth->finish;undef $sth;}
		}
	elsif($dbt=~/^sqlite$/i){
		$new_id = $dbh->func('last_insert_rowid');
		if($sth){$sth->finish;undef $sth;}
		}
	elsif($dbt=~/msaccess|mssql/is){
		if($sth){$sth->finish;}
		my $aquery="select max(_id) from $tablename";
		if($dbt=~/ms-sql|mssql/i){$aquery="select \@\@identity as newid";}
		$sth = $dbh->prepare($aquery);
		$sth->execute();
		$new_id = $sth->fetchrow_array;
		if($sth){$sth->finish;undef $sth;}
		}
	if($sth){$sth->finish;undef $sth;}
	if(wantarray){return ($new_id,$query);}
	return $new_id;
	}
#################
sub prepDBFieldValue{
	my $ftype=shift || return;
	my $val=shift;
	$val=strip($val);
	if($ftype=~/^date$/is && $val=~/^([0-9]{2,2})[\-\/]([0-9]{2,2})[\-\/]([0-9]{4,4})/s){
		$val=$3 . '-' . $1 . '-' . $2;
		$val=prepDBString($val);
		}
	elsif($ftype=~/^time$/is){
		my ($hr,$min,$sec);
		if($val=~/^([0-9]{1,2})\:([0-9]{1,2})(AM|PM)$/is){
            ($hr,$min)=($1,$2);
            if($3=~/^PM$/is){$hr=$hr+12;}
	        }
		elsif($val=~/^([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})(.*)/is){
            ($hr,$min,$sec)=($1,$2,$3);
            }
		my $newval=$hr . ':' . $min . ':' . $sec;
		$val=prepDBString($newval);
		}
	elsif($ftype!~/^(bit|tinyint|bigint|decimal|integer|smallint|float|real|number)$/is){
		$val=prepDBString($val);
		}
	elsif($val=~/[^0-9\.]/s || $val=~/[a-z]/is || $val=~/\..*?\./s){
		$val=prepDBString($val);
		}
	if($val=~/^\.[0-9]+$/s){
		$val="0".$val;
		}
	if(length($val)==0){$val="\'\'";}
	elsif($val=~/^\.$/is){$val="\'\.\'";}
	return $val;
	}
#################
sub addDBRecord{
	#usage: addDBRecord($tablename,field=>val,field2=>val2);
	#info: adds a record to $tablename
	#tags: database
	my $table=shift || return "No Table in addDBRecord";
	my %params=@_;
 	my @vals=();
 	my @fields=getDBFields($table,1);
 	foreach my $field (@fields){
		my $val='';
		if(length($params{$field})){$val=$params{$field};}
		elsif(length($input{$field})){$val=$input{$field};}
		push(@vals,$field=>$val) if length($val);
		}
	my $vcnt=@vals;
	return "No Vals [$table][@fields][$vcnt]" if !$vcnt;
	my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
	my ($id,$sql)=addDBData($table,
	   _cdate	=> $cdate,
	   @vals,
	   );
	if(!isNum($id)){return "Error: $id $sql";}
	return $id;
 	}
#################
sub alterDBTable{
	$DBI::query='';
	my $tablename=lc(shift) || return "No Table in alterDBTable";
	my %pairs=@_;
	$tablename=strip($tablename);
	$tablename=~s/[\r\n\t]+//sg;
	if(isDBReservedWord($tablename)){return qq|<u>$tablename</u> is a reserved word|;}
	#check fields for reserved words
	foreach my $field (keys(%pairs)){
		next if $field=~/^\_/s;
		if(isDBReservedWord($field)){return qq|<u>$field</u> is a reserved word|;}
		}

	if($dbt!~/sqlite/is){
		#$ck=modifyDBColumn($tablename,name=>'varchar(255)');
		#get current table schema.
		my @tmp=getTableSchema($tablename);
		my %cpairs=@tmp;
		print qq|Altering schema for $tablename<br>\n|;
		foreach my $field (keys(%pairs)){
			next if $field=~/^\_/s;
			my $type=$pairs{$field};
			my $ctype=$cpairs{$field};
			if(length($ctype) && $type !~ /^\Q$ctype\E$/is){
				print qq|<div style="padding-left:20px">modify $field from "$ctype" to "$type"</div>\n|;
				my $ck=modifyDBColumn($tablename,$field=>$type);
				if(!isNum($ck)){return $ck;}
				delete($cpairs{$field});
				}
			#add if field is new
			elsif(length($ctype)==0){
				print qq|<div style="padding-left:20px">add $field => $type</div>\n|;
				my $ck=addDBColumn($tablename,$field=>$type);
				if($ck != 1){return "Error adding $field $type<br> $ck";}
				delete($cpairs{$field});
				}
			}
		#drop any fields left if cpairs
		my @dropfields=();
		foreach my $field (keys(%cpairs)){
			next if $field=~/^\_/s;
			next if length($pairs{$field});
			push(@dropfields,$field);
			}
		my $dcnt=@dropfields;
		if($dcnt){
			print qq|<div style="padding-left:20px">dropping fields: @dropfields</div>\n|;
			$ck=dropDBColumn($tablename,@dropfields);
			if($ck != 1){return $ck;}
			}
		return 1;
		}
	#if you got this far, the database is sqlite. dump and recreate the table with new fields
#	SQLite does not support the "ALTER TABLE" SQL command. If you what to change the structure of a table, you have to recreate the table. You can save existing data to a temporary table, drop the old table, create the new table, then copy the data back in from the temporary table.
#	For example, suppose you have a table named "table1" with columns names "a", "b", and "c" and that you want to delete column "c" from this table. The following steps illustrate how this could be done:
# 	BEGIN TRANSACTION;
# 	CREATE TEMPORARY TABLE table1_backup(a,b);
# 	INSERT INTO table1_backup SELECT a,b FROM table1;
# 	DROP TABLE table1;
# 	CREATE TABLE table1(a,b);
# 	INSERT INTO table1 SELECT a,b FROM table1_backup;
# 	DROP TABLE table1_backup;
# 	COMMIT;
	#Get current tables schema
	my %mlist=();
	my $sql="select sql from sqlite_master where type=\'table\' and name=\'$tablename\'";
	my $mcnt=getDBData(\%mlist,$sql,"nocount=1");
	#print "$sql\n$mcnt\n";
	my $oldsql=$mlist{0}{sql};
	#get field list of current table
	%mlist=();
	$mcnt=getDBData(\%mlist,"select * from $tablename where 1=0","nocount=1");
	my @oldfields=@{$mlist{fields}};
	my $oldfieldstr=join(',',@oldfields);
	my @newfields=keys(%pairs);
	my $newfieldstr=join(',',@newfields);
	#compare oldfields to newfields and create a sharedfields array with fields in both
	my @sharedfields=();
	foreach my $fld (@oldfields){
		if(length($pairs{$fld})){push(@sharedfields,$fld);}
		}
	my $sharedfieldstr=join(',',@sharedfields);
	my $temptable=$input{_table} . "\_backup";
	#create table _pages ( body
	$oldsql=~m/create table (.+)/is;
	my $oldstrip=$1;
	$oldstrip=~s/\Q$tablename\E//is;
	$sql="create table $temptable" . $oldstrip . "\;\n";
	my $ck=executeSQL($sql);
	if($ck !=1){return "Error1\n$sql\n $ck";}
	$ck=executeSQL("insert into $temptable select $oldfieldstr from $tablename\;\n");
	if($ck !=1){return "Error2 $ck";}
	$ck=executeSQL("drop table $tablename;\n");
	if($ck !=1){return "Error3 $ck";}
	$runsql = "create table $tablename (";
	my @tmp=();
	foreach my $fld (@newfields){
		push(@tmp,"$fld $pairs{$fld}");
	 	}
	$runsql .= join(',',@tmp);
	$runsql .=")\;\n";
	$ck=executeSQL($runsql);
	if($ck !=1){return "Error4\n$runsql\n $ck";}
	#The following line wont work if there are different number of columns
	#$runsql .="insert into $tablename select $sharedfieldstr from $temptable\;\n";
	#So lets Get the data in temptable and add it in ourselves.
	my %dlist=();
	my $ccnt=getDBData(\%dlist,"select * from $temptable","nocount=1");
	#return 1;
	if($ccnt > 0){
		my @data=();
		for(my $x=0;$x<$ccnt;$x++){
			@data=();
			foreach my $field (@sharedfields){
				my $val=$dlist{$x}{$field};
				$val=strip($val);
				next if length($val)==0;
				push(@data,$field=>$val);
				}
			my $num=addDBData($tablename,@data);
			}
		}
	$ck=executeSQL("drop table $temptable");
	if($ck !=1){return $ck;}
	return 1;
	}
############
sub cleanDBMetaData{
	#removes records in _fielddata that no longer match up with a valid table field
	my $table=shift || return "No table";
	my @fields=getDBFields($table);
	my %valid=();
	foreach my $field (@fields){$valid{$field}=1;}
	my %list=getDBRecords(-table=>"_fielddata",tablename=>$table);
	if($list{-error}){return $list{-error};}
	my $cnt=$list{count};
	my @ids=();
	for(my $x=0;$x<$cnt;$x++){
		my $id=$list{$x}{_id};
		my $name=$list{$x}{fieldname};
		my $tname=$list{$x}{tablename};
		next if $tname !~ /^\Q$table\E$/is;
		next if defined $valid{$name};
		push(@ids,$id);
    	}
    my $idcnt=@ids;
    if($idcnt){
		my $idstr=join(',',@ids);
		my $ok=deleteDBData("_fielddata","_id in ($idstr)");
		}
    return $idcnt;
	}
###############
sub cloneDBRecord{
	#usage: my $newid=cloneDBRecord(-table=>$tablename,-id=>$id,field=>$val,field2=>$val3);
	#info:  clones said record and returns the id of the new record or an error on failure.
	#tags: database
	my @inopts=@_;
	my %rec=getDBRecord(@opts);
	if($rec{-error}){return $rec{-error};}
	my %params=@inopts;
	#Get current records to copy
	my @fields=@{$rec{-fields}};
	my $copydate=getDate("YYYY-NM-ND MH:MM:SS");
	my @sets=(_cdate=>$copydate);
	foreach my $field (@fields){
		next if $field=~/^\_/s;
		my $val=strip($rec{$field});
		if(length($params{$field})){$val=strip($params{$field});}
		next if length($val)==0;
		push(@sets,$field=>$val);
		}
	my $new=addDBData($tablename,@sets);
	return $new;
	}
###############
sub createDBTable {
	$DBI::query='';
	my $tablename=lc(shift) || return "No tablename in createDBTable";
	my %fields = @_;
	#initialize
	$DBI::query='';
	my $sth;
	#Access and MS-SQL formatting
	if($dbt=~/msaccess|mssql/is){$tablename="[$tablename]";}
	#Check to make sure tablename is not a reserved word
	my $def;
	foreach my $field (keys(%fields)){
		my $type=$fields{$field};
		#change type based on database
		($field,$type)=fixDBFieldType(lc($field),$type);
		#Null?
		$def = $def . ',' . $field . ' ' . $type;
		if($type !~ /null/is){$def .= ' NULL';}
		}
	$def=~s/^[,]//;
	#Build SQL query
	my $query = 'create table ' . $tablename . ' ( ' . $def . ' )';
	$DBI::query=$query;
	#print "Creating Table: $tablename\n$query\n";
	#Prepare and Execute query
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return $DBI::errstr;
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
#####################
sub dbConnect{
	#internal usage: dbConnect(dbtype=>'mysql', dbhost=>'www.basgetti.com', dbname =>'basgetti', dbuser=>'jonhar', dbpass=>'dingo', verbose=> 0);
	#internal info: connects to said database. sets $dbh to database handle. returns previous database handle it it exist.
	my %params=@_;
	my $host=$params{host} || $params{dbhost} || 'localhost';
 	my $verbose=$params{verbose} || $Config{$host}{verbose} || 0;
	my $old_dbvhost=$dbvhost || $host;
	print "Connecting to $host...\r\n" if $verbose;
	if($dbh){$dbh->disconnect;}
	$dbvhost=$host;
	$dbt=$params{dbtype} || "SQLite";
	$dbhost=$params{dbhost} || "localhost";
	$dbname=$params{dbname} || abort("dbConnect Error: Unable to determine dbname");
	$dsn=$params{dsn} || qq|dbi:$dbt:dbname=$dbname:host=$dbhost|;
	my $dbuser=$params{dbuser};
	my $dbpass=$params{dbpass};
	our $dbiversion=$DBI::VERSION;
	our $dbversion='';
	#ODBC DSNless Connections Strings: http://www.basic-ultradev.com/articles/ADOConnections/
	#dBase
        #	Driver={Microsoft dBASE Driver (*.dbf)};DriverID=277;Dbq=c:\somepath\dbname.dbf;
	#	Driver={Microsoft dBASE Driver (*.dbf)};DriverID=277;Dbq=c:\somepath;
	#		Then specify the filename in the SQL statement:
	#		Select * From user.dbf
	#Excel
        #	Driver={Microsoft Excel Driver (*.xls)};DriverId=790;Dbq=c:\somepath\mySpreadsheet.xls;DefaultDir=c:\somepath;
        #MS Access
        #	Driver={Microsoft Access Driver (*.mdb)};Dbq=c:\somepath\dbname.mdb;Uid=Admin;Pwd=pass;
        #MS SQL Server
	#	Driver={SQL Server};Server=servername;Database=dbname;Uid=sa;Pwd=pass;
	#MS Text
	#	Driver={Microsoft Text Driver (*.txt; *.csv)};Dbq=c:\somepath\;Extensions=asc,csv,tab,txt;Persist Security Info=False;
	#MySQL DSNless connection
	#	driver={mysql}; database=yourdatabase;server=yourserver;uid=username;pwd=password;option=16386;
        #Oracle
        #	Driver={Microsoft ODBC for Oracle};Server=OracleServer.world;Uid=admin;Pwd=pass;
        #  or 	Driver={Oracle ODBC Driver};Dbq=myDBName;Uid=myUsername;Pwd=myPassword
        #		The DBQ name must be defined in the tnsnames.ora file
        #Paradox
        #	Driver={Microsoft Paradox Driver (*.db )};DriverID=538;Fil=Paradox 5.X;DefaultDir=c:\dbpath\;Dbq=c:\dbpath\;CollatingSequence=ASCII
	#Visual Foxpro
	#	Driver={Microsoft Visual FoxPro Driver};SourceType=DBC;SourceDB=c:\somepath\dbname.dbc;Exclusive=No;
        #	SourceType=DBC;SourceDB=c:\somepath\mySourceDb.dbc;Exclusive=No
        #		change SourceType=DBC to SourceType=DBF to connect without a database container (Free Table Directory)
	if($dbt=~/sqlite/is){
		eval('use DBD::SQLite;');
 		if($@){
			print "DBD::SQLite is not installed\nINC PATH\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		$dsn=$Config{$host}{dsn} || qq|dbi:SQLite:dbname=$dbname|;
		$dbversion=$DBD::SQLite::VERSION;
		}
	elsif($dbt=~/MsSQL/is){
		eval('use DBD::ODBC;');
 		if($@){
			print "DBD::ODBC is not installed\nINC PATH\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		$dsn=$Config{$host}{dsn} || qq|dbi:ODBC:driver={SQL Server};Server=$dbhost;database=$dbname;uid=$dbuser;pwd=$dbpass;|;
		$dbversion=$DBD::ODBC::VERSION;
		}
	elsif($dbt=~/Excel/is){
		eval('use DBD::ODBC;');
 		if($@){
			print "DBD::ODBC is not installed\nINC PATH\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		$dsn=$Config{$host}{dsn} || qq|dbi:ODBC:Driver={Microsoft Excel Driver (*.xls)};DriverId=790;Dbq=$dbname;DefaultDir=$dbhost;|;
		$dbversion=$DBD::ODBC::VERSION;
		}
	elsif($dbt=~/Oracle/is){
		eval('use DBD::ODBC;');
 		if($@){
			print "DBD::ODBC is not installed.\nINC PATH:\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		$dsn=$Config{$host}{dsn} || qq|dbi:ODBC:driver=Driver={Microsoft ODBC for Oracle};Server=$dbname;Uid=$dbuser;Pwd=$dbpass;|;
		$dbversion=$DBD::ODBC::VERSION;
		}
	elsif($dbt=~/MsAccess/is){
		eval('use DBD::ODBC;');
 		if($@){
			print "DBD::ODBC is not installed.\nINC PATH:\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		#String connString = "jdbc:odbc:DRIVER=Microsoft Access Driver(*.mdb);DBQ=c:/test.mdb;PWD=mypass";
		#Connection conn = DriverManager.getConnection(connString,"Admin","password");
		$dsn=$Config{$host}{dsn} || qq|dbi:ODBC:driver=Microsoft Access Driver (*.mdb);DBQ=$dbname;PWD=mypass|;
		$dbversion=$DBD::ODBC::VERSION;
		}
	elsif($dbt=~/mysql/is){
		eval('use DBD::mysql;');
 		if($@){
			print "DBD::mysql is not installed.\nINC PATH:\n";
			foreach my $i (@INC){
        		print " $i\n";
			}
		}
		$dsn=$Config{$host}{dsn} || qq|dbi:mysql:dbname=$dbname:host=$dbhost|;
		$dbversion=$DBD::mysql::VERSION;
		}
	else{
		print "Unknown dbtype [$dbt] for [$dbhost].\nINC PATH:\n";
		foreach my $i (@INC){
        	print " $i\n";
		}
		exit;
		}
	####################################
	if($verbose && $verbose==2){
		print "-------------------------\r\n";
		print "DBI Version: $dbiversion\r\n";
		print "DBD Version: $dbversion\r\n";
		print "DB Type: $dbt\r\n";
		print "DB Name: $dbname\r\n";
		print "DB Host: $dbhost\r\n";
		print "-------------------------\r\n";
		}
	####################################
	print "Connecting to $dsn\n" if $verbose;
	$dbh = DBI->connect($dsn, $dbuser, $dbpass,{PrintError=>0, RaiseError => 0, AutoCommit => 1 });
	if(!$dbh){
		if($verbose){
			print "Error connecting to $host\r\n";
			print "Dsn: $dsn\r\n";
			print "User: $dbuser\r\n";
			print "Pass: $dbpass\r\n";
			print "Error:$DBI::errstr\r\n";
			}
		return $!;
		}
	if($dbt=~/MsSQL/is){
		$dbh->{LongReadLen}=99999;
		}
	if($verbose){
		print "Connected.\r\n";
		print "-------------------------\r\n";
		}	
	return $old_dbvhost;
	}
#####################
sub dbDisconnect{
	#internal usage: dbDisconnect();
	#internal info: disconnects from the database if $dbh exists
	if($dbh){$dbh->disconnect;}
	}
###########
sub deleteDBData{
	#usage: deleteDBData($tablename,$criteria);
	#info:  deletes data from $tablename where $criteria. returns 1 on success, otherwise returns error message.
	#tags: database
	$DBI::query='';
	my $tablename=lc(shift) || return "No Table in deleteDBData";
	my $criteria=shift || return "No Criteria";
	#initialize
	my $start=times();
	$DBI::query='';
	my $sth;
	#Access and MS-SQL formatting
	if($dbt=~/msaccess|mssql/is){
		$tablename="[$tablename]";
		}
	# BUILD QUERY
	my $query = 'delete from ' . $tablename . ' where ' . $criteria;
	$DBI::query=$query;
	#return -1 if query is blank.
	$query=~s/[\r\n\t\ ]+$//;
	$query=~s/^[\ \t]+//;
	return 'Invalid Blank Query in Delete Data' if !$query;
	#Prepare and Execute Query
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return $DBI::errstr;
	if($sth){$sth->finish;undef $sth;}
	if(isDBTable("_history") && $tablename !~ /^\_(history|users|tabledata|fielddata)$/is && length($criteria) && $USER{_id}){
		my $ctime=localtime();
		my $user = $USER{username} || "unknown";
		my $note = "Records in $tablename where $criteria were deleted by $user on $ctime";
		my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
		my @opts=();
		if($criteria=~/\_id\=([0-9]+)/is){push(@opts,recid=>$1);}
		my $new=addDBData("_history",_cdate=>$cdate,tablename=>$tablename,note=>$note,@opts);
		}
	return 1;
	}
###############
sub dropDBColumn{
	#internal usage: $ck=dropDBColumn($tablename,@fields);
	#internal info:  drop column from table
	$DBI::query='';
	if($dbt=~/^sqlite$/i){return "SQLite does not support dropping a column";}
	my $tablename=lc(shift) || return "No Table in dropDBColumn";
	my @fields=@_;
	my @tmp=();
	my $query = qq|alter table $tablename |;
	foreach my $field (@fields){
		push(@tmp,"drop column $field");
		}
	my $tcnt=@tmp;
	if($tcnt==0){return "No fields to drop";}
	$query .= join(",",@tmp);
	$DBI::query=$query;
	my $sth = $dbh->prepare($query);
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("$query $DBI::errstr");}
	$sth->execute();
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("$query $DBI::errstr");}
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
###############
sub dropDBTable{
	#internal usage: $ck=dropDBTable($tablename);
	#internal info:  drops $tablename from database. returns 1 on success, otherwise returns error message.
	$DBI::query='';
	my $tablename=lc(shift) || return "No Table in dropDBTable";
	$DBI::query='';
	my $sth;
	#Access and MS-SQL formatting
	if($dbt=~/msaccess|mssql/is){$tablename="[$tablename]";}
	#Build Query
	my $query= 'drop table ' . $tablename;
	$DBI::query=$query;
	#Prepare and Execute query
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return $DBI::errstr;
	if($sth){$sth->finish;undef $sth;}
	return  1;
	}
###############
sub editDBData {
	#usage: editDBData($tablename,$criteria,name=>'Bob',age=>25);
	#info:  edits record in table where criteria with passed in data
	#tags: database
	$DBI::query='';
	my $tablename=lc(shift) || return "No Table in editDBData";
	my $criteria=shift || return "No Criteria";
	#Get the field types so we know whether or not to qoute the values
	my %Ftype=();
	getDBFieldTypes(\%Ftype,$tablename);
	my %sets=@_;
	if(!length($sets{_edate}) && defined($Ftype{_edate}) && $tablename !~/^\_users$/is){$sets{_edate}=getDate("YYYY-NM-ND MH:MM:SS");}
	if(!length($sets{_euser}) && defined($Ftype{_euser}) && $tablename !~/^\_users$/is){$sets{_euser}=$USER{_id};}
	my @fields=();
	my @vals=();
	foreach my $field (keys(%sets)){
		my $val=$sets{$field};
		next if !length($Ftype{$field});
		if($val=~/^\'/s && $val=~/\'$/s){}
		elsif(length($Ftype{$field}) && $Ftype{$field}=~/^time$/is){
			my ($hr,$min,$sec);
			if($val=~/^([0-9]{1,2})\:([0-9]{1,2})(AM|PM)$/is){
	               ($hr,$min)=($1,$2);
	               if($3=~/^PM$/is){$hr=$hr+12;}
		          }
			elsif($val=~/^([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})(.*)/is){
                    ($hr,$min,$sec)=($1,$2,$3);
               	}
			my $newval=$hr . ':' . $min . ':' . $sec;
			#abort("val=$val -- newval=$newval");
			$val=prepDBString($newval);
			}
		elsif(length($Ftype{$field}) && $Ftype{$field}!~/^(bit|tinyint|bigint|decimal|integer|smallint|float|real|number)$/is){$val=prepDBString($val);}
		elsif($val=~/[^0-9\.]/s || $val=~/[a-z]/is || $val=~/\..*?\./s){$val=prepDBString($val);}
		if(length($val)==0){$val="NULL";}
		if($val=~/^\.$/is){$val="\'\.\'";}
		push(@fields,"$field=$val");
		}
	my $fieldstr=join(",",@fields);
	#initialize
	$DBI::query='';
	my $sth;
	#Add ability for spaces in table and field names for MS-Access and MS-SQL
	if($dbt=~/msaccess|mssql/is){
		$tablename="[$tablename]";
		}
	#deference fields for Mysql
	if($dbt=~/^(mysql|sqlite)/is){$fields=~s/\\/\\\\/g;}
	# BUILD QUERY
	my $query = qq|update $tablename set $fieldstr|;
	#Add criteria if any
	$query = $query . ' where ' . $criteria	if $criteria;
	$DBI::query=$query;
	#print "$query\n";
	#return -1 if query is blank.
	$query=~s/[\r\n\t\ ]+$//;
	$query=~s/^[\ \t]+//;
	return "editDBData Error: Blank Query" if length($query)==0;
	#Get the values before the edit
	my %plist=();
	my $pcnt=0;
	if(isDBTable("_history") && $tablename !~ /^\_(history|users|tabledata|fielddata)$/is && length($criteria) && $USER{_id}){
		#get a list of records that were updated
		my $sql=qq|select * from $tablename where $criteria|;
		$pcnt=getDBData(\%plist,$sql,"nocount=1");
		if(!isNum($pcnt)){abort($pcnt);}
		}
	# Prepare and execute query
	#print "$query\n";
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return "$query\n$DBI::errstr\n";
	if($sth){$sth->finish;undef $sth;}
	#Record History of changes?
	if(1==2 && isDBTable("_history") && $tablename !~ /^\_(history|users|tabledata|fielddata)$/is && length($criteria) && $USER{_id}){
		#get a list of records that were updated
		for(my $x=0;$x<$pcnt;$x++){
			my $id=$plist{$x}{_id};
			foreach my $field (keys(%sets)){
				next if $field=~/^\_/s;
				next if $tablename=~/^\_users$/is && $field=~/^(guid)$/is;
				my $val=$sets{$field};
				if($tablename=~/^\_users$/is && $field=~/^password$/is){
					my $pcnt=length($val);
					$val="*"x$pcnt;
	                    }
				next if !length($Ftype{$field});
				#has it changed?
				my $oldval=encodeCRC($plist{$x}{$field});
				my $curval=encodeCRC($val);
				next if $oldval==$curval;
				my $ctime=localtime();
				my $user = $USER{username} || "unknown";
				my $note = "Record $id was changed by $user on $ctime from " . length($plist{$x}{$field}) . " chars to " . length($val) . " chars.";
				my $cdate=getDate("YYYY-NM-ND MH:MM:SS");
				my $new=addDBData("_history",_cdate=>$cdate,tablename=>$tablename,fieldname=>$field,recid=>$id,fieldvalue=>$val,note=>$note);
				if(!isNum($new)){abort("Add History Error:<br>$new");}
				}
	          }
    		}
	if(wantarray){return (1,$query);}
	return 1;
	}
###########
sub checkSQL{
	my $sql=shift;
	$sql=strip($sql);
	if(length($sql)==0){return "No SQL passed in<br>\n";}
    my $sth;
    if($sql=~/^select\ /is){$sql = "explain " . $sql;}
    elsif($sql=~/^explain\ /is){}
    else{return "Syntax check only works on select statements";}
	$sth = $dbh->do($sql) || return $DBI::errstr;
	return 1;
	}
###############
sub executeSQL{
	#internal usage: $ck=executeSQL($sql);
	#internal info:  executes the SQL statement passed in.
	$DBI::query='';
	my $query=shift || return "No SQL passed in to execute";
	my $sth;
	$DBI::query=$query;
	#Prepare and Execute query
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return $DBI::errstr;
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
##################
sub exportDB{
	#internal usage: exportDB(\%tableinfo,$outfile);
	#internal info: exports database to said outfile based on params in %tableinfo
	my $tableinfo=shift;
	my $outfile=shift || "$progpath/wasqldump.xml";
	#tables
	my @tables=keys(%{$tableinfo});
	my $tcnt=@tables;
	my $all=0;
	if($tcnt==0){
		$all++;
		@tables=getDBTables();
		}
	@tables=sort(@tables);
	$tcnt=@tables;
	#print OF "exporting to $outfile\n";
	#print "tables: [@tables]\n";
	open(OF,">$outfile") || abort("Error: cannot open/create $outfile. Perhaps you do not have permission to write files to $progpath.");
	binmode(OF);
	#iterate through each table and build the xml for it
	foreach my $table (@tables){
		#print OF "\########## $table ##########\n";
		if($all || $tableinfo->{$table}{schema}){
			#print OF "\# $table Schema\n";
			my $xml = exportTable($table,"schema");
			#abort("Exporting schema<hr>$xml");
			if(length($xml)){
				print OF $xml;
				print OF "\r\n\r\n";
				}
			}
		if($all || $tableinfo->{$table}{meta}){
			#print OF "\# $table Meta\n";
			my $xml = exportTable($table,"meta");
			if(length($xml)){
				print OF $xml;
				print OF "\r\n\r\n";
				}
			}
		next if $table=~/^\_(fielddata|tabledata)$/is;
		if($all || $tableinfo->{$table}{data}){
			#print OF "\# $table Data\n";
			my $xml = exportTable($table,"data");
			if(length($xml)){
				print OF $xml;
				print OF "\r\n\r\n";
				}
			}
		print OF "\r\n\r\n";
		}
	close(OF);
	return 1;
	}
##################
sub exportCSV{
	#internal usage: exportCSV(\%params,$outfile);
	my $hash=shift;
	my $csvfile=shift || "$progpath/" . time() . "\.csv";
	my @fields=@{$hash->{fields}};
	my $fcnt=@fields;
	if(!$fcnt){return "No fields to export to csv";}
	my $cnt=0;
	if($hash->{count}){$cnt=$hash->{count};}
	else{
		foreach my $key (keys(%{$hash})){
			if(isNum($key)){$cnt++;}
			}
		}
	if($cnt==0){
		if($DBI::errstr){return $DBI::errstr;}
		return "Hash count is empty";
		}
	open(CSV,">$csvfile") || return "exportCSV error: $^E";
	#return "HERE [$cnt]: @fields";
	#print fields as first line in csv file
	print CSV join(",",@fields) . "\n";
	for(my $x=0;$x<$cnt;$x++){
		my @vals=();
		foreach my $field (@fields){
			my $val=strip($hash->{$x}{$field});
			if(length($val)>0){
				$val=~s/\n/\\n/sg;
				$val=~s/\r/\\r/sg;
				$val=~s/\"/\"\"/g;
				if($val=~/\,/is){$val=qq|"$val"|;}
				}
			else{$val='';}
			push(@vals,$val);
			}
		print CSV join(",",@vals) . "\n";
		}
	close(CSV);
	my $ck=&pushFile($csvfile);
	unlink $csvfile;
	if($ck==1){exit;}
	&printHeader();
	print $ck;
	}
##################
sub exportTable{
	#internal usage: my $xml = exportTable($table,"schema");
	#internal info: exports table to xml. second parameter can be schema,meta, or data
	my $table=shift || return "No Table in exportTable";
	my $etype=shift || "all";
	my $xml;

	#Get table fields
	my @fields=getDBFields($table);
	@fields=sort(@fields);
	#build schema
	if($etype=~/^(schema|all)$/is){
		#Get table schema
		my $schema=getTableSchema($table);
		#Replace (5,2) with (5%C%2)
		$schema=~s/\(([0-9])\,([0-9])\)/\(\1\%01\2\)/sig;
		#if($table=~/cart/is){abort($schema);}
		$xml .= qq|<xmlschema name="$table">\n|;
		foreach my $field (@fields){
			my $type;
			#shipping2 real(7,2) Default NULL,
			#item_number varchar(25) Default NULL,
			if($schema=~/$field\ (.+?)[\,\n]/is){$type=strip($1);}
			elsif($schema=~/$field\ (.+)/is){
				$type=strip($1);
				$type=~s/[\)]$//s;
				if($type=~/[\(]/s && $type!~/[\)]/s){$type .= ')';}
				}
			$type=strip($type);
			#print "$field $type<br>\n";
			$type=~s/%01/\,/g;
			$xml .= qq|\t<field name="$field" type="$type">\n|;
			}
		$xml .= qq|</xmlschema>\n|;
		}
	if($etype=~/^schema$/is){return $xml;}
	#META
	if($etype=~/^(meta|all)$/is){
		#Get Data for table and build data xml
		my %clist=();
		#Gather _fielddata info
		my $sql="select * from _fielddata where tablename like '$table' order by _cdate,_id";
		my $cnt=getDBData(\%clist,$sql,"nocount=1");
		my @mfields=@{$clist{fields}};
		#$xml .= qq|# SQL: $sql\nFIELDS:
		for(my $x=0;$x<$cnt;$x++){
			$xml .= qq|<xmlmeta table="_fielddata">\n|;
			foreach my $fld (@mfields){
				next if $fld=~/^\_id$/is;
				my $val=strip($clist{$x}{$fld});
				next if length($val)==0;
				$xml .= qq|\t<$fld>$val</$fld>\n|;
				}
			$xml .= qq|</xmlmeta>\n|;
			}
		#Gather _tabledata info
		$sql="select * from _tabledata where tablename like '$table' order by _cdate,_id";
		$cnt=getDBData(\%clist,$sql,"nocount=1");
		@mfields=@{$clist{fields}};
		for(my $x=0;$x<$cnt;$x++){
			$xml .= qq|<xmlmeta table="_tabledata">\n|;
			foreach my $fld (@mfields){
				next if $fld=~/^\_id$/is;
				my $val=strip($clist{$x}{$fld});
				next if length($val)==0;
				$xml .= qq|\t<$fld>$val</$fld>\n|;
				}
			$xml .= qq|</xmlmeta>\n|;
			}
		}
	#DATA
	if($etype=~/^(data|all)$/is){
		#Get Data for table and build data xml
		my %clist=();
		my $sql="select * from $table order by _cdate,_id";
		my $cnt=getDBData(\%clist,$sql,"nocount=1");
		for(my $x=0;$x<$cnt;$x++){
			$xml .= qq|<xmldata table="$table">\n|;
			foreach my $fld (@fields){
				my $val=strip($clist{$x}{$fld});
				next if length($val)==0;
				$xml .= qq|\t<$fld>$val</$fld>\n|;
				}
			$xml .= qq|</xmldata>\n|;
			}
		}
	return $xml;
	}
####################
sub fixDBTables{
	my $h=getDBTables(1,1);
	my %info=%{$h};
	my $rtn='';
	foreach my $table (keys(%info)){
		my @opts=();
		if(!defined $info{$table}{_id}){
			my ($field,$type)=fixDBFieldType("_id","integer NULL");
			my $ok=addDBColumn($table,$field=>$type);
			if(isNum($ok)){
				if(defined $info{$table}{id}){
					$ok=executeSQL("update $table set _id=id");
					}
				elsif(defined $info{$table}{$table."id"}){
					$ok=executeSQL("update $table set _id=".$table."id");
					}
            	}
			}
		if(!defined $info{$table}{_cdate}){
			my ($field,$type)=fixDBFieldType("_cdate","datetime");
			push(@opts,$field=>$type);
			}
		if(!defined $info{$table}{_edate}){
			my ($field,$type)=fixDBFieldType("_edate","datetime");
			push(@opts,$field=>$type);
			}
		if(!defined $info{$table}{_cuser}){
			my ($field,$type)=fixDBFieldType("_cuser","int");
			push(@opts,$field=>$type);
			}
		if(!defined $info{$table}{_euser}){
			my ($field,$type)=fixDBFieldType("_euser","int");
			push(@opts,$field=>$type);
			}
		if(scalar @opts){
			my $ok=addDBColumn($table,@opts);
			$rtn .= qq|Fix $table\[$ok]: @opts<br>\n|;
        	}
		}
	return $rtn;
	}
####################
sub fixDBFieldType{
	my ($field,$type)=@_;
	if($dbt=~/msaccess/is){
		if ($type=~/auto_increment/i){$type='counter';}
		elsif($type=~/^int/i){$type='integer';}
		elsif($type=~/^date$/i){$type='DateTime';}
		elsif($type=~/^blob/is){$type='OLEObject';}
		elsif($type=~/^text/is){$type='LongChar';}
		$field="[$field]";
		}
	elsif($dbt=~/mssql/is){
		if($type=~/^blob/is){$type='image';}
		elsif($type=~/^date$/is){$type='DateTime';}
		elsif($type=~/^number$/is){$type='numeric';}
		$field="[$field]";
		}
	elsif($dbt=~/^oracle/is){
		if($type=~/^text/is){$type='char(2000)';}
		}
	elsif($dbt=~/mysql/is){
		if($type=~/integer primary key/is){$type='integer auto_increment primary key NOT NULL';}
		elsif($type=~/^currency/is){$type=~s/currency/real\(7\,2\)/s;}
		elsif($type=~/^number/is){$type=~s/number/float\(7\,2\)/s;}
		elsif($type=~/^bit/is){$type='tinyint';}
		}
	elsif($dbt=~/sqlite/is){
		if($type=~/auto_increment/is){$type='integer primary key';}
		elsif($type=~/^int/i && $type!~/primary/i){$type='integer';}
		}
	return ($field,$type);
	}
####################
sub getDBCharsets(){
	my %list=getDBRecords('-sql'=>"show character set");
	my %charsets=();
	for(my $x=0;$x<$list{count};$x++){
		my $set=$list{$x}{charset};
		$charsets{$set}=$list{$x}{description};
    	}
    return %charsets;
	}
####################
sub getDBCharset(){
	#returns current default charset
	my %list=getDBRecords('-sql'=>"SHOW CREATE DATABASE $dbname");
	if($list{count}==1 && $list{0}{'create database'}=~/DEFAULT CHARACTER SET(.+)/i){
		my $charset=strip($1);
		$charset=~s/[\s\*\/]+$//sg;
		return $charset;
    	}
    return "unknown";
	}
####################
#Note Change this to getDBRecordCount for external use
sub getDBCount{
	#usage: my $cnt=getDBCount($table,field=>$value,field2=>$value2);
	#info: returns a single dimensional hash with the values for the record it finds.
	#tags: database
	my $table=shift || return "No Table";
	my %param=@_;
	my $sql=qq|select count(*) as cnt from $table|;
	if($param{-where}){
        $sql .= " where $param{-where}";
    	}
    else{
		my @where=();
		foreach my $fld (keys(%param)){
			if($param{$fld}=~/^NULL$/is){push(@where,"$fld is null");}
			elsif($param{$fld}=~/(not|null|is|like|\=)/is){
				push(@where,"$fld $param{$fld}");
				}
			elsif($fld=~/\_id$/is){push(@where,"$fld in ($param{$fld})");}
			else{push(@where,"$fld like '$param{$fld}'");}
			}
		my $wcnt=@where;
		if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
		}
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){
		return $cnt;
		}
	if(wantarray){return ($list{0}{cnt},$list{sql});}
	return $list{0}{cnt};
	}
###############
sub getDBData {
	#usage: my $cnt=getDBData(\%list,$query[,"nocount=1;limit=5;offset=10"]);
	#info: returns a md hash with query results $list{$x}{$field}=$value
	#info: Specials returns in list hash: $list{fields}=column name array, list{count}, list{tcount}
	#info: @fieldcols=@{$list{fields}};
	#tags: database
	$DBI::query='';
    my $h=shift || return "getDBData Error: No Hash Reference";
	my $query=shift || return "getDBData Error: No Query";
	my $paramstr=shift;
	#Allow user to pass in total count query (for joins, etc)
	my $tsql=shift;
	my %params=();
	if($paramstr){
		$paramstr=strip($paramstr);
		@parts=split(/\;/,$paramstr);
		foreach my $part (@parts){
			my ($par,$val)=split(/\=/,$part);
			$par=strip($par);
			$val=strip($val);
			next if !$val;
			$params{$par}=$val;
			}
		}

	#initialize
	$DBI::query='';
	my @colnames=();
	my $dbcnt=0;
	my $sth;
	my $rcount=0;my $count=0;
	my $tcount=0;my $offset=0;
	$query=strip($query);
	return "getDBData Error: No Query" if !$query;
	my $tquery;
	$tcount=1;
	#Get Tcount
	#Get total count
    if(length($tsql)){
		$h->{tsql}=$tsql;
		my $tsth;
		if($tsth = $dbh->prepare($tsql)){
			$tsth->execute();
			$tcount = $tsth->fetchrow_array;
			$tsth->finish;
			}
		else{$tcount=1;}
		undef $tsth;
		}
	elsif($params{nocount}!=1 && $dbt=~/msaccess|mssql/is && $query=~/^select top(.+?)\ \* from/is){
		$tquery=$query;
		$tquery=~s/^(select top(.+?)\ \* from)/select count\(\*\) from/is;
		$tquery=~s/order\ by(.*)$//is;
		my $tsth;
		if($tsth = $dbh->prepare($tquery)){
			$tsth->execute();
			$tcount = $tsth->fetchrow_array;
			$tsth->finish;
			}
		else{$tcount=1;}
		undef $tsth;
		}
	elsif($params{nocount}!=1 && $query=~/^select\ (.+?)\ from\ (\w+)(.*)/is){
		my $fstr=$1;my $tstr=$2;my $endstr=$3;
		$tquery=$query;
		if($fstr=~/distinct\((.+?)\)/is){
			my $nfld=$1;
			my $nstr=qq|count(distinct($nfld))|;
			$tquery=~s/\Q$fstr\E/$nstr/;
			}
		elsif($params{table}){$tquery=~s/\Q$fstr\E/count\($params{table}\._id\)/;}
		else{$tquery=~s/\Q$fstr\E/count\(_id\)/;}
		$tquery=~s/\ limit\ (.+)$//is;
		$tquery=~s/order\ by(.*)$//is;
		my $tsth;
		#print "Tquery:$tquery\n";
		if($tsth = $dbh->prepare($tquery)){
			$tsth->execute();
			$tcount = $tsth->fetchrow_array;
			$tsth->finish;
			}
		else{$tcount=1;}
		undef $tsth;
		}
	#Set limit and offset if possible;
	my $page_support=0;
	if(isNum($params{limit}) and $query=~/^select/i){
		if(!isNum($params{limit}) || $params{limit} < 0){return "getDBData Error: Limit must be a positive integer value";}

		$offset=$params{offset} if $params{offset};
		#Mysql
		if($dbt=~/(mysql|sqlite)/i){
			my $newlimit=qq| limit $offset,$params{limit}|;
			if($query=~/\ limit(.+)/is){
				my $str=$&;
				$query=~s/\Q$str\E/$newlimit/sig;
				}
			else{$query .= $newlimit;}
			$page_support=1;
			}
		#Postgresql
		elsif($dbt=~/postgresql/i && $query!~/\ limit\ /is){
			$query .= qq| limit $params{limit},$offset|;
			$page_support=1;
			}
		#Access and MS SQL
		elsif($dbt=~/msaccess|mssql/i){
			my $top=$offset+$params{limit};
			if($query=~/^select\ TOP(.+?)\ /is){
				my $str=$&;
				$query=~s/\Q$str\E/select TOP $top\ /i;
				}
			else{$query=~s/select\ /select TOP $top\ /i;}
			}
		}
     if($dbt=~/^sqlite/i && $query=~/date\_format/is){
		$query=~s/date\_format\((.+?)\,(.+?)\)/strftime\(\2\,\1\)/sig;
	     }
	# set Query
	#print "GD:offset:$params{offset}\n\t$query\n";
	$DBI::query=$query;

	#print "$query\n----------------\n";
	#Prepare and execute Query
	#$dbh->{LongReadLen} = 32768;
	$sth = $dbh->prepare($query);
	#$DBI::debug="prepared;";
	if($DBI::err){if($sth){$sth->finish;undef $sth;}return "getDBData Error: Prepare Failed<br>$query<br>$DBI::errstr";}
	$sth->execute() || return "getDBData Error: Execute Failed<br>$query<br>$DBI::errstr";
	#$DBI::debug .= "executed;";
	if($tcount==0 && $sth->rows >0){$tcount=$sth->rows;}
	#$DBI::debug .= "tcount=0;";
	#Build an array of column names
	my $fields = $sth->{NUM_OF_FIELDS};
	#$DBI::debug .= "fields[$fields];";
	for ( my $i = 0 ; $i < $fields ; $i++ ) {
		my $name = lc($sth->{NAME}->[$i]);
		$name=strip($name);
		next if !$name;
		push(@colnames,$name);
		}
	# If database does not support offset, move sth to desired location by popping off until you reach offset
	if ($params{limit} && $offset>0 && $page_support==0){
		my $tempcnt=0;
		while ($tempcnt < $offset){
			$sth->fetchrow_array;
			$tempcnt++;
			}
		}
	# Populate params hash with query data
	my $continue = 1;
	my $limit=0;
	$limit=$params{limit} if $params{limit};
	$h->{limit}=$limit;
    $h->{offset}=$offset;
	#$DBI::debug .= "continue[$continue];";
	while (($continue eq 1) && ($row = $sth->fetchrow_arrayref)) {
		if (($limit != 0) && ($dbcnt >= $limit-1)) {$continue=0};
		my $counter=0;
		#$DBI::debug .= "cnt[$counter];";
		foreach my $field (@colnames){
			my $val=strip($row->[$counter]);
			$val=~s/[^a-z0-9\_\.\!\@\#\$\%\^\&\*\(\)\+\-\=\`\~\[\]\{\}\;\'\:\"\,\.\/\<\>\?]+$//is;
			if(length($val)){$h->{$dbcnt}{$field} = $val;}
			$counter++;
			}
		$dbcnt++;
		}          
	#Close sth
	if($sth){$sth->finish;undef $sth;}
	#Set final params variables
	$h->{'fields'}=[@colnames];
	$h->{count}=$dbcnt;
	$h->{tcount}=$tcount;
	$h->{sql}=$query;
	return $dbcnt;
	}
############
sub getDBFieldInfo{
	#internal usage: $hash=getDBFieldInfo($table);
	#internal info: returns $hash->{$field} with attributes of type,precision,scale,nullable, and  sql
	my $table=shift;
	my $query = "SELECT * FROM $table where 1=0";
	#Prepare and Execute query
	my $sth = $dbh->prepare($query);
	#$sth->trace('ALL',STDOUT);
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("Error in getDBFieldTypes:<br>\n$query<br>\n$DBI::errstr");}
	$sth->execute;
	#Create a type map
	my %TypeMap=();
	foreach $name (@{ $DBI::EXPORT_TAGS{sql_types} }) {
		my $val=&{"DBI::$name"};
		$TypeMap{$val}=$name;
  		}
  	#override a few
  	$TypeMap{-4}='TEXT';
  	$TypeMap{-5}='BIGINT';
  	$TypeMap{11}='DATETIME';

	my $hash=();
	my $count = $sth->{NUM_OF_FIELDS};
	for (my $i = 0 ; $i < $count ; $i++ ) {
		my $field = lc($sth->{NAME}->[$i]);
		$hash->{$field}{name}=$sth->{NAME}->[$i];
		my $typeid=$sth->{TYPE}->[$i];
		$hash->{$field}{typeid}=$typeid;
		my $type=$TypeMap{$typeid} || "unknown";
		$type=~s/^SQL\_//is;
        $hash->{$field}{type}=$type;
		$hash->{$field}{precision}=$sth->{PRECISION}->[$i];
        $hash->{$field}{scale}=$sth->{SCALE}->[$i];
        $hash->{$field}{nullable}=$sth->{NULLABLE}->[$i];
        $hash->{$field}{sql}=$hash->{$field}{name} . " $type";
		if($hash->{$field}{precision}){$hash->{$field}{sql} .= "(" . $hash->{$field}{precision} . ")";}
		if($hash->{$field}{nullable}){$hash->{$field}{sql} .= " DEFAULT NULL";}
		}
	if($sth){$sth->finish;undef $sth;}
	return $hash;
	}
###############
sub getDBFields{
	#usage: getDBFields($table[,1]);
	#info: returns field names of $table, if second parameter is 1, then only returns the user defined fields
	#tags: database
	my $table=shift || return;
	my $userfieldsonly=shift;
	my %list=();
	my $cnt=getDBData(\%list,"select * from $table where 1=0","nocount=1");
	my @fields=@{$list{fields}};
	if($userfieldsonly){
		my @ufields=();
		foreach my $field (@fields){
			next if $field=~/^\_/s;
			push(@ufields,$field);
			}
		return @ufields;
		}
	return @fields;
	}
###############
sub createDBIndex{
	#Syntax:
	# - CREATE [UNIQUE] INDEX index-name ON table-name ( column-name [, column-name]* )
	# SQLite automatically creates an index for every UNIQUE column, including PRIMARY KEY columns, in a CREATE TABLE statement
	# A UNIQUE index creates a constraint such that all values in the index must be distinct.
	# Index values are always stored in ascending order. Create indexes on columns by which you often search
	# Indexes are best created on columns with a high degree of selectivity—that is, columns or combinations of columns in which the majority of the data is unique.
	#Create indexes on frequently searched columns, such as:
	# - Primary keys
	# - Foreign keys or other columns that are used frequently in joining tables
	# - Columns that are searched for ranges of key values
	# - Columns that are accessed in sorted order
	#Do not index columns that:
	# - You seldom reference in a query.
	# - Contain few unique values. For example, an index on a column with only two values, such as male and female, returns a high percentage of rows and is not beneficial.
	# - Are defined with bit, text, and image data types.
	my %params=@_;
	$DBI::query='';
	my $query=qq|CREATE INDEX|;
	if($params{unique}){$query =qq|CREATE UNIQUE INDEX|;}
	$query .= qq| $params{name} on $params{table} ($params{cols})|;
	my $sth;
	$DBI::query=$query;
	#Prepare and Execute query
	$sth = $dbh->prepare($query) || return $DBI::errstr;
	$sth->execute || return $DBI::errstr;
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
###############
sub dropDBIndex{
	#SQLite :: DROP INDEX index-name
	#Mysql :: DROP INDEX index_name ON tbl_name
	return 1;
	}
###############
sub getDBIndex{
	#internal usage: my $cnt=getDBIndex(\%hash,$table);
	#internal info: returns indexes for $table. Only works in mysql and sqlite.
	my $hash=shift || return "no hash";
	my $table=shift || return "no table";
	if($dbt=~/^mysql/is){
		my $cnt=getDBData($hash,"show index from $table");
		return $cnt;
		}
	elsif($dbt=~/^sqlite/is){
		#select name,tbl_name as table,rootpage from sqlite_master where tbl_name like '_pages' and type like 'index'
		my $sql=qq|select * from sqlite_master where tbl_name like '$table' and type like 'index'|;
		#abort($cnt,$sql);
		my $cnt=getDBData($hash,$sql,"nocount=1");
		if(!isNum($cnt)){return "Error<br>$cnt<hr>$sql";}
		return $cnt;
		}
	return 0;	
	}
###############
sub getDBMeta{
	#internal info: returns a hash of meta information for said table
	#meta{formfields|listfields}
	#$meta{$field}{required|max|filter|tvals|dvals}
	my $meta=shift;
	my $table=shift || return "No Table in getDBMeta";
	%{$meta}=();
	#Table Meta
	my %list=();
	my $sql=qq|select formfields,listfields from _tabledata where tablename like '$table'|;
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	#print "[$cnt] $sql\n";
	if(!isNum($cnt)){return $cnt;}
	$meta->{formfields}=$list{0}{formfields};
	$meta->{listfields}=$list{0}{listfields};
	#Field Meta
	$sql="select * from _fielddata where tablename like '$table'";
	$cnt=getDBData(\%list,$sql,"nocount=1;");
	#print "[$cnt] $sql\n";
	my @fields=@{$list{fields}};
	for(my $x=0;$x<$cnt;$x++){
		my $name=$list{$x}{fieldname} || next;
		foreach my $field (@fields){
			next if $field=~/^(fieldname|tablename)$/is;
			next if $field=~/^\_/is;
			my $val=strip($list{$x}{$field});
			next if !$val;
			$meta->{$name}{$field}=$val;
			#print "meta->{$name}{$field}=$meta->{$name}{$field}\n";
			}
		}
	return 1;
	}
###############
sub getDBDval{
	#internal usage: getDBDval($table,$field,$tval);
	#internal info: returns a display value of a true value given the table and field
	my $table=shift || return "Error: no table";
	my $field=shift || return "Error: no field";
	my $value=shift;
	if(!length($value)){return "Error: no tval";}
	my $sql="select tvals,dvals from _fielddata where tablename like '$table' and fieldname like '$field'";
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return "Error: $cnt";}
	if($cnt==0){return "Error:No fielddata found for $field in $table: $sql";}
	my @tvals=buildVals($list{0}{tvals});
	my @dvals=buildVals($list{0}{dvals});
	my $tcnt=@tvals;
	my $dcnt=@dvals;
	for(my $x=0;$x<$tcnt;$x++){
		my $tval=$tvals[$x];
		my $dval=$dvals[$x] || $tval;
		if(isNum($value) && isNum($tval) && $value==$tval){return $dval;}
		elsif($value=~/^\Q$tval\E$/is){return $dval;}
		}
	return $value;
	}
###############
sub getDBTval{
	#internal usage: getDBDval($table,$field,$tval);
	#internal info: returns a display value of a true value given the table and field
	my $table=shift || return "Error: no table";
	my $field=shift || return "Error: no field";
	my $value=shift;
	if(!length($value)){return "Error: no dval";}
	my $sql="select tvals,dvals from _fielddata where tablename like '$table' and fieldname like '$field'";
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return "Error: $cnt";}
	if($cnt==0){return "Error:No fielddata found for $field in $table: $sql";}
	my @tvals=buildVals($list{0}{tvals});
	my @dvals=buildVals($list{0}{dvals});
	my $tcnt=@tvals;
	my $dcnt=@dvals;
	#my $debug='';
	for(my $x=0;$x<$tcnt;$x++){
		my $tval=$tvals[$x];
		my $dval=$dvals[$x] || $tval;
		#$debug .= qq|[$tval ~ $dval]<br>\n|;
		if(isNum($value) && isNum($dval) && $value==$dval){return $tval;}
		elsif($value=~/^\Q$dval\E$/is){return $tval;}
		}
	return "Error: No tval found for $value in $table<br>$tcnt Tvals:@tvals<br>\n$dcnt Dvals:@dvals\n";
	}
###############
sub getDBUnique{
	#usage: getDBUnique($table,$field);
	#info: returns a display value of a true value given the table and field
	#tags: database
	my $table=shift || return "Error: no table";
	my $field=shift || return "Error: no field";
	my %params=@_;
	my $sql="select $field from $table where 1=1";
	foreach my $field (keys(%params)){
		next if $field=~/^\-/is;
		my $val=$params{$field};
		if($val=~/\%/s){$sql .= qq| and $field like '$val'|;}
		elsif(isNum($val)){$sql .= qq| and $field=$val|;}
		else{$sql .= qq| and $field='$val'|;}
    	}
    $sql .= qq| group by $field|;
	if($params{-order}){$sql .= " order by $params{-order}";}
	else{$sql .= " order by $field";}
	if($params{-rtnsql}){return $sql;}
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;");
	if($params{-debug}){return "[$cnt] $sql";}
	my @vals=();
	for(my $x=0;$x<$cnt;$x++){
		push(@vals,$list{$x}{$field});
		}
	if(wantarray){return @vals;}
	return scalar @vals;
	}
###############
sub getDBFieldTypes{
	#internal usage: getDBFieldTypes(\%hash,$table);
	#internal info: returns a hash of field information for said table
	my $hash=shift;
	%{$hash}=();
	my $table=shift || return "No Table in getDBFieldTypes";
	#Dabatabe Type Map
	my %TypeMap=(
		-11	=> "UniqueIdentifier",
		-10	=> "Ntext",
		-9	=> "Nvarchar",
		-8	=> "Nchar",
		-7	=> 'Bit',
		-6	=> 'TinyInt',
		-5	=> 'BigInt',
		-4	=> 'Text',
		-3	=> 'Text',
		-2	=> 'Text',
		-1	=> 'LongChar',
		1	=> 'Char',
		2	=> 'Number',
		3	=> 'Decimal',
		4	=> 'Integer',
		5	=> 'Smallint',
		6	=> 'Float',
		7	=> 'Real',
		8	=> 'Number',
		9	=> 'Date',
		10	=> 'Time',
		11	=> 'DateTime',
		12	=> 'Varchar',
		93	=> 'Time',
		);
	# Types that do NOT need to be quoted are -7,-6,-5,3,4,5,6,7,8:  bit,tinyint,bigint,decimal,integer,smallint,float,real,number
	#
	#Build Query
	$query = "SELECT * FROM $table where 1=0";
	$DBI::query=$query;
	#Prepare and Execute query
	$sth = $dbh->prepare($query);
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("Error in getDBFieldTypes:<br>\n$query<br>\n$DBI::errstr");}
	$sth->execute;
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("Error in getDBFieldTypes:<br>\n$query<br>\n$DBI::errstr");}
	my $fields = $sth->{NUM_OF_FIELDS};
	#my @fields=();
	for (my $i = 0 ; $i < $fields ; $i++ ) {
		my $field = $sth->{NAME}->[$i];
		$field=lc($field);
		my $type=$sth->{TYPE}->[$i];
		my $stype=$TypeMap{$type} || $type;
		$stype=strip($stype);
		$hash->{$field}=$stype;
		}
	if($sth){$sth->finish;undef $sth;}
	my @fields=keys(%{$hash});
	return @fields;
	}
####################
sub getRecord{
	#depreciated
	#return (0,"getRecord is depreciated. use getDBRecord instead");
	#my %rec=getDBRecord(-table=>$table,field=>$value,field2=>$value2);
	my $hash=shift;
	my $table=shift;
	my @opts=@_;
	%{$hash}=getDBRecord(-table=>$table,@opts);
	if($hash{-error}){return $hash{-error};}
	return 1;
	}
###############
sub getDBRecord{
	#usage: my %rec=getDBRecord(-table=>$table,field=>$value,field2=>$value2);
	#info: returns a single dimensional hash with the values for the record it finds.  getRecord
	#tags: database
	my %params=@_;
	#initialize return hash
	my %hash=();
	#check for table
	if(!$params{-table}){
		$hash{-error}="No Table";
		return %hash;
    	}
    #Get fieldTypes for the table
	my %Ftype=();
	getDBFieldTypes(\%Ftype,$params{-table});
	#Build the fieldlist to return
	my $fieldstr='*';
	if($dbt=~/mysql/is){
		foreach my $fld (keys(%Ftype)){
			if($Ftype{$fld}=~/^(date|time)/is){$fieldstr .= qq|,UNIX_TIMESTAMP($fld) as $fld\_utime|;}
    		}
		}
	my $sql=qq|select $fieldstr from $params{-table}|;
	if($params{-where}){
	    $sql .= " where $params{-where}";
	    }
	elsif($params{-search}){
        my @where=();
        my $val=$params{-search};
		foreach my $fld (keys(%Ftype)){
			#skip internal fields
			next if $fld=~/^\_/is;
			if($Ftype{$fld}=~/^int/is && (isNum($val) || ($val=~/[\:\,]/is && $val!~/[a-z\_]/is))){
				$val=~s/\:/\,/sg;
				push(@where,"$fld in ($val)");
				}
			else{push(@where,"$fld like '\%$val\%'");}
			}
		my $wcnt=@where;
		if($wcnt){$sql .= " where (" . join(" or ",@where) . ")";}
    	}
    else{
		my @where=();
		foreach my $fld (keys(%params)){
			#skip keys that begin with -
			next if $fld=~/^\-/is;
			my $val=$params{$fld};
			if($val=~/^NULL$/is){push(@where,"$fld is null");}
			elsif($fld=~/\_id$/is){
				$val=~s/\:/\,/sg;
				push(@where,"$fld in ($val)");
				}
			else{push(@where,"$fld like '$val'");}
			}
		my $wcnt=@where;
		if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
		}
	#query the database
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	$hash{-sql}=$sql;
	if(!isNum($cnt)){
		$hash{-error}=$cnt;
		return %hash;
		}
	if($cnt==0){
		$hash{-error}="No record found";
		return %hash;
		}
	if($cnt>1){
		$hash{-error}="Multiple records found";
		return %hash;
		}
	my @fields=@{$list{fields}};
	$hash{'-fields'}=[@fields];
	foreach my $field (@fields){
		my $val=strip($list{0}{$field});
		next if !length($val);
		$hash{$field}=$val;
	    }
	return %hash;
	}
####################
sub getDBRecords{
	#usage: my %recs=getDBRecords(-table=>$table,field=>$value,field2=>$value2);
	#info: returns a multi dimensional hash with the values for the records it finds.
	#info: special params are -table, -order, -limit, -offset, and -sql
	#tags: database
	my @inopts=@_;
	my %params=@inopts;
	#initialize return hash
	my %hash=();
    my $sql='';
    if($params{-sql}){$sql=$params{-sql};}
    elsif($params{-query}){$sql=$params{-query};}
    else{
		#check for table
		if(!$params{-table}){
			$hash{-error}="No Table";
			return %hash;
	    	}
	    #Get fieldTypes for the table
		my %Ftype=();
		getDBFieldTypes(\%Ftype,$params{-table});
		#Build the fieldlist to return
		my $fieldstr='*';
		if($dbt=~/mysql/is){
			foreach my $fld (keys(%Ftype)){
				if($Ftype{$fld}=~/^(date|time)/is){$fieldstr .= qq|,UNIX_TIMESTAMP($fld) as $fld\_utime|;}
	    		}
			}
		$sql=qq|select $fieldstr from $params{-table}|;
		if($params{-where}){
	        $sql .= " where $params{-where}";
	    	}
	    elsif($params{-search}){
	        my @where=();
	        my $val=$params{-search};
			foreach my $fld (keys(%Ftype)){
				#skip internal fields
				next if $fld=~/^\_/is;
				if($Ftype{$fld}=~/^int/is && (isNum($val) || ($val=~/[\:\,]/is && $val!~/[a-z\_]/is))){
					$val=~s/\:/\,/sg;
					push(@where,"$fld in ($val)");
					}
				else{push(@where,"$fld like '\%$val\%'");}
				}
			my $wcnt=@where;
			if($wcnt){$sql .= " where (" . join(" or ",@where) . ")";}
	    	}
	    else{
			my @where=();
			foreach my $fld (keys(%params)){
				#skip special keys that begin with a dash
				next if $fld=~/^\-/is;
				my $val=$params{$fld};
				#Special Cases: [>|<]7=[greater|less than] 7, ~7,6,5=in (7,6,5), ^=not(...)
				my $start='';
				my $end='';
				if($val=~/^\^/s){
					$start="not(";
					$end=")";
					$val=~s/\^//s;
					}
				if($val=~/^NULL$/is){push(@where,"$start$fld is null$end");}
				elsif($val=~/^([\<\>])(.+)/is){
					my $op=$1;
					$val=strip($2);
					if(isNum($val)){push(@where,"$start$fld $op $val$end");}
					else{push(@where,"$start$fld $op '$val'$end");}
                	}
				elsif($Ftype{$fld}=~/^int/is && (isNum($val) || ($val=~/[\:\,]/is && $val!~/[a-z\_]/is))){
					$val=~s/\:/\,/sg;
					push(@where,"$start$fld in ($val)$end");
					}
				else{push(@where,"$start$fld like '$val'$end");}
				}
			my $wcnt=@where;
			if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
			}
		#order by?
		if($params{-order}){$sql .= qq| order by $params{-order}|;}
		}
	my @opts=();
	#limit and/or offset ?
	if($params{-limit}){push(@opts,"limit=$params{-limit}");}
	if($params{-offset}){push(@opts,"offset=$params{-offset}");}
	my $optstr=join(';',@opts);
	#query the database
	my $cnt=getDBData(\%hash,$sql,$optstr);
	if(!isNum($cnt)){
		$hash{-error}=$cnt;
		}
	return %hash;
	}
###############
sub getDBResults{
	#internal usage: $ck=getDBResults($sql);
	#internal info:  executes the SQL statement passed in and returns the result in an HTML table format .
	my $sql=shift || return;
	my %mlist=();
	my $result;
	my $mcnt=getDBData(\%mlist,$sql,"nocount=1");
	my @cols=@{$mlist{fields}};
	$result .= qq|<table border="0" bgcolor="#000000" cellspacing="1" cellpadding="2">\n|;
	$result .= qq|<tr style="font-size:11px;" bgcolor="#C0C0C0">\n|;
	foreach my $col (@cols){
		my $ucol=ucfirst($col);
		$result .= qq|<td>$ucol</td>\n|;
		}
	$result .= qq|</tr>\n|;
	for(my $x=0;$x<$mcnt;$x++){
		$result .= qq|<tr style="font-size:11px;" bgcolor="#FFFFFF">\n|;
		foreach my $col (@cols){
			$result .= qq|<td>$mlist{$x}{$col}</td>\n|;
			}
		$result .= qq|</tr>\n|;
		}
	$result .= qq|</table>\n|;
	return $result;
	}
###########
sub getTableSchema{
	#internal usage: my $schema=getTableSchema($table[,1]);
	#internal info: returns schema for $table. if second param is 1, then it only returns schema for user defiend fields
	my $table=shift ||  return;
	my $custonly=shift;
	my %mlist=();
	my @schema=();
	my @fields=();
	if($dbt=~/sqlite/is){
		my $mcnt=getDBData(\%mlist,"select sql from sqlite_master where type like 'table' and name like '$table'","nocount=1");
		my $schema=$mlist{0}{sql};
		$schema=~s/\(([0-9])\,([0-9])\)/\(\1\%01\2\)/g;
		$schema=~s/\,/\n/sg;
		$schema=~s/\(/\n/s;
  		$schema=~s/^create table $table//is;
		$schema=~s/\)$//s;
		$schema=strip($schema);
		my @lines=split(/[\r\n]+/,$schema);
		foreach my $line (@lines){
			$line=strip($line);
			#next if $line=~/^\_/s;
			$line=~s/%01/\,/g;
			next if $line=~/^create table/s;
			$line=~/(.+?)\ (.+)/is;
			my $field=lc(strip($1));
			my $type=strip($2);
			if($custonly && $field=~/^\_/s){next;}
			push(@fields,$field=>$type);
			push(@schema,"$field $type");
			}

		%mlist=();
		if(wantarray){return @fields;}
		return join("\n",@schema);
		}
	elsif($dbt=~/mysql/is){
		#build an array that looks like sample=>varchar(255) Default NULL Unique
		my %mlist=getDBRecords(-sql=>"desc $table");
        for(my $x=0;$x<$mlist{count};$x++){
			my $field=$mlist{$x}{field};
			next if $custonly && $field=~/^\_/s;
			my $typestring=$mlist{$x}{type};
			if(length($mlist{$x}{null})){
				if($mlist{$x}{null}=~/yes/is){$typestring .= qq| NULL|;}
				else{$typestring .= qq| NOT NULL|;}
            	}
            if(length($mlist{$x}{default})){
				my $default=$mlist{$x}{default};
				if(!isNum($default)){$default=qq|'$default'|;}
				$typestring .= qq| Default $default|;
				}
			if(length($mlist{$x}{key})){
				if($mlist{$x}{key}=~/uni/is){$typestring .= qq| UNIQUE|;}
				if($mlist{$x}{key}=~/pri/is){$typestring .= qq| Primary Key|;}
            	}
            if(length($mlist{$x}{extra})){$typestring .= qq| $mlist{$x}{extra}|;}
            push(@fields,$field=>$typestring);
			push(@schema,"$field $typestring");
        	}
        #sort the fields the the returning hash is in order
		%mlist=@fields;
		my @keys=keys(%mlist);
		@keys=sortTextArray(@keys);
		@fields=();
		foreach my $key (@keys){push(@fields,$key=>$mlist{$key});}
		if(wantarray){return @fields;}
		@schema=sortTextArray(@schema);
		return join("\n",@schema);
		}
	return;
	}
###############
sub getDBTables {
	#usage: my @tables=getDBTables([$type]) or $hash=getDBTables(2,1);
	#info: returns an array of valid table names
	#info: if $type=1, return user defined tables only
	#info: if $type=2, return internal tables (starting with a _)
	#info: if $field=1, include field info for each table
	#tags: database
	my $type=shift;
	my $fieldinfo=shift;
	#info: returns and array of tables in the database
	my $sth = $dbh->table_info(undef,undef,undef,"TABLE") || return $DBI::errstr;
	### Iterate through all the tables...
	my %Table=();
	while ( my ($qual,$owner,$name,$tabletype) = $sth->fetchrow_array() ) {
		my $lcname=lc($name);
		#type?
		if(isNum($type)){
			if($type==1 && $lcname=~/^\_/s){next;}
			elsif($type==2 && $lcname!~/^\_/s){next;}
			}
		$Table{$lcname}=1;
		if($fieldinfo){$Table{$lcname}=getDBFieldInfo($lcname);}
		}
	if($sth){$sth->finish;undef $sth;}
	my @tables=keys(%Table);
	if(wantarray){return @tables;}
	return \%Table;
	}
####################
sub getIds{
	#internal usage: @ids=getIds($table,field=>$value);
	#internal info: returns either a comma separated string or an array with the ids of $table filtered by fields passed in.
	my $table=shift;
	my %param=@_;
	my $sql=qq|select _id from $table|;
	my @where=();
	foreach my $fld (keys(%param)){
		if($fld=~/\_id$/is){push(@where,"$fld in ($param{$fld})");}
		else{push(@where,"$fld like '$param{$fld}'");}
		}
	my $wcnt=@where;
	if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
	$sql .= " order by _id";
	my %list=();
	my $cnt=getDBData(\%list,$sql);
	my @idlist=();
	for(my $x=0;$x<$cnt;$x++){
		my $id=$list{$x}{_id};
		push(@idlist,$id);
		}
	my $idstr=join(',',@idlist);
	$rtn .= qq|\n<div style="display:none" sub="getIds" table="$table" result="$idstr">$sql</div>\n|;
	if(wantarray){return @idlist;}
	return $idstr;
	}
####################
sub getDBFieldValue{
	#internal usage: my $ok=getDBFieldValue($table,$field,field=>$value,field2=>$value2);
	#internal info: returns a single value for the record it finds.
	my $table=shift || return "No Table";
	my $field=shift || return "No Field";
	my %param=@_;
	my $sql=qq|select $field from $table|;
	my @where=();
	foreach my $fld (keys(%param)){
		if($fld=~/\_id$/is){push(@where,"$fld in ($param{$fld})");}
		else{push(@where,"$fld like '$param{$fld}'");}
		}
	my $wcnt=@where;
	if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return $cnt;}
	if($cnt==0){return (0,0,$sql);}
	if($cnt>1){return "Multiple records found:[$cnt] $sql";}
	return $list{0}{$field};
	}
####################
sub getDBNextId{
	#internal usage: my $ok=getDBNextId($table);
	#internal info: returns the next _id in a table.
	my $table=shift || return "No Table";
	my %param=@_;
	my $sql=qq|select MAX(_id) as lastid from $table|;
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return $cnt;}
	my $nextid=$list{0}{$field} || 1;
	return $nextid;
	}
####################
sub getDBSize{
	#internal usage: my $size=getDBSize($table,$field,field=>$value,field2=>$value2);
	#internal info: returns a single value for the record it finds.
	my $table=shift || return "No Table";
	my $field=shift || return "No Field";
	my %param=@_;
	my $sql=qq|select sum(length($field)) as len from $table|;
	my @where=();
	foreach my $fld (keys(%param)){
		if($fld=~/\_id$/is){push(@where,"$fld in ($param{$fld})");}
		else{push(@where,"$fld like '$param{$fld}'");}
		}
	my $wcnt=@where;
	if($wcnt){$sql .= " where (" . join(" and ",@where) . ")";}
	my %list=();
	my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
	if(!isNum($cnt)){return $cnt;}
	if($cnt==0){return (0,0,$sql);}
	if($cnt>1){return "Multiple records found:[$cnt] $sql";}
	if(wantarray){return ($list{0}{len},$sql);}
	return $list{0}{len};
	}
##############
sub getUserByGuid{
	#usage: my $username=getUserByGuid($guid,$field);  or my ($username,$name)=getUserByGuid($guid,"username","name");
	#info: returns value of $field for user with a guid of $guid
	#tags: database
	my $guid=shift || return '';
	my @flds=@_;
	my $fcnt=@flds;
	if(!$fcnt){push(@flds,"username");}
	my $fieldstr=join(',',@flds);
	my %list=();
    my $sql=qq|select $fieldstr from _users where guid=$guid|;
    my $cnt=getDBData(\%list,$sql,"nocount=1");
    if(!isNum($cnt)){return $cnt;}
    my @tmp=();
    foreach my $fld (@flds){
		push(@tmp,$list{0}{$fld});
		}
	if(wantarray){return @tmp;}
    return $tmp[0];
	}
##############
sub getUserById{
	#usage: my $username=getUserById($id,$field);  or my ($username,$name)=getUserById($id,"username","name");
	#info: returns value of $field for user with _id of $id
	#tags: database
	my $id=shift || return '';
	my @flds=@_;
	my $fcnt=@flds;
	if(!$fcnt){push(@flds,"username");}
	my $fieldstr=join(',',@flds);
	my %list=();
     my $sql=qq|select $fieldstr from _users where _id=$id|;
     my $cnt=getDBData(\%list,$sql,"nocount=1");
     if(!isNum($cnt)){return $cnt;}
     my @tmp=();
     foreach my $fld (@flds){
		push(@tmp,$list{0}{$fld});
		}
	if(wantarray){return @tmp;}
     return $tmp[0];
	}
################
sub graphSQL{
	#usage: $rtn .= graphSQL(-sql=>$sql,-graph=>"Column2D",...)
	#info: create the html for FusionChart graphs included with WaSQL installation.
	#tags: graph charts
	#example:
	# my $sql=qq|select count(_id) as cnt,DAYNAME(ci_when) as day from checkins where not(whoid=3) group by TO_DAYS(ci_when) order by ci_when desc limit 7|;
    # $rtn .= graphSQL(
	#	-sql=>$sql,
	#	-graph=>"Column2D",
	#	-xfield=>"day",
	#	-xtitle=>"Day of Week",
	#	-yfield=>"cnt",
	#	-ytitle=>"Number of Checkins",
	#	-reverse=>1,
	#	-caption=>"CVS Checkins Last 7 Days",
	#	-subcaption=>"By Date",
	#	);
	my %params=@_;
	my $sql=$params{-sql};
	my $graph=$params{-graph};
	my $caption=$params{-caption} || "";
	my $subcaption=$params{-subcaption} || "";
	my %list=getDBRecords(-sql=>$sql);
	my $crc=encodeCRC($sql);
	my $sid="swf_" . $crc;
	my $gid="graph_" . $crc;
	my $datafile="data_" . $crc . ".xml";
	my %list=getDBRecords(-sql=>$sql);
	my @fields=@{$list{fields}};
	my $valfield=$params{-yfield} || shift(@fields);
	my $namefield=$params{-xfield} || shift(@fields);
	my $xaxis=$params{-xtitle} || ucfirst($namefield);
	my $yaxis=$params{-ytitle} || ucfirst($valfield);
	my $rtn='';
	#graph div
	$rtn .= qq|<div id="$gid"></div>\n|;
	#dataset
	my $xml = qq|<graph caption='$caption' subcaption='$subcaption' xAxisName='$xaxis' yAxisName='$yaxis' decimalPrecision='0'>\n|;
	if(isNum($params{-reverse}) && $params{-reverse}==1){
		for(my $x=$list{count}-1;$x>= 0;$x--){
			my $name=$list{$x}{$namefield};
			my $val=$list{$x}{$valfield};
			$xml .= qq|	<set name='$name' value='$val' />\n|;
	        }
    	}
	else{
		for(my $x=0;$x<$list{count};$x++){
			my $name=$list{$x}{$namefield};
			my $val=$list{$x}{$valfield};
			$xml .= qq|	<set name='$name' value='$val' />\n|;
	        }
		}
	$xml .= qq|</graph>\n|;
	setFileContents("$ENV{DOCUMENT_ROOT}/$datafile",$xml);
	#script
	$rtn .= qq|<script type="text/javascript">\n|;
	$rtn .= qq|	if(typeof(FusionCharts) == 'function'){\n|;
  	$rtn .= qq|		var chart = new FusionCharts('/wfiles/charts/FCF\_$graph\.swf', "$sid", "600", "400", "0", "0");\n|;
   	$rtn .= qq|		chart.setDataURL("/$datafile");\n|;
   	$rtn .= qq|		chart.render('$gid');\n|;
   	$rtn .= qq|		}\n|;
   	$rtn .= qq|	else{alert('FusionCharts script is not loaded. Unable to graph');}\n|;
	$rtn .= qq|</script>\n|;
	return $rtn;
    }
##############
sub isDBTable{
	#usage: if(isDBTable("stats")){...}
	#info:  returns 1 if the table exists in the current database
	#tags: database
	my $table=shift;
	my @tables=getDBTables();
	foreach my $ctable (@tables){
		if($table=~/^\Q$ctable\E$/is){return 1;}
	     }
     return 0;
	}
##############
sub isValidUser{
	#usage: if(!isValidUser($username)){...}
	#info: return 1 if $username already exists in the _users table
	#tags: database
	my $username=shift || return 0;
	my %list=();
     my $sql=qq|select _id from _users where username like '$username'|;
     my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
     if(!isNum($cnt)){return $cnt;}
     return $list{0}{_id};
	}
##############
sub isValidUserId{
	#usage: if(!isValidUserId($id)){...}
	#info: return 1 if a record with _id of $id already exists in the _users table
	#tags: database
	my $id=shift || return 0;
	if(!isNum($id)){return 0;}
	my %list=();
     my $sql=qq|select _id from _users where _id=$id|;
     my $cnt=getDBData(\%list,$sql,"nocount=1;limit=1");
     if(!isNum($cnt)){return $cnt;}
     return $list{0}{_id};
	}
##################
sub importXML{
	#internal usage: importXML(file=>"test.xml",type=>"schema|meta|data");
	my %params=@_;
	my $output=$params{output}=~/^(off|0)$/is?0:1;
	my $infile=$params{file} || "$progpath/wasqldump.xml";
	print "Collecting xml records from $infile<br>\n" if $output;
	if(!open(IF,$infile)){
		print "Error: cannot read $infile<br>\n" if $output;
		return -1;
		}
	my @lines=<IF>;
	close(IF);
	my $linecnt=@lines;
	print "Line Count: $linecnt<br>\n" if $output;
	my $xml=join('',@lines);
	my $tcnt=0;
	my $sxml=$xml;
	if($output==1){
		foreach my $key (sort(keys(%params))){
			print "<b>$key</b> = $params{$key}<br>\n";
			}
		print "<u><b>-------- Import Status Below ---------</b></u><br>\n";
		}
	my %NewMap=();
	my %OldMap=();
	my $str=$params{mapfields};
	if(length($str)){
		my @pairs=split(/\,+/,$str);
		foreach my $pair (@pairs){
			my ($oldfld,$newfld)=split(/\=/,$pair,2);
			$NewMap{$oldfld}=$newfld;
			$OldMap{$newfld}=$oldfld;
			}
		}
	#print "<u><b>-------- Import Status Below ---------</b></u><br>\n";
	#Import Schema
	if($params{schema}){
		print qq|<b style="color:#6699cc">Importing Schema ..</b><br>\n| if $output;
		while($sxml=~m/\<xmlschema name="(.+?)"(.*?)\>(.+?)\<\/xmlschema\>/sig){
			my $table=strip($1);
			my $pstr=strip($2);
			my $str=strip($3);
			my @fieldsets=();
			while($str=~m/\<field name="(.+?)" type="(.+?)"\>/sig){
				my $field=lc(strip($1));
				my $type=strip($2);
				if($NewMap{$field}){$field=$NewMap{$field};}
				push(@fieldsets,$field=>$type);
				}
			print "... Creating $table table ..." if $output;
			my $ck=createDBTable($table,@fieldsets);
			if($ck && $ck!=1){
				if($output){
					print "createDBTable Error<br>$DBI::query<br>$DBI::errstr<br>\n";
					}
				return 0;
	            }
	        #Check for indexes
	        #<index name="idx_fielddata" cols="_tablename,_fieldname" unique="1">
	        while($str=~m/\<index name="(.+?)" cols="(.+?)"(.*?)\>/sig){
				my $name=strip($1);
				my $cols=strip($2);
				my @opts=(table=>$table,name=>$name,cols=>$cols);
				my $extra=$3;
				if($extra=~/unique="(true|1)"/is){push(@opts,unique=>1);}
				print qq|<b style="color:#6699cc">Creating index $name for $table ($cols)..</b><br>\n| if $output;
				my $ok=createDBIndex(@opts);
				if(!isNum($ok)){return abort($ok . "<hr>" . $DBI::query);}
				}
			$tcnt++;
			#if the table def contained datafile="somefile.csv" then import the data
			if(length($pstr) && $pstr=~/datafile\=\"(.+?)\"/is){
				my $str=strip($1);
				my @files=split(/[\,\;]+/,$str);
				foreach my $file (@files){
					if(-s $file && $etype=~/^(data|both)$/is){
						if($file=~/\.csv$/is){
							print "... Importing csv file $file for $table<br>\n" if $output;
							&importCSV(file=>$file,table=>$table);
							}
						}
					}
				}
			}
		}
	#Meta
	if($params{meta}){
		my $dxml=$xml;
		print qq|\n\n<br><b style="color:#6699cc">Importing Meta ..</b><br>\n| if $output;
		my @fields;
		my $table='';
		while($dxml=~m/\<xmlmeta table="(.+?)"\>(.+?)\<\/xmlmeta\>/sig){
			my $xtable=strip($1);
			my $str=strip($2);
			$str=~/\<tablename\>(.+?)\<\/tablename\>/is;
			my $table=$1;
			if(length($table)==0 || $xtable!~/^\Q$table\E$/is){
				$table=$xtable;
				#print "<br>\n... Table $table " if $output;
				}
			else{$table=$xtable;}
			@fields=getDBFields($table);
			#print "Fields: @fields<br>\n" if $output;
			my @sets=();
			foreach my $field (@fields){
				my $sfield=$field;
				if($OldMap{$field}){$sfield=$OldMap{$field};}
				if($str=~m/\<$sfield\>(.*)\<\/$sfield\>/is){
					my $val=strip($1);
					#print "----$field=[$val]<br>\n";
					next if length($val)==0;
					push(@sets,$field=>$val);
					}
				}
			if(1==2 && $output){
				my %Sets=@sets;
				foreach my $key (sort(keys(%Sets))){print "$key = [$Sets{$key}]<br>\n";}
				}
			my $setcnt=@sets;
			if($setcnt){
				my $ck=addDBData($table,@sets);
				if(!isNum($ck)){print "\n<br>addDBData Error: $ck<br>\n" if $output;}
				else{print "." if $output;}
				}
			}
		}
	#Data
	if($params{data}){
		$dxml=$xml;
		@fields=();
		$table='';
		print qq|\n\n<br><b style="color:#6699cc">Importing Data ..</b><br>\n| if $output;
		while($dxml=~m/\<xmldata table="(.+?)"\>(.+?)\<\/xmldata\>/sig){
			my $xtable=strip($1);
			my $str=strip($2);
			if(length($table)==0 || $xtable!~/^\Q$table\E$/is){
				$table=$xtable;
				print "<br>\n... Table $table " if $output;
				@fields=getDBFields($table);
				}
			#print "Fields: @fields<br>\n";
			my @sets=();
			foreach my $field (@fields){
				next if $table=~/^\_(fielddata|tabledata)$/is && $field=~/^\_id$/is;
				my $sfield=$field;
				if($OldMap{$field}){$sfield=$OldMap{$field};}
				if($str=~m/\<$sfield\>(.*)\<\/$sfield\>/is){
					my $val=strip($1);
					#strip cdata tags
					if($val=~/^\<\!\[CDATA\[(.+)\]\]\>$/is){$val=$1;}
					#print "----$field=[$val]<br>\n";
					next if length($val)==0;
					push(@sets,$field=>$val);
					}
				}
			my $setcnt=@sets;
			if($setcnt){
				my ($ck,$sql)=addDBData($table,@sets);
				if(!isNum($ck)){print "\n<br>addDBData Error: [$ck] $DBI::errstr <br>\n" if $output;}
				else{print "." if $output;}
				}
			}
		}
	return 2;
	}
###############
sub importCSV{
	#internal usage: importCSV(file=>"test.xml",table=>$table,startline=>345);
	my %params=@_;
	my $file=$params{file} || return;
	my $table=$params{table} || return;
	#Build a hash of table fields
	my @tablefields=getDBFields($table);
	my %Field=();
	foreach my $field (@tablefields){
		$field=lc(strip($field));
		$Field{$field}=1;
		}
	##############
	print qq|<b style="color:#336699">Importing records to $table table.</b><br>\n|;
	print "Opening $file<br>\n";
	#Get Pointer
	my $pointer=$params{startline} || 2;
	if(!open(CSV,$file)){
		print "Unable to Open $file<br>\n";
		return;
		}
	my $lcnt=0;
	my $acnt=0;
	my $bcnt=0;
	my $ccnt=0;
	my $pnt=0;
	my @header=();
	print "Begin Importing Records starting at line $pointer. Import Status Below.<br>\n";
	while(<CSV>){
		my $csvline=strip($_);
		#ignore comment lines
		next if $csvline=~/^[\#\;]/is;
		$lcnt++;
		my @parts=csvParseLine($csvline);
		if($lcnt==1){
			@header=@parts;
			print "Fields to Import: @header<br>\n";
			}
		else{
			if($pointer>$lcnt){next;}
			elsif($pointer==$pnt){
				print "Starting Data at line $pnt [$lcnt]<br>\n";
				}
			my $hcnt=@header;
			my @sets=();
			if($Field{importline}==1){push(@sets,importline=>$lcnt);}
			for(my $x=0;$x<$hcnt;$x++){
				$header[$x]=~s/\ +/\_/sg;
				my $field=lc(strip($header[$x]));
				my $val=strip($parts[$x]);
				next if length($val)==0;
				if($Field{$field}==1){push(@sets,$field=>$val);}
				}
			my $setcnt=@sets;
			if($setcnt){
				#print "CSV: $csvline<br>\n";
				#print "Sets: @sets<br>\n<hr>\n\n";
				my $ck=addDBData($table,@sets);
				if(!isNum($ck)){
					print "\n<br>Error: $ck<br>\n";
					next;
					}
				$acnt++;$bcnt++;$ccnt++;
				if($acnt>=250){
					print "Line $lcnt. $ccnt records imported. Last Record number=$ck.<br>\n";
					$acnt=0;
					}
				if($bcnt>25){print ".";$bcnt=0;}
				}
			}
		}
	close(CSV);
	print "<br>Done importing $ccnt records to $table table.";
	}
###############
sub isDBReservedWord{
	#internal usage: if(!isDBReservedWord('mod')){code here...};
	#internal info: return 1 if word is a reserved database word.. ie - dont use it
	my $word=shift;
	$word=strip($word);
	my @reserved=(
		'action','add','all','allfields','alter','and','as','asc','auto_increment','between','bigint','bit','binary','blob','both','by',
		'cascade','char','character','change','check','column','columns','create',
		'data','database','databases','date','datetime','day','day_hour','day_minute','day_second','dayofweek','dec','decimal','default','delete','desc','describe','distinct','double','drop','escaped','enclosed',
		'enum','explain','fields','float','float4','float8','foreign','from','for','full',
		'grant','group','having','hour','hour_minute','hour_second',
		'ignore','in','index','infile','insert','int','integer','interval','int1','int2','int3','int4','int8','into','is','inshift','in1',
		'join','key','keys','leading','left','like','lines','limit','lock','load','long','longblob','longtext',
		'match','mediumblob','mediumtext','mediumint','middleint','minute','minute_second','mod','month',
		'natural','numeric','no','not','null','on','option','optionally','or','order','outer','outfile',
		'partial','precision','primary','procedure','privileges',
		'read','real','references','rename','regexp','repeat','replace','restrict','rlike',
		'select','set','show','smallint','sql_big_tables','sql_big_selects','sql_select_limit','sql_log_off','straight_join','starting',
		'table','tables','terminated','text','time','timestamp','tinyblob','tinytext','tinyint','trailing','to',
		'use','using','unique','unlock','unsigned','update','usage',
		'values','varchar','varying','varbinary','with','write','where',
		'year','year_month','zerofill'
		);
	foreach my $rword (@reserved){
		if($rword=~/^\Q$word\E$/is){return 1;}
		}
	return 0;
	}
####################
sub loadPage{
	#depreciated - use includePage instead
	return "loadPage is depreciated. Use includePage instead";
	}
###############
sub modifyDBColumn{
	#internal usage: $ck=modifyDBColumn($tablename,name=>'varchar(255)');
	#internal info:  modifies column table. returns 1 on success or error message on failure
	$DBI::query='';
	if($dbt=~/^sqlite$/i){return "SQLite does not support modifying a column";}
	my $tablename=lc(shift) || return "No Table in modifyDBColumn";
	my %params=@_;
	my $query = qq|alter table $tablename |;
	my @tmp=();
	foreach my $field (keys(%params)){
		next if $field=~/^\_id$/s;
		my $type=$params{$field};
		push(@tmp,"modify $field $type");
		}
	my $tcnt=@tmp;
	if($tcnt==0){return 'No Fields passed to modify in modifyDBColumn';}
	$query .= join(",",@tmp);
	$DBI::query=$query;
	$sth = $dbh->prepare($query);
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("$query $DBI::errstr");}
	$sth->execute();
	if(length($DBI::err)){if($sth){$sth->finish;undef $sth;}return ("$query $DBI::errstr");}
	if($sth){$sth->finish;undef $sth;}
	return 1;
	}
###############
sub prepDBString{
	#internal usage: $val=prepDBString($newval);
	#internal info: quotes values for use in database inserts.
	my $str=shift;
	if($str=~/^NULL$/is){return "\'\'";}
	#print "prepDBString[$str]\n";
	my $newstr=$dbh->quote($str);
	return $newstr;
	}
############## tvals
sub searchDBReplace{
	#internal usage: my ($err,$sql,@changed_ids)= searchDBReplace($table, $str1, $str2[, _where=>$where]);
	#internal info: given a table it will search through each record in given table and replace $str1 with $str2. Returns count of records affected or an array of ids affected.
	my $table=shift || return ("Table is required",'',0);
	my $str1=shift;
	my $str2=shift;
	my %params=@_;
	if(!length($str1) || !length($str2)){return ("Search and Replace fields are required",'',0);}
	my %list=();
	my @fields=();
	if($params{_fields}){@fields=split(/[:,]/,$params{_fields});}
	else{@fields=getDBFields($table,1);}
	my $foundid=0;
	foreach my $field (@fields){
		if($field=~/^\_id$/is){$foundid++;last;}
	     }
	if(!$foundid){unshift(@fields,"_id");}
	my $fieldcnt=@fields;
	if(!$fieldcnt){return ("Fields are required",'',0);}
	my $fieldstr=join(',',@fields);
	my $sql=qq|select $fieldstr from $table|;
	if($params{_where}){$sql .= qq| where $params{_where}|;}
	my $crit="nocount=1";
     if($params{_limit}){$crit .= ";limit=$params{_limit}";}
	my ($cnt,$rsql)=getDBData(\%list,$sql,$crit);
	#return "TEST: $cnt<br>\nsql:$sql<br>rsql:$rsql<br>crit:$crit";
	if(!isNum($cnt)){return ("$cnt",$rsql,0);}
	if($cnt==0){return ('',$rsql,0);}
	my @changed_ids=('',$rsql,$cnt);
	for(my $x=0;$x<$cnt;$x++){
		my @changes=();
		my $id=$list{$x}{_id} || return ("No _id for record $x in $table in searchDBReplace",$rsql,$cnt);
		foreach my $field (@fields){
			my $val=$list{$x}{$field};
			if($params{_ignorecase}==1){
			 	if($val=~m/\Q$str1\E/is){
					if($params{_global}==1){$val=~s/\Q$str1\E/$str2/isg;}
					else{$val=~s/\Q$str1\E/$str2/is;}
					push(@changes,$field=>$val);
					}
				}
    			else{
				if($val=~m/\Q$str1\E/s){
					if($params{_global}==1){$val=~s/\Q$str1\E/$str2/sg;}
					else{$val=~s/\Q$str1\E/$str2/sg;}
					push(@changes,$field=>$val);
					}
	               }
	          }
	     my $change=@changes;
		if($change){
			#Edit the current record
			my ($ok,$csql)=editDBData($table,"_id=$id",@changes);
			if(isNum($ok)){push(@changed_ids,$id);}
			else{return ($ok,$csql,$cnt);}
			}
	     }
	return @changed_ids;
	}
###############################################
### Redirect subs for backward compatibility

###########
return 1;
