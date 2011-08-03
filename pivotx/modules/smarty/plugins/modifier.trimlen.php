<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty timlen modifier plugin
 *
 * Type:     modifier<br>
 * Name:     trimlen<br>
 * Purpose:  Trim a string to a given length, appending it with an indicator.
 * @param string
 * @param integer
 * @param string
 * @return string
 */
function smarty_modifier_trimlen($string, $length = 80, $etc = '&hellip;') {

    if ($length == 0)
        return '';

    if (px_strlen($string) > $length) {

    	$string = px_substr($string, 0, ($length-1)).$etc;

    }

    return $string;
}


?>
