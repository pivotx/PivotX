<?php


/**
 * Emoticons is the extension that replaces pivot's old builtin 
 * emoticons. Using this extension it'll be much easier to replace
 * the emoticons, and they won't get overwritten when you update 
 * your pivot to a newer version.
 *
 * @author Pivot Dev-team 
 * @version 0.1 
 *
 */



// in the following lines, you can change the links to the files
// that are used to insert the emoticons.


// default 'trillian' style emoticons..
$emoticon_images = "trillian/";
$emoticon_window = "trillian/emoticons.html";
$emoticon_window_width = 264;
$emoticon_window_height = 234;
$emoticon_triggers = "trillian/triggers.php";

// Goddelijke gladiolen emoticons..
//$emoticon_images = "gg/";
//$emoticon_window = "gg/emoticons.html";
//$emoticon_window_width = 521;
//$emoticon_window_height = 331;
//$emoticon_triggers = "gg/triggers.php";



// ----------------- Nothing to tweak below this line -----------------------

// do not change this, unless you know what you're doing..
$emoticon_basepath = dirname(__FILE__)."/";

include_once($emoticon_basepath.$emoticon_triggers);

?>