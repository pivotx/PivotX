<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty strip_only_tags modifier plugin
 *
 * Type:    modifier

 * Name:    strip_only_tags

 * Purpose: strip only the specified html tags from text
 *
 * @author  PivotX Team
 *
 * @version 1.0
 *
 * @param   string $string
 * @param   mixed $disallowedtags
 * @return  string
 */
function smarty_modifier_strip_only_tags($string, $disallowedtags) {
 
	return stripOnlyTags($string, $disallowedtags);
 
}

/* vim: set expandtab: */

?>
