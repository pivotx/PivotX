<?php


/**
 * Smarty trimhttp modifier plugin
 *
 */
function smarty_modifier_trimhttp($string) {

    $string = str_replace("http://", "", $string);
    $string = str_replace("https://", "", $string);
        
    return $string;
    
}

/* vim: set expandtab: */

?>
