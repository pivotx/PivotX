<?php

/**
 * Contains the functions we use to support multi-site PivotX installs.
 *
 * @package pivotx
 * @subpackage modules
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


/**
 * The class that contains the multi-sites functions.
 *
 */
class MultiSite {

    var $active;
    var $path;

    /**
     * Initializes the class.
     */
    function MultiSite() {
        global $PIVOTX, $pivotx_path;

        $sites_path = $pivotx_path . 'sites/';

        if (file_exists($sites_path)) {
            /* Based on similar code in Drupal (see conf_path function in includes/bootstrap.inc). */
            if (function_exists('getPivotxURL')) {
                $uri = explode('/',getPivotxURL());
                $dummy = array_pop($uri);
            } else {
                // The function isn't defined when PivotX isn't loaded (i.e, viewing the debug log).
                if (!empty($_SERVER['PATH_INFO'])) {
                    $uri = $_SERVER['PATH_INFO'];
                } else if (!empty($_SERVER['PHP_SELF'])) {
                    $uri = $_SERVER['PHP_SELF'];
                } else {
                    $uri = $_SERVER['SCRIPT_NAME'];
                }
                $uri = dirname($uri);
                $uri = explode('/', $uri);
            }
            $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
            for ($i = count($uri) - 1; $i > 0; $i--) {
                for ($j = count($server); $j > 0; $j--) {
                    $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
                    if (file_exists($sites_path . $dir)) {
                        $this->path = 'sites/' . $dir . '/';
                        $this->active = true;
                        return;
                    }
                }
            }
        }

        $this->active = false;

        return;
    }

    /**
     * Returns whether Multi-Site is active.
     * 
     * @return boolean
     */
    function isActive() {
        return $this->active;
    }

    /**
     * Returns the relative directory path for this site.
     *
     * @return string
     */
    function getPath() {
        if ($this->active) {
            return $this->path;
        } else {
            return '';
        }
    }

}

?>
