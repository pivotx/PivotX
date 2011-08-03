<?php
/**
 * Adds protection to files.
 *
 * The protection are for files that, when called directly, execute code,
 * while they shouldn't. It is not intended for files that either do nothing
 * when called directly (just function- or class-definitions), or are meant to
 * be called directly, and therefore have proper checks on the incoming
 * parameters.
 *
 * Usage: In the file that needs protection add:
 * <code>
 * $currentfile = basename(__FILE__);
 * require dirname(__FILE__)."/lamer_protection.php";
 * </code>
 *
 * If the file isn't in the same directory as "lamer_protection.php", adjust
 * the path in the require call.
 * @package pivotx
 */

/**
 * Protecting against direct loading and changing of central PivotX
 * variable through $_GET/$_POST/$_SERVER/$_COOKIE.
 */
if ( (strpos($pivotx_path,"tp://")>0) || (strpos($pivotx_path,"tps://")>0) ) { 
    die('no off-site paths');
}
$scriptname = basename((isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : $_SERVER['PHP_SELF']);
if (!empty($currentfile) && ($scriptname==$currentfile)) { 
    die('no direct access'); 
}

$checkvars = array_merge($_GET , $_POST, $_SERVER, $_COOKIE);
if ( isset($checkvars['PIVOTX']) || isset($checkvars['pivotx_url']) || isset($checkvars['pivotx_path']) ) {
    // Note: even though 'pivotx_url', 'pivotx_path' aren't used anymore, we still
    // check for them. Older extensions might use them.
    die('no changing of internal variables');
}

?>
