<?php

// Disabling all warnings from PHP
error_reporting(0);  // always leave this line active

// Determince the directory containing PivotX.
$pivotx_parent_directory = dirname(dirname(dirname(__FILE__))) . '/';

// Defaults (or hard code your changes)
// Zoomcrop value
$default_zc    = 1;
$upload_folder = 'images/';  //  this is relative to the directory containing PivotX - do not start with a slash

$src_string = $_GET['src'];

// Allow for base64 encoded src (as used by the Image Tools extension).
if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $src_string)) {
    $_GET['src'] = $src_string = base64_decode($src_string);
}

// If the src parameter isn't an URL, we assume it's a PivotX image and
// we set the timthumb LOCAL_FILE_BASE_DIRECTORY config var.
if(!preg_match('/^https?:\/\/[^\/]+/i', $src_string)){
    // Remove the path to the upload_folder from the src parameter, if present.
    if (strpos($src_string, $upload_folder) === 0) {
        $_GET['src'] = substr($src_string,strlen($upload_folder));
    } else if (strpos($src_string, '/' . $upload_folder) !== false) {
        list($prepath, $src_string) = explode($upload_folder, $src_string);
        $_GET['src'] = $src_string;
    }
    // Set base folder taking multisite into account. We must include the multisite module
    // since we haven't initialized PivotX. We also have to set $pivotx_path since it's 
    // used by the module (and expected to be set).
    include_once($pivotx_parent_directory . 'pivotx/modules/module_multisite.php');
    if (class_exists('MultiSite')) {
	$pivotx_path = $pivotx_parent_directory . 'pivotx/';
        $multisite = new MultiSite();
        if ($multisite->isActive()) {
            $upload_folder = 'pivotx/' . $multisite->getPath() . $upload_folder;
        }
    }
    define ('LOCAL_FILE_BASE_DIRECTORY', $pivotx_parent_directory . $upload_folder);
}
// Set the other wanted timthumb config vars (see description in timthumb for possible values)  
define ('FILE_CACHE_DIRECTORY', '../' . $sites_path . 'db/cache/thumbnails/');   
define ('FILE_CACHE_SUFFIX', '.timthumb');  
define ('FILE_CACHE_TIME_BETWEEN_CLEANS', -1);
define ('DEFAULT_ZC', $default_zc);
define ('NOT_FOUND_IMAGE', 'timthumb-notfnd.jpg'); 
define ('ERROR_IMAGE', 'timthumb-error.jpg'); 

?>
