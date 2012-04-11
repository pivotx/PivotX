<?php
/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 *
 * ----
 * 
 * Modified by the PivotX team.
 *
 * $Id$
 */

// HTTP headers for no cache etc
header('Content-type: text/plain; charset=UTF-8');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (($_COOKIE['PHPSESSID'] == '') && ($_GET['sess'] != '')) {
    session_id($_GET['sess']);
}

// Make sure we're logged in..
require_once(dirname(__FILE__).'/lib.php');
initializePivotX(false);
$PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

// Settings
$targetDir = $PIVOTX['paths']['cache_path'].'plupload';
$cleanupTargetDir = true; // Remove old files
$maxFileAge = 60 * 60; // Temp file age in seconds

switch ($_GET['type']) {
    case 'image':
    case 'images':
    case 'file':
    case 'files':
        $targetDir = makeUploadFolder();
        $cleanupTargetDir = false;
        break;
}

if (isset($_GET['path']) && ($_GET['path'] != '')) {
    /* Using same user level as in fileOperations (in lib.php) */
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);

    $path = $_GET['path'];

    // Remove some idiotic and unsafe parts of the path
    $path = str_replace('../','',$path);
    $path = str_replace('..\\','',$path);
    $path = str_replace('..'.DIRECTORY_SEPARATOR,'',$path);

    // Don't ever allow uploading outside the images, templates and db folders.
    if (!uploadAllowed($path)) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Uploading to illegal directory."}, "id" : "id"}');
    }

    $targetDir = stripTrailingSlash($path);
    $cleanupTargetDir = false;
}

// 5 minutes execution time
@set_time_limit(5 * 60);
// usleep(5000);

// Get parameters
$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

if (($fileName == '') && isset($_FILES['file']['name'])) {
    $fileName = $_FILES['file']['name'];
}

// Clean the fileName for security reasons
// This *has* to be the same as the javascript one!
//$fileName = preg_replace('/[^a-zA-Z0-9_. -]+/', ' ', $fileName);
$fileName = safeString($fileName,true,'.');

// Make sure the fileName is unique
$previous_fileName = $fileName;
if (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
    $ext = strrpos($fileName, '.');
    $fileName_a = substr($fileName, 0, $ext);
    $fileName_b = substr($fileName, $ext);

    $count = 1;
    while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
        $count++;

    $fileName = $fileName_a . '_' . $count . $fileName_b;
    if ($count > 1) {
        $previous_fileName = $fileName_a . '_' . ($count-1) . $fileName_b;
    }
}

// special hook to allow the javascript to 
if ($_GET['act'] == 'filename') {
    echo $previous_fileName;
    exit();
}


if (false) {
    $dbg  = '';
    $dbg .= 'date: ' . date('Y-m-d H:i:s') . "\n";
    $dbg .= 'targetdir: ' . $targetDir . "\n";
    $dbg .= 'fileName: ' . $fileName . "\n";
    file_put_contents('/tmp/sess.txt',$dbg);
}

// Create target dir
if (!file_exists($targetDir)) {
    @mkdir($targetDir);
}

// Remove old temp files
if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
    if ($cleanupTargetDir) {
        while (($file = readdir($dir)) !== false) {
            $filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

            // Remove temp files if they are older than the max age
            if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge)) {
                @unlink($filePath);
            }
        }
    }

    closedir($dir);
} else {
    die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
}

// Look for the content type header
if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
    $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
}

if (isset($_SERVER["CONTENT_TYPE"])) {
    $contentType = $_SERVER["CONTENT_TYPE"];
}

if (strpos($contentType, "multipart") !== false) {
    /* NB! Plupload currently changes the file type for all uploaded files
       to 'application/octet-stream' - ref http://www.plupload.com/punbb/viewtopic.php?id=58
       Using the PHP Fileinfo extension as a work-around.
    */
    if ($_FILES['file']['type'] == 'application/octet-stream') {
        // Pluplod has probably messed with the file type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); 
            if (!$finfo) {
                debug("Opening fileinfo database failed");
            } else {
                $_FILES['file']['type'] = finfo_file($finfo, $_FILES['file']['tmp_name']);
                finfo_close($finfo);
            }
        }
    }
    // Only allowing user approved file types.
    $allowedtypes = array_map('trim', explode(',', $PIVOTX['config']->get('upload_accept')));
    if (!in_array($_FILES['file']['type'], $allowedtypes)) {
        $msg = sprintf(__("Illegal file type %s uploaded. Check your %s setting."), $_FILES['file']['type'], __('Allow filetypes')); 
        debug($msg);
        die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "'.$msg.'"}, "id" : "id"}');
        // Argh! This die statement is *not* reflected in the upload dialog at all. 
    }
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        // Open temp file
        $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
        if ($out) {
            // Read binary input stream and append it to temp file
            $in = fopen($_FILES['file']['tmp_name'], "rb");

            if ($in) {
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
            } else {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
            fclose($out);
            unlink($_FILES['file']['tmp_name']);
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }
    } else {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
    }
} else {
    // Open temp file
    $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
    if ($out) {
        // Read binary input stream and append it to temp file
        $in = fopen("php://input", "rb");

        if ($in) {
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
        }
        fclose($out);
    } else {
        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
    }
}

// Ensure the uploaded file has the correct file permission.
chmodFile($targetDir . DIRECTORY_SEPARATOR . $fileName);

// FIXME: Add auto_thumbnail 

// Return JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
?>
