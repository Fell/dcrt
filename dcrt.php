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
 * @version 0.2
 * @link https://github.com/Fell/dcrt
 * @license Creative Commons Attribution-ShareAlike 4.0 International License
 *
 * This work is licensed under the Creative Commons Attribution-ShareAlike 4.0 International License.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/4.0/.
 */

define("MODE_STATUS_ONLY", 0);
define("MODE_KEEP_LATEST", 1);

/**
 * Class DCRT
 */
class DCRT
{
    private $regex = array(
        'de' => '/^(\S*)\ \(.*\bin Konflikt stehende Kopie\b.*\)(\.(.+))?$/i',
        'en' => '/^(\S*)\ \(.*\b\'s conflicted copy\b.*\)(\.(.+))?$/i',
    );

    // Settings
    private $mode;
    private $lang = 'de';
    private $path = '.';
    private $shorten = false;

    /**
     * The constructor. Acts as entry-point and controls the program sequence
     * @param $argv Command-line arguments
     */
    public function __construct($argv)
    {
        echo "\n=== Dropbox Conflict Resolving Tool v0.1 ===\n\n";

        // Load the command line
        $data = $this->cmdline($argv);
        foreach($data as $key => $val)
            $this->$key = $val;

        switch($this->mode)
        {
            default:
            case MODE_STATUS_ONLY: echo "Searching for conflicted files (this may take a while)...\n\n"; break;
            case MODE_KEEP_LATEST: echo "Resolving conflicts by keeping the latest version...\n\n"; break;
        }

        $this->process($this->path, $this->mode);
    }


    /**
     * Processes the command line and provides an array containing the data
     * @param $argv The command-line arguments
     * @return array
     */
    private function cmdline($argv)
    {
        $result = array();

        $argc = count($argv);
        for($i = 0;$i < $argc;$i++)
        {
            switch($argv[$i])
            {
                case "-l":
                    if($i + 1 <= $argc - 1)
                    {
                        $i++;
                        if(in_array(strtolower($argv[$i]), array('de','en')))
                            $result['lang'] = $argv[$i];
                        else
                            $this->usage();
                    }
                    else
                        $this->usage();
                    break;
                case "-s":
                    $this->shorten = true;
                    break;
                case "status":
                    if(!isset($result['mode']))
                        $result['mode'] = MODE_STATUS_ONLY;
                    else
                        $this->usage();
                    break;
                case "resolve":
                    if(!isset($result['mode']))
                        $result['mode'] = MODE_KEEP_LATEST;
                    else
                        $this->usage();
                    break;
                default:
                    if(is_dir($argv[$i]))
                        $result['path'] = $argv[$i];
                    break;
            }
        }

        // We need at least a mode
        if(!isset($result['mode']))
            $this->usage();

        return $result;
    }

    /**
     * Prints out the usage message.
     */
    private function usage()
    {
        echo "Usage: php dcrt.php [-l en|de] [-s] <command> [path]\n\n";
        echo "Commands:\n";
        echo "  status\tDisplay a list of all conflicted files (read-only)\n";
        echo "  resolve\tStart the automatic conflict resolving process (be careful!)\n\n";
        echo "Arguments:\n";
        echo "  -l en|de\tSets the language of the conflicted files. Currently\n\t\t'de' and 'en' are supported. Default is: de \n";
        echo "  -s \t\tShortens the output for an 80 characters wide terminal.";
        exit;
    }

    /**
     * Utility function that formats a path to be displayed in the console.
     * @param $path The path
     * @param $file The filename
     * @return string Shortened path with filename
     */
    private function format_path($path, $file)
    {
        // Use consistent slashes
        if(DIRECTORY_SEPARATOR == '/')
            $path = str_replace('\\', '/', $path);
        else if(DIRECTORY_SEPARATOR == '\\')
            $path = str_replace('/', '\\', $path);

        if($this->shorten)
        {
            $path = $this->shorten_path($path, $file);
        }

        return $path.DIRECTORY_SEPARATOR.$file;
    }

    /**
     * Utility function that shortens a path to be displayed in the console without line-breaks.
     * @param $path The path
     * @param $file The filename (needed for witdh calculation)
     * @return string Shortened path
     */
    private function shorten_path($path, $file)
    {
        $flen = strlen($file);
        $res = (strlen($path)>64-$flen) ? substr($path, 0, 64-$flen)."..." : $path;
        return $res;
    }

    /**
     * Starts the automatic conflict resolving procedure for a given path in a given mode. (see mode defines above)
     * @param $path The path
     * @param $mode The mode
     */
    private function process($path, $mode)
    {
        $dir = opendir($path);

        while($file = readdir($dir))
        {
            if(in_array($file, array('.','..')))
                continue;

            if(is_dir($path.'/'.$file))
                $this->process($path.'/'.$file, $mode);

            else if(is_file($path.'/'.$file))
            {
                if($orig = preg_filter($this->regex[$this->lang],'$1.$3', $file))
                {
                    // Check if original file exists
                    if(!file_exists($path.'/'.$orig))
                        echo "  [ERROR] Original file ".$orig." could not be found!\n";

                    // Strip trailing dot if the file has no extension
                    if($orig[strlen($orig)-1] == '.')
                        $orig = substr($orig, 0, strlen($orig)-1);

                    // Resolve the conflict
                    switch($mode)
                    {
                        default:
                        case MODE_STATUS_ONLY: $this->res_status_only($path, $file, $orig); break;
                        case MODE_KEEP_LATEST: $this->res_keep_latest($path, $file, $orig); break;
                    }
                }
            }
        }
    }

    /**
     * Prints the given conflict out.
     * @param $path
     * @param $conflict
     * @param $original
     */
    private function res_status_only($path, $conflict, $original)
    {
        echo "[CONFLICT] ".$this->format_path($path, $original)."\n";
    }

    /**
     * Resolves a conflict using the KEEP_LATEST method.
     * @param $path
     * @param $conflict
     * @param $original
     */
    private function res_keep_latest($path, $conflict, $original)
    {
        $time_conf = filemtime($path.'/'.$conflict);
        $time_orig = filemtime($path.'/'.$original);

        if($time_conf > $time_orig)
        {
            //echo "Conflicted is newer!\n";
            echo "[REPLACE] ".$this->format_path($path, $original)."\n";
            unlink($path.'/'.$original); // Delete original
            rename($path.'/'.$conflict, $path.'/'.$original); // Rename newer file
        }
        else
        {
            //echo "Original is newer!\n";
            echo "   [KEEP] ".$this->format_path($path, $original)."\n";
            unlink($path.'/'.$conflict); // Delete conflicted file -> keep original
        }
    }
}

$dcrt = new DCRT($argv);