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
 * The PivotX AJAX helper script. This file contains several functions
 * to dynamically load data into an existing page and some functions that 
 * update PivotX configuration and data.
 *  
 * @package pivotx
 */

define('PIVOTX_INAJAXHELPER', TRUE);


require_once(dirname(__FILE__).'/lib.php');

// When developing, you can uncomment the following line. Then the script will allow
// $_GET parameters, for easier testing.
// $_POST = array_merge((array)$_POST, (array)$_GET);

$functionname = @getDefault($_POST['function'], $_GET['function']);
$methodname   = 'ext_' . preg_replace('|[^a-zA-Z0-9_]+|','_',$functionname);

// Check if a function is select or if the function exists, else we die().
if (empty($functionname)) {
    die();
} else if (!method_exists('ajaxhelper',$methodname)) {
    echo "Sorry, but you're not allowed to call '".htmlspecialchars($functionname)."'.";
    die();
} else {

    initializePivotX();

    header("status: 200"); 
    header("HTTP/1.1 200 OK");
    header('Content-Type: text/html; charset=utf-8');

	call_user_func(array('ajaxhelper',$methodname));

    // Process the last hook, after we're done with everything else.
    $PIVOTX['extensions']->executeHook('after_execution', $dummy);

    die();
}


/**
 * Ajaxhelper class
 *
 * All methods starting with 'ext_' can be called from the 'outside'.
 */
class ajaxhelper {
    /**
     * Get all tags, to display in 'suggested tags' when editing an entry, or
     * when inserting a tag.
     */
    public static function ext_getAllTags() {

        $minsize=11;
        $maxsize=19;
        $amount = getDefault($_POST['amount'], 20);
        $output = __("Suggestions") . ": ";

        $htmllinks = array();
        $tagcosmos = getTagCosmos($amount);

        // If there are no tags abort immediately
        if ($tagcosmos['amount'] == 0) {
            echo $output;
            return;
        }

        /*
         TODO: investigate if this is still needed, and improve it
         if(empty($tagcosmos) || (($tagcosmos['maxvalue']-$tagcosmos['minvalue'])==0)) {
             return;
         }
         */
        foreach($tagcosmos['tags'] as $key => $value)   {

            // Calculate the size, depending on value.
            $nSize = $minsize + ( ($value-$tagcosmos['minvalue']) / ($tagcosmos['maxvalue']-$tagcosmos['minvalue']) ) * ($maxsize - $minsize);

            // Write the tags, we add events to them using jquery.
            $htmllinks[$key] = sprintf("<a style=\"font-size:%1.1fpx;\" rel=\"tag\" title=\"%s: %s, %s %s\">%s</a>\n",
                $nSize,
                __('Tag'),
                $key,
                $value,
                __('Entries'),
                str_replace("+","_",$key)
            );
        }

        $output .= implode(" ", $htmllinks);

        if ($tagcosmos['amount']>$amount){ 
            $output .= sprintf("(<a onclick='getAllTags(%s);'>%s</a>)", $amount*2, __("Show more tags") );
        }

        echo $output;

    }


    public static function ext_getTagSuggest() {

        $tag = safeString($_GET['q']);

        $tagcosmos = getTagCosmos(50, '', $tag);

        $output = "";

        if (is_array($tagcosmos) && !empty($tagcosmos)) {
            foreach($tagcosmos['tags'] as $key => $value)   {
                $output .= $key."\n";
            }
        }

        echo $output;

    }



    /**
     * Sets a number of config key/value pairs in a batch.
     *
     * @return void
     */
    public static function ext_setConfigBatch() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        // debug_printr( $_POST );

        foreach ($_POST as $key=>$value) {

            // Skip 'function' and 'csrfcheck' as they are not settings..
            if ($key=="csrfcheck" || $key=="function" || $key=="") {
                continue; 
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $PIVOTX['config']->set($key, $value);
            
            $PIVOTX['events']->add('edit_config', safeString($key), safeString($value));
            
            echo __("The configuration was succesfully saved.");
  
        }

    }
    

    /**
     * Sets a config key/value pair via an AJAX call.
     *
     * @return void
     */
    public static function ext_setConfig() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        // If we come from 'advanced config' we need to unentify the returned value.
        if ($_POST['unentify']==1) {
            $_POST['value'] = @html_entity_decode($_POST['value'], ENT_COMPAT, 'UTF-8');
        }

        // If the id contains '[]' we remove it, since those were added by pivotX to 
        // allow for multiple select, but should be stored without.
        $_POST['id'] = str_replace('[]', '', $_POST['id']);

        if ($_POST['id']!="") {
            $PIVOTX['config']->set($_POST['id'], $_POST['value']);

            $PIVOTX['events']->add('edit_config', safeString($_POST['id']), safeString($_POST['value']));
        }

        echo htmlentities($_POST['value'], ENT_COMPAT, "UTF-8");


    }



    /**
     * Adds a config key/value pair via an AJAX call.
     *
     * @return void
     */
    public static function ext_addConfig() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        if ($_POST['key']!="") {
            $PIVOTX['config']->set($_POST['key'], $_POST['value']);

            $PIVOTX['events']->add('edit_config', safeString($_POST['key']), safeString($_POST['value']));

        }

        echo "OK!";


    }



    /**
     * Deletes a config key via an AJAX call.
     *
     * @return void
     */
    public static function ext_delConfig() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        if ($_POST['key']!="") {
            $key = urldecode($_POST['key']);
            $PIVOTX['config']->del($key);

            $PIVOTX['events']->add('delete_config', safeString($_POST['key']));

        }

        echo "OK!";


    }


    /**
     * Show / Edit a file in the ajaxy editor..
     *
     */
    public static function ext_view() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // TODO: Check if the file is writable before showing the editor.

        if (empty($_GET['basedir'])) {
            die('Basedir is empty.');
        } else {
            $basedir = cleanPath(base64_decode($_GET['basedir']));
        }
        // Don't allow opening files outside $PIVOTX['paths']['home_path'].
        // This is consistent with the file explorer functions in pages.php.
        if (strpos($basedir, $PIVOTX['paths']['home_path']) === 0) {
            $filename = $basedir . cleanPath($_GET['file']);
        } else {
            die('Basedir outside home_path. Hacking attempt?');
        }

        if ($contents = loadSerialize($filename)) {

            // Get the output in a buffer..
            ob_start();
            print_r($contents);
            $contents = ob_get_contents();
            ob_end_clean();

            echo "<pre>\n";
            echo htmlentities($contents, ENT_QUOTES, "UTF-8");
            echo "</pre>\n";

        } else {

            $extension = getExtension($filename);

            $contents = implode("", file( $filename ));

            $contents = preg_replace('/<textarea/i','<*textarea', $contents);
            $contents = preg_replace('/<\/textarea/i','<*/textarea', $contents);


            echo "<form id='editor' class='formclass' method='post' action='' style='border: 0px;'>";
            echo "<input type='hidden' value='".$_GET['basedir']."' id='editBasedir'>";
            echo "<input type='hidden' value='".$_GET['file']."' id='editFile'>";
            echo "<textarea style='width: 759px; border: 1px inset #999; height: 380px;' id='editContents' name='editContents' class='Editor' >";
            echo htmlentities($contents, ENT_QUOTES, 'UTF-8');
            echo "</textarea>";

            if (in_array($extension, array('html','htm','tpl','xml','css'))) {
                echo '<script language="javascript" type="text/javascript">' . "\n";
                echo 'jQuery(function($) {' . "\n";
                echo '  $("#editContents").markItUp(markituphtml);' . "\n";
                echo '});' . "\n";
                echo '</script>' . "\n";
            } else {
                echo '<script language="javascript" type="text/javascript">' . "\n";
                echo 'jQuery(function($) {' . "\n";
                echo '  $("#editContents").css("height", "384px");' . "\n";
                echo '});' . "\n";
                echo '</script>' . "\n";
            }


            printf('<p class="buttons" style="margin: 0 0 6px 0; clear: both;"><a href="#" onclick="saveEdit();"><img src="pics/accept.png" alt="" />%s</a>',
                __('Save') );
            printf('<a href="#" onclick="saveEditAndContinue();"><img src="pics/accept.png" alt="" />%s</a>',
                __('Save and continue editing'));
            printf('<a href="#" onclick="closeEdit();" class="negative" style="margin-left: 20px;"><img src="pics/delete.png" alt="" />%s</a></p>',
                __('Cancel'));

            if($PIVOTX['config']->get('smarty_cache') || $PIVOTX['config']->get('minify_frontend')) {
                $msg = __("You have Caching and/or Minify enabled. If your changes do not show up immediately, %click here% and disable Caching and Minify while you're working on your site.");
                $msg = preg_replace('/%(.*)%/i', "<a href='index.php?page=configuration#section-1'>\\1</a>", $msg);
                echo "\n\n<p class='small' style='width: 500px;clear: both;'>" . $msg . "</p>\n";
            }

            echo "</form>";

        }

    }


    /**
     * Save an edited file in the ajaxy editor..
     *
     */
    public static function ext_save() {
        global $PIVOTX;


        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        // TODO: make sure we don't try to pass stupid things here!!
        $filename = base64_decode($_POST['basedir']) . $_POST['file'];

        if (is_writable($filename)) {

            $contents = $_POST['contents'];
            $contents = preg_replace('/<\*textarea/i','<textarea', $contents);
            $contents = preg_replace('/<\*\/textarea/i','</textarea', $contents);

            if (!$handle = fopen($filename, 'wb')) {
                printf(__("Cannot open file %s"), $_POST['file']);
                exit;
            }

            // Write $somecontent to our opened file.
            if (fwrite($handle, $contents) === FALSE) {
                printf(__("Cannot write to file %s"), $_POST['file']);
                exit;
            }

            printf(__("Wrote contents to file %s"), $_POST['file']);

            fclose($handle);

            $PIVOTX['events']->add('save_file', "", safeString($_POST['file'], false, "/"));

            // Remove the compiled/parsed pages from the cache.
            if($PIVOTX['config']->get('smarty_cache')){
                $PIVOTX['template']->clear_cache();
            }

        } else {
            printf(__("The file %s is not writable"), $_POST['file']);
        }

    }


    /**
     * Update a weblog's settings..
     *
     * Note: This function is deprecated. 
     *
     */
    public static function ext_updateWeblog() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        $PIVOTX['weblogs']->set($_POST['weblog'], $_POST['key'], $_POST['value']);

        $PIVOTX['events']->add('edit_weblog', "", safeString($_POST['weblog']));

        echo "ok";
    }


    /**
     * Update a weblog's settings in a batch..
     *
     */
    public static function ext_updateWeblogBatch() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_ADMIN);

        // Check against CSRF exploits..
        $PIVOTX['session']->checkCSRF($_POST['csrfcheck']);

        foreach ($_POST as $key=>$value) {
           
            // Skip 'function' and 'csrfcheck' as they are not settings..
            if ($key=="csrfcheck" || $key=="function" || $key=="weblog" || $key=="") {
                continue; 
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $PIVOTX['weblogs']->set($_POST['weblog'], $key, $value);

        }
        
        $PIVOTX['events']->add('edit_weblog', "", safeString($_POST['weblog']));

        echo "ok";
    }


    /**
     * Dynamically load the settings screen for 'subweblogs'. We need to do this
     * dynamically, because the settings are dependant on what it set for the
     * 'frontpage' template.
     *
     */
    public static function ext_loadSubWeblogs() {

        $form = getWeblogForm3($_POST['weblog']);

        $html = $form->fetch();

        echo $html;

    }


    /**
     * Rebuild the entry index
     */
    public static function ext_rebuildIndex() {
        global $PIVOTX, $output;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $output = "";

        $PIVOTX['db']->generate_index();

        $status = sprintf(__('Finished! Generating index for %s entries took %s seconds '), 
            $PIVOTX['db']->get_entries_count(), timeTaken());
        $output .= sprintf("<br />\n<b>%s</b><br />\n", $status);

        /* We are doing the indexing in one run */
        $result = array(
            'text' => $output,
            'done' => true,
        );

        echo json_encode($result);

    }



    /**
     * Rebuild the tag index
     */
    public static function ext_rebuildTagIndex() {
        global $output, $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $output = "";

        // initialise the threshold.. Initially it's set to 10 * the rebuild_threshold,
        // roughly assuming we index 10 entries per second.
        if ($PIVOTX['config']->get('rebuild_threshold')>4) {
            $chunksize = (10 * $PIVOTX['config']->get('rebuild_threshold'));
        } else {
            $chunksize = 100;
        }

        @set_time_limit(0);

        include_once("modules/module_tags.php");

        $start = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $time = (isset($_POST['time'])) ? $_POST['time'] : 0;
        $stop = $start + $chunksize;

        if ($start==0) { $PIVOTX['db']->clearIndex('tags');  }

        $continue = writeTagIndex($start, $stop, $time);

        $time += timeTaken('int');

        $result = array();

        $result['func'] = 'rebuildTagIndex';
        $result['start'] = $stop;
        $result['time'] = $time;
        if($continue) {
            $result['done']  = false;
        } else {
            $result['done']  = true;
            $status = sprintf(__('Finished! Generating index for %s entries took %s seconds '), 
                $PIVOTX['db']->get_entries_count(), $time);
            $output .= sprintf("<br />\n<b>%s</b><br />\n", $status);
        }
        $result['text']  = $output;

        echo json_encode($result);
    }

    /**
     * Rebuild the search index
     */
    public static function ext_rebuildSearchIndex() {
        global $output, $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $output = "";

        // initialise the threshold.. Initially it's set to 10 * the rebuild_threshold,
        // roughly assuming we index 10 entries per second.
        if ($PIVOTX['config']->get('rebuild_threshold')>4) {
            $chunksize = (10 * $PIVOTX['config']->get('rebuild_threshold'));
        } else {
            $chunksize = 100;
        }

        @set_time_limit(0);

        include_once("modules/module_search.php");

        $start = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $time = (isset($_POST['time'])) ? $_POST['time'] : 0;
        $stop = $start + $chunksize;

        if($start==0) { $PIVOTX['db']->clearIndex('search');  }

        $continue = createSearchIndex($start, $stop, $time);

        writeSearchIndex(FALSE);

        $time += timeTaken('int');

        $result = array();

        $result['func'] = 'rebuildSearchIndex';
        $result['start'] = $stop;
        $result['time'] = $time;
        if($continue) {
            $result['done']  = false;
        } else {
            $result['done']  = true;
            $status = sprintf(__('Finished! Generating index for %s entries took %s seconds '), 
                $PIVOTX['db']->get_entries_count(), $time);
            $output .= sprintf("<br />\n<b>%s</b><br />\n", $status);
        }
        $result['text']  = $output;

        echo json_encode($result);

    }


    /**
     * Used to get the filenames when using the autocomplete function in the image popup/insert
     * dialog window.
     *
     *
     */
    public static function ext_autoComplete() {
        global $PIVOTX;

        $uploadpath = $PIVOTX['paths']['upload_base_path'];

        $files = self::autoCompleteFindFiles($uploadpath, '', $_GET['q']);

        sort($files);

        foreach ($files as $file) {
            $imagesize = getimagesize($uploadpath."/".$file);
            $filesize = formatFilesize(filesize($uploadpath."/".$file));
            printf("%s|%s &times; %s, %s.|%s\n", $file, $imagesize[0], $imagesize[1], $filesize, trimText($file, 44));
        }

    }

    /**
     * Helper function for autoComplete()
     *
     * @param string $path
     * @param string $additional_path
     * @param string $match
     * @return array
     */
    protected static function autoCompleteFindFiles($path, $additional_path, $match) {

        $allowed = array("gif", "jpg", "jpeg", "png", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "flv", "avi", "mp3");
        $path = addTrailingSlash($path);

        $files = array();

        $dir = dir($path);
        while (false !== ($entry = $dir->read())) {
            $entries[] = $entry;
        }
        $dir->close();

        foreach ($entries as $entry) {
            $fullname = $path . $entry;
            if ($entry != '.' && $entry != '..' && is_dir($fullname)) {

                // Recursively parse the folder below it.
                $files = array_merge($files, self::autoCompleteFindFiles($fullname, $additional_path.$entry."/", $match));

            } else if (is_file($fullname) && (strpos($fullname, $match)!==false) &&
                    (in_array(strtolower(getExtension($entry)), $allowed)) && (strpos($fullname, ".thumb.")===false) ) {

                // Add the file to our array of matches.
                $files[] = $additional_path.$entry;

            }
        }

        return $files;

    }

    /**
     * Ajax helper function to get the latest news from PivotX.net.
     *
     * You can change the URL by setting the 'notifier_url' to a valid URL in
     * Advanced Configuration.
     *
     * @return string
     *
     */
    public static function ext_getPivotxNews() {
        global $build, $PIVOTX;

        // do not display the news if SafeMode is enabled.
        if($PIVOTX['extensions']->safemode) {
            echo "<p>" . __("The latest PivotX news is not available as long as safemode is enabled.") . "</p>";
            echo "--split--";
            echo "<p>" . __("The latest PivotX news is not available as long as safemode is enabled.") . "</p>";
            die();
        }

        // Setting the labels..
        $readon = "<img class='readmorelink' src='pics/readmore.png' />";
        $showmore = __("Show more items");

        // Get the latest PivotX news, fresh from the website.

        include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

        $sqlite_exists = function_exists("sqlite_query") ? "1" : "-1";

        $notifier_request = base64_encode(sprintf("%s|%s|%s|%s|%s", $_SERVER['SERVER_NAME'], phpversion(), 
            $PIVOTX['db']->db_type, strip_tags($build), $sqlite_exists));
        $notifier_url = getDefault($PIVOTX['config']->get('notifier_url'), "http://pivotx.net/notifier.xml" ) . 
            "?" . $notifier_request;

        $rss = fetch_rss($notifier_url);

        $news = "";

        if (count($rss->items)>0) {

            // Slice it, so no more than 4 items will be shown.
            $rss->items = array_slice($rss->items, 0, 4);

            $count=0;

            foreach($rss->items as $item) {
                $news .= sprintf("<h3>%s</h3> <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>\n",
                    $item['title'],
                    $item['summary'],
                    $item['link'],
                    $readon
                );

                if (($count++)==1) {
                    $news .= "<p id='newsmoreclick'><a onclick='moreNews();'>$showmore</a></p>\n<div id='newsmore'>";
                }

            }

            echo $news;

        } else {
            debug("<p>Oops! I'm afraid I couldn't read the News feed.</p>");
            echo "<p>" . __("Oops! I'm afraid I couldn't read the News feed.") . "</p>";
            debug(magpie_error());
        }

        echo "</div>";

        echo "--split--";

        // If people don't want to see the forum posts, we can end here..
        if ($PIVOTX['config']->get('hide_forumposts')) {
            return;
        }
        $notifier_url = "http://forum.pivotx.net/feed.xml";

        $rss = fetch_rss($notifier_url);

        $news = "";

        if (count($rss->items)>0) {

            // Slice it, so no more than 8 items will be shown.
            $rss->items = array_slice($rss->items, 0, 8);

            $count = 0;

            foreach($rss->items as $item) {

                // Get the description, and remove HTML from it..
                $author = $item['dc']['creator'];
                $description = str_replace("\n", " ", str_replace("<br", " <br", $item['summary']));
                $description = strip_tags($author . ": " .$description);
                $description = trimText($description, 82);


                $news .= sprintf("<h3>%s</h3> <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>\n",
                    htmlspecialchars($item['title'], ENT_NOQUOTES),
                    $description,
                    $item['link'],
                    $readon
                );


                if (($count++)==2) {
                    $news .= "<p id='forumpostsmoreclick'><a onclick='moreForumPosts();'>$showmore</a></p>\n<div id='forumpostsmore'>";
                }

            }

            echo $news;

        } else {
            debug("<p>Oops! I'm afraid I couldn't read the Forum feed.</p>");
            echo "<p>" . __("Oops! I'm afraid I couldn't read the Forum feed.") . "</p>";
            debug(magpie_error());
        }

    }


    /**
     * Fetches tag-information from one of the various social bookmarking websites.
     */
    public static function ext_getTagFeed() {
        global $PIVOTX;

        if(isset($_GET["type"]) && isset ($_GET["tag"])) {

            $type = safeString($_GET["type"]);
            $tag = safeString($_GET["tag"]);

            $amount = getDefault($PIVOTX['config']->get('tag_fetcher_amount'), 8);

            switch($_GET["type"]) {

                case "tagzania":
                    self::_getTagFeedHelper('http://www.tagzania.com/rss/tag/'.str_replace(" ", "+", $tag), 'tagzania.com', $tag);
                    break;

                case "icerocket":
                    self::_getTagFeedHelper('http://www.icerocket.com/search?tab=blog&q='.str_replace(" ", "+", $tag).'&rss=1', 'icerocket.com', $tag);
                    break;

                case "google":
                    self::_getTagFeedHelper('http://blogsearch.google.com/blogsearch_feeds?hl=en&q='.str_replace(" ", "+", $tag).'&btnG=Search+Blogs&num='.
                    $amount .'&output=rss', 'blogsearch.google.com', $tag);
                    break;

                case "delicious":
                    self::_getTagFeedHelper('http://feeds.delicious.com/rss/tag/'.str_replace(" ", "+", $tag), 'Delicious', $tag);
                    break;

                case "43things":
                    self::_getTagFeedHelper('http://www.43things.com/rss/goals/tag?name='.str_replace(" ", "+", $tag), '43things.com', $tag); break;
            }
        }
    }


    protected static function _getTagFeedHelper($feedurl, $feedname, $tag) {
        global $PIVOTX;

        $amount = getDefault($PIVOTX['config']->get('tag_fetcher_amount'), 8);

        include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');

        $rss = fetch_rss($feedurl);

        $output = "";

        if (count($rss->items)>0) {

            // Slice it, so no more than '$amount' items will be shown.
            $rss->items = array_slice($rss->items, 0, $amount);

            foreach($rss->items as $item) {
                $output .= sprintf("\n<li><a href='%s'>%s</a><br /><small>%s</small></li> \n", 
                    // <p>%s <span class='readmore'><a href='%s'>%s</a></span></p>
                    $item['link'],
                    $item['title'],
                    trimText($item['summary'], 200),
                    $readon
                );

            }

        } else {
            debug("<p>Oops! I'm afraid I couldn't read the Tag feed.</p>");
            echo "<p>" . __("Oops! I'm afraid I couldn't read Tag feed.") . "</p>";
            debug(magpie_error());
        }




        $output = @html_entity_decode($output, ENT_COMPAT, 'UTF-8');
        if($output == '')  {
            $output = sprintf(__('Nothing on <strong>%s</strong> for <strong>%s</strong>'), $feedname, $tag);
        } else  {
            $output = sprintf(__('Latest on <strong>%s</strong> for <strong>%s</strong>'), $feedname, $tag) .
            ':<ul class="taggeratilist">' . $output . '</ul>';
        }
        echo $output;

    }


    /**
     * Ajax helper function to facilitate the selection of files from the images/
     * folder.
     *
     */
    public static function ext_fileSelector() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $path = $PIVOTX['paths']['upload_base_path'];
        $url = $PIVOTX['paths']['upload_base_url'];

        if (empty($path) || empty($url) ) {
            echo "Can't continue: paths not set..";
            die();
        }

        $breadcrumbs=array("<a href='#' onclick=\"fileSelectorChangefolder('')\">".basename($path)."</a>");

        if (!empty($_POST['folder'])) {
            $folder = fixPath($_POST['folder'])."/";
            $path .= $folder;
            $url .= $folder;

            $incrementalpath="";
            foreach(explode("/", $folder) as $item) {
                if (!empty($item)) {
                    $incrementalpath = $incrementalpath . $item . "/";
                    $breadcrumbs[] = sprintf("<a href='#' onclick=\"fileSelectorChangefolder('%s')\">%s</a>", $incrementalpath, $item);
                }    
            }
        }

        $breadcrumbs = implode(" &raquo; ", $breadcrumbs);

        $files = array();
        $folders = array();

        $d = dir($path);

        while (false !== ($filename = $d->read())) {

            if (strpos($filename, '.thumb.')!==false || strpos($filename, '._')!==false || $filename==".DS_Store" || 
                    $filename=="Thumbs.db" || $filename=="." || $filename==".." || $filename==".svn" ) {
                // Skip this one..
                continue;
            }        

            if (is_file($path.$filename)) {
                $files[$filename]['link'] = $url.urlencode($filename);
                $files[$filename]['name'] = trimText($filename,50);

                $ext = strtolower(getExtension($filename));
                $files[$filename]['ext'] = $ext;
                $files[$filename]['bytesize'] = filesize($path."/".$filename);
                $files[$filename]['size'] = formatFilesize($files[$filename]['bytesize']);
                if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
                    $dim = getimagesize($path."/".$filename);
                    $files[$filename]['dimension'] = sprintf('%s &#215; %s', $dim[0], $dim[1]);
                    $files[$filename]['image_type'] = $ext;
                }

                $files[$filename]['path'] = $folder.$filename;
            }

            if (is_dir($path.$filename)) {

                $folders[$filename] = array(
                    'link'=> $url.urlencode($filename),
                    'name'=> trimText($filename,50),
                    'path'=> $folder.$filename
                );

            }        
        }
        $d->close();

        ksort($folders);
        ksort($files);

        echo "<div id='fileselector'>";

        printf("<p><strong>%s:</strong> %s </p>", __("Current path"), $breadcrumbs);

        foreach($folders as $folder) {    
            printf("<div class='folder'><a href='#' onclick=\"fileSelectorChangefolder('%s'); return false;\">%s</a></div>",
                addslashes($folder['path']), $folder['name']
            );
        }

        foreach($files as $file) {    
            if ($PIVOTX['config']->get('fileselector_thumbs') && !empty($file['image_type'])) {
                $height = getDefault($PIVOTX['config']->get('fileselector_thumbs_height'), 40);
                $link_text = sprintf("<img src='%sincludes/timthumb.php?h=%s&amp;src=%s' alt='%s' title='%s'>",
                    $PIVOTX['paths']['pivotx_url'], $height, $file['path'], $file['name'], $file['name']
                );
                $extra_style = "style='height: ${height}px; margin-bottom: 5px;'";
            } else {
                $link_text = $file['name'];
                $extra_style = "";
            }
            printf("<div class='file' $extra_style><a href='#' onclick=\"fileSelectorChoose('%s'); return false;\">%s</a> <span>(%s%s)</span></div>",
                addslashes($file['path']),   
                $link_text,
                $file['size'],
                (!empty($file['dimension'])) ? " - ".$file['dimension']." px" : ""
            );
        }

        echo "</div>";

        //echo "<pre>\n"; print_r($folders); echo "</pre>";
        //echo "<pre>\n"; print_r($files); echo "</pre>";

    }


    public static function ext_getExtensionUpdates(){

        require_once( dirname(__FILE__) . '/includes/Snoopy.class.php');

        $url = "http://extensions.pivotx.net/updatecheck/index.php";

        $vars['ids'] = base64_encode($_GET['ids']);

        $snoopy = new Snoopy;
        $snoopy->submit($url, $vars);
        print $snoopy->results;

    }

    public static function ext_setExtensionCompact() {
        global $PIVOTX;

        $currentuser = $PIVOTX['session']->currentUsername();
        $compactview = $PIVOTX['config']->get('compactview');

        if (!empty($compactview)) { $compactview = unserialize($compactview); }
        if (!is_array($compactview)) { $compactview = array(); }

        if ($_GET['set']==1) {
            $compactview[ $currentuser ] = 1;
        } else {
            unset($compactview[$currentuser]);
        }

        // print_r($compactview);

        $PIVOTX['config']->set('compactview', serialize($compactview));

    }

    /**
     * Adds the value of $_GET['log'] to the debug log
     */
    public static function ext_logDebug() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $log = strip_tags($_GET['log']);

        debug($log);
    }

    /**
     */
    public static function ext_approveComment() {
        global $PIVOTX;

        $PIVOTX['session']->minLevel(PIVOTX_UL_NORMAL);

        $result = 'error';

        if (isset($_GET['comment']) && is_numeric($_GET['comment']) && ($_GET['comment'] > 0)) {
            $comment_uid = intval($_GET['comment']);

            include_once "modules/module_comments.php";

            approveComments(array($comment_uid));

            $result = 'ok';
        }

        echo $result;
    }
}

?>
