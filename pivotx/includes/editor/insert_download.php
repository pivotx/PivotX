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

define('PIVOTX_INEDITOR', TRUE);
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');
initializePivotX(false);

// Make sure the person requesting this page is logged in:
$PIVOTX['session']->isLoggedIn();
$PIVOTX['session']->minLevel(1);


if (isset($_GET['f_target'])) {
	$target= $_GET['f_target'];
} else {
	$target= $_POST['f_target'];
}

if (isset($_GET['f_text'])) {
	$text= urldecode($_GET['f_text']);
} else {
	$text= $_POST['f_text'];
}


if ( $_GET['f_hasthumb'] == "1") {
	$PIVOTX['template']->assign('thumb', "checked='checked'");
	$PIVOTX['template']->assign('notthumb', "");
} else {
	$PIVOTX['template']->assign('thumb', "");
	$PIVOTX['template']->assign('notthumb', "checked='checked'");
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
$PIVOTX['template']->assign('title', __("Insert a Download"));
$PIVOTX['template']->assign('paths', $PIVOTX['paths']);
$PIVOTX['template']->assign('config', $PIVOTX['config']->getConfigArray() );

$PIVOTX['template']->display("window_insert_download.tpl");

?>
