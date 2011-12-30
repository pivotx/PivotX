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


/**
 * Display the login screen.
 *
 * This screen should also check if PivotX is set up correctly. If it isn't, the
 * user will be redirected to the Troubleshooting or Setup screen.
 *
 */
function pageLogin($template="normal") {
    global $PIVOTX;

    if (!isInstalled()) {
        pageSetupUser();
        die();
    }

    $PIVOTX['template']->assign('title', __("Login"));
    $PIVOTX['template']->assign('heading', __("Login"));

    $template = getDefault($_POST['template'], $template);

    if (isMobile()) {
        $template="mobile";
    }

    $form = getLoginForm($template);

    // If a 'return to' is set, pass it onto the template, but only the 'path' and 'query'
    // part. This means that we do NOT allow redirects to another domain!!
    $returnto = getDefault($_GET['returnto'], $_POST['returnto']);
    if (!empty($returnto)) {
        $returnto = parse_url($returnto);
        $returnto_link = $returnto['path'];
        if (!empty($returnto['query'])) {
            $returnto_link .= '?' . $returnto['query'];
        }
        $form->setvalue('returnto', $returnto_link );
    }

    // Get the validation result
    $result = $form->validate();
    $extraval = array();

    if ( $result != FORM_OK ) {
        if (isset($_GET['resetpassword']) && isset($_GET['username']) && isset($_GET['id'])) {
            $form->setvalue('username', $_GET['username']);
            $user = $PIVOTX['users']->getUser($_GET['username']);
            if ($user && !empty($user['reset_id']) && ($_GET['id'] == $user['reset_id'])) {
                $extraval['pass1'] = randomString(8, true);
                $extraval['reset_id'] = '';

                $pass = "'<strong>".$extraval['pass1']."</strong>'";
                $message = "<p>". __('Your new password is %pass%.') ."</p>";
                $message = str_replace('%pass%', $pass, $message);
                $PIVOTX['messages']->addMessage($message);
                $html = $message;
                $PIVOTX['users']->updateUser($user['username'],$extraval);
                $PIVOTX['events']->add('password_reset', "", $user['username']);
            } else {
                $PIVOTX['messages']->addMessage(__('Oops') . ' - ' .
                __('Password reset request failed.'));
                debug('Password reset request failed - wrong id.');
            }
        }

        $PIVOTX['template']->assign("html", $html);
        $PIVOTX['template']->assign("form", $form->fetch(true));

    } else {

        $val = $form->getvalues();

        if ($val['resetpassword'] == 1) {

            $can_send_mail = true;
           
            $user = $PIVOTX['users']->getUser($val['username']);
            if ($user) {
                $extraval['reset_id'] = md5($PIVOTX['config']->get('server_spam_key') . $user['password']);
                $PIVOTX['users']->updateUser($user['username'], $extraval);
                $link = $PIVOTX['paths']['host'] . makeAdminPageLink('login') .
                    '&resetpassword&username=' . urlencode($user['username']) . '&id=' . $extraval['reset_id'];
                $can_send_mail = mailResetPasswordLink(array(
                    'name' => $user['username'],
                    'email' => $user['email'],
                    'reset_id' => $extraval['reset_id'],
                    'link' => $link)
                );
            }

            if ($can_send_mail) {
                // Posting this message even if an invalid username is given so
                // crackers can't enumerate usernames.
                $PIVOTX['messages']->addMessage(__('A link to reset your password was sent to your mailbox.'));
            } else {
                $PIVOTX['messages']->addMessage(__('PivotX was not able to send a mail with the reset link.'));
            }

            $PIVOTX['events']->add('request_password', "", $user['username']);

            $PIVOTX['template']->assign("form", $form->fetch(true));

        } elseif ($PIVOTX['session']->login($val['username'], $val['password'], $val['stayloggedin'])) {

            // User successfully logged in... set language and go to Dashboard or 'returnto'
            $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
            $PIVOTX['languages']->switchLanguage($currentuser['language']);

            if (!empty($returnto_link)) {
                header("Location: " . $returnto_link );
                die();
            } else {
                if ($template=="normal") {
                    pageDashboard();
                } else if ($template=="mobile") {
                    header('Location: index.php');
                } else {
                    pageBookmarklet();
                }
                die();
            }

        } else {

            // User couldn't be logged in

            $PIVOTX['events']->add('failed_login', "", safeString($_POST['username']));

            $PIVOTX['messages']->addMessage($PIVOTX['session']->getMessage());
            $PIVOTX['template']->assign("form", $form->fetch(true));

        }


    }

    // Check for warnings to display
    $PIVOTX['messages']->checkWarnings();

    if ($template=="normal") {
        $templatename = "generic.tpl";
    } else if ($template=="mobile") {
        $templatename = "mobile/generic.tpl";
    } else {
        $templatename = "bookmarklet_login.tpl";
    }

    renderTemplate($templatename);


}


/**
 * Display the 'About' screen.
 *
 */
function pageAbout() {
    global $PIVOTX;

    $PIVOTX['template']->assign('title', __("About PivotX"));
    $PIVOTX['template']->assign('skiptitle', true);

    renderTemplate('about.tpl');


}


/**
 * Display the Dashboard. (also known as the Home or Overview screen)
 *
 */
function pageDashboard() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);
    
    $PIVOTX['template']->assign('title', __("Dashboard"));
    $PIVOTX['template']->assign('heading', "");

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    // Check if we need to 'force' a user filter, based on the
    // 'show_only_own_userlevel' settings..
    if ( $currentuser['userlevel'] <= $PIVOTX['config']->get('show_only_own_userlevel') ) {
        $filter_user = $currentuser['username'];
    } else {
        $filter_user = "";
    }

    // Get the 20 latest events, if the user is an administrator or higher.
    if ($currentuser['userlevel'] >= PIVOTX_UL_ADMIN) {
        $events = $PIVOTX['events']->get(20);
    }

    // Get the 8 latest entries..
    $entries = $PIVOTX['db']->read_entries(array('show'=>8, 'user'=>$filter_user, 'order'=>'desc'));

    // Mark the entries as editable or not..
    foreach ($entries as $key => $entry) {
        $entries[$key]['editable'] = $PIVOTX['users']->allowEdit('entry', $entry['user']);
    }

    // Get the 8 latest pages..
    $pages = $PIVOTX['pages']->getLatestPages(8, $filter_user);


    // Mark the pages as editable or not..
    foreach ($pages as $key => $page) {
        $pages[$key]['editable'] = $PIVOTX['users']->allowEdit('page', $page['user']);
    }


    // Get the 8 latest comments.. (non-moderated get priority)
    require_once(dirname(__FILE__).'/modules/module_comments.php');
    $modcomments = getModerationQueue();

    $latestcomments = $comments = $PIVOTX['db']->read_latestcomments(array(
        'amount'=>10,
        'cats'=>'',
        'count'=>15,
        'moderated'=>1
    ));

    $latestcomments = array_merge($modcomments, $latestcomments);
    $latestcomments = array_slice($latestcomments, 0, 8);

    // Check for blocked IPs
    $blocklist = new IPBlock();
    foreach($latestcomments as $key=>$latestcomment) {
        $latestcomments[$key]['blocked'] = $blocklist->isBlocked($latestcomment["ip"]);
    }
    // Check for warnings to display
    $PIVOTX['messages']->checkWarnings();

    // Check if debug is disabled and remove the debug logfile, if it exists.
    if( !$PIVOTX['config']->get('debug') && (file_exists($PIVOTX['paths']['db_path'].'logfile.php') ) ) {
        unlink($PIVOTX['paths']['db_path'].'logfile.php');
    }

    $PIVOTX['template']->assign('news', $news);
    $PIVOTX['template']->assign('events', $events);
    $PIVOTX['template']->assign('entries', $entries);
    $PIVOTX['template']->assign('pages', $pages);
    $PIVOTX['template']->assign('latestcomments', $latestcomments);
    $PIVOTX['template']->assign('users', $PIVOTX['users']->getUserNicknames() );

    renderTemplate('home.tpl');

}



/**
 * Display the user setup screen. This screen can only be shown when there are no
 * users present.
 *
 */
function pageSetupUser() {
    global $PIVOTX;

    // check if there really are no users.
    if ($PIVOTX['users']->count()!=0) {
        // if there aren't, display the Login screen in stead.
        pageLogin();
        die();
    }

    $PIVOTX['template']->assign('title', __("Setup user"));
    $PIVOTX['template']->assign('heading', __("Setup a new user"));

    $form = getSetupUserForm();

    // Get the validation result
    $result = $form->validate();

    if ( $result != FORM_OK ) {

        // Make sure we don't have any lingering sessions or cookies..
        $PIVOTX['session']->logout();

        $PIVOTX['template']->assign("form", $form->fetch());
    } else {

        $data = $form->getvalues();
        $data['userlevel'] = 4;
        $data['text_processing'] = 5;
        if (!empty($data['language'])) { 
            $PIVOTX['config']->set('language', $data['language']); 
        } else {
            $data['language'] = 'en';
        }
        if (!empty($data['db_model'])) { $PIVOTX['config']->set('db_model', $data['db_model']); }
        if (!empty($data['db_username'])) { $PIVOTX['config']->set('db_username', $data['db_username']); }
        if (!empty($data['db_password'])) { $PIVOTX['config']->set('db_password', $data['db_password']); }
        if (!empty($data['db_databasename'])) { $PIVOTX['config']->set('db_databasename', $data['db_databasename']); }
        if (!empty($data['db_hostname'])) { $PIVOTX['config']->set('db_hostname', $data['db_hostname']); }
        if (!empty($data['db_prefix'])) { $PIVOTX['config']->set('db_prefix', $data['db_prefix']); }

        $PIVOTX['users']->addUser( $data );

        $PIVOTX['events']->add('add_user', "", $data['username']);

        // Ensure the message is in the correct language
        $PIVOTX['languages']->switchLanguage($data['language']);
        $message = urlencode(__("The user has been added! You can login with your new account now."));

        // Reload the page, because we want to make sure the correct language is used.
        header("Location: index.php?page=login&px_message=".$message);
        die();

    }



    renderTemplate('generic.tpl');


}

/**
 * Display 'Entries' page.
 */
function pageEntries() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Entries'));
//    $PIVOTX['template']->assign('heading', __('Overview of Entries'));



    if (count($_POST)>1) {
        // Ah, we have to do something, like deleting or publishing entries.

        $PIVOTX['template']->assign('search', $_POST['search']);

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        // Flip the array in $_POST['check'] so that it has useable ids.
        $ids = array();
        foreach ($_POST['check'] as $key=>$value) {
            $ids[] = $key;
        }

        // Perform 'set to publish'..
        if ($_POST['action']=="publish") {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
            if ($PIVOTX['db']->publish_entries($ids)) {
                $PIVOTX['messages']->addMessage(__("The entries were published."));
            } else {
                $PIVOTX['messages']->addMessage(__("The entries could not be published."));
            }
        }

        // Perform 'set to hold'..
        if ($_POST['action']=="hold") {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
            if ($PIVOTX['db']->depublish_entries($ids)) {
                $PIVOTX['messages']->addMessage(__("The entries were put on hold."));
            } else {
                $PIVOTX['messages']->addMessage(__("The entries could not be put on hold."));
            }
        }

        // Perform 'delete entries'..
        if ($_POST['action']=="delete") {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);
            if ($PIVOTX['db']->delete_entries($ids)) {
                $PIVOTX['messages']->addMessage(__("The entries were deleted."));
                // Remove the compiled/parsed pages from the cache.
                if($PIVOTX['config']->get('smarty_cache')){
                    $PIVOTX['template']->clear_cache();
                }
            } else {
                $PIVOTX['messages']->addMessage(__("The entries could not be deleted."));
            }
        }


    }

    if (!empty($_GET['del'])) {

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_GET['pivotxsession']);

        $entry = $PIVOTX['db']->read_entry(intval($_GET['del']));
        if ( !$PIVOTX['users']->allowEdit('entry', $entry['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to delete this entry.")."</p>");
            renderTemplate('generic.tpl');
            return;
        } else {
            $PIVOTX['db']->delete_entry();
            // Remove the compiled/parsed pages from the cache.
            if($PIVOTX['config']->get('smarty_cache')){
                $PIVOTX['template']->clear_cache();
            }            
        }

        $PIVOTX['messages']->addMessage(__("The entry has been deleted"));

    }


    renderTemplate('entries.tpl');

}



/**
 * Display 'Extensions' page.
 */
function pageExtensions() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', __('Extensions'));
    $PIVOTX['template']->assign('heading', __('Manage installed Extensions'));

    $list = $PIVOTX['extensions']->scanExtensions();

    // Set the 'compact' or 'expanded' view for the current user..
    $currentuser = $PIVOTX['session']->currentUsername();
    $compactview = unserialize($PIVOTX['config']->get('compactview'));
    $PIVOTX['template']->assign('compactview', (isset($compactview[$currentuser]) ? "1" : "0") );

    // Set the identifier
    $identifiers = $PIVOTX['extensions']->getIdentifiers(array('admin', 'snippet', 'hook'));
    $PIVOTX['template']->assign("identifiers", implode(",", $identifiers));

    $form = getExtensionsForm($list);

    // Get the validation result
    $result = $form->validate();

    if ( $result != FORM_OK ) {
        $PIVOTX['template']->assign("form", $form->fetch());
    } else {

        // Start out with the current widgets, because we don't want to
        // change those..
        $activated = $PIVOTX['extensions']->getActivated('widget_');

        // Add the selected ones from $_POST
        unset($_POST['csrfcheck']);
        foreach($_POST as $name => $value) {
            if ($value == 1) {
                $activated[] = $name;
            }
        }

        // Set them as activated.
        $PIVOTX['extensions']->setActivated($activated);

        $PIVOTX['events']->add('activate_extensions', $activated);
        $message = urlencode(__("Your Extension settings have been stored."));

        // Reload the page, because we want to make sure the new extensions are loaded and initialized..
        header("Location: index.php?page=extensions&px_message=".$message);
        die();

    }

    renderTemplate('extensions.tpl');

}



/**
 * Display 'Widgets' page.
 */
function pageWidgets() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', __('Widgets'));
    $PIVOTX['template']->assign('heading', __('Manage installed Widget extensions'));

    $list = $PIVOTX['extensions']->scanExtensions();

    // Set the 'compact' or 'expanded' view for the current user..
    $currentuser = $PIVOTX['session']->currentUsername();
    $compactview = unserialize($PIVOTX['config']->get('compactview'));
    $PIVOTX['template']->assign('compactview', (isset($compactview[$currentuser]) ? "1" : "0") );

    // Set the identifier
    $identifiers = $PIVOTX['extensions']->getIdentifiers('widget');
    $PIVOTX['template']->assign("identifiers", implode(",", $identifiers));
    // if we have $_GET['widget'], we submitted the page, so we need to store
    // the selected widgets.
    if ( isset($_GET['widget']) )  {
        $active_snippets = $PIVOTX['extensions']->getActivated('snippet_');
        $active_hooks = $PIVOTX['extensions']->getActivated('hook_');
        $active_admin = $PIVOTX['extensions']->getActivated('admin_');

        // Make sure $_GET['widget'] is an array, even if nothing is passed,
        // otherwise array_merge will get confused.
        if (!is_array($_GET['widget'])) {
           $_GET['widget'] = array();
        }

        $active = array_unique(array_merge($active_snippets, $active_hooks, $active_admin, $_GET['widget']));

        $PIVOTX['extensions']->setActivated($active);

        $PIVOTX['events']->add('activate_extensions', $active);

        $message = urlencode(__("Your Extension settings have been stored."));

        // Reload the page, because we want to make sure the new extensions are loaded and initialized..
        header("Location: index.php?page=widgets&px_message=".$message);
        die();

    }

    $active = "";
    $inactive = "";

    foreach ($list['widget'] as $extension) {

        $description = sprintf("<li class=\"widget\" id=\"widget_%s\"><span rel='%s' id='update-%s'></span><strong>%s</strong><br /><p style='margin: 0;'>%s</p><p style='color: #999; margin: 0; font-size: 11px;'>By: %s",
            $extension['identifier'],
            $extension['version'],
            $extension['identifier'],
            $extension['extension'],
            $extension['description'],
            $extension['author']
        );

        if ($extension['date']!="") {
            $description .= ", last updated: " . $extension['date'];
        }

        if ($extension['site']!="") {
            $description .= ", <a href='".$extension['site']."'>link</a>";
        }

        // Show it if it's in the list of active extensions, or it is
        // in the $_GET['active'], but NOT in $_GET['available']..
        if ( $extension['active']==1 ) {

             // If the extension has its own config screen, we make a direct link to it.
            if ($extension['config']!="" ) {
                $description .= ", <a href='index.php?page=configuration#section-".$extension['config'].
                    "'>" . __('edit configuration') . "</a>";
            }

            $description .= ".</li>";
            $active .= $description;

        } else {

            $description .= ".</li>";
            $inactive .= $description;

        }


    }

    $PIVOTX['template']->assign("active", $active);
    $PIVOTX['template']->assign("inactive", $inactive);


    renderTemplate('widgets.tpl');

}


/**
 * Display 'Entry' page.
 */
function pageEntry() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    if ($_GET['uid']=="") {
        $PIVOTX['template']->assign('title', __('New Entry'));
    } else {
        $PIVOTX['template']->assign('title', __('Edit Entry'));
    }

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    if (!empty($_GET['uid'])) {

        // Editing an entry.. Get it from the DB..

        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

        $PIVOTX['events']->add('edit_entry', intval($_GET['uid']), $entry['title']);

        if ( !$PIVOTX['users']->allowEdit('entry', $entry['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to edit this entry.")."</p>");
            renderTemplate('generic.tpl');
            return;
        }

        // Make sure we tweak the </textarea> in the intro or body text (since
        // that would break our own textarea, if we didn't)..
        $entry['introduction'] = str_replace("<textarea", "&lt;textarea", $entry['introduction']);
        $entry['introduction'] = str_replace("</textarea", "&lt;/textarea", $entry['introduction']);
        $entry['body'] = str_replace("<textarea", "&lt;textarea", $entry['body']);
        $entry['body'] = str_replace("</textarea", "&lt;/textarea", $entry['body']);

        // If the entry was written in 'convert LB', 'textile' or 'markdown', and is now
        // being edited in 'Plain XHTML' or 'WYSIWYG', we must convert it.
        if ( ($entry['convert_lb']=="1" || $entry['convert_lb']=="2" || $entry['convert_lb']=="3") &&
            ($currentuser['text_processing']=="0" || $currentuser['text_processing']=="5") ) {
            $entry['introduction'] = parse_intro_or_body($entry['introduction'], false, $entry['convert_lb'], true);
            $entry['body'] = parse_intro_or_body($entry['body'], false, $entry['convert_lb'], true);
        }

        // Otherwise, if the entry was written in 'Plain XHTML' or 'WYSIWYG', and is now
        // being edited in 'convert LB', 'textile' or 'markdown', there is not much more we
        // can do than strip out the <p> and <br/> tags to replace with linebreaks.
        if ( ($entry['convert_lb']=="0" || $entry['convert_lb']=="5") &&
            ($currentuser['text_processing']=="1" || $currentuser['text_processing']=="2" || $currentuser['text_processing']=="3") ) {
            $entry['introduction'] = unparse_intro_or_body($entry['introduction']);
            $entry['body'] = unparse_intro_or_body($entry['body']);
        }

        list($entry['link'], $entry['link_end']) = explode($entry['uri'], $entry['link']);

    } else {

        // Make a new entry.

        $entry = array();

        if ($PIVOTX['config']->get('default_category')!="") {
            $entry['category'] = array($PIVOTX['config']->get('default_category'));
        }
        if ($PIVOTX['config']->get('allow_comments')!="") {
            $entry['allow_comments'] = $PIVOTX['config']->get('allow_comments');
        }

        if ($PIVOTX['config']->get('default_post_status')!="") {
            $entry['status'] = $PIVOTX['config']->get('default_post_status');
        }

        $entry['user'] = $currentuser['username'];

        $entry['link'] = makeFileLink(array('uri' => 'xxx', 'date'=>date("Y-m-d-H-i-s")), "", "");
        list($entry['link'], $entry['link_end']) = explode('xxx', $entry['link']);

    }

    // Make sure we only show the allowed categories.. Superadmins can always
    // see and use all categories..
    $categories = $PIVOTX['categories']->getCategories();

    if ($currentuser['userlevel'] < PIVOTX_UL_SUPERADMIN) {
        $allowedcats = $PIVOTX['categories']->allowedCategories($currentuser['username']);
        foreach ($categories as $key => $value) {
            if (!in_array($value['name'], $allowedcats)) {
                unset($categories[$key]);
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD']=="GET") {

        // Ignore URI if we aren't using mod_rewrite.
        if ($PIVOTX['config']->get('mod_rewrite') == 0) {
            unset($entry['uri']);
        }

        $PIVOTX['extensions']->executeHook('entry_edit_beforeedit', $entry);

        // Show the screen..
        $PIVOTX['template']->assign('entry', $entry);
        $PIVOTX['template']->assign('categories', $categories);
        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign('users', $PIVOTX['users']->getUsers());
        $PIVOTX['template']->assign('entryuser', $PIVOTX['users']->getUser($entry['user']));
        renderTemplate('editentry.tpl');

    } else {

        if ($_POST['code']!=$_GET['uid']) {
            $PIVOTX['events']->add('fatal_error', intval($_GET['uid']), "Tried to fake editing an entry");
            echo "Code is wrong! B0rk!";
            die();
        }

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        // Sanitize the $_POST into an entry we can store
        $entry = sanitizePostedEntry($entry);

        $PIVOTX['extensions']->executeHook('entry_edit_beforesave', $entry);

        $entry = $PIVOTX['db']->set_entry($entry);

        if ($PIVOTX['db']->save_entry(TRUE)) {
            $PIVOTX['messages']->addMessage( sprintf( __('Your entry "%s" was successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' ) );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave', $entry);
        } else {
            $PIVOTX['messages']->addMessage( sprintf( __('Your entry "%s" was NOT successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' ) );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave_failed', $entry);
        }

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // only trigger the ping if it's a new entry..
        if ( ($entry['code']==">") && ($entry['status']=="publish") ) {
            $ping=TRUE;
        } else {
            $ping=FALSE;
        }

        // only notify if entry is published, and is either new or status changed to publish.
        if (($entry['status']=="publish") && !$PIVOTX['config']->get('disable_new_entry_notifications')) {
           if ( ($entry['code']==">") || ($entry['oldstatus']!="publish") ) {
               $notified = sendMailNotification('entry',$PIVOTX['db']->entry);
               $notified = "<br /><br />" . $notified;
           }
        }

        // perhaps send a trackback ping.
        if ( ($_POST['tb_url'] != "") && ($entry['status']=="publish") ) {

            require_once( 'includes/send_trackback.php' );
            $weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($PIVOTX['db']->entry['category']);
            $entry_url = $PIVOTX['paths']['host'] . makeFileLink( $PIVOTX['db']->entry['code'],$weblogs[0],'' );
            $weblogdata = $PIVOTX['weblogs']->getWeblog($weblogs[0]);
            $weblog_title = $weblogdata['name'];
            $excerpt = parse_intro_or_body($entry['introduction'], false, $entry['convert_lb']);
            $excerpt = trimText(strip_tags($excerpt),255);

            $tb_urls = explode("\n", $_POST['tb_url']);
            foreach($tb_urls as $tb_url) {
                $tb_url = trim($tb_url);
                if(isUrl($tb_url)) {
                    $PIVOTX['messages']->addMessage(sprintf(__('A trackback ping has been sent to "%s".'),$tb_url));
                    trackback_send($tb_url, $entry_url, $entry['title'], $weblog_title, $excerpt);
                }
            }
        }

        // TODO: check input for valid categories for user

        // If we use 'save and continue' on a new Entry, we need to redirect to the page
        // for editing, or we can stop displaying stuff here.. We redirect to
        // that entry, because otherwise we would end up with several double entries.
        if (($_POST['postedfrom']=="continue") ) {

            if ($_POST['code']=="") {
                // New entry..
                echo "<script type='text/javascript'>";
                echo "window.top.location.href ='index.php?page=entry&uid=".$PIVOTX['db']->entry['uid']."';";
                echo "</script>";
            } else {
                // nothing..
            }

        } else {

            // Redirect to the listing page
            header('Location: ' . makeAdminPageLink('entries'));
            exit;

        }
    }
}


/**
 * Display 'Logout' page.
 */
function pageLogout() {
    global $PIVOTX;

    $PIVOTX['session']->logout();

    $PIVOTX['template']->assign('title', __('Log out'));
    $PIVOTX['template']->assign('heading', __('Log out'));
    $PIVOTX['messages']->addMessage( __('You are now logged out of PivotX'));

    $form = getLoginForm();

    $PIVOTX['template']->assign("form", $form->fetch(true));

    renderTemplate('generic.tpl');


}


/**
 * Display 'My Info' page.
 */
function pageMyinfo() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('My Settings'));
    $PIVOTX['template']->assign('heading', __('Add a Bookmarklet'));

    $form = getMyInfoForm();

    // Get the validation result
    $result = $form->validate();

    if ( $result == FORM_NOTPOSTED ) {

        // Get the values for the user.
        $user = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

        // Put the user values in the form.
        $form->setvalues($user);

    } else if ( $result == FORM_HASERRORS ) {

        // Try again.

        // Log error, if it was in CSRF
        if (in_array('csrfcheck', $form->geterrors())) {

            $values = $form->getvalues();

            // Note that the value of the cookie is padded, because we never
            // want to give it away..
            $error = sprintf("Error in CSRF check. \nI have: '%s'\nI want: '%s'" ,
                $values['csrfcheck'],
                substr($_COOKIE['pivotxsession'], 0, 12)."...."
            );
            debug($error);
        }

        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));

    } else {

        $val = $form->getvalues();

        // Make sure we don't try to set the username or userlevel.
        unset($val['username']);
        unset($val['userlevel']);

        // Update the user..
        $PIVOTX['users']->updateUser($PIVOTX['session']->currentUsername(), $val);

        // If the user was editing his own settings, update them in the session immediately:
        $currentuser = $PIVOTX['session']->currentUser();
        foreach($val as $key=>$value) {
            $currentuser[$key] = $value;
        }
        $PIVOTX['session']->setUser($currentuser);

        $PIVOTX['events']->add('edit_info');
        // Set the language (in case it changed)
        $PIVOTX['languages']->switchLanguage($currentuser['language']);

        // Show a message
        $PIVOTX['messages']->addMessage(__("Your settings have been updated."));



    }

    $html = sprintf("<p style='width: 440px; margin-top: 0px;'>%s</p><p>
        <a href='javascript:(function(){PX_URI=\"%s\";var s=document.createElement(\"script\");s.setAttribute(\"src\",PX_URI+\"includes/js/bookmarklet.js?\"+ (new Date().getTime()));document.getElementsByTagName(\"head\")[0].appendChild(s);})()' class='bookmarklet'>%s</a>

",
        __("Add a personal 'Quick post' Bookmarklet to your browser, with the following link. Drag the link to your browser's Bookmark Toolbar, or right-click the link, and choose 'Add Bookmark'."),
        $PIVOTX['paths']['host'] . $PIVOTX['paths']['pivotx_url'],
        __('Post to PivotX')
    );


    $html .= "<h2>" . __('Edit my personal Settings') . "</h2>";

    // Prevent browsers from being 'helpful' and filling out the password field..
    // Add an event to prevent 'clicking' on the bookmarklet
    $html .= sprintf("    <script type=\"text/javascript\">
    jQuery(function($) {
        $('#pass1, #pass2').val('******');

        $('.bookmarklet').bind('click', function() {
            alert(\"%s\");
            return false;
        });

    });
    </script>", __("You must add the Bookmarklet to your browser before you can use it.") );

    // echo "<pre>\n"; print_r($PIVOTX['paths']); echo "</pre>";

    $PIVOTX['template']->assign("form", $form->fetch());
    $PIVOTX['template']->assign("html", $html);

    renderTemplate('generic.tpl');

}


/**
 * Display 'Page' page.
 */
function pagePage() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    if ($_GET['uid']=="") {
        $PIVOTX['template']->assign('title', __('Write a new Page'));
    } else {
        $PIVOTX['template']->assign('title', __('Edit Page'));
    }



    if ($_GET['uid']!="") {

        // Editing a page.. Get it from the DB..
        $page = $PIVOTX['pages']->getPage($_GET['uid']);

        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

        if ( !$PIVOTX['users']->allowEdit('page', $page['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to edit this page.")."</p>");
            renderTemplate('generic.tpl');
            return;
        }

        $PIVOTX['events']->add('edit_page', intval($page['uid']), $page['title']);

        // Make sure we tweak the </textarea> in the intro or body text (since
        // that would break our own textarea, if we didn't)..
        $page['introduction'] = str_replace("<textarea", "&lt;textarea", $page['introduction']);
        $page['introduction'] = str_replace("</textarea", "&lt;/textarea", $page['introduction']);
        $page['body'] = str_replace("<textarea", "&lt;textarea", $page['body']);
        $page['body'] = str_replace("</textarea", "&lt;/textarea", $page['body']);


        // If the page was written in 'convert LB', 'textile' or 'markdown', and is now
        // being edited in 'Plain XHTML' or 'WYSIWYG', we must convert it.
        if ( ($page['convert_lb']=="1" || $page['convert_lb']=="2" || $page['convert_lb']=="3") &&
            ($currentuser['text_processing']=="0" || $currentuser['text_processing']=="5") ) {
            $page['introduction'] = parse_intro_or_body($page['introduction'], false, $page['convert_lb'], true);
            $page['body'] = parse_intro_or_body($page['body'], false, $page['convert_lb'], true);
        }

        // Otherwise, if the page was written in 'Plain XHTML' or 'WYSIWYG', and is now
        // being edited in 'convert LB', 'textile' or 'markdown', there is not much more we
        // can do than strip out the <p> and <br/> tags to replace with linebreaks.
        if ( ($page['convert_lb']=="0" || $page['convert_lb']=="5") &&
            ($currentuser['text_processing']=="1" || $currentuser['text_processing']=="2" || $currentuser['text_processing']=="3") ) {
            $page['introduction'] = unparse_intro_or_body($page['introduction']);
            $page['body'] = unparse_intro_or_body($page['body']);
        }

        list($page['link'], $page['link_end']) = explode($page['uri'], $page['link']);

    } else {

        // Make a new page.

        $page = array();

        if ($_GET['chapter']!="") {
            $page['chapter'] = intval($_GET['chapter']);
        }

        $user = $PIVOTX['session']->currentUser();
        $page['user']= $user['username'];

        $page['sortorder']=10;


        /*
        if ($PIVOTX['config']->get('allow_comments')!="") {
            $page['allow_comments'] = $PIVOTX['config']->get('allow_comments');
        }
        */

        if ($PIVOTX['config']->get('default_post_status')!="") {
            $page['status'] = $PIVOTX['config']->get('default_post_status');
        }

        $page['link'] = makePagelink("xxx");
        list($page['link'],$page['link_end']) = explode('xxx', $page['link']);

    }

    $templates = templateOptions(templateList(), 'page', array('_sub_', '_aux_'));

    if ($_SERVER['REQUEST_METHOD']=="GET") {

        $PIVOTX['extensions']->executeHook('page_edit_beforeedit', $page);

        // Show the screen..
        $PIVOTX['template']->assign('templates', $templates);
        $PIVOTX['template']->assign('page', $page);
        $PIVOTX['template']->assign('chapters', $PIVOTX['pages']->getIndex());
        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign('users', $PIVOTX['users']->getUsers());
        $PIVOTX['template']->assign('pageuser', $PIVOTX['users']->getUser($page['user']));

        renderTemplate('editpage.tpl');

    } else {

        if ($_POST['code']!=$_GET['uid']) {
            echo "Code is wrong! B0rk!";
            die();
        }

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        // Sanitize the $_POST into an entry we can store
        $page = sanitizePostedPage($page);

        $PIVOTX['extensions']->executeHook('page_edit_beforesave', $page);
        $new_id = $PIVOTX['pages']->savePage($page);
        $PIVOTX['extensions']->executeHook('page_edit_aftersave', $page);

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // If we use 'save and continue' on a new Page, we need to redirect to the page
        // for editing, or we can stop displaying stuff here.. We redirect to
        // that entry, because otherwise we would end up with several double entries.
        if (($_POST['postedfrom']=="continue") ) {

            if ($_POST['code']=="") {
                // New page..
                echo "<script type='text/javascript'>";
                echo "window.top.location.href ='index.php?page=page&uid=".$new_id."';";
                echo "</script>";
            } else {
                // nothing..
            }

            die();

        } else {

            $PIVOTX['events']->add('save_page', intval($new_id), $page['title']);

        }

        // TODO: check input for valid categories for user

        // Update the search index for this entry, but only if we're using flat files.
        if ($PIVOTX['db']->db_type == "flat") {
            $page['code'] = $page['uid'] = $new_id;
            updateSearchIndex($page, 'p');
        }

        pagePagesoverview();

    }

}




/**
 * Display 'pages overview' page.
 */
function pagePagesoverview() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Pages'));
    $PIVOTX['template']->assign('skiptitle', true);
    $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());

    // Todo: check if CSRF is ok when deleting a chapter or page. The "pivotxsession"
    // cookie must be added to the delete request(s) in pages.tpl.

    // Perhaps delete a chapter or a page
    if (isset($_GET['del'])) {
        if ( !$PIVOTX['users']->allowEdit('chapter') ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to delete this chapter.")."</p>");
            renderTemplate('generic.tpl');
            return;
        } else {
            $PIVOTX['pages']->delChapter($_GET['del']);
            $PIVOTX['messages']->addMessage( __('The Chapter has been deleted.'));

            // Remove the compiled/parsed pages from the cache.
            if($PIVOTX['config']->get('smarty_cache')){
                $PIVOTX['template']->clear_cache();
            }            
        }
    } elseif (isset($_GET['delpage'])) {
        $page = $PIVOTX['pages']->getPage($_GET['delpage']);
        if ( !$PIVOTX['users']->allowEdit('page', $page['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to delete this page.")."</p>");
            renderTemplate('generic.tpl');
            return;
        } else {
            $PIVOTX['pages']->delPage($_GET['delpage']);
            $PIVOTX['messages']->addMessage( __('The Page has been deleted.'));
            // Remove the compiled/parsed pages from the cache.
            if($PIVOTX['config']->get('smarty_cache')){
                $PIVOTX['template']->clear_cache();
            }
        }
    }

    $PIVOTX['template']->assign('pages', $PIVOTX['pages']->getIndex(true, true) );
    $PIVOTX['template']->assign('users', $PIVOTX['users']->getUserNicknames() );

    renderTemplate('pages.tpl');

}


/**
 * Add or edit a chapter
 *
 */
function pageChapter() {
   global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);


    $form = getEditChapterForm();

    $result = $form->validate();

    if ( $result == FORM_NOTPOSTED ) {

        if (isset($_GET['id'])) {
            // Put the chapter values in the form.
            $form->setvalues($PIVOTX['pages']->getChapter($_GET['id']));
        }

    } else if ( $result == FORM_HASERRORS ) {

        // Try again.
        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));

    } else {

        $chapter = $form->getvalues();

        if ( !$PIVOTX['users']->allowEdit('chapter') ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to add/edit this page.")."</p>");
            renderTemplate('generic.tpl');
            return;
        } else {
            if (isset($_GET['id'])) {
                // Update the chapter
                $PIVOTX['pages']->updateChapter($_GET['id'], $chapter);
                $PIVOTX['events']->add('edit_chapter', intval($_GET['id']), $chapter['chaptername']);
            } else {
                // New chapter
                $PIVOTX['pages']->addChapter($chapter);
                $PIVOTX['events']->add('add_chapter', 0, $chapter['chaptername']);
            }
            
            // Remove the compiled/parsed pages from the cache.
            if($PIVOTX['config']->get('smarty_cache')){
                $PIVOTX['template']->clear_cache();
            }
            
        }

        // Remove the modal form and show a message
        $PIVOTX['messages']->addMessage(sprintf(__("The settings for chapter '%s' have been updated."),
            $chapter['chaptername']));
        pagePagesoverview();
        die();

    }

    $PIVOTX['template']->assign("form", $form->fetch());

    renderTemplate('modal.tpl');

}


/**
 * Display 'Comments' page.
 */
function pageTrackbacks() {
    require_once(dirname(__FILE__).'/modules/module_trackbacks.php');
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Trackbacks'));


    if ($_GET['uid']!="") {

        // Editing an entry.. Get it from the DB..
        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
        $trackbacks = $entry['trackbacks'];

        // Check if the user is allowed to edit this entry. It should either be his/her own
        // Entry, or the userlevel should be advanced.
        if ($PIVOTX['session']->currentUsername() != $entry['user']) {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
        }

        $heading = __('Edit or Delete Trackback for Entry %number% - %editlink%');
        $heading = str_replace('%number%', $entry['uid'], $heading);
        $heading = str_replace('%editlink%', '<a href="index.php?page=entry&amp;uid='.
            $entry['uid'] . '">' . $entry['title'] . '</a>', $heading);

        $PIVOTX['template']->assign('heading', $heading);

        // Perhaps delete a trackback
        if ($_GET['del']!="") {

            $PIVOTX['db']->delete_trackback($_GET['del']);

            $PIVOTX['messages']->addMessage(__("The trackback was deleted."));

            // Reread trackbacks
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $trackbacks = $entry['trackbacks'];

        } else if ($_GET['block']!="") {
            // Or add the IP to the blocklist..

            $trackback = $PIVOTX['db']->get_trackback($_GET['block']); 

            if (!empty($trackback['ip'])) {

                // Initialise the IP blocklist.
                $blocklist = new IPBlock();

                $blocklist->add($trackback['ip'], $trackback['name'] . " - " . $trackback['url']);

                $PIVOTX['messages']->addMessage(__("The IP-address has been added to the blocklist."));

            } else {

                $PIVOTX['messages']->addMessage(__("The IP-address couldn't be added to the blocklist."));

            }

            // Reread trackbacks
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $trackbacks = $entry['trackbacks'];

        } else if ($_GET['unblock']!="") {
            // Or remove the IP to the blocklist..

            $trackback = $PIVOTX['db']->get_trackback($_GET['unblock']); 

            if (!empty($trackback['ip'])) {

                // Initialise the IP blocklist.
                $blocklist = new IPBlock();

                $blocklist->remove($trackback['ip'], $trackback['name'] . " - " . $trackback['url']);

                $PIVOTX['messages']->addMessage(__("The IP-address has been removed from the blocklist."));

            } else {

                $PIVOTX['messages']->addMessage(__("The IP-address couldn't be removed from the blocklist."));

            }

            // Reread trackbacks
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $trackbacks = $entry['trackbacks'];

        } elseif ($_GET['msg']!="") {

            $PIVOTX['messages']->addMessage($_GET['msg']);

        }

        // Check for blocked IPs
        $blocklist = new IPBlock();
        foreach($trackbacks as $key=>$trackback) {
            $trackbacks[$key]['blocked'] = $blocklist->isBlocked($trackback["ip"]);
        }

        $PIVOTX['template']->assign('uid', $_GET['uid']);
        $PIVOTX['template']->assign('entry', $entry);
        $PIVOTX['template']->assign('trackbacks', $trackbacks);

    } else {

        $PIVOTX['template']->assign('heading', __('Edit or Delete the Latest Trackbacks'));

        // If we don't get a specific uid, we show the comments that are in moderation, and the latest comments..
        if (isset($_POST['action_approve'])) {
            deleteTrackbacks($_POST['checked']);
        }

        $lasttrackbacks = $PIVOTX['db']->read_lasttrackbacks(array(
            'amount'=>10,
            'cats'=>'',
            'count'=>15,
            'moderated'=>1
        ));

        // Check for blocked IPs
        $blocklist = new IPBlock();
        foreach($lasttrackbacks as $key=>$trackback) {
            $lasttrackbacks[$key]['blocked'] = $blocklist->isBlocked($trackback["ip"]);
        }

        $PIVOTX['template']->assign('trackbacks', $lasttrackbacks);

    }

    // Allow only admins to block/unblock IP addresses..
    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
    $allowblock = ($currentuser['userlevel'] >= PIVOTX_UL_ADMIN) ? true : false;
    $PIVOTX['template']->assign('allowblock', $allowblock);

    renderTemplate('trackbacks.tpl');

}

/**
 * Display 'Moderatecomments' page.
 */
function pageModerateComments() {
    require_once(dirname(__FILE__).'/modules/module_comments.php');
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Comment Moderation Queue'));

    $PIVOTX['template']->assign('heading', __('Edit or Delete Comment Moderation Queue'));

    // If we don't get a specific uid, we show the comments that are in moderation, and the latest comments..
    if (isset($_POST['action_approve'])) {
        approveComments($_POST['checked']);
    } elseif (isset($_POST['action_delete'])) {
        deleteComments($_POST['checked']);
    }

    $modcomments = getModerationQueue();

    // Check for blocked IPs
    $blocklist = new IPBlock();
    foreach($modcomments as $key=>$comment) {
        $modcomments[$key]['blocked'] = $blocklist->isBlocked($comment["ip"]);
    }

    $PIVOTX['template']->assign('moderating', true);
    $PIVOTX['template']->assign('modcomments', $modcomments);


    // Allow only admins to block/unblock IP addresses..
    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
    $allowblock = ($currentuser['userlevel'] >= PIVOTX_UL_ADMIN) ? true : false;
    $PIVOTX['template']->assign('allowblock', $allowblock);

    $truncate = getDefault($PIVOTX['config']->get('comment_truncate'), 210);
    $PIVOTX['template']->assign('truncate', $truncate);

    renderTemplate('moderatecomments.tpl');

}

/**
 * Display 'Comments' page.
 */
function pageComments() {
    require_once(dirname(__FILE__).'/modules/module_comments.php');
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Comments'));

    if ($_GET['uid']!="") {

        // Editing an entry.. Get it from the DB..
        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
        $comments = $entry['comments'];

        // Check if the user is allowed to edit this entry. It should either be his/her own
        // Entry, or the userlevel should be advanced.
        if ($PIVOTX['session']->currentUsername() != $entry['user']) {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
        }

        $heading = __('Edit or Delete Comments for Entry %number% - %editlink%');
        $heading = str_replace('%number%', $entry['uid'], $heading);
        $heading = str_replace('%editlink%', '<a href="index.php?page=entry&amp;uid='.
            $entry['uid'] . '">' . $entry['title'] . '</a>', $heading);

        $PIVOTX['template']->assign('heading', $heading);
        
        if ($_GET['del']!="") {
            // Perhaps delete a comment
            
            $PIVOTX['db']->delete_comment($_GET['del']);

            $PIVOTX['messages']->addMessage(__("The comment was deleted."));

            // Reread comments
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $comments = $entry['comments'];

            // If we have to return to the dahboard or the overview screen, we do it here..
            if ($_GET['return']=="overview") {
                $_GET['uid'] = ''; // Clear the uid, so PivotX doesn't try to load the entry.
                pageComments();
                die();
            } else if ($_GET['return']=="dashboard") {
                pageDashboard();
                die();
            }
             
        } else if ($_GET['block']!="") {
            // Or add the IP to the blocklist..
           
            $comment = $PIVOTX['db']->get_comment($_GET['block']); 

            if (!empty($comment['ip'])) {
                
                // Initialise the IP blocklist.
                $blocklist = new IPBlock();
                
                $blocklist->add($comment['ip'], $comment['name']);
                
                $PIVOTX['messages']->addMessage(__("The IP-address has been added to the blocklist."));
                
            } else {
                
                $PIVOTX['messages']->addMessage(__("The IP-address couldn't be added to the blocklist."));
                
            }

            // Reread comments
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $comments = $entry['comments'];

        } else if ($_GET['unblock']!="") {
            // Or remove the IP to the blocklist..

            $comment = $PIVOTX['db']->get_comment($_GET['unblock']); 

            if (!empty($comment['ip'])) {
                
                // Initialise the IP blocklist.
                $blocklist = new IPBlock();

                $blocklist->remove($comment['ip'], $comment['name']);

                $PIVOTX['messages']->addMessage(__("The IP-address has been removed from the blocklist."));

            } else {

                $PIVOTX['messages']->addMessage(__("The IP-address couldn't be removed from the blocklist."));

            }

            // Reread comments
            $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));
            $comments = $entry['comments'];

        } elseif ($_GET['msg']!="") {

            $PIVOTX['messages']->addMessage($_GET['msg']);

        }

        switch ($_GET['return']) {
            case 'moderatecomments':
                pivotxAdminRedirect('moderatecomments');
                break;
        }


        // Check for blocked IPs
        $blocklist = new IPBlock();
        foreach($comments as $key=>$comment) {
            $comments[$key]['blocked'] = $blocklist->isBlocked($comment["ip"]);
        }
        

        $PIVOTX['template']->assign('moderating', false);
        $PIVOTX['template']->assign('uid', $_GET['uid']);
        $PIVOTX['template']->assign('entry', $entry);
        $PIVOTX['template']->assign('comments', $comments);

    } else {

        $PIVOTX['template']->assign('heading', __('Edit or Delete Latest Comments'));

        // If we don't get a specific uid, we show the comments that are in moderation, and the latest comments..
        if (isset($_POST['action_approve'])) {
            approveComments($_POST['checked']);
        } elseif (isset($_POST['action_delete'])) {
            deleteComments($_POST['checked']);
        }

        $latestcomments = $PIVOTX['db']->read_latestcomments(array(
            'amount'=>10,
            'cats'=>'',
            'count'=>15,
            'moderated'=>1
        ));
        // Since 'comments.tpl' displays if the entry is moderated or not
        // we must add this to the latest comments.
        foreach ($latestcomments as $key => $value) {
            $latestcomments[$key]['moderate'] = 0;
        }

        // Check for blocked IPs
        $blocklist = new IPBlock();
        foreach($latestcomments as $key=>$comment) {
            $latestcomments[$key]['blocked'] = $blocklist->isBlocked($comment["ip"]);
        }

        $PIVOTX['template']->assign('moderating', true);
        $PIVOTX['template']->assign('comments', $latestcomments);

    }

    // Allow only admins to block/unblock IP addresses..
    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
    $allowblock = ($currentuser['userlevel'] >= PIVOTX_UL_ADMIN) ? true : false;
    $PIVOTX['template']->assign('allowblock', $allowblock);

    $truncate = getDefault($PIVOTX['config']->get('comment_truncate'), 210);
    $PIVOTX['template']->assign('truncate', $truncate);

    renderTemplate('comments.tpl');

}


/**
 * Display 'Edit Comment' page.
 */
function pageEditcomment() {
    global $PIVOTX;

    require_once(dirname(__FILE__).'/modules/module_comments.php');

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Comments'));
    $PIVOTX['template']->assign('heading', __('Edit or Delete Comments'));

    if (($_GET['uid']!="") && ($_GET['key']!="")) {

        // uid should be numeric. (If it's not, someone is hacking ...)
        if (!is_numeric($_GET['uid'])) {
            echo "uid must be numeric";
            die();
        }

        // Editing a comment.. Get it from the DB..
        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

        // Check if the user is allowed to edit this entry. It should either be his/her own
        // Entry, or the userlevel should be advanced.
        if ($PIVOTX['session']->currentUsername() != $entry['user']) {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
        }

        if (isset($entry['comments'][$_GET['key']])) {
            $comment = $entry['comments'][$_GET['key']];
        } else {
            // This should only happen for non-SQL db when editing a comment from
            // the latest comments screen (or similar functions) which uses fake UIDs.
            foreach ($entry['comments'] as $key => $value) {
                if ($_GET['key'] == makeCommentUID($value)) {
                    $comment = $value;
                    // Setting the key to the array key
                    $_GET['key'] = $key;
                    break;
                }
            }
        }

        $PIVOTX['template']->assign('uid', $_GET['uid']);

        $form = getCommentForm();

        // Get the validation result
        $result = $form->validate();

        if ( $result != FORM_OK ) {

            // Put the user values in the form.
            $form->setValues($comment);
            $PIVOTX['template']->assign("form", $form->fetch());

        } else {

            $val = $form->getValues();

            unset($val['csrfcheck']);

            editComment($entry, $_GET['key'], $val);

            // Set a message, show the comments screen.
            $msg = __('The Comment was saved!');
            $PIVOTX['messages']->addMessage($msg);

            // Show the correct page, or 'return' to where we came from..
            switch ($_GET['return']) {
                case 'overview':
                    pivotxAdminRedirect('comments');
                    break;
                case 'moderatecomments':
                    pivotxAdminRedirect('moderatecomments');
                    break;
                case 'dashboard':
                    pivotxAdminRedirect(false);
                    break;

                default:
                    pivotxAdminRedirect('comments');
                    break;
            }
            die();

        }


    } else {

        $PIVOTX['messages']->addMessage(__('You have to select an entry in order to view its comments.'));

        pageComments();
        die();

    }

    renderTemplate('modal.tpl');

}


/**
 * Display 'Edit Trackback' page.
 */
function pageEdittrackback() {
    require_once(dirname(__FILE__).'/modules/module_trackbacks.php');
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Trackbacks'));
    $PIVOTX['template']->assign('heading', __('Edit or Delete Trackbacks'));

    if (($_GET['uid']!="") && ($_GET['key']!="")) {

        // uid should be numeric. (If it's not, someone is hacking ...)
        if (!is_numeric($_GET['uid'])) {
            echo "uid must be numeric";
            die();
        }

        // Editing a trackback.. Get it from the DB..
        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

        // Check if the user is allowed to edit this entry. It should either be his/her own
        // Entry, or the userlevel should be advanced.
        if ($PIVOTX['session']->currentUsername() != $entry['user']) {
            $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
        }

        if (isset($entry['trackbacks'][$_GET['key']])) {
            $trackback = $entry['trackbacks'][$_GET['key']];
        } else {
            // This should only happen for non-SQL db when editing a trackback from
            // the last trackback screen (or similar functions) which uses fake UIDs.
            foreach ($entry['trackbacks'] as $key => $value) {
                if ($_GET['key'] == makeTrackbackUID($value)) {
                    $trackback = $value;
                    // Setting the key to the array key
                    $_GET['key'] = $key;
                    break;
                }
            }
        }

        $PIVOTX['template']->assign('uid', $_GET['uid']);

        $form = getTrackbackForm();

        // Get the validation result
        $result = $form->validate();

        if ( $result != FORM_OK ) {

            // Put the user values in the form.
            $form->setValues($trackback);
            $PIVOTX['template']->assign("form", $form->fetch());

        } else {

            $val = $form->getValues();

            unset($val['csrfcheck']);

            editTrackback($entry, $_GET['key'], $val);

            // Set a message, show the trackback screen.
            $msg = __('The Trackback was saved!');
            $PIVOTX['messages']->addMessage($msg);

            pageTrackbacks();
            die();

        }


    } else {

        $PIVOTX['messages']->addMessage(__('You have to select an entry in order to view its trackback.'));

        pageTrackbacks();
        die();

    }

    renderTemplate('modal.tpl');

}

/**
 * Display 'Media' page.
 */
function pageMedia() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // TODO: userlevel 1 and 2 may see images, but not delete them..

    fileOperations($PIVOTX['paths']['upload_base_path']);

    getFiles($PIVOTX['paths']['upload_base_path'], $_GET['additionalpath'], $PIVOTX['paths']['upload_base_url']);

    $title = __('Manage Media') . " <small>&raquo; ". basename($PIVOTX['paths']['upload_base_path']) . "/" . $_GET['additionalpath'] . "</small>";
    $PIVOTX['template']->assign('title', $title );

    renderTemplate('fileexplorer.tpl');

}


/**
 * Display 'upload' page.
 */
function pageUpload() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Upload'));
    $PIVOTX['template']->assign('heading', __('Upload Files'));


    renderTemplate('generic.tpl');

}


/**
 * Display 'visitors' page.
 */
function pageVisitors() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();

    // Delete a visitor, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['del'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {
        if ($visitors->delUser($_GET['del'])) {
            $PIVOTX['messages']->addMessage( __('The visitor account has been deleted.'));
        } else {
            $PIVOTX['messages']->addMessage( __('Failed to deleted the visitor account.'));
        }
    }


    $PIVOTX['template']->assign('title', __('Registered Visitors'));
    $PIVOTX['template']->assign('heading', __('View and edit Registered Visitors'));

    $users = $visitors->getUsers();

    $PIVOTX['template']->assign('users', $users);

    renderTemplate('visitors.tpl');

}

/**
 * Display 'Categories' page.
 */
function pageCategories() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', __('Categories'));
    $PIVOTX['template']->assign('heading', __('Create, edit and delete the Categories'));

    // Delete a category, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['del'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {
        $thiscat = $PIVOTX['categories']->getCategory($_GET['del']);
        $PIVOTX['categories']->deleteCategory($_GET['del']);
        $PIVOTX['messages']->addMessage( __('The category was deleted.'));
        $PIVOTX['events']->add('delete_category', intval($_GET['del']), $thiscat['display']);
    }


    $cats = $PIVOTX['categories']->getCategories();

    // Make sure we have a default category..
    if ( ( ($PIVOTX['config']->get('default_category')!="") && ($PIVOTX['categories']->isCategory($PIVOTX['config']->get('default_category'))) ) ||
       $PIVOTX['config']->get('default_category')=="(none)" ) {
        $defcat = $PIVOTX['config']->get('default_category');
    } else {

        $firstcat = each($cats);
        $defcat = $firstcat['value']['name'];
        $PIVOTX['config']->set('default_category', $defcat);
    }

    // Add nicknames to the categories.
    $nicknames = $PIVOTX['users']->getUserNicknames();
    foreach ($cats as $key => $cat) {
        $cats[$key]['nicknames'] = array();
        if (is_array($cat['users'])) {
            foreach ($cat['users'] as $user) {
                $cats[$key]['nicknames'][] = $nicknames[$user];
            }
        }
    }

    $cnt_cats = count($cats);
    for ($i=0; $i < $cnt_cats; $i++) {
        $name = $cats[$i]['name'];
        $cats[$i]['no_of_entries'] = $PIVOTX['db']->get_entries_count(array('cats' => $name));
    }

    $PIVOTX['template']->assign('categories', $cats);
    $PIVOTX['template']->assign('defaultcategory', $defcat);

    renderTemplate('categories.tpl');


}


/**
 * Display 'Weblogs' page.
 */
function pageWeblogs() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Weblogs'));
    $PIVOTX['template']->assign('heading', __('Create, edit and delete Weblogs'));

    // Delete a weblog, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['del'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {
        $PIVOTX['weblogs']->delete($_GET['del']);
        $PIVOTX['messages']->addMessage( __('The weblog has been deleted.'));
        $PIVOTX['events']->add('delete_weblog', "", safeString($_GET['del']));
    }

    // Export the weblog as a Theme file
    if (isset($_GET['export'])) {
        $PIVOTX['weblogs']->export($_GET['export']);
        $PIVOTX['messages']->addMessage( __('The weblog was exported as a Theme file.'));
    }


    // No need to set the weblogs, since they're already set in renderTemplate();

    renderTemplate('weblogs.tpl');

}




/**
 * Display the New Weblog screen.
 *
 *
 */
function pageWeblognew() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('title', __('Create New Weblog'));
    $PIVOTX['template']->assign('backlink', array('page'=>'weblogs', 'text'=>__('Weblogs')));

    $form = getNewWeblogForm();

    // Get the validation result
    $result = $form->validate();

    if ( $result != FORM_OK ) {

        $PIVOTX['template']->assign("form", $form->fetch());

    } else {

        $val = $form->getvalues();

        $weblogname = $PIVOTX['weblogs']->add($_POST['internal'], $_POST['name'], $_POST['theme']);

        $PIVOTX['events']->add('add_weblog', "", $weblogname);

        $_GET['weblog'] = $weblogname;
        pageWeblogedit();

        die();

    }


    renderTemplate('generic.tpl');


}



/**
 * Display 'templates' page.
 */
function pageTemplates() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    fileOperations($PIVOTX['paths']['templates_path']);

    getFiles($PIVOTX['paths']['templates_path'], $_GET['additionalpath'], $PIVOTX['paths']['templates_url']);

    $title = __('Templates') . " <small>&raquo; templates/" . $_GET['additionalpath'] . "</small>";
    $PIVOTX['template']->assign('title', $title );

    renderTemplate('fileexplorer.tpl');

}


/**
 * Display 'configuration' page.
 */
function pageConfiguration() {
    global $form_titles, $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Configuration'));
    //$PIVOTX['template']->assign('heading', __('Edit the Configuration file'));

    $confvalues = $PIVOTX['config']->getConfigArray();

    $form1 = getConfigForm1();
    $form1->setValues($confvalues);

    $form2 = getConfigForm2();
    $form2->setValues($confvalues);

    $form3 = getConfigForm3();
    $form3->setValues($confvalues);

    $form4 = getConfigForm4();
    $form4->setValues($confvalues);

    $form5 = getConfigForm5();
    $form5->setValues($confvalues);

    $form6 = getConfigForm6();
    $form6->setValues($confvalues);

    $form7 = getConfigFormMoblog();
    $form7->setValues($confvalues);

    $form_html[1] = $form1->fetch();
    $form_html[2] = $form2->fetch();
    $form_html[3] = $form3->fetch();
    $form_html[4] = $form4->fetch();
    $form_html[5] = $form5->fetch();
    $form_html[6] = $form6->fetch();
    $form_html[7] = $form7->fetch();

    $form_titles = array(1=>__("Common settings"), 2=>__("Database"), 3=>__("Tags"),
        4=>__("Debug"), 5=>__("File Uploads"), 6=>__("Date / Time"), 7=>__("Moblogging"));

    // Fetch the forms for the enabled Extensions that have registered an
    // Administration screen.
    $PIVOTX['extensions']->executeHook('configuration_add', $form_html);

    $PIVOTX['template']->assign('form_titles', $form_titles);
    $PIVOTX['template']->assign('form_html', $form_html);


    renderTemplate('config.tpl');

}


/**
 * Display 'advancedconfiguration' page.
 */
function pageAdvconfiguration() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Advanced Configuration'));
    $PIVOTX['template']->assign('heading', __('Edit, Add and Delete advanced Configuration options'));

    $advconfig = $PIVOTX['config']->getConfigArray();

    // Make sure all of the values are htmlencoded properly.
    foreach($advconfig as $key=>$value) {
        $advconfig[$key] = htmlentities($value, ENT_COMPAT, "UTF-8");
    }

    $PIVOTX['template']->assign('advconfig', $advconfig );

    renderTemplate('advconfig.tpl');

}



/**
 * Display 'spamprotection' page.
 */
function pageSpamprotection() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Spam Protection'));
    $PIVOTX['template']->assign('heading', __('Overview of the various tools to keep your weblogs spam-free'));

    $PIVOTX['template']->assign('listing', "spamprotection");

    renderTemplate('placeholder.tpl');

}





/**
 * Display 'buildindex' page.
 */
function pageBuildindex() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Rebuild the Index'));
    $PIVOTX['template']->assign('heading', __('Rebuild the index of your Database'));

    @set_time_limit(0);

    // Force the archive index and tag index file to be updated
    @unlink($PIVOTX['paths']['db_path'].'ser-archives.php');

    // Force the tag index to be updated
    @unlink($PIVOTX['paths']['db_path'].'ser_tags.php');
    $dir = dir($PIVOTX['paths']['db_path'].'tagdata/');
    while (false !== ($entry = $dir->read())) {
        if (getExtension($entry)=="cache") {
            unlink($PIVOTX['paths']['db_path'].'tagdata/'.$entry);
        }
    }
    $dir->close();

    // Make a new archive array.
    makeArchiveArray();

    $output = "<p>". __('Now building Index. This may take a short while, so please do not interrupt.') ."<br />\n";

    $output .= "<div id='output'></div>";

    // Much of the hard work is done by the ajaxy function rebuildIndex() in ajaxhelper.php
    $output .= "<script type='text/javascript'>
        jQuery(function($) {
            ajaxRebuildCall('rebuildIndex',0,0);
        });
        </script>
        ";

    $PIVOTX['template']->assign('html', $output);

    renderTemplate('generic.tpl');

}


/**
 * Display 'buildsearchindex' page.
 */
function pageBuildsearchindex() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Rebuild the Search Index'));
    $PIVOTX['template']->assign('heading', __('Rebuild the Searchindex, to allow searching in entries'));

    $output = "<p>". __('Now building Search Index. This may take a short while, so please do not interrupt.')  ."<br />\n";

    $output .= "<div id='output'></div>";

    // Much of the hard work is done by the ajaxy function rebuildSearchIndex() in ajaxhelper.php
    $output .= "<script type='text/javascript'>
        jQuery(function($) {
            ajaxRebuildCall('rebuildSearchIndex',0,0);
        });
        </script>
        ";

    $PIVOTX['template']->assign('html', $output);

    renderTemplate('generic.tpl');

}

/**
 * Display 'buildtagindex' page.
 */
function pageBuildtagindex() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Rebuild the Tag Index'));
    $PIVOTX['template']->assign('heading', __('Rebuild the Tagindex, to display tag clouds and tags below entries'));

    $output = "<p>". __('Now building Tag Index. This may take a short while, so please do not interrupt.')  ."<br />\n";

    $output .= "<div id='output'></div>";

    // Much of the hard work is done by the ajaxy function rebuildTagIndex() in ajaxhelper.php
    $output .= "<script type='text/javascript'>
        jQuery(function($) {
            ajaxRebuildCall('rebuildTagIndex',0,0);
        });
        </script>
        ";

    $PIVOTX['template']->assign('html', $output);

    renderTemplate('generic.tpl');

}


/**
 * Display the 'Backup' page.
 */
function pageBackup() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Backup'));
    $PIVOTX['template']->assign('heading', __('Download a zip file containing your configuration files, templates or entries database'));

    $form_backup_cfg = getBackupCfgForm();
    $form_backup_templates = getBackupTemplatesForm();
    $form_backup_database = getBackupDatabaseForm();
    $form_html = $form_backup_cfg->fetch() . $form_backup_templates->fetch() . $form_backup_database->fetch();
    if ($PIVOTX['config']->get('db_model')=="flat") {
        $form_backup_entries = getBackupEntriesForm();
        $form_html .= $form_backup_entries->fetch();
    } else {
        $form_html .= '<p>'.__('NB! Backup the (entries) database using your ISP control panel or the command line.').'</p>';
    }

    // Which form is used below is irrelevant.
    if ($form_backup_cfg->validate() == FORM_OK) {
        $val = $form_backup_cfg->getvalues();
        if (($val['what'] == 'config') || ($val['what'] == 'entries') ||
                ($val['what'] == 'templates') || ($val['what'] == 'db-directory')) {
            backup($val['what']);
            // The script stops here - the backup starts the download.
        } else {
            debug("Unknown page action");
            return;
        }
    }

    $PIVOTX['template']->assign("form", $form_html);
    $PIVOTX['template']->assign("html", $html);

    renderTemplate('generic.tpl');

}


/**
 * Display the 'Empty Cache' page.
 */
function pageEmptyCache() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Empty Cache'));
    $PIVOTX['template']->assign('heading', __("Clear PivotX's internal cache for stored files."));

    $deletecounter = wipeSmartyCache();

    $html = sprintf(__('deleted %s cache files in %s seconds.'), $deletecounter, timeTaken() );

    $html = '<p>'. $html.'</p>';

    $PIVOTX['template']->assign("html", $html);

    renderTemplate('generic.tpl');

}


/**
 * Display 'fileexplore' page.
 */
function pageFileexplore() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    fileOperations($PIVOTX['paths']['db_path']);

    getFiles($PIVOTX['paths']['db_path'], $_GET['additionalpath'], $PIVOTX['paths']['db_url']);

    $title = __('Explore Database Files') . " <small>&raquo; db/" . $_GET['additionalpath'] . "</small>";
    $PIVOTX['template']->assign('title', $title );

    renderTemplate('fileexplorer.tpl');

}


/**
 * Display 'homeexplore' page.
 */
function pageHomeexplore() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    // TODO: Allow for other home dirs than just '..'


    // For now we assume the home dir is one level higher than pivotx.

    fileOperations($PIVOTX['paths']['home_path']);

    getFiles($PIVOTX['paths']['home_path'], $_GET['additionalpath'], $PIVOTX['paths']['home_url']);

    $title = __('Explore files') . " <small>&raquo; ../" . $_GET['additionalpath'] . "</small>";
    $PIVOTX['template']->assign('title', $title );

    renderTemplate('fileexplorer.tpl');

}


/**
 * Display 'spamconfig' page.
 */
function pageSpamconfig() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Spam configuration'));
    $PIVOTX['template']->assign('heading', __('Configure Spam Protection tools (like HashCash and SpamQuiz).'));


    $form = getSpamConfigForm();
    $result = $form->validate();
    if ( $result == FORM_NOTPOSTED ) {
        $form->setvalues($PIVOTX['config']->getConfigArray());
    } else if ( $result == FORM_HASERRORS ) {
        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));
    } else {
        $PIVOTX['messages']->addMessage(__("Spam configuration saved."));
        $values = $form->getvalues();
        unset($values['csrfcheck']);
        foreach ($values as $key => $value) {
            $PIVOTX['config']->set($key,$value);
        }
        $PIVOTX['config']->save();
    }
    $form->setValues($confvalues);
    $PIVOTX['template']->assign('form', $form->fetch());

    renderTemplate('generic.tpl');

}


/**
 * Display 'ignoreddomains' page.
 */
function pageIgnoreddomains() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Blocked Phrases'));
    $PIVOTX['template']->assign('heading', __('View and Edit the Blocked Phrases to combat spam.'));

    renderTemplate('generic.tpl');

}




/**
 * Display 'ignoreddomainsupdate' page.
 */
function pageIgnoreddomainsupdate() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Update the Global Phrases List'));
    $PIVOTX['template']->assign('heading', __('Update the global phrases list from pivotx.net'));



    renderTemplate('generic.tpl');

}


/**
 * Display 'spamwasher' page.
 */
function pageSpamwasher() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Spam Washer'));
    $PIVOTX['template']->assign('heading', __('Search for spam, and delete all of it from your entries and trackbacks.'));



    renderTemplate('generic.tpl');

}


/**
 * Display 'ipblocks' page.
 */
function pageIpblocks() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('IP blocks'));
    $PIVOTX['template']->assign('heading', __('View and Edit the blocked IP addresses.'));


    renderTemplate('generic.tpl');

}


/**
 * Display 'spamlog' page.
 */
function pageSpamlog() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Spam Log'));
    $PIVOTX['template']->assign('heading', __('View and Reset the Spam Log.'));

    $form = getResetSpamLogForm();
    $result = $form->validate();
    if ($result != FORM_NOTPOSTED) {
        set_spamlog();
        $PIVOTX['messages']->addMessage(__("Spam log reset."));
    }
    $spamlog = get_spamlog();

    $PIVOTX['template']->assign('html', $spamlog);
    $PIVOTX['template']->assign('form', $form->fetch());

    renderTemplate('generic.tpl');

}


/**
 * Display 'administration' page.
 */
function pageAdministration() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Administration'));
    $PIVOTX['template']->assign('heading', __('Overview of Administrative functions'));

    $PIVOTX['template']->assign('listing', "administration");

    renderTemplate('placeholder.tpl');

}


/**
 * Display 'maintenance' page.
 */
function pageMaintenance() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign('title', __('Maintenance'));
    $PIVOTX['template']->assign('heading', __('Perform routine maintenance on PivotX\'s files'));

    $PIVOTX['template']->assign('listing', "maintenance");

    renderTemplate('placeholder.tpl');

}


/**
 * Display 'Users' page.
 */
function pageUsers() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $user_lev = array(
        PIVOTX_UL_NOBODY => __('Inactive user'),
        PIVOTX_UL_MOBLOGGER => __('Moblogger'),
        PIVOTX_UL_NORMAL => __('Normal'),
        PIVOTX_UL_ADVANCED => __('Advanced'),
        PIVOTX_UL_ADMIN => __('Administrator'),
        PIVOTX_UL_SUPERADMIN => __('Superadmin')
    );

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    // Delete a user, but only if we have the right session, to prevent spoofing.
    if ( (isset($_GET['del'])) && ($_GET['pivotxsession']==$_COOKIE['pivotxsession']) ) {
        $user = $PIVOTX['users']->getUser($_GET['del']);
        if (!$user) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['messages']->addMessage(sprintf(__("The user '<tt>%s</tt>' does not exist."),
                htmlspecialchars($_GET['del'])));
            renderTemplate('generic.tpl');
            return;
        }
        // Determine if this user can be deleted
        // - superadmin can delete at all levels.
        // - admin can only delete users with lower userlevel.
        if ( ($currentuser['userlevel'] == PIVOTX_UL_SUPERADMIN) ||
                ($currentuser['userlevel'] > $user['userlevel']) ) {
            $PIVOTX['users']->deleteUser($_GET['del']);
            $PIVOTX['events']->add('delete_user', "", safeString($_GET['del']));
            $PIVOTX['messages']->addMessage(__("The user was deleted"));
        } else {
            debug("Deleting user {$_GET['del']} by {$currentuser['username']} not allowed");
            die();
        }
    }

    $userlist = array();
    $usernames = $PIVOTX['users']->getUsernames();
    foreach ($usernames as $user) {
        $userlist[$user] = $PIVOTX['users']->getUser($user);
        // Determine if this user can be edited
        // - superadmin can edit everyone.
        // - admin can only edit users with lower userlevel.
        // - anyone can edit themselves.
        if ( ($currentuser['userlevel'] == PIVOTX_UL_SUPERADMIN) ||
                ($currentuser['userlevel'] > $userlist[$user]['userlevel']) ||
                ($currentuser['username'] == $user) ) {
            $userlist[$user]['allow_edit'] = true;
        }
        // Transform some keys to more userfriendly values.
        $userlist[$user]['userlevel'] = $user_lev[$userlist[$user]['userlevel']];
        if (!empty($userlist[$user]['lastseen'])) {
            $userlist[$user]['lastseen'] = date("Y-m-d", $userlist[$user]['lastseen']);
        }
    }

    $PIVOTX['template']->assign('title', __('Users'));
    $PIVOTX['template']->assign('heading', __('Create, edit and delete Users'));

    $PIVOTX['template']->assign('users', $userlist);

    renderTemplate('users.tpl');

}

/**
 * Display 'Useredit' page.
 */
function pageUseredit() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $PIVOTX['template']->assign('backlink', array('page'=>'users', 'text'=>__('Users')));
    $PIVOTX['template']->assign('backlink', array('page'=>'users', 'text'=>__('Users')));

    if (!isset($_GET['user'])) {
        $PIVOTX['template']->assign('title', __('Users') . " &raquo; " .__('Create New User'));
    } else {
        $PIVOTX['template']->assign('title', __('Users') . " &raquo; " .__('Edit User'));

        // Get the values for the user and abort if he doesn't exist.
        $user = $PIVOTX['users']->getUser($_GET['user']);
        if (!$user) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['messages']->addMessage(sprintf(__("The user '<tt>%s</tt>' does not exist."),
                htmlspecialchars($_GET['user'])));
            renderTemplate('generic.tpl');
            return;
        }
        // Get the categories the user is allowed to post in.
        $allowed = $PIVOTX['categories']->allowedCategories($user['username']);
    }

    // Make sure the username is lowercase..
    if (!empty($_POST['username'])) {
        $_POST['username'] = strtolower(safeString($_POST['username']));
    }

    $form = getEditUserForm($user);
    $result = $form->validate();

    if ( $result == FORM_NOTPOSTED ) {

        // Put the user values in the form.
        $form->setvalues($user);
        $form->setvalue("categories", $allowed);

    } else if ( $result == FORM_HASERRORS ) {

        // Try again.

        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));

        $val = $form->getvalues();
        $val['username'] = strtolower(safeString($val['username']));

        if (empty($_GET['retry'])) {
            // After we display the Users screen, we need to re-open the dialog,
            // so the errors can be fixed.
            $title = addslashes(__("Edit User"));
            $link = addslashes("index.php?page=useredit&retry=1");
            $vars = makeJsVars($val);
            $script = "<script type=\"text/javascript\">
    jQuery(function($) {
    openDialog(\"$title\", \"$link\", 540, 520 , $vars);
    });</script>";

            $PIVOTX['template']->assign('script', $script);

            pageUsers();

            die();
        } else {

            $PIVOTX['template']->assign("form", $form->fetch());

            renderTemplate('modal.tpl');
            die();

        }


    } else {

        $val = $form->getvalues();
        $val['username'] = strtolower(safeString($val['username']));

        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

        $allcats = $PIVOTX['categories']->getCategorynames();

        // Loop all categories, and Allow/disallow users in the set categories..
        // TODO: Do this after checking if we can edit/create user?
        foreach($allcats as $cat) {
            if(in_array($cat, $val['categories'])){
                // we can post to this cat.
                $PIVOTX['categories']->allowUser($cat, $val['username']);
            } else {
                // we can't post to this cat.
                $PIVOTX['categories']->disallowUser($cat, $val['username']);
            }

        }

        // Save the categories..
        $PIVOTX['categories']->saveCategories();

        // If the user is saving/editing his own settings:
        // - ensure that he doesn't change his userlevel.
        // - update the user in the session immediately.
        if ($currentuser['username'] == $val['username']) {
            $val['userlevel'] = $currentuser['userlevel'];
            $PIVOTX['session']->setUser($val);
        }

        // Now do the actual user-saving.
        if ($user) {

            // Updating an existing user.

            // Determine if this user can be updated
            // - superadmin can edit everyone.
            // - admin can only edit users with lower userlevel.
            // - anyone can edit themselves.
            if ( ($currentuser['userlevel'] == PIVOTX_UL_SUPERADMIN) ||
                    ($currentuser['userlevel'] > $val['userlevel']) ||
                    ($currentuser['username'] == $val['username']) ) {
                $allow_edit = true;
            }

            if (!$allow_edit) {
                debug("Editing user {$user['username']} by {$currentuser['username']} not allowed");
                die();
            }

            // Check if the (new) email or nickname is already taken.
            if (($val['email'] != $user['email']) &&
                    in_array($val['email'], $PIVOTX['users']->getUserEmail() )) {
                $email_taken = true;
                $error = true;
            } elseif (($val['nickname'] != $user['nickname']) &&
                    in_array($val['nickname'], $PIVOTX['users']->getUserNicknames() )) {
                $nickname_taken = true;
                $error = true;
            }

            if ($error) {

                if ($email_taken) {
                    $message = __('Email already in use');
                    $errorfield = 'email';
                } elseif ($nickname_taken) {
                    $message = __('Nickname already in use');
                    $errorfield = 'nickname';
                }

                $PIVOTX['messages']->addMessage($message);

                if (empty($_GET['retry'])) {
                    // After we display the Users screen, we need to re-open the dialog,
                    // so the errors can be fixed.
                    $title = addslashes(__("Edit User"));
                    $link = addslashes("index.php?page=useredit&user={$user['username']}&retry=1");
                    $vars = makeJsVars($val);
                    $script = "<script type=\"text/javascript\">
                    jQuery(function($) {
                    openDialog(\"$title\", \"$link\", 540, 520 , $vars);
                    });</script>";

                    $PIVOTX['template']->assign('script', $script);

                    pageUsers();

                    die();

                } else {

                    $form->seterror($errorfield, $message);

                    $PIVOTX['template']->assign("form", $form->fetch());

                    renderTemplate('modal.tpl');
                    die();

                }

            } else {

                // Update the user..
                unset($val['username']);
                unset($val['csrfcheck']);
                unset($val['categories']);

                $PIVOTX['users']->updateUser($_GET['user'], $val);

                $PIVOTX['events']->add('edit_user', "", safeString($_GET['user']));

                // Remove the modal form and show a message
                $msg = addslashes(sprintf(__('The settings for user %s have been updated.'), $_GET['user']));
                $PIVOTX['messages']->addMessage($msg);

                pageUsers();

                die();

            }

        } else {

            // New user

            unset($val['csrfcheck']);
            unset($val['categories']);

            // Determine if this user can be created
            // - superadmin can create at all levels.
            // - admin can only create users with lower userlevel.
            if ( ($currentuser['userlevel'] == PIVOTX_UL_SUPERADMIN) ||
                    ($currentuser['userlevel'] > $val['userlevel']) ) {
                $userlevel_ok = true;
            } else {
                $error = true;
            }

            // Check if the username, email or nickname is already taken.
            if (in_array($val['username'], $PIVOTX['users']->getUsernames() )) {
                $username_taken = true;
                $error = true;
            } elseif (in_array($val['email'], $PIVOTX['users']->getUserEmail() )) {
                $email_taken = true;
                $error = true;
            } elseif (in_array($val['nickname'], $PIVOTX['users']->getUserNicknames() )) {
                $nickname_taken = true;
                $error = true;
            }

            if ($error) {

                if (!$userlevel_ok) {
                    $message = __('You are not allowed to create users with userlevel administrator or higher');
                    $errorfield = 'userlevel';
                } elseif ($username_taken) {
                    $message = __('Username already in use');
                    $errorfield = 'username';
                } elseif ($email_taken) {
                    $message = __('Email already in use');
                    $errorfield = 'email';
                } elseif ($nickname_taken) {
                    $message = __('Nickname already in use');
                    $errorfield = 'nickname';
                }

                $PIVOTX['messages']->addMessage($message);

                if (empty($_GET['retry'])) {
                    // After we display the Users screen, we need to re-open the dialog,
                    // so the errors can be fixed.
                    $title = addslashes(__("Create New User"));
                    $link = addslashes("index.php?page=useredit&retry=1");
                    $vars = makeJsVars($val);
                    $script = "<script type=\"text/javascript\">
                    jQuery(function($) {
                    openDialog(\"$title\", \"$link\", 540, 520 , $vars);
                    });</script>";

                    $PIVOTX['template']->assign('script', $script);

                    pageUsers();

                    die();

                } else {

                    $form->seterror($errorfield, $message);

                    $PIVOTX['template']->assign("form", $form->fetch());

                    renderTemplate('modal.tpl');
                    die();

                }

            } else {

                // Ok, we can store the new user.
                $PIVOTX['users']->addUser($val);

                // Remove the modal form and show a message
                $msg = addslashes(sprintf(__('The user %s has been created.'), $val['username']));

                $PIVOTX['events']->add('add_user', "", safeString($val['username']));

                $PIVOTX['messages']->addMessage($msg);

                pageUsers();

                die();

            }

        }

    }


    $PIVOTX['template']->assign("form", $form->fetch());

    renderTemplate('modal.tpl');

}


/**
 * Display 'Visitoredit' page.
 */
function pageVisitoredit() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();

    $PIVOTX['template']->assign('title', __('Edit Visitor'));

    if (!($user = $visitors->loadUser($_GET['user']))) {
        // This shouldn't happen, but better safe than sorry
        $PIVOTX['messages']->addMessage(__("No account found for visitor."));
        pageVisitors();
        die();
    }

    $form = getEditVisitorForm();
    $result = $form->validate();

    if ( $result == FORM_NOTPOSTED ) {

        $form->setvalues($user);

    } else if ( $result == FORM_HASERRORS ) {

        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));

    } else {

        $values = $form->getvalues();
        // Can only change email, verified, disabled.
        $user['email'] = $values['email'];
        $user['verified'] = $values['verified'];
        $user['disabled'] = $values['disabled'];
        $visitors->saveUser($user);
        $PIVOTX['messages']->addMessage(__("Visitor account updated."));
        pageVisitors();
        die();
    }

    $PIVOTX['template']->assign("form", $form->fetch());

    renderTemplate('modal.tpl');

}



/**
 * Display 'Categoryedit' page.
 */
function pageCategoryedit() {
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);



    if ($_GET['cat']!="") {

        $cat = $PIVOTX['categories']->getCategory($_GET['cat']);

        $title = __('Categories') . " &raquo; " . __('Edit Category');

    } else {

        $title = __('Categories') . " &raquo; " . __('Create Category');

    }

    $PIVOTX['template']->assign('title', $title);

    $PIVOTX['template']->assign('backlink', array('page'=>'categories', 'text'=>__('Categories')));

    $form = getEditCategoryForm();

    $result = $form->validate();

    if ( $result == FORM_NOTPOSTED ) {

        // Put the user values in the form.
        $form->setvalues($cat);

    } else if ( $result == FORM_HASERRORS ) {

        $PIVOTX['messages']->addMessage(__("Some fields were not correct. Please try again."));

        $val = $form->getvalues();
        $val['username'] = strtolower(safeString($val['username']));

        if (empty($_GET['retry'])) {
            // After we display the Users screen, we need to re-open the dialog,
            // so the errors can be fixed.
            $title = addslashes(__("Edit User"));
            $link = addslashes("index.php?page=categoryedit&retry=1");
            $vars = makeJsVars($val);
            $script = "<script type=\"text/javascript\">
    jQuery(function($) {
    openDialog(\"$title\", \"$link\", 460, 440 , $vars);
    });</script>";

            $PIVOTX['template']->assign('script', $script);

            pageUsers();

            die();
        } else {

            $PIVOTX['template']->assign("form", $form->fetch());

            renderTemplate('modal.tpl');
            die();

        }

    } else {

        $cat = $form->getvalues();

        // New cat, so we need to check if it's not taken yet.
        if ($_GET['cat']=="") {

            // Make sure the 'name' doesn't contain weird stuff.
            $cat['name'] = safeString($cat['name'], true);

            if (in_array($cat['name'], $PIVOTX['categories']->getCategorynames() )) {

                // if 'retry' isn't set, we display the 'categories' page, and call
                // the form again, otherwise if 'retry' is set, it means we're already
                // being called for the second time..
                if (empty($_GET['retry'])) {

                    $title = addslashes($title);
                    $link = addslashes("index.php?page=categoryedit&retry=1");
                    $vars = makeJsVars($cat);
                    $script = "<script type=\"text/javascript\">
jQuery(function($) {
    openDialog(\"$title\", \"$link\", 460, 440 , $vars);
});</script>";

                    $PIVOTX['template']->assign('script', $script);

                    pageCategories();
                    die();

                } else {

                    $form->seterror('name', __('A category by this name already exists'));
                    $PIVOTX['template']->assign("form", $form->fetch());

                    renderTemplate('modal.tpl');
                    die();
                }



            }
        }


        unset($cat['csrfcheck']);

        $PIVOTX['categories']->setCategory($cat['name'], $cat);

        $PIVOTX['events']->add('edit_category', "", safeString($cat['name']));


        // Remove the modal form and show a message
        $msg = addslashes(sprintf(__("The settings for category '%s' have been updated."),$cat['name']));
        $PIVOTX['messages']->addMessage($msg);

        pageCategories();

        die();

    }

    $PIVOTX['template']->assign("form", $form->fetch());

    renderTemplate('modal.tpl');

}

/**
 * Display 'Weblogedit' page.
 */
function pageWeblogedit() {
    /* @var $weblogs Weblogs */
    global $PIVOTX;

    // check if the user has the required userlevel to view this page.
    $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

    $weblog = $PIVOTX['weblogs']->getWeblog($_GET['weblog']);

    $PIVOTX['template']->assign('title', __("Edit Weblog") . " <span>&raquo; " . $weblog['name'] . "</span>");
    // $PIVOTX['template']->assign('heading', __('Create, edit and delete Users'));

    $form1 = getWeblogForm1($_GET['weblog']);
    $form1->setValues($weblog);
    $PIVOTX['template']->assign('form1', $form1->fetch());

    $form2 = getWeblogForm2();
    $form2->setValues($weblog);
    $PIVOTX['template']->assign('form2', $form2->fetch());

    $form3 = getWeblogForm3($_GET['weblog']);
    $form3->setValues($weblog);
    $PIVOTX['template']->assign('form3', $form3->fetch());

    $form4 = getWeblogForm4($_GET['weblog']);
    $form4->setValues($weblog);
    $PIVOTX['template']->assign('form4', $form4->fetch());

    $form5 = getWeblogForm5();
    $form5->setValues($weblog);
    $PIVOTX['template']->assign('form5', $form5->fetch());

    renderTemplate('weblog.tpl');

}


/**
 * Display extension documentation using Textile or Markdown.
 */
function pageDocumentation() {
    global $PIVOTX;

    // Get the filename, extension (markdown or textile) and type (summary or other)    
    $filename = $PIVOTX['paths']['extensions_path'] . $_GET['file'];
    $basename = makeAdminPageLink('documentation') . "&amp;file=" . dirname($_GET['file']);
    $extension = strtolower(getExtension($filename));
    list ($type, $dummy) = explode(".", basename($filename));
        
    if (!file_exists($filename) ||
        ( $extension!="textile" && $extension!="markdown" )) {
        echo "Not a valid filename";
        die();
    }
           
    $source = file_get_contents($filename);
    
    if ($extension=="markdown") {
        $html = pivotxMarkdown($source);
    } else {
        $html = pivotxTextile($source);
    }
    
    // Find the fist <h1>, to use as title.. But, only for full docs..
    if ($type!="summary") {
        preg_match_all('/<h1>(.*)<\/h1>/i', $html, $match);
        if (!empty($match[1][0])) {
            $PIVOTX['template']->assign('title', strip_tags($match[1][0]));
        }
    }

    // Find links to other pages in the docs, and rewrite them, so that they're parsed into correct links
    $html = preg_replace('/a href="([a-z0-9_-]*)\.(markdown|textile)"/', 'a href="'.$basename.'/\\1.\\2"', $html);

    $PIVOTX['template']->assign('html', $html);

    // Check for 'toc.markdown' or 'toc.textile', and insert those, if present..
    $tocfilename = dirname($filename)."/toc.".$extension;
    if ( file_exists($tocfilename)) {
        $toc = file_get_contents($tocfilename);
            
        if ($extension=="markdown") {
            $tochtml = pivotxMarkdown($toc);
        } else {
            $tochtml = pivotxTextile($toc);
        }        
        
        // Find links to other pages in the docs, and rewrite them, so that they're parsed into correct links
        $tochtml = preg_replace('/a href="([a-z0-9_-]*)\.(markdown|textile)"/', 'a href="'.$basename.'/\\1.\\2"', $tochtml);
                   
        $PIVOTX['template']->assign('toc', $tochtml);
        
    }
    
    renderTemplate('documentation.tpl');
    
}

/**
 * Pages for the Mobile (Iphone / Android) Interface
 */

/**
 * Log out of Pivotx
 */
function pagem_logout() {
    global $PIVOTX;

    $PIVOTX['session']->logout();

    $PIVOTX['template']->assign("title", __('Logout'));
    $PIVOTX['template']->assign("active", "");

    $PIVOTX['messages']->addMessage( __('You are now logged out of PivotX'));

    $html .= sprintf("<a class='large awesome' href='index.php?page=login'>%s</a>", __("Ok"));

    $PIVOTX['template']->assign('html', $html);

    renderTemplate('mobile/generic.tpl');


}


/**
 * Show the mobile dashboard
 */
function pagem_dashboard() {
    global $PIVOTX;

    // check if the user is logged in.
    if (!$PIVOTX['session']->isLoggedIn()) {
        header("Location: index.php?page=login");
        die();
    }

    // Get the 5 latest entries..
    $entries = $PIVOTX['db']->read_entries(array('show'=>5, 'user'=>$filter_user, 'order'=>'desc'));

    // Mark the entries as editable or not..
    foreach ($entries as $key => $entry) {
        $entries[$key]['editable'] = $PIVOTX['users']->allowEdit('entry', $entry['user']);
    }

    // Get the 3 latest pages..
    $pages = $PIVOTX['pages']->getLatestPages(3);

    // Mark the pages as editable or not..
    foreach ($pages as $key => $page) {
        $pages[$key]['editable'] = $PIVOTX['users']->allowEdit('page', $page['user']);
    }

    // Get the 6 latest comments.. (non-moderated get priority)
    require_once(dirname(__FILE__).'/modules/module_comments.php');
    $modcomments = getModerationQueue();

    $latestcomments = $comments = $PIVOTX['db']->read_latestcomments(array(
        'amount'=>10,
        'cats'=>'',
        'count'=>15,
        'moderated'=>1
    ));

    $latestcomments = array_merge($modcomments, $latestcomments);
    $latestcomments = array_slice($latestcomments, 0, 6);

    // Check for blocked IPs
    $blocklist = new IPBlock();
    foreach($latestcomments as $key=>$latestcomment) {
        $latestcomments[$key]['blocked'] = $blocklist->isBlocked($latestcomment["ip"]);
    }
    // Check for warnings to display
    $PIVOTX['messages']->checkWarnings();

    $PIVOTX['template']->assign('news', $news);
    $PIVOTX['template']->assign('entries', $entries);
    $PIVOTX['template']->assign('pages', $pages);
    $PIVOTX['template']->assign('comments', $latestcomments);
    $PIVOTX['template']->assign('users', $PIVOTX['users']->getUserNicknames() );


    $PIVOTX['template']->assign("title", __('Dashboard'));
    $PIVOTX['template']->assign("active", "");

    renderTemplate('mobile/home.tpl');

}


/**
 * Show a list of pages, for the mobile interface.
 */
function pagem_pages() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // Get the 20 latest pages..
    $pages = $PIVOTX['pages']->getLatestPages(20);

    // Mark the pages as editable or not..
    foreach ($pages as $key => $page) {
        $pages[$key]['editable'] = $PIVOTX['users']->allowEdit('page', $page['user']);
    }

    $PIVOTX['template']->assign('pages', $pages);

    $PIVOTX['template']->assign("title", __('Pages'));
    $PIVOTX['template']->assign("active", "pages");

    renderTemplate('mobile/pages.tpl');

}


/**
 * Show a list of entries, for the mobile interface.
 */
function pagem_entries() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // Get the 20 latest entries..
    $entries = $PIVOTX['db']->read_entries(array('show'=>20, 'user'=>$filter_user, 'order'=>'desc'));

    // Mark the entries as editable or not..
    foreach ($entries as $key => $entry) {
        $entries[$key]['editable'] = $PIVOTX['users']->allowEdit('entry', $entry['user']);
    }

    $PIVOTX['template']->assign('entries', $entries);

    $PIVOTX['template']->assign("title", __('Entries'));
    $PIVOTX['template']->assign("active", "entries");

    renderTemplate('mobile/entries.tpl');

}


/**
 * Edit an entry in the mobile interface.
 */
function pagem_editentry() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    if ($_GET['uid']=="") {
        $PIVOTX['template']->assign('title', __('New Entry'));
    } else {
        $PIVOTX['template']->assign('title', __('Edit Entry'));
    }

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    if (!empty($_GET['uid'])) {

        // Editing an entry.. Get it from the DB..

        $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

        $PIVOTX['events']->add('edit_entry', intval($_GET['uid']), $entry['title']);

        if ( !$PIVOTX['users']->allowEdit('entry', $entry['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to edit this entry.")."</p>");
            renderTemplate('mobile/generic.tpl');
            return;
        }

        // Make sure we tweak the </textarea> in the intro or body text (since
        // that would break our own textarea, if we didn't)..
        $entry['introduction'] = str_replace("<textarea", "&lt;textarea", $entry['introduction']);
        $entry['introduction'] = str_replace("</textarea", "&lt;/textarea", $entry['introduction']);
        $entry['body'] = str_replace("<textarea", "&lt;textarea", $entry['body']);
        $entry['body'] = str_replace("</textarea", "&lt;/textarea", $entry['body']);

        // If the entry was written in  'markdown', and is now
        // being edited in the mobile editor, we must convert it.
        if ( $entry['convert_lb']=="3" ) {
            $entry['introduction'] = parse_intro_or_body($entry['introduction'], false, $entry['convert_lb'], true);
            $entry['body'] = parse_intro_or_body($entry['body'], false, $entry['convert_lb'], true);
        }

        // Otherwise, if the entry was written in 'Plain XHTML' or 'WYSIWYG', and is now
        // being edited, there is not much more we
        // can do than strip out the <p> and <br/> tags to replace with linebreaks.
        if ( ($entry['convert_lb']=="0" || $entry['convert_lb']=="5")  ) {
            $entry['introduction'] = unparse_intro_or_body($entry['introduction']);
            $entry['body'] = unparse_intro_or_body($entry['body']);
        }

        list($entry['link'], $entry['link_end']) = explode($entry['uri'], $entry['link']);

    } else {

        // Make a new entry.

        $entry = array();

        if ($PIVOTX['config']->get('default_category')!="") {
            $entry['category'] = array($PIVOTX['config']->get('default_category'));
        }
        if ($PIVOTX['config']->get('allow_comments')!="") {
            $entry['allow_comments'] = $PIVOTX['config']->get('allow_comments');
        }

        if ($PIVOTX['config']->get('default_post_status')!="") {
            $entry['status'] = $PIVOTX['config']->get('default_post_status');
        }

        $entry['user'] = $currentuser['username'];

        $entry['link'] = makeFileLink(array('uri' => 'xxx', 'date'=>date("Y-m-d-H-i-s")), "", "");
        list($entry['link'], $entry['link_end']) = explode('xxx', $entry['link']);

    }

    // Make sure we only show the allowed categories.. Superadmins can always
    // see and use all categories..
    $categories = $PIVOTX['categories']->getCategories();

    if ($currentuser['userlevel'] < PIVOTX_UL_SUPERADMIN) {
        $allowedcats = $PIVOTX['categories']->allowedCategories($currentuser['username']);
        foreach ($categories as $key => $value) {
            if (!in_array($value['name'], $allowedcats)) {
                unset($categories[$key]);
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD']=="GET") {

        // Ignore URI if we aren't using mod_rewrite.
        if ($PIVOTX['config']->get('mod_rewrite') == 0) {
            unset($entry['uri']);
        }

        // Show the screen..
        $PIVOTX['template']->assign('entry', $entry);
        $PIVOTX['template']->assign('categories', $categories);
        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign('users', $PIVOTX['users']->getUsers());
        $PIVOTX['template']->assign('entryuser', $PIVOTX['users']->getUser($entry['user']));

        $PIVOTX['template']->assign("active", "entries");

        renderTemplate('mobile/editentry.tpl');

    } else {

        if ($_POST['code']!=$_GET['uid']) {
            $PIVOTX['events']->add('fatal_error', intval($_GET['uid']), "Tried to fake editing an entry");
            echo "Code is wrong! B0rk!";
            die();
        }

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        //var_dump($entry);

        // Sanitize the $_POST into an entry we can store
        $entry = sanitizePostedEntry($entry);
        $entry['convert_lb']="2"; // Make sure it's processed as 'Textile'

        //var_dump($entry);
        // die();

        $PIVOTX['extensions']->executeHook('entry_edit_beforesave', $entry);

        $entry = $PIVOTX['db']->set_entry($entry);

        if ($PIVOTX['db']->save_entry(TRUE)) {
            $PIVOTX['messages']->addMessage( sprintf( __('Your entry "%s" was successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' ) );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave', $entry);
        } else {
            $PIVOTX['messages']->addMessage( sprintf( __('Your entry "%s" was NOT successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' ) );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave_failed', $entry);
        }

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        pagem_Entries();

    }




}



/**
 * Edit a page in the mobile interface.
 */
function pagem_editpage() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    if ($_GET['uid']=="") {
        $PIVOTX['template']->assign('title', __('Write a new Page'));
    } else {
        $PIVOTX['template']->assign('title', __('Edit Page'));
    }

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    if (!empty($_GET['uid'])) {

        // Editing a page.. Get it from the DB..
        $page = $PIVOTX['pages']->getPage($_GET['uid']);

        $PIVOTX['events']->add('edit_entry', intval($_GET['uid']), $entry['title']);

        if ( !$PIVOTX['users']->allowEdit('page', $page['user']) ) {
            $PIVOTX['template']->assign('heading', __("PivotX encountered an error"));
            $PIVOTX['template']->assign('html',
                "<p>".__("You are not allowed to edit this entry.")."</p>");
            renderTemplate('mobile/generic.tpl');
            return;
        }

        // Make sure we tweak the </textarea> in the intro or body text (since
        // that would break our own textarea, if we didn't)..
        $page['introduction'] = str_replace("<textarea", "&lt;textarea", $page['introduction']);
        $page['introduction'] = str_replace("</textarea", "&lt;/textarea", $page['introduction']);
        $page['body'] = str_replace("<textarea", "&lt;textarea", $page['body']);
        $page['body'] = str_replace("</textarea", "&lt;/textarea", $page['body']);

        // If the entry was written in  'markdown', and is now
        // being edited in the mobile editor, we must convert it.
        if ( $page['convert_lb']=="3" ) {
            $page['introduction'] = parse_intro_or_body($page['introduction'], false, $page['convert_lb'], true);
            $page['body'] = parse_intro_or_body($page['body'], false, $page['convert_lb'], true);
        }

        // Otherwise, if the entry was written in 'Plain XHTML' or 'WYSIWYG', and is now
        // being edited, there is not much more we
        // can do than strip out the <p> and <br/> tags to replace with linebreaks.
        if ( ($page['convert_lb']=="0" || $page['convert_lb']=="5")  ) {
            $page['introduction'] = unparse_intro_or_body($page['introduction']);
            $page['body'] = unparse_intro_or_body($page['body']);
        }

        list($page['link'], $page['link_end']) = explode($page['uri'], $page['link']);

    } else {

        // Make a new entry.

        $page = array();

        if ($_GET['chapter']!="") {
            $page['chapter'] = intval($_GET['chapter']);
        }

        $user = $PIVOTX['session']->currentUser();
        $page['user']= $user['username'];

        $page['sortorder']=10;

        if ($PIVOTX['config']->get('default_post_status')!="") {
            $page['status'] = $PIVOTX['config']->get('default_post_status');
        }

        $page['link'] = makePagelink("xxx");
        list($page['link'],$page['link_end']) = explode('xxx', $page['link']);

    }

    $templates = templateOptions(templateList(), 'page', array('_sub_', '_aux_'));

    if ($_SERVER['REQUEST_METHOD']=="GET") {

        // Show the screen..
        // Show the screen..
        $PIVOTX['template']->assign('templates', $templates);
        $PIVOTX['template']->assign('page', $page);
        $PIVOTX['template']->assign('chapters', $PIVOTX['pages']->getIndex());
        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign('users', $PIVOTX['users']->getUsers());
        $PIVOTX['template']->assign('pageuser', $PIVOTX['users']->getUser($entry['user']));

        $PIVOTX['template']->assign("active", "pages");

        renderTemplate('mobile/editpage.tpl');

    } else {

        if ($_POST['code']!=$_GET['uid']) {
            $PIVOTX['events']->add('fatal_error', intval($_GET['uid']), "Tried to fake editing an entry");
            echo "Code is wrong! B0rk!";
            die();
        }

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);



        // Sanitize the $_POST into an entry we can store
        $page = sanitizePostedPage($page);
        $page['convert_lb']="2"; // Make sure it's processed as 'Textile'

        $PIVOTX['extensions']->executeHook('page_edit_beforesave', $page);
        $new_id = $PIVOTX['pages']->savePage($page);
        $PIVOTX['extensions']->executeHook('page_edit_aftersave', $page);

        $PIVOTX['messages']->addMessage( sprintf( __('Your page "%s" was successfully saved.'),
                '<em>'.trimText( $page['title'],25 ).'</em>' ) );

        // Remove the frontpages and entrypages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // Update the search index for this page, but only if we're using flat files.
        if ($PIVOTX['db']->db_type == "flat") {
            $page['code'] = $page['uid'] = $new_id;
            updateSearchIndex($page, 'p');
        }

        pagem_Pages();

    }




}







function pagem_comments() {
    global $PIVOTX;

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // Get the 6 latest comments.. (non-moderated get priority)
    require_once(dirname(__FILE__).'/modules/module_comments.php');
    $modcomments = getModerationQueue();

    $comments = $PIVOTX['db']->read_latestcomments(array(
        'amount'=>30,
        'cats'=>'',
        'count'=>30,
        'moderated'=>1
    ));

    $comments = array_merge($modcomments, $comments);
    $comments = array_slice($comments, 0, 20);

    // Check for blocked IPs
    $blocklist = new IPBlock();
    foreach($comments as $key=>$comment) {
        $comments[$key]['blocked'] = $blocklist->isBlocked($comment["ip"]);
    }

    $PIVOTX['template']->assign('comments', $comments);


    $PIVOTX['template']->assign("title", __('Comments'));
    $PIVOTX['template']->assign("active", "comments");

    renderTemplate('mobile/comments.tpl');

}



function pagem_editcomment() {
    global $PIVOTX;

    require_once(dirname(__FILE__).'/modules/module_comments.php');

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // uid should be numeric. (If it's not, someone is hacking ...)
    if (!is_numeric($_GET['uid'])) {
        echo "uid must be numeric";
        die();
    }

    $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

    // Check if the user is allowed to edit this entry. It should either be his/her own
    // Entry, or the userlevel should be advanced.
    if ($PIVOTX['session']->currentUsername() != $entry['user']) {
        $PIVOTX['session']->minLevel(PIVOTX_UL_ADVANCED);
    }

    if (isset($entry['comments'][$_GET['key']])) {
        $comment = $entry['comments'][$_GET['key']];
    } else {
        // This should only happen for non-SQL db when editing a comment from
        // the latest comments screen (or similar functions) which uses fake UIDs.
        foreach ($entry['comments'] as $key => $value) {
            if ($_GET['key'] == makeCommentUID($value)) {
                $comment = $value;
                // Setting the key to the array key
                $_GET['key'] = $key;
                break;
            }
        }
    }


    if (count($_POST)<4) {

        $PIVOTX['template']->assign('uid', $_GET['uid']);
        $PIVOTX['template']->assign('comment', $comment);

        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign("title", __('Edit Comment'));
        $PIVOTX['template']->assign("active", "comments");

        renderTemplate('mobile/editcomment.tpl');

    } else {

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        // Make sure 'moderate' is set..
        $_POST['moderate'] = getDefault($_POST['moderate'], 0);

        // Merge the $_POST into the comment..
        foreach ($comment as $key=>$value) {
            if (isset($_POST[$key])) {
                $comment[$key] = $_POST[$key];
            }
        }

        editComment($entry, $_GET['key'], $comment);

        $PIVOTX['messages']->addMessage( __('The Comment was saved!') );

        pagem_comments();

    }

}




function pagem_moderatecomment() {
    global $PIVOTX;

    require_once(dirname(__FILE__).'/modules/module_comments.php');

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    // uid should be numeric. (If it's not, someone is hacking ...)
    if (!is_numeric($_GET['uid'])) {
        echo "uid must be numeric";
        die();
    }

    $entry = $PIVOTX['db']->read_entry(intval($_GET['uid']));

    if (isset($entry['comments'][$_GET['key']])) {
        $comment = $entry['comments'][$_GET['key']];
    } else {
        // This should only happen for non-SQL db when editing a comment from
        // the latest comments screen (or similar functions) which uses fake UIDs.
        foreach ($entry['comments'] as $key => $value) {
            if ($_GET['key'] == makeCommentUID($value)) {
                $comment = $value;
                // Setting the key to the array key
                $_GET['key'] = $key;
                break;
            }
        }
    }

    // Flip the moderation, and save it again..
    $comment['moderate'] = 1 - intval($comment['moderate']);
    editComment($entry, $_GET['key'], $comment);

    if ($comment['moderate']) {
        $PIVOTX['messages']->addMessage( __('The Comment was disapproved!') );
    } else {
        $PIVOTX['messages']->addMessage( __('The Comment was approved!') );
    }

    pagem_comments();

}





/**
 * Display the mobile 'about' page.
 */
function pagem_about() {
    global $PIVOTX, $codename, $version;

    if (!empty($codename)) {
        $build = sprintf(" - %s: <span>%s</span>", $version, $codename);
    } else {
        $build = sprintf(" - %s", $version);
    }

    $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

    $PIVOTX['template']->assign("title", __("About PivotX") . $build);
    $PIVOTX['template']->assign("active", "other");

    renderTemplate('mobile/about.tpl');

}



/**
 * Page for the Bookmarklet.
 */
function pageBookmarklet() {
    global $PIVOTX;

    // check if the user is logged in.
    if (!$PIVOTX['session']->isLoggedIn()) {
        pageLogin('small');
        die();
    }


    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

    $entry = array();

    if ($PIVOTX['config']->get('default_category')!="") {
        $entry['category'] = array($PIVOTX['config']->get('default_category'));
    }


    if ($PIVOTX['config']->get('default_post_status')!="") {
        $entry['status'] = $PIVOTX['config']->get('default_post_status');
    }

    $entry['link'] = makeFileLink(array('date'=>date("Y-m-d-H-i-s")), "", "");
    $entry['publish_date'] = date("Y-m-d-H-i-s", strtotime('+1 month'));


    // Set some things, based on referring page..
    $entry['introduction'] = "";

    // Execute the hook, if present..
    $PIVOTX['extensions']->executeHook('begin_bookmarklet', $entry);

    if (!empty($_GET['selection'])) {
        $entry['introduction'] .= "<p>&nbsp;</p>\n\n<blockquote>\n". $_GET['selection'] . "\n</blockquote>\n\n";
    }

    if (!empty($_GET['title'])) {
        $entry['title'] = sanitizeTitle($_GET['title']);
        $entry['introduction'] .= sprintf("<p><a href='%s'>%s</a></p>", $_GET['url'], $entry['title'] );
    } else {
        $entry['introduction'] .= sprintf("<p><a href='%s'>%s</a></p>", $_GET['url'], __("link") );
    }

    $PIVOTX['extensions']->executeHook('end_bookmarklet', $entry );

    // Make sure we only show the allowed categories.. Superadmins can always
    // see and use all categories..
    $categories = $PIVOTX['categories']->getCategories();

    if ($currentuser['userlevel'] < PIVOTX_UL_SUPERADMIN) {
        $allowedcats = $PIVOTX['categories']->allowedCategories($currentuser['username']);
        foreach ($categories as $key => $value) {
            if (!in_array($value['name'], $allowedcats)) {
                unset($categories[$key]);
            }
        }
    }

    if (!isset($_POST['title'])) {

        // Show the screen..
        $PIVOTX['template']->assign('entry', $entry);
        $PIVOTX['template']->assign('categories', $categories);
        $PIVOTX['template']->assign('pivotxsession', $PIVOTX['session']->getCSRF());
        $PIVOTX['template']->assign('entryuser', $PIVOTX['users']->getUser($entry['user']));
        renderTemplate('bookmarklet_entry.tpl');

    } else {

        // Make sure the current user is properly logged in, and that the request is legitimate
        $PIVOTX['session']->checkCSRF($_POST['pivotxsession']);

        // Sanitize the $_POST into an entry we can store
        $entry = sanitizePostedEntry($entry);

        if ($PIVOTX['config']->get('allow_comments')!="") {
            $entry['allow_comments'] = $PIVOTX['config']->get('allow_comments');
        }
        $entry['user'] = $currentuser['username'];

        $PIVOTX['extensions']->executeHook('entry_edit_beforesave', $entry);

        $entry = $PIVOTX['db']->set_entry($entry);

        if ($PIVOTX['db']->save_entry(TRUE)) {
            $message = sprintf( __('Your entry "%s" was successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave', $entry);
        } else {
            $message = sprintf( __('Your entry "%s" was NOT successfully saved.'),
                '<em>'.trimText( $entry['title'],25 ).'</em>' );
            $PIVOTX['extensions']->executeHook('entry_edit_aftersave_failed', $entry);
        }

        // Remove the compiled/parsed pages from the cache.
        if($PIVOTX['config']->get('smarty_cache')){
            $PIVOTX['template']->clear_cache();
        }

        // Show the screen..
        $PIVOTX['template']->assign('message', $message);
        $PIVOTX['template']->assign('uid', $PIVOTX['db']->entry['uid']);
        renderTemplate('bookmarklet_menu.tpl');

    }

}


?>
