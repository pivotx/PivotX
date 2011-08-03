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
 * Class to work with Pages, using the flat file storage model.
 *
 */
class PagesFlat {

    /**
     * Initialisation.
     *
     * @return PagesFlat
     */
    function PagesFlat() {
        global $PIVOTX;

        //init vars..
        static $initialisationchecks;

        if (!$initialisationchecks) {
            // Verify that the pages folder exists.
            if (!file_exists($PIVOTX['paths']['db_path']."pages")) {
                makeDir($PIVOTX['paths']['db_path']."pages");
            }
        }

        // Create the default pages. They can be recreated by setting
        // 'dont_recreate_default_pages' to 0 in the advanced config.
        if (!$initialisationchecks && !$PIVOTX['config']->get('dont_recreate_default_pages')) {
            $PIVOTX['config']->set('dont_recreate_default_pages', 1);
            
            $now = date("Y-m-d-H-i", getCurrentDate());
            $userdata = $PIVOTX['users']->getUsers();
            $username = $userdata[0]['username'];

            $this->index = $this->getIndex();

            $pages = array();

            $pages['1'] = array(
                'user' => $username,
                'sortorder' => 10,
                'allow_comments' => 1,
                'code' => 1,
                'date' => $now.'-01',
                'uri' => 'about',
                'chapter' => 0,
                'publish_date' => $now.'-01',
                'edit_date' => $now.'-01',
                'title' => __('About PivotX'),
                'subtitle' => '',
                'template' => '',
                'introduction' => "<p>Hi! This website runs on <a href=\"http://pivotx.net\">PivotX</a>,
                the coolest free and open tool to power your blog and website. To change this text, edit '<tt>About PivotX</tt>',
                under '<tt>Pages</tt>' in the PivotX backend.</p>",
                'body' => '<p>PivotX is a feature rich weblogging tool that is simple enough for the novice
weblogger to use and complex enough to meet the demands of advanced webmasters.
It can be used to publish a variety of websites from the most basic weblog to
very advanced CMS style solutions.</p>
<p>PivotX is - if we do say so ourselves - quite an impressive piece of software.
It is made even better through the use of several external libraries. We thank their
authors for the time taken to develop these very useful tools and for making
them available to others.</p>
<p>Development of PivotX (originally Pivot) started back in 2001 and has continuously
forged ahead thanks to the efforts of a lot of dedicated and very talented people. 
The PivotX core team is still very active but keep in mind that PivotX would not be 
what it is today without the valuable contributions made by several other people.</p>',
                'convert_lb' => '',
                'status' => 'publish',
                'keywords' => '',
                'uid' => 1,
                'link' => '/page/welcome',
                'extrafields' => array(
                    'image' => '',
                    'description' => '' 
                )
            );
            $pages['2'] = array(
                'user' => $username,
                'sortorder' => 10,
                'allow_comments' => 1,
                'code' => 2,
                'date' => $now.'-01',
                'uri' => 'links',
                'chapter' => 0,
                'publish_date' => $now.'-01',
                'edit_date' => $now.'-01',
                'title' => __('Links'),
                'subtitle' => '',
                'template' => '',
                'introduction' => '<p>Some links to sites with more information:</p>
<ul>
<li>PivotX - <a href="http://pivotx.net">The PivotX website</a></li>
<li>Get help on <a href="http://forum.pivotx.net">the PivotX forum</a></li>
<li>Read <a href="http://book.pivotx.net">the PivotX documentation</a></li>
<li>Browse for <a href="http://themes.pivotx.net">PivotX Themes</a></li>
<li>Get more <a href="http://extensions.pivotx.net">PivotX Extensions</a></li>
<li>Follow <a href="http://twitter.com/pivotx">@pivotx on Twitter</a></li>
</ul>
<p><small>To change these links, edit \'<tt>Links</tt>\', under \'<tt>Pages</tt>\' in the PivotX backend.</small></p>',
                'body' => '',
                'convert_lb' => '',
                'status' => 'publish',
                'keywords' => '',
                'uid' => 2,
                'link' => '/page/about-pivotx',
                'extrafields' => array(
                    'image' => '',
                    'description' => '' 
                )
            );

            for ($i=1; $i<3; $i++) {
                if (!file_exists($PIVOTX['paths']['db_path']."pages/page_$i.php")) {
                    $this->savePage($pages[$i]);
                }
            }
        }

        $initialisationchecks = true;
     }

    /**
     * Get the current index of the pages.
     *
     * @return array
     */
    function getIndex($filter_user="", $excerpts=false, $links=false) {
        global $PIVOTX;

        // If we've already built the index, and we don't need to filter
        // on user or to create excerpts or links.
        if (is_array($this->index) && empty($filter_user) && ($excerpts==false) && ($links==false)) {
            return $this->index;
        }

        // Load the index, if it exists.
        if (file_exists($PIVOTX['paths']['db_path']."pages/pages.php")) {
            $this->index = loadSerialize($PIVOTX['paths']['db_path']."pages/pages.php");
        } else {
            $this->index = getDefaultPages();
            $this->saveIndex(false);
        }

        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
        $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);
        
        // Spider the folder, read all pages..
        $dir = dir($PIVOTX['paths']['db_path']."pages/");
        while (false !== ($entry = $dir->read())) {
            if (preg_match('/^page_[0-9]+.php$/',$entry)) {
                $fullpage = loadSerialize($PIVOTX['paths']['db_path']."pages/".$entry);

                // Skip corrupted files.
                if (!$fullpage) {
                    debug("Couldn't load page from file $entry");
                    continue;
                }

                $page = array(
                    'uid' => $fullpage['code'],
                    'title' => $fullpage['title'],
                    'subtitle' => $fullpage['subtitle'],
                    'user' => $fullpage['user'],
                    'date' => $fullpage['date'],
                    'publish_date' => $fullpage['publish_date'],
                    'chapter' => $fullpage['chapter'],
                    'uri' => $fullpage['uri'],
                    'status' => $fullpage['status'],
                    'template' => $fullpage['template'],
                    'sortorder' => $fullpage['sortorder'],
                    'editable' => $PIVOTX['users']->allowEdit('page', $fullpage['user'])
                );

                // Make the 'excerpts', if we have to..
                if ($excerpts) {
                    $page['excerpt'] = makeExcerpt($fullpage['subtitle'].$fullpage['introduction']);
                }

                // Make the links, if required..
                if ($links) {
                    $page['link'] = makePageLink($fullpage['uri']);
                }

                // Skip this page, if we're filtering for a user, and the user doesn't match.
                if (!empty($filter_user) && ($filter_user!=$pageuser['username']) ) {
                    continue;
                }

                if (isset($this->index[ $page['chapter'] ])) {
                    $this->index[ $page['chapter'] ]['pages'][] = $page;
                } else {
                    $this->index['orphaned']['pages'][] = $page;
		    $this->index['orphaned']['editable'] = $PIVOTX['users']->allowEdit('chapter');
                }
            }
        }
        $dir->close();

        foreach($this->index as $key => $chapter) {
            // Sort the pages if there are any.
            if (isset($chapter['pages'])) {
                usort($chapter['pages'], array($this, 'sort'));
            }
            $chapter['editable'] = $PIVOTX['users']->allowEdit('chapter');
            $this->index[$key]= $chapter;
        }

        return $this->index;

    }

    /**
     * Save the index to the file system
     *
     */
    function saveIndex($reindex=true) {
        global $PIVOTX;

        if($reindex) {
        // Make sure we have a fresh index to start with..
            unset($this->index);
            $this->getIndex();
        }
        
        $my_index = $this->index;

        // Cleanup..
        foreach ($my_index as $key => $value) {

            // In the index we only store the chapters. not the pages..
            unset($my_index[$key]['pages']);

            // Don't store empty chapters.
            if ($value['chaptername']=="") {
                unset($my_index[$key]);
            }
        }

        saveSerialize($PIVOTX['paths']['db_path']."pages/pages.php", $my_index );

        unset($this->index);
        $this->getIndex();

    }

    /**
     * Sets the index from
     *
     * @param array $index
     */
    function setIndex ( $index ) {

        $this->index = $index;

    }

    /**
     * Add a chapter.
     *
     * @param array $chapter
     */
    function addChapter($chapter) {

        $new_chapter = array(
            'chaptername' => $chapter['chaptername'],
            'description' => $chapter['description'],
            'sortorder' => $chapter['sortorder']
        );

        $this->index[] = $new_chapter;

        return $this->index;

    }


    /**
     * Delete a chapter.
     *
     * @param integer $uid
     */
    function delChapter($uid) {

        unset($this->index[$uid]);

        return $this->index;

    }


    /**
     * Update the information for a chapter.
     *
     * @param integer $id
     * @param array $chapter
     */
    function updateChapter($id,$chapter) {

        $this->index[$id]['chaptername'] = $chapter['chaptername'];
        $this->index[$id]['description'] = $chapter['description'];
        $this->index[$id]['sortorder'] = $chapter['sortorder'];

        return $this->index;

    }


    /**
     * Get a single page by its uid
     *
     * @param integer $uid
     * @return array
     */
    function getPage($uid) {
        global $PIVOTX;

        $page = loadSerialize($PIVOTX['paths']['db_path']."pages/page_$uid.php");

        $page['link'] = makePageLink($page['uri']);
        if ($page['title'] == '') {
            $page['title'] = __('No title..');
        }
            
        // Set the chapter name and description (in addition to just the chapter's ID)
        $chapters = $PIVOTX['pages']->getIndex();
        $page['chaptername'] = $chapters[ $page['chapter'] ]['chaptername'];
        $page['chapterdesc'] = $chapters[ $page['chapter'] ]['description'];
        
        return $page;

    }

    /**
     * Get a single page by its URI
     *
     * @param string $uri
     * @return array
     */
    function getPageByUri($uri) {
        global $PIVOTX;

        foreach($this->index as $chapter) {

            // Search the chapter only if there are any pages.
            if (!isset($chapter['pages'])) {
                continue;
            }

            foreach($chapter['pages'] as $page) {

                if ($page['uri'] == $uri) {
                    return ($this->getPage($page['uid']));
                }

            }

        }


        // If we get to here, we couldn't find the page. Bummer!
        return array();


    }


    /**
     * Gets a list of the $amount latest pages
     *
     * @param integer $amount
     */
    function getLatestPages($amount, $filter_user="") {

        $pages = array();

        $pageindex = $this->getIndex($filter_user);

        // We need a flat array, with just the pages. We use the date as the 
        // array index so we can easily sort the array.
        foreach($pageindex as $chapter) {
            // Skip chapters with no pages
            if (!isset($chapter['pages'])) {
                continue;
            }
            foreach ($chapter['pages'] as $page) {
                $page['chaptername'] = $chapter['chaptername'];
                $pages[$page['date']] = $page;
            }
        }

        krsort($pages);
        $pages = array_slice($pages, 0, $amount);

        return $pages;

    }


    /**
     * Delete a single page
     *
     * @param integer $uid
     */
    function delPage($uid) {
        global $PIVOTX;

        unlink($PIVOTX['paths']['db_path']."pages/page_$uid.php");

        $this->saveIndex();

    }


    /**
     * Save a single page
     *
     * @param integer $id
     * @param array $page
     */
    function savePage($page) {
        global $PIVOTX;

        // Get a new code, for newly created pages..
        if (($page['code']=="") || ($page['code']==">")) {
            for($i=1; $i<1000; $i++) {
                if (!file_exists($PIVOTX['paths']['db_path']."pages/page_$i.php")) {
                    $page['code'] = $i;
                    $page['uid'] = $i;
                    break;
                }
            }
        }

        unset($page['oldstatus']);
        if ($page['title'] == '') { 
            $page['title'] = __('No title..'); 
        }
        
        // Edit date is 'now'..
        $page['edit_date'] = date("Y-m-d-H-i", getCurrentDate());

        saveSerialize($PIVOTX['paths']['db_path']."pages/page_".intval($page['code']).".php", $page);

        // TODO: Update the search index.

        $this->saveIndex();

        // Return the uid of the page we just inserted / updated..
        return intval($page['code']);


    }

    /**
     * Checks if any pages set to 'timed publish' should be published.
     *
     * This code might have to be optimized to only check the latest pages for 
     * a very big site.
     */
    function checkTimedPublish() {
        $date = date("Y-m-d-H-i", getCurrentDate());

        $pageindex = $this->getIndex();

        foreach($pageindex as $chapter) {
            // Skip chapters with no pages
            if (!isset($chapter['pages'])) {
                continue;
            }
            foreach ($chapter['pages'] as $page) {
                if (($page['status'] == 'timed') && ($page['publish_date'] <= $date)) {
                    $page = $this->getPage($page['uid']);
                    $page['date'] = $page['publish_date'];
                    $page['status'] = "publish";
                    $this->savePage($page);
                    /* Disabled for now since it sendMailNotification doesn't support pages yet:
                    if (!$PIVOTX['config']->get('disable_new_page_notifications')) {
                        sendMailNotification('page',$page);
                    }
                    */
                }
            }
        }
    }
    
    /**
     * Sort the pages based on the order and string comparison
     * of (page) title if order is identical.
     * 
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        if ($PIVOTX['config']->get('sort_pages_by_alphabet')==true) {
            // If we set 'sort_pages_by_alphabet' to true, always sort by alphabet..
            return strcmp($a['title'],$b['title']);
        } else if ($a['sortorder'] == $b['sortorder']) {
            // Else sort by alphabet, if order is the same..
            return strcmp($a['title'],$b['title']);
        } else {
            // else sort by order..
            return ($a['sortorder'] < $b['sortorder']) ? -1 : 1;
        }

    }

}


?>
