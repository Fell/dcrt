<?php
/**
 * Dropbox Conflict Resolving Tool
 *
 * This script allows you to scan for conflicted files in your Dropbox folder and automatically resolve any conflicts.
 * Currently, the only supported resolving method is KEEP_LATEST, which always keeps the latest version of a file and
 * deletes any others.
 *
 * WARNING: This script is extremely untested and may behave harmful. Use it at your own risk!
 * KEEP IN MIND:
 * - Absolutely no backups will be made
 * - Don't use this script on shared folders, since it will delete any conflicting files for everyone who has access to
 *   that folder.
 *
 * @author Felix Urbasik
 * @version 0.1
 * @link https://github.com/Fell/dcrt
 * @license Creative Commons Attribution-ShareAlike 4.0 International License
 *
 * This work is licensed under the Creative Commons Attribution-ShareAlike 4.0 International License.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/4.0/.
 */


/**
 * Prints out the usage message.
 */
function usage()
{
    echo "\nUsage: php dcrt.php <command> [path]\n\n";
    echo "Commands:\n";
    echo "  status\tDisplay a list of all conflicted files (read-only)\n";
    echo "  process\tStart the automatic conflict resolving process (be careful!)\n";
    exit;
}

/**
 * Lists all conflicted files under a given path.
 * @param $path
 */
function status($path) 
{
	$dir = opendir($path);
	
	while($file = readdir($dir))
	{
		if(in_array($file, array('.','..')))
			continue;
		
		if(is_dir($path.'/'.$file))
			status($path.'/'.$file);
			
		else if(is_file($path.'/'.$file))
		{
			if($orig = preg_filter('/^(\S*)\ .*\bin Konflikt stehende Kopie\b.*\.(.*)$/i','$1.$2', $file))
			{
				echo "[CONFLICT] ".$orig."\n";
			}
		}
	}
}

/**
 * Starts the automatic conflict resolving for a given path.
 * @param $path
 */
function process($path) 
{
	$dir = opendir($path);
	
	while($file = readdir($dir))
	{
		if(in_array($file, array('.','..')))
			continue;
		
		if(is_dir($path.'/'.$file))
			process($path.'/'.$file);
			
		else if(is_file($path.'/'.$file))
		{
			if($orig = preg_filter('/^(\S*)\ .*\bin Konflikt stehende Kopie\b.*\.(.*)$/i','$1.$2', $file))
			{
				if(!file_exists($path.'/'.$orig))
					echo "  [ERROR] Original file ".$orig." could not be found!\n";
							
				$time_conf = filemtime($path.'/'.$file);
				$time_orig = filemtime($path.'/'.$orig);
				
				if($time_conf > $time_orig)
				{
					//echo "Conflicted is newer!\n";
					echo "[REPLACE] ".$orig."\n";
					unlink($path.'/'.$orig); // Delete original
					rename($path.'/'.$file, $path.'/'.$orig); // Rename newer file
				}
				else
				{
					//echo "Original is newer!\n";
					echo "   [KEEP] ".$orig."\n";
					unlink($path.'/'.$file); // Delete conflicted file -> keep original				
				}
			}
		}
	}
}

/* -------------------------------------------------------------------------- */
/* ENTRY POINT */

echo "\n=== Dropbox Conflict Resolving Tool v0.1 ===\n";

if(!isset($argv[1]))
{
    usage();
}

$path = ".";

if(isset($argv[2]))
	$path = $argv[2];

switch($argv[1])
{
case "status":
	echo "\nSearching for conflicted files in ".$path."\n\n";
	status($path);
	break;
case "process":
	echo "\nResolving conflicts in ".$path."\n\n";
	echo "Method: KEEP_LATEST\n\n";
	process($path);
	break;
case "--help":
case "/?":
case "help":
default:
    usage();
    break;
}


?>