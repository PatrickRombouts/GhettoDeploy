<?php
$time_start = microtime(true); 

ini_set("date.timezone", "Europe/Amsterdam"); // https://www.youtube.com/watch?v=rzOzUHYwPms
$runDate = date("YmdHis");
$options = parseParameters(
				array (
					"help",
					"site:",
					"commit::",
					"backup",
					"branch::",
					"deploy",
					"prepare",
					"directory::"
				
				)
				);
				
if (isset($options['directory']))
	$runDate = $options['directory'];

echo "\n";
echo "Batch deployment tool\n";
echo "Assigned directory: {$runDate}\n";
echo "\n";
echo "===========================\n";	
if (count($options) == 0 or isset($options['help']) or (!isset($options['site'])))
{
	echo "Usage: pusher.php --deploy <action>\n";
	echo "Arguments:\n";
	echo "  --site <sitename>: Select specific site\n";
	echo "Optional:\n";
	echo "  --branch <branchname>: Use selected  git branchname\n";
	echo "  --backup: Create a backup \n";
	echo "  --commit <git id>: Use selected commitid as HEAD\n";
	echo "  --deploy: deployment\n";
	echo "  --directory: Use assigned deployment directory\n";
	echo "  --prepare: generate package for deployment\n";
	echo "  --help: Display this helptext\n";
	die();
}

require_once('pusher.config.php');
require_once('housekeeping.php');
if (isset($options['site']))
{
	if (!isset($Configuration['sites'][$options['site']]))
	{
		echo 'Unknown site specified.';
		die();
	}
	
	echo "Site ".$options['site']." selected\n";
	
	// Do checks
	$SiteName = $options['site'];
	$SiteConfig = $Configuration['sites'][$options['site']];
	if ($SiteConfig['deploymethod'] == 'FTP' and !isset ($SiteConfig['FTP']))
	{
		echo 'Error: Deploy method FTP is used but no FTP configuration found, exiting.';
		die();
	}

	if ($SiteConfig['backup'] or isset($options['backup']))
	{
		$backupRootDir = $Configuration['general']['backupdir'].$SiteName.'/'.$runDate."/";
		echo "Creating backup...\n";
		echo " Backup location: ".$backupRootDir."\n";
		mkdir($backupRootDir, 775, true);
		foreach ($SiteConfig['backuppaths'] as $remote => $local)
		{
			if ($SiteConfig['deploymethod'] == 'FTP' and isset ($SiteConfig['FTP']))
			{
				$localPath = $backupRootDir.$local;
				$remotePath = $remote;
				$excludePath = '';
				if (isset($SiteConfig['excludebackuppaths']))
					$excludePath = " -X ".implode(",", $SiteConfig['excludebackuppaths']);
				if (strlen($excludePath > 0))
					$excludePath = substr($excludePath, 0, -1);
				echo "\t".$remotePath."... ";
				exec($Configuration['general']['wget']." -q -N -r -l inf {$excludePath} -nH --ftp-user={$SiteConfig['FTP']['username']} --ftp-password={$SiteConfig['FTP']['password']} -P \"{$localPath}\" ftp://{$SiteConfig['FTP']['hostname']}:{$SiteConfig['FTP']['port']}{$remotePath}");
				echo " Done!\n";
			}
			elseif ($SiteConfig['deploymethod'] == 'SSH' and isset ($SiteConfig['SSH']))
			{
				$localPath = $backupRootDir.$local;
				$remotePath = $remote;
				$excludePath = '';
				if (isset($SiteConfig['excludebackuppaths']))
				{
					foreach ($SiteConfig['excludebackuppaths'] as $path)
					{
						$excludePath = " --exclude \"{$path}\"";
					}
				}
				echo "\t".$remotePath."... ";
				exec($Configuration['general']['rsync']." --rsh \"ssh -p {$SiteConfig['FTP']['port']}\" --recursive --archive {$excludePath} {$SiteConfig['FTP']['username']}@{$SiteConfig['FTP']['hostname']}:{$remotePath} {$localPath}");
				echo "Done!\n";
			}
			else {
				echo 'Error: Deploy method '.$SiteConfig['deploymethod'].' is but we don\'t have backup instructions for that, exiting.';
				die();
			}
		}
		echo "Backup done!\n";
		echo currenttime()." ===========================\n";
		sleep(2);
	}
	if (isset($options['deploy']) or isset($options['prepare']))
	{
		echo "Preparing source {$options['site']} from {$SiteConfig['deploysource']}\n";
		echo "Preparing source data..";
		$deployDirectory = $Configuration['general']['deploydir'].$SiteName.'/'.$runDate."/";
		@mkdir($deployDirectory, 775, true);
		if ($SiteConfig['deploysource'] == "folder")
		{
			copyfolder($SiteConfig['sourcefolder'], $deployDirectory);	
		}
		elseif ($SiteConfig['deploysource'] == "git")
		{
			$gitBranch = '';
			if (isset($options['branch']))
				$SiteConfig['gitbranch'] = $options['branch'];
			if (isset($SiteConfig['gitbranch']) and strlen($SiteConfig['gitbranch']) > 0)	
				$gitBranch = ' --branch '.$options['branch'].' ';
			echo "Pulling from git... ";
			exec ($Configuration['general']['gitexecutable']." clone {$gitBranch} {$SiteConfig['giturl']} \"{$deployDirectory}\"");
			if (isset($options['commit']))
			{
				$ourDir = __DIR__;
				chdir($deployDirectory);
				echo "Applying specific commit... ";
				exec("{$Configuration['general']['gitexecutable']} checkout {$options['commit']}");
				chdir($ourDir);
			}
		}
		else {
			echo 'Error: Deploy source '.$SiteConfig['deploysource'].' is not available, exiting.';
			die();
		}
		sleep(10);
		echo " Done!\n";
		echo currenttime()." ===========================\n";
		sleep(2);
		echo "Housekeeping..\n";
		deleteDirectory($deployDirectory.'.git');
		rdir_cleanup($deployDirectory, true);
		echo currenttime()." ===========================\n";
		sleep(2);
	}
	if (isset($options['deploy']))
	{	
		if (isset($SiteConfig['excludecopypaths']) and count($SiteConfig['excludecopypaths']) > 0)
		{
			echo "Processing exclude paths:\n";
			foreach ($SiteConfig['excludecopypaths'] as $exclude)
			{
				if (substr($exclude, 0, 1) == "/")
				{	// Remove first char slash
					$exclude = substr($exclude, 1);
				}
				if (substr($exclude, -1) == "/") // Directory
				{
					echo "\tDirectory /".$exclude."\n";
					deleteDirectory($deployDirectory.$exclude);
				}
				else {
					echo "\tFile /".$exclude."\n";
					unlink($deployDirectory.$exclude);
				}
			}
			echo "  Done!\n";
			echo currenttime()." ===========================\n";
			sleep(2);
		}
		
		if (isset($SiteConfig['replacefiles']) AND count($SiteConfig['replacefiles']) > 0)
		{
			echo "Replacing files:\n";
			foreach ($SiteConfig['replacefiles'] as $source => $dest)
			{
				if (substr($source, 0, 1) == "/")
				{	// Remove first char slash
					$source = substr($source, 1);
				}
				
				if (substr($dest, 0, 1) == "/")
				{	// Remove first char slash
					$dest = substr($dest, 1);
				}
				
				if (substr($exclude, -1) == "/") // Directory
				{
					echo "\tDirectory /".$source."\n";
					echo "\t\t => /".$dest."\n";
					copy(__DIR__.'/'.$source, $deployDirectory . $dest);
				}
				else {
					echo "\tFile /".$source."\n";
					echo "\t\t => /".$dest."\n";
					copy(__DIR__.'/'.$source, $deployDirectory . $dest);
				}
			}
			echo " Done!\n";
			echo currenttime()." ===========================\n";
			sleep(2);
		}
		
		if (isset($SiteConfig['replacelines']) AND count($SiteConfig['replacelines']) > 0)
		{
			echo "Editing files (lines):\n";
			foreach ($SiteConfig['replacelines'] as $file => $edits)
			{
				if (substr($file, 0, 1) == "/")
				{	// Remove first char slash
					$file = substr($file, 1);
				}
				
				echo "\tFile /".$file."\n";
				$fileContents = file_get_contents( $deployDirectory . $file );
				if (!$fileContents)
				{
					echo 'ERROR@replacelines: Cannot load '.$file;
					die();
				}
				$fileSplit = explode("\n", $fileContents);
				foreach ($edits as $lineNumber => $newText)
				{
						echo "\t{$lineNumber}. {$newText}\n";
						$fileSplit [ $lineNumber - 1 ] = $newText;
				}
				$fileContents = implode("\n", $fileSplit);
				file_put_contents($deployDirectory . $file, $fileContents);
			}
			
			echo " Done!\n";
			echo currenttime()." ===========================\n";
			sleep(2);
		}
		
		echo "Transfering files...\n";
		foreach ($SiteConfig['copypaths'] as $local => $remote)
		{
			if ($SiteConfig['deploymethod'] == 'FTP' and isset ($SiteConfig['FTP']))
			{
				if (substr($local, 0, 1) == "/")
				{	// Remove first char slash
					$local = substr($local, 1);
				}
				if (substr($local, -1) == "/")
				{	// Directory? add a *
					$local = $local.'*';
				}
				$deployDirectory = $Configuration['general']['deploydir'].$SiteName.'/'.$runDate."/";
				$localPath = $deployDirectory.$local;
				$remotePath = $remote;
				
				echo "\t/".$local."... ";
				
				exec($Configuration['general']['ncftpput']." -u {$SiteConfig['FTP']['username']} -p {$SiteConfig['FTP']['password']} -P {$SiteConfig['FTP']['port']} -R -F {$SiteConfig['FTP']['hostname']} \"{$remotePath}\" \"{$localPath}\"");
				echo "\n";
			}
			elseif ($SiteConfig['deploymethod'] == 'SSH' and isset ($SiteConfig['SSH']))
			{
				if (substr($local, 0, 1) == "/")
				{	// Remove first char slash
					$local = substr($local, 1);
				}
				if (substr($local, -1) == "/")
				{	// Directory? add a *
					$local = $local.'*';
				}
				$deployDirectory = $Configuration['general']['deploydir'].$SiteName.'/'.$runDate."/";
				$localPath = $deployDirectory.$local;
				$remotePath = $remote;
				
				echo "\t/".$local."... ";
				
				exec($Configuration['general']['rsync']." --rsh \"ssh -p {$SiteConfig['FTP']['port']}\" --recursive --archive {$localPath} {$SiteConfig['FTP']['username']}@{$SiteConfig['FTP']['hostname']}:{$remotePath}");
				echo "\n";
			}
			else {
				echo 'Error: Deploy method '.$SiteConfig['deploymethod'].' is but we don\'t have deploy instructions for that, exiting.';
				die();
			}
		}
		echo "Transfering files Done!\n";
		echo  currenttime()." ===========================\n";
		sleep(2);
	}
	
	echo currenttime().": All tasks are finished!\n\n";
}

function copyfolder($source, $dest)
{
    if(is_dir($source)) {
        $dir_handle=opendir($source);
        while($file=readdir($dir_handle)){
            if($file!="." && $file!=".." && $file!=".git" && $file!=".svn" && $file!=".gitignore"){
                if(is_dir($source."/".$file)){
                    if(!is_dir($dest."/".$file)){
                        mkdir($dest."/".$file);
                    }
                    copyfolder($source."/".$file, $dest."/".$file);
                } else {
                    copy($source."/".$file, $dest."/".$file);
                }
            }
        }
        closedir($dir_handle);
    } else {
        copy($source, $dest);
    }
}

function deleteDirectory($dirPath) {
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);
        foreach ($objects as $object) {
            if ($object != "." && $object !="..") {
                if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                    deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
		reset($objects);
		rmdir($dirPath);
    }
}

 function parseParameters($noopt = array()) {
        $result = array();
        $params = $GLOBALS['argv'];
        // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
        reset($params);
        while (list($tmp, $p) = each($params)) {
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname{0} == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    }
	
	
function currenttime()
{
	global $time_start;
	$seconds = microtime(true) - $time_start;
	$minutes = 0;
	while ($seconds >=60 )
	{
		$seconds = $seconds - 60;
		$minutes++;
	}
	return sprintf("%1d:%2$02d", $minutes, $seconds);	
}
