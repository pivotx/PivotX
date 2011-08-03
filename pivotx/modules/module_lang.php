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

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }

// Load PHP Gettext and mark that we are infact using it.
require_once $pivotx_path.'includes/gettext.php';
define('PHP_GETTEXT',1);

/** 
 * Translates a string for a given domain.
 *
 * @param string $text
 * @param string $domain
 * @return string
 */
function translate($text, $domain = 'default') {
    global $l10n;

    if ($domain == 'default') {
        $domain = $l10n['currlang'];
    }

    if (isset($l10n[$domain])) {
        return $l10n[$domain]->translate($text);
    } else {
        return $text;
    }
}

/** 
 * Returns a translated string.
 *
 * @param string $text
 * @param string $domain
 * @return string
 */
function __($text, $domain = 'default') {
    return translate($text, $domain);
}

/**
 * Echos a translated string.
 *
 * @param string $text
 * @param string $domain
 * @return void
 */
function _e($text, $domain = 'default') {
    echo translate($text, $domain);
}

/** 
 * Returns a translated string - alias of '__' to be consistent
 * with the naming of '__ngettext'.
 *
 * @param string $text
 * @param string $domain
 * @return string
 */
function __gettext($text, $domain = 'default') {
    return translate($text, $domain);
}

/**
 * Returns the plural form or a given domain.
 *
 * @param string $single
 * @param string $plural
 * @param string $number
 * @param string $domain
 * @return string
 */
function __ngettext($single, $plural, $number, $domain = 'default') {
    global $l10n;

    if (isset($l10n[$domain])) {
        return $l10n[$domain]->ngettext($single, $plural, $number);
    } else {
        if ($number != 1)
        return $plural;
        else
        return $single;
    }
}

/**
 * Returns the words to filtered for the current weblog/language as an array.
 */
function getFilteredWords( ) {
    global $l10n, $PIVOTX;

    if( file_exists( $PIVOTX['paths']['db_path'].'search/filtered_words.txt' )) {
        $filtered_file = file( $PIVOTX['paths']['db_path'].'search/filtered_words.txt' );
        foreach( $filtered_file as $val ) {
                        if (substr($val,0,2)!== "//") {
                $filtered_words[] = trim( $val );
            }
        }
    } else {
        $filtered_words = array();  
    }

    $theLang = $l10n['currlang'];
    
    if((''!=$theLang) && file_exists($PIVOTX['paths']['db_path'].'search/filtered_words_'.$theLang.'.txt')) {

        $filtered_file = file($PIVOTX['paths']['db_path'].'search/filtered_words_'.$theLang.'.txt');
        foreach( $filtered_file as $val ) {
            $filtered_words[] = trim( $val );
        }
    }
    return $filtered_words;
}

function lang($a='', $b='', $c='') {
    return "$a - $b - ($c)";
}

/**
 * The class that loads a language. 
 *
 * FIXME The class currently works with languages, not domains - as in Pivot 1.
 */
class Languages {
    
    var $default = 'en';

    function Languages($language='') {
        global $l10n, $PIVOTX;

        if ($language == '') {
            if (!isInstalled()) {
                $language = $this->default;
            } elseif (defined('PIVOTX_INADMIN')) {
                $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
                $language = $currentuser['language'];
            } elseif (defined('PIVOTX_INWEBLOG')) {
                $language = $PIVOTX['weblogs']->get('','language');
            }
            // Fallback - system default
            if ($language == '') {
                $language = $PIVOTX['config']->get('language');
            }
            // Final fallback - English.
            if ($language == '') {
                $language = $this->default;
            }
        }

        // FIXME: Add check if language exists - fallback to English.

        $l10n['currlang'] = $language;
        $this->loadLanguage($language);

        $this->code = $language;
        $this->name = $this->codes[$language];
    }

    /**
     * Loads the translations for a given language
     */
    function loadLanguage($language) {
        global $l10n, $PIVOTX;

        // This loads the "codes" array.
        $file = $PIVOTX['paths']['pivotx_path'].'langs/codes/'.$language.'.php';
        if (file_exists($file)) {
            include $file;
        } else {
            include $PIVOTX['paths']['pivotx_path'].'langs/codes/en.php';
        }

        // Load the mo (compiled po) file if not already done.
        if (isset($l10n[$language])) {
            return;
        }

        $mofile = $PIVOTX['paths']['pivotx_path'].'langs/'.$language.'.mo';
        if (is_readable($mofile)) {
            require_once $PIVOTX['paths']['pivotx_path'].'includes/streams.php';
            $input = new CachedFileReader($mofile);
        } else {
            return;
        }

        $l10n[$language] = new gettext_reader($input);
    }
    
    /**
     * Switches to the translations for the given language
     */
    function switchLanguage($language) {
        global $l10n;

        $l10n['currlang'] = $language;
        $this->code = $language;
        $this->name = $this->codes[$language];
        $this->loadLanguage($language);

    }

    /**
     * Gets a sorted list of the available language files.
     *
     * @return array of the available files and their names
     *
     */
    function getLangs() {
        global $PIVOTX;

        $lang = array();
        $dh = opendir($PIVOTX['paths']['pivotx_path'].'langs/');
        while($fname = readdir($dh)) {
            if(preg_match('!([a-z]{2}).mo!', $fname, $null)){
                $lang[$null[1]] = $this->codes[$null[1]];
            }
        }
        closedir($dh);
        asort($lang);

        return $lang;
    }

    function getCode() {
        return $this->code;
    }

    function getName() {
        return $this->name;
    }
}

/**
 * Gives access to translations for date and time.
 *
 * This code is based on WP_Locale found in Wordpress.
 */
class px_Locale {
    var $weekday;
    var $weekday_initial;
    var $weekday_abbrev;

    var $month;
    var $month_abbrev;

    var $meridiem;

    function init() {
        // The Weekdays
        $this->weekday[0] = __('Sunday');
        $this->weekday[1] = __('Monday');
        $this->weekday[2] = __('Tuesday');
        $this->weekday[3] = __('Wednesday');
        $this->weekday[4] = __('Thursday');
        $this->weekday[5] = __('Friday');
        $this->weekday[6] = __('Saturday');

        // The first letter of each day.  The _%day%_initial suffix is a hack to make
        // sure the day initials are unique.
        $this->weekday_initial[__('Sunday')]    = __('S_Sunday_initial');
        $this->weekday_initial[__('Monday')]    = __('M_Monday_initial');
        $this->weekday_initial[__('Tuesday')]   = __('T_Tuesday_initial');
        $this->weekday_initial[__('Wednesday')] = __('W_Wednesday_initial');
        $this->weekday_initial[__('Thursday')]  = __('T_Thursday_initial');
        $this->weekday_initial[__('Friday')]    = __('F_Friday_initial');
        $this->weekday_initial[__('Saturday')]  = __('S_Saturday_initial');

        foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
            $this->weekday_initial[$weekday_] = preg_replace('/_.+_initial$/', '', $weekday_initial_);
        }

        // Abbreviations for each day.
        $this->weekday_abbrev[__('Sunday')]    = __('Sun');
        $this->weekday_abbrev[__('Monday')]    = __('Mon');
        $this->weekday_abbrev[__('Tuesday')]   = __('Tue');
        $this->weekday_abbrev[__('Wednesday')] = __('Wed');
        $this->weekday_abbrev[__('Thursday')]  = __('Thu');
        $this->weekday_abbrev[__('Friday')]    = __('Fri');
        $this->weekday_abbrev[__('Saturday')]  = __('Sat');

        // The Months
        $this->month['01'] = __('January');
        $this->month['02'] = __('February');
        $this->month['03'] = __('March');
        $this->month['04'] = __('April');
        $this->month['05'] = __('May');
        $this->month['06'] = __('June');
        $this->month['07'] = __('July');
        $this->month['08'] = __('August');
        $this->month['09'] = __('September');
        $this->month['10'] = __('October');
        $this->month['11'] = __('November');
        $this->month['12'] = __('December');

        // Abbreviations for each month. Uses the same hack as above to get around the
        // 'May' duplication.
        $this->month_abbrev[__('January')] = __('Jan_January_abbreviation');
        $this->month_abbrev[__('February')] = __('Feb_February_abbreviation');
        $this->month_abbrev[__('March')] = __('Mar_March_abbreviation');
        $this->month_abbrev[__('April')] = __('Apr_April_abbreviation');
        $this->month_abbrev[__('May')] = __('May_May_abbreviation');
        $this->month_abbrev[__('June')] = __('Jun_June_abbreviation');
        $this->month_abbrev[__('July')] = __('Jul_July_abbreviation');
        $this->month_abbrev[__('August')] = __('Aug_August_abbreviation');
        $this->month_abbrev[__('September')] = __('Sep_September_abbreviation');
        $this->month_abbrev[__('October')] = __('Oct_October_abbreviation');
        $this->month_abbrev[__('November')] = __('Nov_November_abbreviation');
        $this->month_abbrev[__('December')] = __('Dec_December_abbreviation');

        foreach ($this->month_abbrev as $month_ => $month_abbrev_) {
            $this->month_abbrev[$month_] = preg_replace('/_.+_abbreviation$/', '', $month_abbrev_);
        }

        // The Meridiems
        $this->meridiem['am'] = __('am');
        $this->meridiem['pm'] = __('pm');
        $this->meridiem['AM'] = __('AM');
        $this->meridiem['PM'] = __('PM');

        // The twentyone first numbers
        $this->number[0] = __('zero');
        $this->number[1] = __('one');
        $this->number[2] = __('two');
        $this->number[3] = __('three');
        $this->number[4] = __('four');
        $this->number[5] = __('five');
        $this->number[6] = __('six');
        $this->number[7] = __('seven');
        $this->number[8] = __('eight');
        $this->number[9] = __('nine');
        $this->number[10] = __('ten');
        $this->number[11] = __('eleven');
        $this->number[12] = __('twelve');
        $this->number[13] = __('thirteen');
        $this->number[14] = __('fourteen');
        $this->number[15] = __('fifteen');
        $this->number[16] = __('sixteen');
        $this->number[17] = __('seventeen');
        $this->number[18] = __('eigthteen');
        $this->number[19] = __('nineteen');
        $this->number[20] = __('twenty');

        // The twentyone first ordinals
        $this->ordinal[0] = __('zeroth');
        $this->ordinal[1] = __('first');
        $this->ordinal[2] = __('second');
        $this->ordinal[3] = __('third');
        $this->ordinal[4] = __('forth');
        $this->ordinal[5] = __('fifth');
        $this->ordinal[6] = __('sixth');
        $this->ordinal[7] = __('seventh');
        $this->ordinal[8] = __('eighth');
        $this->ordinal[9] = __('ninth');
        $this->ordinal[10] = __('tenth');
        $this->ordinal[11] = __('eleventh');
        $this->ordinal[12] = __('twelfth');
        $this->ordinal[13] = __('thirteenth');
        $this->ordinal[14] = __('fourteenth');
        $this->ordinal[15] = __('fifteenth');
        $this->ordinal[16] = __('sixteenth');
        $this->ordinal[17] = __('seventeenth');
        $this->ordinal[18] = __('eighteenth');
        $this->ordinal[19] = __('nineteenth');
        $this->ordinal[20] = __('twentieth');

    }

    function getWeekday($weekday_number) {
        return $this->weekday[$weekday_number];
    }

    function getWeekdayInitial($weekday_name) {
        return $this->weekday_initial[$weekday_name];
    }

    function getWeekdayAbbrev($weekday_name) {
        return $this->weekday_abbrev[$weekday_name];
    }

    function getMonth($month_number) {
        return $this->month[zeroise($month_number, 2)];
    }

    function getMonthInitial($month_name) {
        return $this->month_initial[$month_name];
    }

    function getMonthAbbrev($month_name) {
        return $this->month_abbrev[$month_name];
    }

    function getMeridiem($meridiem) {
        return $this->meridiem[$meridiem];
    }

    function getNumber($number) {
        if (isset($this->number[$number])) {
            return $this->number[$number];
        } else {
            return $number;
        }
    }

    function getOrdinal($number) {
        if (isset($this->ordinal[$number])) {
            return $this->ordinal[$number];
        } else {
            return $number;
        }
    }

    function px_Locale() {
        // Currently only running init - more can be added.
        $this->init();
    }
}

?>
