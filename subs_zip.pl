#subs_zip.pl
#ppm install archive-zip
use Archive::Zip qw(:ERROR_CODES :CONSTANTS);

###############
sub unzipFile{
	#usage: unzipFile($file[,$dir]);
	#info: unzips a zip file to $dir , expanding the tree.
	my $file=shift || return 0;
	my $dir=shift;
	my $zip = Archive::Zip->new();
	my $status = $zip->read($file);
	if($status != AZ_OK){return 0;}
	$zip->extractTree('','',$dir);
	return 1;
	}
###############
sub zipFile{
	#usage: zipFile($zipname,$file[,$file2,...]);
	#info: zips a zip file to $dir , expanding the tree.
	my $name=shift || return "zipFile Error: No Name";
	my @files=@_;
	my $zip = Archive::Zip->new() || return $^E;
	foreach my $file (@files){
		my $status;
		if (-d $file){
			#directory
			$status=$zip->addTree( $file, $file);
			if($status != AZ_OK){return 0;}
			}
		else{
			$zip->addFile($file);
			}
		}
	$status = $zip->writeToFileNamed($name);
	my $status = $zip->read($name);
	if($status != AZ_OK){return;}
	return $zip;
	}
###############
return 1;