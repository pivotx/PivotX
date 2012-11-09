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
require dirname(dirname(__FILE__))."/lamer_protection.php";

/**
 * The class that handles the visitor registration.
 *
 */
class Visitors {

    var $input;
    var $user;

    /**
     * Initialise the Visitors object.
     */
    function Visitors() {
        global $PIVOTX;

        // Clean up user input to avoid HTML injection and/or stored XSS.
        // Also using strip_tags since none of the fields should contain HTML.
        $input = array_merge($_POST, $_GET);
        foreach ($input as $key => $value) {
            $input[$key] = htmlspecialchars(strip_tags(trim($value)));
        }

        $this->input = $input;
        $this->input['message'] = '';

        // Make sure the db/users/ folder is present.
        makeDir($PIVOTX['paths']['db_path'].'users/');

    }

    function getPage() {

        $private_functions = array('options','edit_prefs','subm_prefs','del_user');
        $func = '';

        if ($user = $this->isLoggedIn()) {
            $this->user = $user;
            if (!isset($this->input['func']) || empty($this->input['func'])) {
                $func = "options";
            } else {
                $func = $this->input['func'];
            }
        } else {
            if (!in_array($this->input['func'],$private_functions)) {
                $func = $this->input['func'];
            } else {
                debug('Tried to access private function when not being logged in.');
            }
        }

        // FIXME / TODO - what if cookies are disabled?

        switch ($func) {
            case 'login':
                $page = $this->login();
                break;
            case 'options':
                $page = $this->showOptions();
                break;
            case 'logout':
                $page = $this->logout();
                break;
            case 'regUser':
                $page = $this->showRegPage();
                break;
            case 'del_user':
                $this->delUser();
                $page = $this->showLogin();
                break;
            case 'send_pass':
                $page = $this->sendPass();
                break;
            case 'reset_passwd':
                $page = $this->resetPasswd();
                break;
            case 'edit_prefs':
                $page = $this->editPrefs();
                break;
            case 'subm_reg':
                $page = $this->submitReg();
                break;
            case 'subm_prefs':
                $page = $this->submitPrefs();
                break;
            case 'verify':
                $page = $this->verify();
                break;
            default:
                $page = $this->showLogin();
                break;
        }

        return $page;
    }

    function showLogin() {

        $link_login = makeVisitorPageLink('login');
        $link_reset_passwd = str_replace('login','reset_passwd',$link_login);
        $link_regUser = str_replace('login','regUser',$link_login);

        $form = <<<EOM
        <h2>%header%</h2>
        <p><b>%message%</b></p>
        <form name="form1" id="form1" method="post" action="$link_login">
        <table border="0" cellspacing="0" cellpadding="3">
        <tr>
        <td>%formname%:</td>
        <td><input type="text" name="name" /></td>
        </tr>
        <tr>
        <td>%formpass%:</td>
        <td><input type="password" name="pass" /></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="%login%" /></td>
        </tr>
        <tr>
        <td colspan="2"><p><br />&raquo; <a href="$link_reset_passwd">%pass_forgot%</a></p>
        <p>&raquo; <a href="$link_regUser">%register_new%</a></p></td>
        </tr>
        </table>
        </form>

EOM;

        foreach ($this->input as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $trans = array();
        $trans['header'] = __('Log in as a registered visitor');
        $trans['formname'] = __('Username');
        $trans['formpass'] = __('Password');
        $trans['login'] = __('Login');
        $trans['pass_forgot'] = __('Forgotten your password?');
        $trans['register_new'] = __('Register a new username.');
        foreach ($trans as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $form .= $this->weblogLinks();

        return $form;

    }


    function showOptions() {
        global $PIVOTX;

        $link_logout = makeVisitorPageLink('logout');
        $link_edit_prefs = str_replace('logout','edit_prefs',$link_logout);
        $link_del_user = str_replace('logout','del_user',$link_logout);

        $form = <<<EOM
        <h2>%loggedinas% %name%</h2>
        <p>%message%</p>
        <p>&raquo; <a href="$link_edit_prefs">%pref_edit%</a></p>
        <p>&raquo; <a href="$link_logout">%logout%</a></p>
        <p>&raquo; <a href="$link_del_user">%del_user%</a></p>
EOM;

        foreach ($this->input as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        if (is_array($this->user)) {
            foreach ($this->user as $key => $val) {
                $form = str_replace("%".$key."%", $val, $form);
            }
        }
        $trans = array();
        $trans['loggedinas'] = __('Logged in as');
        $trans['logout'] = __('Log out.');
        $trans['del_user'] = __('Delete account.');
        $trans['pref_edit'] = __('Edit your preferences');
        foreach ($trans as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $form .= $this->weblogLinks();

        return $form;

    }



    function login() {
        global $PIVOTX;

        $logged_in = false;

        if ($user=$this->loadUser($this->input['name'])) {

            if ($user['pass'] == $this->input['pass']) {
                $logged_in = true;
                // This is an old Pivot user which isn't using the new hashed passwords
                // so we create a new salt, and set the hashed/salted password.
                $salt = md5(rand(1,999999) . time());
                $md5_pass = md5($user['pass'] . $salt);
                $user['salt'] = $salt;
                $user['pass'] = $md5_pass;
            } else if ( (md5($this->input['pass'] . $user['salt']) == $user['pass']) && 
                    ($user['verified']==1) && ($user['disabled']!=1)) {
                $logged_in = true;
            } else if ($user['disabled']==1) {
                $this->input['message'] = __('User disabled');
                $text = $this->showLogin();
            } else {
                $this->input['message'] = __('Incorrect password or username');
                $text = $this->showLogin();
            }

        } else {
            $this->input['message'] = __('Incorrect password or username');
            $text = $this->showLogin();
        }

        if ($logged_in) {
            $PIVOTX['session']->setCookie('piv_reguser', stripslashes($user['name'].'|'.md5($user['pass'])));
            $user['last_login'] = date("Y-m-d");
            $this->saveUser($user);
            $this->input['message'] = __('Logged in');
            $text = $this->showOptions();
        }
 
        return $text;

    }





    function showRegPage() {

        $link = makeVisitorPageLink();
        $link_subm_reg = makeVisitorPageLink('subm_reg');

        $form= <<<EOM

        <h2>%register%: </h2>
        <p><b>%message%</b></p>
        <form name="form1" id="form1" method="post" action="$link_subm_reg">
        <p>%register_info%</p>
        <table border="0" cellspacing="0" cellpadding="3">
        <tr>
        <td>%formname%:</td>
        <td colspan="4"><input name="name" type="text" id="name" value="%name%" /></td>
        </tr>
        <tr>
        <td>%formemail%:</td>
        <td colspan="4"><input name="email" type="text" id="email" value="%email%" /></td>
        </tr>
        <tr>
        <td>%formurl%:</td>
        <td colspan="4"><input name="url" type="text" id="url" value="%url%" /></td>
        </tr>
        <tr>
        <td>%formpass1%:</td>
        <td colspan="4"><input name="pass" type="password" id="pass" /></td>
        </tr>
        <tr>
        <td valign="top">%formpass2%:</td>
        <td colspan="4"><input name="pass2" type="password" id="pass2" />
        </td>
        </tr>
        <tr>
        <td colspan="5">



        <strong>%options%:</strong></td>
        </tr>
        <tr>
        <td>%show_email%: </td>
        <td><input name="show_address" type="radio" value="1" %radio1a% /> %Yes%</td>

        <td><input name="show_address" type="radio" value="0" %radio1b% /> %No%</td>
        <td></td><td></td>
        </tr>
        <tr>
        <td>%notify%: </td>
        <td><input name="notify_entries" type="radio" value="1" %radio2a% /> %Yes%</td>

        <td><input name="notify_entries" type="radio" value="0" %radio2b% /> %No%</td>
        <td></td><td></td>
        </tr>
        <tr>
        <td>%def_notify%:</td>
        <td><input name="notify_default" type="radio" value="1" %radio3a% /> %Yes%</td>

        <td><input name="notify_default" type="radio" value="0" %radio3b% /> %No%</td>
        <td></td><td></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td colspan="4"><br />
        <input type="submit" name="Submit" value="%register%" /></td></tr>
        <tr>
        <td colspan="5"><p><br />
        &laquo; <a href="$link">%back_login%</a></p>
        </td>
        </tr>
        </table>
        </form>
EOM;

        $input = $this->input;

        if (!isset($input['name'])) { $input['name'] = ""; }
        if (!isset($input['email'])) { $input['email'] = ""; }
        if (!isset($input['url'])) { $input['url'] = ""; }

        if ($input['show_address']==1) {
            $input['radio1a'] = "checked='checked' ";
            $input['radio1b'] = "";
        } else {
            $input['radio1a'] = "";
            $input['radio1b'] = "checked='checked' ";
        }

        if ($input['notify_entries']==1) {
            $input['radio2a'] = "checked='checked' ";
            $input['radio2b'] = "";
        } else {
            $input['radio2a'] = "";
            $input['radio2b'] = "checked='checked' ";
        }

        if ($input['notify_default']==1) {
            $input['radio3a'] = "checked='checked' ";
            $input['radio3b'] = "";
        } else {
            $input['radio3a'] = "";
            $input['radio3b'] = "checked='checked' ";
        }

        foreach ($input as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $trans = array();
        $trans['register'] = __('Register');
        $trans['register_info'] = __('Please fill out the following information. <strong>Be sure to give a valid email address</strong>, because we will send a verification email to that address.');
        $trans['Yes'] = __('Yes');
        $trans['No'] = __('No');
        $trans['formname'] = __('Username');
        $trans['formemail'] = __('Email');
        $trans['formurl'] = __('URL');
        $trans['formpass1'] = __('Password');
        $trans['formpass2'] = __('Password (confirm)');
        $trans['options'] = __('Options');
        $trans['back_login'] = __('Back to login');
        $trans['show_email'] = __('Show my email address with comments');
        $trans['notify'] = __('Notify me via email of new entries');
        $trans['def_notify'] = __('Default notification of replies');
        $trans['back_login'] = __('Back to login');
        foreach ($trans as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        return $form;
    }





    function editPrefs() {

        $link = makeVisitorPageLink();
        $link_subm_prefs = makeVisitorPageLink('subm_prefs');

        $form= <<<EOM

        <h2>%pref_edit%: </h2>
        <p><b>%message%</b></p>
        <form name="form1" id="form1" method="post" action="$link_subm_prefs">
        <p>%change_info%</p>
        <table border="0" cellspacing="0" cellpadding="3">
        <tr>
        <td>%formname%:</td>
        <td colspan="4"><input name="name" type="text" id="name" value="%name%" readonly="readonly" /></td>
        </tr>
        <tr>
        <td>%formemail%:</td>
        <td colspan="4"><input name="email" type="text" id="email" value="%email%" /></td>
        </tr>
        <tr>
        <td>%formurl%:</td>
        <td colspan="4"><input name="url" type="text" id="url" value="%url%" /></td>
        </tr>
        <tr>
        <td>%formpass1%:</td>
        <td colspan="4"><input name="ch_pass" type="password" id="ch_pass" value="" /></td>
        </tr>

        <tr>
        <td>%formpass2%:</td>
        <td colspan="4"><input name="ch_pass2" type="password" id="ch_pass2" value="" /></td>
        </tr>
        <tr>
        <td colspan="5"><strong>%options%:</strong></td>
        </tr>
        <tr>
        <td>%show_email%: </td>
        <td><input name="show_address" type="radio" value="1" %radio1a% /></td>
        <td>%Yes%</td>
        <td><input name="show_address" type="radio" value="0" %radio1b% /></td>
        <td> %No% </td>
        </tr>
        <tr>
        <td>%notify%:</td>
        <td><input name="notify_entries" type="radio" value="1" %radio2a% /></td>
        <td>%Yes%</td>
        <td><input name="notify_entries" type="radio" value="0" %radio2b% /></td>
        <td>%No%</td>
        </tr>
        <tr>
        <td>%def_notify%:</td>
        <td><input name="notify_default" type="radio" value="1" %radio3a% /></td>
        <td>%Yes%</td>
        <td><input name="notify_default" type="radio" value="0" %radio3b% /></td>
        <td>%No%</td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td colspan="4"><br />
        <input type="submit" name="Submit" value="%pref_change%" /></td></tr>
        <tr>
        <td colspan="5"><p><br />
        &laquo; <a href="$link">%back_login%</a></p>
        </td>
        </tr>
        </table>
        </form>
EOM;

        $user = $this->user;

        $user['message'] = $this->input['message'];
        $user['pass'] = "";

        if ($user['show_address']==1) {
            $user['radio1a'] = "checked='checked' ";
            $user['radio1b'] = "";
        } else {
            $user['radio1a'] = "";
            $user['radio1b'] = "checked='checked' ";
        }

        if ($user['notify_entries']==1) {
            $user['radio2a'] = "checked='checked' ";
            $user['radio2b'] = "";
        } else {
            $user['radio2a'] = "";
            $user['radio2b'] = "checked='checked' ";
        }

        if ($user['notify_default']==1) {
            $user['radio3a'] = "checked='checked' ";
            $user['radio3b'] = "";
        } else {
            $user['radio3a'] = "";
            $user['radio3b'] = "checked='checked' ";
        }

        foreach ($user as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $trans = array();
        $trans['Yes'] = __('Yes');
        $trans['No'] = __('No');
        $trans['change_info'] = __('Here you can change your information.');
        $trans['formname'] = __('Username');
        $trans['formemail'] = __('Email');
        $trans['formurl'] = __('URL');
        $trans['formpass1'] = __('Password');
        $trans['formpass2'] = __('Password (confirm)');
        $trans['options'] = __('Options');
        $trans['show_email'] = __('Show my email address with comments');
        $trans['notify'] = __('Notify me via email of new entries');
        $trans['def_notify'] = __('Default notification of replies');
        $trans['pref_edit'] = __('Edit your preferences');
        $trans['pref_change'] = __('Change preferences');
        $trans['back_login'] = __('Back to login');
        foreach ($trans as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        return $form;
    }




    function resetPasswd() {

        if (isset($this->input['id'])) {
            $user = $this->loadUser($this->input['name']);
            if ($user && ($this->input['id'] == $user['reset_id'])) {
                $message = __('The new password is <q>%pass%</q>.');
                $this->input['message'] = str_replace('%pass%',$user['pass_reset'],$message);
                $user['salt'] = md5(rand(1,999999) . time());
                $user['pass'] = md5($user['pass_reset'] . $user['salt']);
                unset($user['pass_reset']);
                unset($user['reset_id']);
                $this->saveUser($user);
            } else {
                $this->input['message'] = __('Oops') . ' - ' . 
                    __('Password reset request failed.');
            }
            return $this->showLogin();
        } 

        $link = makeVisitorPageLink();
        $link_send_pass = makeVisitorPageLink('send_pass');
        $link_regUser = str_replace('send_pass','regUser',$link_send_pass);

        $form = <<<EOM
        <h2>%pass_reset%</h2>
        <p>%message%</p>
        <form name="form1" id="form1" method="post" action="$link_send_pass">
        <p>%pass_reset_desc%</p>
        <table border="0" cellspacing="0" cellpadding="3">
        <tr>
        <td>%name%:</td>
        <td><input name="name" type="text" id="name" /></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="%pass_send%" /></td>
        </tr>
        <tr>
        <td colspan="2"><p><br />&laquo; <a href="$link">%back_login%</a></p>
        <p>&raquo; <a href="$link_regUser">%register_new%</a></p></td>
        </tr>
        </table>
        </form>

EOM;

        foreach ($this->input as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        $trans = array();
        $trans['name'] = __('Username');
        $trans['pass_send'] = __('Send password');
        $trans['pass_reset'] = __('Reset password');
        $trans['pass_reset_desc'] = __('If you\'ve forgotten your password, enter your username and PivotX will send you an email with a link to reset your password.');
        $trans['back_login'] = __('Back to login');
        $trans['register_new'] = __('Register a new username.');
        foreach ($trans as $key => $val) {
            $form = str_replace("%".$key."%", $val, $form);
        }

        return $form;
    }



    function submitReg() {

        $input = $this->input;

        if ($this->isUser($input['name'])) {
            $this->input['message'] .= __('User already exists... Please pick another name.');
            $text = $this->showRegPage();
        } else if ($this->emailTaken($input['email'])) {
            $this->input['message'] .= __('Email address is already taken... Please use another address.');
            $text = $this->showRegPage();
        } else if (strlen($input['pass'])<4) {
            $this->input['message'] .= __('Password must be at least 4 letters long.');
            $text = $this->showRegPage();
        } else if ($input['pass'] != $input['pass2']) {
            $this->input['message'] .= __('Passwords do not match');
            $text = $this->showRegPage();
        } else if (!isEmail($input['email'])) {
            $this->input['message'] .= __('You must give your email address, since without it\'ll ' . 
                'be impossible to verify your account. You can always choose not to show ' . 
                'your address to other visitors.');
            $text = $this->showRegPage();
        } else {

            // Create a new salt, and set the salted password.
            $salt = md5(rand(1,999999) . time());
            $md5_pass = md5($input['pass'] . $salt);

            $user = array(
                'name' => $input['name'],
                'email' => $input['email'],
                'url' => $input['url'],
                'salt' => $salt,
                'pass' => $md5_pass,
                'show_address' => $input['show_address'],
                'notify_entries' => $input['notify_entries'],
                'notify_default' => $input['notify_default'],
                'verified' => 0,
            );

            $this->regUser($user);

            $text = $this->showOptions();
        }

        return $text;
    }



    function submitPrefs() {
        global $PIVOTX;

        $user = $this->user;
        $input = $this->input;

        if ((strlen($input['pass'])>0) && (strlen($input['pass'])<4)) {
            $this->input['message'] .= __('Password must be at least 4 letters long.');
            $text = $this->editPrefs();
        } else if ($input['ch_pass'] != $input['ch_pass2']) {
            $this->input['message'] .= __('Passwords do not match');
            $text = $this->editPrefs();
        } else {

            $this->input['message'] = __('The changes have been stored');

            if ( (strlen($input['ch_pass'])>3) && ($input['ch_pass'] == $input['ch_pass2']) ) {
                $user['pass'] = md5($input['ch_pass'] . $user['salt']);
                $PIVOTX['session']->setCookie("piv_reguser", stripslashes($user['name']."|".md5($user['pass'])));
            }

            $user['email'] = $input['email'];
            $user['url'] = $input['url'];
            $user['show_address'] = $input['show_address'];
            $user['notify_entries']  = $input['notify_entries'];
            $user['notify_default']  = $input['notify_default'];

            unset($user['message']);
            $this->saveUser($user);

            $text = $this->showOptions();

        }

        return $text;
    }


    function verify() {

        if ($user=$this->loadUser($this->input['name'])) {

            $verify_code = md5($user['pass']."email");

            if ($verify_code == $this->input['code']) {

                $user['verified'] = 1;
                $this->saveUser($user);

                $this->input['message'] = __('Your account is verified. Please log in..');
                sendMailNotification('visitor_registration', array('verify',$user['name']));
                $text = $this->showLogin();
            } else {
                $text = __('That code seems to be incorrect. I\'m sorry, but I can\'t verify.');
            }

        } else {
            $text = __('Oops');
        }

        return $text;

    }


    function sendPass() {
        global $PIVOTX;

        if ($user=$this->loadUser($this->input['name'])) {

            if ($user['name'] == $this->input['name'])  {

                $user['reset_id'] = md5($PIVOTX['config']->get('server_spam_key') . $user['pass']);
                $user['pass_reset'] = randomString(10);;
                $this->saveUser($user);

                $link = $PIVOTX['paths']['host'] . makeVisitorPageLink('reset_passwd') .
                    '&amp;name=' . urlencode($user['name']) . '&amp;id=' . $user['reset_id'];
                mailResetPasswordLink(array(
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'reset_id' => $user['reset_id'],
                    'link' => $link)
                );

            }

        }

        // Posting this message even if an invalid username is given so 
        // crackers can't enumerate usernames.
        $this->input['message'] = __('A link to reset your password was sent to your mailbox.');
        $text = $this->showLogin();

        return $text;

    }


    function delUser($name = '') {
        global $PIVOTX;

        $deleted = true;
        // If a name is supplied the function is not executed by a logged in visitor.
        if ($name == '') { 
            $PIVOTX['session']->setCookie("piv_reguser", "", time()-1000);
            $name_md5 = strtolower(md5(strtolower($this->user['name'])));
        } else {
            $name_md5 = strtolower(md5(strtolower($name)));
        }
        $filename = $PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php';
        if (file_exists($filename)) {
            unlink($filename);
            unset($this->user);
            $this->input['message'] = __('Account deleted.');
        } else {
            $this->input['message'] = __('Oops');
            $deleted = false;
        }

        return $deleted;
    }


    function logout() {
        global $PIVOTX;

        $PIVOTX['session']->setCookie("piv_reguser", "", time()-1000);
        unset ($this->user);
        $this->input['message'] = __('Logged out');
        return $this->showLogin();
    }


    function weblogLinks() {
        global $PIVOTX;

        if (isset($_GET['w']) && $PIVOTX['weblogs']->isWeblog($_GET['w'])) {
            $weblogkey = $_GET['w'];
            $our_weblogs = array($weblogkey => $PIVOTX['weblogs']->getWeblog($weblogkey));
        } else {
            $our_weblogs = $PIVOTX['weblogs']->getWeblogs();
        }

        $text = "<br />\n";

        foreach ($our_weblogs as $weblogkey => $weblog) {
            $text .= sprintf("<p>&laquo; ".__('Back to')." <a href=\"%s\">%s</a></p>", 
                $weblog['link'], $weblog['name'] );
        }

        return $text;
    }


    function emailTaken($email) {
        global $PIVOTX;

        $found = false;
        foreach($this->getUsers() as $user) {
            if ($user['email'] == $email) {
                $found = true;
                break;
            }
        }
        return $found;
    }


    function getUsers() {
        global $PIVOTX;

        $users = array();
        $d = dir( $PIVOTX['paths']['db_path'].'users/');
        while(false !== ($entry = $d->read())) {
            $file = $PIVOTX['paths']['db_path'].'users/' . $entry;
            if (is_file($file) && (getExtension($file) == "php") && 
                    ($user = loadSerialize($file,true))) {
                $users[urlencode($user['name'])] = $user;
            }
        }

        ksort($users);

        return $users;

    }



    function isUser($name) {
        global $PIVOTX;

        $name_md5 = strtolower(md5(strtolower($name))); 

        if (file_exists($PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php')) {
            return TRUE;    
        } else {
            return FALSE;
        }

    }


    function regUser($user) {
        global $PIVOTX;

        $name_md5 = strtolower(md5(strtolower($user['name']))); 

        if (saveSerialize($PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php', $user)) { 
            $text = sprintf("<h2>%s</h2>\n\n", __('User stored!'));  
        } else {    
            $text = sprintf("<h2>%s</h2>\n\n", __('Could not store new user!!'));
        }

        $mail1 = __("You have registered as a user on PivotX \"%s\" \n\n");
        $mail2 = __("To verify your account, click the following link:\n%s\n\n");;
        $url = sprintf("%s&amp;name=%s&amp;code=%s", $PIVOTX['paths']['host'] . makeVisitorPageLink('verify'), 
            urlencode($user['name']), md5($user['pass']."email"));

        $mail = sprintf($mail1.$mail2, $PIVOTX['config']->get('sitename'), str_replace('&amp;', '&' , $url) );
        if (!pivotxMail($user['email'], __('Registration confirmation'), $mail)) {
            $mail2 = '<a href="%s">'.__('Verify your account').'</a>';
            $mail = sprintf($mail1.$mail2, $PIVOTX['config']->get('sitename'), $url );
            $text = "\n<br />". nl2br($mail) ."<br />\n";
        } else {
            $text = sprintf(__('Mail verification sent to %s. ' . 
                'Please check your email in a minute to confirm your account.'), $user['email']);
        }

        $this->input['message'] = $text;
        sendMailNotification('visitor_registration', array('add',$user['name']));
    }

    function loadUser($name) {
        global $PIVOTX;
        // Abort immediately if empty name given
        if ($name == "") {
            return FALSE;
        }

        if (strpos($name, "|")>0) {
            list($name) = explode("|", $name);
        }

        $name_md5 = strtolower(md5(strtolower($name))); 

        if ($this->isUser($name)) {
            $user = loadSerialize($PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php',true);
            return $user;
        } else {
            return FALSE;
        }
    }

    /**
     * Returns an array with user info if a registered visitor is logged in, 
     * else false.
     */
    function isLoggedIn() { 
        global $PIVOTX;

        if (isset($_COOKIE['piv_reguser'])) {
            list($name, $hash) = explode("|", $_COOKIE['piv_reguser']);
            $name_md5 = strtolower(md5(strtolower($name))); 
            if ($this->isUser($name)) {
                $user = loadSerialize($PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php',true);
                if (md5($user['pass']) == $hash) {
                    return $user;
                }
            }
        }

        return false;

    }

    function saveUser($user) {
        global $PIVOTX;

        $name_md5 = strtolower(md5(strtolower($user['name']))); 

        if (saveSerialize($PIVOTX['paths']['db_path'].'users/'.$name_md5.'.php', $user)) {

            // echo "User stored!<br /><br />";

        } else {

            echo "Could not store user!!<br /><br />";  
        }

    }
}

?>
