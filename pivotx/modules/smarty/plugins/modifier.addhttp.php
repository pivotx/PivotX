<?php


/**
 * Smarty addhttp modifier plugin
 *
 */
function smarty_modifier_addhttp($string) {

    if (strpos($string, "http")!==0) {
        $string = "http://".$string;
    }
        
    return $string;
    
}

/* vim: set expandtab: */

?>
