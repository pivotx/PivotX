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

DEFINE('PIVOTX_INWEBLOG', TRUE);

/* Find the current directory. Should we use the same code as is used in 
   lib.php to set $pivotx_path? */
$curr_dir = dirname(__FILE__); 

// First line defense.
if (file_exists("$curr_dir/first_defense.php")) {
    include_once("$curr_dir/first_defense.php");
    block_refererspam();
    block_postedspam();
}

/**
 * Check if the website is offline. Falls thru if user is allowed to see it anyway.
 */
if (is_file("$curr_dir/db/ser_offline.php")) {
    require_once("$curr_dir/offline.php");
    PivotXOffline::showOffline("$curr_dir/db/");
} 

require_once("$curr_dir/lib.php");

/**
 * Make sure we're in the correct folder. PivotX correctly detects if it's called
 * from the current or an adjacent folder. If you call it from another location,
 * set the COMPENSATE_PATH constant, like this:
 *
 * define("COMPENSATE_PATH", "../weblogfolder/pivotx/");
 * require_once("../weblogfolder/pivotx/render.php");
 *
 */
if (strpos($_SERVER['PHP_SELF'], "pivotx/render.php")===false) {
    
    if (file_exists('pivotx') && is_dir('pivotx')) {
        chdir('pivotx');
        $_SERVER['COMPENSATE_PATH'] = "pivotx/";
    } else if (file_exists('../pivotx') && is_dir('../pivotx')) {
        chdir('../pivotx');
        $_SERVER['COMPENSATE_PATH'] = '../pivotx/';
    } else if (defined('COMPENSATE_PATH') && is_dir(COMPENSATE_PATH)) {        
        chdir(COMPENSATE_PATH);
        $_SERVER['COMPENSATE_PATH'] = COMPENSATE_PATH;
    } else {
        echo("<p>Couldn't set the correct path to PivotX automatically. <br />Please use 'COMPENSATE_PATH' in the file that calls render.php. For example:</p>");
        echo("<pre>\ndefine(\"COMPENSATE_PATH\", \"../weblogfolder/pivotx/\");\n\nrequire_once(\"../weblogfolder/pivotx/render.php\");</pre>");
        die();
    }
    
}


initializePivotX();

// If not installed, redirect to the setup page.
if (!isInstalled()) {
    if (strpos($_SERVER['PHP_SELF'], "pivotx/render.php")>0) {
        $location = "index.php";
    } else {
        $location = "pivotx/index.php";
    }
    header("Location: ".$location);
}

// No trailing slashes on the URI, plz.
$_GET['uri'] = stripTrailingSlash($_GET['uri']);

// Check if we need to get the parameters from a 'non crufty' URL..
if (!empty($_GET['rewrite'])) {
    parseRewrittenURL($_GET['rewrite'], $_GET['uri']);
}

// Cleaning user input - safeString-ing all values in the super globals 
// ($_GET, $_POST, $_REQUEST and $_COOKIE) that are used in render.php
cleanUserInput();

/**
 * Check if we need to handle a posted comment or trackback
 */
$trackback = getDefault($PIVOTX['config']->get('localised_trackback_name'), "trackback");
if (!empty($_POST['piv_code'])) {
    require_once(dirname(__FILE__)."/modules/module_comments.php");
    handlePostComment();
} elseif (isset($_GET[$trackback])) {
    if (count($_POST) > 0) {
        require_once(dirname(__FILE__)."/modules/module_trackbacks.php");
        handlePostTrackback($_GET['e'], $_GET['date']);
    } elseif (isset($_GET['getkey'])) {
        require_once(dirname(__FILE__)."/modules/module_trackbacks.php");
        getTracbackKeyJS($_GET['e'], $_GET['date']);
    }
}

// No previewing for users that aren't logged in.
if ($PIVOTX['session']->isLoggedIn() === false) {
    unset($_GET['previewpage']);
    unset($_GET['previewentry']);
}

/**
 * Determine the action we need to take..
 */

// Set 'render weblog' as the default action.
$action = "";
$modifier = array();

// Get a requested 'page' from the URL..
if ( (!empty($_GET['p'])) || (!empty($_GET['previewpage'])) ) {
    $action = "page";
    $modifier['uri'] = $_GET['p'];
}

// Get a requested 'entry' from the URL..
if ( (!empty($_GET['e'])) || (!empty($_GET['previewentry'])) ) {
    $action = "entry";
    $modifier['uri'] = $_GET['e'];
}

// Get a requested 'tag' from the URL..
if (!empty($_GET['t'])) {
    $action = "tag";
    $modifier['uri'] = $_GET['t'];
}

// Get a requested 'searchpage' from the URL..
if (isset($_GET['q'])) {
    $action = "search";
    $modifier['uri'] = getDefault($_POST['q'], $_GET['q']);
}

// Get a requested 'special page' from the URL..
if (!empty($_GET['x'])) {
    $action = "special";
    $modifier['uri'] = $_GET['x'];
}

// Get a requested XML Feed from the URL..
if (!empty($_GET['feed'])) {
    $action = "feed";
    $modifier['feedtype'] = strtolower($_GET['feed']);
    if (isset($_GET['comm']) || ($_GET['content'] == 'comments')) {
        $modifier['feedcontent'] = 'comments';
    } else {
        $modifier['feedcontent'] = 'entries';
    }
}

// Get a requested 'weblog' from the URL.. If action already set, 
// assume it's purpose is to select one of many weblogs.
if (defined('PIVOTX_WEBLOG') || !empty($_GET['w']) || !empty($_POST['w'])) {
    if (defined('PIVOTX_WEBLOG')) {
        $weblog = PIVOTX_WEBLOG;
    } else {
        $weblog = trim( getDefault($_GET['w'], $_POST['w']));
    }
    if ($action == '') {
        $action = "weblog";
        $modifier['uri'] = $weblog;
    } else {
        $modifier['weblog'] = $weblog;
    }
    // Setting an initial weblog (that might be overridden but 
    // currently only by the renderWeblog function).
    $PIVOTX['weblogs']->setCurrent($weblog);
}



/**
 * See if there are any extra modifiers we need to take into account. Might
 * duplicate action detection above since what is modifiers depends on what
 * the action is. ('weblog' is handled specially above.)
 */

// Get a requested 'category' from the URL..
if (isset($_GET['c']) && $_GET['c']!="") {
    $modifier['category'] = $_GET['c'];
}

// Get a requested 'entry' from the URL..
if (isset($_GET['e']) && $_GET['e']!="") {
    $modifier['entry'] = $_GET['e'];
}

// Get a requested 'author' from the URL..
if (isset($_GET['u']) && $_GET['u']!="") {
    $modifier['user'] = $_GET['u'];
}

// Get a requested 'number of' (whatever) from the URL..
if (isset($_GET['n']) && $_GET['n']!="") {
    $modifier['number'] = $_GET['n'];
}

// Get a requested 'offset' from the URL..
if (isset($_GET['o']) && $_GET['o']!="") {
    $modifier['offset'] = $_GET['o'];
}

// Get a requested 'template name' from the URL..
if (isset($_GET['te']) && $_GET['te']!="") {
    $modifier['template'] = $_GET['te'];
}

// Get a requested 'archive' from the URL..
if (isset($_GET['a']) && $_GET['a']!="") {
    $modifier['archive'] = $_GET['a'];
}

// Get a requested 'date' from the URL..
if (isset($_GET['date']) && $_GET['date']!="") {
    $modifier['date'] = $_GET['date'];
}

// If there is no 'modifier' set by this point, do so by setting it to
// the 'root' as selected in the configuration.
if (empty($modifier)) {
    
    $root = getDefault( $PIVOTX['config']->get('root'), "");
    list($root, $root_modifier) = explode(":", $root);

    // Either it's 'p' for 'page', or we fall back to 'w' for 'weblog'
    if ($root == "p") {
        $action = "page";
    } else {
        $action = "weblog";
    }

    $modifier['uri'] = $root_modifier;
    $modifier['root'] = true;
    
    // Note: in module_parser.php : parseTemplate() we set $modifier['home'],
    // to check if we're at the homepage.
    
} else {
    $modifier['root'] = false;
}

// Setting the language and date/time (locale) for the current weblog (that 
// might be overridden but currently only by the renderWeblog and renderEntry 
// functions).
$language = $PIVOTX['weblogs']->get('','language');
$PIVOTX['languages']->switchLanguage($language);
$PIVOTX['locale']->init();

/**
 * Initialise the object that takes care of rendering the page, and then
 * create and render the page.
 */
$PIVOTX['parser'] = new Parser($action, $modifier);

$PIVOTX['parser']->render();
$PIVOTX['parser']->output();

?>
