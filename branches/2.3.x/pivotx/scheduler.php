<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id$
//
// ---------------------------------------------------------------------------

/**
 * The PivotX scheduler script. This file contains the functions that need to
 * be run periodically.
 *
 * For now it's just 
 * - cache cleaning
 * - checks for timed publish entries 
 * - moblogging
 * but we might add other functions here at a later date.
 */

define('PIVOTX_INSCHEDULER', TRUE);

$scheduler['frequency'] = 5*60; // Run every 5 minutes, tops
$scheduler['duration'] = 5; // Run 5 seconds, tops

$scheduler['max_age_template'] = 10*60; // 10 minutes max for template files.
$scheduler['max_age_feed'] = 60*60; // 60 minutes max for feed files.
$scheduler['max_age_zip'] = 24*60*60; // 24 hours for zipped Minify / TinyMCE files.
$scheduler['max_age_image'] = 7*24*60*60; // 7 days for thumbnails.
$scheduler['max_age_other'] = 24*60*60; // 24 hours for other stuff.

if (!isset($_GET['force']) || ($_GET['force'] != 'yes')) {
    header("HTTP/1.0 204 No Content");
    // Make sure the session is started, then flush the headers to output.
    session_start();
    flush();
}

error_reporting(0);

require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/modules/module_moblog.php');
initializePivotX();

$db_path = $PIVOTX['paths']['db_path'];

if (empty($db_path)) {
    debug("Can't run scheduler: Paths not set");
    die();
}

// First, get the timestamp of the last invocation. If it's less than $scheduler['frequency']
// ago, we just quit.
if (file_exists($db_path."scheduler.txt")) {
    $lastrun = implode("", file($db_path."scheduler.txt"));
    $lastrun = intval(trim($lastrun));
} else {
    $lastrun = 0;
}

// Set the new timestamp. 
$now = date("U");

if ( ($_GET['force'] == 'yes') || ($now - $lastrun) >= $scheduler['frequency'] ) {
    
    // Write the new timestamp, if we start doing stuff..
    $fp = fopen($db_path."scheduler.txt", w);
    fwrite($fp, $now);
    fclose($fp);
    
    
    // Here we call the functions that need to be run.
    cleanCache();
    $PIVOTX['db']->checkTimedPublish();
    $PIVOTX['pages']->checkTimedPublish();
    if ($PIVOTX['config']->get('moblog_active')) {
        $moblog = new Moblog();
        $messages = $moblog->execute();
        if (!empty($messages)) {
            debug(implode("\n",$messages));
        }
    }
    
    // Execute a hook, if present.
    $PIVOTX['extensions']->executeHook('scheduler', $dummy);

} else {
    
    // debug("Scheduler: Nothing to do yet.. " . ($now - $lastrun) . " secs. ");

}


// Process the last hook, after we're done with everything else.
$PIVOTX['extensions']->executeHook('after_execution', $dummy);

die();


// ------------------------


/**
 * Go through the cache folder, clean old files.
 */
function cleanCache() {
    global $PIVOTX, $scheduler, $deletecounter, $filecounter;
    
    $filecounter = 0;
    $deletecounter = 0;
    
    $cache_path = $PIVOTX['paths']['cache_path'];
        
    if (empty($cache_path)) {
        debug("Can't run scheduler: Paths not set");
        die();
    }    
        
    cleanCacheFolder($cache_path);
    
    debug("Scheduler: deleted " . intval($deletecounter) . " cache files in ". timeTaken() . " secs. " . intval($filecounter) . " files were checked.");
    
    
}

/**
 * Helper function for cleanCache().
 *
 * @see cleanCache();
 * @param string $path
 */
function cleanCacheFolder($path) {
    global $PIVOTX, $scheduler, $filecounter;
    
    // Make sure we do not take too long..
    if (timeTaken('int')>$scheduler['duration']) { return; }
    
    $d = dir($path);

    while (false !== ($entry = $d->read())) {
        
        if ($entry=="." || $entry==".." || $entry==".svn") {
            continue;
        }
        
        // Recursively go through the sub folders
        if (is_dir($path.$entry)) {
            cleanCacheFolder($path.$entry."/");
            continue;
        }
        
        // Then handle any files in the folder
        $ext = getextension($entry);

        if ( (strpos($entry, "%%")===0) || $ext=="cache") {
            cleanCacheDelete($path, $entry, $scheduler['max_age_template']);
        }
        
        $filecounter++;
        
        if ( $ext=="gz" || $ext=="" || $ext=="zd" || $ext=="zg") {
            cleanCacheDelete($path, $entry, $scheduler['max_age_zip']);
        } else if ( $ext=="mpc") {
            cleanCacheDelete($path, $entry, $scheduler['max_age_feed']);
        } else if ( $ext=="jpg" || $ext=="png" || $ext=="timthumb") {
            cleanCacheDelete($path, $entry, $scheduler['max_age_image']);
        } else {
            cleanCacheDelete($path, $entry, $scheduler['max_age_other']);
        }
        
    }
    
    $d->close();
    
}

/**
 * Helper function for cleanCacheFolder().
 *
 * @see cleanCacheFolder();
 * @param string $path
 * @param string $entry
 * @param int $maxage
 */
function cleanCacheDelete($path, $entry, $maxage) {
    global $deletecounter, $now;

    if (file_exists($path.$entry)) {
        $timestamp = filectime($path.$entry);
        $oldness = $now - $timestamp;

        if ($oldness >= $maxage) {
            // Note: '@' is evil, but this shouldn't throw a warning, when the file
            // is already deleted. Since the scheduler is often run because a user(browser)
            // requests it, there's a non-neglectable chance of this happening.
            @unlink($path.$entry);
            $deletecounter++;
        }
    }
    
}

?>
