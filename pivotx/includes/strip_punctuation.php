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

/**
 * Defining a simple wrapper for the strip punctuation function in case PCRE 
 * wasn't compiled with UTF-8 support.
 */

if (@preg_match('/\p{L}/u', 'a') == 1) {
    require_once dirname(__FILE__) . '/strip_punctuation_preg_utf8.php';
} else {
    /**
     * Strip US-ASCII punctuation characters from UTF-8 text.
     *
     * @param string $text The UTF-8 text to strip
     * @return string The stripped UTF-8 text.
     */
    function strip_punctuation( $text ) {

	return preg_replace(
            array(
                // Remove (most) US-ASCII punctuation characters
                    '/[\'"!?.,:;\[\]{}()<=>~]/',
		// Remove consecutive spaces
			'/ +/',
            ),
            ' ',
            $text 
        );
    }
}
