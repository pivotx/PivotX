<?php

/**
 * Contains the functions we use to support moblogging
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
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";

// Resources needed
$moblogdir = dirname(__FILE__) . "/moblog/";
require_once($moblogdir.'mail.class.php');
if (!class_exists('pear')){
	require_once $moblogdir.'pear.php';
}
require_once($moblogdir.'socket.php');
include_once($moblogdir.'pop3.php');
include_once($moblogdir.'mimedecode.php');


/**
 * The class that contains the moblogging functions.
 *
 */
class Moblog {

    var $cfg;
    var $active;
    var $entry;
    var $mimedecode_params;

    /**
     * Initializes the class.
     */
    function Moblog() {
        global $PIVOTX, $moblogdir;
        
        if ($PIVOTX['config']->get('moblog_active') == '') {
            $this->active = false;
            return;
        } else {
            $this->active = true;
        }
        
        $cfg = array();
        
        $imap_type = $PIVOTX['config']->get('moblog_imap_protocol');
        if ($imap_type == 'x') {
            $cfg['use_imap'] = false;
        } else {
            $cfg['use_imap'] = true;
        }

        if ($cfg['use_imap'] && !function_exists('imap_open')) {
            debug("There doesn't seem to be IMAP support in your PHP. " . 
                "Disable the IMAP extension in the PivotX configuration and try again.");
            $this->active = false;
            return;
        }

        $options = array('moblog_server', 'moblog_username', 'moblog_password', 
            'moblog_author', 'moblog_category', 'moblog_imap_protocol', 'moblog_allowed_senders');

        foreach ($options as $option) {
            $cfg[str_replace('moblog_','',$option)] = $PIVOTX['config']->get($option);
        }
        $cfg['allowed_senders'] = preg_split("/[\s,]+/", $cfg['allowed_senders']);

        if (empty($cfg['username']) && empty($cfg['password']) && empty($cfg['server'])) {
            debug("Username, password and mail server must be set - " . 
                "check the settings in the PivotX configuration and try again.");
            $this->active = false;
            return;
        }

        $cfg['pop_port'] = getDefault($PIVOTX['config']->get('moblog_pop_port'), 110);
        $cfg['imap_mailbox'] = getDefault($PIVOTX['config']->get('moblog_imap_mailbox'),'' );
        $cfg['spam_category'] = getDefault($PIVOTX['config']->get('moblog_spam_category'), 'spam');
        $cfg['replyaddress'] = getDefault($PIVOTX['config']->get('moblog_replyaddress'), '');
        $cfg['title'] = getDefault($PIVOTX['config']->get('moblog_title'), "Moblog on " . date("m-d H:i"));
        $cfg['status'] = getDefault($PIVOTX['config']->get('moblog_status'), 'publish');
        $cfg['allow_comments'] = getDefault($PIVOTX['config']->get('moblog_allow_comments'), 1);
        $cfg['save_mail'] = getDefault($PIVOTX['config']->get('moblog_save_mail'), false);
        $cfg['leave_on_server'] = getDefault($PIVOTX['config']->get('moblog_leave_on_server'), false);
        $cfg['send_confirmation'] = getDefault($PIVOTX['config']->get('moblog_send_confirmation'), true);
        $cfg['skip_thumbnail'] = getDefault($PIVOTX['config']->get('moblog_skip_thumbnail'), false);
        $cfg['maxwidth'] = getDefault($PIVOTX['config']->get('moblog_maxwidth'), 400);
        $cfg['maxheight'] = getDefault($PIVOTX['config']->get('moblog_maxheight'), 200);
        $cfg['quality'] = getDefault($PIVOTX['config']->get('moblog_quality'), 60);
        $cfg['click_for_image'] = getDefault($PIVOTX['config']->get('moblog_click_for_image'), __('Click for image'));

        include_once($moblogdir.'known_providers.php');

        $this->cfg = $cfg;
    }

    function execute() {  
        global $PIVOTX;

        if (!$this->active) {
            return; 
        }

        $messages[] =  "Checking email..";

        // Create the class and fetch the list of available emails..
        if ($this->cfg['use_imap']) {
            $mail = new Mail;
            $protocol = strtolower(substr($this->cfg['imap_protocol'], 0, 4));
            if (str_replace($protocol, '', $this->cfg['imap_protocol']) == 's') {
               $secure = true;
            } else { 
               $secure = false;
            }
            $server = array(
                'type'      => $protocol,
                'server'    => $this->cfg['server'],
                'secure'    => $secure,
                'mailbox'   => $this->cfg['imap_mailbox'],
                'username'  => $this->cfg['username'],
                'password'  => $this->cfg['password']
            );
            if (!$mail->connect($server)) {
                debug("Moblog: No connection (using IMAP extension).");
                exit(1);
            } else {
                $messages[] = "Moblog: OK connection (using IMAP extension).\n";
            }
            $mail->parse_messages();
            if (is_array($mail->messages)) {
                $listing = $mail->messages;
            } else {
                $listing = array();
            }
        } else {
            $pop3 = new Net_POP3();
            $ret = $pop3->connect($this->cfg['server'] , $this->cfg['pop_port'] );
            if (!$ret) {
                debug("Moblog: No connection.");
                exit(1);
            } elseif (PEAR::isError( $ret= $pop3->login($this->cfg['username'] , $this->cfg['password'], 'USER' ) )){
                debug("Moblog: error logging in: " . $ret->getMessage());
                exit(1);
            } else {
                $messages[] = "Moblog: OK connection.\n";
            }
            $listing = $pop3->getListing();
        }

        $messages[] = count($listing)." email found on the server.";

        $regen = false;

        // Then we iterate through the list..
        foreach ($listing as $list_item) {

            if ($this->cfg['use_imap']) {
                $msg_id = $list_item['message_id'];
                $email = $list_item['rawdata'];
            } else {
                $msg_id = $list_item['msg_id'];
                $email = $pop3->getMsg( $msg_id );
            }
            $messages[] = "fetched mail $msg_id";

            if (!$this->cfg['leave_on_server']) {
                if ($this->cfg['use_imap']) {
                    $mail->delete($list_item['msgno']);
                } else {
                    $pop3->deleteMsg($msg_id);
                }
                $messages[] = "Message was deleted from the server..";
            } else {
                $messages[] = "Message was left on the server (if supported)..";
            }

            // Perhaps save a local copy..
            if($this->cfg['save_mail']) {
                $maildir = $PIVOTX['paths']['db_path'] . 'mail/';
                if (!file_exists($maildir)) {
                    makeDir($maildir);
                }
                $msg_id = str_replace(array('<','>'), '', $msg_id);
                $filename = $maildir . date("Ymd-His") . "-" . $msg_id . ".eml";
                if ($fp = fopen( $filename, "w" )) {
                    fwrite($fp, $email);
                    $messages[] = "Local copy saved as: $filename";
                    fclose($fp);
                } else {
                    $messages[] = "Alas! Woe is me! I couldn't save a local copy.";
                }

            }

            $this->entry = array();

            // Parse and post the email..
            $this->parse_email($email);

            $messages[] = $this->compose_entry();

            $regen = true;

        }


        if ($this->cfg['use_imap']) {
            $mail->close();
        } else {
            $pop3->disconnect();
        }

        if ($regen) {
            // Clear cache?
            // $messages[] = "Cleared cache after adding new moblog entry";
        }

        $messages[] = "Done!";

        return $messages;

    }

    /**
     * Parses the email.
     *
     */
    function parse_email( $email ) {
        global $entry;

        $mimedecode_params = array();

        $mimedecode_params['include_bodies'] = true;
        $mimedecode_params['decode_bodies']  = true;
        $mimedecode_params['decode_headers'] = true;

        $this->mimedecode_params = $mimedecode_params;

        $decode = new Mail_mimeDecode($email, "\r\n");
        $structure = $decode->decode($mimedecode_params);

        $this->moblog_print("<h1>Headers</h1>");

        $this->moblog_print("Subject: ". $structure->headers['subject']);
        $this->entry['title'] = $structure->headers['subject'];


        // We check to see if we can figure out the name of the provider that sent the email.
        $this->entry['carrier'] = "all"; // default value..
        $fields = $structure->headers['from'] . 
        $structure->headers['return-path'].
        $structure->headers['x-return-path'].
        $structure->headers['x-mms-message-id'] . 
        $structure->headers['x-mailer'] ;

        foreach ($this->cfg['known_carriers'] as $temp_carrier) {
            if (strpos($fields, $temp_carrier)>0) {
                $this->entry['carrier'] = $temp_carrier;
            }
        }

        $this->moblog_print("My carrier is: ".$this->entry['carrier']);

        if ($this->entry['carrier']=="all") {
            $this->moblog_print("Fields: $fields");
        }

        // get the replyaddress..
        if (isset($structure->headers['x-loop'])) {
            $replyaddress = "";
        } else {
            if (!empty($this->cfg['replyaddress'])) {
                $replyaddress = $structure->headers[$this->cfg['replyaddress']];
            } else {
                $replyto1 = $structure->headers['from'];
                $replyto2 = $structure->headers['return-path'];
                
                // Make sure the addresses aren't incorrectly parsed as 'Array'.
                if (is_array($replyto1)) { $replyto1 = implode(", ", $replyto1); }
                if (is_array($replyto2)) { $replyto2 = implode(", ", $replyto2); }                
                
                $replyaddress = (strlen($replyto2)>2) ? $replyto2 : $replyto1;
                $this->moblog_print("replyaddress: from) $replyto1 - return-path) $replyto2");
            }
        }

        $this->entry['replyaddress'] = preg_replace('/^<(.*)>$/U',"\\1",$replyaddress);

        // Handling encoded titles and replyaddress. 
        if (preg_match("/=\?(.*)\?B\?(.*)\?=/Ui", $this->entry['title'], $matches)) {
            $this->entry['title'] = str_replace($matches['0'], base64_decode($matches[2]), $this->entry['title']);
        } elseif (preg_match("/=\?(.*)\?Q\?(.*)\?=/Ui", $this->entry['title'], $matches)) {
            $this->entry['title'] = str_replace($matches['0'], quoted_printable_decode($matches[2]), $this->entry['title']);
        }
        if (preg_match("/=\?(.*)\?B\?(.*)\?=/Ui", $this->entry['replyaddress'], $matches)) {
            $this->entry['replyaddress'] = str_replace($matches['0'], base64_decode($matches[2]), $this->entry['replyaddress']);
        } elseif (preg_match("/=\?(.*)\?Q\?(.*)\?=/Ui", $this->entry['replyaddress'], $matches)) {
            $this->entry['replyaddress'] = str_replace($matches['0'], quoted_printable_decode($matches[2]), $this->entry['replyaddress']);
        }
        if (!i18n_is_utf8($this->entry['title'])) {
            $this->entry['title'] = utf8_encode($this->entry['title']);
        }
        if (!i18n_is_utf8($this->entry['replyaddress'])) {
            $this->entry['replyaddress'] = utf8_encode($this->entry['replyaddress']);
        }


        // for 'plain text' messages, parse the body.
        // parse_body($structure->body);
        $this->parse_body($structure);

        // for mime mail, parse each part
        if ((isset($structure->parts)) && (is_array($structure->parts))) {

            foreach ($structure->parts as $part) {
                $this->parse_parts($part);
            }

        }

    }

    /**
     * Parses the parts of an email.
     *
     */
    function parse_parts($part) {

        $temp_headers = array_merge( (array)$part->headers, (array)$part->ctype_parameters, (array)$part->d_parameters);

        $extension = strtolower(getExtension($temp_headers['filename']));

        $this->moblog_print("<hr /><b>part: ".$part->ctype_primary ."</b>" );

        if (strtolower($part->ctype_primary) == "text") {

            $this->parse_body($part);

        } else if (strtolower($part->ctype_primary) == "multipart") {

            foreach ($part->parts as $temp_part) {
                $this->parse_parts($temp_part);
            }

        } else if ((strtolower($part->ctype_primary) == "image")  || ($extension == "gif") || 
                ($extension == "jpg") || ($extension == "jpeg") || ($extension == "png")  ) {

            $this->parse_image($part);

        } else {

            $this->parse_download($part);

        } // end if ($filename ... )


    }

    /**
     * Composes an entry based on the email.
     *
     */
    function compose_entry() {
        global $PIVOTX;

        $entry = $this->entry;

        if ((strlen($entry['introduction'])>2) || (strlen($entry['body'])>2)) {

            // if so, save the new entry and generate files (if necessary)
            $entry['code']=">";
            $entry['date'] = $entry['publish_date'] = date('Y-m-d-H-i', getCurrentDate());

            if ( (!isset($entry['title'])) || ($entry['title']=="") ) {
                $entry['title'] = $this->cfg['title'];
            }

            if (!isset($entry['subtitle'])) {
                $entry['subtitle'] = '';
            }

            if ( (!isset($entry['status'])) || ($entry['status']=="") ) {
                $entry['status'] = $this->cfg['status'];
            }

            $entry['allow_comments'] = $this->cfg['allow_comments'];

            $entry['convert_lb'] = 0;

            if ( !isset($entry['user']) || ($entry['user']=="") || !$PIVOTX['users']->getUser($entry['user'])) {
                $entry['user'] = $this->cfg['author'];
            }

            //check for valid sender: $replyaddress must be in $this->cfg['allowed_senders']
            $allowed = false;
            if (strlen($entry['replyaddress'])>2) {
                $replyaddress = 'x'.strtolower($entry['replyaddress']);
                foreach ($this->cfg['allowed_senders'] as $sender) {
                    if (strpos($replyaddress, strtolower($sender)) > 0) {
                        $allowed = true;
                        break;
                    }
                }
            }

            if ($allowed) {
                $entry['category'] = array ($this->cfg['category']);
                if(isset($entry['override_cat'])) {
                    $entry['category'] = array ($entry['override_cat']);
                }
            } else {
                $entry['category'] = array ($this->cfg['spam_category']);
                $entry['status'] = 'hold';
            }

            $entry = $PIVOTX['db']->set_entry($entry);
            $PIVOTX['db']->save_entry(true);

            $msg = __('Your entry has been posted!') . "\n\n" . 
                sprintf("%s: %s\n%s: %s\n%s: %s\n%s: %s\n%s: %s",
                    __('User'), $entry['user'], 
                    __('Category'), implode(',', $entry['category']), 
                    __('Title'), $entry['title'], 
                    __('Subtitle'), $entry['subtitle'], 
                    __('Introduction'), $entry['introduction']
                );

            $msg_title = __('Moblog entry posted');

        } else {

            $msg = __("Not posted: Could not parse your entry.\n\nPlease report this in the PivotX forum");
            $msg_title = __('Moblog entry not posted');

        }

        $msg .= "\n\n" . sprintf(__("Processed: %s"), formatDate('', "%day%-%month%-'%ye% %hour24%:%minute%")) . "\n";

        // to wrap it up, send a confirmation by mail..
        if (( $entry['replyaddress'] != "") && ($this->cfg['send_confirmation'])) {
            $add_header = sprintf("From: %s", $entry['replyaddress']."\n");
            $add_header .= sprintf("X-loop: pivotx-moblog");
            if (!pivotxMail( $entry['replyaddress'], $msg_title, $msg, $add_header)) {
                debug("Failed to send moblog confirmation message");
            }
        }

        return $msg;

    }

    /**
     * Parses the body part of an email.
     *
     */
    function parse_body($part) {
        $entry = $this->entry;

        // Here we check the various 'skipcontent' rules, so we can easily skip mime parts we
        // don't need. (like gifs or ads that were added by the carrier)
        $temp_rules = array_merge( (array)$this->cfg['skipcontent']['all'], (array)$this->cfg['skipcontent'][$entry['carrier']] );
        $temp_headers = array_merge( (array)$part->headers, (array)$part->ctype_parameters, (array)$part->d_parameters);
        foreach ($temp_rules as $rule => $value) {
            if ((isset($temp_headers[$rule])) && ($temp_headers[$rule] == $value)) {
                $this->moblog_print("We skip this part because rule '$rule' == '$value'");
                return "";
            }
        }

        $this->moblog_print("Temp_headers:");
        $this->moblog_printr($temp_headers);

        if (is_string($part)) {
            // simple email body//
            $body = $part;
        } else {
            // multipart..
            $body = $part->body;
        }

        // Only decode if it hasn't been done by Mail_mimeDecode already (in 
        // function parse_email).
        if (!$this->mimedecode_params['decode_bodies']) {
            if (strtolower($temp_headers['content-transfer-encoding']) == "base64") {
                $body = base64_decode($body);
                $this->moblog_print("un-base-64");
            }

            if (strtolower($temp_headers['content-transfer-encoding']) == "quoted-printable") {
                $body = quoted_printable_decode($body);
                $this->moblog_print("un-quoted-printable");
            }
        }


        $body = preg_replace("/<style(.*)<\/style>/Usi", "", $body);
        $body = stripTagsAttributes($body, "*");

        $this->moblog_print("Original body is: ". ($body));

        // Convert body to UTF-8 if the email isn't using UTF-8 as charset.
        if (strtolower($part->ctype_parameters['charset']) != "utf-8") {
            $body = utf8_encode($body);
        }

        // We try to find out where the line containing the title is at...
        // Then we remove the complete line from the body. (We repeat the same 
        // trick for all vars wanted.)
        if (preg_match("/^title:(.*)/mi", $body, $title)) {
            $entry['title'] = trim($title[1]);
            $body = str_replace ($title[0], "", $body);
        }

        if (preg_match("/^subtitle:(.*)/mi", $body, $subtitle)) {
            $entry['subtitle'] = trim($subtitle[1]);
            $body = str_replace ($subtitle[0], "", $body);
        }

        if (preg_match("/^user:(.*)/mi", $body, $user)) {
            $entry['user'] = trim($user[1]);
            $body = str_replace ($user[0], "", $body);
        }

        if (preg_match("/^pass:(.*)/mi", $body, $pass)) {
            $entry['pass'] = trim($pass[1]);
            $body = str_replace ($pass[0], "", $body);
        } else if (preg_match("/^password:(.*)/mi", $body, $password)) {
            $entry['pass'] = trim($password[1]);
            $body = str_replace ($password[0], "", $body);
        }

        if (preg_match("/^publish:(.*)/mi", $body, $publish)) {
            if (trim($publish[1]) == "1") {
                $entry['status'] = 'publish';
            } else {
                $entry['status'] = 'hold';
            }
            $body = str_replace ($publish[0], "", $body);
        }

        if (preg_match("/^cat:(.*)/mi", $body, $cat)) {
            $entry['override_cat'] = trim($cat[1]);
            $body = str_replace ($cat[0], "", $body);
        } else if (preg_match("/^category:(.*)/mi", $body, $category)) {
            $entry['override_cat'] = trim($category[1]);
            $body = str_replace ($category[0], "", $body);
        }

        if (preg_match("/^introduction:(.*)/mi", $body, $introduction)) {
            $entry['introduction'] = trim($introduction[1]) . $entry['introduction'];
            @$body = str_replace ($introduction[0], "", $body);
        }

        if (preg_match("/^body:(.*)/mi", $body, $new_body)) {
            $entry['body'] = trim($new_body[1]);
        } else {
            // Body isn't specified explicitly - trying to guess the right thing

            // First strip off a standard signature, then tidying
            list($body, $sig) = explode("\n-- ", $body);
            $body = $this->tidy(nl2br(trim($this->tidy($body))));

            // We replace the $entry['introduction'] with the newly parsed $body. 
            if (strlen($body)>strlen($entry['introduction'])) {

                // unless it already contains a [[image]] or [[popup]].. In which case we append it..
                if( (strpos($entry['introduction'], "[popup")>0) || (strpos($entry['introduction'], "[image")>0) ) {
                    $entry['introduction'] .= $body;
                } else {
                    $entry['introduction'] = $body;	
                }
            }
        }

        $this->entry = $entry;
    }

    /**
     * Creates a image from a part of an email.
     *
     */
    function parse_image($part) {
        global $PIVOTX;

        $entry = $this->entry;

        // Here we check the various 'skipcontent' rules, so we can easily skip mime parts we
        // don't need. (like gifs or ads that were added by the carrier)
        $temp_rules = array_merge( (array)$this->cfg['skipcontent']['all'], (array)$this->cfg['skipcontent'][$entry['carrier']] );
        $temp_headers = array_merge( (array)$part->headers, (array)$part->ctype_parameters, (array)$part->d_parameters);
        foreach ($temp_rules as $rule => $value) {
            if (isset($temp_headers[$rule])) {
                if (is_array($value)) {
                    if (in_array($temp_headers[$rule], $value)) {
                        $this->moblog_print("We skip this part because rule '$rule'");
                        return "";
                    }
                } else {
                    if ($temp_headers[$rule] == $value) {
                        $this->moblog_print("We skip this part because rule '$rule' == '$value' ");
                        return "";
                    }
                }
            }
        }

        $this->moblog_print("Temp_headers:");
        $this->moblog_printr($temp_headers);

        // It's an image. We'll add all the images as an array to the entry..
        // get the original filename from the email..
        $filename = isset($part->ctype_parameters['name']) ? $part->ctype_parameters['name'] : $part->d_parameters['filename'];
        $filename = strtolower(safeString($filename, false));

        $ext = getExtension($filename);

        if ( ($filename !="") && ( ($ext=="jpg") || ($ext=="jpeg") ||  ($ext=="gif") ||  ($ext=="png") )) {

            $filename = safeString($filename);
            $filename = str_replace(' ', '_', $filename);

            if ($ext=="jpeg") {
                $filename=str_replace(array('.jpeg','.JPEG'), '.jpg', $filename);
                $ext = "jpg";
            }

            $absfilename = $PIVOTX['paths']['upload_base_path'].$filename;    
            if (file_exists($absfilename)) {
                $this->moblog_print("File $filename (.$ext) exists..");
                $filename = str_replace(array('.'.$ext, '.'.strtoupper($ext)), '', $filename);
                $filename = substr($filename, 0, 7)."_".date("Ymd-his").".".$ext;
                $absfilename = $PIVOTX['paths']['upload_base_path'].$filename;    
            }

            $this->moblog_print("Write out as $absfilename");

            $fp = fopen($absfilename, "wb");
            fwrite($fp, $part->body);
            fclose($fp);

            list ($mywidth, $myheight) = getimagesize($absfilename);

            if ( ($mywidth=="") && ($mywidth=="") ) {
                // Some mailers like pine, need content to get base64_decode'd
                $fp = fopen($absfilename, "wb");
                fwrite($fp, base64_decode($part->body));
                fclose($fp);
                list ($mywidth, $myheight) = getimagesize($absfilename);
            }

            if ( ($mywidth > $this->cfg['maxwidth']) || ($myheight > $this->cfg['maxheight'])) {

                if ($this->cfg['skip_thumbnail']) {
                    $thumbfile = "";
                } else {
                    $thumbfile = $this->resize_image($absfilename, $this->cfg['maxwidth'], $this->cfg['maxheight']);
                }

                if (strlen($entry['introduction'])>2) {
                    $entry['introduction'] .="\n";
                }

                if (strlen($thumbfile)>2) {
                    $entry['introduction'] .="\n[[popup file=\"$filename\" description=\"(thumbnail)\" ]]\n";
                } else {
                    $entry['introduction'] .="\n[[popup file=\"$filename\" description=\"" . $this->cfg['click_for_image'] . "\" ]]\n";
                }

            } else {

                $entry['introduction'] .= "\n[[image file=\"$filename\" ]]";

            }

        }

        $this->entry = $entry;

    }


    /**
     * Creates a download from a part of an email.
     *
     */
    function parse_download($part) {
        global $PIVOTX;

        $temp_headers = array_merge( (array)$part->headers, (array)$part->ctype_parameters, (array)$part->d_parameters);

        $this->moblog_print("Temp_headers:");
        $this->moblog_printr($temp_headers);


        $filename = $temp_headers['filename'];
        $ext = getExtension($filename);

        // Skip .smil files.
        if ($ext == "smil") {
            return;
        }

        $body = $part->body;

        // [[download:another.zip:icon:Download a zipfile:]]
        $this->moblog_print("filename: ". $filename ." . $ext ");
        $this->moblog_print("filesize: ". strlen($part->body));

        if (strlen($this->entry['introduction'])>2) {
            $this->entry['introduction'] .="\n&nbsp;\n";
        }

        /*
         if (strtolower($temp_headers['content-transfer-encoding']) == "base64") {
             $body = base64_decode($body);
             $this->moblog_print("un-base-64");
         }
         */

        $fp = fopen($PIVOTX['paths']['upload_base_path'].$filename, "wb");
        fwrite($fp, $body);
        fclose($fp);

        $this->entry['introduction'] .="[[download file=\"$filename\" text=\"$filename\" ]]";


    }


    function tidy($text) {

        foreach($this->cfg['skipcontent'][ $this->entry['carrier'] ]['body'] as $skip) {
            $text = str_replace($skip, "", $text);
        }

        // Be sure to remove phone-numbers
        $text = preg_replace('/((\\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}/', "", $text);

        // Trim all leading and trailing whitespace from lines..
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);


        $text = str_replace("&nbsp;<br />", "", $text);
        $text = preg_replace("/([\n\r\t])+/is", "\n", $text);


        return ($text);
    }



    function resize_image($imagename) {

        $ext = getExtension($imagename);

        $thumbname = str_replace( $ext, "thumb.".$ext, $imagename);

        // echo "imagename = $imagename";

        $filename = ( $imagename );
        $thumbfilename = ( $thumbname );

        list($curwidth, $curheight) = getimagesize($filename);

        if ( ($curwidth>1701) || ($curheight>1701) || (!function_exists('ImageCreateFromJPEG')) ) {
            $this->moblog_print("file to big to make thumbnail: $curwidth x $curheight.");
            return "";
        }

        $factor = min( ($this->cfg['maxwidth'] / $curwidth) , ($this->cfg['maxheight'] / $curheight) );

        $dw		= $curwidth * $factor;
        $dh		= $curheight *  $factor;

        $ext = strtolower($ext);

        if ($ext == "jpg") { $src = ImageCreateFromJPEG($filename); }
        if ($ext == "png") { $src = ImageCreateFromPNG($filename); }

        if(function_exists('ImageCreateTrueColor')) {
            $dst = ImageCreateTrueColor($dw,$dh);
        } else {
            $dst = ImageCreate($dw,$dh);
        }

        ImageCopyResampled($dst,$src,0,0,0,0,$dw,$dh,$curwidth,$curheight);

        if($ext == "jpg") ImageJPEG($dst, $thumbfilename, $this->cfg['quality']);
        if($ext == "png") ImagePNG($dst, $thumbfilename, $this->cfg['quality']);

        ImageDestroy($dst);

        $this->moblog_print("thumbfilename: $thumbfilename");

        return $thumbfilename;
    }



    function moblog_print($str) {
        if ($this->cfg['verbose']) {
            echo htmlspecialchars($str)."<br />\n";
        }
    }



    function moblog_printr(&$var) {
        if ($this->cfg['verbose']) {
            ob_start();
            print_r($var);
            $output = '<pre>'.htmlspecialchars(ob_get_contents()).'</pre>';
            ob_end_clean();
            echo $output;
        }
    }
}

?>
