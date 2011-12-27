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



function getLoginForm($template="normal") {

    $form = new Form("login", "index.php?page=login", __("Login"));

    $form->add( array(
        'type' => 'text',
        'name' => 'username',
        'label' => __('Username'),
        'value' => '',
        'error' => __('That\'s not a proper username!'),
        'size' => 20,
        'isrequired' => 1,
        'text' => makeJtip(__('Username'), __('Usernames can only contain lowercase alphanumeric characters (a-z, 0-9) and underscores (_).')),
        'validation' => 'string|minlen=2|maxlen=20'
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'password',
        'label' => __('Password'),
        'error' => __('Please give a proper password!'),
        'size' => 20,
        'isrequired' => 1,
        'validation' => 'string|minlen=4|maxlen=20'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'stayloggedin',
        'label' => __('Stay logged in?')
    ));

    $form->add( array(
        'type' => 'submit'
    ));

    // Adding a bit of javascript to switch between login and reset password mode.
    if ($template=="normal") {
        $form->add( array(
            'type' => 'custom',
            'text' => "<tr><td colspan='3'><hr  noshade='noshade' size='1' /></td></tr>
            <tr><td colspan='3'><h3 style='margin: 0;'>" . __('Lost your password?') . "</h3></td></tr>
            <script type='text/javascript'>
            jQuery(function($) {
                // Attach event for 'cancel' button.
                $('#login_resetpassword').click(function(event, data, formatted) {
                    if ($('#login_resetpassword:checked').val()) {
                        $('#password').attr('readonly', 'readonly').addClass('dim').val('******');
                        $('#login_stayloggedin').attr('disabled', 'disabled');
                        $('form#login').find('button').children('span.text').html('" . __("Send password") . "');
                        $('#login_stayloggedin')
                    } else {
                        $('#password').attr('readonly', '').removeClass('dim').val('');
                        $('#login_stayloggedin').attr('disabled', '');
                        $('form#login').find('button').children('span.text').html('" . __("Login") . "');
                    }
                });

            });
            </script>\n\n"
        ));

        $form->add( array(
            'type' => 'checkbox',
            'name' => 'resetpassword',
            'label' => __('Reset my password'),
            'text' => makeJtip(__('Reset my password'),
                __('If you\'ve forgotten your password, check the box, enter your username and PivotX will send you an email with a link to reset your password.')),
        ));

    }

    $form->add( array(
        'type' => 'hidden',
        'name' => 'returnto',
        'value' => '',
        'validation' => ''
    ));


    $form->add( array(
        'type' => 'hidden',
        'name' => 'template',
        'value' => $template,
        'validation' => ''
    ));


    $form->use_javascript(true);

    // Setting some styles, for when we're using the mobile version..
    if ($template=="mobile") {
        $form->use_javascript(false);
    }

    return $form;


}

function getBackupTemplatesForm() {

    $form = new Form("backuptemplatesform", "", __("Backup templates"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => 'hidden',
        'name' => 'what',
        'value' => 'templates'
    ));

    return $form;
}

function getBackupEntriesForm() {

    $form = new Form("backupentriesform", "", __("Backup all your entries"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => 'hidden',
        'name' => 'what',
        'value' => 'entries'
    ));

    return $form;
}

function getBackupDatabaseForm() {

    $form = new Form("backupdatabaseform", "", __("Backup all files in the db folder"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => 'hidden',
        'name' => 'what',
        'value' => 'db-directory'
    ));

    return $form;
}

function getBackupCfgForm() {

    $form = new Form("backupcfgform", "", __("Backup configuration files"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => 'hidden',
        'name' => 'what',
        'value' => 'config'
    ));

    return $form;
}

function getResetSpamLogForm() {

    $form = new Form("resetspamlog", "", __("Reset Spam Log"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    return $form;
}

function getCommentForm() {

    $form = new Form("editcomment", "", __("Save Comment"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'name',
        'label' => __('Name'),
        'error' => __('Error'),
        'size' => 30,
        'value' => '',
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'email',
        'label' => __('Email'),
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 0
        // 'validation' => 'ifany|email|string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'url',
        'label' => __('URL'),
        'error' => __('Error'),
        'size' => 40,
        'isrequired' => 0
        // 'validation' => 'ifany|string|minlen=2|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'date',
        'label' => __('Date'),
        'error' => __('Error'),
        'size' => 20,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'comment',
        'label' => __('Comment'),
        'error' => __('Error'),
        'size' => 20,
        'cols' => 50,
        'rows' => 8,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ip',
        'label' => __('IP-address'),
        'error' => __('Error'),
        'size' => 20,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'useragent',
        'label' => __('User agent'),
        'error' => __('Error'),
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2'
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'registered',
        'label' => __('Registered user'),
        'text' => makeJtip(__('Registered user'), __('Determines if this comment is displayed like it was by registered user. (Regardless of whether the comment was actually made by a registered user.)')),
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'notify',
        'label' => __('Notify'),
        'text' => makeJtip(__('Notify'), __('If set to \'yes\', the visitor will be notified by email of any subsequent comments to this entry.')),
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'discreet',
        'label' => __('Show discreetly'),
        'text' => makeJtip(__('Show discreetly'), __('If set to \'yes\', the comment will be shown without the accompanying email-address.')),
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'moderate',
        'label' => __('Moderate Comment'),
        'text' => makeJtip(__('Moderate Comment'), __('If set to \'yes\', this comment will not be shown on the site, until it has been approved in the moderation queue.')),
    ));

    $form->use_javascript(true);

    return $form;

}


function getTrackbackForm() {

    $form = new Form("edittrackback", "", __("Save Trackback"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'name',
        'label' => __('Blog Name'),
        'error' => __('Error'),
        'size' => 30,
        'value' => '',
        'validation' => 'string|minlen=2|maxlen=200'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'title',
        'label' => __('Entry title'),
        'error' => __('Error'),
        'size' => 30,
        'value' => '',
        'validation' => 'string|minlen=2|maxlen=200'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'url',
        'label' => __('URL'),
        'error' => __('Error'),
        'size' => 40,
        // 'validation' => 'ifany|string|minlen=2|maxlen=80'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'ip',
        'label' => __('IP-address'),
        'error' => __('Error'),
        'size' => 20,
        // 'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'date',
        'label' => __('Date'),
        'error' => __('Error'),
        'size' => 20,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'excerpt',
        'label' => __('Excerpt'),
        'error' => __('Error'),
        'size' => 20,
        'cols' => 50,
        'rows' => 8,
        'validation' => 'string|minlen=2|maxlen=4000'
    ));

    $form->use_javascript(true);

    return $form;


}


function addDBSelectionToForm(&$form) {
    $form->add( array(
        'type' => 'select',
        'name' => 'db_model',
        'label' => __('Database Model'),
        'value' => 'mysql',
        'error' => __('Error'),
        'firstoption' => __('Select'),
        'options' => array(
            'flat' => __("Flat Files"),
            'mysql' => "MySQL",
            //'sqlite' => "SQLite",
            //'postgresql' => "PostgreSQL"
        ),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => makeJtip(__('Database Model'), __('Select which type of Database to use. Flat Files will work on almost every platform. If your server is capable of using databases, the performance of PivotX will be best if you use MySQL or SQLite.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'db_username',
        'label' => __('Username'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2',
        'text' => makeJtip(__('Username'), __('Your MySQL username'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'db_password',
        'label' => __('Password'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'extra' => 'autocomplete="off"',
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2', // we have to allow for 'empty passwords' with MySQL.
        'text' => makeJtip(__('Password'), __('Your MySQL password'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'db_databasename',
        'label' => __('Database Name'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2',
        'text' => makeJtip(__('Database Name'), __('The name of the MySQL database in which you wish to store the information'))
    ));

    $form->add( array(
        'type' => 'info',
        'text' => __('Advanced database settings')
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'db_hostname',
        'label' => __('Hostname'),
        'value' => 'localhost',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2',
        'text' => makeJtip(__('Hostname'), __('Your MySQL hostname. If you do not know, this is most likely "localhost".'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'db_prefix',
        'label' => __('Table Prefix'),
        'value' => 'pivotx_',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2',
        'text' => makeJtip(__('Table Prefix'), __('The prefix to use for the database tables. By changing this, you can run multiple installations of PivotX from one MySQL database. If you don\'t intend to do so (yet), just leave this set to "pivotx_".'))
    ));
}



function getSetupUserForm() {
    global $PIVOTX;

    $form = new Form("setupuser", "", __("Continue"));

    // The setup form shouldn't have the CSRF check since the pivotxsession
    // cookie isn't set before after logging in.

    $form->add(array(
        'type' => 'custom',
        'text' => "<tr><td colspan='2'><p>" . __("Please provide the following information, to set up the first user.") . "<p></td></tr>",
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'username',
        'label' => __('Username'),
        'value' => '',
        'error' => __('That\'s not a proper username!'),
        'size' => 20,
        'isrequired' => 1,
        'text' => makeJtip(__('Username'), __('Usernames can only contain lowercase alphanumeric characters (a-z, 0-9) and underscores (_).')),
        'validation' => 'string|minlen=2|maxlen=20|safestring'
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'pass1',
        'label' => __('Password'),
        'error' => __('Please give a proper password!'),
        'size' => 20,
        'isrequired' => 1,
        'extra' => 'autocomplete="off"',
        'validation' => 'string|minlen=4|maxlen=20'
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'pass2',
        'label' => __('Password (confirm)'),
        'error' => __('The passwords do not match!'),
        'size' => 20,
        'isrequired' => 1,
        'extra' => 'autocomplete="off"',
        'validation' => 'sameas=pass1'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'email',
        'label' => __('Email'),
        'value' => '',
        'error' => __('That\'s not a proper email address!'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|email'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'nickname',
        'label' => __('Nickname'),
        'value' => '',
        'error' => __('That\'s not a proper Nickname!'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'language',
        'label' => __('Default language'),
        'value' => '',
        'error' => __('Please select a language!'),
        'firstoption' => __('Default language'),
        'options' => $PIVOTX['languages']->getLangs(),
        'isrequired' => 1,
        'validation' => 'any'
    ));


    // If we know the server supports MySQL, we allow the user to select it as DB model
    if (function_exists('mysql_get_client_info')) {
        $mysqlversion = mysql_get_client_info();
        if ($mysqlversion > $minrequiredmysql) {

            $form->add(array(
                'type' => 'hr'
            ));

            $form->add(array(
                'type' => 'info',
                'text' => wordwrap(__("PivotX detected that your webserver supports MySQL databases.") ." " .
                    __("If you have a MySQL user and database, specify them below.") . " " .
                    __("If you do not have these, either ask your hosting provider or select the 'Flat Files' model."),
                    80, "<br />\n")
            ));

            addDBSelectionToForm($form);

            // Add a bit of javascript to disable the form-fields for MySQL stuff,
            // When the user selects flat files.
            $form->add( array(
                'type' => 'custom',
                'text' => "<script type='text/javascript'>
                jQuery(function($) {
                    $('#db_model').change( function() {
                        if ( $('#db_model').val() == 'mysql') {
                            $('#db_username').attr('readonly', '').removeClass('dim');
                            $('#db_password').attr('readonly', '').removeClass('dim');
                            $('#db_databasename').attr('readonly', '').removeClass('dim');
                            $('#db_hostname').attr('readonly', '').removeClass('dim');
                            $('#db_prefix').attr('readonly', '').removeClass('dim');
                        } else {
                            $('#db_username').attr('readonly', 'readonly').addClass('dim');
                            $('#db_password').attr('readonly', 'readonly').addClass('dim');
                            $('#db_databasename').attr('readonly', 'readonly').addClass('dim');
                            $('#db_hostname').attr('readonly', 'readonly').addClass('dim');
                            $('#db_prefix').attr('readonly', 'readonly').addClass('dim');
                        }
                    });
                });
                </script>\n\n"
            ));


        }
    }

    $form->use_javascript(true);

    return $form;


}



function getMyInfoForm() {
    global $PIVOTX;

    $form = new Form("edituser", "", __("Save"));

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'text_readonly',
        'name' => 'username',
        'label' => __('Username'),
        'value' => '',
        'size' => 20,
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'pass1',
        'label' => __('Password'),
        'error' => __('Please give a proper password!'),
        'value' => '******',
        'extra' => 'autocomplete="off"',
        'size' => 20,
        'text' => makeJtip(__('Password'), __('Password must be at least 4 letters long, and it can\'t be the same as the username.')),
        'validation' => 'ifany|string|minlen=4|maxlen=20'
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'pass2',
        'label' => __('Password (confirm)'),
        'error' => __('Passwords do not match'),
        'value' => '******',
        'extra' => 'autocomplete="off"',
        'size' => 20,
        'validation' => 'sameas=pass1'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'email',
        'label' => __('Email'),
        'value' => '',
        'error' => __('That\'s not a valid email address'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|email'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'nickname',
        'label' => __('Nickname'),
        'value' => '',
        'error' => __('That\'s not a proper Nickname!'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'image_select',
        'name' => 'image',
        'label' => __('Image'),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|string|minlen=2|maxlen=100'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'language',
        'label' => __('Language'),
        'value' => '',
        'error' => __('You must select a language!'),
        'firstoption' => __('Language'),
        'options' => $PIVOTX['languages']->getLangs(),
        'isrequired' => 1,
        'validation' => 'any'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'text_processing',
        'label' => __('Text editor'),
        'value' => '',
        'text' => makeJtip(__('Text editor'), __("Determines the default text processing, when a user is using Writing a new entry. For most users 'Wysiwyg editor' will be the easiest option. 'Plain XHTML' is for those who like to edit raw XHTML, while 'Convert Linebreaks' does nothing more than change linebreaks to a &amp;lt;br /&amp;gt;-tag. <a href='http://www.textism.com/tools/textile/' target='_blank'>Textile</a> and <a href='http://daringfireball.net/projects/markdown/' target='_blank'>Markdown</a> are both powerful, yet easy to learn markup styles.")),
        'error' => __('Error'),
        'options' => array(
               5 => __('Wysiwyg'),
               0 => __('None (plain XHTML)'),
               1 => __('XHTML, Convert Linebreaks to &lt;br /&gt;'),
               2 => __('Textile'),
               3 => __('Markdown'),
               4 => __('Markdown and Smartypants'))
    ));

    $form->add( array(
        'type' => 'info',
        'name' => 'lastseen',
        'text' => __("User last seen on") . ": " . date("d-m-Y H:i", $_SESSION['user']['lastseen'])
    ));

    $form->use_javascript(true);

    return $form;


}




function getEditUserForm($user) {
    global $PIVOTX;

    if (!isset($_GET['user'])) {
        $save = __('Create New User');
    } else {
        $save = __('Edit User');
    }

    $form = new Form("edituser", "", $save);

    // No border for forms in modal windows.
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    if (!isset($_GET['user'])) {

        // New user

        $form->add( array(
            'type' => 'text',
            'name' => 'username',
            'label' => __('Username'),
            'value' => '',
            'error' => __('That\'s not a proper username!'),
            'size' => 20,
            'isrequired' => 1,
            'text' => makeJtip(__('Username'), __('Usernames can only contain lowercase alphanumeric characters (a-z, 0-9) and underscores (_).')),
            'validation' => 'string|minlen=2|maxlen=20|safestring',
            'extra' => "onKeyUp=\"setSafename('username','username');\" onChange=\"setSafename('username','username');\""
        ));

        $form->add( array(
            'type' => 'password',
            'name' => 'pass1',
            'label' => __('Password'),
            'error' => __('Please give a proper password!'),
            'value' => '',
            'size' => 20,
            'isrequired' => 1,
            'extra' => 'autocomplete="off"',
            'text' => makeJtip(__('Password'), __('Password must be at least 4 letters long, and it can\'t be the same as the username.')),
            'validation' => 'ifany|string|minlen=4|maxlen=20'
        ));

        $form->add( array(
            'type' => 'password',
            'name' => 'pass2',
            'label' => __('Password (confirm)'),
            'error' => __('Passwords do not match'),
            'value' => '',
            'size' => 20,
            'isrequired' => 1,
            'extra' => 'autocomplete="off"',
            'validation' => 'sameas=pass1'
        ));


    } else {

        // Edit user

        $form->add( array(
            'type' => 'text_readonly',
            'name' => 'username',
            'label' => __('Username'),
            'value' => '',
            'size' => 20,
        ));

        $form->add( array(
            'type' => 'password',
            'name' => 'pass1',
            'label' => __('Password'),
            'error' => __('Please give a proper password!'),
            'value' => '******',
            'size' => 20,
            'extra' => 'autocomplete="off"',
            'isrequired' => 1,
            'text' => makeJtip(__('Password'), __('Password must be at least 4 letters long, and it can\'t be the same as the username.')),
            'validation' => 'ifany|string|minlen=4|maxlen=20'
        ));

        $form->add( array(
            'type' => 'password',
            'name' => 'pass2',
            'label' => __('Password (confirm)'),
            'error' => __('Passwords do not match'),
            'value' => '******',
            'size' => 20,
            'extra' => 'autocomplete="off"',
            'isrequired' => 1,
            'validation' => 'sameas=pass1'
        ));


    }

    $user_lev = array(
        PIVOTX_UL_NOBODY => __('Inactive user'),
        PIVOTX_UL_MOBLOGGER => __('Moblogger'),
        PIVOTX_UL_NORMAL => __('Normal'),
        PIVOTX_UL_ADVANCED => __('Advanced'),
        PIVOTX_UL_ADMIN => __('Administrator'),
        PIVOTX_UL_SUPERADMIN => __('Superadmin')
    );

    $form->add( array(
        'type' => 'select',
        'name' => 'userlevel',
        'label' => __('Userlevel'),
        'value' => '',
        'error' => __('Error'),
        'firstoption' => __('Userlevel'),
        'options' => $user_lev,
        'isrequired' => 1,
        'validation' => 'any',
        'text' => makeJtip(__('Userlevel'), __('Userlevel will determine what kind of actions this user can perfom in PivotX.')),
    ));

    $allcats = $PIVOTX['categories']->getCategories();

    // Make an array where the keys are the same as the values
    foreach($allcats as $cat) {
        $catoptions[$cat['name']] = sprintf("%s (%s)", $cat['display'], $cat['name']);
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'categories',
        'label' => __('Allowed Categories'),
        'value' => '',
        'options' => $catoptions,
        'multiple' => true,
        'text' => makeJtip(__('Allowed Categories'), __('This user is allowed to post entries in the selected categories')),
        'size' => 10,
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'email',
        'label' => __('Email'),
        'value' => '',
        'error' => __('That\'s not a valid email address'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|email'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'nickname',
        'label' => __('Nickname'),
        'value' => '',
        'error' => __('That\'s not a proper Nickname!'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));

    $form->add( array(
        'type' => 'image_select',
        'name' => 'image',
        'label' => __('Image'),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2|maxlen=100'
    ));



    $form->add( array(
        'type' => 'select',
        'name' => 'language',
        'label' => __('Language'),
        'value' => $PIVOTX['config']->get('deflang'),
        'error' => __('You must select a language!'),
        'firstoption' => __('Language'),
        'options' => $PIVOTX['languages']->getLangs(),
        'isrequired' => 1,
        'validation' => 'any'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'text_processing',
        'label' => __('Text editor'),
        'value' => '',
        'text' => makeJtip(__('Text editor'), __("Determines the default text processing, when a user is using Writing a new entry. For most users 'Wysiwyg editor' will be the easiest option. 'Plain XHTML' is for those who like to edit raw XHTML, while 'Convert Linebreaks' does nothing more than change linebreaks to a &amp;lt;br /&amp;gt;-tag. <a href='http://www.textism.com/tools/textile/' target='_blank'>Textile</a> and <a href='http://daringfireball.net/projects/markdown/' target='_blank'>Markdown</a> are both powerful, yet easy to learn markup styles.")),
        'error' => __('Error'),
        'options' => array(
               5 => __('Wysiwyg'),
               0 => __('None (plain XHTML)'),
               1 => __('XHTML, Convert Linebreaks to &lt;br /&gt;'),
               2 => __('Textile'),
               3 => __('Markdown'),
               4 => __('Markdown and Smartypants'))
    ));

    if (isset($_GET['user'])) {
        $lastseen = '';
        if (!empty($user['lastseen'])) {
            $lastseen = date("Y-m-d H:i", $user['lastseen']);
        }
        $form->add( array(
            'type' => 'info',
            'name' => 'lastseen',
            'text' => __("Last seen on") . ": " . $lastseen
        ));
    }

    $form->use_javascript(true);

    return $form;


}


function getEditVisitorForm() {
    global $PIVOTX;

    // We only allow changing the email, verified and disabled status.
    $form = new Form("editvisitor", "", __('Edit Visitor'));

    // No border for forms in modal windows.
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important; width: 100%;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));


    $form->add( array(
        'type' => 'text_readonly',
        'name' => 'name',
        'label' => __('Username'),
        'size' => 30,
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'email',
        'label' => __('Email'),
        'error' => __('That\'s not a valid email address'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|email'
    ));

    $form->add( array(
        'type' => 'text_readonly',
        'name' => 'url',
        'label' => __('URL'),
        'size' => 30,
    ));

    $form->add( array(
        'type' => 'text_readonly',
        'name' => 'last_login',
        'label' => __('Last Login'),
        'size' => 10,
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'verified',
        'label' => __('Verified'),
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'disabled',
        'label' => __('Disabled'),
    ));

    $form->use_javascript(true);

    return $form;

}




function getEditCategoryForm() {
    global $PIVOTX;

    if (!isset($_GET['cat'])) {
        $save = __('Create Category');
    } else {
        $save = __('Edit Category');
    }

    $form = new Form("editcategory", "", $save);

    // No border for forms in modal windows.
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important; width: 100%;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));



    if (!isset($_GET['cat'])) {

        $form->add( array(
            'type' => 'text',
            'name' => 'display',
            'label' => __('Display Name'),
            'error' => __('Error'),
            'size' => 25,
            'isrequired' => 1,
            'text' => makeJtip(__('Display Name'), __('The name that the category is represented by in the weblog.')),
            'validation' => 'string|minlen=2|maxlen=40',
            'class' => "xl",
            'extra' => "onKeyUp=\"setSafename('display','name');\" onChange=\"setSafename('display','name');\""
        ));

        // New category, we can edit the name
        $form->add( array(
            'type' => 'text',
           'name' => 'name',
           'label' => __('Name'),
           'value' => '',
           'size' => 30,
           'isrequired' => 1,
           'text' => makeJtip(__('Name'), __('This category needs a name.')),
           'validation' => 'string|minlen=2|maxlen=40',
           'extra' => "onKeyUp=\"setSafename('name','name');\" onChange=\"setSafename('name','name');\""
        ));

    } else {

        $form->add( array(
            'type' => 'text',
            'name' => 'display',
            'label' => __('Display Name'),
            'error' => __('Error'),
            'size' => 26,
            'isrequired' => 1,
            'text' => makeJtip(__('Display Name'), __('The name that the category is represented by in the weblog.')),
            'validation' => 'string|minlen=2|maxlen=40',
            'class' => "xl"
        ));

        // Edit category, we can't edit the name
        $form->add( array(
            'type' => 'text_readonly',
           'name' => 'name',
           'label' => __('Name'),
           'value' => '',
           'size' => 30,
        ));
    }



    foreach ($PIVOTX['users']->getUsers() as $key=>$user) {
        $options[ $user['username'] ] = sprintf("%s (%s)", $user['nickname'], $user['username']);
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'users',
        'label' => __('Users'),
        'value' => '',
        'options' => $options,
        'multiple' => true,
        'text' => makeJtip(__('Users'), __('Select the Users that you would like to give permission to post in this category')),
        'size' => 10
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'order',
        'label' => __('Sorting Order'),
        'value' => '100',
        'error' => __('Error'),
        'text' => makeJtip(__('Sorting Order'), __('Categories with a lower sorting order will appear higher in the list. If you keep all the numbers the same, they will be sorted alphabetically.')),
        'size' => 10,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=999999'
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'hidden',
        'label' => __('Hidden Category'),
        'text' => makeJtip(__('Hidden Category'), __('If set to "Yes", this category will be hidden in archive listings. (Applies only to live pages.)')),
    ));


    $form->use_javascript(true);

    return $form;


}




function getEditChapterForm() {


    $form = new Form("editchapter", "", __("Save"));

    // No border for forms in modal windows.
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important; width: 100%;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'chaptername',
        'label' => __('Chapter name'),
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 1,
        'text' => makeJtip(__('Chapter name'), __('The name of this chapter.')),
        'validation' => 'string|minlen=2|maxlen=40'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'description',
        'label' => __('Description'),
        'error' => __('Error'),
        'size' => 50,
        'text' => makeJtip(__('Description'), __('A short description of this chapter.')),
    ));




    $form->add( array(
        'type' => 'text',
        'name' => 'sortorder',
        'label' => __('Order'),
        'value' => '',
        'error' => __('Error'),
        'text' => makeJtip(__('Order'), __('The sorting order determines the order in which the Chapters are listed.')),
        'size' => 10,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=999999'
    ));



    $form->use_javascript(true);

    return $form;


}



function getNewWeblogForm() {
    global $PIVOTX;

    $form = new Form("newweblog", "", __("Save"));

    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<fieldset style="display: none">
%hidden_fields%
</fieldset>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important; width: 750px;">
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'name',
        'label' => __('Weblog Name'),
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40',
        'extra' => "onKeyUp=\"setSafename('name','internal');\" onChange=\"setSafename('name','internal');\""
    ));

    // include the weblog's internal name
    $form->add( array(
        'type' => "text_readonly",
        'name' => "internal",
        'label' => __('Internal Name'),
        'size' => 30,
        'text' => makeJtip(__('Internal Name'), __('The internal name can consist of only lowercase letters, numbers and underscore (a-z,0-9,_). While the Weblog Name can be changed later on, the Internal Name will always stay the same.'))
    ));


    $options = array(
           "blank" => "<strong>".__('Start from scratch')."</strong>",
        );



    $themes = themeList();

    foreach($themes as $themename) {

        $html = "";

        $imgname = str_replace(".theme", ".jpg", $themename);

        if(file_exists($imgname)) {
            // In case an absolute path is used, we replace the file system path with the corresponding URL.
            $imgname = str_replace($PIVOTX['paths']['pivotx_path'], $PIVOTX['paths']['pivotx_url'], $imgname);
            $html = sprintf("<img src='%s' width='200' height='133' alt='screenshot' align='left' style='border: 1px solid #666; margin-right: 10px;' />",
                $imgname );
        }

        $theme = loadSerialize($themename);

        $html .= sprintf("<strong>%s</strong><br /><br />%s", $theme['name'], $theme['payoff']);

        $options[$themename] = $html;

    }


    $form->add( array(
        'type' => "radio",
        'name' => "theme",
        'label' => __('Theme'),
        'options' => $options,
        'text' => makeJtip(__('Theme'), __('Select the theme you\'d like to base your weblog on. All options can be changed later on.'))
    ));




    return $form;

}


function getWeblogForm1($weblogname) {
    global $PIVOTX;

    $form = new Form("weblog1", "", __("Save"));

    // No border and no 'submit' for this form:
    // TODO: Think about accessibility / non-js users!
    $form->html['start'] = <<< EOM
<form enctype='multipart/form-data' name='%name%' id='%name%' action="%action%" method='post'>
<fieldset style="display: none">
%hidden_fields%
</fieldset>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";


    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    // include the weblog's internal name
    $form->add( array(
        'type' => "hidden",
        'name' => "internalname",
        'value' => $weblogname
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='2'><h3>".__('General Settings')."</h3></td></tr>"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'name',
        'label' => __('Weblog Name'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=40'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'payoff',
        'label' => __('Payoff'),
        'value' => '',
        'error' => __('Error'),
        'size' => 60,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2|maxlen=80',
        'text' => makeJtip(__('Payoff'), __('The Payoff can be used as a subtitle or a short description of your weblog'))
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'language',
        'label' => __('Language'),
        'value' => '',
        'error' => __('You must select a language!'),
        'firstoption' => __('Language'),
        'options' => $PIVOTX['languages']->getLangs(),
        'isrequired' => 1,
        'validation' => 'any',
        'text' => makeJtip(__('Language'), __('The Language determines in what language the dates and numbers will be output.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'read_more',
        'label' => __('"Read More" Text'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2|maxlen=80',
        'text' => makeJtip(__('Filename'), __('The text that is used to indicate that there is more text in this entry than is shown on the front page. If you leave this blank, PivotX will use the default as defined by the language settings.'))
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='3'><hr noshade='noshade' size='1' /></td></tr>"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'site_url',
        'label' => __('URL to Weblog'),
        'value' => '',
        'error' => __('Error'),
        'size' => 30,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2|maxlen=80',
        'text' => makeJtip(__('URL to Weblog'), __('Leave this field blank, unless you know what you are doing. If left empty, PivotX will automatically determine the URL of your weblog, which is usually the best option. Normally this setting is only used if you want one directory for each weblog.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'sortorder',
        'label' => __('Sorting order'),
        'value' => '',
        'error' => __('Error'),
        'size' => 10,
        'validation' => 'integer|min=1|max=10000',
        'text' => makeJtip(__('Sorting order'), __('The sorting order is used to determine the order of the weblogs.'))
    ));

    $form->use_javascript(true);

    return $form;

}



function getWeblogForm2() {

    $form = new Form("weblog2", "", __("Save"));

    // No border and no 'submit' for this form:
    // TODO: Think about accessibility / non-js users!
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='2'><h3>".__('Templates')."</h3></td></tr>"
    ));

    $templates= templateList();

    $templateoptions = templateOptions($templates, 'front', array('_sub_', '_aux_'));

    $form->add( array(
        'type' => 'select',
        'name' => 'front_template',
        'label' => __('Frontpage Template'),
        'value' => '',
        'options' => $templateoptions,
        'text' => makeJtip(__('Frontpage Template'), __('The Template which determines the layout of the index page of this weblog.'))
    ));


    $templateoptions = templateOptions($templates, 'archive', array('_sub_', '_aux_'));

    $form->add( array(
        'type' => 'select',
        'name' => 'archivepage_template',
        'label' => __('Archivepage Template'),
        'value' => '',
        'options' => $templateoptions,
        'text' => makeJtip(__('Frontpage Template'), __('The Template which determines the layout of your archives. This can be the same as "Frontpage Template".'))
    ));


    $templateoptions = templateOptions($templates, 'entry', array('_sub_', '_aux_'));

    $form->add( array(
        'type' => 'select',
        'name' => 'entry_template',
        'label' => __('Entrypage Template'),
        'value' => '',
        'options' => $templateoptions,
        'text' => makeJtip(__('Entrypage Template'), __('The Template which determines the layout of single entries.'))
    ));

    $templateoptions = templateOptions($templates, array('search','extra'), array('_sub_', '_aux_'));

    $form->add( array(
        'type' => 'select',
        'name' => 'extra_template',
        'label' => __('Extra Template'),
        'value' => '',
        'options' => $templateoptions,
        'text' => makeJtip(__('Extra Template'), __('The Template that defines how a search, tag or other special page will look like.'))
    ));

    $templateoptions = templateOptions($templates, 'page', array('_sub_', '_aux_'));

    $form->add( array(
        'type' => 'select',
        'name' => 'page_template',
        'label' => __('Page Template'),
        'value' => '',
        'options' => $templateoptions,
        'text' => makeJtip(__('Page Template'), __('The Template that defines how a page will look like if you haven\'t specified a template for it.'))
    ));

    $form->use_javascript(true);

    return $form;

}






function getWeblogForm3($weblogname) {
    global $PIVOTX;

    $form = new Form("weblog3", "", __("Save"));

    // No border and no 'submit' for this form:
    // TODO: Think about accessibility / non-js users!
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    $weblog = $PIVOTX['weblogs']->getWeblog($weblogname);

    $subweblogs= $PIVOTX['weblogs']->getSubweblogs($weblogname);

    $allcats = $PIVOTX['categories']->getCategories();

    // Make an array where the keys are the same as the values
    foreach($allcats as $cat) {
        $catoptions[$cat['name']] = sprintf("%s (%s)", $cat['display'], $cat['name']);
    }

    $templates= templateList();
    $templateoptions = templateOptions($templates, '_sub', array('_aux_', 'frontpage', 'entrypage', 'archivepage', 'searchpage'));

    // We use a counter to add lines between the subweblogs.
    $counter=0;

    foreach ($subweblogs as $key) {

        $key = trim($key);

        if ($counter>0) {
            $form->add( array(
                'type' => 'custom',
                'text' => "<tr><td colspan='3'><hr noshade='noshade' size='1' /></td></tr>"
            ));
        }
        $counter++;

        $form->add( array(
            'type' => 'custom',
            'text' => "<tr><td colspan='2'><h3>" . sprintf(__("Subweblog %s"), $key) . '</h3></td></tr>'

        ));


        $form->add( array(
            'type' => 'text',
            'name' => "$key#num_entries",
            'label' => __('Number of Entries'),
            'value' => 0 + $weblog['sub_weblog'][$key]['num_entries'],
            'error' => __('Error'),
            'size' => 10,
            'isrequired' => 1,
            'validation' => 'integer|min=1|max=200',
            'text' => makeJtip(__('Number of Entries'), __('The Number of entries in this subweblog that will be shown on the frontpage.'))
        ));

        $form->add( array(
            'type' => 'text',
            'name' => "$key#offset",
            'label' => __('Offset'),
            'value' => 0 + $weblog['sub_weblog'][$key]['offset'],
            'error' => __('Error'),
            'size' => 10,
            'isrequired' => 1,
            'validation' => 'integer|min=0|max=200',
            'text' => makeJtip(__('Offset'), __('If Offset is set to a number, that amount of entries will be skipped when generating the page. You can use this to make a "Previous entries" list, for example.'))
        ));



        $form->add( array(
            'type' => 'select',
            'name' => "$key#categories",
            'label' => __('Categories'),
            'value' => $weblog['sub_weblog'][$key]['categories'],
            'options' => $catoptions,
            'multiple' => true,
            'text' => makeJtip(__('Categories'), __('Publish these categories')),
            'size' => 10,
        ));


    }

    $form->use_javascript(true);

    return $form;

}



function getWeblogForm4() {

    $form = new Form("weblog4", "", __("Save"));

    // No border and no 'submit' for this form:
    // TODO: Think about accessibility / non-js users!
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));


    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='2'><h3>".__('RSS and Atom Configuration')."</h3></td></tr>"
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'rss',
        'label' => __('Generate Feeds'),
        'text' => makeJtip(__('Generate Feeds'), __('This determines whether or not pivot will automatically generate an RSS and an Atom feed for this weblog.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "rss_url",
        'label' => __('RSS URL'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=4',
        'text' => makeJtip(__('RSS URL'), __('If you use a service like FeedBurner, you can give the link to your FeedBurner feed here. The link will be used in the RSS badge and in the autodiscovery link on this weblog. Leave this blank to use PivotX\'s Feed, or specify a full URL.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "atom_url",
        'label' => __('Atom URL'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=4',
        'text' => makeJtip(__('Atom URL'), __('If you use a service like FeedBurner, you can give the link to your FeedBurner feed here. The link will be used in the Atom badge and in the autodiscovery link on this weblog. Leave this blank to use PivotX\'s Feed, or specify a full URL.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'rss_full',
        'label' => __('Create Full Feeds'),
        'text' => makeJtip(__('Create Full Feeds'), __('Determines whether PivotX creates full Atom and RSS feeds. If set to "no" PivotX will create feeds that just contains short descriptions, thereby making your feeds less useful.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => "rss_link",
        'label' => __('Feed Link'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=4',
        'text' => makeJtip(__('Feed Link'), __('The link to send with the Feed, to point to the main page. If you leave this blank, PivotX will send the weblog\'s index as link.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "rss_img",
        'label' => __('Feed Image'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=4',
        'text' => makeJtip(__('Feed Image'), __('You can specify an image to send with the Feed. Some feed readers will display this image along with your feed. Leave this blank, or specify a full URL.'))
    ));

    $form->use_javascript(true);

     return $form;

}





function getWeblogForm5() {

    $form = new Form("weblog5", "", __("Save"));

    // No border and no 'submit' for this form:
    // TODO: Think about accessibility / non-js users!
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    // Place the tooltips to the right of the textarea's, not below them.
    $form->html['textarea'] = <<< EOM
    <tr>
        <td valign="top">
            <label for="%name%">%label% %isrequired%</label>
        </td>
        <td valign="top">
            <textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
           %error%
        </td>
        <td valign="top">
            %text%
        </td>
    </tr>
EOM;


    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='2'><h3>".__('Commenting Settings')."</h3></td></tr>"

    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'comment_sendmail',
        'label' => __('Send Mail?'),
        'text' => makeJtip(__('Send Mail?'), __('After a comment has been placed, mail can be sent to maintainers of this weblog.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "comment_emailto",
        'label' => __('Mail to'),
        'error' => __('Error'),
        'size' => 50,
        'validation' => 'email|minlen=1|maxlen=80',
        'text' => makeJtip(__('Mail to'), __('Specify the email address(es) to whom mail will be sent. Separate multiple addresses with a comma.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'comment_texttolinks',
        'label' => __('Text to links'),
        'text' => makeJtip(__('Text to links'), __('Define whether typed urls and email addresses will be made clickable.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "comment_wrap",
        'label' => __('Wrap comments after'),
        'error' => __('Error'),
        'size' => 10,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=500',
        'text' => makeJtip(__('Wrap comments after'), __('To prevent long strings of characters from breaking your layout, text will be wrapped after the specified number of characters.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'comment_pop',
        'label' => __('Comments Popup?'),
        'text' => makeJtip(__('Comments Popup?'), __('Define whether the comments page (or \'single entry\') will be shown in a popup window, or in the original browser window.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'comment_width',
        'label' => __('Width of Popup'),
        'error' => __('Error'),
        'size' => 10,
        'validation' => 'integer|min=100|max=2000',
        'text' => makeJtip(__('Height of Width'), __('The width (in pixels) of the comments popup.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'comment_height',
        'label' => __('Height of Popup'),
        'error' => __('Error'),
        'size' => 10,
        'validation' => 'integer|min=100|max=2000',
        'text' => makeJtip(__('Height of Popup'), __('The height (in pixels) of the comments popup.'))
    ));

    /* -- Commented out until this is fully implemented

    $form->add( array(
        'type' => 'text',
        'name' => 'comment_reply',
        'label' => __('Format of "reply .."'),
        'text' => makeJtip(__('Format of "reply .."'), __('This determines the formatting of the link that visitors can use to reply on a specific comment.')),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=500'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "comment_forward",
        'label' => __('Format of "reply by .."'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80',
        'text' => makeJtip(__('Format of "reply by .."'), __('This determines the formatting of the text that is displayed when the comment is replied by another comment.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => "comment_backward",
        'label' => __('Format of "reply on .."'),
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=80',
        'text' => makeJtip(__('Format of "reply on .."'), __('This determines the formatting of the text that is displayed when the comment is a reply on another comment.'))
    ));
    */

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'comment_textile',
        'label' => __('Allow Textile?'),
        'text' => makeJtip(__('Allow Textile'), __('If this is set to "Yes", visitors are allowed to use <a href="http://www.textism.com/tools/textile/" target="_blank">Textile</a> in their comments.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'emoticons',
        'label' => __('Allow Emoticons?'),
        'text' => makeJtip(__('Allow Emoticons'), __('If this is set to "Yes", visitors are allowed to use emoticons, which are displayed as graphics.'))
    ));

    $form->use_javascript(true);

    return $form;

}




function getConfigForm1() {
    global $PIVOTX;

    $form = new Form("config1", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'sitename',
        'label' => __('Site Name'),
        'value' => '',
        'error' => __('That\'s not a proper sitename!'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=2|maxlen=60'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'sitedescription',
        'label' => __('Site description'),
        'text' => makeJtip(__('Site description'), __('A short description of the website. Maximum length is 400 characters.')),
        'value' => '',
        'error' => __('Error'),
        'rows' => 4,
        'cols' => 53,
        'validation' => 'maxlen=400'
    ));

    // Make the list of weblogs and pages for 'root' and '404' select boxes ..
    $root_options = array();
    $page404_options = array();

    $webloglist = $PIVOTX['weblogs']->getWeblogs();

    // Iterate through the weblogs..
    foreach($webloglist as $key => $weblog) {
        $root_options[ "w:".$key ] = __("Weblog"). " '" . $weblog['name'] ."'";
    }

    $pagelist = $PIVOTX['pages']->getIndex();

    // Iterate through the pages:
    foreach($pagelist as $chapter) {
        foreach ($chapter['pages'] as $page) {
            $root_options[ "p:".$page['uri'] ] = __("Page") . " '" . $page['title'] ."'";
            $page404_options[ $page['uri'] ] = __("Page") . " '" . $page['title'] ."'";
        }
    }

    // Get the offline setting in its simplest form
    $offline_online = '1';
    if (is_file($PIVOTX['paths']['db_path'].'ser_offline.php')) {
        $offline_online = '';
    }


    $form->add( array(
        'type' => 'select',
        'name' => 'root',
        'label' => __('Site Homepage'),
        'value' => '',
        'error' => __('Please select an option'),
        'firstoption' => __('Site Homepage'),
        'options' => $root_options,
        'isrequired' => 1,
        'validation' => 'any',
        'text' => makeJtip(__('Site Homepage'), __('This determines the page that is shown to visitors of the site, if they go to the front page.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'favicon',
        'label' => __('Site Favicon'),
        'value' => '',
        'error' => __('That\'s not a proper filename!'),
        'size' => 50,
        'validation' => 'maxlen=180',
        'text' => makeJtip(__('Site Favicon'), __('This determines the favicon that is used on the website. Use an absolute path to the image\'s filename for best results.'))

    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'language',
        'label' => __('Default language'),
        'value' => '',
        'error' => __('Please select a language!'),
        'firstoption' => __('Default language'),
        'options' => $PIVOTX['languages']->getLangs(),
        'isrequired' => 1,
        'validation' => 'any'
    ));


/* Commented out until we have ported self-registration from Pivot.
    $form->add( array(
        'type' => 'checkbox',
        'name' => 'selfreg',
        'label' => __('Allow self-registration'),
        'text' => makeJtip(__('Allow self-registration'), __('Setting this to yes enables people to register as (normal) users and hence post entries. (This is not a "comment" user.)'))
    ));
*/


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'xmlrpc',
        'label' => __('Allow XML-RPC'),
        'text' => makeJtip(__('Allow XML-RPC'), __('Setting this to yes enables you to post to your blog from a desktop blog application (using the MetaWeblog API).'))
    ));

    $options = array(
               '0' => __('No'),
               '1' => __('Yes, like /archive/2005/04/28/title-of-entry'),
               '2' => __('Yes, like /archive/2005-04-28/title-of-entry'),
               '3' => __('Yes, like /entry/1234'),
               '4' => __('Yes, like /entry/1234/title-of-entry'),
               '5' => __('Yes, like /2005/04/28/title-of-entry'),
               '6' => __('Yes, like /2005-04-28/title-of-entry')
            );

    foreach($options as $key => $option) {
        $options[$key] = str_replace("2005/04/28", date("Y/m/d"), $options[$key]);
        $options[$key] = str_replace("2005-04-28", date("Y-m-d"), $options[$key]);
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'mod_rewrite',
        'label' => __('Use Mod_rewrite'),
        'value' => '',
        'text' => makeJtip(__('Use Mod_rewrite'), __('If you use Apache\'s Mod_rewrite option, PivotX will make URLs like www.example.org/archive/2008/05/30/nice-weather, instead of www.example.org/index.php?e=134. Not all servers support this, so please read the <a href="http://book.pivotx.net/page/1-14/">Using Apache\'s Mod_rewrite</a> section in the PivotX book about this.')),
        'error' => __('Error'),
        'options' => $options
    ));


    $form->add( array(
        'type' => 'select',
        'name' => '404page',
        'label' => __('Site \'Not Found\' page (404)'),
        'value' => '',
        'error' => __('Please select an option'),
        'firstoption' => __('Default 404'),
        'options' => $page404_options,
        'isrequired' => 1,
        'validation' => 'any',
        'text' => makeJtip(__('Site \'Not Found\' page (404)'), __('This page is displayed when the visitor requests a page on the site that does not exist. Leave this blank to display PivotX\'s default 404 page.'))
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'allow_comments',
        'label' => __('Allow comments by default'),
        'value' => '',
        'text' => makeJtip(__('Allow comments by default'), __('Determine whether entries are set to allow comments or not.'))

    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'moderate_comments',
        'label' => __('Moderate comments'),
        'value' => '',
        'text' => makeJtip(__('Moderate comments'), __('Determines whether comments must by approved before they are visible on the site.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'offline_online',
        'label' => __('Website online'),
        'value' => $offline_online,
        'text' => makeJtip(__('Website online'), __('Set your website offline for maintenance or upgrade.'))
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => sprintf("<tr><td colspan='2'><h3>%s</h3> <em>(%s)</em></td></tr>",
            __('Cache Settings'),
            __('Warning! These features are experimental, so use them with caution!') )
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'minify_backend',
        'label' => __('Use Minify in Backend'),
        'value' => '',
        'text' => makeJtip(__('Use Minify in Backend'), __('Use Minify to group and compress Javascript and CSS files to reduce the amount of requests for all pages in the PivotX interface. Disable this if you are experiencing problems.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'minify_frontend',
        'label' => __('Use Minify in Frontend'),
        'value' => '',
        'text' => makeJtip(__('Use Minify in Frontend'), __('Use Minify to group and compress Javascript and CSS files to reduce the amount of requests for all pages in the website. Disable this while you\'re working on your Templates, or if you are experiencing problems.'))
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'smarty_force_compile',
        'label' => __('Force compile templates'),
        'value' => '',
        'text' => makeJtip(__('Force compile templates'), __('If you enable this, all templates will be compiled everytime. Only enable this, if you\'re having trouble with the templates, as this setting severely degrades performance.'))
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'no_cached_include',
        'label' => __('Disallow cached includes'),
        'value' => '',
        'text' => makeJtip(__('Disallow cached includes'), __('If you enable this, all [[cached_include]] tags will work like regular [[include]] tags, without being cached.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'smarty_cache',
        'label' => __('Use output caching'),
        'value' => '',
        'text' => makeJtip(__('Use output caching'), __('If you enable this, all pages will be cached. This will make PivotX much \'lighter\', so you should always use this when you\'re having performance issues, or you have a site with a lot of visitors. Disable this while you\'re working on your Templates, or if you are experiencing problems.'))
    ));


    $form->use_javascript(true);

    return $form;

}




function getConfigForm2() {

    $form = new Form("config2", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    addDBSelectionToForm($form);

    $form->use_javascript(true);

    return $form;

}




function getConfigForm3() {

    $form = new Form("config3", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'tag_flickr_enabled',
        'label' => __('Show Flickr Images'),
        'value' => '',
        'text' => makeJtip(__('Show Flickr Images'), __('If set to "yes", PivotX will fetch images with this tag from Flickr.com.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'tag_flickr_amount',
        'label' => __('Number of images'),
        'text' => makeJtip(__('Number of images'), __('The amount of images to fetch from Flickr.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=1000'
    ));



    $form->add( array(
        'type' => 'checkbox',
        'name' => 'tag_fetcher_enabled',
        'label' => __('Show feeds'),
        'value' => '',
        'text' => makeJtip(__('Show feeds'), __('If set to "yes", PivotX will show the buttons to fetch feeds with this tag from various sources.'))
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'tag_fetcher_amount',
        'label' => __('Number of items'),
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=1000',
        'text' => makeJtip(__('Number of items'), __('The amount of items to fetch from each source'))
    ));

    $form->add( array(
        'type' => "hr"
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'tag_cloud_amount',
        'label' => __('Cloud size'),
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=1000'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'tag_min_font',
        'label' => __('Minimum size'),
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=1000'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'tag_max_font',
        'label' => __('Maximum size'),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=0|max=1000',
        'text' => makeJtip(__('Maximum size'), __('The size (in pixels) used to display the tag cloud. Tags that are used more often are shown in a larger font.'))
    ));


    $form->use_javascript(true);

    return $form;

}



function getConfigForm4() {

    $form = new Form("config4", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'debug',
        'label' => __('Debug mode'),
        'value' => '',
        'text' => makeJtip(__('Debug mode'), __('Show the Debug Bar with information about rendering times, debug notices and other information to help you develop a site.'))
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'log_queries',
        'label' => __('Log Queries'),
        'value' => '',
        'text' => makeJtip(__('Log Queries'), __('Log all MySQL queries in the debug log. Use this when you are investigation performance issues, but turn it off on production websites.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'debug_logfile',
        'label' => __('Log to file'),
        'value' => '',
        'text' => makeJtip(__('Log to file'), __('Log the debug output to a file. Use this when you are investigation performance issues, but turn it off on production websites. Only works when \'Debug\' is enabled.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'unlink',
        'label' => __('Unlink Files'),
        'value' => '',
        'text' => makeJtip(__('Unlink Files'), __('Some instances of servers on which the ghastly safe_mode is enabled, might require playing with this option. On most servers this option will not have any effect.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'chmod',
        'label' => __('Chmod Files To'),
        'text' => makeJtip(__('Chmod Files To'), __('Some servers require that created files are chmodded in a specific way. Common values are "0644" and "0755". Do not change this, unless you know what you\'re doing.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|minlen=4|maxlen=4|max=777'
    ));


    $form->use_javascript(true);

    return $form;

}






function getConfigForm5() {

    $form = new Form("config5", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    // Place the tooltips to the right of the textarea's, not below them.
    $form->html['textarea'] = <<< EOM
    <tr>
        <td valign="top">
            <label for="%name%">%label% %isrequired%</label>
        </td>
        <td valign="top">
            <textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
           %error%
        </td>
        <td valign="top">
            %text%
        </td>
    </tr>
EOM;


    $form->add( array(
        'type' => 'text',
        'name' => 'upload_path',
        'label' => __('File Upload Path'),
        'text' => makeJtip(__('File Upload Path'), __('The path to the folder where uploaded files are stored. If you change this, be sure to change it in the file <tt>pivotx/includes/timthumb-config.php</tt> as well.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=64'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'upload_save_mode',
        'label' => __('Overwrite'),
        'value' => '',
        'error' => "error",
        'firstoption' => __('Overwrite'),
        'options' => array( 1=> __('Yes'), 2 => __('Increment Filename'), 3=> __('No')),
        'isrequired' => 1,
        'validation' => 'any'
    ));



    $form->add( array(
        'type' => 'text',
        'name' => 'upload_max_filesize',
        'label' => __('Maximum Filesize'),
        'text' => makeJtip(__('Maximum Filesize'), __('The maximum size of files upload to your server.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 12,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=99999999'
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'upload_autothumb',
        'label' => __('Automatic Thumbnails'),
        'value' => '',
        'text' => makeJtip(__('Automatic Thumbnails'), __('Create thumbnails automatically when uploading images.'))
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'upload_thumb_width',
        'label' => __('Thumbnail width'),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=999'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'upload_thumb_height',
        'label' => __('Thumbnail height'),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=1|max=999'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'upload_thumb_quality',
        'label' => __('Thumbnail quality'),
        'text' => makeJtip(__('Thumbnail quality'), __('The quality of the thumbnail PivotX creates. 0 is the lowest quality, but also the smallest file. 100 is the highest, but creates a large file. 75 is a good default. ')),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=0|max=100'
    ));



    $form->add( array(
        'type' => 'textarea',
        'name' => 'upload_accept',
        'label' => __('Allow filetypes'),
        'text' => makeJtip(__('Allow filetypes'), __('These filetypes are acceptable for upload. Separate the different types with a comma. Be sure to use the proper mimetype. "image/png" is correct, while "png" is not.')),
        'value' => '',
        'error' => __('Error'),
        'rows' => 6,
        'cols' => 60,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=500'
    ));


/*
    $form->add( array(
        'type' => 'text',
        'name' => 'upload_thumb_remote',
        'label' => __('Remote cropping script'),
        'text' => makeJtip(__('Remote cropping script'), __('If your server does not have the necessary libraries installed to perform image cropping, you can use a remote cropping script.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=100'
    ));

*/

    $form->use_javascript(true);

    return $form;

}



function getConfigForm6() {

    $form = new Form("config6", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";


    $form->add( array(
        'type' => 'text',
        'name' => 'fulldate_format',
        'label' => __('Full date format'),
        'text' => makeJtip(__('Full date format'), __('This determines the format for the full date and time. Most often used at the top of a single entry page.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=100'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'entrydate_format',
        'label' => __('Entry Date'),
        'text' => makeJtip(__('Entry Date'), __('The date used when displaying the entry.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=100'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'diffdate_format',
        'label' => __('Diff Date'),
        'text' => makeJtip(__('Diff Date'), __('The "Diff Date" is most commonly used in conjunction with the "Entry Date". The Entry Date is displayed on every entry on your log, while the Diff Date is only displayed if the date differs from the previous entry.')),
        'value' => '',
        'error' => __('Error'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=100'
    ));

    $form->add( array(
        'type' => "hr"
    ));

    $form->add( array(
        'type' => "info",
        'text' => __('Current server time') . ": " . date("Y-m-d H:i:s")
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'timeoffset_unit',
        'label' => __('Time Offset Unit'),
        'value' => '',
        'error' => __('Error'),
        'firstoption' => __('Overwrite'),
        'options' => array(
                'y' => __('Year'),
                'm' => __('Month'),
                'd' => __('Day'),
                'h' => __('Hour'),
                'i' => __('Minute')
            ),
        'isrequired' => 1,
        'validation' => 'any'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'timeoffset',
        'label' => __('Time Offset'),
        'value' => '',
        'error' => __('Error'),
        'size' => 6,
        'isrequired' => 1,
        'validation' => 'integer|min=0|max=999'
    ));

    $form->use_javascript(true);

    return $form;

}



/*
function getConfigForm7() {

    $form = new Form("config7", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";

    // Place the tooltips to the right of the textarea's, not below them.
    $form->html['textarea'] = <<< EOM
    <tr>
        <td valign="top">
            <label for="%name%">%label% %isrequired%</label>
        </td>
        <td valign="top">
            <textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
           %error%
        </td>
        <td valign="top">
            %text%
        </td>
    </tr>
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'ping',
        'label' => __('Ping update trackers'),
        'value' => '',
        'text' => makeJtip(__('Ping update trackers'), __('This determines whether update trackers like weblogs.com will be automatically notified by PivotX if you post a new entry. Services like blogrolling.com depend on these pings'))
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'ping_urls',
        'label' => __('URLs to ping'),
        'text' => makeJtip(__('URLs to ping'), __('You can provide several urls to send pings to. Do not include the http:// part, otherwise it won\'t work. Just place each server on a new line, or separated by a pipe character. Some common servers to ping are:<br /><b>rpc.pingomatic.com</b> (the one most widely used)<br /><b>rpc.weblogs.com/RPC2</b>(also widely used)<br />')),
        'value' => '',
        'error' => __('Error'),
        'rows' => 6,
        'cols' => 60,
        'isrequired' => 1,
        'validation' => 'string|minlen=1|maxlen=500'
    ));



    $form->use_javascript(true);

    return $form;

}
*/

function getConfigFormMoblog() {
    global $PIVOTX;

    $form = new Form("configmoblog", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;
    $form->html['submit'] = "";


    $form->add( array(
        'type' => 'checkbox',
        'name' => 'moblog_active',
        'label' => __('Activate'),
        'value' => '',
        'isrequired' => 0,
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'moblog_server',
        'label' => __('Mail server'),
        'error' => __('Please give a proper domain name!'),
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=4'
    ));


    $form->add( array(
        'type' => 'text',
        'name' => 'moblog_username',
        'label' => __('Username'),
        'value' => '',
        'error' => __('That\'s not a proper username!'),
        'size' => 50,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2'
    ));

    $form->add( array(
        'type' => 'password',
        'name' => 'moblog_password',
        'label' => __('Password'),
        'error' => __('Please give a proper password!'),
        'size' => 20,
        'extra' => 'autocomplete="off"',
        'isrequired' => 0,
        'validation' => 'ifany|minlen=2'
    ));


    $form->add( array(
        'type' => 'select',
        'name' => 'moblog_author',
        'label' => __('Default author'),
        'value' => '',
        'error' => __('Please select a user'),
        'firstoption' => __('Select a user'),
        'options' => $PIVOTX['users']->getUserNicknames(),
        'isrequired' => 0,
        'validation' => 'any',
        'text' => makeJtip(__('Default author'),
            __('The user that the posted entries will belong to.'))
    ));

    $allcats = $PIVOTX['categories']->getCategories();
    foreach($allcats as $cat) {
        $catoptions[$cat['name']] = $cat['display'];
    }

    $form->add( array(
        'type' => 'select',
        'name' => 'moblog_category',
        'label' => __('Default category'),
        'value' => '',
        'error' => __('Please select a category'),
        'firstoption' => __('Select a category'),
        'options' => $catoptions,
        'isrequired' => 0,
        'validation' => 'any',
        'text' => makeJtip(__('Default category'),
            __('The category that normal messages will be posted to.'))
    ));

    $form->add( array(
        'type' => 'select',
        'name' => 'moblog_imap_protocol',
        'label' => __('Use the IMAP extension'),
        'value' => '',
        'error' => __('Please select an option'),
        'firstoption' => __('What protocol'),
        'options' => array(
            'x' => __('Disabled'),
            'pop3' => 'POP3',
            'imap' => 'IMAP',
            'pop3s' => 'POP3S',
            'imaps' => 'IMAPS'),
        'validation' => 'any',
        'text' => makeJtip(__('Use the IMAP extension'),
            __('The IMAP extension, which isn\'t enabled on all web servers, supports both POP3 and IMAP and is required if you need secure POP3 (as Hotmail and Gmail uses).'))
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'moblog_allowed_senders',
        'label' => __('Allowed senders'),
        'text' => makeJtip(__('Allowed senders'),
            __('Only email that has a "From" that matches one of the following comma separated items will be posted. You can enter complete email addresses, or partial ones.')),
        'value' => '',
        'error' => __('Error'),
        'rows' => 3,
        'cols' => 50,
        'isrequired' => 0,
        'validation' => 'minlen=1|maxlen=500'
    ));

    $form->use_javascript(true);

    return $form;

}




function getSpamConfigForm() {

    $form = new Form("spamconfig", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;">
EOM;

    // Place the tooltips to the right of the textarea's, not below them.
    $form->html['textarea'] = <<< EOM
    <tr>
        <td valign="top">
            <label for="%name%">%label% %isrequired%</label>
        </td>
        <td valign="top">
            <textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
           %error%
        </td>
        <td valign="top">
            %text%
        </td>
    </tr>
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

/* -- We're not doing anything with this yet..

    $form->add( array(
        'type' => 'text',
        'name' => 'spamthreshold',
        'label' => __('Spamscore Threshold'),
        'value' => '',
        'text' => makeJtip(__('Spamscore Threshold'), __("Each comment gets 'points', where a higher score means that it's more likely that the comment or trackback is spam. For instance: a failed Hascash raises the spamscore by 6.")),
        'error' => "Value must be a number bigger than or equal to zero.",
        'size' => 2,
        'isrequired' => 1,
        'validation' => 'integer|min=0'
    ));


*/

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'hashcash',
        'label' => __('Use HashCash'),
        'value' => '',
        'text' => makeJtip(__('Use HashCash'), __('HashCash is the most powerful, completely invisible spam protection available. It requires javascript to be enabled on the client. If this is unacceptable for you, then don\'t enable it.'))
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'hardened_trackback',
        'label' => __('Use Hardened Trackback'),
        'value' => '',
        'text' => makeJtip(__('Use Hardened Trackback'), __('Hardened Trackback is a powerful trackback spam protection. It requires javascript to be enabled on the client. If this is unacceptable for you, then don\'t enable it.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'maxhrefs',
        'label' => __('Number of links'),
        'value' => '',
        'text' => makeJtip(__('Number of links'), __('Maximum number of hyperlinks in allowed in comments. Useful to get rid of those pesky comment spammers. Set to 0 for unlimited links.')),
        'error' => "Value must be a number bigger than or equal to zero.",
        'size' => 2,
        'isrequired' => 1,
        'validation' => 'integer|min=0'
    ));

    $form->add( array(
        'type' => 'custom',
        'text' => "<tr><td colspan='3'><h3>" . __('SpamQuiz') . "</h3></td></tr>"
    ));

    $form->add( array(
        'type' => 'checkbox',
        'name' => 'spamquiz',
        'label' => __('Use SpamQuiz'),
        'value' => '',
        'text' => makeJtip(__('Use SpamQuiz'), __('Before sending a comment, your users have to answer correctly a simple question everyone knows the answer to. This completely baffles automated spam bots because every blogger will choose something different.'))
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'spamquiz_question',
        'label' => __('Question'),
        'text' => makeJtip(__('Question'), __('Example: What are the first two letters of the word "spam"?')),
        'value' => '',
        'size' => 60,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=500'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'spamquiz_answer',
        'label' => __('Answer'),
        'text' => makeJtip(__('Answer'), __('Example: <b>sp</b>')),
        'value' => '',
        'size' => 20,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=500'
    ));

    $form->add( array(
        'type' => 'text',
        'name' => 'spamquiz_age',
        'label' => __('Age'),
        'text' => makeJtip(__('Age'), __('Only ask for the spamquiz question, when the entry is older than the given amount of days.')),
        'value' => '',
        'size' => 60,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=500'
    ));

    $form->add( array(
        'type' => 'textarea',
        'name' => 'spamquiz_explain',
        'label' => __('Explanation'),
        'text' => makeJtip(__('Explanation'), __('Example: To prevent automated commentspam we require you to answer this silly question')),
        'value' => '',
        'rows' => 4,
        'cols' => 60,
        'isrequired' => 0,
        'validation' => 'ifany|minlen=1|maxlen=500'
    ));

    $form->use_javascript(true);

    return $form;

}

function getExtensionsForm($list) {
    global $PIVOTX;

    $form = new Form("extensions", "", __("Save"));

    // No border for this form:
    $form->html['start'] = <<< EOM
<form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
<table border='0' cellspacing='0' cellpadding='2' class='formclass' style="border-width: 0px !important;" width="700">
<tr><td width="1"></td><td width="1000"></td></tr>
EOM;

    // The label is after the checkbox 
    $form->html['checkbox'] = <<< EOM
<tr>
    <td valign="top">
        <input type="checkbox" name="%name%" value="1" %checked% id="%formname%_%name%"
              class="noborder" tabindex="%tabindex%" />
       %error%
    </td>
    <td>
       <label for="%formname%_%name%">%label% %isrequired%</label>
       %text%
    </td>
</tr>
EOM;

    $form->add( array(
        'type' => "csrf",
        'cookie' => "pivotxsession",
        'sessionvariable' => "pivotxsession"
    ));

    ksort($list);

    foreach ($list as $type => $extensionslist) {

        if (!empty($extensionslist)) {

            // Print a divider with a header:

            switch($type) {
                case "admin":
                    $title = __("Admin Page Extensions");
                    break;
                case "hook":
                    $title = __("Hook Extensions");
                    break;
                case "snippet":
                    $title = __("Snippet Extensions");
                    break;
                case "widget":
                    $title = __("Widget Extensions"); // -- Temporarily also show Widgets, until Jquery UI sortables work.

                    // Skip the widgets, these have their own page..
                    continue(2);
                    break;

            }

            $form->add( array(
                'type' => "custom",
                'text' => "<tr><td colspan='2'><h3 style='margin: 6px 0 0 0;'>$title</h3></td></tr>\n"
            ));

            foreach ($extensionslist as $extension) {

                $updatecheck = sprintf(
                    "<span rel='%s' id='update-%s'></span>", $extension['version'], $extension['identifier']
                );
                $label = sprintf("%s <strong>%s</strong>", $updatecheck, $extension['extension']);
                $description = sprintf(
                    "<p class='extension-desc'>%s</p>\n" .
                    "<p class='extension-metadata'>%s: %s",
                    $extension['description'],
                    __("By"),
                    $extension['author']
                );

                if ($extension['version']!="") {
                    $description .= ', '. __("version"). ': ' . $extension['version'];
                }

                if ($extension['date']!="") {
                    $description .= ', '.__("last updated").': ' . $extension['date'];
                }

                if ($extension['site']!="") {
                    $description .= ". <a href='".$extension['site']."'>".__("site link")."</a>";
                }

                // Directly take values from $_POST, if we're viewing
                // a posted form.
                if (!empty($_POST)) {
                    $value = $_POST[$extension['identifier']];
                } else {
                    $value = in_array($extension['identifier'], $PIVOTX['extensions']->getActivated()) ? 1 : 0;
                }

                // If the extension is enabled and has its own config screen,
                // We make a direct link to it.
                if ($value==1 && ($PIVOTX['extensions']->getAdminScreenName($extension['identifier'])!==false)) {
                    $description .= ", <a href='index.php?page=configuration#section-".
                        $PIVOTX['extensions']->getAdminScreenName($extension['identifier']) .
                        "'>" . __('edit configuration') . "</a>";
                }


                $description .= "</p>";

                $form->add( array(
                    'type' => 'checkbox',
                    'name' => $extension['identifier'],
                    'label' => $label,
                    'value' => $value,
                    'text' => $description
                ));
            }

            $form->add( array(
                'type' => "hr"
            ));
            
        }
    }

    return $form;

}




/**
 * Makes the HTML for a jtip
 */
function makeJtip($caption, $str) {

    static $tip_id = 0;
    $tip_id++;
    $caption = str_replace("'", "&#39;", $caption);
    $str = str_replace("'", "&#39;", $str);
    $id = "tip{$tip_id}_".safeString($caption, true);

    $html = sprintf("<span class='formInfo'><a href='#' class='jTip' name='%s'
        rel='%s' id='%s'><img src='pics/information.png' width='16' height='16' alt='i' /></a></span>\n",
        $caption,
        $str,
        $id
    );

    $html .= sprintf("<noscript>%s</noscript>\n", $str);

    return $html;

}

?>
