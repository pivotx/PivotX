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
 * Set up the menus that are used in the PivotX interface. (version 2)
 *
 * Version 2 now has full seperation of form and content.
 */
function getMenus() {
    global $PIVOTX;

    // !! (note to Bob/Hans, this doesn't work because $modqueue is local)
    if (count($modqueue)>0) {
        // __('Moderate Comments'));
        $queuemsg = __('There are %1 comment(s) waiting to be approved.');
        $queuemsg = str_replace("%1", count($modqueue), $queuemsg);
    } else {
        $queuemsg = __('No comments are waiting to be approved.');
    }

    // determine user-level
    if (isset($PIVOTX['session'])) {
        $currentuser = $PIVOTX['session']->currentUser();
        $currentuserlevel = $currentuser['userlevel'];
    } else {
        $currentuserlevel = PIVOTX_UL_NOBODY;
    }


    // create a basic menu structure

    if ($currentuserlevel <= PIVOTX_UL_NOBODY) {
        $raw_menu = array(
            array(
                'uri' => 'login',
                'name' => __('Log in')
            )
        );
    } else {
        $raw_menu = array(
            array(
                'sortorder' => 1000,
                'uri' => 'dashboard',
                'name' => __('Dashboard'),
                'description' => '',
                'menu' => array(
                    array(
                        'uri' => 'dashboard',
                        'name' => __('Back to dashboard'),
                        'description' => ''
                    )
                )
            ),
            array(
                'sortorder' => 2000,
                'uri' => 'entries',
                'name' => __('Entries &amp; Pages'),
                'description' => __('Overview of Entries'),
                'level' => PIVOTX_UL_NORMAL,
                'menu' => array(
                    array(
                        'sortorder' => 1000,
                        'uri' => 'entries',
                        'name' => __('Entries'),
                        'description' => __('Overview of Entries')
                    ),
                    array(
                        'sortorder' => 2000,
                        'uri' => 'entry',
                        'name' => __('New Entry'),
                        'description' => __('Write and Publish a new Entry')
                    ),
                    array(
                        'sortorder' => 3000,
                        'is_divider' => true
                    ),
                    array(
                        'sortorder' => 4000,
                        'uri' => 'pagesoverview',
                        'name' => __('Pages'),
                        'description' => __('Overview of Pages')
                    ),
                    array(
                        'sortorder' => 5000,
                        'uri' => 'page',
                        'name' => __('New Page'),
                        'description' => __('Write and Publish a new Page')
                    ),
                    array(
                        'sortorder' => 6000,
                        'is_divider' => true
                    ),
                    array(
                        'sortorder' => 7000,
                        'uri' => 'moderatecomments',
                        'name' => __('Moderate Comments'),
                        'description' => $queuemsg
                    ),
                    array(
                        'sortorder' => 7100,
                        'uri' => 'comments',
                        'name' => __('Comments'),
                        'description' => __('Overview of Comments')
                    ),
                    array(
                        'sortorder' => 8000,
                        'uri' => 'trackbacks',
                        'name' => __('Trackbacks'),
                        'description' => __('Overview of Latest Trackbacks')
                    ),
                ),
            ),
            array(
                'sortorder' => 3000,
                'uri' => 'media',
                'name' => __('Manage Media'),
                'description' => __('Manage and Upload Media'),
                'level' => PIVOTX_UL_ADVANCED,
                'menu' => array(
                    array(
                        'uri' => 'media',
                        'name' => __('Manage Media'),
                        'description' => __('Manage and Upload Media'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'uri' => 'templates',
                        'name' => __('Templates'),
                        'description' => __('Create, edit and delete Templates'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'fileexplore',
                        'name' => __('Explore Database Files'),
                        'description' => __('View files (both text and database files)'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                    array(
                        'uri' => 'homeexplore',
                        'name' => __('Explore Files'),
                        'description' => __('View files in the site\'s root'),
                        'level' => PIVOTX_UL_ADMIN
                    ),
                ),
            ),
            array(
                'sortorder' => 4000,
                'uri' => 'administration',
                'name' => __('Administration'),
                'description' => __('Overview of Administrative functions'),
                'level' => PIVOTX_UL_ADMIN,
                'menu' => array(
                    array(
                        'uri' => 'configuration',
                        'name' => __('Configuration'),
                        'description' => __('Edit the Configuration file')
                    ),
                    array(
                        'uri' => 'advconfiguration',
                        'name' => __('Advanced Configuration'),
                        'description' => __('Edit, Add and Delete advanced Configuration options')
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'users',
                        'name' => __('Users'),
                        'description' => __('Create, edit and delete Users')
                    ),
                    array(
                        'uri' => 'categories',
                        'name' => __('Categories'),
                        'description' => __('Create, edit and delete the Categories')
                    ),
                    array(
                        'uri' => 'weblogs',
                        'name' => __('Weblogs'),
                        'description' => __('Create, edit and delete Weblogs')
                    ),
                    array(
                        'uri' => 'visitors',
                        'name' => __('Registered Visitors'),
                        'description' => __('View and edit Registered Visitors')
                    ),
                    array(
                        'uri' => 'maintenance',
                        'name' => __('Maintenance'),
                        'description' => __('Perform routine maintenance on PivotX\'s files'),
                        'level' => PIVOTX_UL_ADMIN,
                        'menu' => array(
                            array(
                                'uri' => 'spamprotection',
                                'name' => __('Spam Protection'),
                                'description' => __('Overview of the various tools to keep your weblogs spam-free'),
                                'menu' => array(
                                    array(
                                        'uri' => 'spamconfig',
                                        'name' => __('Spam Configuration'),
                                        'description' => __('Configure Spam Protection tools (like HashCash and SpamQuiz).')
                                    ),
                                    array(
                                        'uri' => 'spamlog',
                                        'name' => __('Spam Log'),
                                        'description' => __('View and Reset the Spam Log.')
                                    ),
                                /* 'ignoreddomains' => array(__('Blocked Phrases'), 
                                       __('View and Edit the Blocked Phrases to combat spam.')),
                                   'ignoreddomains_update' => array(__('Update the Global Phrases list from pivotlog.net'), 
                                       __('Update the Global Phrases list from pivotlog.net')),
                                   'spamwasher' => array(__('Spam Washer'), 
                                       __('Search for spam, and delete all of it from your entries and trackbacks.')),
                                   'ipblocks' => array(__('IP blocks'), __('View and Edit the blocked IP addresses.')),
                                 */
                                )
                            ),
                            array(
                                'uri' => 'backup',
                                'name' => __('Backup'),
                                'description' => __('Download a zip file containing your configuration files, templates or entries database')
                            ),
                            array(
                                'uri' => 'emptycache',
                                'name' => __('Empty Cache'),
                                'description' => __('Clear PivotX\'s internal cache for stored files.')
                            ),
                        )
                    ),
                )
            ),
            array(
                'sortorder' => 5000,
                'uri' => 'extensions',
                'name' => __('Extensions'),
                'description' => __('Manage installed Extensions'),
                'level' => PIVOTX_UL_ADMIN,
                'menu' => array(
                    array(
                        'uri' => 'extensions',
                        'name' => __('Extensions'),
                        'description' => __('Manage installed Extensions')
                    ),
                    array(
                        'is_divider' => true
                    ),
                    array(
                        'uri' => 'widgets',
                        'name' => __('Widgets'),
                        'description' => __('Manage installed Widgets')
                    ),
                )
            ),
        );
    }


    // specific pivotx modifications to the menu

    if ($currentuserlevel > PIVOTX_UL_NOBODY) {
        $weblogarray = $PIVOTX['weblogs']->getWeblogs();

        $weblogarray_menu = array();
        $cnt = 0;
        foreach($weblogarray as $wa) {
            $cnt++;
            $weblogarray_menu[] = array(
                'uri' => 'weblog.'.$cnt,
                'href' => $wa['link'],
                'name' => __('view') . ' ' . $wa['name'],
                'target_blank' => ($PIVOTX['config']->get('front_end_links_same_window') ? false : true), 
                'description' => $wa['name'] . ' - ' . $wa['payoff']
            );
        }

        if (count($weblogarray) > 2) {
            $weblog_menu = array(
                'menu' => array(
                    array(
                        'uri' => 'weblogs',
                        'name' => __('View weblog'),
                        'description' => '',
                        'menu' => $weblogarray_menu
                    )
                )
            );
            modifyMenu($raw_menu, 'dashboard', $weblog_menu);
        }
        else {
            modifyMenu($raw_menu, 'dashboard', array('menu'=>$weblogarray_menu));
        }
    }

    if ($currentuserlevel >= PIVOTX_UL_ADMIN) {
        $items = $PIVOTX['extensions']->getAdminScreenNames();

        $extensions_menu = array();
        foreach($items as $uri => $name) {
            $extensions_menu[] = array ( 'uri' => $uri, 'name' => $name );
        }
        if (count($extensions_menu) > 0) {
            // we have extensions, we need to add the configure extensions anchor and the extensions themselves

            $cfgext_menu = array(
                array(
                    'is_divider' => true
                ),
                array(
                    'uri' => 'cfgextensions',
                    'name' => __('Configure Extensions'),
                    'description' => __('Configure Extensions')
                )
            );

            modifyMenu($raw_menu, 'extensions', array('menu'=>$cfgext_menu));
            modifyMenu($raw_menu, 'extensions/cfgextensions', array('menu'=>$extensions_menu));
        }
    }

    if (isset($PIVOTX['config'])) {

        // If 'browse_blog_folder' is set, we show the menu option to browse it as well.
        if ($PIVOTX['config']->get('browse_blog_folder')==1) {
            modifyMenu($raw_menu, 'media', array(
                'menu' => array(
                    array(
                        'uri' => 'homeexplore',
                        'name' => __('Explore Home folder'),
                        'description' => __('View files (both text and database files)'),
                    )
                )
            ));
        }

        // Add 'build index', if we're using flat files..
        if ($PIVOTX['config']->get('db_model')=="flat") {
            modifyMenu($raw_menu, 'administration/maintenance', array(
                'menu' => array(
                    array(
                        'uri' => 'buildindex',
                        'name' => __('Rebuild the Index'),
                        'description' => __('Rebuild the index of your database'),
                    ),
                    array(
                        'uri' => 'buildsearchindex',
                        'name' => __('Rebuild Search Index'),
                        'description' => __('Rebuild the Searchindex, to allow searching in entries and pages'),
                    ),
                    array(
                        'uri' => 'buildtagindex',
                        'name' => __('Rebuild Tag Index'),
                        'description' => __('Rebuild the Tagindex, to display tag clouds and tags below entries')
                    )
                )
            ));
        }

    }


    // Extension modifications
    if (!empty($PIVOTX['extensions'])) {
        $args = array(&$raw_menu);
        $PIVOTX['extensions']->executeHook('modify_pivotx_menu', $args);
    }

    // now prepare menu for output

    $menu = organizeMenuLevel($raw_menu, $currentuserlevel);

    $PIVOTX['template']->assign('menu',$menu);
}

/**
 * Modify menu items
 *
 * @param array &$menu    root of the menu
 * @param string $path    the 'uri-path' to the menu item to modify
 * @param array $item     the modifications
 */
function modifyMenu(&$menu, $path, $item) {
    $ptrmenu = &$menu;
    $ptr     = false;

    if ($path != '') {
        $parts = explode('/',$path);
        $found_parts = 0;
        foreach($parts as $part) {
            $idx = false;
            for($i=0; $i < count($ptrmenu); $i++) {
                if ($ptrmenu[$i]['uri'] == $part) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx !== false) {
                $found_parts++;
                if ((!isset($ptrmenu[$idx]['menu'])) || (!is_array($ptrmenu[$idx]['menu']))) {
                    $ptrmenu[$idx]['menu'] = array();
                }
                $ptr     = &$ptrmenu[$idx];
                $ptrmenu = &$ptr['menu'];
            }
        }

        if ($found_parts != count($parts)) {
            // we searched but didn't find enough parts
            
            unset($ptrmenu);
            unset($ptr);
            $ptrmenu = false;
            $ptr     = false;
        }

        if ($ptr !== false) {
            // modify the menu item with everything except menu items
            foreach($item as $key => $value) {
                if ($key != 'menu') {
                    $ptr[$key] = $value;
                }
            }
        }
    }

    if (isset($item['menu'])) {
        // modify the menu item with menu items
        if ($ptrmenu == false) {
            if (!isset($ptr['menu'])) {
                $ptr['menu'] = array();
                $ptrmenu     = &$ptr['menu'];
            }
        }
        foreach($item['menu'] as $subitem) {
            $idx = false;
            for($i=0; $i < count($ptrmenu); $i++) {
                if ($ptrmenu[$i]['uri'] == $subitem['uri']) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx === false) {
                $ptrmenu[] = $subitem;
            }
            else {
                $ptrmenu[$i] = $subitem;
            }
        }
    }
}

/**
 * Compare two menu items
 *
 * @param array &$a
 * @param array &$b
 * @return
 */
function compareMenuItem(&$a,&$b) {
    if ($a['sortorder'] < $b['sortorder']) {
        return -1;
    }
    if ($a['sortorder'] > $b['sortorder']) {
        return +1;
    }
    return 0;
}

/**
 * Organize a single menu level of the menu structure
 *
 * - sorts the level
 * - applies user-level restrictions
 * - converts uri's to href's
 * - removes 'disabled' items
 * - create 'have_menu' booleans for menu's with subs
 *
 * @param array $in                menu level (and subs)
 & @param array $currentuserlevel
 */
function organizeMenuLevel($in,$currentuserlevel,$path=false,$level=0) {
    $out = array();

    if (!is_array($path)) {
        $path = array();
    }

    foreach($in as $item) {
        if (isset($item['level']) && ($currentuserlevel < $item['level'])) {
            continue;
        }
        if (isset($item['disabled']) && $item['disabled']) {
            continue;
        }

        if (!isset($item['href'])) {
            if ($item['uri'] == 'dashboard') {
                $item['href'] = makeAdminPageLink();
            } else {
                $item['href'] = makeAdminPageLink($item['uri']);
            }
        }

        if (!isset($item['is_divider'])) {
            $item['is_divider'] = false;
        }
        $all_pages = array();
        if (isset($item['uri'])) {
            $all_pages[] = $item['uri'];
        }
        if ((isset($item['menu'])) && (count($item['menu']) > 0)) {
            $item['have_menu'] = true;
            $item['menu'] = organizeMenuLevel($item['menu'],$currentuserlevel,$item['path'],$level+1);
            foreach($item['menu'] as $i2) {
                if (isset($i2['uri'])) {
                    $all_pages[] = $i2['uri'];
                }
                if (isset($i2['all_pages']) && is_array($i2['all_pages'])) {
                    $all_pages = array_merge($all_pages,$i2['all_pages']);
                }
            }
        } else {
            $item['have_menu'] = false;
        }
        
        $item['all_pages'] = $all_pages;

        $out[] = $item;
    }

    $highest_sortorder = 1;
    foreach($out as $item) {
        if ((!isset($item['sortorder'])) && ($item['sortorder'] > $highest_sortorder)) {
            $highest_sortorder = $item['sortorder'];
        }
    }

    for($i=0; $i < count($out); $i++) {
        if (!isset($out[$i]['sortorder'])) {
            $out[$i]['sortorder'] = $highest_sortorder++;
        }
    }

    usort($out, 'compareMenuItem');

    return $out;
}


/**
 * Get the default categories. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * @return array
 *
 */
function getDefaultCategories() {
    global $PIVOTX;

    $userdata = $PIVOTX['users']->getUsers();
    $username = $userdata[0]['username'];


    $categories = array (
        0 => array (
            'name' => 'default',
            'display' => __('Default'),
            'users' => array (
                    0 => $username,
                ),
            'order' => '100',
            'hidden' => -1,
        ),
        1 => array (
            'name' => 'linkdump',
            'display' => __('Linkdump'),
            'users' => array (
                 0 => $username,
                ),
            'order' => '101',
            'hidden' => -1,
        ),
    );

    return $categories;

}


/**
 * Get the default configuration. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * We also use this to check if the required values haven't been deleted accidentily.
 *
 * @return array
 *
 */
function getDefaultConfig() {
    global $dbversion;

    $config = array (
        'allow_comments' => '1',
        'allow_paragraphs' => '0',
        'chmod' => '0644',
        'cookie_length' => '1814400',
        'db_version' => $dbversion,
        'debug' => 0,
        'default_category' => 'default',
        'diffdate_format' => '%ordday% %monname% \'%ye% - ',
        'emoticons' => '1',
        'encode_email_addresses' => '0',
        'entrydate_format' => '%hour24%:%minute%',
        'extensions_path' => 'extensions/',
        'fulldate_format' => '%ordday% %monthname% \'%ye% - %hour24%:%minute%',
        'hardened_trackback' => '0',
        'hashcash' => '0',
        'ignore_magic_quotes' => '0',
        'ignore_register_globals' => '0',
        'ignore_setupscript' => '0',
        'installed' => '0',
        'language' => 'eng',
        'lastcomm_amount_max' => '60',
        'limit_feed_items' => '15',
        'log' => '0',
        'maxhrefs' => '3',
        'mod_rewrite' => '0',
        'moderate_comments' => 0,
        'overview_entriesperpage' => '20',
        'ping' => '0',
        'ping_urls' => 'rpc.pingomatic.com',
        'pivotx_url' => '/pivotx/',
        'rebuild_threshold' => '28',
        'search_index' => '1',
        'selfreg' => 0,
        'sitename' => 'PivotX Powered',
        'spampingurl' => '',
        'spamquiz' => '0',
        'spamthreshold' => '5',
        'tag_fetcher_amount' => '8',
        'tag_fetcher_enabled' => '1',
        'tag_flickr_amount' => '8',
        'tag_flickr_enabled' => '1',
        'tag_cloud_amount' => '30',
        'tag_max_font' => '17',
        'tag_min_font' => '9',
        'text_processing' => '1',
        'timeoffset' => '0',
        'timeoffset_unit' => 'h',
        'unlink' => '0',
        'upload_accept' => 'image/gif, image/jpeg, image/png, text/html, text/plain, text/xml, application/pdf, video/x-msvideo, application/x-shockwave-flash, video/x-msvideo, video/x-ms-wmv, video/mp4, video/mpeg, video/quicktime, application/octet-stream, application/x-zip-compressed, application/x-bittorrent, text/css, application/x-javascript',
        'upload_autothumb' => '1',
        'upload_extension' => '.jpg',
        'upload_file_name' => 'userfile',
        'upload_make_safe' => '0',
        'upload_max_filesize' => '5000000',
        'upload_path' => 'images/%year%-%month%/',
        'upload_save_mode' => '2',
        'upload_thumb_height' => '100',
        'upload_thumb_quality' => '78',
        'upload_thumb_width' => '350',
        'wysiwyg_editor' => '1',
        'xmlrpc' => 0,

        'db_model' => 'flat',
        'db_username' => "",
        'db_password' => "",
        'db_hostname' => "localhost",
        'db_databasename' => "",
        'db_prefix' => "pivotx_",

    );


    return $config;

}


/**
 * Get the default weblog. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 * Also, if we're creating a new weblog from scratch, we can use this to do so.
 *
 */
function getDefaultWeblog() {
    global $PIVOTX;
    
    // Use the skinny/skinny.theme as the template for the new Weblog.
    $weblog = loadSerialize(dirname(__FILE__)."/templates/skinny/skinny.theme");
    $weblog['language'] = $PIVOTX['config']->get('language');
    $weblog['payoff'] = __('Welcome to your new online presence!');

    return $weblog;

}


/**
 * Get the default pages. We need this for setting up PivotX: if the file is
 * not present, we use this to recreate it.
 *
 */
function getDefaultPages() {

    $pages = array(
        0 => array(
            'chaptername' => __('Pages'),
            'description' => __('Add some pages here, or start a new chapter.'),
            'pages' => array(),
            'sortorder' => 1,
        )

    );

    return $pages;

}


/**
 * Get the default styles for the widgets.
 *
 * @return array
 */
function getDefaultWidgetStyles() {

    $styles = array(
        'widget-lg' => __('Light gray'),
        'widget-dg' => __('Dark gray'),
        'widget-min' => __('Minimally styled')
    );

    return $styles;

}

/**
 * Create the SQL table for Pages.
 *
 * @param link $sql
 */
function makePagesTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."pages", true);

    $userdata = $PIVOTX['users']->getUsers();
    $username = $userdata[0]['username'];

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `title` tinytext collate utf8_unicode_ci NOT NULL,
      `uri` tinytext collate utf8_unicode_ci NOT NULL,
      `subtitle` tinytext collate utf8_unicode_ci NOT NULL,
      `introduction` mediumtext collate utf8_unicode_ci NOT NULL,
      `body` mediumtext collate utf8_unicode_ci NOT NULL,
      `convert_lb` int(11) NOT NULL default '0',
      `template` tinytext collate utf8_unicode_ci NOT NULL,
      `status` tinytext collate utf8_unicode_ci NOT NULL,
      `date` datetime NOT NULL default '0000-00-00 00:00:00',
      `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
      `edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
      `chapter` int(11) NOT NULL default '0',
      `sortorder` int(11) NOT NULL default '0',
      `user` tinytext collate utf8_unicode_ci NOT NULL,
      `allow_comments` int(11) NOT NULL default '0',
      `keywords` tinytext collate utf8_unicode_ci NOT NULL,
      `extrafields` text collate utf8_unicode_ci NOT NULL,
      PRIMARY KEY  (`uid`),
      FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ";

    /**
     * 'utf8_unicode' (or any charset for that matter) in this way is only
     * supported in MYSQL 4.1 and higher.
     * If we're on MySQL 4.0.x, we'll need to do a more generic statement,
     * which works, but we can't guarantee the proper storage of the more
     * exotic Characters.
     *
     * Perhaps we need to  upgrade users who are on 4.0 now later on.
     * see http://cvs.drupal.org/viewcvs/drupal/drupal/update.php?rev=1.211&view=markup
     * for some relevant information.
     */
    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $query2 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
(1, '%title%', 'about', '', '<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>, the coolest free and open tool to power your blog and website. To change this text, edit ''<tt>About PivotX</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</p>', '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice     weblogger to use and complex enough to meet the demands of advanced webmasters.     It can be used to publish a variety of websites from the most basic weblog to very advanced CMS style solutions.</p>\r\n<p>PivotX is - if we do say so ourselves - quite an impressive piece of software. It     is made even better through the use of several external libraries. We thank their     authors for the time taken to develop these very useful tools and for making     them available to others.</p>\r\n<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously     forged ahead thanks to the efforts of a lot     of dedicated and very talented people. The PivotX core team is still very active     but keep in mind that PivotX would not be what it is today without the valuable     contributions made by several other people.</p>', 5, '', 'publish', '%now%-00', '%now%-00', '%now%-00', 1, 10, '$username', 1, ''); ";

    $query3 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `template`, `status`, `date`, `publish_date`, `edit_date`, `chapter`, `sortorder`, `user`, `allow_comments`, `keywords`) VALUES
(2, '%title%', 'links', '', '<p>Some links to sites with more information:</p>\r\n<ul>\r\n<li>PivotX - <a href=\"http://pivotx.net\">The PivotX website</a></li>\r\n<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>\r\n<li>Read <a href=\"http://book.pivotx.net\">the PivotX documentation</a></li>\r\n<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>\r\n<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>\r\n<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>\r\n</ul>\r\n<p><small>To change these links, edit ''<tt>Links</tt>'', under ''<tt>Pages</tt>'' in the PivotX backend.</small></p>', '', 5, '', 'publish', '%now%-01', '%now%-01', '%now%-01', 1, 10, '$username', 1, '');";


    $now = date("Y-m-d-H-i", getCurrentDate());

    $query2 = str_replace("%now%", $now, $query2);
    $query2 = str_replace("%title%", __('About PivotX'), $query2);
    $query3 = str_replace("%now%", $now, $query3);
    $query3 = str_replace("%title%", __('Links'), $query3);

    $sql->query($query1);
    $sql->query($query2);
    $sql->query($query3);


}


/**
 * Create the SQL table for Chapters.
 *
 * @param link $sql
 */
function makeChaptersTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."chapters", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `chaptername` tinytext collate utf8_unicode_ci NOT NULL,
      `description` tinytext collate utf8_unicode_ci NOT NULL,
      `sortorder` int(11) NOT NULL default '0',
      PRIMARY KEY  (`uid`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

    /**
     * 'utf8_unicode' (or any charset for that matter) in this way is only
     * supported in MYSQL 4.1 and higher.
     * If we're on MySQL 4.0.x, we'll need to do a more generic statement,
     * which works, but we can't guarantee the proper storage of the more
     * exotic Characters.
     *
     * Perhaps we need to  upgrade users who are on 4.0 now later on.
     * see http://cvs.drupal.org/viewcvs/drupal/drupal/update.php?rev=1.211&view=markup
     * for some relevant information.
     */
    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }


    $query2 = "INSERT INTO `$tablename` (`uid`, `chaptername`, `description`, `sortorder`) VALUES
        (1, '%name%', '%desc%', 10);
    ";
    $query2 = str_replace("%name%", mysql_real_escape_string(__('Pages')), $query2);
    $query2 = str_replace("%desc%", mysql_real_escape_string(__('Add some pages here, or start a new chapter.')), $query2);

    $sql->query($query1);
    $sql->query($query2);

}


/**
 * Create the SQL table for Entries.
 *
 * @param link $sql
 */
function makeEntriesTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."entries", true);

    $userdata = $PIVOTX['users']->getUsers();
    $username = $userdata[0]['username'];

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `title` tinytext collate utf8_unicode_ci NOT NULL,
      `uri` tinytext collate utf8_unicode_ci NOT NULL,
      `subtitle` tinytext collate utf8_unicode_ci NOT NULL,
      `introduction` mediumtext collate utf8_unicode_ci NOT NULL,
      `body` mediumtext collate utf8_unicode_ci NOT NULL,
      `convert_lb` int(11) NOT NULL default '0',
      `status` tinytext collate utf8_unicode_ci NOT NULL,
      `date` datetime NOT NULL default '0000-00-00 00:00:00',
      `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
      `edit_date` datetime NOT NULL default '0000-00-00 00:00:00',
      `user` tinytext collate utf8_unicode_ci NOT NULL,
      `allow_comments` int(11) NOT NULL default '0',
      `keywords` tinytext collate utf8_unicode_ci NOT NULL,
      `via_link` tinytext collate utf8_unicode_ci NOT NULL,
      `via_title` tinytext collate utf8_unicode_ci NOT NULL,
      `comment_count` int(11) collate utf8_unicode_ci NOT NULL,
      `comment_names` mediumtext collate utf8_unicode_ci NOT NULL,
      `trackback_count` int(11) collate utf8_unicode_ci NOT NULL,
      `trackback_names` mediumtext collate utf8_unicode_ci NOT NULL,
      `extrafields` text collate utf8_unicode_ci NOT NULL,
      PRIMARY KEY  (`uid`),
      FULLTEXT KEY `title` (`title`,`subtitle`,`introduction`,`body`, `keywords`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $query2 = "INSERT INTO `$tablename` (`uid`, `title`, `uri`, `subtitle`, `introduction`, `body`, `convert_lb`, `status`, `date`, `publish_date`, `edit_date`, `user`, `allow_comments`, `keywords`, `via_link`, `via_title`, `comment_count`, `comment_names`, `trackback_count`, `trackback_names`) VALUES
(1, '%version%', '%uri-version%', '', '<p>If you can read this, you have successfully installed [[tt tag=\"PivotX\"]]. Yay!! To help you further on your way, the following links might be of use to you:</p>
<ul>
<li>PivotX.net - <a href=\"http://pivotx.net\">The official PivotX website</a></li>
<li>The online documentation at <a href=\"http://book.pivotx.net\">PivotX Help</a> should be of help.</li>
<li>Get help on <a href=\"http://forum.pivotx.net\">the PivotX forum</a></li>
<li>Browse for <a href=\"http://themes.pivotx.net\">PivotX Themes</a></li>
<li>Get more <a href=\"http://extensions.pivotx.net\">PivotX Extensions</a></li>
<li>Follow <a href=\"http://twitter.com/pivotx\">@pivotx on Twitter</a></li>
</ul>
<p>And, of course: Have fun with PivotX!</p>', '<h3>More</h3>
<p>All text that you write in the \'body\' part of the entry will only appear on the entry\'s own page. To see how this works, edit this entry in the PivotX administration by going to \'Entries &amp; Pages\' &raquo; \'Entries\' &raquo; \'Edit\'.</p>', 0, 'publish', '%now%-00', '%now%-00', '%now%-00', '$username', 1, 'pivot pivotx', '', '', 1, 'Bob', 0, '');";



    $now = date("Y-m-d-H-i", getCurrentDate());
    $version = __("Welcome to"). " " . strip_tags($GLOBALS['build']);

    $query2 = str_replace("%version%", $version, $query2);
    $query2 = str_replace("%uri-version%", makeURI($version), $query2);
    $query2 = str_replace("%now%", $now, $query2);

    $sql->query($query1);
    $sql->query($query2);

}




/**
 * Create the SQL table for the Extra fields in Entries and Pages.
 *
 * @param link $sql
 */
function makeExtrafieldsTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

    $query1 = "CREATE TABLE IF NOT EXISTS `$tablename` (
        `uid` int(11) NOT NULL auto_increment,
        `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
        `target_uid` int(11) NOT NULL default '0',
        `fieldkey` tinytext collate utf8_unicode_ci NOT NULL,
        `value` text collate utf8_unicode_ci NOT NULL,
        PRIMARY KEY  (`uid`),
        KEY `target_uid` (`target_uid`),
        KEY `fieldkey` (`fieldkey`(16)),
        FULLTEXT KEY `value` (`value`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $sql->query($query1);


}




/**
 * Create the SQL table for Entries.
 *
 * @param link $sql
 */
function makeCommentsTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."comments", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
      `entry_uid` int(11) NOT NULL default '0',
      `name` tinytext collate utf8_unicode_ci NOT NULL,
      `email` tinytext collate utf8_unicode_ci NOT NULL,
      `url` tinytext collate utf8_unicode_ci NOT NULL,
      `ip` tinytext collate utf8_unicode_ci NOT NULL,
      `useragent` tinytext collate utf8_unicode_ci NOT NULL,
      `date` datetime NOT NULL default '0000-00-00 00:00:00',
      `comment` mediumtext collate utf8_unicode_ci NOT NULL,
      `registered` tinyint(4) NOT NULL default '0',
      `notify` tinyint(4) NOT NULL default '0',
      `discreet` tinyint(4) NOT NULL default '0',
      `moderate` tinyint(4) NOT NULL default '0',
      `spamscore` tinyint(4) NOT NULL default '0',
      PRIMARY KEY  (`uid`),
      KEY `entry_uid` (`entry_uid`),
      KEY `date` (`date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $query2 = "INSERT INTO `$tablename` VALUES(1, 'entry', 1, 'Bob', '', 'http://pivotx.net', '127.0.0.1', '', '%now%-10', 'Hi! This is what a comment looks like!', 0, 0, 0, 0, 0);";

    $now = date("Y-m-d-H-i", getCurrentDate());
    $query2 = str_replace("%now%", $now, $query2);

    $sql->query($query1);
    $sql->query($query2);

}



/**
 * Create the SQL table for Trackbacks.
 *
 * @param link $sql
 */
function makeTrackbacksTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."trackbacks", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `entry_uid` int(11) NOT NULL default '0',
      `name` tinytext collate utf8_unicode_ci NOT NULL,
      `title` tinytext collate utf8_unicode_ci NOT NULL,
      `url` tinytext collate utf8_unicode_ci NOT NULL,
      `ip` tinytext collate utf8_unicode_ci NOT NULL,
      `date` datetime NOT NULL default '0000-00-00 00:00:00',
      `excerpt` mediumtext collate utf8_unicode_ci NOT NULL,
      `moderate` tinyint(4) NOT NULL default '0',
      `spamscore` tinyint(4) NOT NULL default '0',
      PRIMARY KEY  (`uid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $sql->query($query1);

}



/**
 * Create the SQL table for Tags.
 *
 * @param link $sql
 */
function makeTagsTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."tags", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `tag` tinytext collate utf8_unicode_ci NOT NULL,
      `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
      `target_uid` int(11) NOT NULL default '0',
      PRIMARY KEY  (`uid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $sql->query($query1);

    $sql->query("ALTER TABLE `$tablename` ADD INDEX ( `target_uid` ) ;");
    $sql->query("ALTER TABLE `$tablename` ADD INDEX ( `tag`(32) ) ;");

}



/**
 * Create the SQL table for Categories.
 *
 * @param link $sql
 */
function makeCategoriesTable($sql) {
    global $PIVOTX;

    $tablename = safeString($PIVOTX['config']->get('db_prefix')."categories", true);

    $query1 = "CREATE TABLE `$tablename` (
      `uid` int(11) NOT NULL auto_increment,
      `contenttype` tinytext collate utf8_unicode_ci NOT NULL,
      `category` tinytext collate utf8_unicode_ci NOT NULL,
      `target_uid` int(11) NOT NULL default '0',
      PRIMARY KEY  (`uid`),
      KEY `target_uid` (`target_uid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

    if ($sql->get_server_info() < "4.1") {
        $query1 = trimQuery($query1);
    }

    $sql->query($query1);
    $sql->query("INSERT INTO `$tablename` (`uid`, `contenttype`, `category`, `target_uid`) VALUES (1, 'entry', 'default', 1);");
    $sql->query("INSERT INTO `$tablename` (`uid`, `contenttype`, `category`, `target_uid`) VALUES (2, 'entry', 'linkdump', 1);");

}



/**
 * Check if the current version of the DB is updated to the latest version,
 * and update it if it isn't..
 *
 */
function checkDBVersion() {
    global $PIVOTX, $dbversion;

    if ( !(($PIVOTX['config']->get('db_model') == "mysql") && ($PIVOTX['config']->get('db_version') < $dbversion)) ) {
        return;
    }
    
    $db1 = new sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password') );
    $db2 = new sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password') );

    $db_version = $PIVOTX['config']->get('db_version');

    $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
    $categoriestable = safeString($PIVOTX['config']->get('db_prefix')."categories", true);
    $commentstable = safeString($PIVOTX['config']->get('db_prefix')."comments", true);
    $trackbackstable = safeString($PIVOTX['config']->get('db_prefix')."trackbacks", true);
    $pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
    $extratable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);
    $tagstable = safeString($PIVOTX['config']->get('db_prefix')."tags", true);

    // DB changes from PivotX 2.0 alpha 2 to alpha 3.
    if (intval($db_version) < 1) {
        debug("now updating DB to version 1..");

        // We need to set the URI's for all entries in the DB.
        $db1->query("SELECT uid,title FROM $entriestable");

        while ($entry = $db1->fetch_row()) {
            $uri = makeURI($entry['title']);
            $db2->query("UPDATE $entriestable SET uri=". $db2->quote($uri) . " WHERE uid= ". $entry['uid'] ." LIMIT 1;");
        }

        // Add fultext search for entries and pages..
        $db1->query("ALTER TABLE $entriestable ADD FULLTEXT(title, subtitle, introduction, body);");
        $db1->query("ALTER TABLE $pagestable ADD FULLTEXT(title, subtitle, introduction, body);");

        debug("Updated DB to version 1");
        $PIVOTX['config']->set('db_version', 1);
    }


    // DB changes introduced between Alpha 4 and Beta 1.
    if (intval($db_version) < 3) {
        debug("now updating DB to version 3..");

        // Add extrafields field for entries and pages..
        $db1->query("CREATE TABLE IF NOT EXISTS `$extratable` (
            `uid` int(11) NOT NULL auto_increment,
            `contenttype` tinytext character set utf8 collate utf8_unicode_ci NOT NULL,
            `target_uid` int(11) NOT NULL default '0',
            `fieldkey` tinytext character set utf8 collate utf8_unicode_ci NOT NULL,
            `value` text character set utf8 collate utf8_unicode_ci NOT NULL,
            PRIMARY KEY  (`uid`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        debug("Updated DB to version 3");
        $PIVOTX['config']->set('db_version', 3);
    }


    // DB changes from PivotX 2.0 beta 1 to beta 2.
    if (intval($db_version) < 4) {
        debug("now updating DB to version 4..");

        // Add fultext search for entries and pages..
        $db1->query("ALTER TABLE `$entriestable` DROP INDEX `title`;");
        $db1->query("ALTER TABLE `$pagestable` DROP INDEX `title`;");
        $db1->query("ALTER TABLE `$entriestable` ADD FULLTEXT(title, subtitle, introduction, body, keywords);");
        $db1->query("ALTER TABLE `$pagestable` ADD FULLTEXT(title, subtitle, introduction, body, keywords);");

        debug("Updated DB to version 4");
        $PIVOTX['config']->set('db_version', 4);
    }

    // DB changes for PivotX 2.0 RC 1d and up.
    if (intval($db_version) < 5) {
        
        // Add indices to speed up JOINs..
        $db1->query("ALTER TABLE `$categoriestable` ADD KEY `target_uid` (`target_uid`);");
        $db1->query("ALTER TABLE `$commentstable` ADD KEY `entry_uid` (`entry_uid`);");
        $db1->query("ALTER TABLE `$commentstable` ADD KEY `date` (`date`);");
        
        debug("Updated DB to version 5");
        $PIVOTX['config']->set('db_version', 5);
    }

    // DB changes for PivotX 2.1 and up.
    if (intval($db_version) < 6) {
        
        // Add column to store useragent for comments..
        $db1->query("ALTER TABLE `$commentstable`  ADD `useragent` TINYTEXT NOT NULL AFTER `ip`;");

        debug("Updated DB to version 6");
        $PIVOTX['config']->set('db_version', 6);
    }

    if (intval($db_version) < 7) {
        
        // Add column to store moderate for trackbacks..
        $db1->query("ALTER TABLE `$trackbackstable` ADD `moderate` TINYINT NOT NULL AFTER `excerpt` ;");

        debug("Updated DB to version 7");
        $PIVOTX['config']->set('db_version', 7);
    }

    if (intval($db_version) < 8) {
        
        // Add Indices to tags table...
        $db1->query("ALTER TABLE `$tagstable` ADD INDEX ( `target_uid` ) ;");
        $db1->query("ALTER TABLE `$tagstable` ADD INDEX ( `tag`(32) ) ;");

        debug("Updated DB to version 8");
        $PIVOTX['config']->set('db_version', 8);
    }

    if (intval($db_version) < 9) {
        
        // Add Indices to extrafields table...
        $db1->query("ALTER TABLE `$extratable` ADD INDEX ( `target_uid` ) ;");
        $db1->query("ALTER TABLE `$extratable` ADD INDEX ( `fieldkey`(16) ) ;");

        // Bob is a moran. Why in the name of sweet jeebus would someone ever define a column name like comment_COUNT as a tinytext. Sheeesh...
        $db1->query("ALTER TABLE  `$entriestable` CHANGE `comment_count` `comment_count` INT NOT NULL;");
        $db1->query("ALTER TABLE  `$entriestable` CHANGE `trackback_count` `trackback_count` INT NOT NULL;");

        debug("Updated DB to version 9");
        $PIVOTX['config']->set('db_version', 9);
    }

    if (intval($db_version) < 10) {
        
        // Add column to category for entrytypes..
        $db1->query("ALTER TABLE `$categoriestable` ADD `contenttype` TINYTEXT NOT NULL AFTER `uid` ;");
        $db1->query("UPDATE `$categoriestable` SET `contenttype` = 'entry' WHERE 1;");
        
        // Add column to comments for entrytypes..
        $db1->query("ALTER TABLE `$commentstable` ADD `contenttype` TINYTEXT NOT NULL AFTER `uid` ;");
        $db1->query("UPDATE `$commentstable` SET `contenttype` = 'entry' WHERE 1;");
        
        debug("Updated DB to version 10");
        $PIVOTX['config']->set('db_version', 10);
    }

    if (intval($db_version) < 11) {

        // Add indexes to extrafields..
                    // This is a huge performance improvement when you query a lot of extrafields
        $db1->query("ALTER TABLE `$extratable` ADD INDEX (  `target_uid` );");
        // Most fields differ so we want a fulltext here
        $db1->query("ALTER TABLE `$extratable` ADD FULLTEXT (`value`);");

        debug("Updated DB to version 11");
        $PIVOTX['config']->set('db_version', 11);
    }

}


?>
