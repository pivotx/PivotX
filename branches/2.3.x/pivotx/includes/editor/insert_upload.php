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


require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');

// Include some other files
require_once($pivotx_path.'lib.php');
//require_once($pivotx_path.'modules/module_db.php');
require_once($pivotx_path.'modules/module_i18n.php');
require_once($pivotx_path.'modules/module_lang.php');
require_once($pivotx_path.'modules/module_parser.php');
//require_once($pivotx_path.'modules/module_ipblock.php');
//require_once($pivotx_path.'modules/module_spamkiller.php');
require_once($pivotx_path.'modules/module_snippets.php');
require_once($pivotx_path.'modules/module_tags.php');

initializePivotX(false);

// Make sure the person requesting this page is logged in:
$PIVOTX['session']->isLoggedIn();
$PIVOTX['session']->minLevel(1);

if (isset($_GET['f_target'])) {
	$target= $_GET['f_target'];
} else {
	$target= $_POST['f_target'];
}


$imagename= "";

if (isset($_GET['f_image'])) {
	$imagename = $_GET['f_image'];
} else if ($success) {
	$imagename = $my_uploader->file['name'];
}

// Show a warning if we're on 'localhost'.
$host = parse_url($PIVOTX['paths']['host']);

if ($host['host']=="localhost") {
    $PIVOTX['template']->assign('msg', __("The Uploader does not work well from 'localhost'. Please use the server's (internal) IP-address instead."));
}


$PIVOTX['template']->assign('target', $target);
$PIVOTX['template']->assign('imagename', $imagename);
$PIVOTX['template']->assign('text', $text);
$PIVOTX['template']->assign('pivotxsession', $_COOKIE['pivotxsession']);
$PIVOTX['template']->assign('title', __("Upload a file"));
$PIVOTX['template']->assign('paths', $PIVOTX['paths']);
$PIVOTX['template']->assign('config', $PIVOTX['config']->getConfigArray() );

if($_GET['f_type'] == "file") {
	$PIVOTX['template']->display("window_fileupload.tpl");
} else {
	$PIVOTX['template']->display("window_upload.tpl");
}

?>
