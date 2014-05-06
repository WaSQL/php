#!/usr/bin/perl
#####################################################
### Compile Options - These options are stamped into the exe when compiled
#perl2exe_info CompileOptions -v -gui -opt -tiny -icon=postedit.ico
#perl2exe_bundle "postedit.ico"
#perl2exe_info CompanyName=http://www.wasql.com
our $CompanyName="http://www.wasql.com";
#perl2exe_info FileDescription=WaSQL Edit Manager
our $FileDescription="WaSQL Edit Manager";
#perl2exe_info OriginalFilename=postEdit.exe
our $OriginalFilename="postEdit.exe";
#perl2exe_info InternalName=postEdit
our $InternalName="postEdit";
#perl2exe_info ProductName=WaSQL Edit Manager
our $ProductName="WaSQL Edit Manager";
#perl2exe_info ProductVersion=1.600.31
our $ProductVersion="1.600.31";
#perl2exe_info FileVersion=1.1404.26
our $FileVersion="1.1404.26";
#perl2exe_info LegalCopyright=Copyright 2004-2012, WaSQL.com
our $LegalCopyright="Copyright 2004-2012, WaSQL.com";
#################################################################
#  WaSQL - Copyright 2004 - 2011 - All Rights Reserved
#  http://www.wasql.com - info@wasql.com
#  Author is Steven Lloyd
#  See license.txt for licensing information
#################################################################
# HTML Validation: http://validator.w3.org/#validate_by_input
# CSS Validation: http://jigsaw.w3.org/css-validator/#validate_by_input
#################################################################
use Win32::GUI;
use Win32::API;
use Win32::GUI::DropFiles;
use Win32::Sound;
#ppm install http://trouchelle.com/ppm/Data-Lazy.ppd
#ppm install http://trouchelle.com/ppm/Win32-AbsPath.ppd
#ppm install http://trouchelle.com/ppm/Win32-FileOp.ppd
use Win32::FileOp;
use Cwd 'abs_path';
use Socket;
$|=1;
#print "Begin\n";
#determine the progpath
$progpath = abs_path($0) ;
$progpath =~ m/^(.*)(\\|\/)(.*)\.([a-z]*)/;
if($progpath=~/\.(exe|pl)/i){
	my @tmp=split(/[\\\/]+/,$progpath);
	my $name=pop(@tmp);
	if($^O =~ /^MSWIN32$/is){$progpath=join("\\",@tmp);}
	else{$progpath=join('/',@tmp);}
}
#print "CWD:".$progpath."\n";exit;
require '../subs_common.pl';
require '../subs_socket.pl';
#read any filters
our $lockfile='';
our %input=();
foreach my $arg (@ARGV){
	my ($key,$val)=split(/\=/,$arg);
	$key=lc(strip($key));
	$val=strip($val);
	next if !length($key) || !length($val);
	$input{$key}=$val;
	}
$progname="postedit";
our $xmlfile="$progpath/$progname\.xml";
if(! -s $xmlfile){
	Win32::GUI::MessageBox(0,"Missing $progname\.xml config file\r\nProgram will abort.","postEdit Error",0x0010);
	print "Missing $xmlfile\n";
	exit;
	}
###################################
our $baseDir="$progpath\\postEditFiles";
if(!-d $baseDir){buildDir($baseDir);}
our $filesDir=$baseDir;
our $baseXMLDir="$progpath\\postEditXML";
if(!-d $baseXMLDir){buildDir($baseXMLDir);}
our %xml=readXML($xmlfile,'hosts');
#print "XML:\n";
#print hashValues(\%xml);
#exit;
our %settings=readXML($xmlfile,'settings');
our %watchtable=();
#print hashValues(\%xml);
#print "\nSETTINGS:\n";
#print hashValues(\%settings);
#exit;
###################################
$|=1;
our $update=1;
our $inimtime=0;
our @inilines=();
our %Watch=();
our %FileCount=();
#hide child windows
Win32::SetChildShowWindow(0);
#remove old log file if it exists
unlink("$progpath/$progname\.log") if -e "$progpath/$progname\.log";
my $ico="$progpath\\$progname\.ico";
my $icon = new Win32::GUI::Icon($ico);
my $hwnd_class = new Win32::GUI::Class(
 		-name => "postEdit Class",
 		-icon => $icon
 		);
#Create some fonts we need
my $Font14 = Win32::GUI::Font->new(
	-name => "Times",
	-size => 14,
	);
my $Font11 = Win32::GUI::Font->new(
	-name => "Times",
	-size => 11,
	);
my $Font10 = Win32::GUI::Font->new(
	-name => "Times",
	-size => 10,
	);
my $Font8 = Win32::GUI::Font->new(
	-name => "Times",
	-size => 8,
	);
#create the window
our $mw = new Win32::GUI::Window(
    -name   	=> "MainWindow",
    -title  	=> "WaSQL Edit Manager",     # Title window
    -pos    	=> [50,50],                   # Default position
    -size   	=> [500,400],                   # Default size
    -resizable  => 0,
    -hasmaximize=> 0,
    -topmost	=> 1,
    -class   	=> $hwnd_class,
    -icon 		=> $icon,
    -dialogui => 1,
    -onDropFiles => \&dropFilesEvent,
    -onTimer	=> \&mainTimer,
    -onTerminate => \&terminateWindowEvent,
    -acceptfiles => 1,
	);
$mw->ChangeIcon($icon);
setWindowPosition($mw,center=>"middle");
my $rowheight=20;
#First GUI Row - Group, Site Name/Alias, Icon
my $ypos = 8;
$mw->AddLabel(
	-name 	=> "Group_Label",
	-pos    => [10, $ypos],
	-size	=> [35,20],
	-font	 => $Font8,
	-foreground => 0x0080C0,
	-text	=> "Group"
	);
$mw->AddLabel(
	-name 	=> "Domain_Label",
	-pos    => [166, $ypos],
	-size	=> [325,20],
	-tip	=> "Click to browse",
	-font	 => $Font8,
	-foreground => 0x0080C0,
	#-background => 0x00BBC0,
	-text	=> "Domain/Alias",
	-notify => 1,
	-onClick => sub{return processButton('OpenDir');},
	);
$mw->AddButton(
	-name => "OpenDir",
	-text => "OpenDir",
	-tip => "Click to open PostEdit directory",
	-font	 => $Font10,
	-tabstop => 1,
	-pos     => [420, $ypos+12],
	-size	=> [65,30],
	-onClick => sub{return processButton('OpenDir');},
	);
$mw->{OpenDir}->Disable();
our $logo=Win32::GUI::Bitmap->new("$progpath/wasql.bmp");
$mw->AddLabel(
	-name 	=> "Image_Label",
	-pos    => [425, $ypos-5],
	-size	=> [65,65],
	-foreground => 0x0080C0,
	-bitmap	=> $logo,
	);
#Second GUI Row - Group select, Site select
$ypos += $rowheight;
#Group List
 $mw->AddCombobox(
 	-text    => "",
 	-name    => "Group",
 	-pos     => [10, $ypos],
 	-tabstop => 0,
 	-size	 => [150,200],
 	-font	 => $Font10,
 	-tip	 => "Current Group",
 	-addstyle=>WS_VISIBLE | 3 | WS_VSCROLL | WS_TABSTOP,
 	-onChange=>\&processSelect
 	);
#Site list
 $mw->AddCombobox(
 	-text    => "",
 	-name    => "Domain",
 	-pos     => [165, $ypos],
 	-tabstop => 0,
 	-size	 => [250,200],
 	-font	 => $Font10,
 	-tip	 => "Current Domain",
 	-addstyle=>WS_VISIBLE | 3 | WS_VSCROLL | WS_TABSTOP,
 	-onChange=>\&processSelect
 	);
$mw->{Domain}->Disable();

#3rd GUI Row - Status, Refresh Filter, Refresh Labels
$ypos += $rowheight+5;
$mw->AddLabel(
	-name 	=> "Status_Label",
	-pos    => [10, $ypos],
	-size	=> [35,20],
	-font	 => $Font10,
	-foreground => 0x0080C0,
	-text	=> "Status"
	);
$mw->AddLabel(
	-name 	=> "Page_Label",
	-pos    => [170, $ypos],
	-size	=> [120,20],
	-font	 => $Font10,
	-foreground => 0x0080C0,
	-text	=> "Refresh Filter"
	);
#4th GUI Row - Status, Refresh Filter, Refresh boxes
$ypos += $rowheight;
#colorbox with border
$mw->AddLabel(
	-name 	=> "ColorBox",
	-pos    => [10, $ypos],
	-size	=> [148,20],
	-sunken	=> 1,
	-foreground => 0x000000,
	-align	=>"center",
	-font	=> $Font10,
	-text	=> ""
	);
$mw->AddTextfield(
	-name 	=> "RefreshFilter",
	-pos     => [165, $ypos],
 	-tabstop => 1,
 	-size	 => [250,20],
	-font	 => $Font10,
	-remstyle => WS_BORDER,
	-text	=> $input{filter}
	);
$mw->AddButton(
	-name => "Refresh",
	-text => "Refresh",
	-tip => "Enter text to filter refresh by",
	-font	 => $Font10,
	-tabstop => 1,
	-pos     => [420, $ypos-10],
	-size	=> [65,30],
	-onClick => sub{return processButton('Refresh');},
	);
#$mw->{RefreshFilter}->Disable();
$mw->{Refresh}->Disable();
# New GUI ROW - settings, Upload
$ypos += $rowheight+5;
$mw->AddGroupbox(
	-name 	=> "SettingsGroupBox",
	-pos    => [10, $ypos],
	-size	 => [235,140],
 	-font	 => $Font10,
	-foreground => 0x0080C0,
	-text	=> "Settings"
	);
#Settings Area - Perl Check
$mw->AddCheckbox(
	-name 	=> "Check_perl",
	-pos    => [20, $ypos + 20],
	-tabstop => 1,
	-foreground => 0x696969,
	-font	 => $Font11,
	-text	=> "Perl Syntax Check"
	);
#Check PHP
$mw->AddCheckbox(
	-name 	=> "Check_php",
	-pos    => [20, $ypos+40],
	-foreground => 0x696969,
	-tabstop => 1,
	-font	 => $Font11,
	-text	=> "PHP Syntax Check"
	);
#Stay Open
$mw->AddCheckbox(
	-name 	=> "Check_min",
	-pos    => [20, $ypos+60],
	-foreground => 0x696969,
	-tabstop => 1,
	-font	 => $Font11,
	-text	=> "Don't Minimize Window"
	);
#links
$mw->AddLabel(
	-name 	=> "VersionLink",
	-pos    => [18, $ypos+85],
	-size	=> [148,18],
	-notify => 1,
	-foreground => 0x00a45e10,
	-font	=> $Font10,
	-text	=> "Show Version Information",
	-onClick => sub{return processButton('About');},
	);
# $mw->AddLabel(
# 	-name 	=> "User_Group",
# 	-pos    => [18, $ypos+105],
# 	-size	=> [200,16],
# 	-font	 => $Font10,
# 	-text	=> "Load Local PostEdit File:"
# 	);
# $mw->AddButton(
# 	-name => "LocalFile",
# 	-text => "Browse",
# 	-tip => "Click to load local postEdit file",
# 	-font	 => $Font10,
# 	-tabstop => 1,
# 	-pos    => [175, $ypos+110],
# 	-size	=> [60,20],
# 	-onClick => sub{return processButton('LocalFile');},
# 	);
#File Upload Area
$mw->AddGroupbox(
	-name 	=> "UploadGroupBox",
	-pos    => [250, $ypos],
	-size	 => [235,140],
 	-font	 => $Font11,
	-foreground => 0x0080C0,
	-text	=> "File Upload Directories"
	);
$mw->AddCombobox(
 	-text    => "",
 	-name    => "DropDirs",
 	-pos     => [255, $ypos+20],
 	-tabstop => 1,
 	-size	 => [190,200],
 	-font	 => $Font10,
 	-tip	 => "Upload Directory",
 	-addstyle=>WS_VISIBLE | 3 | WS_VSCROLL | WS_TABSTOP,
 	-onChange=>\&processSelect
 	);
$mw->AddButton(
	-name => "DropList",
	-text => "List",
	-pos     => [448, $ypos+20],
	-tabstop => 1,
	-size	=> [30,21],
	-font	 => $Font10,
	-onClick => sub{return processButton('DropList');},
	);
our $dropboxtext="Select a directory above to enable drag and drop file upload to that directory. Click List to list current files in the selected directory.";
our $dropboxenabled="Drag and drop files anywhere in this program to upload files to selected directory. Click List to list current files in the selected directory.";
$mw->AddTextfield(
	-name 	=> "DropBox",
	-pos    => [255, $ypos+50],
	-size	=> [225,80],
	-background => 0xf7dfd6,
	-font	 => $Font10,
	-readonly=>1,
	-multiline => 1,
	-vscroll=>1,
	-align	=>"left",
	-remstyle => WS_BORDER,
	-text	=> $dropboxtext,
	);
$mw->AcceptFiles(1);
$mw->{DropBox}->Disable();
$mw->{DropDirs}->Disable();
$mw->{DropList}->Disable();
# New GUI ROW - Activity Log
$ypos += $rowheight+125;
$mw->AddGroupbox(
	-name 	=> "MessageTitle",
	-pos    => [10, $ypos],
	-size	 => [475,105],
 	-font	 => $Font10,
	-foreground => 0x0080C0,
	-text	=> "Recent Activity Log"
	);
$mw->AddTextfield(
	-name => "Message",
	-text=>"",
	-font	=> $Font10,
	-pos	=> [17,$ypos+18],
	-size	=> [460,82],
	-background => 0x00eaeaea,
	-readonly=>1,
	-multiline => 1,
	-vscroll=>1,
	-remstyle => WS_BORDER,
	);

$mw->AddStatusBar(
    -name => "Status",
    -text => "Ready",
	);
my $systray = $mw->AddNotifyIcon(
 	-name 	=> "_Systray",
 	-icon 	=> $icon,
 	-tip 	=> "postEdit is Running",
 	-font	=> $Font14,
 	-balloon         => 0,
	-balloon_tip     => "",
	-balloon_title   => $progname,
	-balloon_icon    => 'none',
	-balloon_timeout => 3000,
 	-onClick=> \&restoreWindow
 	);
$systray->SetBehaviour(1);
$mw->AddTimer('BalloonTimeout', 0);
our $WDTimer = $mw->AddTimer('WatchDir', 500);
#our $RsyncTimer = $mw->AddTimer('Rsync', 30000);
our $CWTimer = $mw->AddTimer('CloseWin', 0);
$mw->Show();
#populate the group
@vals=getGroups();
my $i=0;
my $si=-1;
foreach my $val (@vals){
	$mw->{Group}->InsertItem($val,$i);
	if($input{group}=~/^\Q$val\E$/i){
		$si=$i;
		}
	$i++;
	}
if($si > -1){
	$mw->{Group}->Select($si);
	processSelect($mw->{Group});
	$input{group}='';
}
Win32::GUI::Dialog();
exit;
###############
sub loadLocalPosteditFile{
	#Note: Win32::FileOp::OpenDialog is much prettier than Win32::GUI::BrowseForFolder
	my $file = Win32::FileOp::OpenDialog(
		-title   => "Select source file",
		-filters => ['All Files' => '*.*'],
		-defaultfilter => 1,
		-dir => $progpath,
		-filename => '*.result',
		-options => OFN_EXPLORER,
		-handle => $mw->{-handle},
	);
	if(!-s $file){return 0;}
	my $body=getFileContents($file);
	print "Processing File: $file\n";
	my $ok=processPostEditXML($body,$cdir);
	return;
}
sub getProcessByPid{
	my $pid=shift;
	my %list=GetProcessList();
	foreach my $exe (keys(%list)){
		my @ppids=split(/\;/,$list{$exe}{pid});
		foreach my $ppid (@ppids){
    		if($ppid==$pid){
				return $list{$exe};
				}
		}
	}
	return null;
}
###############
sub GetProcessList {
    # return a hash of processes running indexed by name
    # Import required functions
    my $includeHandles=shift;
    my $CreateToolhelp32Snapshot = new Win32::API("kernel32","CreateToolhelp32Snapshot",["N", "N"],"I") || return $^E;
    my $Process32First = new Win32::API("kernel32","Process32First",["I", "P"],"I") || return $^E;
    my $Process32Next = new Win32::API("kernel32","Process32Next",["I", "P"],"I") || return $^E;
    my $CloseHandle = new Win32::API("kernel32","CloseHandle",["I"],"V") || return $^E;
    #constants
	my $TH32CS_SNAPPROCESS = 0x00000002;
	my $DWORD_SIZE = 4;
	my $LONG_SIZE = 4;
	my $MAX_PATH_SIZE = 260;
    # Take a snapshot of all processes in the system.
    my $hProcessSnap = $CreateToolhelp32Snapshot->Call($TH32CS_SNAPPROCESS, 0) || return $^E;
    # Fill in the size of the structure with blanks before using it.
    my $structLen=($DWORD_SIZE*8+$LONG_SIZE+$MAX_PATH_SIZE);
    my $PROCESSENTRY32 = " "x$structLen;
    #  Walk the snapshot of the processes, and for each process
    my %list=();
    if ($Process32First->Call($hProcessSnap, $PROCESSENTRY32)) {
        do {
            # Unpack structure to hash
            my ($size,	#Specifies the length, in bytes, of the structure.
            $refcount,	#Number of references to the process. Once zero, process terminates.
            $pid,		#ProcessID. Usable by Win32 API
            $heap,		#Identifier of the default heap for the process. NOT usable by Win32 API
            $mid,       #Module identifier of the process. NOT usable by Win32 API
            $threadcnt, #Number of execution threads started by the process.
            $ppid,		#parent ProcessID of process that created it. Usable by Win32 API
            $priority,  #Base priority of any threads created by this process.
            $flags,  	#Reserved - NOT useful
            $name		#Path and filename of the executable file for the process.
			) = unpack("LLLLLLLlLA*",$PROCESSENTRY32);
			#skip those we don't care about
			goto endDo if $name=~/^(svchost.exe|System|winhlp32.exe)$/is;
			goto endDo if $name!~/\.exe/is;
			goto endDo if $pid == 0;
			my $hprocess=getProcessHandle($pid);
            my ($path,$exe)=getProcessPath($hprocess);
            goto endDo if !length($exe);
            $path=~s/^[\\\/\?]+//s;
            $exe=~s/\.exe$/\.exe/is;
            $list{$exe}{execount}+=1;
            $list{$exe}{exe}=$exe;
			$list{$exe}{size}+=$size;
			$list{$exe}{refcount}+=$refcount;
			if(defined $list{$exe}{pid}){$list{$exe}{pid}.=';'.$pid;}
			else{$list{$exe}{pid}=$pid;}
			$list{$exe}{threadcount}+=$threadcnt;
			$list{$exe}{priority}=$priority;
			if(!defined $list{$exe}{path}){
				$list{$exe}{path}=$path;
				}
			$CloseHandle->Call($hprocess);
			#get parent process info
			if(!defined $list{$exe}{parent_exe}){
				my $phprocess=getProcessHandle($ppid);
				my ($ppath,$pexe)=getProcessPath($phprocess);
				$ppath=~s/^[\\\/\?]+//s;
				if($pexe){
					$pexe=~s/\.exe$/\.exe/is;
					$list{$exe}{parent_exe}=$pexe;
					$list{$exe}{parent_path}=$ppath;
					$list{$exe}{parent_pid}=$ppid;
					}
				$CloseHandle->Call($phprocess);
				}
            # Fill in the size of the structure again with blanks.
            $PROCESSENTRY32 = " "x$structLen;
endDo:

        } while ($Process32Next->Call($hProcessSnap, $PROCESSENTRY32));
    }
    # Close handle
    $CloseHandle->Call($hProcessSnap);
    # Return process list
    return %list;
	};
######################################################################
sub getProcessHandle {
	#usage:$hprocess=getProcessHandle($processID);
	#info: returns a  process handle of the processID passed in.
	my $processID=shift || return;
	my $PROCESS_QUERY_INFORMATION = 0x0400;
	my $PROCESS_VM_READ = 0x0010;
	my $DWORD = $PROCESS_QUERY_INFORMATION | $PROCESS_VM_READ;
	my $OpenProcess = new Win32::API( "kernel32.dll", "OpenProcess", ['I','I','N'],'N') || return $^E;
	my $hProcess  = $OpenProcess->Call( $DWORD,0, $processID );
	return $hProcess;
	};
###############
sub getProcessPath{
    my $hProcess=shift || return;
    my $DWORD_SIZE = 4;
	my $PROC_ARRAY_SIZE = 100;
	my $MODULE_LIST_SIZE = 200;
	# Define some Win32 API constants
	my $PROCESS_QUERY_INFORMATION = 0x0400;
	my $PROCESS_VM_READ = 0x0010;
	my $EnumProcessModules = new Win32::API( 'psapi.dll', 'EnumProcessModules', [N,P,N,P], I ) || return $^E;
	my $GetModuleFileNameEx = new Win32::API( 'psapi.dll', 'GetModuleFileNameEx', [N,N,P,N], N ) || return $^E;
	my $BufferSize = $MODULE_LIST_SIZE * $DWORD_SIZE;
    my $MemStruct = "\x00"  x $BufferSize;
    my $iReturned = "\x00"  x $BufferSize;
    $EnumProcessModules->Call( $hProcess, $MemStruct, $BufferSize, $iReturned ) || return "Not Sure";
    my $StringSize = 255 * ( ( Win32::API::IsUnicode() )? 2 : 1 );
    my $ModuleName = "\x00"  x $StringSize;
    my @ModuleList = unpack( "L*", $MemStruct );
    my $hModule = $ModuleList[0];
    $GetModuleFileNameEx->Call( $hProcess, $hModule, $ModuleName, $StringSize ) || return "Unknown";
    my $afile =  FixAPIString($ModuleName);
    my $path=getFilePath($afile);
    my $name=getFileName($afile);
    if(wantarray){return ($path,$name);}
    return $path;
	}
###############
sub FixAPIString{
    my( $String ) = @_;
    $String =~ s/(.)\x00/$1/g if( Win32::API::IsUnicode() );
    return( unpack( "A*", $String ) );
	}
#############
sub showBalloon{
	#msg is limited to 255 characters and will be truncated as necessary
	my $msg=shift || return;
	#title is limited to 63 characters and will be truncated as necessary
	my $title=shift || "WaSQL postEdit";
	#valid icons: error, info, warning, none.  Defaults to 'none'
	my $icon=shift || 'info';
	$mw->{BalloonTimeout}->Interval(0);
	$systray->Change(-balloon_tip=>$msg);
	$systray->Change(-balloon_title=>$title);
	$systray->Change(-balloon_icon=>$icon);
	$systray->ShowBalloon(0);
	$systray->ShowBalloon(1);
	my $intim=3000;
	if($msg=~/error/i || $icon=~/error/i){$intim=10000;}
	#print "Setting BalloonTimeout to $intim\n";
	$mw->{BalloonTimeout}->Interval($intim);
	}
#############
sub BalloonTimeout_Timer{
	#print "BalloonTimeout went off" . time() . "\n";
	$systray->ShowBalloon(0);
	$mw->{BalloonTimeout}->Interval(0);
	}
#############
sub getGroups{
	my %hash=();
	foreach my $host (keys(%xml)){
		my $group=$xml{$host}{group} || "Other";
		$hash{$group}++;
		}
	my @groups=keys(%hash);
	@groups=sortTextArray(@groups);
	return @groups;
	}
#############
sub getDomains{
	my $group=shift || return;
	my %hash=();
	foreach my $host (keys(%xml)){
		my $cgroup=$xml{$host}{group} || "Other";
		if(isEqual($cgroup,$group)){
			my $domain=$xml{$host}{alias} || $xml{$host}{name};
			$hash{$domain}++;
			}
		}
	my @domains=keys(%hash);
	@domains=sortTextArray(@domains);
	return @domains;
	}
#############
sub processButton{
	my $button=shift;
	print "processButton: $button\n";
	if($button=~/^OpenDir$/is){
		my $name=getSelectedValue("Domain") || return 1;
	    my $host=getDomainHost($name) || return 1;
	    my $cdir=$xml{$host}{alias} || $xml{$host}{name};
		#if($xml{$host}{dbname}){$cdir.='_'.$xml{$host}{dbname};}
	    $cdir=~s/[^a-z0-9\s\.\(\)\_\-]+//ig;
	    my $opendir="$baseDir\\$cdir";
		setMessage(getMessage() . "\r\nOpening $opendir");
		my $ok=$mw->ShellExecute('open',$opendir,'','',1);
		return 1;
    	}
    elsif($button=~/^Refresh/is){
		%xml=readXML($xmlfile,'hosts');
		my $name=getSelectedValue("Domain") || return 1;
		createWasqlFiles($name);
    	}
	elsif($button=~/^LocalFile$/is){
		loadLocalPosteditFile();
    	}
    elsif($button=~/^DropList/is){
		my $name=getSelectedValue("Domain") || return 1;
	    my $host=getDomainHost($name) || return;
	    my $chost=$xml{$host}{name};
		$apikey=$xml{$host}{apikey};
		$username=$xml{$host}{username};
		my %postopts=(
			apikey=>$apikey,
			username=>$username,
			_noguid=>1,
			apimethod=>"posteditlist",
			_sub=>"winEvents",
			_path=>"/".$wasql_dir
			);
		my $url="http://$chost/php/index.php";
		#check for alternate port
		if($chost=~/^(.+?)\:([0-9]+)$/s){
			$postopts{_port}=$2;
			$chost=$1;
			$url="http://$chost/php/index.php";
	    	}
	    $mw->{DropBox}->Disable();
	    $mw->{DropList}->Disable();
	    setColorBox('working','Reading ...');
		appendMessage("Reading File list ... $wasql_dir");
		my ($head,$body,$code)=postURL($url,%postopts);
		setColorBox('success','Completed.');
		$mw->{DropBox}->Enable();
		$mw->{DropList}->Enable();
		my @files=split(/[\r\n]+/,$body);
		#setMessage('');
		my $filecnt=@files;
		my $list="--$wasql_dir--\r\n$filecnt files found:\r\n";
		foreach my $file (@files){
			$list .=" - $file\r\n";
        	}
        $mw->{DropBox}->Text($list);
    	}
    elsif($button=~/^About/is){
		return about();
    	}
    elsif($button=~/^Editor$/is){
		#launch their editor and open postedit.xml
		$cmd='start "poseEdit.XML" "' . $settings{editor}{exe}."\" \"$progpath\\postedit.xml\"";
		my @lines=cmdResults($cmd,$settings{editor}{path});
    	}
	return 1;
	}
#############
sub processSelect{
	my $obj=shift;
	my $selname=$obj->{-name};
	#print "processSelect: $selname\n";
	%xml=readXML($xmlfile,'hosts');
	if($selname=~/^Domain$/is){
		my $name=getSelectedValue("Domain") || return 1;
		#checks
		my $host=getDomainHost($name);
		#print " - Name:{$name}, HOST:{$host}\n";
		#print hashValues(\%input);
		my @checks=qw(perl php min);
		foreach my $check (@checks){
			next if $check!~/^(php|perl|min)$/i;
			my $cbname="Check\_$check";
			print " - CBName:$cbname\n";
			if(defined($input{checks}) && $input{checks}=~/\Q$check\E/is){
				$mw->{$cbname}->Checked(1);
			}
			elsif(defined($xml{$host}{checks}) && $xml{$host}{checks}=~/\Q$check\E/is){
				$mw->{$cbname}->Checked(1);
			}
			else{$mw->{$cbname}->Checked(0);}
	    }
	    $mw->{Domain_Label}->Text('Domain/Alias - '.$name);
		#listen for this domain now
		createWasqlFiles($name);
		}
	elsif($selname=~/^Group$/is){
		my $group=getSelectedValue("Group");
		if(!$group){
			$mw->{Domain}->Disable();
			return 1;
			}
		$mw->{Domain}->Enable();
		#print "Group:$group\n";
		$mw->{Domain}->Clear();
		@vals=getDomains($group);
		my $i=0;
		my $pick=-1;
		foreach my $val (@vals){
			$mw->{Domain}->InsertItem($val,$i);
			#print "Inserting Domain:$i - $val\n";
			my $chost=getDomainHost($val);
			my $cname=$xml{$chost}{name};
			my $calias=$xml{$chost}{alias};
			if($input{name} && $input{name}=~/^\Q$cname\E$/i){
				#print "Setting Pick to $i - $cname\n";
				$pick=$i;
				$input{name}='';
				}
			elsif($input{alias} && $input{alias}=~/^\Q$calias\E$/i){
				#print "Setting Pick to $i - $calias\n";
				$pick=$i;
				$input{alias}='';
				}
			$i++;
			}
		if($pick != -1){
			$mw->{Domain}->Select($pick);
			print " - Selected Domain: $pick\n";
			processSelect($mw->{Domain});
        	}
    	}
    elsif($selname=~/^DropDirs$/is){
		$wasql_dir=getSelectedValue("DropDirs");
		if($wasql_dir){
			$mw->{DropBox}->Text("--$wasql_dir--\r\n".$dropboxenabled);
			$mw->{DropBox}->Enable();
			$mw->{DropList}->Enable();
			}
		else{
			$mw->{DropBox}->Text($dropboxtext);
			$mw->{DropBox}->Disable();
			$mw->{DropList}->Disable();
			}
		return 1;
		}
    return 1;
	}
sub dropFilesEvent {
	print "DropBox_DropFiles\n";
    my ($self, $dropObj) = @_;
    $wasql_dir=getSelectedValue("DropDirs");
	if(!$wasql_dir){
		#print " - no wasql_dir\n";
		return 0;
		}
    # Get a list of the dropped file names
    my @files = $dropObj->GetDroppedFiles();
    #print "Files: @files\n";
    my $name=getSelectedValue("Domain") || return 1;
    my $host=getDomainHost($name) || return 1;
    my $chost=$xml{$host}{name};
	$apikey=$xml{$host}{apikey};
	$username=$xml{$host}{username};
    my %postopts=(
		apikey=>$apikey,
		username=>$username,
		_noguid=>1,
		apimethod=>"posteditupload",
		_sub=>"winEvents",
		_path=>"/".$wasql_dir
		);
	my @ufiles=();
	$cnt=0;
	foreach my $file (@files){
		my $key=getFileName($file);
		push(@ufiles,"file_".$cnt,$file);
		$cnt++;
    	}
    $postopts{_file}=[@ufiles];
	my $url="http://$chost/php/index.php";
	#check for alternate port
	if($chost=~/^(.+?)\:([0-9]+)$/s){
		$postopts{_port}=$2;
		$chost=$1;
		$url="http://$chost/php/index.php";
    	}
    #$mw->{DropBox}->Disable();
    #$mw->{DropList}->Disable();
    setColorBox('working','Uploading...');
	appendMessage("Uploading Files to $wasql_dir ...");
	print "Uploading Files to $url\n";
	#print hashValues(\%postopts);
	#return 1;
	my ($head,$body,$code)=postURL($url,%postopts);
	appendMessage("Uploading Completed.");
	setColorBox('success','Completed');
	#$mw->{DropBox}->Enable();
	#$mw->{DropList}->Enable();
    return 1;
  }
#############
sub getSelectedValue{
	my $obj=shift || return '';
    my $index=$mw->{$obj}->SelectedItem();
	if($index==-1){	return $mw->{$obj}->Text();}
	else{return $mw->{$obj}->GetString($index);}
	return '';
	}
#############
sub terminateWindowEvent{
	my $type=0x0004 | 0x0020 | 0x0100;
	$ok=$mw->MessageBox("Exit WaSQL PostEdit Manager?","Exit Confirmation",$type);
	if($ok==7){
		#Do not Exit - No was pushed
		return 0;
		}
	#Yes was pushed
	$mw->Hide();
	return -1;
	}
#############
sub CloseWin_Timer{
	#print "CloseWin Timer\n";
	$CWTimer->Interval(0);
	if(!$mw->Check_min->Checked()){$mw->Minimize();}
	return 1;
	}
##############
sub winEvents{
	my $ok=Win32::GUI::DoEvents();
	if($ok==-1){exit(0);}
	}
#############
sub setMessage{
	my $message=shift;
	$mw->Message->Text($message);
	Win32::GUI::DoEvents();
	return 1;
	}
#############
sub about{
	my $type=0x0000 | 0x0020;
	my $message="$ProductName\r\n$FileDescription\r\n\r\n$CompanyName\r\n\r\nVersion $ProductVersion\r\n\r\n$LegalCopyright";
	$ok=$mw->MessageBox($message,"About $ProductName",$type);
	return 0;
	}
#############
sub alertMessage{
	my $message=shift || return 0;
	my $type=0x0000 | 0x0030;
	$ok=$mw->MessageBox($message,"$ProductName Alert",$type);
	return 0;
	}
#############
sub abortMessage{
	my $message=shift || return 0;
	my $type=0x0000 | 0x0040;
	$ok=$mw->MessageBox($message,"$ProductName Abort",$type);
	$mw->Hide();
	exit(1);
	}
#############
sub appendMessage{
	my $msg=shift;
	my $interval=shift || 0;
	setMessage($msg . "\r\n" . getMessage());
	if($interval){$CWTimer->Interval($interval);}
	}
#############
sub getMessage{
	return $mw->Message->Text();
	}
#############
sub setTitle{
	my $title=shift;
	$mw->MessageTitle->Text('Recent Activity Log - '.$title);
	$mw->Text($title);
	Win32::GUI::DoEvents();
	}
#############
sub getDomainHost{
	my $name=shift;
	foreach my $chost (keys(%xml)){
		if($xml{$chost}{alias}=~/^\Q$name\E$/is){
			return $chost;
        	}
		elsif($xml{$chost}{name}=~/^\Q$name\E$/is){
			return $chost;
        	}
    	}
    return '';
}
#############
sub createWasqlFiles{
	my $name=shift || return;
	print "createWasqlFiles: $name\n";
	my @wasql_dirs=();
	my $wasql_user='';
	my $wasql_group='';
	#determine what host
	my $host=getDomainHost($name) || return 1;
	my $new_lockfile="$progpath/wasql_".$name.".lock";
	if(length($lockfile)!=0 && encodeBase64($lockfile) == encodeBase64($new_lockfile)){
     	#do nothing - just a refresh of the same domain
	}
	else{
		if(-e $new_lockfile){
			my $lockpid=getFileContents($new_lockfile);
			my $proc=getProcessByPid($lockpid);
			if(length($proc->{pid}) && $lockpid != $$){
				my $msg="A $ProductName instance is already running for $name\r\nI am not able to comply [".$$."].\r\n";
				$msg .= hashValues(\%proc);
				alertMessage($msg);
				return 1;
			}
		}
	}
	setColorBox('working','Cleaning...');
	$WDTimer->Interval(0);
	%Watch=();
	my $chost=$xml{$host}{name};
	my $newtitle=$chost;
	my $filter=$mw->{RefreshFilter}->Text();
	if($xml{$host}{alias}){$newtitle=$xml{$host}{alias};}
	if($xml{$host}{group}){$newtitle .= ' ('.$xml{$host}{group}.')';}
	setTitle($newtitle);
	setMessage("Cleaning");
	print " - Cleaning $filesDir\n";
	my $cdir=$xml{$host}{alias} || $chost;
	#if($xml{$host}{dbname}){$cdir.='_'.$xml{$host}{dbname};}
	$cdir=~s/[^a-z0-9\s\.\(\)\_\-]+//ig;
	$filesDir="$baseDir/$cdir";
	if(!-d $filesDir){buildDir($filesDir);}
	else{cleanDir($filesDir,1);}
	#remove directories inside also
	my $tables=$xml{$host}{tables};
	#remove extra dirs that do not match up
	my @tdirs=split(/[\s\,\;]+/,strip($tables));
	my %istdir=();
	foreach my $tdir (@tdirs){
		$istdir{$tdir}=1;
		}
	my @dirs=listDirs($filesDir,0,1);
	foreach my $dir (@dirs){
		if(!$istdir{$dir}){rmDir("$filesDir/$dir");}
    	}
    #check for file attribute
    if($xml{$host}{file}){
    	my $body=getFileContents($xml{$host}{file});
    	my $ok=processPostEditXML($body);
    	return;
	}
	return 1 if !$xml{$host}{apikey};
	return 1 if !$xml{$host}{username};
	$apikey=$xml{$host}{apikey};
	$username=$xml{$host}{username};
	#set key/values to pass to postURL
	my %postopts=(apikey=>$apikey,username=>$username,_noguid=>1,postedittables=>$tables,apimethod=>"posteditxml",_sub=>"winEvents");
	my $url="http://$chost/php/index.php";
	if(length($filter)){$postopts{filter}=$filter;}
	if($xml{$host}{dbname}){$postopts{dbname}=$xml{$host}{dbname};}
	#check for alternate port
	if($chost=~/^(.+?)\:([0-9]+)$/s){
		$postopts{_port}=$2;
		$chost=$1;
		$url="http://$chost/php/index.php";
    	}
    setColorBox('working','Calling API...');
	appendMessage("Calling WaSQL API ...");
	print "calling $url\n";
	#$postopts{_debug}=1;
	#print hashValues(\%postopts);
	my ($head,$body,$code)=postURL($url,%postopts);
	appendMessage("$code - Saving Results");
	#save to $baseXMLDir
	my $cdir=$xml{$host}{alias} || $xml{$host}{name};
	$cdir="$baseXMLDir/$cdir/".getDate('YYYY AM');
	#print "cdir:$cdir\n";
	if(!-d $cdir){buildDir($cdir);}
	setFileContents("$cdir/postedit_".getDate('ND').".xml",$body);
	if($code==200 && $body=~/\<\?xml/is){
		if(-e $lockfile){unlink($lockfile);}
		$lockfile=$new_lockfile;
		$ok=setFileContents($lockfile,$$);
		appendMessage("Parsing XML ...");
		#check for fatal errors from server
		my $ok=processPostEditXML($body);
 		}
 	else{
		#Update Failed - show error
		setColorBox("Fail",'Error');
		appendMessage($body);
		$WDTimer->Interval(0);
		appendMessage("Update Error - $url");
		print hashValues(\%postopts);
    	}
	}
#######################
sub processPostEditXML{
	my $body=shift;
	if($body=~/<fatal_error>(.+?)<\/fatal_error>/is){
		abortMessage($1);
		return 0;
	}
	if($body=~/<wasql_dirs>(.+?)<\/wasql_dirs>/is){
		@wasql_dirs=split(/\,+/,$1);
	}
	setColorBox('working','Parsing...');
	#Build a page hash will all the pages
	%watchtable=();
	appendMessage("Parsing results ...");
	while($body=~m/\<WASQL_RECORD(.*?)>(.+?)\<\/WASQL_RECORD\>/sig){
		my %att=parseAttributes($1);
		my $content=$2;
		my $table=$att{table};
		my $id=$att{_id};
		#print "Getting $table results for record $id\n";
		foreach my $key (keys(%att)){
			$watchtable{$table}{$id}{$key}=$att{$key};
			#print "watchtable{$table}{$id}{$key} = " . $watchtable{$table}{$id}{$key} . "\n";;
		}
		my @xmlfields=split(/\,/,$att{_xmlfields});
		foreach my $field (@xmlfields){
            while($content=~m/\<$table\_$field>(.*?)\<\/$table\_$field\>/sig){
				my $val=removeCDATA($1);
				$watchtable{$table}{$id}{$field}=$val;
            }
        }
    }
    #write the page files
    setColorBox('working','Writing...');
    appendMessage("Writing local files");
    foreach my $table (keys(%watchtable)){
		foreach my $id (keys(%{$watchtable{$table}})){
			Win32::GUI::DoEvents();
			my $fname=$watchtable{$table}{$id}{name};
			$fname=~s/[\ \-\_]+/\_/sg;
			$fname=~s/[^a-z0-9\_]+//isg;
			my @xmlfields=split(/\,/,$watchtable{$table}{$id}{_xmlfields});
			foreach my $field (@xmlfields){
				my $ext=getExtension($watchtable{$table}{$id}{$field},$field);
				my $filename="$fname\.$table\.$field\.$id\.$ext";
				my $pdir="$filesDir\\$table";
				if(!-d $pdir){buildDir($pdir);}
				my $pfile="$filesDir\\$table\\$filename";
				$pfile=~s/\//\\/sg;
				$pfile=strip($pfile);
				if(open(FH,">$pfile")){
					binmode(FH);
					print FH strip($watchtable{$table}{$id}{$field}) . "\r\n";
					close(FH);
					if($watchtable{$table}{$id}{atime} && $watchtable{$table}{$id}{mtime}){
						utime($watchtable{$table}{$id}{atime},$watchtable{$table}{$id}{mtime},$pfile);
					}
					elsif($watchtable{$table}{$id}{ctime} && $watchtable{$table}{$id}{mtime}){
						utime($watchtable{$table}{$id}{ctime},$watchtable{$table}{$id}{mtime},$pfile);
					}
					elsif($watchtable{$table}{$id}{mtime}){
						utime(time(),$watchtable{$table}{$id}{mtime},$pfile);
					}
					appendMessage(" - $filename");
					my @stats=stat($pfile);
					my $mtime=$stats[9];
					#print "Writing [".$pfile."][".$mtime."]\n";
                    $Watch{$pfile}=$mtime;
                }
			}
		}
    }
    setColorBox('success','Completed.');
    $WDTimer->Interval(500);
 	appendMessage("Update Complete.",1000);
 	#wasql files?
	my $wasql_file_cnt=@wasql_dirs;
	if($wasql_file_cnt){
		$mw->{DropDirs}->Enable();
		$mw->{DropDirs}->Clear();
		foreach my $val (@wasql_dirs){
			#print " - Adding Dir:$val\n";
			$mw->{DropDirs}->InsertItem($val);
		}
    }
    else{
		$mw->{DropDirs}->Disable();
    }
    $mw->{Refresh}->Enable();
    $mw->{OpenDir}->Enable();
}
#######################
#mainTimer is a function to manage timers since we are calling them with onTimer
sub mainTimer{
	my ($self,$name)=@_;
	if($name eq 'BalloonTimeout'){
		return BalloonTimeout_Timer();
	}
	elsif($name eq 'CloseWin'){
    	return CloseWin_Timer();
	}
	elsif($name eq 'WatchDir'){
    	return WatchDir_Timer();
	}
	print "mainTimer: $name\n";
}
#############
sub WatchDir_Timer{
	#print "WatchDir_Timer\n";
	winEvents();
	my $name=getSelectedValue("Domain");
	#Restore window if program is launched again
	if(-e "$progpath/$progname\.txt" && unlink("$progpath/$progname\.txt")){
		restoreWindow();
		return 1;
    	}
	$WDTimer->Interval(0);
	my @watchFiles=keys(%Watch);
	my $watchcnt=@watchFiles;
	#print "checking $watchcnt files for changes\r\n";
	foreach my $afile (keys(%Watch)){
		my @stats=stat($afile);
		my $mtime=$stats[9];
		if($Watch{$afile} != $mtime){
            if(length($Watch{$afile})){fileChanged(getFileName($afile));}
            $Watch{$afile}=$mtime;
        	}
        winEvents();
        #select(undef,undef,undef,.015);
    	}
    $WDTimer->Interval(500);
	return 1;
	}
#############
sub fileChanged{
	my $file=shift;
	print "fileChanged:".$file."\n";
	my ($fname,$table,$field,$id,$ext)=split(/\./,$file);
	my $balloonMsg="WaSQL detected the following file has changed:\r\nFilename: $fname\r\n\r\n";
	#print "fileChanged: $fname in $table\n";
	my $name=getSelectedValue("Domain");
	my $key=getDomainHost($name) || return;
	my $host=$xml{$key}{name};
	if($xml{$key}{dbname}){
    	#read-only
    	showBalloon($balloonMsg,"You cannot update this database - using dbname","error");
    	setMessage("file update Failed - using dbname");
		setColorBox("Fail");
		return 0;
	}
	if($xml{$key}{file}){
    	#read-only
    	showBalloon($balloonMsg,"Cannot update Local Filesets - Readonly","error");
    	setMessage("file update Failed - local fileset");
		setColorBox("Fail");
		return 0;
	}

	setMessage("Change: $fname in $table");
	setColorBox('working','File Changed...');
	my $apikey=$xml{$key}{apikey};
	my $username=$xml{$key}{username};
	my $url="http://$host/php/index.php";
	#print " - $url\n";
	#_pages.facebook.body.41.pl
	showBalloon($balloonMsg,"Checking File Content...","warning");
	my $afile=fixPathSlashes("$filesDir\\$table\\$file");
	my $content='';
	if(open(FH,$afile)){
		binmode(FH);
		my @lines=<FH>;
		close(FH);
		$content=join('',@lines);
	}
	else{
    	print "ERROR opening $afile\n\t-".$^E;
    	exit;
	}
	if(!isNum($id)){
		print "[$id] is not a number\r\n";
		return 0;
    	}
	if(!length($content)){
		print "No content for $afile!!!\r\n";
		return 0;
		}
	$content=encodeBase64($content);
	#checks
	if($file=~/\.pl$/is && $mw->{Check_perl}->Checked()){
		setColorBox('working','Perl Syntax');
		appendMessage("Checking Perl Syntax");
		#check for the correct perl syntax before uploading...
		showBalloon($balloonMsg,"Checking Perl Syntax...","warning");
		my @errors=checkPerlSyntax($content);
		if(scalar @errors){
			showBalloon($balloonMsg."@errors","Checking Perl Syntax...Perl Errors!","error");
			#restoreWindow();
			setMessage("Perl Syntax Failed");
			setColorBox("Fail");
			appendMessage("$file\r\n@errors");
			if($settings{sound}{fail}){
				playSound($settings{sound}{fail});
				}
			return 0;
        	}
        setColorBox("Pass");
        appendMessage("Perl Syntax Passed");
    	}
    if($file=~/\.php$/is && $mw->{Check_php}->Checked()){
		setColorBox('working','PHP Syntax');
		appendMessage("Checking PHP Syntax");
		#check for the correct perl syntax before uploading...
		showBalloon($balloonMsg,"Checking PHP Syntax...","warning");
		my @errors=checkPHPSyntax($file,$table);
		if(scalar @errors){
			showBalloon($balloonMsg."@errors","Checking PHP Syntax...PHP Errors!","error");
			#restoreWindow();
			setMessage("PHP Syntax Failed");
			setColorBox("Fail");
			appendMessage("$file\r\n@errors");
			if($settings{sound}{fail}){
				playSound($settings{sound}{fail});
				}
			return 0;
        	}
        setColorBox("Pass");
        appendMessage("PHP Syntax Passed");
    	}
    #
	my $timestamp=$watchtable{$table}{$id}{timestamp};
	my %postopts=(apikey=>$apikey,username=>$username,_noguid=>1,_base64=>1,_id=>$id,timestamp=>$timestamp,_action=>'postEdit',_table=>$table,_fields=>$field,$field=>$content,_return=>'XML',_sub=>"winEvents");
	#check for alternate port
	if($host=~/^(.+?)\:([0-9]+)$/s){
		$postopts{_port}=$2;
		$host=$1;
		$url="http://$host/php/index.php";
    	}
    print hashValues(\%postopts);
	appendMessage("Sending changes to $url ...");
	appendMessage("timestamp=$timestamp");
	#print "timestamp:$timestamp\n";
	sendEditChanges:
	setColorBox('working','Sending Changes');
	showBalloon($balloonMsg,"Sending Changes to Server...","warning");
	my ($head,$body,$code)=postURL($url,%postopts);
	print "body:\n$body\n";
	appendMessage("Return Code: $code");
	if($code != 200){
		$err=strip($head);
		showBalloon($balloonMsg."$err","Sending Changes to Server...Failed!","error");
		appendMessage("Update Failed");
		setColorBox("Fail",'Update Failed');
		appendMessage("$file\r\n$head");
		if($settings{sound}{fail}){
			playSound($settings{sound}{fail});
		}
		return 1;
    	}
    elsif($body=~/<fatal_error>(.+?)<\/fatal_error>/is){
		my $err=strip($1);
		showBalloon($balloonMsg."$err","Sending Changes to Server...Failed!","error");
		#restoreWindow();
		abortMessage($err);
		if($settings{sound}{fail}){
			playSound($settings{sound}{fail});
		}
		return 0;
		}
    elsif($body=~/<error>(.+?)<\/error>/is){
		$err=strip($1);
		showBalloon($balloonMsg."$err","Sending Changes to Server...Failed!","error");
		#restoreWindow();
		appendMessage("$file\r\n$body\n\n");
		appendMessage($err);
		setColorBox("Fail",'Update Error');
		setColorBox("Fail");
		my $type=0x0004 | 0x0030 | 0x0100;
		$ok=$mw->MessageBox($err."\r\n\r\nOverwrite Anyway?","Overwrite Confirmation",$type);
		if($ok==7){
			#Do not Exit - No was pushed
			appendMessage("NOTE: Your changes were not sent.\r\nClick Refresh to update your local files.\r\n");
			return 1;
			}
		else{
			#Yes was pushed
			$postopts{_overwrite}=1;
			goto sendEditChanges;
			}
		return 1;
    	}
    else{
		appendMessage("$file\r\n$body\r\n");
		if($body=~/<timestamp>(.+?)<\/timestamp>/is){
			my $ts=$1;
			$watchtable{$table}{$id}{timestamp}=$ts;
			appendMessage("timestamp updated for $table\.$id to $ts");
			}
		setColorBox("Pass",'Completed');
		appendMessage("Update Succeeded\r\n$file",1000);
		showBalloon($balloonMsg."SUCCESSFULLY Updated $fname in $table","Sending Changes to Server...SUCCESS","info");
		if($settings{sound}{success}){
			playSound($settings{sound}{success});
			}
		}
	return 1;
	}
#############
sub checkPHPSyntax{
	my $file=shift;
	my $table=shift;
	my $cmd=qq|php -l $file|;
	my @errors=();
	my @lines=cmdResults($cmd,"$filesDir\\$table");
	foreach my $line (@lines){
		if($line=~/No syntax errors detected/is){return @errors;}
    	}
	return @lines;
	}
#############
sub checkPerlSyntax{
	my $str=shift;
	my @errors=();
	while ($str=~m/\<perl(.*?)\>(.*?)\<\/perl\>/sig){
		my $code=strip($2);
		$perltags++;
		#remove any beginning and ending comments <!--  -->
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		if(length($code)){
			$code="return;$code";
			eval($code);
			if($@){
				#return the error message.
				my @lines=split(/[\r\n]+/,$@);
				my $err=join("\r\n",@lines);
				push(@errors,$err);
                }
			}
		}
	while ($str=~m/\<\?(.*?)\?\>/sig){
		my $code=strip($1);
		#Do not process xml defination strings as Perl
		next if $code=~/^(xml|php)\ /is;
		$perltags++;
		#remove any beginning and ending comments <!--  -->
		if($code=~/^\<\!\-\-/s){
			$code=~s/^\<\!\-\-//s;
			$code=~s/\-\-\>$//s;
			}
		if(length($code)){
			$code="return;$code";
			eval($code);
			if($@){
				#return the error message.
				my @lines=split(/[\r\n]+/,$@);
				my $err=join("\r\n",@lines);
				push(@errors,$err);
                }
			}
		}
	return @errors;
	}
#############
sub getExtension{
	my $content=shift;
	my $field=shift || '';
	if($field=~/^(body)$/i){return 'html';}
	if($field=~/^(js_min)$/i){return 'js';}
	if($field=~/^(css_min)$/i){return 'css';}
	if($field=~/^(js|css|php|rb|xml)$/i){return lc($field);}
	if($field=~/^(controller|functions)$/i){return 'php';}
	$content=strip($content);
	if($content=~m/^(.{0,5})filetype\:([a-z0-9]+)\*/is){return $2;}
	while($content=~m/<\?(.+?)\?\>/sig){
		my $code=$1;
		if($code=~m/\*filetype\:(.+?)\*/is){return $1;}
		return 'php';
		my @lines=split(/[\r\n]+/,$code);
		foreach my $line (@lines){
			$line=strip($line);
			if($line=~/^(function|echo)\ /){return 'php';}
			if($line=~/^(sub|my|require|use|our)\ /){return 'pl';}
			if($line=~/^(END|BEGIN)/){return 'pl';}
			if($line=~/^\#\!\//){return 'pl';}

        	}
		return "php";
		}
	return "php";
	if(strip($content)=~/^function\ /s){return "js";}
	if(isHtml($content)){return 'html';}
	return 'txt';
	}
######################
sub getScreenDimensions{
#http://msdn.microsoft.com/en-us/library/ms724385.aspx
#Primary Display Monitor Values
	#SM_CXSCREEN = 0         	The width of the screen of the primary display monitor, in pixels.
	#SM_CYSCREEN = 1         	The height of the screen of the primary display monitor, in pixels.
	#SM_CXFULLSCREEN = 16       The width of the client area for a full-screen window on the primary display monitor, in pixels
	#SM_CYFULLSCREEN = 17 		The height of the client area for a full-screen window on the primary display monitor, in pixels.
#Virtual Screen Values - The virtual screen is the bounding rectangle of all display monitors.
	#SM_XVIRTUALSCREEN = 76		The coordinates for the left side of the virtual screen.
	#SM_YVIRTUALSCREEN = 77		The coordinates for the top of the virtual screen.
	#SM_CXVIRTUALSCREEN = 78    The width of the virtual screen, in pixels.
	#SM_CYVIRTUALSCREEN = 79  	The height of the virtual screen, in pixels.
#Misc Useful Values
	#SM_MEDIACENTER = 87 		Nonzero if the current operating system is the Windows XP, Media Center Edition, 0 if not.
	#SM_CMOUSEBUTTONS = 43      The number of buttons on a mouse, or zero if no mouse is installed.
	#SM_CXMINIMIZED = 57		The width of a minimized window, in pixels.
	#SM_REMOTECONTROL = 0x2001	This system metric is used in a Terminal Services environment. Its value is nonzero if the current session is remotely controlled; otherwise, 0.
	#SM_SHUTTINGDOWN = 0x2000	Nonzero if the current session is shutting down; otherwise, 0.
	#SM_TABLETPC = 86			Nonzero if the current operating system is the Windows XP Tablet PC edition, 0 if not.
	my @tmp=(0,1,78,79);
	my @vals=();
	foreach my $c (@tmp){
		my $val = Win32::GUI::GetSystemMetrics($c);
		push(@vals,$val);
		}
	return @vals;
	}
######################
sub setWindowPosition{
	#usage: setWindowPosition($win,top=>5,left=>5) or bottom=>5,right,5 or   center=>[top,middle,bottom,left,right]
	my $mw=shift;
	my %params=@_;
	my ($screen_width,$screen_height,$virtual_width,$virtual_height)=getScreenDimensions();
	my $width=$mw->Width();
	my $height=$mw->Height();
	my $x=0;
	my $y=0;
	if($params{center}){
		my $val=$params{center};
		if($val=~/^(1|middle)$/is){return $mw->Center($params{parent});}
		elsif($val=~/^top$/is){
			$x=int(($screen_width-$width)/2);
			}
		elsif($val=~/^left$/is){
			$y=int(($screen_height-$height)/2);
			}
		elsif($val=~/^right$/is){
			$y=int(($screen_height-$height)/2);
			$x=int($screen_width-$width);
			}
		elsif($val=~/^bottom$/is){
			$y=int($screen_height-$height);
			$x=int(($screen_width-$width)/2);
			}
    	}
    if($params{top}){$y=int($params{top});}
    elsif($params{bottom}){$y=int($screen_height-$height-$params{bottom});}
    if($params{left}){$x=int($params{left});}
    elsif($params{right}){$x=int($screen_width-$width-$params{right});}
    #print "setWindowPosition[@_]: window ($width,$height) pos ($x,$y)\r\n";
    $mw->Change(-pos=>[$x,$y]);
    return 1;
	}
######################
sub restoreWindow{
	$mw->OpenIcon();
	$mw->SetForegroundWindow();
    return 1;
	}
########################
sub setColorBox{
	my $str=shift;
	my $txt=shift;
	#default to grey
	my $color=0xE3DFE0;
	if($str=~/(red|fail)/is){
		$mw->ColorBox->Change(
			-background=>0x0d13ff,
			-text=>$txt || "Failed"
			);
		}
	elsif($str=~/(green|success|pass|done|complete)/is){
		$mw->ColorBox->Change(
			-background=>0x22e21d,
			-text=>$txt || "Passed"
			);
		}
	elsif($str=~/(yellow|working|pending|uploading)/is){
		$mw->ColorBox->Change(
			-background=>0x42fffb,
			-text=>$txt || "Working"
			);
		}
	winEvents();
	}
###############
sub playSound{
	my $sound=shift || "SystemDefault";
	if(-s "$progpath/$sound"){$sound="$progpath/$sound";}
	if(!-s $sound){return;}
	my $repeat=shift || 1;
	for(my $x=0;$x<$repeat;$x++){
    	Win32::Sound::Play($sound,"SND_ASYNC");
		}
	return $repeat;
	}
#############
END{
	if(-e $lockfile){unlink($lockfile);}
}
#############
BEGIN {
	#call exit when CTRL-C is used to abort - this way we cleanup properly
	$SIG{INT} = sub {exit(1);};
	our ($temp_dir,$progpath,$progexe,$LogFile,$progname,$isexe)=('','','','','',0);
	$temp_dir = ( $ENV{TEMP} || $ENV{TMP} || $ENV{WINDIR} || '/tmp' ) . "/p2xtmp-$$";
	$0 = $^X unless ($^X =~ m%(^|[/\\])(perl)|(perl.exe)$%i);
	($progpath) = $0 =~ m%^(.*)[/\\]%;
	$progpath ||= ".";
	$progname=$0;
	if($progname=~/[\/\\]/){
		my @stmp=split(/[\/\\]/,$progname);
		$progname=pop(@stmp);
	}
	$progname=~s/\Q$progpath\E//s;
	$progname=~s/^[\\\/]+//s;
	if($progname=~/(.+?)\.(exe|pl|so)/is){$progname=$1;}
	if ($^X =~ /(perl)|(perl\.exe)$/i) {
		$progexe=$progname . '.pl';
		$isexe=0;
	}
	else{
		#Compiled
		$isexe=1;
		$progexe=$progname . '.exe';
	}
	return;
	#log output to a log file
	$LogFile="$progpath/postedit.log";
	open(STDOUT, "> $LogFile");
	select(STDOUT);
	$|=1;
	open(STDERR, "> $LogFile");
	select(STDERR);
	$|=1;
	if(-e "$progpath/$progname\_exit\.txt"){unlink("$progpath/$progname\_exit\.txt");}
}
