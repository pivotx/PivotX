<?php
/**
 * Contains the functions needed to use PivotX in different languages (locales)
 * and in UTF-8 encoding.
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

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }
/** 
 * Checks if a string is UTF-8 encoded.
 *
 * @link From http://w3.org/International/questions/qa-forms-utf-8.html
 */
function i18n_is_utf8($string) {
    return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $string);
}

/** 
 * Checks if a string contains only 7bit ASCII bytes.
 */
function i18n_is_ascii($str) {
    return ! preg_match('/[^\x00-\x7F]/S', $str);
}

/**
 * Makes a string's first character uppercase.
 */
function i18n_ucfirst($string) {

   $string[0] = strtr($string,
   "abcdefghijklmnopqrstuvwxyz".
   "\x9C\x9A\xE0\xE1\xE2\xE3".
   "\xE4\xE5\xE6\xE7\xE8\xE9".
   "\xEA\xEB\xEC\xED\xEE\xEF".
   "\xF0\xF1\xF2\xF3\xF4\xF5".
   "\xF6\xF8\xF9\xFA\xFB\xFC".
   "\xFD\xFE\xFF",
   "ABCDEFGHIJKLMNOPQRSTUVWXYZ".
   "\x8C\x8A\xC0\xC1\xC2\xC3\xC4".
   "\xC5\xC6\xC7\xC8\xC9\xCA\xCB".
   "\xCC\xCD\xCE\xCF\xD0\xD1\xD2".
   "\xD3\xD4\xD5\xD6\xD8\xD9\xDA".
   "\xDB\xDC\xDD\xDE\x9F");

   return $string;  
    
}


/**
 * Encodes a string to UTF-8 from the internal encoding in the input string. 
 *
 * @param string $string
 * @param boolean $force
 * @return string
 */
function i18n_str_to_utf8($string,$force=false) {

    if (i18n_is_ascii($string) || i18n_is_utf8($string)) {
        return $string;
    }

    if (!function_exists('mb_detect_encoding')) {
        debug('Unable to detect string encoding since multibyte string functions aren\'t available');
        debug('Assuming string to be Latin-1 (ISO-8859-1) or Win-Latin-1 (CP1252).');
        return i18n_western_to_utf8($string);
    }

    $encoding = mb_detect_encoding($string);

    switch (strtolower($encoding)) {

        case 'iso-8859-1':
            $output = utf8_encode($string);
            break;
        case 'euc-jp':
            $output = i18n_eucjp_to_utf8($string);
            break;
        case '':                        
        case 'utf-8':
            $output = $string;
            break;
        default:
            $output = utf8_encode($string);
            break;
    }

    return $output;
}

/**
 * PHP version of Perl's Encoding::FixLatin by Grant McLean. It will correctly 
 * transform Latin-1 (ISO-8859-1) and Win-Latin-1 (CP1252) characters to 
 * UTF-8, and preserve ASCII and well-formed UTF-8 multi-byte characters. It 
 * will always produce valid UTF-8, but might inroduce the odd 'typo'.
 *
 * Based on code found on http://www.php.net/manual/en/function.utf8-encode.php
 * Perl documentation at http://search.cpan.org/dist/Encoding-FixLatin/lib/Encoding/FixLatin.pm
 */
function i18n_western_to_utf8($instr){
    static $byte_map, $nibble_good_chars;
    static $byte_map_initialized;

    if (!$byte_map_initialized) {
        $byte_map_initialized = true;

        $ascii_char='[\x00-\x7F]';
        $cont_byte='[\x80-\xBF]';
        $utf8_2='[\xC0-\xDF]'.$cont_byte;
        $utf8_3='[\xE0-\xEF]'.$cont_byte.'{2}';
        $utf8_4='[\xF0-\xF7]'.$cont_byte.'{3}';
        $utf8_5='[\xF8-\xFB]'.$cont_byte.'{4}';
        $nibble_good_chars = "@^($ascii_char+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$@s";

        $byte_map=array();
        for($x=128;$x<256;++$x){
            $byte_map[chr($x)]=utf8_encode(chr($x));
        }
        $cp1252_map=array(
            "\x80" => "\xE2\x82\xAC",  // EURO SIGN
            "\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
            "\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
            "\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
            "\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
            "\x86" => "\xE2\x80\xA0",  // DAGGER
            "\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
            "\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
            "\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
            "\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
            "\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
            "\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
            "\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
            "\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
            "\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
            "\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
            "\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
            "\x95" => "\xE2\x80\xA2",  // BULLET
            "\x96" => "\xE2\x80\x93",  // EN DASH
            "\x97" => "\xE2\x80\x94",  // EM DASH
            "\x98" => "\xCB\x9C",      // SMALL TILDE
            "\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
            "\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
            "\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
            "\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
            "\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
            "\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
        );
        foreach($cp1252_map as $k=>$v){
            $byte_map[$k]=$v;
        }
    }

    $outstr='';
    $char='';
    $rest='';

    while((strlen($instr))>0){
        if (1==preg_match($nibble_good_chars,$instr,$match)){
            $char=$match[1];
            $rest=$match[2];
            $outstr.=$char;
        } elseif (1==preg_match('@^(.)(.*)$@s',$instr,$match)){
            $char=$match[1];
            $rest=$match[2];
            $outstr.=$byte_map[$char];
        }
        $instr=$rest;
    }
    return $outstr;
}

/**
 * Encodes every string in the array to UTF-8 from the 
 * internal encoding in the items. 
 *
 * @param mixed $item
 * @param mixed $key
 * @param boolean $force
 * @return void
 */
function i18n_array_to_utf8(&$item, &$key, $force=false) {
    if (is_array($item)) {
        array_walk($item, 'i18n_array_to_utf8', $force);
    } else {
        $item = i18n_str_to_utf8($item, $force);
    }
}


/**
 * Decodes a string to UTF-8 from EUC-JP 
 *
 * @param string $string
 * @return string
 */
function i18n_eucjp_to_utf8($string) {
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($string, "UTF-8");
    }
    return $string;
}


/**
 * Mimics the ord() function for UTF-8 text.
 *
 * A character in UTF-text may consist of several bytes. This function
 * calculates the ASCII value without using the multibyte (mb) functions
 * that was introduced in PHP 4.3.0.
 * {@link http://php.net/manual/en/function.ord.php#46267 More info}
 *
 * @param string $str UTF-8 string 
 * @param int $i The current position in the string 
 * @return array The ASCII value of the current character of string and
 *   the number of bytes used.
 */
function i18n_ord($str,$i) {
    $ud = 0;
    $n = 1;
    if (ord($str{$i})>=0 && ord($str{$i})<=127) {
    $ud = ord($str{$i});
    } elseif (ord($str{$i})>=192 && ord($str{$i})<=223) {
    $ud = (ord($str{$i})-192)*64 + (ord($str{$i+1})-128);
    $n = 2;
    } elseif (ord($str{$i})>=224 && ord($str{$i})<=239) {
    $ud = (ord($str{$i})-224)*4096 + (ord($str{$i+1})-128)*64 + (ord($str{$i+2})-128);
    $n = 3;
    } elseif (ord($str{$i})>=240 && ord($str{$i})<=247) {
    $ud = (ord($str{$i})-240)*262144 + (ord($str{$i+1})-128)*4096 + (ord($str{$i+2})-128)*64
        + (ord($str{$i+3})-128);
    $n = 3;
    } elseif (ord($str{$i})>=248 && ord($str{$i})<=251) {
    $ud = (ord($str{$i})-248)*16777216 + (ord($str{$i+1})-128)*262144 +
        (ord($str{$i+2})-128)*4096 + (ord($str{$i+3})-128)*64 + (ord($str{$i+4})-128);
    $n = 4;
    } elseif (ord($str{$i})>=252 && ord($str{$i})<=253) {
    $ud = (ord($str{$i})-252)*1073741824 + (ord($str{$i+1})-128)*16777216 +
        (ord($str{$i+2})-128)*262144 + (ord($str{$i+3})-128)*4096 + (ord($str{$i+4})-128)*64 +
        (ord($str{$i+5})-128);
    $n = 5;
    } elseif (ord($str{$i})>=254 && ord($str{$i})<=255) { //error
    $ud = false;
    }
    return array($ud,$n);
}
?>
