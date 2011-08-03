<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty hyphenize modifier plugin
 *
 * Type:     modifier<br>
 * Name:     hyphenize<br>
 * Purpose:  Add soft hyphens to a string, so Internet Explorer will be able to fill out lines better
 * @author   Bob for PivotX <bob@pivotx.net>
 * @param string
 * @return string
 */
function smarty_modifier_hyphenize($string) {
    
    $string = px_preg_replace("/(\w)/", "\\1&#173;", $string);
    return $string;

}

?>
