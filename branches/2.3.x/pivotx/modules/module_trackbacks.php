<?php
// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: $
//
// ---------------------------------------------------------------------------

// don't access directly..
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require_once dirname(dirname(__FILE__))."/lamer_protection.php";

/**
 * Enter description here...
 *
 */
function handlePostTrackback($uri,$date) {
    global $PIVOTX;

    $message = "";

    // Using our integrated Trackback Spam Killer 
    killtrackbackspam();
    
    // Initialise the IP blocklist.
    $blocklist = new IPBlock();

    // checking if IP address of trackbacking site is blocked
    if ($blocklist->isBlocked($_SERVER['REMOTE_ADDR'])) {
        debug("Blocked user from ".$_SERVER['REMOTE_ADDR']." tried to trackback");
        respondExit("Your IP-address has been blocked, so you are not".
        " allowed to leave trackbacks on this site. We know IP-adresses can easily be faked,".
        " but it helps." ,1);
    }

    // Get the entry from the DB..
    $entry = $PIVOTX['db']->read_entry($uri, $date);

    // Exit if non-existing ID supplied
    if (empty($entry['code'])) {
        respondExit('Entry not found', 1);
    }

    // Keep original excerpt for spam checks ...
    $orig_excerpt = $_POST['excerpt'];
    
    // Strip out HTML from input and convert to utf-8.
    $_POST['blog_name'] = i18n_str_to_utf8(strip_tags($_POST['blog_name']));
    $_POST['title'] = i18n_str_to_utf8(strip_tags($_POST['title']));
    $_POST['url'] = strip_tags($_POST['url']);
 
    if ($PIVOTX['config']->get('allow_html_in_comments')==1) {
        $_POST['excerpt'] = stripTagsAttributes($_POST['excerpt'], "*");
    } else {
        $_POST['excerpt'] = stripTagsAttributes($_POST['excerpt'], "<b><em><i><strong>");
    }
    $_POST['excerpt'] = i18n_str_to_utf8($_POST['excerpt']);

    $my_trackback = array(
        'entry_uid' => intval($entry['code']),
        'name' => $_POST['blog_name'],
        'title' => $_POST['title'],
        'url' => trim($_POST['url']),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'date' => formatDate("", "%year%-%month%-%day%-%hour24%-%minute%"),
        'excerpt' => trimText($_POST['excerpt'], 255, false, true, false),
    );

    // Exit if no URL is given - need to know URL to foreign entry that
    // trackbacked us.
    if (empty($my_trackback['url'])) {
        respondExit('No URL (url) parameter given', 1);
    }

    //here we do a check to prevent double entries...
    $duplicate=FALSE;

    if (isset($entry['trackbacks']) && (count($entry['trackbacks']) > 0 ) ) {
        foreach($entry['trackbacks'] as $loop_trackback) {
            $diff =  1 / ( min( strlen($loop_trackback['excerpt']), 200) /
                (levenshtein( substr($loop_trackback['excerpt'],0,200) , 
                    substr($my_trackback['excerpt'],0,200) )+1) );
            if ( ($diff < 0.25) && ($loop_trackback['ip'] == $my_trackback['ip']) ) {
                $duplicate=TRUE;
                break;
            }
        }
    }

    if (!$duplicate)  {
        // update the current entry
        $entry['trackbacks'][] = $my_trackback;
        $post = TRUE;
    } else {
        $message = 'Your trackback has not been stored, because it seems to be a duplicate';
        $post = FALSE;
    }

    if ($PIVOTX['config']->get('maxhrefs') > 0) {
        $low_excerpt = strtolower(trackbackFormat($orig_excerpt));
        if ( substr_count($low_excerpt, "href=") > $PIVOTX['config']->get('maxhrefs') ) {
            $message = 'The maximum number of hyperlinks was exceeded. Are you spamming us?';
            $post = FALSE;
        }
    }


    if ($post) {
        $PIVOTX['db']->set_entry($entry);

        $PIVOTX['db']->save_entry(FALSE); // do not update the index.

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // send mail..
        sendMailTrackback($my_trackback);

        debug("A trackback from '".$my_trackback['name']."' added.");

        //update the 'last trackbacks' file
        if (isset($my_trackback)) {
            generateLastTrackbacks($my_trackback);
        }

        // Clean the simple cache..
        $PIVOTX['cache']->clear();

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // After messing about with the trackbacks, clear the cache.
        $PIVOTX['cache']->cache['entries'] = array();

        respondExit();

    } else {

        respondExit($message,1);

    }

}

/**
 * Edits/modifies a trackback.
 *
 * @param array $entry The entry containing the trackback
 * @param string $track_key The key for the edited trackback
 * @param array $track_val The (content of the) edited trackback
 * @return void
 */
function editTrackback($entry,$track_key,$track_val) {
    global $PIVOTX;

    // if we're using SQL, delete the old trackback, before saving it again.
    // This is not necesary for 'flat' db's.
    if ($PIVOTX['db']->db_type == "sql") {
        $PIVOTX['db']->delete_trackback($track_key);
    }

    // If we are using a flat file db, we must update the last trackbacks file.
    // Iterate through the last trackbacks, updating trackback if found.
    if ($PIVOTX['db']->db_type == "flat") {
        $lasttrackfile = $PIVOTX['paths']['db_path']."ser_lasttrack.php";

        if (file_exists($lasttrackfile)) {
            $lasttrack = loadSerialize($lasttrackfile, true, true);
        } else {
            $lasttrack = array();
        }
        
        $olduid = makeTrackbackUID($entry['trackbacks'][$track_key]);
        foreach ($lasttrack as $key => $trackback) {
            if ($trackback['uid'] == $olduid) {
                // Don't add any new keys ...
                foreach ($lasttrack[$key] as $subkey => $value) {
                    if (isset($track_val[$subkey])) {
                        $lasttrack[$key][$subkey] = $track_val[$subkey];
                    }
                }
                // Update the UID accounting for any changes.
                $lasttrack[$key]['uid'] = makeTrackbackUID($lasttrack[$key]);
                break;
            }
        }

        saveSerialize($lasttrackfile, $lasttrack );
    }


    $entry['trackbacks'][$track_key] = $track_val;

    $PIVOTX['db']->set_entry($entry);
    $PIVOTX['db']->save_entry();
    
    // Remove the compiled/parsed pages from the cache.
    if($PIVOTX['config']->get('smarty_cache')){
        $PIVOTX['template']->clear_cache();
    }
        
}


/**
 * Creates the file that holds the last trackbacks. Just returns
 * if we're using SQL.
 *
 * @param array $temptrack
 * @return void
 */
function generateLastTrackbacks($temptrack) {
    global $PIVOTX;

    // If we're using MySQL, there's no need for the last trackbacks file..
    if ($PIVOTX['db']->db_type != "flat") {
        return "";
    }

    $lasttrack_file = $PIVOTX['paths']['db_path'].'ser_lasttrack.php';
    // if it exists, load it
    if (file_exists($lasttrack_file)) {
        $lasttrack = loadSerialize($lasttrack_file, true, true);
    } else {
         $lasttrack = array();
    }

    $lasttrack[] = array(
        'title' => $temptrack['title'],
        'excerpt' => trimText($temptrack['excerpt'],250),
        'name' => $temptrack['name'],
        'url' => $temptrack['url'],
        'date' => $temptrack['date'],
        'entry_uid' => $temptrack['entry_uid'],
        'uid' => makeTrackbackUID($temptrack),
        'category' => $PIVOTX['db']->entry['category'],
        'ip' => $temptrack['ip'],
    );

    if (count($lasttrack)>intval($PIVOTX['config']->get('lastcomm_amount_max'))) {
        array_shift ($lasttrack);
    }

    saveSerialize($lasttrack_file, $lasttrack );
}




function sendMailTrackback($my_trackback) {
    global $PIVOTX;

    $cat_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($PIVOTX['db']->entry['category']);

    $addr_arr= array();

    // Using the same settings as for comments
    foreach ($cat_weblogs as $this_weblog) {
        if ($PIVOTX['weblogs']->get($this_weblog, 'comment_sendmail') == 1) {
            $addr_arr = array_merge($addr_arr, explode(",", $PIVOTX['weblogs']->get($this_weblog, 'comment_emailto')));
        }
    }

    // make a nice title for the mail..
    if (strlen($PIVOTX['db']->entry['title'])>2) {
        $title=$PIVOTX['db']->entry['title'];
        $title=strip_tags($title);
    } else {
        $title=substr($PIVOTX['db']->entry['introduction'],0,300);
        $title=strip_tags($title);
        $title=str_replace("\n","",$title);
        $title=str_replace("\r","",$title);
        $title=substr($title,0,60);
    }

    $title = i18n_str_to_utf8($title);

    // maybe send some mail to authors..
    if (count($addr_arr)>0) {

        $adminurl = $PIVOTX['paths']['host'] . makeAdminPageLink();
        $id = formatDate($my_trackback["date"], "%ye%%month%%day%%hour24%%minute%");
        $editlink = $adminurl."?page=trackbacks&uid=".$PIVOTX['db']->entry['code'];
        /*
        $blocklink = $adminurl."menu=entries&func=edittracks&id=". $PIVOTX['db']->entry['code']. 
            "&blocksingle=".$my_trackback['ip'];
        */
        $body=sprintf(__('"%s" posted the following trackback').":", unentify($my_trackback['name']));
        $body.=sprintf("\n\n-------------\n");
        $body.=sprintf(__('Title').": %s\n", $my_trackback['title']);
        $body.=sprintf(__('URL').": %s\n", $my_trackback['url']);
        $body.=sprintf(__('Excerpt').":\n%s", unentify($my_trackback['excerpt']));
        $body.=sprintf("\n-------------\n");
        $body.=sprintf(__('IP-address').": %s\n", $my_trackback['ip']);
        $body.=sprintf(__('Date').": %s\n", $my_trackback['date']);
        $body.=sprintf("\n".__('This is a trackback on entry "%s"')."\n", $title);

        $body.=sprintf("-------------\n");
        $body.=sprintf("%s:\n%s%s\n", __('View this entry'), $PIVOTX['paths']['host'],
            makeFileLink($PIVOTX['db']->entry, "", ""));
        $body.=sprintf("\n%s:\n%s\n", __('Edit this trackback'), $editlink );
        //$body.=sprintf("\n%s:\n%s\n", __('Block this IP'), $blocklink );
        $body = i18n_str_to_utf8($body);

        // pivotxMail encodes the subject and adds the needed headers for UTF-8
        $subject = sprintf(__('New trackback on entry "%s"'), $title);

        $addr_arr = array_unique($addr_arr);

        foreach($addr_arr as $addr) {
            $addr = trim($addr);
            if (pivotxMail($addr, $subject, $body, $add_header)) {
                debug("Sent Mail to $addr for '".$my_trackback['name']."'");
            } else {
                debug("Failed sending mail to $addr for '".$my_trackback['name']."'");
                break;
            }
        }

    }

}

/**
 * Creates (an unique) UID for trackbacks. 
 *
 * This function isn't needed for SQL DB since this key is part of the table. 
 * However for flatfile DB this is convenient.
 *
 * @param array $trackback
 * @return string
 */
function makeTrackbackUID($trackback) {

    return substr(md5($trackback['entry_uid'].$trackback['ip'].$trackback['date'].$trackback['excerpt']),0,10);

}

/**
 * Returns the Javascript code and creates the key for hardened trackbacks.
 */
function getTracbackKeyJS($uri, $date) {
    global $PIVOTX;

    // Abort immediately if hardened trackbacks isn't enabled.
    if ($PIVOTX['config']->get('hardened_trackback') != 1)  {
        exit;
    }
    
    // Get the entry from the DB..
    $entry = $PIVOTX['db']->read_entry($uri, $date);

    // Exit if non-existing ID supplied
    if (empty($entry['code'])) {
        debug('Entry not found');
    } else {
        $id = intval($entry['code']);
    }

    $keydir = $PIVOTX['paths']["db_path"]."tbkeys/";
    $tburl = $PIVOTX['paths']['host'] . makeFileLink($entry['code'], '', '');
    $trackback = getDefault($PIVOTX['config']->get('localised_trackback_name'), "trackback");
    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $tburl .= "&amp;$trackback&amp;key=";
    } else {
        $tburl .= "/$trackback/?key=";
    }

    if (!strstr($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_HOST"]))  {
        // Creating a bogus key
        $tbkey = md5(microtime());
        debug("hardened trackbacks: illegal request - creating bogus key");
    } else {
        makeDir($keydir);
        $tbkey = md5($PIVOTX['config']->get('server_spam_key').$_SERVER["REMOTE_ADDR"].$id.time());
        if (!touch ($keydir.$tbkey)) {
            debug("hardened trackbacks: directory $keydir isn't writable - can't create key");
        } else {
            chmodFile($keydir.$tbkey);
        }
    }

    // Getting the time offset between the web and file server (if there is any)
    $offset = timeDiffWebFile($tbkey_debug);

    // delete keys older than 15 minutes
    $nNow = time();
    $handle=opendir($keydir);
    while (false!==($file = readdir($handle))) {
        $filepath = $keydir.$file;
        if (!is_dir($filepath) && ($file != "index.html")) {
            $Diff = ($nNow - filectime($filepath));
            if ($Diff > (60*15 + $offset)) {
                unlink($filepath);
            }
        }
    }
    closedir($handle);

    header('Content-Type: text/javascript');

    echo <<<EOM

function showTBURL_{$entry['code']}(element_id)  {
    var element = document.getElementById(element_id);
    element.innerHTML = '<br />{$tburl}' + '{$tbkey}';
}

function showTBURLgen_{$entry['code']}(element_id, tburl_gen)  {
    var element = document.getElementById(element_id);
    element.innerHTML = tburl_gen;
}

EOM;

    exit;

}


/**
* Print result of trackback posting and exit
*
* @param   bool    $error     print error
* @param   string  $msg       addtional text to display
* @param   bool
*/
function respondExit($msg = "", $error = false) {
    header("Content-Type: text/xml\n\n");
    print "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";

    print "<response>\n";
    if($error) printf("<error>1</error>\n%s\n",xml("message", $msg));
    else printf("<error>0</error>\n%s", $msg);
    print "</response>\n";

    exit;

}

/**
* Build xml tag
*
* @param   string   $tag     xml tag name
* @param   string   $value   value of tag
* @return  string
*/
function xml($tag, $value) {
    return sprintf("<%s>%s</%s>\n",
                    $tag,
                    htmlspecialchars($value),
                    $tag
            );
}



?>
