<?php

/**
 * PivotX implementation for 
 * 1) PHP functions missing from older PHP versions
 * 2) PHP functions that can be improved (to for example
 *    handle multibyte strings properly) because a site
 *    has additional PHP extensions enabled.
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

// ========== Functions that are missing in older versions of PHP ============

// None needed/used in PivotX so far.

// ========== Functions that can be improved =================================

if (extension_loaded('mbstring')) {
    define('PX_MBSTRING_LOADED',1);
}

if (@preg_match('/\p{L}/u', 'a') == 1) {
    define('PX_PREG_WITH_UTF8',1);
}

if (defined('PX_MBSTRING_LOADED')) {
    function px_strlen($str) {
        return mb_strlen($str, 'UTF-8');
    }
    function px_strtolower($str) {
        return mb_strtolower($str, 'UTF-8');
    }
    function px_substr($str, $start, $length='') {
        if ($length!='') {
            return mb_substr($str, $start, $length, 'UTF-8');
        } else {
            return mb_substr($str, $start, mb_strlen($str), 'UTF-8');
        }
    }
} else {
    function px_strlen($str) {
        return strlen($str);
    }
    function px_strtolower($str) {
        return strtolower($str);
    }
    function px_substr($str, $start, $length='') {
        if ($length!='') {
            return substr($str, $start, $length);
        } else {
            return substr($str, $start);
        }
    }
}

if (defined('PX_PREG_WITH_UTF8')) {
    // Note that the fifth argument of preg_replace is ignored (to avoid warnings) since 
    // you can't set default values for paramters passed by reference in PHP4.
    function px_preg_replace($pattern, $replacement, $subject, $limit=-1) {
        return preg_replace($pattern.'u', $replacement, $subject, $limit);
    }
} else {
    // Note that the fifth argument of preg_replace is ignored (to avoid warnings) since 
    // you can't set default values for paramters passed by reference in PHP4.
    function px_preg_replace($pattern, $replacement, $subject, $limit=-1) {
        return preg_replace($pattern, $replacement, $subject, $limit);
    }
}

   
?>
