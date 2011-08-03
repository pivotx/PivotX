<?php

/**
 * Contains support functions used by PivotX.
 *
 * @package pivotx
 */

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

DEFINE('INPIVOTX', TRUE);

$version = "2.3";
$codename = "beta";
$svnrevision = '$Rev$';

$minrequiredphp = "5.2.0";
$minrequiredmysql = "4.1";
$dbversion = "11"; // Used to track if it's necessary to upgrade the DB.

$timetaken = array('query_count'=>0, 'sql'=>0, 'template_count'=>0, 'templates'=>0);

if (!empty($codename)) {
    $build = sprintf("PivotX - %s: <span>%s</span>", $version, $codename);    
} else {
    $build = sprintf("PivotX - %s", $version);
}

// some global initialisation stuff
if(realpath(__FILE__)=="") {
    $pivotx_path = dirname(realpath($_SERVER['SCRIPT_FILENAME']))."/";
} else {
    $pivotx_path = dirname(realpath(__FILE__))."/";
}
$pivotx_path = str_replace("\\", "/", $pivotx_path);

// Ensure that $_GET and $_POST are arrays (to avoid PHP warnings).
// At least on some server $_POST is simply empty.
if (!is_array($_POST)) {
    $_POST = array();
}
if (!is_array($_GET)) {
    $_GET = array();
}

// Include some other files
require_once($pivotx_path.'includes/compat.php');
require_once($pivotx_path.'modules/module_db.php');
require_once($pivotx_path.'modules/module_i18n.php');
require_once($pivotx_path.'modules/module_lang.php');
require_once($pivotx_path.'modules/module_ipblock.php');
require_once($pivotx_path.'modules/module_spamkiller.php');
require_once($pivotx_path.'modules/module_tags.php');
require_once($pivotx_path.'modules/module_extensions.php');
require_once($pivotx_path.'modules/module_messages.php');
require_once($pivotx_path.'modules/module_multisite.php');
require_once($pivotx_path.'modules/module_parser.php');
require_once($pivotx_path.'modules/module_search.php');

require_once($pivotx_path.'modules/module_outputsystem.php');
require_once($pivotx_path.'modules/module_upload.php');
require_once($pivotx_path.'forms.php');
require_once($pivotx_path.'objects.php');
require_once($pivotx_path.'data.php');
require_once($pivotx_path.'pages.php');
require_once($pivotx_path.'offline.php');
require_once($pivotx_path.'modules/module_smarty.php');
require_once($pivotx_path.'modules/formclass.php');
require_once($pivotx_path.'modules/module_sql.php');

// Start the timer:
$starttime=getMicrotime();

/**
 * Initializes PivotX: set up the global $PIVOTX object.
 *
 * (Form will be initialized when needed, Smarty is initialized in
 * modules/module_smarty.php)
 *
 */
function initializePivotX($loadextensions=true) {
    global $PIVOTX;

    // Make sure we initialize only once.
    if (!empty($PIVOTX['users'])) {
        return;
    }

    // FIXME - add CheckSanity as in Pivot 1.40.2
    $PIVOTX['multisite'] = new MultiSite();
    if ($PIVOTX['multisite']->isActive()) {
        checkDB($PIVOTX['multisite']->getPath());
        $PIVOTX['config'] = new Config($PIVOTX['multisite']->getPath());
    } else {
        checkDB();
        $PIVOTX['config'] = new Config();
    }

    // If we have magic_quotes, remove all of them at once!   
    // Some servers do 'magic_quotes', without a way of detecting it apparently. In this case you can 
    // use the hidden setting for 'always_stripslashes'.  
    if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
       || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")) 
       || ($PIVOTX['config']->get('always_stripslashes')==1) ){
        stripSlashesDeep($_GET);
        stripSlashesDeep($_POST);
        stripSlashesDeep($_REQUEST);
        stripSlashesDeep($_COOKIE);
    } 
    
    if ($PIVOTX['multisite']->isActive()) {
        setPaths($PIVOTX['multisite']->getPath());
    } else {
        setPaths();
    }
    checkPaths();

    require_once($PIVOTX['paths']['pivotx_path'].'modules/module_debug.php');

    // Check if the admin pages should be run over HTTPS
    if (defined('PIVOTX_INADMIN') && empty($_SERVER['HTTPS']) && 
            ($PIVOTX['config']->get('force_admin_https')==1)) {
        $location = "https://" . $_SERVER['HTTP_HOST'] .
            $PIVOTX['paths']['pivotx_url'] . 'index.php';
        header("Location: ".$location);
        exit;
    }


    $PIVOTX['session'] = new Session();
    $PIVOTX['users'] = new Users();
    $PIVOTX['messages'] = new Messages();
    $PIVOTX['events'] = new Events();

    if (!isInstalled()) {
        // We shouldn't initialize more stuff before the setup is complete, 
        // but we need the list of languages.
        $PIVOTX['languages'] = new Languages();
        return;
    }

	// Check the database version, and perform updates if needed.
	checkDBVersion();

    // If we are in the backend, we must load languages first so weblogs 
    // and categories get the correct settings based on the default language.
    if (defined('PIVOTX_INADMIN')) {
        $PIVOTX['languages'] = new Languages();
        $PIVOTX['locale']  = new px_Locale();
        $PIVOTX['weblogs'] = new Weblogs();
        $PIVOTX['categories'] = new Categories();
    } else {
        $PIVOTX['weblogs'] = new Weblogs();
        $PIVOTX['categories'] = new Categories();
        $PIVOTX['languages'] = new Languages();
        $PIVOTX['locale']  = new px_Locale();
    }

    if ($loadextensions) {
        $PIVOTX['extensions'] = new Extensions();
    }

    // Loading entries and pages - the actual content.
    $PIVOTX['db'] = new db();
    $PIVOTX['pages'] = new Pages();

    $PIVOTX['cache'] = new SimpleCache();
    
    if ($loadextensions) {
        $PIVOTX['extensions']->executeHook('after_initialize', $dummy);
    }
    
    // Set force_compile and debug. We do this here, because earlier the 'config' isn't yet initialised..
    $PIVOTX['template']->force_compile = $PIVOTX['config']->get('smarty_force_compile');
    $PIVOTX['template']->debugging = $PIVOTX['config']->get('debug');
}


/**
 * Checks whether PivotX is installed/setup.
 *
 * @return boolean
 */
function isInstalled() {
    global $PIVOTX;
    // Currently using the number of users as an indicator.
    if ($PIVOTX['users']->count()==0) {
        return false;
    } else {
        return true;
    }
}

/**
 * Returns our SVN Revision number
 *
 * @return integer
 */
function getSvnRevision() {
    global $svnrevision;
    
    $id = substr($svnrevision, 6);
    return intval(substr($id, 0, strlen($id) - 2));
}



/**
 * Determines which page needs to be shown, and calls the handler for that page
 *
 */
function displayPage() {
    global $PIVOTX;

    $page = getDefault($_GET['page'], "");
    // If we're not logged in, we're shown the login screen.
    if ( ( $PIVOTX['session']->isLoggedIn() === false) || ($PIVOTX['users']->count()==0) ) {

        // Unless we're requesting the bookmarklet page, show the login screen.
        if(!in_array($page, array('bookmarklet', 'm_login', 'login'))) {

            // If another page was requested, redirect there after login if
            // 1) the page exists and
            // 2) returnto isn't already set
            if ($page != '') {
                $function = 'page' . ucfirst(strtolower(safeString($page)));
                $returnto = getDefault($_GET['returnto'], $_POST['returnto']);
                if (($returnto == '') && function_exists($function)) {
                    $_GET['returnto'] = makeAdminPageLink() . '?' . $_SERVER['QUERY_STRING'];
                }
            }
            $page="login";
        } 
    }

    // Check to see if we're on a mobile device. If so, make sure we see one of the
    // mobile pages.
    if (isMobile() && ( substr($page, 0, 2)!="m_" && $page!="login") ) {
        $page = "m_dashboard";
    }


    // Determine which page should be shown.
    if ($page == "") {

        $function = 'pageDashboard';
        $PIVOTX['template']->assign('currentpage', "dashboard");

    } else {

        $function = 'page' . ucfirst(strtolower(safeString($page)));
        $PIVOTX['template']->assign('currentpage', strtolower(safeString($page)));

    }

    // we should allow rewrite's to happen in the PivotX backend
    $PIVOTX['template']->allowRewriteHtml();

    // Call the correct function, if it exists
    if (function_exists($function)) {

        $function();

    } else {

        // .. or else show an error page.
        $PIVOTX['template']->assign('title', __("Oops."));
        $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
        $PIVOTX['messages']->addMessage("The page '<tt>".htmlspecialchars($page)."</tt>' does not exist.");
        renderTemplate('generic.tpl');

    }


}




/**
 * Display template.
 *
 * @param string $template
 * @param array $page
 */
function renderTemplate($template, $page="") {
    global $build, $PIVOTX, $timetaken;

    // If the debug framework isn't loaded we handle some issues here so the 
    // template is rendered correctly.
    if (!function_exists('debug')) {
        ini_set("display_errors", "0");
    }

    if (($_GET['update']) || (count($_POST)>1) ) {
        // Force uncachen..
        $PIVOTX['template']->caching = false;
        $PIVOTX['template']->force_compile = true;
    }

    // Set the messages and warnings..
    if (isset($PIVOTX['messages'])) {
        $PIVOTX['template']->assign('messages', $PIVOTX['messages']->getMessages() );
        $PIVOTX['template']->assign('warnings', $PIVOTX['messages']->getWarnings() );
    }

    // If safemode is enabled, set an error..
    if ($PIVOTX['extensions']->safemode) {
        $PIVOTX['template']->assign("error", sprintf("<p>%s</p>\n<p>%s</p>\n",
            __("PivotX is running in 'safe mode' now. This means all extensions are disabled until you remove the file <tt>pivotxsafemode.txt</tt> from the <tt>pivotx/</tt> folder. "),
            __("If an extension is breaking your PivotX, you can disable the extensions from the Extensions Setup screen, and then leave safe mode to try again. "))
        );
    }

    // Assign some other global stuff to smarty
    $PIVOTX['template']->assign('build', $build);
    $PIVOTX['template']->assign('version', $version);        
    $PIVOTX['template']->assign('codename', $codename);
    $PIVOTX['template']->assign('year', date("Y"));
    if (isset($PIVOTX['config'])) {
        $PIVOTX['template']->assign('svnbuild', getSvnRevision() . "-" . $PIVOTX['config']->get('db_version') );    
        $PIVOTX['template']->assign('now', formatDate('', $PIVOTX['config']->get('fulldate_format')));
        $PIVOTX['template']->assign('config', $PIVOTX['config']->getConfigArray() );
    }
    if (isset($PIVOTX['session'])) {
        $PIVOTX['template']->assign('user', $PIVOTX['session']->currentUser());
    }
    $PIVOTX['template']->assign('timetaken', timeTaken() );
    $PIVOTX['template']->assign('memtaken', getMem() );
    $PIVOTX['template']->assign('paths', $PIVOTX['paths']);
    if ($PIVOTX['db']->db_type == "sql") {
        $PIVOTX['template']->assign('query_count', $timetaken['query_count']);
        $PIVOTX['template']->assign('timetaken_sql', $timetaken['sql'] );
    }

    $PIVOTX['template']->assign('online', PivotxOffline::isOnline());

    // Set the 'weblogs'
    if (isset($PIVOTX['weblogs'])) {
        $weblogarray = $PIVOTX['weblogs']->getWeblogs();
        $PIVOTX['template']->assign('weblogs', $weblogarray);
    }

    // Fetch the menus..
    getMenus();

    // Fetch the template
    $html = $PIVOTX['template']->fetch($template, $cache_id);

    // Send HTML and XML templates with the correct mime-type.
    if (strpos(strtolower($template), ".xml") > 0 ) {
        header("content-type: text/xml; charset=utf-8");
    } else {
       header('Content-Type: text/html; charset=utf-8');  
    }

    // If minify_backend is enabled, we compress our output here. Never
    // compress when safemode is active, though.
    if (isset($PIVOTX['config']) && $PIVOTX['config']->get('minify_backend') && 
            (!$PIVOTX['extensions']->safemode)) {
        $minify = new Minify($html);
        $html = $minify->minifyURLS();
    }
    
    // If debug is enabled, we add a line that states how long it took to render
    // the page in total and what template was used. This is in addition to
    // the debug output in templates_internal/inc_footer.tpl
    if ($PIVOTX['config']->get('debug')==1) {
    
        $format = "\n<!-- Time taken in total: %s sec. Template: %s -->\n";

        // If $query_log is filled, output the executed queries..
        if ( $PIVOTX['config']->get('log_queries') && count($GLOBALS['query_log'])>0 ) {
            sort($GLOBALS['query_log']);
            debug_printr($GLOBALS['query_log']);
        }
        
        if ($PIVOTX['config']->get('debug_cachestats')) {
            debug_printr($PIVOTX['cache']->stats());
        }
        
        $debugcode = sprintf($format, timeTaken('int'), $template);
        
        $html = str_replace('</body>', $debugcode.'</body>', $html);

    }

    // Output the results!
    echo $html;

    // Process the last hook, after we're done with everything else.
    // (This variable isn't set when we are setting up PivotX.)
    if (isset($PIVOTX['extensions'])) {
        $PIVOTX['extensions']->executeHook('after_execution', $dummy);
    }

}




/**
 * Display our error page, if something goes wrong.
 *
 * @param string $error
 * @param string $additionalinfo
 */
function renderErrorpage($error, $additionalinfo) {
    global $build, $PIVOTX;


    // Make sure we're in the correct folder.
    $PIVOTX['template']->template_dir   = $PIVOTX['paths']['templates_path'];

    // Set the messages and warnings..
    if (isset($PIVOTX['messages'])) {
        $PIVOTX['template']->assign('messages', $PIVOTX['messages']->getMessages() );
        $PIVOTX['template']->assign('warnings', $PIVOTX['messages']->getWarnings() );
    }

    // Assign some other global stuff to smarty
    $PIVOTX['template']->assign("build", $build);
    $PIVOTX['template']->assign('timetaken', timeTaken() );
    $PIVOTX['template']->assign('memtaken', getMem() );
    $PIVOTX['template']->assign('paths', $PIVOTX['paths']);
    $PIVOTX['template']->assign('error', $error);
    $PIVOTX['template']->assign('additionalinfo', $additionalinfo);
    if(function_exists('debug_printbacktrace')) {
        $PIVOTX['template']->assign('backtrace', debug_printbacktrace(true) );
    }
    $PIVOTX['template']->assign('phpversion', phpversion() );
    $PIVOTX['template']->assign('dbtype', $PIVOTX['db']->db_type );


    // Fetch the template
    if (file_exists($PIVOTX['paths']['templates_path']."error.html")) {
        $html = $PIVOTX['template']->fetch("error.html");
        echo $html;
    } else {
        // if we can't even find the error template..
        echo '<p>' . __('PivotX error') . ': <strong>' . $error. "</strong></p><p>$additionalinfo</p>";
    }

    die();

}





/**
 * Custom error handler for the SQL object. We don't want to output the entire error message to the user,
 * but instead print a slightly more helpful message without breaking the page layout.
 *
 * @param string $error_msg
 * @param string $sql_query
 * @param integer $error_no
 */
function setError($type='general', $error_msg, $sql_query="") {
    global $PIVOTX;

    $error_text = '';

    switch($type) {

        case "sql":

            $error_no = mysql_errno();
            $error_text = mysql_error();

            // If the given error is the same as the error we get from mySQL,
            // we don't need to print 'em both:
            if ($error_msg == $error_text) {
                $error_msg = "";
            } else {
                $error_msg = "<p><strong>$error_msg</strong></p>";
            }

            $error = sprintf(__("<p>There was a problem with the Database: </p>
            %s
            <p><tt>error code %s: %s</tt></p>
            </p>
            <ul><li>If you're in the process of setting up PivotX, you should review your
            <a href='%s'>Database connection settings</a>.</li>
            <li>If it worked before, you should check if the Mysql database engine is
            still running on the server (or ask your systems administrator to check for you).</li>
            </ol>"),
                $error_msg,
                $error_no,
                $error_text,
                "index.php?page=configuration#section-2"
            );

            $PIVOTX['template']->assign('error', $PIVOTX['template']->_tpl_vars['error'] . $error);

            // Also debug the messages:
            debug("error_msg: $error_msg");
            debug("sql_query: $sql_query");
            debug("error_no: $error_no");

            break;

        default:

            if (isset($PIVOTX['template']->_tpl_vars['error'])) {
                $error_text = $PIVOTX['template']->_tpl_vars['error'];
            }
            $error_text .= "<p>$error_msg</p>";

            $PIVOTX['template']->assign('error', $error_text);

    }


}



/**
 * Make the $PIVOTX['paths'] array, which is used in many places to figure out where files should be
 * read from or written to. Also updates directories for the templates.
 *
 * @see fixpath()
 */
function setPaths($sites_path = ''){
    global $PIVOTX;

    $PIVOTX['paths']['pivotx_url'] = getPivotxURL();
    $PIVOTX['paths']['site_url'] = addTrailingSlash(str_replace('\\', '/', dirname($PIVOTX['paths']['pivotx_url'])));

    // Set the current host name..
    $PIVOTX['paths']['host']="http://".$_SERVER['HTTP_HOST'];
    
    // Get the 'canonical hostname'..
    $PIVOTX['paths']['canonical_host'] = $PIVOTX['config']->get('canonical_host');
    
    // If we don't have a canonical hostname yet, set it here..
    if (empty($PIVOTX['paths']['canonical_host'])) {
        $PIVOTX['paths']['canonical_host'] = $PIVOTX['paths']['host'];
        $PIVOTX['config']->set('canonical_host', $PIVOTX['paths']['canonical_host']);
    }
    
    if(realpath(__FILE__)=="") {
        $PIVOTX['paths']['pivotx_path'] = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME']))."/");
    } else {
        $PIVOTX['paths']['pivotx_path'] = str_replace('\\', '/', dirname(realpath(__FILE__))."/");
    }
    $PIVOTX['paths']['site_path'] = fixpath( dirname($PIVOTX['paths']['pivotx_path']).'/' );
    if (empty($sites_path)) {
        $PIVOTX['paths']['home_path'] = $PIVOTX['paths']['site_path'];
        $PIVOTX['paths']['home_url'] = $PIVOTX['paths']['site_url'];
    } else {
        $PIVOTX['paths']['home_path'] = $PIVOTX['paths']['pivotx_path'] . $sites_path;
        $PIVOTX['paths']['home_url'] = $PIVOTX['paths']['pivotx_url'] . $sites_path;
    }

    $PIVOTX['paths']['extensions_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . $PIVOTX['config']->get('extensions_path') );
    $PIVOTX['paths']['extensions_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . $PIVOTX['config']->get('extensions_path') );

    $PIVOTX['paths']['templates_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . $sites_path . 'templates/' );
    $PIVOTX['paths']['templates_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . $sites_path . 'templates/' );
    $PIVOTX['paths']['db_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . $sites_path . 'db/' );
    $PIVOTX['paths']['db_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . $sites_path . 'db/' );
    $PIVOTX['paths']['cache_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . $sites_path . 'db/cache/' );

    $jquery_filename = getDefault( $PIVOTX['config']->get('jquery_filename'), "jquery-1.6.2.min.js");
    $PIVOTX['paths']['jquery_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . 'includes/js/' . $jquery_filename );

    $upload_base = getDefault($PIVOTX['config']->get('upload_path'), 'images/%year%-%month%/') ;
    if ($sites_path != '') {
        $PIVOTX['paths']['upload_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . $sites_path . $upload_base );
        $PIVOTX['paths']['upload_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . $sites_path . $upload_base );
    } else {
        $PIVOTX['paths']['upload_path'] = fixpath( $PIVOTX['paths']['pivotx_path'] . '../' . $upload_base );
        $PIVOTX['paths']['upload_url'] = fixpath( $PIVOTX['paths']['pivotx_url'] . '../' . $upload_base );
    }

    // The base paths for uploading are the same as the actual paths, only the
    // wildcards are skipped. For now we assume only one level of depth..
    if (strpos($upload_base, "%")>0) {
        $PIVOTX['paths']['upload_base_path'] = addTrailingSlash(dirname($PIVOTX['paths']['upload_path']));
        $PIVOTX['paths']['upload_base_url'] = addTrailingSlash(dirname($PIVOTX['paths']['upload_url']));
    } else {
        $PIVOTX['paths']['upload_base_path'] = $PIVOTX['paths']['upload_path'];
        $PIVOTX['paths']['upload_base_url'] = $PIVOTX['paths']['upload_url'];
    }

    // Update paths to Smarty directories in case the hard-coded defaults in 
    // module_smarty.php isn't correct. (The templates dir is only changed
    // if we are in a weblog.) We are really handling multi-site here...
    if(defined('PIVOTX_INWEBLOG')) {
        $PIVOTX['template']->template_dir   = $PIVOTX['paths']['templates_path'];
        // Only update secure_dir if we updated template_dir
        $PIVOTX['template']->secure_dir = array(
            $PIVOTX['template']->template_dir,
            $PIVOTX['paths']['extensions_path']
        );
    }
    $PIVOTX['template']->compile_dir = $PIVOTX['paths']['db_path'] . 'cache/';
    $PIVOTX['template']->cache_dir = $PIVOTX['paths']['db_path'] . 'cache/';

}


/**
 * Check if the 'db' folder and the most important files are writable.
 *
 * Note: if they don't exist, it's ok: if the folder is writable, PivotX
 * can create them.
 *
 * @param string $sites_path
 * @return boolean
 */
function checkDB($sites_path = '') {
    global $PIVOTX, $pivotx_path;

    $allok = true;

    // Important: If db/ isn't writeable, we can't even render a smarty template
    // to tell people, hence the ugly HTML output.
    if (!file_exists($pivotx_path . $sites_path . "db") || 
            !is_writeable($pivotx_path . $sites_path . "db")) {
        $error = sprintf(__("The directory '<tt>%s</tt>' is not writeable."), "pivotx/${sites_path}db/");
        echo "<h1>PivotX: ". __("Fatal Error") ."</h1>";
        echo "<p>$error</p>";
        die();
    }

    
    if (!file_exists($pivotx_path . $sites_path . "db/cache")) {
        makeDir($pivotx_path . $sites_path . "db/cache");        
    }

    // The same goes for the cache/ folder.
    if (!file_exists($pivotx_path . $sites_path . "db/cache") || 
            !is_writeable($pivotx_path . $sites_path . "db/cache")) {
        $error = sprintf(__("The directory '<tt>%s</tt>' is not writeable."), "pivotx/${sites_path}db/cache/");
        echo "<h1>PivotX: ". __("Fatal Error") ."</h1>";
        echo "<p>$error</p>";
        die();
    }

    $checkfiles = array("db/ser_config.php", "db/ser_sessions.php", "db/ser_categories.php",
        "db/ser_users.php", "db/ser_weblogs.php");

    foreach($checkfiles as $checkfile) {
        $checkfile = $sites_path . $checkfile;
        if (file_exists($pivotx_path . $checkfile) && !is_writeable($pivotx_path . $checkfile)) {
            $error = sprintf(__("The file '<tt>%s</tt>' is not writeable."), "pivotx/$checkfile");
            setError('', $error);
            $allok = false;
        }
    }

    if (!$allok) {
        // If we did only the initial check, we should output the error page, because
        // Nothing will work expectedly until the db/ folder is fixed.
        $PIVOTX['template']->assign('title', __("Fatal error"));
        renderTemplate('generic.tpl');
        die();
    }

}


/**
 * Checks that the templates, upload and sub-db directories exist and are writable.
 * It will try to create the directories if possible.
 *
 * @return boolean
 */
function checkPaths() {
    global $PIVOTX, $pivotx_path;

    $allok = true;

    if ($pivotx_path != $PIVOTX['paths']['pivotx_path']) {
        setError('', "pivotx_path is inconsistent - this can't happen...");
        $allok = false;
    }

    $checkdirs = array($PIVOTX['paths']['templates_path'], 
        $PIVOTX['paths']['upload_base_path'],
        $PIVOTX['paths']['db_path'] . 'search',
        $PIVOTX['paths']['db_path'] . 'cache',
        $PIVOTX['paths']['db_path'] . 'tagdata'
    );

    foreach($checkdirs as $checkdir) {
        if ((!file_exists($checkdir) && !makeDir($checkdir)) || !is_writeable($checkdir)) {
            $error = sprintf(__("The directory '<tt>%s</tt>' is not writeable."), $checkdir);
            setError('', $error);
            $allok = false;
        }
    }

    // Check the templates if running multi-site
    if (file_exists($PIVOTX['paths']['templates_path']) && $PIVOTX['multisite']->isActive()) {
        $templates = templateList($PIVOTX['paths']['templates_path']);
        if (count($templates) == 0) {
            setError('', sprintf(__("No templates found in directory '<tt>%s</tt>'."),$PIVOTX['paths']['templates_path']));
            $allok = false;
        }
    }

    if (!$allok) {

        // If we did only the initial check, we should output the error page, because
        // Nothing will work expectedly until the db/ folder is fixed.
        $PIVOTX['template']->assign('title', __("Fatal error"));
        renderTemplate('generic.tpl');
        die();

    } else {
        return $allok;
    }

}


/**
 * Returns and sets the URL at which PivotX resides. 
 *
 * The URL is only set when a user is logged in/using the admin side of PivotX 
 * to avoid problems with servers reporting wrong URLs (when using mod_rewrite 
 * in particular).
 * 
 * When running Multi-site the config object isn't set first time this function is called.
 * 
 * @return string
 */
function getPivotxURL() {
    global $PIVOTX;

    // If we are not in the PivotX admin interface, we are not changing the URL to PivotX.
    if (!defined('PIVOTX_INADMIN') && isset($PIVOTX['config'])) {

        $url = $PIVOTX['config']->get('pivotx_url');
        if ($url == "") {
            renderErrorpage(__("URL to the PivotX folder is unknown"),__("Log into PivotX to set the PivotX URL"));
        }

    } else {

        // We need to calculate the URL to PivotX.
        if (!empty($_SERVER['PATH_INFO'])) {
            $current_path = $_SERVER['PATH_INFO'];
        } else if (!empty($_SERVER['PHP_SELF'])) {
            $current_path = $_SERVER['PHP_SELF'];
        } else {
            $current_path = $_SERVER['SCRIPT_NAME'];
        }

        // Make sure we have a trailing slash, and remove windows weirdness from current path.
        $url = dirname($current_path)."/";
        $url = str_replace("//", "/", str_replace("\\", "/", $url)); 

        // If we are not on the admin side, compensate the path/url.
        if (!defined('PIVOTX_INADMIN')) {
            if (isset($_SERVER['COMPENSATE_PATH'])) {
                $url .= $_SERVER['COMPENSATE_PATH'];
            } else {
                // lib.php has not been loaded from render.php
                $url = explode('/', $url);
                do {
                    $curr = array_pop($url);
                } while (($curr != 'pivotx') && (count($url) > 0));
                $url = implode('/', $url);
                $url .= '/pivotx/';
            }
        }

        // Perhaps we need to store the value in the config file..
        if (isset($PIVOTX['config']) && ($url != $PIVOTX['config']->get('pivotx_url'))) {
            $PIVOTX['config']->set('pivotx_url',$url);
        }

    }

    return $url;

}




/**
 * Get the current hostname or parse it out of an URL.
 *
 * Try to be as liberal as possible, to prevent unexpected results. These three
 * will all give http://www.example.org as result: www.example.org,
 * http://www.example.org, http://www.example.org/index.html
 *
 * @return string
 */
function getHost($host = '') {
    global $PIVOTX, $Current_weblog;

    if ($host == '') {
        $host = $PIVOTX['paths']['host'];
    }

    if ( (strpos($host, 'ttp://') == 0) && (strpos($host, 'ttps://') == 0) ) {
        $host = "http://".$host;
    }

    // Split it, and put together the parts we require. (and nothing else)
    $host = parse_url($host);
    $host = sprintf("%s://%s", $host['scheme'], $host['host']);

    return $host;

}

/**
 * Makes a random key with the specified length.
 *
 * @param int $length
 * @return string
 */
function makeKey($length) {

    $seed = "0123456789abcdefghijklmnopqrstuvwxyz";
    $len = strlen($seed);
    $key = "";

    for ($i=0;$i<$length;$i++) {
        $key .= $seed[ rand(0,$len) ];
    }

    return $key;

}



/**
 * Get the amount of used memory, if memory_get_usage is defined.
 *
 * @return string
 */
function getMem() {

    if (function_exists('memory_get_usage')) {
        $mem = memory_get_usage();
        return formatFilesize($mem);
    } else {
        return "unknown";
    }
}




/**
 * Compares versions of software.
 *
 * Versions should use the "MAJOR.MINOR.EDIT" scheme, or in other words
 * the format "x.y.z" where (x, y, z) are numbers in [0-9].
 *
 * @param string $currentversion
 * @param string $requiredversion
 * @return boolean
 *
 */
function checkVersion($currentversion, $requiredversion) {
   list($majorC, $minorC, $editC) = preg_split('#[/.-]#', $currentversion);
   list($majorR, $minorR, $editR) = preg_split('#[/.-]#', $requiredversion);

   if ($majorC > $majorR) { return true; }
   if ($majorC < $majorR) { return false; }
   // same major - check minor
   if ($minorC > $minorR) { return true; }
   if ($minorC < $minorR) { return false; }
   // and same minor
   if ($editC  >= $editR) { return true; }
   return false;
}


/**
 * Sanitize titles for the bookmarklet:
 * - Remove extra spaces and linebreaks.
 * - Remove common parts of titles that repeat the website's name.
 *
 * example: "   YouTube    - Name of video   " returns: "Name of video".
 *
 */
function sanitizeTitle($title) {
    
    $replacements = array(
        "YouTube - ",
        " on CollegeHumor - Funny Pictures, Funny Videos, Funny Links!",
        " on Flickr - Photo Sharing!",
        "Viddler.com - ",
        "Dailymotion - ",
        " on Twitpic",
        " on Twitter",
        "Twitter / ",
        "Yfrog - "
    );
    
    $title = trim(preg_replace('/[\s]+/', " ", $title));
    $title = str_replace($replacements, "", $title);
    
    return $title;
    
}


function sanitizePostedEntry($entry) {
    global $PIVOTX;

    // Get the current user:
    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    if ( (isset($_POST['code'])) && ($_POST['code']!=""))  {
        $entry['oldstatus'] = $entry['status'];
    } else {
        // New entries are assigned '>' for code, and get the current user as author
        $entry['code'] = ">";
        $currentuser = $PIVOTX['session']->currentUser();
        $entry['user'] = $currentuser['username'];
    }

    // Only if we're superadmin, are we allowed to change the author of the entry.
    if ( ($currentuser['userlevel']>=4) && (isset($_POST['author'])) ) {
        $entry['user'] = stripslashes($_POST['author']);
    }

    if ( (strlen($_POST['date1'])>7) && ($_POST['date1']!="00-00-0000") ) {
        // if the date is filled, we try to parse it..
        $entry['date'] = fixDate($_POST['date1'], $_POST['date2']);
    } else {
        // otherwise we'll just assume 'now'.
        $entry['date'] = date("Y-m-d-H-i", getCurrentDate());
    }

    if ($_POST['uri']!="") {
        $entry['uri'] = makeURI($_POST['uri']);
    } else {
        $entry['uri'] = makeURI($_POST['title']);
    }
    $entry['uri'] = uniqueURI($entry['uri'], $entry['code'], 'entry');

    $entry['category'] = $_POST['categories'];

    // Check if the user is actually allowed to post into these categories..
    // We do this by intersecting the chosen(posted) categories with the allowed ones.
    // Superadmins are allowed to post in all categories..
    if ($currentuser['userlevel']<4) {
        $allowedcats = $PIVOTX['categories']->allowedCategories($currentuser['username']);    
        $entry['category'] = array_intersect($entry['category'], $allowedcats);
    }


    $entry['publish_date'] = fixDate($_POST['publish_date1'], $_POST['publish_date2']); 
    $entry['title'] = stripTrailingSpace($_POST['title']);

    // If the posted entry has a subtitle, we use it. If not, we ensure
    // that the subtitle is always set. 
    if (isset($_POST['subtitle'])) {
        $entry['subtitle'] = stripTrailingSpace($_POST['subtitle']);
    } else {
        if (!isset($entry['subtitle'])) {
            $entry['subtitle'] = '';
        }
    }

    $entry['introduction'] = stripTrailingSpace($_POST['introduction']);
    $entry['introduction'] = tidyHtml($entry['introduction'], TRUE);

    $entry['body'] = stripTrailingSpace($_POST['body']);
    $entry['body'] = tidyHtml($entry['body'], TRUE);

    $entry['convert_lb'] = intval($currentuser['text_processing']);
    $entry['status'] =  $_POST['status'];
    $entry['allow_comments'] =  intval($_POST['allow_comments']);
    
    if (isset($_POST['vialink'])) {
        $entry['vialink'] =  strip_tags($_POST['vialink']);
    }
    
    if (isset($_POST['viatitle'])) {
        $entry['viatitle'] =  strip_tags($_POST['viatitle']);
    }

    // Only store the tb_url if we're not publishing. (because if we publish, we ping it, and forget it)
    if ($entry['status']!="publish") {
        $entry['tb_url'] =  strip_tags($_POST['tb_url']);
    } else if (isset($entry['tb_url'])) {
        unset($entry['tb_url']);
    }

    // Gather all tags from introduction and body in keywords..
    $tags = getTags(false, $entry['introduction'] . $entry['body'], strip_tags($_POST['keywords']));
    
    $entry['keywords'] = implode(" ", $tags);

    // Make sure we always have an 'extra fields' array.
    if (empty($entry['extrafields'])) { $entry['extrafields'] = array(); }
    
    // Handle unckecked checkboxes (in extrafields) which aren't sent in the POST request. 
    foreach ($entry['extrafields'] as $key => $value) {
        if (!isset($_POST['extrafields'][$key])) {
            $entry['extrafields'][$key] = '';
        }
    }

    if (!empty($_POST['extrafields'])) {
        $entry['extrafields'] = array_merge($entry['extrafields'], $_POST['extrafields']);
    }
    
    return $entry;

}





function sanitizePostedPage($page) {
    global $PIVOTX;

    // Get the current user:
    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    if ( (isset($_POST['code'])) && ($_POST['code']!=""))  {
        $page['oldstatus'] = $page['status'];
    } else {
        // New pages are assigned '>' for code, and get the current user as author
        $page['code'] = ">";
        $page['user'] = $currentuser['username'];
    }

    // Only if we're superadmin, are we allowed to change the author of the entry.
    if ( ($currentuser['userlevel']>=4) && (isset($_POST['author'])) ) {
        $page['user'] = stripslashes($_POST['author']);
    }

    if ( (strlen($_POST['date1'])>7) && ($_POST['date1']!="00-00-0000") ) {
        // if the date is filled, we try to parse it..
        $page['date'] = fixDate($_POST['date1'], $_POST['date2']);
    } else {
        // otherwise we'll just assume 'now'.
        $page['date'] = date("Y-m-d-H-i", getCurrentDate());
    }

    if ($_POST['uri']!="") {
        $page['uri'] = makeURI($_POST['uri'], 'page');
    } else {
        $page['uri'] = makeURI($_POST['title'], 'page');
    }
    $page['uri'] = uniqueURI($page['uri'], $page['uid'], 'page');

    $page['chapter'] = intval($_POST['chapter']);
    
    if (isset($_POST['sortorder'])) {
        $page['sortorder'] = intval($_POST['sortorder']);
    }

    $page['publish_date'] = fixDate($_POST['publish_date1'], $_POST['publish_date2']);
    $page['title'] = stripTrailingSpace($_POST['title']);
    
    if (isset($_POST['subtitle'])) {
        $page['subtitle'] = stripTrailingSpace($_POST['subtitle']);
    }
    
    $page['template'] = stripTrailingSpace($_POST['template']);

    $page['introduction'] = stripTrailingSpace($_POST['introduction']);
    $page['introduction'] = tidyHtml($page['introduction'], TRUE);

    $page['body'] = stripTrailingSpace($_POST['body']);
    $page['body'] = tidyHtml($page['body'], TRUE);

    $page['convert_lb'] = intval($currentuser['text_processing']);
    $page['status'] =  $_POST['status'];
    $page['allow_comments'] =  intval($_POST['allow_comments']);


    // Gather all tags from introduction and body in keywords..
    $tags = getTags(false, $page['introduction'] . $page['body'], strip_tags($_POST['keywords']));
    $page['keywords'] = implode(" ", $tags);

    // Make sure we always have an 'extra fields' array.
    if (empty($page['extrafields'])) { $page['extrafields'] = array(); }

    // Handle unckecked checkboxes (in extrafields) which aren't sent in the POST request. 
    foreach ($page['extrafields'] as $key => $value) {
        if (!isset($_POST['extrafields'][$key])) {
            $page['extrafields'][$key] = '';
        }
    }

    if (!empty($_POST['extrafields'])) {
        $page['extrafields'] = array_merge($page['extrafields'], $_POST['extrafields']);
    }
    
    return $page;

}




/**
 * Make a simple array consisting of key=>value pairs, that can be used
 * in select-boxes in forms.
 *
 * @param array $array
 * @param string $key
 * @param string $value
 */
function makeValuepairs($array, $key, $value) {

        $temp_array = array();

        if (is_array($array)) {
                foreach($array as $item) {
                        if (empty($key)) {
                            $temp_array[] = $item[$value];
                        } else {
                            $temp_array[$item[$key]] = $item[$value];
                        }

                }
        }

        return $temp_array;

}

/**
 * Convert a PHP array into an associative array for javascript..
 *
 * @param array $array
 * @return string;
 */
function makeJsVars($array) {
    
    if (!is_array($array) || empty($array)) {
        return "{}";
    }
    
    $output = array();
    
    foreach ($array as $key=>$value) {
        $output[] = sprintf('"%s": "%s"', $key, str_replace("\n", "\\n", addslashes($value)));
    }
    
    $output = "{ ". implode(",\n", $output) . " }";
    
    return $output;
    
}


/**
 * Gets a list of files to display in the template. It also sets some other
 * things, like used paths, and whether or not the current folder is writable.
 *
 *
 * @param string $basepath
 * @param string $additionalpath
 * @param string $imageurl
 * @param string $imagepath
 */
function getFiles($basepath, $additionalpath, $imageurl) {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);

    // To prevent spoofing, we ensure that we never go higher than
    // the $basepath. We'll also not allow the $additionalpath to contain '..',
    // and we quit if the $basepath is shorter than 5 chars, or if its
    // different than realpath($basepath).


    $additionalpath = str_replace("..", "", $additionalpath);

    if ($additionalpath == ".") {
        $additionalpath = "";
    }

    if (strlen($basepath)<6) {
        debug("Basepath seems to be a bit short.");
        return;
    }

    if (str_replace("\\", "/", $basepath) != str_replace("\\", "/", realpath($basepath)."/")) {
        debug("Basepath seems to be tampered with: $basepath - " . realpath($basepath));
        return;
    }

    if ($additionalpath!="") {
        $atoms = explode("/", $additionalpath);
        foreach($atoms as $key=>$atom) {
            $atoms[$key] = safeString($atom);
        }
        $additionalpath = implode("/", $atoms);
    }

    $path = realpath($basepath)."/".$additionalpath;

    if (!is_readable($path)) {
        $PIVOTX['messages']->addMessage( __("PivotX is not allowed to read this folder.") );
        return "";  
    }

    $dir = dir($path);

    // Iterate through all files.
    $dirs = $files = array();
    while (false !== ($filename = $dir->read())) {

        // Make an array of the folders..
        if (is_dir($path."/".$filename) && ($filename!=".") && ($filename!="..")){
            if ($additionalpath=="") {
                $dirs[$filename] = $filename;
            } else {
                $dirs[$filename] = $additionalpath."/".$filename;
            }
        }

        // Make an array of the files..
        if (is_file($path."/".$filename)){

            if (strpos($filename, '.thumb.')!==false || strpos($filename, '._')!==false || $filename==".DS_Store" || $filename=="Thumbs.db" ) {
                // Skip this one..
                continue;
            }

            if ($additionalpath=="") {
                $files[$filename]['path'] = $filename;
                $files[$filename]['url'] = fixpath($imageurl."/".$filename);
            } else {
                $files[$filename]['path'] = $additionalpath."/".$filename;
                $files[$filename]['url'] = fixpath($imageurl."/".$additionalpath."/".$filename);
            }
            $ext = strtolower(getExtension($filename));
            $files[$filename]['ext'] = $ext;
            $files[$filename]['bytesize'] = filesize($path."/".$filename);
            $files[$filename]['size'] = formatFilesize($files[$filename]['bytesize']);
            if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
                $dim = getimagesize($path."/".$filename);
                $files[$filename]['dimension'] = sprintf('%s &#215; %s', $dim[0], $dim[1]);
            }

            $files[$filename]['writable'] = is_writable($path."/".$filename);
            $files[$filename]['fullpath'] = $path."/".$filename;


        }

    }
    $dir->close();

    // See if we need to go 'up'..
    if ($additionalpath!="") {
        $dirs['..'] = (dirname($additionalpath)!=".") ? dirname($additionalpath) : "";
    }

    ksort($dirs);
    ksort($files);

    // We need $amount and $stepping for determining if there are multiple pages,
    // and if so, making the buttons to navigate to them.
    $amount = count($files);
    $stepping = getDefault($PIVOTX['config']->get('media_paging_threshold'), 50);

    // Prepare the labels for the buttons..
    $pages = ceil($amount/$stepping);
    $labels = array();
    $filenames = array();
    foreach ($files as $key=>$dummy) {
        $filenames[]=$key;
    }
    
    // Make the labels..
    for ($i=0; $i<$pages; $i++) {
        $endofindex = min( (count($files)-1), (($i+1)*$stepping)-1 );
        $first = substr(safeString($filenames[ $i*$stepping ]),0 ,5);
        $last = substr(safeString($filenames[ $endofindex ]),0 ,5);
        $labels[$i] = sprintf("%s - %s", $first, $last  );
    }
    
    // Apply the 'offset' by slicing the results..
    if (empty($_GET['offset'])) {
        $offset = 0;
    } else {
        $offset = intval($_GET['offset']);
    }
    $files = array_slice($files, ($offset*$stepping), $stepping);

    $PIVOTX['template']->assign('dirs', $dirs);
    $PIVOTX['template']->assign('files', $files);
    $PIVOTX['template']->assign('amount', $amount);
    $PIVOTX['template']->assign('stepping', $stepping);
    $PIVOTX['template']->assign('labels', $labels);
    $PIVOTX['template']->assign('basedir', $basepath);
    $PIVOTX['template']->assign('additionalpath', $additionalpath);
    $PIVOTX['template']->assign('imageurl', fixpath($imageurl."/".$additionalpath."/", true));
    $PIVOTX['template']->assign('imagepath', str_replace("//", "/", $basepath.$additionalpath."/"));
    $PIVOTX['template']->assign('writable', is_writable($path) );

}

/**
 * Do basic operations for the file explorers: create files/folder,
 * delete files, duplicate files.
 *
 * @param string $folder
 */
function fileOperations($folder) {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);

    // TODO: make extra, extra sure we can't delete/create anything outside 
    // of './db/', './templates/' and '../images/'

    // Delete a file, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['del'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {

        // Do some clean-up of user-controlled variables, just in case. 
        $_GET['del'] = strip_tags($_GET['del']);
        
        $filename = $folder . $_GET['del'];
        $thumbname = makeThumbname($filename);
        @unlink($filename);
        @unlink($thumbname);

        $_GET['additionalpath'] = dirname($_GET['del']);
        $PIVOTX['messages']->addMessage(sprintf(__('The file %s was deleted.'), basename($filename)));
    }

    // Create a file, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['answer'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {

        // Do some clean-up of user-controlled variables, just in case. 
        $_GET['answer'] = strip_tags($_GET['answer']);

        if (isset($_GET['file'])) {

            // if file is set, we copy a file

            // Do some clean-up of user-controlled variables, just in case. 
            $_GET['file'] = strip_tags($_GET['file']);

            $oldfile = $folder.$_GET['file'];
            $newfile = $folder.dirname($_GET['file'])."/".strtolower($_GET['answer']);

            if((!file_exists($newfile)) && (copy($oldfile, $newfile))) {
                $PIVOTX['messages']->addMessage(sprintf(__('The file has been copied: %s to %s.'), 
                    basename($oldfile), basename($newfile)));
            } else {
                $PIVOTX['messages']->addMessage(__('The file has <b>NOT</b> been copied. Check if the target folder is writable, and whether the target file does not exist.'));
            }

            $_GET['additionalpath'] = dirname($_GET['file']);

        } else {

            if (isset($_GET['addfolder'])) {

                // if addfolder is set, we add a folder

                // Do some clean-up of user-controlled variables, just in case. 
                $_GET['addfolder'] = strip_tags($_GET['addfolder']);

                $newfolder = $folder.$_GET['addfolder']."/".strtolower($_GET['answer']);

                if ((!file_exists($newfolder)) && (makeDir($newfolder))) {

                    $PIVOTX['messages']->addMessage(
                        sprintf(__('The folder %s was created.'), basename($newfolder)));

                } else {

                    $PIVOTX['messages']->addMessage(
                        sprintf(__('The folder %s was <b>NOT</b> created.'), basename($newfolder)));

                }

                $_GET['additionalpath'] = $_GET['addfolder'];

            } else {

                // add a file..

                // Do some clean-up of user-controlled variables, just in case. 
                $_GET['path'] = strip_tags($_GET['path']);

                $newfile = $folder.$_GET['path']."/".strtolower($_GET['answer']);

                if ((!file_exists($newfile)) && ($fp = fopen($newfile, "w"))) {

                    fwrite($fp, "");
                    fclose($fp);

                    $PIVOTX['messages']->addMessage(
                        sprintf(__('The file %s was created.'), basename($newfile)));

                } else {

                    $PIVOTX['messages']->addMessage(
                        sprintf(__('The file %s was <b>NOT</b> created.'), basename($newfile)));

                }

                $_GET['additionalpath'] = $_GET['path'];

            }

        }

    }

}


/**
 * Get a file over HTTP. First try file_get_contents, and if that fails try
 * curl.
 *
 * @param string $url
 * @return string
 */
function getRemoteFile($url) {

    if ($file_contents = file_get_contents($url)) {

        return $file_contents;

    } else {

        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        ob_start();
        curl_exec($ch);
        curl_close($ch);
        $file_contents = ob_get_contents();
        ob_end_clean();

        return $file_contents;

    }

}


/**
 * Format a filesize like '10.3 kb' or '2.5 mb'
 *
 * @param integer $size
 * @return string
 */
function formatFilesize($size) {

    if ($size > 1024*1024 ) {
        return sprintf("%0.1f mb", ($size/1024/1024));
    } else if ($size > 1024 ) {
        return sprintf("%0.1f kb", ($size/1024));
    } else {
        return $size." b";
    }

}

/**
 * Tries to format a filename in a nice way.
 *
 * @param string $filename
 * @param boolean $include_dirs
 * @param string $dir_delimiter
 * $return string
 */
function formatFilename($filename, $include_dirs=false, $dir_delimiter=' ') {
    $formatted_filename = '';
    if ($include_dirs) {
        $formatted_filename = dirname($filename);
        if ($formatted_filename{0} == '/') {
            $formatted_filename = substr($formatted_filename,1);
        }
        $formatted_filename = str_replace('/', $dir_delimiter, $formatted_filename); 
        $formatted_filename .= $dir_delimiter; 
    }
    $formatted_filename .= removeExtension(basename($filename));
    return str_replace('_', ' ', $formatted_filename);
}

/**
 * Looks recursively through the 'templates' folder, and make a list of all template files.
 *
 * @param string $dir
 * @return array
 */
function templateList($dir="", $recursive=false) {
    global $PIVOTX;

    if ($dir=="") {
        $dir = $PIVOTX['paths']['templates_path'];
    }

    $d = Dir($dir);

    $templates = array();

    while (false !== ($entry = $d->read())) {

        if (is_dir($dir.$entry)) {

            if ($entry!="." && $entry!="..") {
                $templates = array_merge( $templates, templateList($dir.$entry."/", true));
            }

        } else {

            $ext = getExtension($entry);

            if (in_array($ext, array('html', 'htm', 'tpl', 'xml')) && (substr($entry,0,1)!=".") && (substr($entry,0,4) != ':2e_')) {
                $templates[] = $dir.$entry;
            }

        }
    }

    $d->close();

    sort($templates);

    // Remove the topmost dirname from the array..
    if (!$recursive) {
        foreach ($templates as $key => $value) {
            $templates[$key] = str_replace($dir, "", $value);
        }
    }

    return $templates;

}


/**
 * Helper function to get defaults from smarty functions. If $a is
 * defined it returns that, else it returns $b. If $strict=true, it
 * will also return $a, if it is a string with value '0' or an
 * integer with value 0.
 *
 * @param mixed $a
 * @param mixed $b
 * @param boolean $strict
 * @return mixed
 */
function getDefault($a, $b, $strict=false) {

    if ( $strict && isset($a) && $a!==false ) {
        return $a;
    } else if ( isset($a) && !empty($a) ) {
        return $a;
    } else {
        return $b;
    }

}


/**
 * Manipulates a query so mySQL 4.1 specific syntax is stripped out.
 * This way, the queries will also work (to some extent) on mysql 4.0.x
 *
 */
function trimQuery($query) {

    $query = str_replace("DEFAULT CHARSET=utf8", "", $query);
    $query = str_replace("COLLATE=utf8_unicode_ci", "", $query);
    $query = str_replace("collate utf8_unicode_ci", "", $query);
    $query = str_replace("default '0'", "", $query);
    $query = str_replace("default '0000-00-00 00:00:00'", "", $query);

    return $query;

}


/**
 * Check if the w parameter is needed in PivotX
 * generated URL (for sites with multiple weblogs).
 *
 * @return boolean
 * @param string $weblog Weblog to be examined.
 * @param array $categories
 */
function paraWeblogNeeded($weblog, $categories = "") {
    global $PIVOTX;

    if (defined('PIVOTX_WEBLOG')) {
        return false;
    }

    $weblognames = $PIVOTX['weblogs']->getWeblogNames();
    $weblog_default = $PIVOTX['weblogs']->getDefault();
    $weblog_url = $PIVOTX['weblogs']->get($weblog, 'site_url');
        
    // If we have more than one weblog and it is not the default, we need
    // the weblog parameter unless: 
    // 1) If 'para_weblog_always' is set, we always use the weblog parameter. 
    // 2) If 'para_weblog_never' is set, we never use it.
    // 3) If the weblog URL is not empty, we don't need the parameter (since 
    //    the URL should be unique per weblog).
    
    if ($PIVOTX['config']->get('para_weblog_always')) {
        return true;
    } else if ($PIVOTX['config']->get('para_weblog_never')) {
        return false;
    } else if (!empty($weblog_url)) {
        return false;
    } else {
        return ( (count($weblognames)>1) && ($weblog != $weblog_default) );
    }

}


/**
 * Looks recursively look through the 'templates' folder, and make a list of all template theme files.
 *
 * @param string $dir
 * @return array
 */
function themeList($dir) {
    global $PIVOTX;

    if ($dir=="") {
        $dir = $PIVOTX['paths']['templates_path'];
    }

    $d = Dir($dir);

    $themes = array();

    while (false !== ($entry = $d->read())) {

        if (is_dir($dir.$entry)) {

            if ($entry!="." && $entry!="..") {
                $themes = array_merge( $themes, themeList($dir.$entry."/"));
            }

        } else {

            $ext = getExtension($entry);

            if ($ext=="theme") {
                $themes[] = $dir.$entry;
            }

        }
    }

    $d->close();

    sort($themes);

    return $themes;


}


/**
 * Returns an array of themes, with the most suitable ones at the top.
 * templates that match $skip are skipped completely.
 *
 * @param array $templates
 * @param string $filter
 * @param array $skip
 * @return array
 */
function templateOptions($templates, $filter, $skip=array()) {

    $top = array("-"=> __("(Select a template)"));
    $bottom = array();

    if (!is_array($filter)) { $filter = array($filter); }
    
    // Make sure $filter won't break our regex..
    foreach ($filter as $key => $value) {
        $filter[$key] = preg_quote($value, "/");
    }
    
    // Put it together..
    $filter = implode("|", $filter);

    // Loop through all templates..
    foreach ($templates as $template) {

        $template = str_replace("./templates/", "", $template);

        // See if we don't have to skip it..
        foreach($skip as $skipelem) {
            if (strpos($template, $skipelem)!==false) {
                continue(2);
            }
        }

        // See if it goes in 'top' or 'bottom'..
        if (preg_match("/[^a-z0-9](".$filter.")/i", $template)) {
            $top[$template] = $template;
        } else {
            $bottom[$template] = $template;
        }

    }

    if (count($bottom)>0) {
        // Add the separator..
        $top['disabled'] = "-----------";

        // Merge top and bottom in one array..
        $options = array_merge($top, $bottom);
    } else {
        $options = $top;
    }

    return $options;

}



/**
 * Returns the most suitable template by guessing.
 *
 * @param string $filter
 * @return string
 */
function templateGuess($filter) {
    global $PIVOTX;

    // Get the templates
    $templates = templateList();

    // Set the items to skip:
    $skip = array('_sub_', '_aux_');

    // Make sure $filter won't break our regex..
    $filter = preg_quote($filter, "/");
    
    // if there's a fallback default theme try to use it
    $fallback_theme = $PIVOTX['config']->data['fallback_theme'];
    
    // Loop through all templates..
    foreach ($templates as $template) {

        // See if we don't have to skip it..
        foreach($skip as $skipelem) {
            if (strpos($template, $skipelem)!==false) {
                continue(2);
            }
        }

        // if a fallback theme exists.. skip all themes in the wrong directory
        if($fallback_theme && (dirname($template)!=$fallback_theme)) {
            continue;
        }

        // See if it'a  match, and return it..
        if (preg_match("/[^a-z0-9]".$filter."/i", $template)) {
            return $template;
        }

    }

    // Alas, we couldn't find one:
    return "";

}


/**
 * Get a simple list of values from a multi-dimensional array
 *
 * @param string $key
 * @param array $array
 * @param bool $implode
 */
function getSimpleList($key, $array, $implode=false) {

    if (!is_array($array) || count($array)==0) {
        if ($implode) {
            return "";
        } else {
            return array();
        }
    }

    $res = array();

    foreach($array as $item) {
        $res[] = $item[$key];
    }

    if ($implode) {
        return implode(", ", $res);
    } else {
        return $res;
    }

}


/**
 * Create a simple aggregated array, with $key as keys and $value as an array
 * of values. If $implode is true, it implodes the values to a string
 *
 * @param string $key
 * @param string $value
 * @param array $array
 * @param bool $implode
 */
function getSimpleAggregate($key, $value, $array, $implode=false) {

    if (!is_array($array) || count($array)==0) {
        if ($implode) {
            return "";
        } else {
            return array();
        }
    }

    $res = array();

    foreach($array as $item){
        $res[ $item[$key] ][] = $item[$value];
    }

    if ($implode) {
        foreach($res as $key => $item) {
            $key = implode(", ", $item);
        }
    }

    return $res;


}

/**
 * Loads a given template.
 *
 * @param string $basename
 * @return string
 */
function loadTemplate($basename) {
    global $PIVOTX;

    $filename = $PIVOTX['paths']['templates_path'].$basename;

    if (!(file_exists($filename))) {
        $filename = $PIVOTX['paths']['templates_path']."default/entrypage_template_2column.html";
    }

    if(file_exists($filename)) {
        $filetext=implode("", file($filename));
        $template_cache[$basename]=$filetext;
    }else {
        $filetext="";
    }


    return $filetext;


}


/**
 * Unserializes a serialised representation of arrays. It was designed to be
 * as liberal as possible, parsing any information it can find.
 *
 * @param string $filename
 * @version 0.2
 * @author Bob den Otter, www.twokings.nl
 *
 */
function liberalUnserialize($filename) {

    $str = implode("", file($filename));

    // Strip the 'pivotx lamer protection'..
    $str = str_replace("<?php /* pivot */ die(); ?>", "", $str);


    $res = _lu_part($str);

    return $res;

}

/**
 * This is the function where the hard work gets done: first
 * we determine the type of the token, and parse that.
 * Gets called by liberalUnserialize() and (recursively) _lu_getarray()
 *
 * @param string $str
 */
function _lu_part(&$str) {

    // Determine what type of token we want to parse..
    list($type, $length) = _lu_gettype($str);

    // parse the token..
    switch ($type) {

        case 's': // string
            $res = (string) _lu_getstring($str, $length);
            break;

        case 'i': // integer
            $res = (int) $length;
            break;

        case 'd': // double (or float)
            $res = (float) $length;
            break;

        case 'b': // boolean
            $res = (bool) $length;
            break;

        case 'a': // array
            $res = (array) _lu_getarray($str, $length);
            break;

        case 'N': // Null
            $res = false; // I'm just guessing here..
            break;

        case 'x': // Something we didn't recognize
            $res = (string) $str;
            break;
    }

    // return the result..
    return $res;

}

/**
 * Find the type of the first token.
 *
 * @param string $str
 */
function _lu_gettype(&$str) {

    // Match the type of the token..
    if (preg_match_all("/^([a-z]):([0-9.]+)[:;]/i", $str, $match)) {

        // Trim the matched token from $str..
        $str = preg_replace("/^". $match[0][0] ."/i", "", $str);

        // return the token and the length (or value)..
        return array($match[1][0], $match[2][0]);

    } else if (preg_match_all("/^([a-z])[:;]/i", $str, $match)) {

        // Trim the matched token from $str..
        $str = preg_replace("/^". $match[0][0] ."/i", "", $str);

        // return the token and the length (or value)..
        return array($match[1][0], $match[2][0]);
    } else {
        // if we can't match a token, return 'x'..
        return array( 'x', 0);
    }


}

/**
 * Get a token of type string.
 *
 * @param string $str
 * @param integer $length
 */
function _lu_getstring(&$str, $length) {

    /**
     * Try to match a string. It must:
     * - start with "
     * - have a bunch of chars
     * - end with ", directly followed by semicolon(;), a new token (a,b,d,i,s), colon (:) , number ( 0-9+ )
     *
     * We use the ungreedy parameter to get the first possible candidate.. Note:
     * this will probably break if we try to unserialize an array of
     * serialised arrays. (but, you shouldn't do that to begin with)
     */
    if (preg_match('/^"(.*)";}*([sdbia]:[0-9]+|N;)/siU', $str, $match)) {

        // remainder of str..
        $str =  substr( $str, (strlen($match[1])+3) );

        // return the matched token
        return $match[1];

    } else if (preg_match('/^"(.*)";}*/siU', $str, $match)) {

        // remainder of str..
        $str =  substr( $str, (strlen($match[1])+3) );

        // return the matched token
        return $match[1];

    } else {

        // munge opening curly bracket '{'..
        if ($str[0] == '"') {
            $str = substr($str, 1);
        }

        // just return what we've got.. oh, well.
        return $str;

    }


}

/**
 * Get a series of tokens from $str to make an array.
 *
 * @param string $str
 * @param integer $count
 */
function _lu_getarray(&$str, $count) {

    $res = array();

    // munge opening curly bracket '{'..
    if ($str[0] == '{') {
        $str = substr($str, 1);
    }

    // for each element in the array, we get two tokens:
    // The key and the value..
    for($i=0; $i<($count);$i++) {

        $key = _lu_part($str);
        $val = _lu_part($str);
        $res[$key] = $val;

    }

    // munge closing curly bracket '}'..
    if ($str[0] == '}') {
        $str = substr($str, 1);
    }

    return $res;

}

/**
 * Clean several keys with values supplied by the user in 
 * the $_GET, $_POST, $_REQUEST and $_COOKIE super globals.
 */
function cleanUserInput() {

    $keys_to_clean = array( 'w', 'c', 'u', 'p', 'e', 'a', 'te', 'date', 'feed');
    foreach ($keys_to_clean as $key) {

        if (isset($_GET[$key])) {
            $_GET[$key] = safeString($_GET[$key], false, '/*');
        }
        if (isset($_POST[$key])) {
            $_POST[$key] = safeString($_POST[$key], false, '/*');
        }
        if (isset($_REQUEST[$key])) {
            $_REQUEST[$key] = safeString($_REQUEST[$key], false, '/*');
        }
        if (isset($_COOKIE[$key])) {
            $_COOKIE[$key] = safeString($_COOKIE[$key], false, '/*');
        }
    }

}

/**
 * Clean strings to be used as (X)HTML attributes - strip tags and entify 
 * ampersands and quotes.
 *
 * @param string $str
 * @return string
 */
function cleanAttributes($str) {

    $str = strip_tags($str);
    $str = entifyAmpersand($str);
    $str = entifyQuotes($str);

    return $str;

}

/**
 * Clean smarty params: strip quotes and HTML entities
 *
 * @param array $params
 * @return array
 */
function cleanParams($params) {

    foreach ($params as $key=>$param) {

        if (is_array($param)) {
            $params[$key] = cleanParams($params[$key]);
        } else if (is_string($param)) {
            $params[$key] = str_replace("&nbsp;", ' ', $params[$key]);
            $params[$key] = @html_entity_decode($params[$key], ENT_QUOTES, 'UTF-8');    
            
            // Strip quotes from the start and the end, if needed..
            if ( ($params[$key][0]==$params[$key][(strlen($params[$key])-1)]) &&
                ( $params[$key][0]=='"' || $params[$key][0]=="'" ) ) {
                $params[$key] = substr($params[$key],1,-1);
            }
            
        }
    }

    return $params;

}


/**
 * This function does the opposite of strip_tags(). instead of _allowing_ certain tags, this
 * function only _strips_ certain tags.
 *
 * @param string $str
 * @return string
 */
function stripOnlyTags($str, $tags) {
    
    if(!is_array($tags)) {
        $tags = ((strpos($str, '>') !== false) ? explode('>', str_replace('<', '', $tags)) : array($tags));
        if(end($tags) == '') {
            array_pop($tags);
        }
    }
    
    foreach($tags as $tag) {
        $str = preg_replace('#</?'.$tag.'[^>]*>#is', '', $str);
    }
    
    return $str;
}


/**
 * Recursively strip slashes from a string or array (of strings)
 *
 * @see http://php.net/manual/en/function.get-magic-quotes-gpc.php#82524
 *
 * @param mixed $value
 * @return mixed
 */
function stripSlashesDeep(&$value) {
    
    $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);

    return $value;
} 



/**
 * Implode an array recursively
 *
 * @param string $glue
 * @param mixed $value
 * @return string
 */
function implodeDeep($glue, $value) {
    
    if (is_array($value)) {
        
        // recursively implode.
        foreach($value as $key=>$val) {
            if (is_array($value)) {
                $value[$key] = implodeDeep($glue, $val);  
            }
        }
        
        return implode($glue, $value);
        
    } else {
        
        return $value;
    
    }
    
}



/**
 * These functions are used to tidy up the general nastyness
 * in html generated by wysiwyg editors in IE and Mozilla.
 *
 * On certain older (5.1.x) versions of PHP, there's a issue with regexes. To
 * work around this, we check for a valid string, before we return. If something
 * went wrong, we just return the original, untidied $text.
 *
 * @param string $text
 * @param boolean $thorough
 *
 * @return string
 */
function tidyHtml($text, $thorough=FALSE) {

    $original_text = $text;
    // Change <br /><br /> into </p><p>
    /*$text = preg_replace("/<br( [^>]*)?>\s*<br( [^>]*)?>/Ui", "</p>\n<p>", $text);*/

    // clean up empty paragraphs
    $text = preg_replace("/<p>[\s|&nbsp;]*<\/p>/Ui", "</p>", $text);

    // Clean up loose br's inside of paragraphs.
    $text = preg_replace("/<p>\s*<br( [^>]*)?>/Ui", "<p>", $text);
    $text = preg_replace("/<br( [^>]*)?>\s*<\/p>/Ui", "</p>", $text);

    // Clean up loose br's outside of paragraphs.
    $text = preg_replace("/<br( [^>]*)?>\s*<p>/Ui", "<p>", $text);
    $text = preg_replace("/<\/p>\s*<br( [^>]*)?>/Ui", "</p>", $text);

    // clean <p><p> and </p></p>
    $text = preg_replace("/<p( [^>]*)?>\s*<p( [^>]*)?>/Ui", "<p\\1\\2>", $text);
    $text = preg_replace("/<\/p>\s*<\/p>/Ui", "</p>\n", $text);

    // after this, we might end up starting with a closing </p>. We don't want that.
    $text = preg_replace("/^\s*<\/p>/Ui", "", $text);

    // clean up <div>'s in <p>'s
    //$text = preg_replace("/<p>\s*<div(.*)>(.*)<\/div>\s*<\/p>/Ui", "<div\\1>\\2</div>\n", $text);

    $text = preg_replace_callback("/<p>(\s*)<div(.*)>(.*)<\/div>(\s*)<\/p>/sUi", "tidyHtmlCallbackNesteddivs", $text);
    $text = preg_replace_callback("/<p>(\s*)<object(.*)>(.*)<\/object>(\s*)<\/p>/sUi", "tidyHtmlCallbackNestedobjects", $text);

    if ($thorough) {
        $text = preg_replace_callback("/<(.*)>/Ui", 'tidyHtmlCallback', $text);
    }

    // Return $text, if it's not empty. It's empty either if the original $text was
    // empty, or if the preg_replace went wrong.
    if (!empty($text) || empty($original_text) ) {
        return $text;
    } else {
        return "<!-- tidy fallback -->" . $original_text;
    }

}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallback($match) {

    $match = $match[0];

    // first, change the tag to lowercase (added the "." because otherwise it breaks my
    // editor's syntax highlighting)
    $match = preg_replace_callback("/<(\/"."*)([a-z]+)([\s|>])/i", "tidyHtmlCallbackChangetag", $match);

    //then, change attributes to lowercase, making sure they are quoted..
    $match = preg_replace_callback('/(\s[a-z]+)="(([^"\\\\]|\\.)+)"/i', "tidyHtmlCallbackDoublequote", $match);
    $match = preg_replace_callback('/(\s[a-z]+)=([a-z0-9]+)/i', "tidyHtmlCallbackDoublequote", $match);
    $match = preg_replace_callback("/(\s[a-z]+)='(([^'\\\\]|\\.)+)'/i", "tidyHtmlCallbackSinglequote", $match);

    //this one doesn't work..
    //$match = preg_replace_callback("/\s([a-z]+)=([\s>])/i", "tidyHtmlCallbackNovalueattr", $match);

    // change 'optional' non closing tags to resemble proper xhtml..
    $match = preg_replace("/<br([^\/]*)>/Ui", "<br \\1 />", $match);
    $match = preg_replace("/<hr([^\/]*)>/Ui", "<hr \\1 />", $match);
    $match = preg_replace("/<img([^\/]*)>/Ui", "<img \\1 />", $match);
    $match = preg_replace("/<input([^\/]*)>/Ui", "<input \\1 />", $match);

    return $match;
}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackChangetag($match) {
    return "<".$match[1].strtolower($match[2]).$match[3];
}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackDoublequote($match) {
    return strtolower($match[1])."=\"".$match[2]."\"";
}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackSinglequote($match) {
    return strtolower($match[1])."='".$match[2]."'";
}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackNovalueattr($match) {
    return " ".strtolower($match[1])."='".$match[1]."'".$match[2];
}

/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackNesteddivs($match) {

    $output="";

    if (strlen(trim($match[1]))>2) { $output .= "<p>".trim($match[1])."</p>\n"; }

    $output .= "<div".$match[2].">".$match[3]."</div>\n";

    if (strlen(trim($match[4]))>2) { $output .= "<p>".trim($match[1])."</p>\n"; }

    return $output;
}


/**
 * Helper function for tidy_html.
 *
 * @see tidy_html
 * @param string $match
 * @return string
 */
function tidyHtmlCallbackNestedobjects($match) {

    $output="";

    if (strlen(trim($match[1]))>2) { $output .= "<p>".trim($match[1])."</p>\n"; }

    $output .= "<object".$match[2].">".$match[3]."</object>\n";

    if (strlen(trim($match[4]))>2) { $output .= "<p>".trim($match[1])."</p>\n"; }

    return $output;
}



// -- End tidy functions ---------------

/**
 * Sets the global variable $Archive_array used by several archive snippets.
 *
 * @param string $unit time unit for the archive.
 * @param boolean $force
 * @return void
 */
function makeArchiveArray($force=FALSE,$unit) {
    global $Archive_array;

    $arc_db = new db();

    $Archive_array = $arc_db->getArchiveArray($force,$unit);
}

/**
 * Makes archive name for a date, weblog and time unit.
 *
 * @param string $date
 * @param string $this_weblog
 * @param string $archive_unit - 'year', 'month' or 'week'.
 * @return string
 */
function makeArchiveName($date='', $this_weblog='', $archive_unit='month') {
    global $PIVOTX;

    if ($date=='') {
        $vars = $PIVOTX['template']->get_template_vars();
        if (isset($vars['date'])) {
            $date = $vars['date'];
        } else {
            $date = date("Y-m-d-H-i");
        }
    }

    $year = formatDate($date, "%year%");

    $archive_num = (($archive_unit=="week")  ? formatDate($date, "%weeknum%") :
                   (($archive_unit=="month") ? formatDate($date, "%month%")   :  ''  ));
    $archive_type= (($archive_unit=="week")  ? "w" : (($archive_unit=="month") ? "m" :  "y" ));

    $archive_name=sprintf("%s-%s%02d", $year, $archive_type, $archive_num);

    return $archive_name;
}

/**
 * Adds a file to a zip file.
 *
 * @param string $zipfile
 * @param string $filename
 * @return void
 */
function addFileToZip(&$zipfile, $filename) {
    $data = implode("", file($filename));
    $zipfile->addFile($data, $filename);
}

/**
 * Adds a directory (recursively) to a zip file.
 *
 * @param string $zipfile
 * @param string $dirname
 * @param mixed $exclude Directories to be exluded
 * @return void
 */
function addDirToZip(&$zipfile,$dirname,$exclude='') {
    $d = dir($dirname);
    if (!is_array($exclude) && $exclude != '') {
        $exclude = array($exclude);
    }
    $exclude[] = '.';
    $exclude[] = '..';
    $exclude[] = '.svn';
    $exclude[] = '.git';
    while (false !== ($entry = $d->read())) {
        if (!in_array($entry, $exclude)) {
            if (is_dir($dirname.$entry)) {
                addDirToZip($zipfile, $dirname.$entry."/");
            } else {
                addFileToZip($zipfile, $dirname.$entry);
            }
        }
    }
    $d->close();
}

/**
 * Downloads the configuration files, templates or entries database.
 */
function backup($what) {
    global $PIVOTX;

    // make the zipfile.
    include_once('modules/zip.lib.php');
    $zipfile = new zipfile();

    // in case we are running a multi-site install, we need to get the
    // correct relative db and template paths.
    $templates_path = str_replace($PIVOTX['paths']['pivotx_path'], "", $PIVOTX['paths']['templates_path']);
    $db_path = str_replace($PIVOTX['paths']['pivotx_path'], "", $PIVOTX['paths']['db_path']);

    if ($what == 'config') {
        addFileToZip($zipfile, $db_path . 'ser_config.php');
        addFileToZip($zipfile, $db_path . 'ser_users.php');
        addFileToZip($zipfile, $db_path . 'ser_weblogs.php');
        addFileToZip($zipfile, $db_path . 'ser_categories.php');
    } elseif ($what == 'templates') { 
        addDirToZip($zipfile, $templates_path);
    } elseif ($what == 'db-directory') { 
        addDirToZip($zipfile, $db_path, array('cache', 'rsscache'));
    } elseif ($what == 'entries') {
        foreach (glob("${db_path}standard-*", GLOB_MARK) as $directory) {
            addDirToZip($zipfile, $directory);
        }
    } else {
        debug("Unknown object to backup.");
        return;
    }

    // get the zipp0red data..
    $zipped = $zipfile->file();

    // trigger a download.
    $basename="pivotx_${what}_".date("Ymd").".zip";
    header("Content-disposition: attachment; filename=$basename");
    header("Content-type: application/zip");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $zipped;
}

/**
 * Wrapper around PHP's standard mail function that sets the
 * necessary headers for UTF-8 messages. 
 *
 * The function only supports text messages. The messages are sent 
 * base64 encoded (since there is no bultin PHP function for quoted printable).
 * If no sender is set, the PivotX superadmin is set in the From field.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $headers
 * @param string $additional_parameters
 *
 */
function pivotxMail($to , $subject , $message, $headers='', $additional_parameters='') {
    global $PIVOTX;
    $utf8_headers = "Content-Type: text/plain; charset=utf-8\n" . 
        "Content-Transfer-Encoding: base64\n" . 
        "Content-Disposition: inline";
    // Set superadmin as default sender if not already set in $headers
    if (strpos($headers, 'From:') === false) {
        foreach ($PIVOTX['users']->getUsers() as $tmpuser) {
            if ($tmpuser['userlevel'] == 4) {
                $adminuser = $tmpuser;
                break;
            }
        }
        $contact_addr = $adminuser['email'];
        $contact_name = $adminuser['nickname'];
        if (empty($contact_name)) {
            $contact_name = $adminuser['username'];
        }
        $contact_name = '=?UTF-8?B?'.base64_encode($contact_name).'?=';
        $fromheader = sprintf("From: \"%s\" <%s>\n", $contact_name, $contact_addr);
        if ($headers != '') {
            $headers .= "\n$fromheader";
        } else {
            $headers = $fromheader;
        }
    }
    $utf8_headers .= "\n$headers";
    $subject_prefix = getDefault( $PIVOTX['config']->get('email_subject_prefix'), 
        __('Notification from "%site%" - '));
    $subject_prefix = str_replace("%site%", $PIVOTX['config']->get('sitename'), $subject_prefix);
    $message = chunk_split(base64_encode($message));
    $subject = '=?UTF-8?B?'.base64_encode($subject_prefix.$subject).'?=';
    return mail($to , $subject , $message, $utf8_headers, $additional_parameters);
}

/**
 * Sends a mail with a password reset link.
 *
 * The input array $values should contain 'name', 'email', 'reset_id' and 'link'. 
 * 
 * @param array $values
 * @return boolean True if we could send the mail, false otherwise.
 */
function mailResetPasswordLink($values) {
    global $PIVOTX;

    $mail = __("Someone (you?) has requested a new password for the user '%user%' " .
    "on the PivotX site '%site%'.\n\n" .
    "To get your new password, click the following link:\n\n%link%\n\n" . 
    "If you haven't asked for a new password, you can safely ignore this message. ".
    "Your old password will still work as usual.");

    $mail = str_replace("%site%", $PIVOTX['config']->get('sitename'), $mail);
    $mail = str_replace("%name%", $PIVOTX['config']->get('sitename'), $mail); 
    $mail = str_replace("%user%", $values['name'], $mail);
    $mail = str_replace("%link%", $values['link'], $mail);

    if (pivotxMail($values['email'], __('Password reset'), 
            str_replace('&amp;', '&' , $mail))) {
        return true;
    } else {
        return false;
    }

}


/**
 * Produces a random string (with numbers and latin letters) of the given length. 
 * Can be useful for generating passwords.
 *
 * @param integer $len
 * @return string
 */
function randomString($len=12, $loweronly=false) {
    $string = "";
    for ($i = 1 ; $i <= $len; $i++) {
        $rchar = mt_rand(1,30);
        if($rchar <= 10) {
            $string .= chr(mt_rand(65,90));
        }elseif($rchar <= 20) {
            $string .= mt_rand(0,9);
        }else{
            $string .= chr(mt_rand(97,122));
        }
    }
    
    if ($loweronly) {
        $string = strtolower($string);
    }
    
    return $string;
}

/**
 * Make a link to an admin page.
 * 
 * @param string $page
 * @return string
 */
function makeAdminPageLink($page = '') {
    global $PIVOTX;

    $link = $PIVOTX['paths']['pivotx_url']."index.php";

    if ($page != '') {
        $link .= '?page=' . $page;
    }
 
    return $link;

}

/**
 * Creates a folder for uploaded files. 
 *
 * Replaces "%year%", "%month%", "%username%", and "%firstletter%" 
 * (if present in the path specification) with the corresponding values. 
 * 
 * @param string $basefilename
 * @return string The name of the folder.
 */
function makeUploadFolder($basefilename='') {
    global $PIVOTX;

    // Check if there are formatting tags to replace (but not in the base part).
    $path = str_replace($PIVOTX['paths']['upload_base_path'], '', $PIVOTX['paths']['upload_path']);
    if (strpos($path, "%") !== false) {
        $path = str_replace("%year%", date("Y"), $path);
        $path = str_replace("%month%", date("m"), $path);
        $path = str_replace("%username%", $PIVOTX['session']->currentUsername(), $path);
        $path = str_replace("%firstletter%", substr(preg_replace("/[^a-z]/", "", strtolower($basefilename)),0,1), $path);
        // Cleaing up in case some of the replacements were empty strings.
        $path = str_replace('--','-',$path);
        $path = str_replace('__','_',$path);
    }

    // Make sure the requested upload folder exists..
    $path = $PIVOTX['paths']['upload_base_path'] . $path;
    makeDir($path);
    return $path;
}

/**
 * Get the upload folder url (relative to images/)
 */
function getUploadFolderUrl($basefilename='') {
    global $PIVOTX;

    $folder = makeUploadFolder($basefilename);

    $url = str_replace($PIVOTX['paths']['upload_base_path'],'',$folder);

    return $url;
}


/**
 * Adds leading zeros when necessary
 *
 * @param integer $number
 * @param integer $threshold
 * @return string 
 */
function zeroise($number,$threshold) {
	return sprintf('%0'.$threshold.'s', $number);
}


/**
 * Checks if the text is a valid email address.
 *
 * Given a chain it returns true if $theAdr conforms to RFC 2822.
 * It does not check the existence of the address.
 * Suppose a mail of the form
 *  <pre>
 *  addr-spec     = local-part "@" domain
 *  local-part    = dot-atom / quoted-string / obs-local-part
 *  dot-atom      = [CFWS] dot-atom-text [CFWS]
 *  dot-atom-text = 1*atext *("." 1*atext)
 *  atext         = ALPHA / DIGIT /    ; Any character except controls,
 *        "!" / "#" / "$" / "%" /      ;  SP, and specials.
 *        "&" / "'" / "*" / "+" /      ;  Used for atoms
 *        "-" / "/" / "=" / "?" /
 *        "^" / "_" / "`" / "{" /
 *        "|" / "}" / "~" / "." /
 * </pre>
 *
 * @param string $theAdr
 * @return boolean
 */
function isEmail($theAdr) {

    // default
    $result = FALSE;

    // go ahead
    if(( ''!=$theAdr )||( is_string( $theAdr ))) {
        $mail_array = explode( '@',$theAdr );
    }

    if( !is_array( $mail_array )) { return FALSE; }

    if( 2 == count( $mail_array )) {
        $localpart = $mail_array[0];
        $domain_array  = explode( '.',$mail_array[1] );
    } else {
        return FALSE;
    }
    if( !is_array( $domain_array ))  { return FALSE; }
    if( 1 == count( $domain_array )) { return FALSE; }

    /* relevant info:
     * $mail_array[0] contains atext
     * $adr_array  contains parts of address
     *          and last one must be at least 2 chars
     */

    $domain_toplevel = array_pop( $domain_array );
    if(is_string($domain_toplevel) && (strlen($domain_toplevel) > 1)) {
        // put back
        $domain_array[] = $domain_toplevel;
        $domain = implode( '',$domain_array );
        // now we have two string to test
        // $domain and $localpart
        $domain    = preg_replace( "/[a-z0-9]/i","",$domain );
        $domain    = preg_replace( "/[-|\_]/","",$domain );
        $localpart = preg_replace( "/[a-z0-9]/i","",$localpart);
        $localpart = preg_replace(
            "#[-.|\!|\#|\$|\%|\&|\'|\*|\+|\/|\=|\? |\^|\_|\`|\{|\||\}|\~]#","",$localpart);
        // If there are no characters left in localpart or domain, the
        // email address is valid.
        if(( '' == $domain )&&( '' == $localpart )) { $result = TRUE; }
    }

    return $result;
}



/**
 * Checks whether the text is an URL or not.
 *
 * @param string $url
 * @return boolean
 */
function isUrl($url) {

    return (preg_match("/((ftp|https?):\/\/)?([a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)+(com\b|edu\b|biz\b|org\b|gov\b|in(?:t|fo)\b|mil\b|net\b|name\b|museum\b|coop\b|aero\b|[a-z][a-z]\b|[0-9]{1,3})/i",$url));

}




/**
 * Gets the extension (if any) of a filename.
 *
 * @param string $filename
 * @return string
 */
function getExtension($filename) {
    $pos=strrpos($filename, ".");
    if ($pos === false) {
        return "";
    } else {
        $ext=substr($filename, $pos+1);
        return $ext;
    }
}


/**
 * Removes the extension (if any) from a filename
 *
 * @param string $filename
 * $return string
 */
function removeExtension($filename) {
    
    $ext = strrchr($filename, '.');
    
    if($ext !== false) {
        $filename = substr($filename, 0, -strlen($ext));
    }
    
    return $filename;
    
}

/**
 * Creates a thumbnail name based on a give filename.
 *
 * @param string $filename
 * @return string
 */
function makeThumbname($filename) {
    $ext = getExtension($filename);

    if ($ext != "") {
        $thumbname = preg_replace('/'.preg_quote($ext).'$/i',"thumb.".$ext,$filename);
    } else {
        $thumbname = $filename.".thumb";
    }

    return $thumbname;
}


/**
 * Formats all data in an entry according to the passed $format.
 *
 * @param array $entry
 * @param string $format
 * @return string
 */
function formatEntry($entry, $format) {

    // if format does not contain '%' just return to save some processing time
	if (strpos($format, "%")=== FALSE) {
		return $format;
	}

	foreach ($entry as $key => $value) {
                if (is_array($value)) {
			$value = implode(', ',$value);
		}
		$format=str_replace("%$key%", $value, $format);
	}
	return $format;
}


/**
 * Formats date, according to the passed $format
 *
 * @param string $date
 * @param string $format
 * @param string $title
 * @return string
 */
function formatDate($date="", $format="", $title="") {
    global $PIVOTX;


    $english_day_array = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
    $english_month_array = array("Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

    if ($format=="") { $format="%day% %monname% '%ye%"; }

    // if format does not contain '%' just return to save some processing time
    if (strpos($format, "%")=== FALSE) {
        return $format;
    }

    if ($date=="" || $date=="now") {
        $date= date("Y-m-d-H-i-s", getCurrentDate());
    }
    list($yr,$mo,$da,$ho,$mi,$se) = preg_split('/[ |\\-|:]/' , $date);

    $mktime = mktime(1,1,1,$mo,$da,$yr);
    $day = @date("w",$mktime);

    $ho12 = ($ho>11) ? $ho - 12 : $ho;
    $ampm= ($ho12==$ho) ? "am" : "pm";
    if ($ho12==0) { $ho12=12; }

    if (isset($PIVOTX['locale'])) {
        $weekday = $PIVOTX['locale']->getWeekday($day);
        $month = $PIVOTX['locale']->getMonth($mo);
        $format=str_replace("%dname%", $PIVOTX['locale']->getWeekdayAbbrev($weekday), $format);
        $format=str_replace("%monname%", $PIVOTX['locale']->getMonthAbbrev($month), $format);
    }
    $format=str_replace("%minute%", $mi, $format);
    $format=str_replace("%hour12%", $ho12, $format);
    $format=str_replace("%ampm%", $ampm, $format);
    $format=str_replace("%hour24%", $ho, $format);
    $format=str_replace("%day%", $da, $format);
    $format=str_replace("%daynum%", $day, $format);
    $format=str_replace("%dayname%", $weekday, $format);
    $format=str_replace("%english_dname%", $english_day_array[$day], $format);
    $format=str_replace("%weekday%", $weekday, $format);
    $format=str_replace("%weeknum%", @date("W",$mktime), $format);
    $format=str_replace("%month%", $mo, $format);
    $format=str_replace("%monthname%", $month, $format);
    $format=str_replace("%english_monname%", $english_month_array[-1+$mo], $format);
    $format=str_replace("%year%", $yr, $format);
    $format=str_replace("%ye%", substr($yr,2), $format);
    $format=str_replace("%sec%", $se, $format);
    $format=str_replace("%aye%", "&#8217;".substr($yr,2), $format);
    $format=str_replace("%ordday%", 1*$da, $format);
    $format=str_replace("%ordmonth%", 1*$mo, $format);

    if (strpos("%fuzzy%", $format) !== false) {
        $format=str_replace("%fuzzy%", formatDateFuzzy($date), $format);
    }

    //while not part of 'dates', we also replace %title% with the
    //entry's, suitable for use in filenames
    $format=str_replace("%title%", safeString(substr($title,0,28),TRUE) , $format);

    return $format;
}


/**
 * Formats the date as a 'fuzzy date', like 'yesterday evening' or 'on wednesday'.
 *
 * Most os this is taken from Graham Keellings 'fuzzy date snippet'. See here for
 * details: http://www.keellings.com/graham/software/pivot.php
 *
 * @param string $date
 * @return string
 */
function formatDateFuzzy($date) {
    global $PIVOTX;

    // some constants
    $secsPerMinute = 60;
    $secsPerQuarterHour = $secsPerMinute * 15;
    $secsPerHour = $secsPerQuarterHour *4;
    $secsPerDay = $secsPerHour * 24;
    $secsPerWeek = $secsPerDay * 7;
    $secsPerMonth = $secsPerDay * 30; // well, it _is_fuzzy
    $secsPerYear = $secsPerDay * 365; // fuzzy - no leap year
    $now = getCurrentDate();

    list($yr,$mo,$da,$ho,$mi,$se) = preg_split('/[ |\\-|:]/' , $date);
    $then = strtotime($yr . '-' . $mo . '-' . $da . ' ' . intval($ho) . ':' . intval($mi) .':' . intval($se));
    $diff = $now - $then;

    if ($diff < ($secsPerMinute * 2)) { return __("just now"); }    
    if ($diff < ($secsPerMinute * 5)) { return __("a few minutes ago"); }
    if ($diff < ($secsPerMinute * 10)) { return __("less than ten minutes ago"); }
    if ($diff < ($secsPerMinute * 22)) { return __("15 minutes ago"); }
    if ($diff < ($secsPerMinute * 45)) { return __("half an hour ago"); }
    if ($diff < ($secsPerMinute * 75)) { return __("about an hour ago"); }
    if ($diff < ($secsPerMinute * 105)) { return __("an hour and a half ago"); }
    if ($diff < ($secsPerMinute * 140)) { return __("about two hours ago"); }

    if ($diff < ($secsPerDay * 2) && $da >= (date("d")-1) ) {
        
        $thenPeriod = ($ho < 12 ? 'morning' : ($ho >= 17 ? 'evening' : 'afternoon'));
        
        if ($da == date("d")) {
            $hours = round($diff / $secsPerHour);
            $nowPeriod = (date("H") < 12 ? 'morning' : (date("H") >= 17 ? 'evening' : 'afternoon'));
            
            if ($nowPeriod == $thenPeriod || $hours < 6) {
                return str_replace("%value%", $hours, __("%value% hours ago"));
            } else {
                $period = 'this';
            }

        } else {

            $period = 'period';
            
        }

        if ($thenPeriod == 'morning') {
            $text = (($period == 'this')?__('this morning'):__('yesterday morning'));
        } elseif ($thenPeriod == 'evening') {
            $text = (($period == 'this')?__('this evening'):__('yesterday evening'));
        } else {
            $text = (($period == 'this')?__('this afternoon'):__('yesterday afternoon'));
        }

        return $text;
        
    }


    if ($diff < $secsPerWeek) {
        return str_replace("%value%", $PIVOTX['locale']->getWeekday(date('w', $then)), __("on %value%"));
    }

    if ($diff < $secsPerMonth) {
        $weeks = round($diff / $secsPerWeek);
        if ($weeks == 1) { return __("1 week ago"); }
        return str_replace("%value%", $weeks, __("%value% weeks ago"));
    }

    if ($diff < $secsPerYear) {
        $months = round($diff / $secsPerMonth);
        if ($months == 1) { return __("about a month ago"); }
        return str_replace("%value%", $PIVOTX['locale']->getMonth(date('n', $then)), __("in %value%"));
    }

    $years = round($diff / $secsPerYear);

    if ($years == 1) { return __("one year ago"); }

    if ($years < 16) { // max number strings - should use count()to calc @@ ///
        return str_replace("%value%", $years, __("%value% years ago"));
    }

    // If we get to here, it's been a long time ago.
    return __("a very long time ago");


}


/**
 * Formats date range, according to the passed format
 *
 * @param string $start_date
 * @param string $end_date
 * @param string $format
 * @return string
 */
function formatDateRange($start_date, $end_date, $format) {
    global $PIVOTX;

    list($st_yr,$st_mo,$st_da) = explode("-",$start_date);
    list($en_yr,$en_mo,$en_da) = explode("-",$end_date);

    $mktime = mktime(1,1,1,$st_mo,$st_da,$st_yr);
    $day = @date("w",$mktime);

    $weekday = $PIVOTX['locale']->getWeekday($day);
    $month = $PIVOTX['locale']->getMonth($st_mo);
    $format=str_replace("%st_day%", $st_da, $format);
    $format=str_replace("%st_daynum%", $day, $format);
    $format=str_replace("%st_dayname%", $weekday, $format);
    $format=str_replace("%st_dname%", $PIVOTX['locale']->getWeekdayAbbrev($weekday), $format);
    $format=str_replace("%st_weekday%", $weekday, $format);
    $format=str_replace("%st_weeknum%", @date("W",$mktime), $format);
    $format=str_replace("%st_month%", $st_mo, $format);
    $format=str_replace("%st_monthname%", $month, $format);
    $format=str_replace("%st_monname%", $PIVOTX['locale']->getMonthAbbrev($month), $format);
    $format=str_replace("%st_year%", $st_yr, $format);
    $format=str_replace("%st_ye%", substr($st_yr,2), $format);

    $format=str_replace("%st_aye%", "&#8217;".substr($st_yr,2), $format);
    $format=str_replace("%st_ordday%", 1*$st_da, $format);
    $format=str_replace("%st_ordmonth%", 1*$st_mo, $format);
    $mktime = mktime(1,1,1,$en_mo,$en_da,$en_yr);
    $day = @date("w",$mktime);

    $weekday = $PIVOTX['locale']->getWeekday($day);
    $month = $PIVOTX['locale']->getMonth($en_mo);
    $format=str_replace("%en_day%", $en_da, $format);
    $format=str_replace("%en_daynum%", $day, $format);
    $format=str_replace("%en_dayname%", $weekday, $format);
    $format=str_replace("%en_dname%", $PIVOTX['locale']->getWeekdayAbbrev($weekday), $format);
    $format=str_replace("%en_weekday%", $weekday, $format);
    $format=str_replace("%en_weeknum%", @date("W",$mktime), $format);
    $format=str_replace("%en_month%", $en_mo, $format);
    $format=str_replace("%en_monthname%", $month, $format);
    $format=str_replace("%en_monname%", $PIVOTX['locale']->getMonthAbbrev($month), $format);
    $format=str_replace("%en_year%", $en_yr, $format);
    $format=str_replace("%en_ye%", substr($en_yr,2), $format);

    $format=str_replace("%en_aye%", "&#8217;".substr($en_yr,2), $format);
    $format=str_replace("%en_ordday%", 1*$en_da, $format);
    $format=str_replace("%en_ordmonth%", 1*$en_mo, $format);

    return $format;
}



/**
 * Get a date in RFC 2822 format, which is _not_ localised!!
 *
 * @see http://www.faqs.org/rfcs/rfc2822
 *
 * @param string $time
 * @return string
 */
function getRfcDate($time) {

    $day_array = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
    $month_array = array("","Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

    $date = date("r", $time);
    $date_arr = preg_split("/[ ]+/", $date);

    $date_arr[0] = $day_array[ date("w", $time) ] . ",";
    $date_arr[2] = $month_array[ date("n", $time) ];

    $date = implode(" ", $date_arr);

    return $date;

}

/**
 * Gets current Unix timestamp (in seconds) with microseconds, as a float.
 *
 * @return float
 */
function getMicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}


/**
 * Calculates time that was needed for execution.
 *
 * @param string $type
 * @return string
 */
function timeTaken($type="") {
    global $starttime;
    $endtime = getMicrotime();
    $time_taken = $endtime - $starttime;
    $time_taken= number_format($time_taken, 3);  // optional

    if ($type=="int") {
        return $time_taken;
    } else {
        return "<span class='timetaken'>$time_taken</span>";
    }
}



/** 
 * Merges a split date (using European day/month ordering for 
 * input) and returns the date in standard PivotX format.
 *
 * @param string $date
 * @param string $time
 * @return string
 */
function fixDate($date, $time) {

    list($day, $month, $year) = preg_split('#[ /.:-]#',$date);
    @list($hour,$minute,$sec) = preg_split('#[ /.:-]#',$time);

    return sprintf("%04d-%02d-%02d-%02d-%02d", $year, $month, $day, $hour, $minute);

}


/**
 * Cleans up/fixes a relative paths.
 *
 * As an example '/site/pivotx/../index.php' becomes '/site/index.php'.
 * In addition (non-leading) double slashes are removed.
 *
 * @param string $path
 * @return string
 */
function fixPath($path, $nodoubleleadingslashes=false) {
    $path = str_replace("\/", "/", $path);
    // Handle double leading slash (that shouldn't be removed).
    if (!$nodoubleleadingslashes && (strpos($path,'//') === 0)) {
        $lead = '//';
        $path = substr($path,2);
    } else {
        $lead = '';
    }
    $path      = preg_replace('#/+#', '/', $path);
    $patharray = explode('/', $path);
    foreach ($patharray as $item) {
        if ($item == "..") {
            // remove the previous element
            @array_pop($new_path);
        } else if ($item == "http:") {
            // Don't break for URLs with http:// scheme
            $new_path[]="http:/";
        } else if ($item == "https:") {
            // Don't break for URLs with https:// scheme
            $new_path[]="https:/";            
        } else if ( ($item != ".") ) {
            $new_path[]=$item;
        }
    }
    return $lead.implode("/", $new_path);
}


/**
 * Ensures that a path has no trailing slash
 *
 * @param string $path
 * @return string
 */
function stripTrailingSlash($path) {
    if(substr($path,-1,1) == "/") {
        $path = substr($path,0,-1);
    }
    return $path;   
}


/**
 * Ensures that a path has a trailing slash
 *
 * @param string $path
 * @return string
 */
function addTrailingSlash($path) {
    if(substr($path,-1,1) != "/") {
        $path .= "/";
    }
    return $path;   
}



/**
 * Recursively creates chmodded directories. Returns true on success, 
 * and false on failure.
 *
 * NB! Directories are created with permission 777 - worldwriteable -
 * unless you have set 'chmod_dir' to 0XYZ in the advanced config.
 *
 * @param string $name
 * @return boolean
 */
function makeDir($name) {
    global $PIVOTX;

    // if it exists, just return.
    if (file_exists($name)) {
        return true;
    }

    // If more than one level, try parent first..
    // If creating parent fails, we can abort immediately.
    if (dirname($name) != ".") {
        $success = makeDir(dirname($name));
        if (!$success) {
            return false;
        }
    }

    // use permission if set in config
    if (isset($PIVOTX['config'])){
        $mode = $PIVOTX['config']->get('chmod_dir');
    }
    if (empty($mode)) {
        $mode = '0777';
    }
    $mode_dec = octdec($mode);

    $oldumask = umask(0);
    $success = @mkdir ($name, $mode_dec);
    @chmod ($name, $mode_dec);
    umask($oldumask);

    return $success;

}


/**
 * Calculates the number of days in a month, taking into account leap years.
 *
 * Code adapted from the Calendar class/extension.
 *
 * @param integer $month
 * @param integer $year
 * @return integer
 */
function getDaysInMonth($month, $year) {
    $daysinmonth = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    if ($month < 1 || $month > 12) {
        return 0;
    }

    $d = $daysinmonth[$month];

    if ($month == 2) {
        // Check for leap year
        // Forget the 4000 rule, I doubt I'll be around then...

        if ($year%4 == 0) {
            if ($year%100 == 0) {
                if ($year%400 == 0) {
                    $d = 29;
                }
            } else {
                $d = 29;
            }
        }
    }

    return $d;
}



function getDateRange($date, $unit) {

    list($yr,$mo,$da,$ho,$mi) = explode("-",$date);

    $yr_min = $yr_max = $yr;
    $mo_min = $mo_max = $mo;

    if ($unit=='day') {

        $range_min = date("Y-m-d-00-00", mktime(0,0,0,$mo,$da,$yr));
        $range_max = date("Y-m-d-23-59", mktime(0,0,0,$mo,$da,$yr));

    } else if ($unit=='week') {

        $dow = ((@date("w", mktime(0,0,0,$mo,$da,$yr)) + 6) % 7);
        $range_min = @date("Y-m-d-00-00", mktime(0,0,0,$mo,($da-$dow),$yr));
        $range_max = @date("Y-m-d-23-59", mktime(0,0,0,$mo,($da-$dow+6),$yr));

    } else if ($unit=='month'){

        $da_min = '01';
        $da_max = getDaysInMonth(intval($mo_max),$yr_max);

        // put the ranges back together.
        $range_min = sprintf("%02d-%02d-%02d-00-00", $yr_min,$mo_min,$da_min);
        $range_max = sprintf("%02d-%02d-%02d-23-59", $yr_max,$mo_max,$da_max);

    } else {

        $mo_min = '01';
        $mo_max = '12';
        $da_min = '01';
        $da_max = '31';

        // put the ranges back together.
        $range_min = sprintf("%02d-%02d-%02d-00-00", $yr_min,$mo_min,$da_min);
        $range_max = sprintf("%02d-%02d-%02d-23-59", $yr_max,$mo_max,$da_max);

    }

    return array($range_min, $range_max);

}

/**
 * Get the start and enddate for an archivename like '2007-10' or '2006-w32'
 *
 * @param unknown_type $name
 */
function archivenameToDates($name) {

    // Pregmatch the given archive name
    preg_match('/([0-9]*)(-([wm]?)([0-9]*))?/i', strtolower($name), $matches);

    // This will give something like:
    //    Array (
    //    [0] => 2007-w10
    //    [1] => 2007
    //    [2] => -w10
    //    [3] => w
    //    [4] => 10
    //)

    if ($matches[4]=="") {
        // We want a whole year
        return getDateRange($matches[1], 'year');
    }

    if ($matches[3]!="w") {
        // We assume we want a month
        $month = sprintf("%02d-%02d-05", $matches[1], $matches[4]);
        return getDateRange($month, 'month');
    } else {
        // We want a single week

        // First we check what week the seventh of january falls in:
        $weekjan7 = date("W", mktime (1, 1, 1, 1, 7, $matches[1]));

        // If jan7 falls in week2, we need to substract 1 from the weekdays
        if (intval($weekjan7)==2) {
            $daynum = 7 * ( $matches[4] - 1);
        } else {
            $daynum = 7 * $matches[4];
        }

        $day = date("Y-m-d", mktime (1, 1, 1, 1, $daynum, $matches[1]));
        return getDateRange($day, 'week');


    }



}



/**
 * Load the translation table that is used to convert textual emoticons
 * to their graphical counterpart
 *
 * @return void
 */
function initEmoticons() {
    global $emot, $PIVOTX, $emoticon_window, $emoticon_window_width, $emoticon_window_height;

    if(!defined('_EMOTICONS_INCLUDED'))  {
        define('_EMOTICONS_INCLUDED',1);

        if (file_exists($PIVOTX['paths']['pivotx_path']."includes/emoticons/config.inc.php")) {
            include ($PIVOTX['paths']['pivotx_path']."includes/emoticons/config.inc.php");

            $path = fixpath(sprintf("%sincludes/emoticons/%s", $PIVOTX['paths']['pivotx_url'], $emoticon_images));

            foreach ($emot as $emot_code => $emot_file) {
                $emot[$emot_code]=sprintf("<img src='%s%s' alt='%s' />", $path, $emot_file, addslashes($emot_code) );
            }
        }
    }


}

/**
 * Convert textual emoticons into their graphical counterpart.
 *
 * @param string $text
 * @return string
 */
function emoticonize($text) {
    global $emot;

    initEmoticons();

    // Loop through the emoticons, replacing them one by one.
    foreach ($emot as $emot_code => $emot_html) {
        $text = preg_replace('/(?!<.*?)('.preg_quote($emot_code,'/').')(?![^<>]*?>)/si', $emot_html, $text);
    }

    return $text;

}


/**
 * Searches in (HTML) text for the value of a attribute.
 *
 * For example, after calling:
 * <code>$my_value=getAttrValue('size', 'color="green" size="12"');</code>
 * my_value will contain 12
 *
 * @param string $att_name Name of attribuite to find the value of
 * @param string $attributes Text which contains the attribute set to a value
 */
function getAttrValue($att_name, $attributes) {

    // first, we need do do some tricks to find out where we'll have
    // to split the $attributes string
    $attributes=stripslashes(str_replace("&quot;",'"', $attributes));
    $this_attr=substr($attributes, strpos($attributes,$att_name));
    $pos=strpos($attributes,$att_name);

    // then we split it, using a regex
    if (preg_match("/$att_name=\"([^\"]*)\"/i", $attributes, $parts)) {
        return $parts[1];
    } else {
        return "";
    }
}

function escape($i) {
    //global $encode_html, $decode_html;

    $i = stripslashes($i);
    //$i = strtr($i, $decode_html);

    // according to the php manual, these have to be translated back
    // in order to output proper html
    // '&' (ampersand) becomes '&amp;'
    // '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
    // ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set.
    // '<' (less than) becomes '&lt;'
    // '>' (greater than) becomes '&gt;'

    $i = str_replace ('&lt;', '<', $i);
    $i = str_replace ('&gt;', '>', $i);
    $i = str_replace ('&quot;', '"', $i);
    $i = str_replace ('&#039;', "'", $i);
    $i = str_replace ('&amp;', "&", $i);

    return $i;
}



/**
 * Converts all applicable characters, encoded in UTF-8, to HTML entities.
 *
 * Currently the function does nothing for PHP version prior to 4.3.0.
 *
 * @param string $i
 * @return string
 */
function entify($i) {
    if(checkVersion(PHP_VERSION, "4.3.0") ) {
        $i = @htmlentities( $i, ENT_NOQUOTES, "UTF-8");
    }
    return $i;
}

/**
 * Converts all HTML entities to their applicable characters encoded in UTF-8.
 *
 * Currently the function does nothing for PHP version prior to 4.3.0.
 *
 * @param string $i
 * @return string
 */
function unentify($i) {
    if(checkVersion(PHP_VERSION, "4.3.0") ) {
        $i = @html_entity_decode( $i, ENT_NOQUOTES, "UTF-8");
    }
     return $i;
}

/**
 * Replaces ' / " with &#039; / &quot; in the text $i.
 *
 * @param string $i
 * @return string
 */
function entifyQuotes($i) {

    $i= str_replace("'", '&#039;', $i);
    $i= str_replace('"', '&quot;', $i);

    return $i;

}

/**
 * Replaces & (which isn't part of an HTML entity) with &amp; in the text $i.
 *
 * @param string $i
 * @return string
 */
function entifyAmpersand($i) {

    $i = preg_replace("/&(?!(#[0-9]+|[a-z]+);)/i", "&amp;", $i);

    return $i;

}

/**
 * Replaces &gt; / &lt; with &amp;gt; / &amp;lt; in the text $i.
 *
 * @param string $i
 * @return string
 */
function addltgt($i) {

    $i= str_replace('&gt;', '&amp;gt;', $i);
    $i= str_replace('&lt;', '&amp;lt;', $i);

    return $i;

}


/**
 * Checks if text is HTML (crap) saved by/pasted
 * from Microsoft word.
 */
function isWordHtml($text) {

    $a = strpos($text, "<o:p></o:p>");
    $b = strpos($text, "MsoNormal");
    $c = strpos($text, "mso-bidi");
    $d = strpos($text, "xml:namespace");

    //echo "$a - $b - $c - $d";

    return ($a || $b || $c || $d);
}





/**
 * Simple function that strips off all the crap
 * Microsoft Word inserts when saving as HTML.
 *
 * Only the following tags are kept:
 * <code><b><i><u><a><br><p><em><strong></code>
 */
function stripWordHtml($text) {
    $text = stripslashes($text);
    $text = str_replace("<?xml:namespace", "<pom", $text);
    // Only keep the elements listed
    $text = strip_tags($text,'<b><i><u><a><br><p><em><strong>');
    // Remove all attributes from the URLs.
    $text = preg_replace ("/<a[^>]+href=([^ >]+)[^>]*>/", "<a href=\\1>", $text);
    // Remove all attributes (from the allowed elements)
    $text = preg_replace ("/<([b|i|u|br|p|em|strong])[^>]*>/","<\\1>", $text);
    // Replace blank/empty lines with two breaks to make a visible blank line
    $text = preg_replace('/^\s*$/m','<br /><br />', $text);

    return $text;
}




/**
 * Trim a text to a given length, taking html entities into account.
 *
 * Formerly we first removed entities (using unentify), cut the text at the
 * wanted length and then added the entities again (using entify). This caused
 * lot of problems so now we are using a trick from
 * http://www.greywyvern.com/code/php/htmlwrap.phps
 * where entities are replaced by the ACK (006) ASCII symbol, the text cut and
 * then the entities reinserted.
 *
 * @param string $str string to trim
 * @param int $length position where to trim
 * @param boolean $nbsp whether to replace spaces by &nbsp; entities
 * @param boolean $hellip whether to add &hellip; entity at the end
 *
 * @return string trimmed string
 */
function trimText($str, $length, $nbsp=false, $hellip=true, $striptags=true) {

    if ($striptags) {
        $str = strip_tags($str);
    }
    
    $str = trim($str);
    
    // Use the ACK (006) ASCII symbol to replace all HTML entities temporarily
    $str = str_replace("\x06", "", $str);
    preg_match_all("/&([a-z\d]{2,7}|#\d{2,5});/i", $str, $ents);
    $str = preg_replace("/&([a-z\d]{2,7}|#\d{2,5});/i", "\x06", $str);

    if (function_exists('mb_strwidth') ) {
        if (mb_strwidth($str)>$length) {
            $str = mb_strimwidth($str,0,$length+1, '', 'UTF-8');
            if ($hellip) {
                $str .= '&hellip;';
            }
        }
    } else {
        if (strlen($str)>$length) {
            $str = substr($str,0,$length+1);
            if ($hellip) {
                $str .= '&hellip;';
            }
        }
    }

    if ($nbsp==true) {
        $str=str_replace(" ", "&nbsp;", $str);
    }

    $str=str_replace("http://", "", $str);

    // Put captured HTML entities back into the string
    foreach ($ents[0] as $ent) {
        $str = preg_replace("/\x06/", $ent, $str, 1);
    }

    return $str;

}

/**
 * Wraps a string to a given number of characters, taking html entities into account.
 *
 * To avoid a lot of problems with html_entity_decode we are using a trick from
 * http://www.greywyvern.com/code/php/htmlwrap.phps
 * where entities are replaced by the ACK (006) ASCII symbol, the string wrapped and
 * then the entities reinserted.
 *
 * @param string $str string to wrap
 * @param int $width position where to wrap
 * @param string $break string used to break the line
 * @param boolean $cut If set to TRUE, always wrap at or before the specified width.
 *
 * @return string wrapped string
 */
function wordwrapHTMLEntities($str, $width=75, $break="\n", $cut=false) {

    // Use the ACK (006) ASCII symbol to replace all HTML entities temporarily
    $str = str_replace("\x06", "", $str);
    preg_match_all("/&([a-z\d]{2,7}|#\d{2,5});/i", $str, $ents);
    $str = preg_replace("/&([a-z\d]{2,7}|#\d{2,5});/i", "\x06", $str);

    $str = wordwrap($str, $width, $break, $cut);

    // Put captured HTML entities back into the string
    foreach ($ents[0] as $ent) {
        $str = preg_replace("/\x06/", $ent, $str, 1);
    }

    return $str;

}

/**
 * Decodes text using different levels. (Opposite of encode_text.)
 *
 * Currently 'minimal' and 'special' is supported. The later is the
 * same as reverting htmlspecialchars (with ENT_QUOTES). 'minimal' behaves
 * as 'special' except that "&lt;" / "&gt;" is left untouched.
 *
 * @param string $text
 * @param string $level
 * @return string
 */
function decodeText($text,$level = 'minimal') {
    if ($level == 'minimal') {
        $text = str_replace(array('&quot;','&#39;','&#039;','&amp;'),array('"','\'','\'','&'),$text);
        return $text;
    } else if ($level == 'special') {
        if (function_exists('htmlspecialchars_decode')) {
            return htmlspecialchars_decode($text,ENT_QUOTES);
        } else {
            $text = str_replace(array('&quot;','&#039;','&amp;'),array('"','\'','&'),$text);
            $text = str_replace(array('&lt;','&gt;'),array("<",">"),$text);
            return $text;
        }
    } else {
        debug("Unknown level - text not decoded");
        return $text;
    }
}

/**
 * Encodes text using different levels.
 *
 * Currently 'minimal' and 'special' is supported. The later is the
 * same as htmlspecialchars (with ENT_QUOTES). 'minimal' behaves
 * as 'special' except that "<" / ">" is left untouched.
 *
 * @param string $text
 * @param string $level
 * @return string
 */
function encodeText($text,$level = 'minimal') {
    if ($level == 'minimal') {
        $text = entifyQuotes($text);
        $text = entifyAmpersand($text);
        return $text;
    } else if ($level == 'special') {
        return htmlspecialchars($text,ENT_QUOTES);
    } else {
        debug("Unknown level - text not encoded");
        return $text;
    }
}

/**
 * Creates a Javascript encoded mailto link.
 *
 * If encoding of email addresses is disabled in the weblog config
 * and $encrypt is false, it outputs a plain HTML mailto link.
 *
 * @param string $mail
 * @param string $text Text of mailto link.
 * @param string $title Title for the mailto link.
 * @param boolean $encrypt
 * @return string
 */
function encodeMailLink($mail, $text, $title="", $encrypt=false) {
    global $PIVOTX;

    if (!isEmail($mail)) {
        debug("Provided email address isn't valid");
        return $text;
    }

    if ($text == "") {
        $text = __('Email');
    }

    if ($title != "") {
        $title = strip_tags($title);
        $title = str_replace("'", '&#039;', $title);
        $title = str_replace('"', '&quot;', $title);
    }

    if ($PIVOTX['config']->get('encode_email_addresses') || $encrypt) {
        require_once "modules/safeaddress.inc.php";
        $mail = safeAddress($mail, $text, $title, 1, 0);
        return $mail;
    } else {
        return "<a href='mailto:$mail' title='$title'>$text</a>";
    }

}


/**
 * Wrapper for Textile.
 *
 * @param string $str
 * @return string
 */
function pivotxTextile($str) {
    global $textile, $PIVOTX;

    if (isset($textile)) {

        $output = $textile->TextileThis($str);
        return $output;

    } else  if (file_exists($PIVOTX['paths']['pivotx_path']."includes/textile/classtextile.php")) {

        include_once($PIVOTX['paths']['pivotx_path']."includes/textile/classtextile.php");

        $textile = new Textile;

        $output = $textile->TextileThis($str);
        return $output;

    } else {

        return $str;

    }

}


/**
 * Wrapper for Markdown/SmartyPants
 *
 * @param string $str
 * @param integer $with_smartypants If equal to 4 SmartyPants is also used.
 * @return string
 */
function pivotxMarkdown($str, $with_smartypants=0) {
    global $PIVOTX;

    if (file_exists($PIVOTX['paths']['pivotx_path']."/includes/markdown/markdown.php")) {

        include_once $PIVOTX['paths']['pivotx_path']."/includes/markdown/markdown.php";

        $output = markdown($str);

        if ($with_smartypants == 4) {
            include_once $PIVOTX['paths']['pivotx_path']."/includes/markdown/smartypants.php";
            $output = SmartyPants($output);
        }

        return $output;

    } else {

        debug("couldn't find includes/markdown/markdown.php");

        return $str;

    }

}


/**
 * Get the link to edit an entry directly from the frontpage.
 *
 * @param string $text
 * @param integer $uid
 * @param string $prefix
 * @param string $postfix
 * @param string $type
 * @return string
 */
function getEditlink($name, $uid, $prefix, $postfix, $type="entry") {
    global $PIVOTX;

    if ($PIVOTX['session']->isLoggedIn()) {

        $link = makeAdminPageLink($type);
        $link .= "&amp;uid=$uid";

        $output = sprintf("%s<a href='%s'>%s</a>%s", $prefix, $link, $name, $postfix);

    } else {
        $output = "";
    }

    return $output;

}



/**
 * Get the link to edit or delete comments directly from the entrypage.
 *
 * @param integer $uid
 * @param integer $count
 * @return string
 */
function getEditCommentLink($uid=0, $number) {
    global $PIVOTX;

    if (isset($_COOKIE['pivotxsession'])) {

        $editlink = makeAdminPageLink('comments') . '&amp;uid=' . $uid;

        $output = sprintf("(<a href='%s&amp;edit=%s'>%s</a>", $editlink, $number, __('Edit'));
        $output .= sprintf(" / <a href='%s&amp;del=%s'>%s</a>)", $editlink, $number, __('Delete'));

    } else {
        $output = "";
    }

    return $output;

}



/**
 * Get the link to edit or delete trackbacks directly from the entrypage.
 *
 * @param integer $uid
 * @param integer $count
 * @return string
 */
function getEditTrackbackLink($uid=0, $number) {
    global $PIVOTX;

    if (isset($_COOKIE['pivotxsession'])) {

        $editlink = makeAdminPageLink('trackbacks') . '&amp;uid=' . $uid;

        $output = sprintf("(<a href='%s&amp;edit=%s'>%s</a>", $editlink, $number, __('Edit'));
        $output .= sprintf(" / <a href='%s&amp;del=%s'>%s</a>)", $editlink, $number, __('Delete'));

    } else {
        $output = "";
    }

    return $output;

}



/**
 * Formats comments according the settings for the current weblog.
 *
 * We strip _all_ tags except <<b>> and <<i>> and after that
 * we convert everything that looks like a url or mail-address
 * to the equivalent link (if enabled). Using textile if enabled.
 *
 * @param string $text
 * @return string
 */
function commentFormat($text, $striplinebreaks=false ) {
    global $PIVOTX;

	// If the hidden configuration option 'allow_html_in_comments' is set, we
	// allow HTML tags. Use at your own risk!
	if ($PIVOTX['config']->get('allow_html_in_comments')==1) {
		$text = stripTagsAttributes($text, "*");
	} else {
	    $text = stripTagsAttributes($text, "<b><em><i><strong>");	
	}

	// If the hidden configuration option 'allow_smarty_in_comments' is set, we
	// allow Smarty tags. You are an idiot if you use this in a site that's
	// publicly available on the internet!
	if ($PIVOTX['config']->get('allow_smarty_in_comments')==1) {
		$text = parse_intro_or_body($text);
	}

    if( $PIVOTX['weblogs']->get('', 'comment_textile') == 1 ) {

        if ( $PIVOTX['weblogs']->get('', 'comment_texttolinks') == 1 ) {
            // the old-style automatic links are converted to textile links.
            $text = preg_replace("/([ \t]|^)www\./mi", "\\1http://www.", $text);
            $text = preg_replace("#([ \t]|^)(http://[^ )\r\n]+)#mi", "\\1\"\\2\":\\2", $text);

            // Fix wrongfully matched images..
            $text = preg_replace('/"http:\\/\/([-a-z0-9_.\/]*)!":http:\/\/([-a-z0-9_.\/]*)!/Ui', '!http://\\1!', $text);

            $text =  preg_replace("/([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)+))/i",
                "<a href=\"mailto:\\1\">\\1</A>",$text);
        }

        $text = pivotxTextile( $text );

        // make textile also obey the target setting
        $text = preg_replace('#<a href="(http://[^"]+)">([^<]+)</a>#i', "<a href=\"\\1\">[[\\2]]</a>",$text);
        $text = preg_replace('#<a href="(https://[^"]+)">([^<]+)</a>#i', "<a href=\"\\1\">[[\\2]]</a>",$text);
        $text = preg_replace('#<a href="(ftp://[^"]+)">([^<]+)</a>#i', "<a href=\"\\1\">[[\\2]]</a>",$text);

    } else if ( $PIVOTX['weblogs']->get('', 'comment_texttolinks') == 1 ) {

        $text = preg_replace("#([ \t]|^)www\.#mi", "\\1http://www.",$text);
        $text = preg_replace("#([ \t]|^)ftp\.#mi", "\\1ftp://ftp.",$text);
        $text = preg_replace("#([ \t]|^)(http://[^ )\r\n]+)#mi", "\\1<a href=\"\\2\">[[\\2]]</a>", $text);
        $text = preg_replace("#([ \t]|^)(https://[^ )\r\n]+)#mi", "\\1<a href=\"\\2\">[[\\2]]</a>", $text);
        $text = preg_replace("#([ \t]|^)(ftp://[^ )\r\n]+)#mi", "\\1<a href=\"\\2\">[[\\2]]</a>", $text);

        preg_match_all ("|\[\[(.*)\]\]|U", $text, $match, PREG_PATTERN_ORDER);

        // do we need to do changes?
        if(( is_array( $match )) && ( count( $match ) > 0 )) {
            foreach( $match[1] as $url ) {
                $url2 = str_replace( '@', '%40', $url );
                $text = str_replace( $url, $url2, $text );
            }
        }

        $text =  preg_replace("#([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0 -9-]+)+))#i",
            "<a href=\"mailto:\\1\">\\1</a>",$text);

        // now change the '@' back...
        $text = str_replace( '%40','@',$text );
    }


    // If not using Textile convert linebreaks to HTML breaks.
    if( ($PIVOTX['weblogs']->get('', 'comment_textile')!= 1) && ($PIVOTX['config']->get('comment_no_formatting')!=1) ) {
        $text = nl2br( trim( $text ));
    }



    // then make long urls into short urls, with correct link..
    preg_match_all ("|\[\[(.*)\]\]|U", $text, $match, PREG_PATTERN_ORDER);

    foreach( $match[1] as $url ) {
        if( strlen( $url ) > 40 ) {
            $s_url = substr( $url,0,40 ).'..';
        } else {
            $s_url = $url;
        }
        $text = str_replace( '[['.$url.']]',$s_url,$text );
    }

    // perhaps redirect the link..
    if( $PIVOTX['weblogs']->get('', 'lastcomm_redirect') == 1 ) {
        //$text = str_replace(  'href="http://','href="'.$PIVOTX['paths']['pivotx_url'].'includes/re.php?http://',$text );
        $text = preg_replace("#<a href=(\"|')([^>\n]+)\\1([^<>]*)>(.*)</a>#iUs",
            "<a href=\"\\2\" \\3 rel='nofollow'>\\4</a>",$text);
    }

    if ($PIVOTX['weblogs']->get('', 'emoticons') == 1 ) {
        $text=emoticonize($text);
    }

    if($striplinebreaks){
        $text = str_replace("\r\n", "", $text);
        $text = str_replace("\n", "", $text);
    }

    return ($text);
}


/**
 * Formats trackbacks according the settings for the current weblog.
 *
 * We strip _all_ tags except <<b>> and <<i>> and after that
 * we convert everything that looks like a url or mail-address
 * to the equivalent link (if enabled).
 *
 * @param string $text
 * @return string
 */
function trackbackFormat($text) {
    global $PIVOTX;

    $text = trim( strip_tags( $text,'<b>,<i>,<em>,<strong>' ));

    if ($PIVOTX['weblogs']->get('', 'comment_texttolinks')) {
        $text = preg_replace("#([ \t]|^)www\.#i", " http://www.", $text);
        $text = preg_replace("#([ \t]|^)ftp\.#i", " ftp://ftp.", $text);
        $text = preg_replace("#(http://[^ )\r\n]+)#i", "<a  href=\"\\1\">[[\\1]]</a>", $text);
        $text = preg_replace("#(https://[^ )\r\n]+)#i", "<a  href=\"\\1\">[[\\1]]</a>", $text);
        $text = preg_replace("#(ftp://[^ )\r\n]+)#i", "<a  href=\"\\1\">[[\\1]]</a>", $text);
        
        // 2004/11/30 =*=*= JM - clear up messed ftp links with '@' in
        preg_match_all ("|\[\[(.*)\]\]|U", $text, $match, PREG_PATTERN_ORDER);

        // do we need to do changes?
        if(( is_array( $match )) && ( count( $match ) > 0 )) {
            foreach( $match[1] as $url ) {
                $url2 = str_replace( '@', '%40', $url );
                $text = str_replace( $url, $url2, $text );
            }
        }
        $text =  preg_replace("#([-a-z0-9_]+(\.[_a-z0-9-]+)*@([a-z0-9-]+(\.[a-z0 -9-]+)+))#i",
            "<a href=\"mailto:\\1\">\\1</a>",$text);

        // now change the '@' back...
        $text = str_replace( '%40','@',$text );

        // then make long urls into short urls, with correct link..
        preg_match_all ("|\[\[(.*)\]\]|U", $text, $match, PREG_PATTERN_ORDER);

        foreach( $match[1] as $url ) {
            if( strlen( $url ) > 40 ) {
                $s_url = substr( $url,0,40 ).'..';
            } else {
                $s_url = $url;
            }
            $text = str_replace( '[['.$url.']]',$s_url,$text );
        }

        // perhaps redirect the link..
        if( $PIVOTX['weblogs']->get('', 'lastcomm_redirect')) {
            //$text = str_replace(  'href="http://','href="'.$PIVOTX['paths']['pivotx_url'].'includes/re.php?http://',$text );
            $text = preg_replace("#<a href=(\"|')([^>\n]+)\\1([^<>]*)>(.*)</a>#iUs",
                "<a href=\"\\2\" \\3 rel='nofollow'>\\4</a>",$text);
        }
    }

    $text = nl2br( trim( $text ));

    if ($PIVOTX['weblogs']->get('', 'emoticons')) {
        $text=emoticonize($text);
    }

    return (stripslashes($text));
}



/**
 * Loads a serialized file, unserializes it, and returns it.
 * 
 * If the file isn't readable (or doesn't exist) or reading it fails, 
 * false is returned. 
 *
 * @param string $filename
 * @param boolean $silent Set to true if you want an visible error.
 * @return mixed
 */
function loadSerialize($filename, $silent=false) {
    global $PIVOTX;

    $filename = fixpath($filename);

    if (!is_readable($filename)) {

        // If we're setting up PivotX, we can't set the paths before we initialise
        // the configuration and vice-versa. So, we just bail out if the paths aren't
        // set yet.
        if(empty($PIVOTX['paths']['pivotx_path'])) { return; }

        if (is_readable($PIVOTX['paths']['pivotx_path'].$filename)) {
            $filename = $PIVOTX['paths']['pivotx_path'].$filename;
        } else {
            $filename = "../".$filename;
        }
    }

    if (!is_readable($filename)) {

        if ($silent) { 
            return FALSE; 
        }

        $message = str_replace("%name%", $filename, "A needed file ('%name%') could not be read. <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). Else go <a href='javascript:history.go(-1)'>back</a> to the last page.");
        renderErrorpage("File is not readable!", $message);
    }

    $serialized_data = trim(implode("", file($filename)));

    $serialized_data = str_replace("<?php /* pivot */ die(); ?>", "", $serialized_data);

    @$data = unserialize($serialized_data);
    if (is_array($data)) {
        return $data;
    } else {
        $temp_serialized_data = preg_replace("/\r\n/", "\n", $serialized_data);
        if (@$data = unserialize($temp_serialized_data)) {
            return $data;
        } else {
            $temp_serialized_data = preg_replace("/\n/", "\r\n", $serialized_data);
            if (@$data = unserialize($temp_serialized_data)) {
                return $data;
            } else {
                return FALSE;
            }
        }
    }
}

// This function serializes some data and then saves it.
function saveSerialize($filename, &$data) {
    global $PIVOTX;

    $filename = fixPath($filename);

    $ser_string = "<?php /* pivot */ die(); ?>".serialize($data);

    // disallow user to interrupt
    ignore_user_abort(TRUE);

    $old_umask = umask(0111);

    if (isset($PIVOTX['config']) && ($PIVOTX['config']->get('unlink') == 1) && (file_exists($filename))) {
        /* unlinking is good for some safe_mode users */
        /* and bad for some others.. i hate safe_mode */
        @unlink($filename);
    }

    // open the file and lock it.
    if($fp=fopen($filename, "a")) {
        
        if (flock( $fp, LOCK_EX | LOCK_NB )) {

            // Truncate the file (since we opened it for 'appending')
            ftruncate($fp, 0); 

            // Write to our locked, empty file.
            if (fwrite($fp, $ser_string)) {
                flock( $fp, LOCK_UN );
                fclose($fp);
            } else {
                flock( $fp, LOCK_UN );
                fclose($fp);
    
                // todo: handle errors better.
                echo("Error opening file<br/><br/>The file <b>$filename</b> could not be written! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
                die();
                return FALSE;
            }
            
        } else {
            fclose($fp);
            
            // todo: handle errors better.
            echo("Error opening file<br/><br/>Could not lock <b>$filename</b> for writing! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
            die();
            return FALSE; 
            
        }
        
    } else {
        // todo: handle errors better.
        echo("Error opening file<br/><br/>The file <b>$filename</b> could not be opened for writing! <br /><br />Try logging in with your ftp-client and check to see if it is chmodded to be readable by the webuser (ie: 777 or 766, depending on the setup of your server). <br /><br />Current path: ".getcwd()."." );
        debug_printbacktrace();
        die();
        return FALSE;
    }
    umask($old_umask);

    // reset the users ability to interrupt the script
    ignore_user_abort(FALSE);


    return TRUE;

}


/**
 *  Saves a file, and outputs some feedback, if wanted.
 */
function writeFile($filename, $output, $mode='w') {
    global $PIVOTX, $VerboseGenerate;

    if ($VerboseGenerate) {
        _e('Write').": ".$filename."<br />\n";
    }

    // open up..
    $opened = false;
    if ($fh = @fopen( $filename, $mode)) {
        $opened = true;
    } else {
        if ($fh = @fopen( fixpath($PIVOTX['paths']['pivotx_path'].$filename), 'w' )) {
            $opened = true;
        }
    }

    // if opening failed it's no reason to continue
    if (!$opened) {
        debug("Unable to open (handle to) $filename - can not write to file");
        if ($VerboseGenerate) {
            _e('Write Error. Could not open file for writing').": ".$filename."<br />\n";
        }
        return;
    }

    // wrrrriting!
    if(!fwrite($fh, $output)) {
        if ($VerboseGenerate) {
            _e('Write Error. Could not write to file').": ".$filename."<br />\n";
        }
    }

    fclose( $fh );
    chmodFile($filename);
}

/**
 * Chmods a file (according to the configuration).
 */
function chmodFile($filename) {
    global $PIVOTX;
    $chmod = $PIVOTX['config']->get('chmod');
    $oldumask = umask(0);
    // to avoid typecasting misery, just use some ugly hardcoded if's
    if ($chmod=='0777') {
        @chmod ($filename, 0777);
    } else if ($chmod=='0755') {
        @chmod ($filename, 0755);
    } else if ($chmod=='0666') {
        @chmod ($filename, 0666);
    } else if ($chmod=='0655') {
        @chmod ($filename, 0655);
    } else {
        @chmod ($filename, 0644);
    }
    umask($oldumask);
}




/**
 * Returns a "safe" version of the given string - basically only US-ASCII and
 * numbers. Needed because filenames and titles and such, can't use all characters.
 *
 * @param string $str
 * @param boolean $strict
 * @return string
 */
function safeString($str, $strict=false, $extrachars="") {

    // replace UTF-8 non ISO-8859-1 first
    $str = strtr($str, array(
        "\xC3\x80"=>'A', "\xC3\x81"=>'A', "\xC3\x82"=>'A', "\xC3\x83"=>'A',
        "\xC3\x84"=>'A', "\xC3\x85"=>'A', "\xC3\x87"=>'C', "\xC3\x88"=>'E',
        "\xC3\x89"=>'E', "\xC3\x8A"=>'E', "\xC3\x8B"=>'E', "\xC3\x8C"=>'I',
        "\xC3\x8D"=>'I', "\xC3\x8E"=>'I', "\xC3\x8F"=>'I', "\xC3\x90"=>'D',
        "\xC3\x91"=>'N', "\xC3\x92"=>'O', "\xC3\x93"=>'O', "\xC3\x94"=>'O',
        "\xC3\x95"=>'O', "\xC3\x96"=>'O', "\xC3\x97"=>'x', "\xC3\x98"=>'O',
        "\xC3\x99"=>'U', "\xC3\x9A"=>'U', "\xC3\x9B"=>'U', "\xC3\x9C"=>'U',
        "\xC3\x9D"=>'Y', "\xC3\xA0"=>'a', "\xC3\xA1"=>'a', "\xC3\xA2"=>'a',
        "\xC3\xA3"=>'a', "\xC3\xA4"=>'a', "\xC3\xA5"=>'a', "\xC3\xA7"=>'c',
        "\xC3\xA8"=>'e', "\xC3\xA9"=>'e', "\xC3\xAA"=>'e', "\xC3\xAB"=>'e',
        "\xC3\xAC"=>'i', "\xC3\xAD"=>'i', "\xC3\xAE"=>'i', "\xC3\xAF"=>'i',
        "\xC3\xB1"=>'n', "\xC3\xB2"=>'o', "\xC3\xB3"=>'o', "\xC3\xB4"=>'o',
        "\xC3\xB5"=>'o', "\xC3\xB6"=>'o', "\xC3\xB8"=>'o', "\xC3\xB9"=>'u',
        "\xC3\xBA"=>'u', "\xC3\xBB"=>'u', "\xC3\xBC"=>'u', "\xC3\xBD"=>'y',
        "\xC3\xBF"=>'y', "\xC4\x80"=>'A', "\xC4\x81"=>'a', "\xC4\x82"=>'A',
        "\xC4\x83"=>'a', "\xC4\x84"=>'A', "\xC4\x85"=>'a', "\xC4\x86"=>'C',
        "\xC4\x87"=>'c', "\xC4\x88"=>'C', "\xC4\x89"=>'c', "\xC4\x8A"=>'C',
        "\xC4\x8B"=>'c', "\xC4\x8C"=>'C', "\xC4\x8D"=>'c', "\xC4\x8E"=>'D',
        "\xC4\x8F"=>'d', "\xC4\x90"=>'D', "\xC4\x91"=>'d', "\xC4\x92"=>'E',
        "\xC4\x93"=>'e', "\xC4\x94"=>'E', "\xC4\x95"=>'e', "\xC4\x96"=>'E',
        "\xC4\x97"=>'e', "\xC4\x98"=>'E', "\xC4\x99"=>'e', "\xC4\x9A"=>'E',
        "\xC4\x9B"=>'e', "\xC4\x9C"=>'G', "\xC4\x9D"=>'g', "\xC4\x9E"=>'G',
        "\xC4\x9F"=>'g', "\xC4\xA0"=>'G', "\xC4\xA1"=>'g', "\xC4\xA2"=>'G',
        "\xC4\xA3"=>'g', "\xC4\xA4"=>'H', "\xC4\xA5"=>'h', "\xC4\xA6"=>'H',
        "\xC4\xA7"=>'h', "\xC4\xA8"=>'I', "\xC4\xA9"=>'i', "\xC4\xAA"=>'I',
        "\xC4\xAB"=>'i', "\xC4\xAC"=>'I', "\xC4\xAD"=>'i', "\xC4\xAE"=>'I',
        "\xC4\xAF"=>'i', "\xC4\xB0"=>'I', "\xC4\xB1"=>'i', "\xC4\xB4"=>'J',
        "\xC4\xB5"=>'j', "\xC4\xB6"=>'K', "\xC4\xB7"=>'k', "\xC4\xB8"=>'k',
        "\xC4\xB9"=>'L', "\xC4\xBA"=>'l', "\xC4\xBB"=>'L', "\xC4\xBC"=>'l',
        "\xC4\xBD"=>'L', "\xC4\xBE"=>'l', "\xC4\xBF"=>'L', "\xC5\x80"=>'l',
        "\xC5\x81"=>'L', "\xC5\x82"=>'l', "\xC5\x83"=>'N', "\xC5\x84"=>'n',
        "\xC5\x85"=>'N', "\xC5\x86"=>'n', "\xC5\x87"=>'N', "\xC5\x88"=>'n',
        "\xC5\x89"=>'n', "\xC5\x8A"=>'N', "\xC5\x8B"=>'n', "\xC5\x8C"=>'O',
        "\xC5\x8D"=>'o', "\xC5\x8E"=>'O', "\xC5\x8F"=>'o', "\xC5\x90"=>'O',
        "\xC5\x91"=>'o', "\xC5\x94"=>'R', "\xC5\x95"=>'r', "\xC5\x96"=>'R',
        "\xC5\x97"=>'r', "\xC5\x98"=>'R', "\xC5\x99"=>'r', "\xC5\x9A"=>'S',
        "\xC5\x9B"=>'s', "\xC5\x9C"=>'S', "\xC5\x9D"=>'s', "\xC5\x9E"=>'S',
        "\xC5\x9F"=>'s', "\xC5\xA0"=>'S', "\xC5\xA1"=>'s', "\xC5\xA2"=>'T',
        "\xC5\xA3"=>'t', "\xC5\xA4"=>'T', "\xC5\xA5"=>'t', "\xC5\xA6"=>'T',
        "\xC5\xA7"=>'t', "\xC5\xA8"=>'U', "\xC5\xA9"=>'u', "\xC5\xAA"=>'U',
        "\xC5\xAB"=>'u', "\xC5\xAC"=>'U', "\xC5\xAD"=>'u', "\xC5\xAE"=>'U',
        "\xC5\xAF"=>'u', "\xC5\xB0"=>'U', "\xC5\xB1"=>'u', "\xC5\xB2"=>'U',
        "\xC5\xB3"=>'u', "\xC5\xB4"=>'W', "\xC5\xB5"=>'w', "\xC5\xB6"=>'Y',
        "\xC5\xB7"=>'y', "\xC5\xB8"=>'Y', "\xC5\xB9"=>'Z', "\xC5\xBA"=>'z',
        "\xC5\xBB"=>'Z', "\xC5\xBC"=>'z', "\xC5\xBD"=>'Z', "\xC5\xBE"=>'z',
        ));
   
    // utf8_decode assumes that the input is ISO-8859-1 characters encoded 
    // with UTF-8. This is OK since we want US-ASCII in the end.
    $str = trim(utf8_decode($str));
    
    $str = strtr($str, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH",
        "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));

    $str=str_replace("&amp;", "", $str);

    $delim = '/';
    if ($extrachars != "") {
        $extrachars = preg_quote($extrachars, $delim);
    }
    if ($strict) {
        $str = strtolower(str_replace(" ", "-", $str));
        $regex = "[^a-zA-Z0-9_".$extrachars."-]";
    } else {
        $regex = "[^a-zA-Z0-9 _.,".$extrachars."-]";
    }

    $str = preg_replace("$delim$regex$delim", "", $str);

    return $str;
}



/**
 * Modify a string, so that we can use it for URI's. Like
 * safeString, but using hyphens instead of underscores.
 *
 * @param string $str
 * @param string $type
 * @return string
 */
function makeURI($str, $type='entry') {

    $str = safeString($str);

    $str = str_replace(" ", "-", $str);
    $str = strtolower(preg_replace("/[^a-zA-Z0-9_-]/i", "", $str));
    $str = preg_replace("/[-]+/i", "-", $str);

    $str = substr($str,0,64); // 64 chars ought to be long enough.

    // Make sure the URI isn't numeric. We can't have that, because it'll get
    // confused with the uids.
    if (is_numeric($str)) { 
        if ($type == 'entry') {
            $str = "e-".$str;
        } elseif ($type == 'page') {
            $str = "p-".$str; 
        } else {
            $str = "x-".$str; 
        }
    }

    return $str;

}

/**
 * Check if a requested uri is already taken,
 * Returns a valid unique uri by appending random stuff
 *
 * TODO: make it work for weblogs and other special items in pivotx too
 *
 * @param string $uri
 * @param int $code
 * @param string $key
 * @return string
 */
function uniqueURI($source_uri, $code=null, $type='page', $iterator=0) {
    global $PIVOTX;

	// debug("called uniqueURI($source_uri, $code, $type, $iterator)");

    if($iterator>=1) {
        $test_uri = $source_uri .'-'. $iterator;
    } else {
        $test_uri = $source_uri;
    }

	// debug("testing the following uri: $test_uri");
	
    // find out if there is an entry with the same uri
    $existing_entry = $PIVOTX['db']->get_entry_by_uri($test_uri);
    $foundentrycode = !empty($existing_entry['uid'])?$existing_entry['uid']:null;

    // find out if there is a page with the same uri
    $existing_page = $PIVOTX['pages']->getPageByUri($test_uri);
    $foundpagecode = !empty($existing_page['uid'])?$existing_page['uid']:null;

	// debug("entry: [ $foundentrycode ] - page: [ $foundpagecode ]");
	
    $iteratormax = getDefault( $PIVOTX['config']->get('max_unique_uri_iterations') , 20);
        
    if($iterator >= $iteratormax) {

        $PIVOTX['messages']->addMessage("Warning: You have too many entries or pages with the same title.");
        return $test_uri;
    }

    if(empty($foundentrycode) && empty($foundpagecode)) {
        // no existing code found, the uri is safe
        return $test_uri;
    } elseif(($code == '>') && (empty($foundentrycode) && empty($foundpagecode))) {
		// new entry
        // no existing code found, the uri is safe
        return $test_uri;
    } elseif ($type == 'entry' && $code == $foundentrycode) {
		// debug('same entry');
		// debug_printr($existing_entry);
        // code is same as current entry
        return $test_uri;
	} elseif($type == 'page' && $code == $foundpagecode) {
		// debug('same page');
        // debug_printr($existing_page);
        // code is same as current page
        return $test_uri;
    } else {
		// debug("** collision detected for the following uri: $test_uri");
        // there was a uri collision, increase the iterator and try again
        // this can go on forever
        $iterator++;
        return uniqueURI($source_uri, $code, $type, $iterator);
    }
}

/**
* Remove trailing whitespace from a given string. Not just spaces and linebreaks,
* but also &nbsp;, <br />'s and the like.
*/
function stripTrailingSpace($text) {
    global $PIVOTX;

    $text=trim($text)."[[end]]";
    $end_p = preg_match("~</p>\[\[end\]\]$~mi", $text);
    $text = preg_replace("~(&nbsp;|<br>|<br />|<p>|</p>|\n|\r|\t| )*\[\[end\]\]$~mi", "", $text);
    if ($end_p) { $text.="</p>"; }

    return $text;
}



/**
 * Make the 'excerpt', used for displaying entries and pages on the dashboard
 * as well as on the Entries and Pages overview screens.
 *
 * @param string $str
 * @param int $length
 * @return string
 */
function makeExcerpt($str, $length=180, $hellip=false) {
    global $PIVOTX;
        
    $oldsecuritysetting = $PIVOTX['template']->security;
    $oldphpsetting = $PIVOTX['template']->security_settings['PHP_TAGS'];
   
    // Never, ever allow PHP to be parsed, when making the excerpts..
    $PIVOTX['template']->security = true;   
    $PIVOTX['template']->security_settings['PHP_TAGS'] = false;

    // Ensure that the excerpt is parsed but prevent the [[smarty]] tags from being executed
    $str = parse_intro_or_body($str, false, 0, true);
    // remove the [[smarty]] tags for clean output
    $str = preg_replace('/\[\[.*\]\]/', ' ', $str);

    $from = array("&quot;", "\n", "\r", ">");
    $to = array('"', " ", " ", "> ");

    $excerpt = str_replace($from, $to, $str);
    $excerpt = strip_tags(@html_entity_decode($excerpt, ENT_NOQUOTES, 'UTF-8'));
    $excerpt = trim(preg_replace("/\s+/i", " ", $excerpt));
    $excerpt = trimText($excerpt, $length, false, $hellip);

    // reset the security setting..
    $PIVOTX['template']->security = $oldsecuritysetting;
    $PIVOTX['template']->security_settings['PHP_TAGS'] = $oldphpsetting;

    return $excerpt;

}



/**
 * Strip tags from a given $source, and also remove attributes / javascript handlers.
 * If $allowedtags is "*", it will use the most common 'safe' tags.
 * If $disabledattributes is empty, it will stip out all javascript handlers.
 *
 * @param string $source
 * @param string $allowedtags
 * @param mixed $disabledattributes
 */
function stripTagsAttributes($source, $allowedtags = "", $disabledattributes = "" )  {

    if ($allowedtags=="*") {
        $allowedtags = "<a><b><em><img><i><strong><div><p><span><ul><ol><li><address>" .
            "<blockquote><cite><hr><table><thead><tr><td><tbody><h1><h2><h3><h4><h5><h6>";
    }

    // Make sure $disabledattributes is an array..
    if (empty($disabledattributes)) {
        $disabledattributes = 'onclick|ondblclick|onkeydown|onkeypress|onkeyup|onload|" .
            "onmousedown|onmousemove|onmouseout|onmouseover|onmouseuponunload'; 
    } else if (is_array($disabledattributes)) {
        $disabledattributes = implode('|', $disabledattributes);    
    }

    $result = strip_tags($source, $allowedtags);    
    $result = preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . $disabledattributes . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", $result);
    
    return $result;

}


/**
 * adapted from an article by Allan Kent on phpbuilder.com
 * this function takes the current system time and date, and offsets
 * it to get the time and date we want to output to our users.
 */
function getCurrentDate() {
    global $PIVOTX;

    $date_time_array  = getdate();

    $hours =  $date_time_array["hours"];
    $minutes =  $date_time_array["minutes"];
    $seconds =  $date_time_array["seconds"];
    $month =  $date_time_array["mon"];
    $day =  $date_time_array["mday"];
    $year =  $date_time_array["year"];

    switch ($PIVOTX['config']->get('timeoffset_unit')) {

        case "y": $year += $PIVOTX['config']->get('timeoffset'); break;
        case "m": $month += $PIVOTX['config']->get('timeoffset'); break;
        case "d": $day += $PIVOTX['config']->get('timeoffset'); break;
        case "h": $hours += $PIVOTX['config']->get('timeoffset'); break;
        case "i": $minutes += $PIVOTX['config']->get('timeoffset'); break;

    }

    $timestamp =  mktime($hours ,$minutes, $seconds,$month ,$day, $year);

    return $timestamp;

}





/**
 * Sends notification for any type - currently only "entries", "comments"
 * and "visitor_registration".
 *
 * @param string $type
 * @param array $data
 * @return void
 */
function sendMailNotification($type, $data){
    global $PIVOTX;

    // FIXME:
    // $contact_addr used below is not set because there is really no
    // good setting for that - the comment_emailto setting for each email
    // isn't meant for the from header...
    if ($type == 'comment') {

        // splitting up input data
        $entry = $data[0];
        $comment = $data[1];
        if (isset($data[2]) && $data[2]) {
            debug("Notification of new comment surpressed (Most likely because moderation is active).");
            return;
        }

        // make a nice title for the mail..
        if (strlen($entry['title'])>2) {
            $title=$entry['title'];
            $title=strip_tags($title);
        } else {
            $title=substr($entry['introduction'],0,300);
            $title=strip_tags($title);
            $title=str_replace("\n","",$title);
            $title=str_replace("\r","",$title);
            $title=substr($title,0,60);
        }

        $id = safeString($comment["name"],TRUE) . "-" .  formatDate($comment["date"], "%ye%%month%%day%%hour24%%minute%");

        // Make the array of users that want to be notified via email..
        $notify_arr = array();

        foreach($entry['comments'] as $temp_comm) {
            if (($temp_comm['notify']==1) && (isEmail($temp_comm['email'])))    {
                $notify_arr[ $temp_comm['email'] ] = 1;
            }
            if (($temp_comm['notify']==0) && (isEmail($temp_comm['email'])))    {
                unset( $notify_arr[ $temp_comm['email'] ] );
            }
        }

        // don't send to the user that did the comment...
        if (isset($notify_arr[ $comment['email'] ])) {
            unset( $notify_arr[ $comment['email'] ] );
        }

        // send mail to those on the 'notify me' list..
        if (count($notify_arr)>0) {
            $userdata = $PIVOTX['users']->getUser($entry['user']);
            $contact_addr = $userdata['email'];
            $user = $userdata['nickname'];
            if (empty($user)) {
                $user = $entry['user'];
            }
            $body=sprintf(__('"%s" posted the following comment').":\n\n", unentify($comment['name']));
            $body.=sprintf("%s", unentify($comment['comment']));
            $body.=sprintf("\n\n-------------\n");
            $body.=sprintf(__('Name').": %s\n", unentify($comment['name']));
            $body.=sprintf(__('This is a comment on entry "%s"')."\n", $title);
            $body.=sprintf("\n%s:\n%s%s\n", __('View this entry'), $PIVOTX['paths']['host'], 
                makeFileLink($entry['code'], "", ""));
            $body.=sprintf("%s:\n%s%s\n", __('View this comment'), $PIVOTX['paths']['host'], 
                makeFileLink($entry['code'], "", $id));
            $body = decodeText($body,'special');

            $contact_name = '=?UTF-8?B?'.base64_encode($user).'?=';
            $header = sprintf("From: \"%s\" <%s>\n", $contact_name, $contact_addr);

            $subject = sprintf(__('New comment on entry "%s"'), $title);

            $sent = true;
            foreach($notify_arr as $addr => $val) {
                $addr = trim($addr);

                if(pivotxMail($addr, $subject, $body, $header)) {
                    debug("Sent Notify to $addr from '".$comment['name']."'");
                    $notified[] = sprintf("%s (%s)", $name, $addr);
                } else {
                    debug("Sending notifications failed");
                    $sent = false;
                    break;
                }

            }

            if ($sent) {
                return sprintf("%s: %s", __('Notifications were sent to') , implode(", ", $notified) );
            } else {
                return __('Failed to send notifications.');
            }

        }

    } else if ($type == 'entry') {

        $entry = $data;

        // make a nice title for the mail..
        if (strlen($entry['title'])>2) {
            $title=$entry['title'];
            $title=strip_tags($title);
        } else {
            $title=substr($entry['introduction'],0,300);
            $title=strip_tags($title);
            $title=str_replace("\n","",$title);
            $title=str_replace("\r","",$title);
            $title=substr($title,0,60);
        }
        $title = unentify($title);

        // Make the array of users that want to be notified via email..
        require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
        $visitors = new Visitors();
        $comment_users = $visitors->getUsers();
        $notify_arr = array();
        foreach ($comment_users as $commuserdata) {
            if ($commuserdata['verified'] && ($commuserdata['disabled'] != 1) && $commuserdata['notify_entries']) {
                $notify_arr[ $commuserdata['email'] ] = $commuserdata['name'];
            }
        }

        // send mail to those on the 'notify me' list..
        if (count($notify_arr)>0) {
            $userdata = $PIVOTX['users']->getUser($entry['user']);
            $contact_addr = $userdata['email'];
            $user = $userdata['nickname'];
            if (empty($user)) {
                $user = $entry['user'];
            }

            $defaultbody = sprintf(__('"%s" posted the following entry').":\n\n", $user );
            $defaultbody .= sprintf("%s\n\n%s\n", $title, unentify(strip_tags($entry['introduction'])));
            $defaultbody .= sprintf("\n\n-------------\n");

            $defaultbody .= sprintf("\n%s:\n%s%s\n", __('View the complete entry'), 
                $PIVOTX['paths']['host'], makeFileLink($entry, "", ""));

            $defaultbody .= sprintf("\n%s:\n%s%s\n", __('View your settings'),
                $PIVOTX['paths']['host'], makeVisitorPageLink());

            $defaultbody .= sprintf("\n%s: %%name%% (%%addr%%)\n", __('This email was sent to'));

            $defaultbody = decodeText($defaultbody, 'special');

            $contact_name = '=?UTF-8?B?'.base64_encode($user).'?=';
            $header = sprintf("From: \"%s\" <%s>\n", $contact_name, $contact_addr);

            $subject = sprintf(__('New entry "%s"'), $title);

            $notified = array();

            $sent = true;
            foreach($notify_arr as $addr => $name) {

                $addr = trim($addr);

                $body = $defaultbody;
                $body = str_replace("%name%", $name, $body);
                $body = str_replace("%addr%", $addr, $body);

                if(pivotxMail($addr, $subject, $body, $header)) {
                    debug("Sent Notify to $addr from '".$entry['user']."'");
                    $notified[] = sprintf("%s (%s)", $name, $addr);
                } else {
                    debug("Sending notifications failed");
                    $sent = false;
                    break;
                }

            }

            if ($sent) {
                return sprintf("%s: %s", __('Notifications were sent to') , implode(", ", $notified) );
            } else {
                return __('Failed to send notifications.');
            }

        }

    } else if ($type == 'visitor_registration') {
        $type = $data[0];
        $name = $data[1];

        // Only sending notification to superadmin
        foreach ($PIVOTX['users']->getUsers() as $user) {
            if ($user['userlevel'] == 4) {
                break;
            }
        }
        $contact_addr = $user['email'];

        if ($type == 'add') {
            $subject = sprintf(__('New visitor registration - %s'),$name);
        } else {
            $subject = sprintf(__('New visitor confirmed - %s'),$name);
        }
        $body = $subject;
        $body .= sprintf("\n\n%s:\n%s%s\n", __('View visitor information'),
            $PIVOTX['paths']['host'], makeAdminPageLink('visitors'));

        if (pivotxMail($contact_addr, $subject, $body)) {
            debug("Sent registered visitor notification for $name");
        } else {
            debug("Failed to send registered visitor notification for $name");
        }
        return;

    } else {
        debug("Unknown notify type '$type'");
    }
}


/**
 * convert relative URL's to absolute URL's. Used when we need an absolute path in RSS feeds.
 *
 * @param string $link
 * @return string
 */
function relativeToAbsoluteURLS($link) {

    $host = "http://".$_SERVER['HTTP_HOST'];

    $link = preg_replace("/a href=(['\"])(?!http)/mUi", "a href=\\1$host\\2", $link);
    $link = preg_replace("/img src=(['\"])(?!http)/mUi", "img src=\\1$host\\2", $link);

    return ($link);
}


/**
 * Calculates the time difference between the web and file server.
 *
 * This function is used by hardened bbclone and trackbacks (to get the
 * correct time when deleting keys that are older than a given time).
 *
 * @param boolean $debug
 * @return int
 */
function timeDiffWebFile($debug=false) {
    global $PIVOTX;
    $dummy = $PIVOTX['paths']['db_path']."dummy.txt";
    @touch($dummy);
    $offset = (time() - filectime($dummy));
    @unlink($dummy);
    if ($debug) {
        debug("The web and file server time diff: $offset");
    }
    return $offset;
}




/**
 * Determine if the current browser is a mobile device or not.
 *
 * adapted from: http://www.russellbeattie.com/blog/mobile-browser-detection-in-php
 *
 * @return boolean
 */
function isMobile() {
    global $PIVOTX;

    if (isset($PIVOTX['config']) && $PIVOTX['config']->get('is_mobile')) {
        return true;
    }

    $isMobile = false;

    $op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $ac = strtolower($_SERVER['HTTP_ACCEPT']);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
        || $op != ''
        || strpos($ua, 'sony') !== false 
        || strpos($ua, 'symbian') !== false 
        || strpos($ua, 'nokia') !== false 
        || strpos($ua, 'samsung') !== false 
        || strpos($ua, 'mobile') !== false
        || strpos($ua, 'windows ce') !== false
        || strpos($ua, 'epoc') !== false
        || strpos($ua, 'opera mini') !== false
        || strpos($ua, 'nexus one') !== false
        || strpos($ua, 'nitro') !== false
        || strpos($ua, 'j2me') !== false
        || strpos($ua, 'midp-') !== false
        || strpos($ua, 'cldc-') !== false
        || strpos($ua, 'netfront') !== false
        || strpos($ua, 'mot') !== false
        || strpos($ua, 'up.browser') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'audiovox') !== false
        || strpos($ua, 'blackberry') !== false
        || strpos($ua, 'ericsson,') !== false
        || strpos($ua, 'panasonic') !== false
        || strpos($ua, 'philips') !== false
        || strpos($ua, 'sanyo') !== false
        || strpos($ua, 'sharp') !== false
        || strpos($ua, 'sie-') !== false
        || strpos($ua, 'portalmmm') !== false
        || strpos($ua, 'blazer') !== false
        || strpos($ua, 'avantgo') !== false
        || strpos($ua, 'danger') !== false
        || strpos($ua, 'palm') !== false
        || strpos($ua, 'series60') !== false
        || strpos($ua, 'palmsource') !== false
        || strpos($ua, 'pocketpc') !== false
        || strpos($ua, 'smartphone') !== false
        || strpos($ua, 'rover') !== false
        || strpos($ua, 'ipaq') !== false
        || strpos($ua, 'au-mic,') !== false
        || strpos($ua, 'alcatel') !== false
        || strpos($ua, 'ericy') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'vodafone/') !== false
        || strpos($ua, 'wap1.') !== false
        || strpos($ua, 'wap2.') !== false;
    
    return $isMobile;

}


/**
 * Determine if the current browser is a tablet device or not.
 *
 * For now this is specific for iPads, but more devices can be added, once they
 * are on the market.
 *
 * @return boolean
 */
function isTablet() {

    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    $isTablet = strpos($ua, 'ipad') !== false;

    return $isTablet;
}

/**
 * Simple check to see if the current browser is Chrome or not
 *
 * @return boolean
 */
function isChrome() {

    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    $isChrome = strpos($ua, 'chrome') !== false;

    return $isChrome;
}

/**
 * Easy way to redirect to other admin pages
 *
 * @param string $page    page to redirect to (if false, we ignored it)
 * @param mixed $args     either url arguments (as string) or an array which build an url from
 * @return                does not return (period)
 */
function pivotxAdminRedirect($page, $args=false)
{
    $url  = $PIVOTX['paths']['host'];
    $url .= $PIVOTX['paths']['pivotx_url'];

    if ($page !== false) {
        $url .= '?page='.$page;
    }
    if ($args === false) {
        // do nothing
    } else if (is_scalar($args)) {
        if (strpos($url,'?') === false) {
            $url .= '?';
        }
        else {
            $url .= '&';
        }
        $url .= $args;
    } else if (is_array($args)) {
        if (strpos($url,'?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($args);
    }

    Header('Location: '.$url);
    exit();
}

/**
 * Check if a given string is base64 encoded.
 *
 * @param string $str
 */
function isBase64Encoded($str) {
    return (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $str));
}


/**
 * Go through the cache folder, delete all files.
 */
function wipeSmartyCache() {
    global $PIVOTX, $deletecounter;
    
    $deletecounter = 0;
    
    $cache_path = $PIVOTX['paths']['cache_path'];
        
    if (empty($cache_path)) {
        debug("Can't wipe cache: Paths not set");
        return "";
    }

    wipeSmartyCacheFolder($cache_path);

    debug("wipeSmartyCache: deleted " . intval($deletecounter) . " cache files in ". timeTaken() . " secs. ");

    return $deletecounter;

}

/**
 * Helper function for wipeSmartyCache().
 *
 * @see wipeSmartyCache();
 * @param string $path
 */
function wipeSmartyCacheFolder($path) {
    global $PIVOTX, $deletecounter;
    
    // Make sure we do not take too long..
    if (timeTaken('int')>29) { return; }
    
    $d = dir($path);

    while (false !== ($entry = $d->read())) {
        
        if ($entry=="." || $entry==".." || $entry=='.svn' || $entry=='.git') {
            continue;
        }

        if (is_dir($path.$entry)) {
            // Recursively go through folders..
            wipeSmartyCacheFolder($path.$entry."/");
        } else {
            // Or else remove the file..
            unlink($path.$entry);
            $deletecounter++;
        }
    }
    
    $d->close();
    
}




/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see checkVersion()
 */
function check_version($currentversion, $requiredversion) {
    return checkVersion($currentversion, $requiredversion);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see makeValuepairs()
 */
function make_valuepairs($array, $key, $value) {
    return makeValuepairs($array, $key, $value);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see makeJsVars()
 */
function make_jsvars($array) {
    return makeJsVars($array);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see getDefault()
 */
function get_default($a, $b, $strict=false) {
    return getDefault($a, $b, $strict);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see cleanParams()
 */
function clean_params($params) {
    return cleanParams($params);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see stripSlashesDeep()
 */
function stripslashes_deep(&$value) {
    return stripSlashesDeep($value);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see implodeDeep()
 */
function implode_deep($glue, $value) {
    return implodeDeep($glue, $value);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see fileArraySort()
 */
function filearray_sort($a, $b) {
    return fileArraySort($a, $b);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see makeThumbname()
 */
function make_thumbname($filename) {
    return makeThumbname($filename);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see formatEntry()
 */
function format_entry($entry, $format) {
    return formatEntry($entry, $format);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see formatDate()
 */
function format_date($date="", $format="", $title="") {
    return formatDate($date, $format, $title);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see formatDateRange()
 */
function format_date_range($start_date, $end_date, $format) {
    return formatDateRange($start_date, $end_date, $format);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see fixDate()
 */
function fix_date($date, $time) {
    return fixDate($date, $time);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see getAttrValue()
 */
function get_attr_value($att_name, $attributes) {
    return getAttrValue($att_name, $attributes);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see liberalUnserialize()
 */
function liberal_unserialize($filename) {
    return liberalUnserialize($filename);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see loadSerialize()
 */
function load_serialize($filename, $silent=false) {
    return loadSerialize($filename, $silent) ;
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see saveSerialize()
 */
function save_serialize($filename, &$data) {
    return saveSerialize($filename, $data);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see writeFile()
 */
function write_file($filename, $output, $mode='w') {
    return writeFile($filename, $output, $mode);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see chmodFile()
 */
function chmod_file($filename) {
    return chmodFile($filename);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see safeString()
 */
function safe_string($str, $strict=false, $extrachars="") {
    return safeString($str, $strict, $extrachars);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see stripTrailingSpace()
 */
function strip_trailing_space($text) {
    return stripTrailingSpace($text);
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see stripTagsAttributes()
 */
function strip_tags_attributes($source, $allowedtags = "", $disabledattributes = "" )  {
    return stripTagsAttributes($source, $allowedtags, $disabledattributes );
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see getCurrentDate()
 */
function get_current_date() {
    return getCurrentDate();
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see getEditCommentLink()
 */
function get_editcommentlink($uid=0, $number) {
    return getEditCommentLink($uid, $number); 
}

/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see commentFormat()
 */
function comment_format($text, $striplinebreaks=false) {
    return commentFormat($text, $striplinebreaks);
}


/**
 * Deprecated function. Kept for backwards compatibility.
 *
 * @see encodeMailLink()
 */
function encodemail_link($mail, $text, $title="", $encrypt=false) {
    return encodeMailLink($mail, $text, $title, $encrypt);
}

/**
 * Convert a PHP-array to JSON formatted string.
 *
 * @see http://www.bin-co.com/php/scripts/array2json/
 */
function arrayToJson($arr) {

    if(function_exists('json_encode')) {
        return json_encode($arr); // Recent versions of PHP already have this functionality.
    }

    $parts = array();
    $is_list = false;

    if (count($arr)>0){
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr)-1;
        if(($keys[0] === 0) && ($keys[$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position
                if($i !== $keys[$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }

        foreach($arr as $key=>$value) {
            $str = ( !$is_list ? '"' . $key . '":' : '' );

            if (is_array($value)) { //Custom handling for arrays
                $parts[] = $str . arrayToJson($value);
            } else {
                //Custom handling for multiple data types
                if (is_numeric($value) && !is_string($value)){
                    $str .= $value; //Numbers
                } elseif(is_bool($value)) {
                    $str .= ( $value ? 'true' : 'false' );
                } elseif( $value === null ) {
                    $str .= 'null';
                } else {
                    $str .= '"' . addslashes($value) . '"'; //All other things
                }
                $parts[] = $str;
            }
        }
    }
    $json = implode(',', $parts);

    if($is_list) {
        return '[' . $json . ']'; // Return numerical JSON
    } else {
        return '{' . $json . '}'; // Return associative JSON
    }
}


?>
