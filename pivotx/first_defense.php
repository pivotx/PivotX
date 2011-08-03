<?php
/**
 * First line of defense script.
 *
 * This first version helps battle referer spam,
 * comment-spam and trackback-spam.
 *
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

/**
 * Block referer spam. Returns true if checks were succesfull,
 * false if not, dies if spam is detected.
 *
 * @return boolean
 *
 */
function block_refererspam() {
    global $blockArray;

    // No need to check anything if there isn't a referer
    if (!isset($_SERVER["HTTP_REFERER"])) {
        return true;
    }

    // load blockarray, if needed.
    if (!isset($blockArray)) {

        $blockArray = array();

        if (file_exists(dirname(__FILE__)."/db/ignored_domains.txt.php"))  {
            $blockArray = array_merge($blockArray, file(dirname(__FILE__)."/db/ignored_domains.txt.php"));
        }

        if (file_exists(dirname(__FILE__)."/db/ignored_global.txt.php"))  {
            $blockArray = array_merge($blockArray, file(dirname(__FILE__)."/db/ignored_global.txt.php"));
        }

    }

    if (isset($blockArray)) {
        // Prevent tampering with the URL.
        $refererparts = parse_url(strtolower($_SERVER["HTTP_REFERER"]));
        if (!isset($refererparts['path'])) {
            $refererparts['path'] = "/";
        }
        $referer = $refererparts['host'].$refererparts['path'];

        if ($_SERVER['HTTP_HOST']==$refererparts['host']) {
            // if the current host is the same as the refering one, we can skip the checks.
            return true;
        } else {
            // else we check it against the blocked phrases
            foreach($blockArray as $blockphrase)  {
                $blockphrase = trim(str_replace("*", "", $blockphrase));
                if(strpos($referer, $blockphrase) !== false)  {
                    echo "Spam is not appreciated.";
                    $text = "Referer matched blocked phrase '".trim($blockphrase)."'";
                    $text .= ' * Referer: ' .  $referer;
                    include_once(dirname(__FILE__)."/modules/module_spamkiller.php");
                    logspammer( $text, "bpreferer");
                    die();
                }
            }
            return true;
        }
    } else {
        return false;
    }
}


/**
 * Block 'posted' spam: In either comments or trackbacks. Returns true
 * if checks were succesfull, false if not, dies if spam is detected.
 *
 * @return boolean
 */
function block_postedspam() {
    global $blockArray;

    // load blockarray, if needed.
    if (!isset($blockArray)) {

        $blockArray = array();

        if (file_exists(dirname(__FILE__)."/db/ignored_domains.txt.php"))  {
            $blockArray = array_merge($blockArray, file(dirname(__FILE__)."/db/ignored_domains.txt.php"));
        }

        if (file_exists(dirname(__FILE__)."/db/ignored_global.txt.php"))  {
            $blockArray = array_merge($blockArray, file(dirname(__FILE__)."/db/ignored_global.txt.php"));
        }

    }



    if (isset($blockArray)) {

        $postedData = array_merge((array)$_POST, (array)$_GET);

        // Keeping track of which entry was spammed.
        if (isset($postedData['id'])) {
            $id = $postedData['id'];
            unset($postedData['id']);
        } else {
            $id = '';
        }

        // ignore a few parameters..
        unset($postedData['p']);
        unset($postedData['f_title']);
        unset($postedData['f_subtitle']);
        unset($postedData['f_introduction_text']);
        unset($postedData['f_body_text']);
        unset($postedData['f_introduction']);
        unset($postedData['f_body']);
        unset($postedData['f_vialink']);
        unset($postedData['f_viatitle']);
        unset($postedData['tb_url']);
        // ignoring some more (irrelevant) parameters from the comment form
        unset($postedData['piv_spkey']);
        unset($postedData['piv_code']);
        unset($postedData['piv_weblog']);
        unset($postedData['piv_notify']);
        unset($postedData['piv_discreet']);
        unset($postedData['piv_rememberinfo']);
        unset($postedData['post']);

        $tmpData = "";
        foreach ($postedData as $value) {
            if (is_array($value)) {
                $tmpData .= implode(" ", $value);
            } else {
                $tmpData .= " $value";
            }
        }
        $postedData = strtolower($tmpData);

        if (strlen($postedData)<3) {
            // if there's no posted data, we can skip the checks.
            return true;
        } else {
            // else run the checks.
            foreach($blockArray as $blockPhrase)  {
                if(strpos($blockPhrase, "*") === false)  {
                    if(strpos($postedData, trim($blockPhrase)) !== false)  {
                        echo "Spam is not appreciated.";
                        include_once(dirname(__FILE__)."/modules/module_spamkiller.php");
                        $text = "Request %entry% matched blocked phrase '".trim($blockPhrase)."'";
                        if ($id != '') {
                            $text = str_replace('%entry%', "(on entry $id)", $text);
                        } else {
                            $text = str_replace('%entry% ', '', $text);
                        }
                        $text .= ' * Posted data: ' .  $postedData;
                        logspammer( $text, "bpcomment");
                        die();
                    }
                }
            }
            return true;
        }
    } else {
        return false;
    }
}


?>
