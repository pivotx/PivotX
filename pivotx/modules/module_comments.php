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
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require_once dirname(dirname(__FILE__))."/lamer_protection.php";


/**
 * Enter description here...
 *
 */
function handlePostComment() {
    global $weblogmessage, $PIVOTX, $temp_comment;
    
    $entry = $PIVOTX['db']->read_entry($_POST['piv_code']);

    // Check if we're allowed to comment on this entry. 'isset' is needed, because old entries
    // might not have 'allow comments' set to either choice.
    if (isset($entry['allow_comments']) && ($entry['allow_comments']==0) ) {
        echo "Spam is not appreciated.";
        logspammer( $_POST['piv_comment'], "closedcomments");
        die();
    }

    // execute a hook here before a comment is processed
    $PIVOTX['extensions']->executeHook('comment_before_processing', $entry);

    $registered = 0;
    // check if the current poster is a (logged in) registered visitor.
    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();
    if ($visitor = $visitors->isLoggedIn()) {
        if ($visitor['name'] == $_POST['piv_name']) {
            $registered = 1;
        }
    }

    // Strip out HTML from input..
    $_POST['piv_name'] = strip_tags($_POST['piv_name']);
    $_POST['piv_email'] = strip_tags($_POST['piv_email']);
    $_POST['piv_url'] = strip_tags($_POST['piv_url']);
    
    if ($PIVOTX['config']->get('allow_html_in_comments')==1) {
        $_POST['piv_comment'] = stripTagsAttributes($_POST['piv_comment'], "*");
    } else {
        if ($PIVOTX['config']->get('allow_html_readable_in_comments')==1) {
            // Make html readable? Done by adding a space after the "<"; it will not be stripped in stripTagsAttributes
            $_POST['piv_comment'] = str_replace("<", "< ", $_POST['piv_comment']);
            // Now repair the "damage"
            $_POST['piv_comment'] = str_replace("<  ", "< ", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< b>", "<b>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< /b>", "</b>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< em>", "<em>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< /em>", "</em>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< i>", "<i>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< /i>", "</i>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< strong>", "<strong>", $_POST['piv_comment']);
            $_POST['piv_comment'] = str_replace("< /strong>", "</strong>", $_POST['piv_comment']);
        } else {
            $_POST['piv_comment'] = stripTagsAttributes($_POST['piv_comment'], "<b><em><i><strong>");
        }
    }

    // Do some more processing on the comment itself: trimming, standardizing line-breaks.
    $comment_text = stripTrailingSpace($_POST['piv_comment']);
    $comment_text = str_replace("\r\n", "\n", $comment_text); // CRLF(Win) to LF
    $comment_text = str_replace("\r", "\n", $comment_text); // CR(Mac) to LF


    $temp_comment = array(
        'entry_uid' => intval($_POST['piv_code']),
        'name' => encodeText($_POST['piv_name']),
        'email' => encodeText($_POST['piv_email']),
        'url' => encodeText($_POST['piv_url']),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'useragent' => $_SERVER['HTTP_USER_AGENT'],
        'date' => formatDate("", "%year%-%month%-%day%-%hour24%-%minute%"),
        'comment' => $comment_text,
        'registered' => $registered,
        'notify' => intval($_POST['piv_notify']),
        'discreet' => intval($_POST['piv_discreet']),
        'rememberinfo' => intval($_POST['piv_rememberinfo']),
        'moderate' => $PIVOTX['config']->get('moderate_comments'),
        'spamscore' => 0
    );



    if ($temp_comment['rememberinfo']==1) {
        rememberCommentInfo($temp_comment);
    }



    //here we do a check to prevent double entries...
    $duplicate=FALSE;

    if (isset($entry['comments']) && (count($entry['comments']) > 0 ) ) {
        foreach($entry['comments'] as $loop_comment) {
            $diff =  1 / ( min( strlen($loop_comment['comment']), 200) /
                (levenshtein(substr($loop_comment['comment'],0,200) ,
                    substr($temp_comment['comment'],0,200)) + 1) );
            if ( ($diff < 0.25) && ($loop_comment['ip'] == $temp_comment['ip']) ) {
                $duplicate=TRUE;
                break;
            }
        }
    }

    
    // Check for Hashcash violations..
    if ( ($PIVOTX['config']->get('hashcash')==1) && (!hashcash_check_hidden_tag())) {
        $weblogmessage = getDefault($PIVOTX['config']->get('hashcash_message'),__('The Hashcash code was not valid, so this comment could not be posted. If you believe this is an error, please make sure you have a modern browser, and that Javascript is enabled. If it still doesn\'t work, contact the maintainer of this website.'));
        unset($_POST['post']);
        $_POST['preview'] = true;
        $spammessage = substr(implode(", ", $temp_comment), 0, 250);
        logspammer($_SERVER["REMOTE_ADDR"], "hashcash", "pom pom pom", $spammessage);
    }
    

    // Check for SpamQuiz violations, but not when previewing..
    if ( ($PIVOTX['config']->get('spamquiz')==1) && !isset($_POST['preview']) ) {

        // Is the entry old enough?
        $entryDate = substr($PIVOTX['db']->entry['date'], 0, 10);
        $then = strtotime($entryDate);
        $secsPerDay = 60*60*24;
        $now = strtotime('now');
        $diff = $now - $then;
        $dayDiff = ($diff/$secsPerDay);
        $numDaysOld = (int)$dayDiff;
    
        if ($numDaysOld>$PIVOTX['config']->get("spamquiz_age")) {
            if (strtolower($_POST['spamquiz_answer']) != (strtolower($PIVOTX['config']->get("spamquiz_answer")))) {
                $weblogmessage = __('The Spamquiz answer was not correct, so this comment could not be posted. If you believe this is an error, please try again. If it still doesn\'t work, contact the maintainer of this website.');
                unset($_POST['post']);
                $_POST['preview'] = true;
                logspammer($_SERVER["REMOTE_ADDR"], "spamquiz");                
            } else {
                // Store the correct answer in a cookie.
                $sess = $PIVOTX['session'];
                setcookie("spamquiz_answer", $_POST["spamquiz_answer"], 
                    time() + $sess->cookie_lifespan, $sess->cookie_path, $sess->cookie_domain );
            }
        }
            
    }


    // set the message and take proper action:
    if (isset($_POST['preview'])) {

        // Add a 'show in preview' flag to $temp_comment, otherwise it would be suppressed on display
        $temp_comment['showpreview'] = 1;

        // update the current entry
        $entry['comments'][] = $temp_comment;
        if (empty($weblogmessage)) {
            $weblogmessage = __('You are previewing your comment. Be sure to click on "Post Comment" to store it.');
        }
        unset($_POST['post']);
        $_POST['preview'] = TRUE;

    } else if ($temp_comment['spamscore'] > $PIVOTX['config']->get('spamthreshold') )  {

        // Add a 'show in preview' flag to $temp_comment, otherwise it would be suppressed on display
        $temp_comment['showpreview'] = 1;

        $weblogmessage = __('Your comment has not been stored, because it seems to be spam.');
        unset($_POST['post']);
        $_POST['preview'] = TRUE;

    } else if ($duplicate)  {

        $temp_comment['duplicate'] = true;

        // Add a 'show in preview' flag to $temp_comment, otherwise it would be suppressed on display
        $temp_comment['showpreview'] = 1;

        $weblogmessage = __('Your comment has not been stored, because it seems to be a duplicate of a previous entry.');
        unset($_POST['post']);
        $_POST['preview'] = TRUE;

    } else if ($PIVOTX['config']->get('moderate_comments') == 1) {

        // update the current entry
        $entry['comments'][] = $temp_comment;

        $weblogmessage = __('Your comment has been stored. Because comment moderation is enabled, it is now waiting for approval by an editor.');
        $_POST['post'] = TRUE;

    } else {

        // update the current entry
        $entry['comments'][] = $temp_comment;

        $weblogmessage =  __('Your comment has been stored.');

        $_POST['post'] = TRUE;

    }


    // if comment or name is missing, give a notice, and show the form again..
    if ( strlen($temp_comment['name'])<2 ) {
        $weblogmessage = __('You should type your name (or an alias) in the "name"-field. Be sure to click on "Post Comment" to store it permanently.');
        unset($_POST['post']);
        $_POST['preview'] = TRUE;
    }

    if ( strlen($temp_comment['comment'])<3 ) {
        $weblogmessage = __('You should type something in the "comment"-field. Be sure to click on "Post Comment" to store it permanently.');
        unset($_POST['post']);
        $_POST['preview'] = TRUE;
    }


    if ($PIVOTX['config']->get('maxhrefs') > 0) {
        $low_comment = strtolower($temp_comment['comment']);
        $low_comment_formatted = strtolower(commentFormat($temp_comment['comment']));
        if ( (substr_count($low_comment, "href=") > $PIVOTX['config']->get('maxhrefs') ) ||
            (substr_count($low_comment_formatted, "href=") > $PIVOTX['config']->get('maxhrefs') ) ) {
            $weblogmessage = __('The maximum number of hyperlinks was exceeded. Stop spamming.');
            unset($_POST['post']);
            $_POST['preview'] = TRUE;
        }
    }

    // execute a hook here after a comment is processed but before that comment is saved
    $PIVOTX['extensions']->executeHook('comment_before_save', $entry);

    if (isset($_POST['post'])) {

        $PIVOTX['db']->set_entry($entry);

        $PIVOTX['db']->save_entry(FALSE); // do not update the index.

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }        

        //update the 'latest comments' file
        if (isset($temp_comment)) {
            if($PIVOTX['config']->get('moderate_comments')!=1)  {
                generateLatestComments($temp_comment);
                debug("comment from '".$_POST['piv_name']."' added.");
            } else {
                generateModerationQueue($temp_comment);
                debug("comment from '".$_POST['piv_name']."' added to moderation queue.");
            }
        }

        // Handle the users that want to be notified via email.. 
        if ($PIVOTX['config']->get('dont_send_mail_notification') != 1) {
            $notifications = sendMailNotification('comment', array($PIVOTX['db']->entry, $temp_comment, 
                $PIVOTX['config']->get('moderate_comments')));
        }

        // send mail..
        sendMailComment($temp_comment, $notifications);

        // Don't display the 'preview' of the comment after posting.
        $temp_comment=array();
        unset($_POST);

        // Clean the simple cache..
        $PIVOTX['cache']->clear();

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }
        
        // Redirect to the entrypage from which we came. (prevents reload-resubmit)
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, "?")>0) {
            $uri .= "&weblogmessage=" . urlencode($weblogmessage);
        } else {
            $uri .= "?weblogmessage=" . urlencode($weblogmessage);
        }
        header('Location: '.$uri);
        exit();
        
    }


    // Set the 'you are previewing' message..
    if (isset($_POST['preview']) && empty($weblogmessage)) {
        $weblogmessage = __('You are previewing your comment. Be sure to click on "Post Comment" to store it.');
    }

    // execute a hook here after a comment is saved and the mails are sent
    $PIVOTX['extensions']->executeHook('comment_after_save', $entry);
        
    // After messing about with the comments, clear the cache.
    $PIVOTX['cache']->cache['entries'] = array();
}

/**
 * Creates the file that holds the latest comments. Just returns
 * if we're using SQL.
 *
 * @param array $tempcomm
 * @return void
 */
function generateLatestComments($tempcomm) {
    global $PIVOTX;

    // If we're using MySQL, there's no need for the latest comments file..
    if ($PIVOTX['db']->db_type != "flat") {
        return "";
    }

    $lastcomm_file = $PIVOTX['paths']['db_path'].'ser_lastcomm.php';
    // if it exists, load it
    if (file_exists($lastcomm_file)) {
        $lastcomm = loadSerialize($lastcomm_file, true, true);
    } else {
         $lastcomm = array();
    }

    $lastcomm[] = array(
        'name' => $tempcomm['name'],
        'email' => $tempcomm['email'],
        'url' => $tempcomm['url'],
        'date' => $tempcomm['date'],
        'comment' => trimText($tempcomm['comment'],250),
        'entry_uid' => $tempcomm['entry_uid'],
        'uid' => makeCommentUID($tempcomm),
        'title' => trimText($PIVOTX['db']->entry['title'],50),
        'category' => $PIVOTX['db']->entry['category'],
        'ip' => $tempcomm['ip'],
        'useragent' => $tempcomm['useragent'],
    );

    if (count($lastcomm)>intval($PIVOTX['config']->get('lastcomm_amount_max'))) {
        array_shift ($lastcomm);
    }

    saveSerialize($lastcomm_file, $lastcomm );
}



/**
 * Creates the file that holds the queue for comment moderation. Just returns
 * if we're using SQL.
 *
 * @param array $tempcomm
 * @return void
 */
function generateModerationQueue($tempcomm) {
    global $PIVOTX;

    // If we're using MySQL, there's no need for the latest comments file..
    if ($PIVOTX['db']->db_type != "flat") {
        return "";
    }

    $modqueue_file = $PIVOTX['paths']['db_path'].'ser_modqueue.php';
    // if it exists, load it
    if (file_exists($modqueue_file)) {
        $modqueue = loadSerialize($modqueue_file, true, true);
    } else {
        $modqueue = array();
    }


    $modqueue[] = array(
        'name' => $tempcomm['name'],
        'email' => $tempcomm['email'],
        'url' => $tempcomm['url'],
        'date' => $tempcomm['date'],
        'comment' => $tempcomm['comment'],
        'entry_uid' => $PIVOTX['db']->entry['code'],
        'title' => trimText($PIVOTX['db']->entry['title'],50),
        'category' => $PIVOTX['db']->entry['category'],
        'ip' => $tempcomm['ip'],
        'useragent' => $tempcomm['useragent'],
    );

    saveSerialize($modqueue_file, $modqueue );

}


/**
 * Reads the comments that are in the queue for comment moderation.
 *
 * @return array
 */
function getModerationQueue() {
    global $PIVOTX;

    $modqueue = array();

    // If we're using MySQL, get it from the DB..
    if ($PIVOTX['db']->db_type == "sql") {

        $commentstable = $PIVOTX['db']->db_lowlevel->commentstable;
        $entriestable = $PIVOTX['db']->db_lowlevel->entriestable;

        $PIVOTX['db']->db_lowlevel->sql->query("SELECT co.*, e.title AS title, e.user
            FROM $commentstable AS co
            LEFT JOIN $entriestable AS e ON (co.entry_uid=e.uid)
            WHERE co.moderate=1
            ORDER BY date DESC;"
        );

        $modqueue = $PIVOTX['db']->db_lowlevel->sql->fetch_all_rows();

        if (!empty($modqueue)) {
            foreach($modqueue as $key=>$value) {
                $modqueue[$key]['allowedit'] = $PIVOTX['users']->allowEdit('comment', $value['user']);
            }
        }

        // make sure we still have an array, and not 'false'
        if ($modqueue===false) {
            $modqueue = array();
        }

    } else {

        // Else we get it from file..

        $modqueue_file = $PIVOTX['paths']['db_path'].'ser_modqueue.php';

    	// Check if there are any comments waiting to be moderated..
    	if (file_exists($modqueue_file)) {
    		$modqueue = array_reverse(loadSerialize($modqueue_file, true, true));
    	}

        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    	// Make sure 'entry_uid' is set, and make up an uid to identify the comments.
    	foreach($modqueue as $key=>$value) {
    	    if (!isset($value['entry_uid'])) {
    	        $modqueue[$key]['entry_uid'] = $modqueue[$key]['code'];
    	    }

    	    $modqueue[$key]['uid'] = makeCommentUID($modqueue[$key]);

            // make sure the moderate is set..
            $modqueue[$key]['moderate'] = 1;

            // Note: This is flawed: does not take into consideration the postings by the user
            // himself. Not quite sure how to do that efficiently with flat files.
            $modqueue[$key]['allowedit'] = ($currentuser['userlevel'] >= PIVOTX_UL_ADVANCED) ? "1" : "0";

    	}

    }

    return $modqueue;

}

/**
 * Approve comments in the moderation queue
 *
 * @param array $comments
 */
function approveComments($comments) {
    global $PIVOTX;
    
    // Abort immediately if no comments passed. 
    if (empty($comments)) {
        return;
    }

    // execute a hook here before a comment is processed
    $PIVOTX['extensions']->executeHook('comment_approve', $comments);

    if ($PIVOTX['db']->db_type == "sql") {

        $commentstable = $PIVOTX['db']->db_lowlevel->commentstable;

        $comments = array_map('intval', $comments);
        $uids = implode(", ", $comments);

        $PIVOTX['db']->db_lowlevel->sql->query("UPDATE $commentstable
            SET moderate=0
            WHERE uid IN ($uids);"
        );

    } else {

        // process the queue for flat file model
        $modqueue = getModerationQueue();

        // Iterate through the queue, approving items as we go.
        foreach ($modqueue as $key => $comment) {
            if (in_array($comment['uid'], $comments)) {
                moderateProcessComment($comment, 1);
                unset($modqueue[$key]);
            }
        }

        // Save back what's left of the moderation queue..
        $modqueue_file = $PIVOTX['paths']['db_path'].'ser_modqueue.php';
        saveSerialize($modqueue_file, $modqueue );

    }


}


/**
 * Delete comments in the moderation queue
 *
 * @param array $comments
 */
function deleteComments($comments) {
    global $PIVOTX;
 
    // Abort immediately if no comments passed. 
    if (empty($comments)) {
        return;
    }

    // execute a hook here before a comment is processed
    $PIVOTX['extensions']->executeHook('comment_approve', $comments);

    if ($PIVOTX['db']->db_type == "sql") {

        $commentstable = $PIVOTX['db']->db_lowlevel->commentstable;

        $comments = array_map('intval', $comments);
        $uids = implode(", ", $comments);

        $PIVOTX['db']->db_lowlevel->sql->query("DELETE FROM $commentstable
            WHERE uid IN ($uids);"
        );

    } else {

        // process the queue for flat file model
        $modqueue = getModerationQueue();

        // Iterate through the queue, approving items as we go.
        foreach ($modqueue as $key => $comment) {
            if (in_array($comment['uid'], $comments)) {
                moderateProcessComment($comment, 2);
                unset($modqueue[$key]);
            }
        }

        // Save back what's left of the moderation queue..
        $modqueue_file = $PIVOTX['paths']['db_path'].'ser_modqueue.php';
        saveSerialize($modqueue_file, $modqueue );

    }


}

/**
 * Edits/modifies a comment.
 *
 * @param array $entry The entry containing the comment
 * @param string $comm_key The key for the edited comment
 * @param array $comm_val The (content of the) edited comment
 * @return void
 */
function editComment($entry,$comm_key,$comm_val) {
    global $PIVOTX;

    // unset unneeded values.
    unset($comm_val['uid']);
    unset($comm_val['entry_uid']);

    // Make sure we don't store negative values..
    $comm_val['registered'] = max($comm_val['registered'], 0);
    $comm_val['notify'] = max($comm_val['notify'], 0);
    $comm_val['discreet'] = max($comm_val['discreet'], 0);
    $comm_val['moderate'] = max($comm_val['moderate'], 0);

    // if we're using SQL, delete the old comment, before saving it again.
    // This is not necesary for 'flat' db's.
    if ($PIVOTX['db']->db_type == "sql") {
        $PIVOTX['db']->delete_comment($comm_key);
    }

    // If we are using a flat file db, we must update the latest comments file.
    // Iterate through the latest comments, updating comment if found.
    if ($PIVOTX['db']->db_type == "flat") {
        $lastcommfile = $PIVOTX['paths']['db_path']."ser_lastcomm.php";

        if (file_exists($lastcommfile)) {
            $lastcomm = loadSerialize($lastcommfile, true, true);
        } else {
            $lastcomm = array();
        }
        
        $olduid = makeCommentUID($entry['comments'][$comm_key]);
        foreach ($lastcomm as $key => $comment) {
            if ($comment['uid'] == $olduid) {
                // Don't add any new keys ...
                foreach ($lastcomm[$key] as $subkey => $value) {
                    if (isset($comm_val[$subkey])) {
                        $lastcomm[$key][$subkey] = $comm_val[$subkey];
                    }
                }
                // Update the UID accounting for any changes.
                $lastcomm[$key]['uid'] = makeCommentUID($lastcomm[$key]);
                break;
            }
        }

        saveSerialize($lastcommfile, $lastcomm );
    }


    $entry['comments'][$comm_key] = $comm_val;

    $PIVOTX['db']->set_entry($entry);
    $PIVOTX['db']->save_entry();

    // Remove the compiled/parsed pages from the cache.
    if($PIVOTX['config']->get('smarty_cache')){
        $PIVOTX['template']->clear_cache();
    }    
    
}

 





/**
 * Process a comment for moderation. Action 1 = allow,
 * action 2 = delete.
 *
 * When allowed, the comment is changed in the entry, so it is displayed, and it
 * is added to the latest_comments. Whene deleted it'll be deleted from the entry
 *
 * @param array $comm
 * @param integer $action
 */
function moderateProcessComment($comm, $action) {
    global $PIVOTX;

    if (!isset($db)) {
        $db = new db();
    }

    if ($action==1) {
        // Allow comment.

        // First, get the entry..
        $entry = $PIVOTX['db']->read_entry($comm['entry_uid']);

        $send_notification = false;

        foreach ($entry['comments'] as $key => $loopcomm) {

            if (($loopcomm['name']==$comm['name']) && ($loopcomm['date']==$comm['date'])) {

                // fix the entry..
                $entry['comments'][$key]['moderate'] = 0;

                // Store the comment that's approved. We need it a bit later on to send the notifications
                $modcomment = $entry['comments'][$key];

                // Save it..
                $PIVOTX['db']->set_entry($entry);
                $PIVOTX['db']->save_entry();

                // Remove the compiled/parsed pages from the cache.
                if($PIVOTX['config']->get('smarty_cache')){
                    $PIVOTX['template']->clear_cache();
                }        

                $lastcommfile = $PIVOTX['paths']['db_path']."ser_lastcomm.php";

                // Add it to the 'latest comments'..
                if (file_exists($lastcommfile)) {
                     $lastcomm = loadSerialize($lastcommfile, true, true);
                } else {
                     $lastcomm = array();
                }
                $lastcomm[] = $comm;

                saveSerialize($lastcommfile, $lastcomm );
                $send_notification = true;

            }

        }

        if ($send_notification && ($PIVOTX['config']->get('dont_send_mail_notification') != 1)) {
            // Handle the users that want to be notified via email..
            sendMailNotification('comment',array($entry,$modcomment));
        }

    } else if ($action==2) {
        // Delete comment.

        // First, get the entry..
        $entry = $PIVOTX['db']->read_entry($comm['entry_uid']);

        foreach ($entry['comments'] as $key => $loopcomm) {

            if (($loopcomm['name']==$comm['name']) && ($loopcomm['date']==$comm['date'])) {

                // fix the entry..
                unset($entry['comments'][$key]);

                // Save it..
                $PIVOTX['db']->set_entry($entry);
                $PIVOTX['db']->save_entry();

            }
        }
        
        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }
        
    }

}





function sendMailComment($temp_comment, $notifications='') {
    global $PIVOTX;

    $cat_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($PIVOTX['db']->entry['category']);

    $addr_arr= array();

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
        $id = safeString($temp_comment["name"],TRUE) . "-" .  
            formatDate($temp_comment["date"], "%ye%%month%%day%%hour24%%minute%");
        $editlink = $adminurl."?page=comments&uid=".$PIVOTX['db']->entry['code'];
        $approvelink = $adminurl."?page=comments";
        //$deletelink = $adminurl."menu=moderate_comments&".urlencode($id)."=2";
        //$blocklink = $adminurl."menu=entries&func=editcomments&id=". $PIVOTX['db']->entry['code']."&blocksingle=".$temp_comment['ip'];

        $comment = ($temp_comment['comment']);

        // $comment = unentify($comment);

        $body=sprintf(__('"%s" posted the following comment').":\n\n", unentify($temp_comment['name']));
        $body.=sprintf("%s", $comment);
        $body.=sprintf("\n\n-------------\n\n");
        $body.=sprintf(__('Name').": %s\n", unentify($temp_comment['name']));
        $body.=sprintf(__('IP-address').": %s\n", $temp_comment['ip']);
        $body.=sprintf(__('Date').": %s\n", $temp_comment['date']);
        $body.=trim(sprintf(__('Email').": %s", $temp_comment['email']))."\n";
        $body.=trim(sprintf(__('URL').": %s\n", $temp_comment['url']))."\n";
        $body.=sprintf("\n".__('This is a comment on entry "%s"')."\n", $title);

        $body.= $notifications;

        $body.=sprintf("\n-------------\n\n");
        if ($PIVOTX['config']->get('moderate_comments')==1) {
            $body.=sprintf(__('Moderate this comment').":\n%s\n", $approvelink);
            // $body.=sprintf("\n".__('Delete this comment').":\n%s\n", $deletelink);
        }
        $body.=sprintf("\n%s:\n%s%s\n", __('View this entry'), $PIVOTX['paths']['host'], 
            makeFileLink($PIVOTX['db']->entry, "", ""));
        $body.=sprintf("\n%s:\n%s%s\n", __('View this comment'), $PIVOTX['paths']['host'], 
            makeFileLink($PIVOTX['db']->entry, "", $id));
        $body.=sprintf("\n%s:\n%s\n", __('Edit this comment'), $editlink );
        //$body.=sprintf("\n%s:\n%s\n", __('Block this IP'), $blocklink );
        $body = i18n_str_to_utf8($body);

        // pivotxMail encodes the subject and adds the needed headers for UTF-8
        $subject = sprintf(__('New comment on entry "%s"'), $title);

        $addr_arr = array_unique($addr_arr);

        foreach($addr_arr as $addr) {
            $addr = trim($addr);
            if (pivotxMail($addr, $subject, $body, $add_header)) {
                debug("Sent Mail to $addr for '".$temp_comment['name']."'");
            } else {
                debug("Failed sending mail to $addr for '".$temp_comment['name']."'");
                break;
            }
        }

    }

}


/**
 * Store the commenter's info in a cookie..
 *
 * @param array $comment
 */
function rememberCommentInfo($comment) {
    global $PIVOTX;

    // clear pipes from the data..
    foreach ($comment as $key => $value) {
        $comment[$key] = str_replace('|', '-', $value);
    }

    $cookievalue = array($comment['name'], $comment['email'], $comment['url'], $comment['registered'], $comment['notify'], $comment['discreet']);
    $cookievalue = stripslashes(implode('|', $cookievalue));

    $PIVOTX['session']->setCookie('pivotxcomment', $cookievalue);
    
    // Also set a cookie for the spamquiz answer, while we're at it..
    if (!empty($_POST['spamquiz_answer'])) {
        $PIVOTX['session']->setCookie('spamquiz_answer', $_POST['spamquiz_answer']);    
    }

}

/**
 * Creates (an unique) UID for comments. 
 *
 * This function isn't needed for SQL DB since this key is part of the table. 
 * However for flatfile DB this is convenient.
 *
 * @param array $comment
 * @return string
 */
function makeCommentUID($comment) {

    return substr(md5($comment['entry_uid'].$comment['ip'].$comment['date'].$comment['comment']),0,10);

}


?>
