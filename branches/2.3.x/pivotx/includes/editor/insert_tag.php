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

// Generate suggested tags
$minsize=11;
$maxsize=19;
$amount=50;

$tagcosmos = getTagCosmos($amount);

foreach($tagcosmos['tags'] as $key => $value)	{

    // Calculate the size, depending on value.
    $nSize = round($minsize + ($value/$tagcosmos['maxvalue']) * ($maxsize - $minsize));

    $htmllinks[$key] = sprintf("<a style=\"font-size:%spx;\" rel=\"dialogtag\" title=\"%s: %s, %s %s\">%s</a>\n",
    $nSize,
    __('Tag'),
    $key,
    $value,
    __('Entries'),
    str_replace("+"," ",$key)
);
}

$output .= implode(" ", $htmllinks);

if ($amount < $tagcosmos['amount']) {
    // We need to print the 'all' link..
    $output .= sprintf('<em>(<a href="javascript:getAllTags(1000, \'../../\');">%s</a>)</em>', __('all'));
}

$PIVOTX['template']->assign('suggestedtags', $output);
$PIVOTX['template']->assign('target', $target);
$PIVOTX['template']->assign('pivotxsession', $_COOKIE['pivotxsession']);
$PIVOTX['template']->assign('title', __("Insert a Tag"));
$PIVOTX['template']->assign('paths', $PIVOTX['paths']);
$PIVOTX['template']->assign('config', $PIVOTX['config']->getConfigArray() );

$PIVOTX['template']->display("window_insert_tag.tpl");

?>
