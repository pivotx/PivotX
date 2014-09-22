<?php

/**
 * Contains the PivotX debug functions.
 *
 * @package pivotx
 * @subpackage modules
 */

global $logfile;
global $debug_log;

// Determine the logfile location
if(!defined('INPIVOTX')){
    // If accessed directly, we must find the db path manually.
    if(realpath(__FILE__)=="") {
        $pivotx_path = dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME'])))."/";
    } else {
        $pivotx_path = dirname(dirname(realpath(__FILE__)))."/";
    }
    $pivotx_path = str_replace("\\", "/", $pivotx_path);
    require_once($pivotx_path.'modules/module_multisite.php');
    $multisite = new MultiSite();
    if ($multisite->isActive()) {
        $sites_path = $multisite->getPath();
    } else {
        $sites_path = '';
    }
    $logfile = $pivotx_path . $sites_path . 'db/logfile.php';
} else {
    $logfile = $PIVOTX['paths']['db_path'] . 'logfile.php';
}


/**
 * If called directly, display the debug log.
 */
if (basename( $_SERVER['PHP_SELF'] ) == "module_debug.php"){
    
    // Check if we're logged in. 
    require_once(dirname(dirname(__FILE__)).'/lib.php');
    initializePivotX();
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);
    
    // Show the log..
    view_log();
}

/**
 * Displays the debug log
 *
 */
function view_log() {
    global $logfile;

    if ((isset($_GET['clear'])) && (file_exists($logfile) )) {
        $fp = fopen($logfile,"w");
        fputs($fp, '<'.'?php /* pivotx */ die(); ?'.'>');
        fclose($fp);
        header("Location: module_debug.php");
        die();
    }

    echo <<<EOM
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8" /> 
        <title>PivotX debug window</title>
        <style>

        body {
            font-family: Andale Mono, "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 13px;
            background-color: #EEE;
            color: #333;
        }

        .timetaken {
            font-size: 10px;
            background-color: #DDD;
            margin: 12px 0px 4px 0px;
        }

        </style>
    </head>

    <body onload="self.focus();">
EOM;

    if (file_exists($logfile)) {
        $file = implode("",file($logfile));
        $file = nl2br(str_replace("  ", "&nbsp; ", $file));
        $file = str_replace("<?php /* pivotx */ die(); ?>", "", $file);
    } else {
        $file = "";
    }
    echo $file;

    echo "<br /><div id='bottom'><a href='module_debug.php?rand=".rand(1000,9999)."#bottom'>reload</a> - <a href='module_debug.php?clear=1'>clear</a></div>";
    echo "</body>\n</html>\n";

    debug_sep("Viewlog");

}



/**
 * If debug is enabled, this will open the file to which
 * the debug log is written. If the file is "old" it will be
 * reset, otherwise it's opened in append-mode.
 *
 */
function open_debug() {
    global $debug_fp, $PIVOTX, $logfile;

    if ( $PIVOTX['config']->get('debug')==1 ) {

        if (file_exists($logfile)) {
            $ch_time = filemtime($logfile);
        } else {
            $ch_time = 0;
        }

        $nu_time = time();

        if ( ($nu_time - $ch_time) > 300) {
            // reset the logfile..
            $debug_fp = fopen( $logfile, "w");
            fputs($debug_fp, '<'.'?php /* pivotx */ die(); ?'.'>');
        } else {
            // append to the logfile..
            $debug_fp = fopen( $logfile, "a");
        }

        // If we can't write to the debug file we die();
        if (!$debug_fp) {
            echo "Couldn't open logfile ".$logfile." - b0rk!";
            die();
        }
    }
}


/**
 * Prints a line of output to the debug window
 *
 * @param string $output
 */
function debug($output) {
    global $debug_fp, $debug_last, $PIVOTX, $debug_log;


    if ( $PIVOTX['config']->get('debug')==1 ) {


        if (!$debug_fp) {
            open_debug();
        }

        $date = date("Y-m-d H:i:s");

        if(function_exists('memory_get_usage')) {
            $mem = " ( ". memory_get_usage() ." ) ";
        } else {
            $mem = "";
        }


        // fix the filename
        if (function_exists("debug_backtrace")) {
            $backtrace = debug_backtrace();
            $file = basename(dirname($backtrace[0]['file'])). '/' . basename($backtrace[0]['file']);
            $line = $backtrace[0]['line'];
            $function = $backtrace[1]['function'];
        }

        if (function_exists("timeTaken")) {
            $timetaken = timeTaken();
        } else {
            $timetaken = 0;
        }

        if ( ($file.$function) != $debug_last ) {
            $output = sprintf("<div class='timetaken'>%s - %s -- %s:%s / %s() --  %s </div>%s\n",
                        $date, $timetaken, $file, $line, $function, $mem, $output);
            $debug_last = $file.$function;
        } else {
            $output = sprintf("%s\n", $output);
        }

        fwrite( $debug_fp, $output );

        $debug_log .= $output;

    }
}



/**
 * Prints a nice seperator to the debug window
 *
 * @param string $output
 */
function debug_sep($output) {
    global $PIVOTX;

    if ( isset($config) && ($PIVOTX['config']->get('debug')==1) ) {

        $sep = "=====================================================================================";

        $len = strlen($output);

        $output = substr($sep, 0, (33 - floor($len / 2)) ) ."  ". trim($output) . "  ". substr($sep, 0, (33 - ceil($len / 2)));

        debug( "<b>".$output."</b>");

    }
}


/**
 * Prints a 'print_r' to the debug window
 *
 * @param string $output
 */
function debug_printr($array) {
    global $PIVOTX;

    if ( $PIVOTX['config']->get('debug')==1 ) {

        debug(print_r($array, true));

    }
}



/**
 * Prints the debug backtrace in readable form to the output window.
 *
 * @param bool $override
 * @return string
 *
 */
function debug_printbacktrace($override=false) {
    global $PIVOTX;

    if(!function_exists('debug_backtrace')) {
        debug('function debug_backtrace does not exist.');
        return 'function debug_backtrace does not exist.';
    }

    $MAXSTRLEN = 50;

    if ( ($PIVOTX['config']->get('debug')==1) || $override )  {

        // fix the filename
        if (!isset($parms['file'])) {
            $backtrace = debug_backtrace();
            $parms['file'] = basename(dirname($backtrace[0]['file'])). '/' . basename($backtrace[0]['file']);
            $parms['line'] = $backtrace[0]['line'];
        }

        ob_start();

        $my_trace = array_reverse(debug_backtrace());

        foreach($my_trace as $t)    {
            echo '&raquo; ';
            if(isset($t['file'])) {
                $line = basename(dirname($t['file'])). '/' .basename($t['file']) .":" . $t['line'];
                printf("%-30s", $line);
            } else {
                // if file was not set, I assumed the functioncall
                // was from PHP compiled source (ie XML-callbacks).
                $line = '<PHP inner-code>';
                printf("%-30s", $line);
            }

            echo ' - ';

            if(isset($t['class'])) echo $t['class'] . $t['type'];

            echo $t['function'];

            if (isset($t['args']) && sizeof($t['args']) > 0) {
                $args= array();
                foreach($t['args'] as $arg) {
                    if (is_null($arg)) $args[] = 'null';
                    else if (is_array($arg)) $args[] = 'Array['.sizeof($arg).']';
                    else if (is_object($arg)) $args[] = 'Object:'.get_class($arg);
                    else if (is_bool($arg)) $args[] = $arg ? 'TRUE' : 'FALSE';
                    else if ( (is_int($arg))||(is_float($arg)) ) $args[] = $arg;
                    else {
                        $arg = (string) @$arg;
                        $str = htmlspecialchars(substr($arg,0,$MAXSTRLEN));
                        $str = str_replace("\n", "", str_replace("\r", "", $str));
                        $str = preg_replace("/(\s)/i", " ", $str);
                        if (strlen($arg) > $MAXSTRLEN) $str .= '~';

                        $args[] = "'".$str."'";
                    }
                }

                echo '( ' . implode(" , ", $args) .  ' )';
            }   else {
                echo '()';
            }

            echo "\r\n";
        }

        $output = ob_get_contents();
        ob_end_clean();

        debug($output);

        return $output;

    }
}

function debug_var_dump($var, $maxlevel=2, $level=0) {

    debug_printr(debug_var_dump_helper($var, $maxlevel, $level));

}

function debug_var_dump_helper($var, $maxlevel, $level){

    if(!is_array($var)) {
        return "string: ".strlen($var);
    } else {


        if ($level<$maxlevel) {

            if(count($var)>0) {
                foreach($var as $key => $sub) {
                    $res[$key] = debug_var_dump_helper($sub, $maxlevel, ($level+1));
                }
                $res[] = sprintf("total: %s, count: %s",
                    debug_var_dump_helper($var, 0, 1),
                    count($var)
                );
            } else {
                $res = sprintf("total: %s, count: %s",
                    debug_var_dump_helper($var, 0, 1),
                    count($var)
                );
            }
        } else if ($level==$maxlevel) {

            $res = sprintf("total: %s, count: %s",
                debug_var_dump_helper($var, 0, 1),
                count($var)
            );
        } else {
            $res = 0;
            foreach($var as $key => $sub) {
                $res += strlen($key) + debug_var_dump_helper($sub, $maxlevel, ($level+1));
            }
        }
        return $res;
    }

}


/**
 * If debug is enabled this function handles the errors and warnings
 *
 * @param integer $errno
 * @param string $errmsg
 * @param string $filename
 * @param integer $linenum
 * @param array $vars
 */
function userErrorHandler ($errno, $errmsg, $filename, $linenum, $vars) {

     $replevel = error_reporting();
     if( ( $errno & $replevel ) != $errno )
     {
       // we shall remain quiet.
       return;
     }

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are 2,8,256,512 and 1024
    $errortype = array (
        1   =>  "Error",
        2   =>  "Warning",
        4   =>  "Parsing Error",
        8   =>  "Notice",
        16  =>  "Core Error",
        32  =>  "Core Warning",
        64  =>  "Compile Error",
        128 =>  "Compile Warning",
        256 =>  "User Error",
        512 =>  "User Warning",
        1024=>  "User Notice"
    );

    // set of errors for which a var trace will be saved
    $filename = basename(dirname($filename)) . "/" . basename( $filename );

    $err = sprintf("<b>%s</b>: %s. (in <em>'%s'</em> on line %s)", $errortype[$errno], $errmsg, $filename, $linenum);

    debug($err);

}

function print_debuglink() {
    global $PIVOTX;

    if ($PIVOTX['config']->get('debug')==1) {
$link = "void(debugwin = window.open('includes/modules/module_debug.php#bottom', 'debugwin', 'status=yes, scrollbars=yes, resizable=yes, width=600, height=300')); return false;";
        echo "<small><a href='includes/modules/module_debug.php#bottom' onclick=\"$link\">debug</a></small>";

    } else {

        echo "no";

    }

}


// If debug is set, we set up the custom error handler..
if ( isset($PIVOTX['config']) && $PIVOTX['config']->get('debug')==1) {
    ini_set("display_errors", "1");
    error_reporting (E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    $old_error_handler = set_error_handler("userErrorHandler");
} else {
    
    if ( isset($PIVOTX['config']) && $PIVOTX['config']->get('suppress_errors')==1) {
        error_reporting( E_ERROR );        
    } else {
        error_reporting( E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    }
}


?>
