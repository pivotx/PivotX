<?php


/**
 * Smarty explode plugin
 *
 * Type:     modifier
 * Name:     explode
 * Purpose:  explode a string by delimiter

 * @param string
 * @param delimiter
 * @return string
 */
function smarty_modifier_explode($string, $delimiter)
{
    return explode($delimiter, $string);
}

?>
