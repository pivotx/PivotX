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
if(!defined('INPIVOTX')){ exit('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";

class EntriesFlat {

    // the name of the log
    var $logname;

    // the data for the current entry
    var $entry;

    // nice, big arrays with all the dates, categories and uris..
    var $date_index;
    var $cat_index;
    var $uri_index;

    // a somewhat smaller array for the entries that share the same
    // directory as the current entry
    var $update_mode;
    var $updated;
    var $entry_index;
    var $entry_index_filename;

    // some helper variables
    var $all_cats;

    // public functions

    function EntriesFlat($loadindex=TRUE, $allow_write=TRUE) {
        global $PIVOTX;
        
        //init vars..
        static $initialisationchecks;

        // Logname will be phased out eventually, since all will be based on categories.
        $this->logname = "standard";

        $this->entry = Array('code' => '', 'id' => '',  'template' => '',  'date' => '',  
            'user' => '',  'title' => '',  'subtitle' => '',  'introduction' => '',  'body' => '', 
            'media' => '',  'links' => '',  'uri' => '',  'filename' => '',  'category' => '');

        $this->entry_index_filename = "";
        $this->entry_index = Array();
        $this->date_index = Array();
        $this->cat_index = Array();
        $this->all_cats = Array();
        $this->uri_index = Array();

        $this->update_mode=TRUE;
        $this->global_reindex=FALSE;

        // Load the index..
        if ($loadindex) {
            $this->read_date_index();
            $this->allow_index=TRUE;
        } else {
            $this->allow_index=FALSE;
        }

        // Load the index..
        if ($allow_write) {
            $this->allow_write=TRUE;
        } else {
            $this->allow_write=FALSE;
        }

        // Any initial settings that might be missing
        if (!$initialisationchecks) {
            // In case it hasn't been set earlier
            if ($PIVOTX['config']->get('entries_per_dir') == '') {
                $PIVOTX['config']->set('entries_per_dir', 100);
            }
        }

        // Create the default entries. They can be recreated by setting
        // 'dont_recreate_default_entries' to 0 in the advanced config.
        if (!$initialisationchecks && !$PIVOTX['config']->get('dont_recreate_default_entries')) {
            $PIVOTX['config']->set('dont_recreate_default_entries', 1);

            // We are setting the timestamp 60 seconds back to avoid problems with
            // the cache on the very first page display after installation.
            $now = date("Y-m-d-H-i", getCurrentDate()-60);

            $version = __("Welcome to"). " " . strip_tags($GLOBALS['build']);
            $userdata = $PIVOTX['users']->getUsers();
            $username = $userdata[0]['username'];

            $entries = array();

            $entries['1'] = array(
                'code' => 1,
                'date' => $now.'-00',
                'introduction' => '
<p>If you can read this, you have successfully installed [[tt tag="PivotX"]]. 
Yay!! To help you further on your way, the following links might be of use to you:</p>
<ul>
<li>PivotX.net - <a href="http://pivotx.net">The official PivotX website</a></li>
<li>The online documentation at <a href="http://book.pivotx.net">PivotX Help</a> should be of help.</li>
<li>Get help on <a href="http://forum.pivotx.net">the PivotX forum</a></li>
<li>Browse for <a href="http://themes.pivotx.net">PivotX Themes</a></li>
<li>Get more <a href="http://extensions.pivotx.net">PivotX Extensions</a></li>
<li>Follow <a href="http://twitter.com/pivotx">@pivotx on Twitter</a></li>
</ul>
<p>And, of course: Have fun with PivotX!</p>',
                'body' => '
<h3>More</h3>
<p>All text that you write in the \'body\' part of the entry will only appear on the entry\'s own page. 
To see how this works, edit this entry in the PivotX administration by going to \'Entries &amp; Pages\' 
&raquo; \'Entries\' &raquo; \'Edit\'.</p>',
                'category' => array(
                    '0' => 'default'
                ),
                'publish_date' => $now.'-00',
                'edit_date' => $now.'-00',
                'title' => $version,
                'subtitle' => '',
                'user' => $username,
                'convert_lb' => 5,
                'status' => 'publish',
                'allow_comments' => 1,
                'keywords' => 'pivot pivotx',
                'vialink' => '',
                'viatitle' => '',
                'comments' => array(
                    '0' => array(
                        'name' => 'Bob',
                        'email' => '',
                        'url' => 'http://pivotx.net',
                        'ip' => '127.0.0.1',
                        'date' => $now.'-10',
                        'comment' => 'Hi! This is what a comment looks like!',
                        'registered' => -1,
                        'notify' => -1,
                        'discreet' => -1,
                        'moderate' => -1
                    )
                ),
                'uri' => makeURI($version),
                'uid' => 1,
                'extrafields' => array(
                    'image' => '',
                    'description' => '' 
                )
            );  
            $entries['2'] = array(
                'code' => 2,
                'date' => $now.'-01',
                'introduction' => '
<p>This is an entry in the linkdump category. Most people use this to 
quickly post links to interesting sites or resources. If you write a 
new entry, and select \'linkdump\' as the category, the entry will
automagically be published in this section of your weblog.</p>',
                'body' => '',
                'category' => array(
                    '0' => 'linkdump'
                ),
                'publish_date' => $now.'-01',
                'edit_date' => $now.'-01',
                'title' => __('Example linkdump...'),
                'subtitle' => '',
                'user' => $username,
                'convert_lb' => 5,
                'status' => 'publish',
                'allow_comments' => 1,
                'keywords' => '',
                'vialink' => '',
                'viatitle' => '',
                'comments' => array(
                ),
                'uri' => 'example-linkdump',
                'uid' => 2,
                'extrafields' => array(
                    'image' => '',
                    'description' => '' 
                )
            );  

            for ($i=1; $i<3; $i++) {
                if (!file_exists($PIVOTX['paths']['db_path']."standard-00000/0000$i.php")) {
                    $this->set_entry($entries[$i]);
                    $this->save_entry();
                }
            }
        }

        $initialisationchecks = true;
    }



    /**
     * Gets an array of archives - flat file implementation.
     *
     * Reads or creates/updates "ser-archives.php". The file 
     * contains 3 arrays - one for each time unit.
     *
     * @param boolean $force tells if "ser-archives.php" should be updated
     * @param string $unit the unit of the archives
     * @return array
     */
    function getArchiveArray($force=FALSE,$unit_para='') {
        global $PIVOTX;
        $units = array('week','month','year');
        $updated = false;

        if ( ($force) || (!file_exists($PIVOTX['paths']['db_path'].'ser-archives.php')) ) {

            $updated = true;
            $Archive_array=array();
            $lastdate = 0;
            foreach ($units as $unit) {
                $Archive_array[$unit] = array();
            }

            ksort($this->date_index);

            foreach ($this->date_index as $code => $date) {

                $this->entry['code']=$code;
                $this->check_current_index();
                $this->entry_from_index();

                if ($this->entry['status'] == 'publish') {
                    $in_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($this->entry['category']);

                    foreach ($in_weblogs as $in_weblog) {
                        foreach ($units as $unit) {
                            $name = makeArchiveName($this->entry['date'], $in_weblog, $unit);
                            $Archive_array[$unit][$in_weblog][$name] = $this->entry['date'];
                        }
                    }
                }
            }

        } else {

            // just load the file, and get the last 3 entries. Much easier..
            $Archive_array = loadSerialize($PIVOTX['paths']['db_path'].'ser-archives.php');
            $entries_arr = $this->read_entries(array(
                'full'=>false, 'show'=>3, 'status'=>'publish', 'order'=>'desc'));

            // loop for all entries
            foreach ($entries_arr as $loopentry) {

                // then loop for all the weblogs that publish this entry
                $in_weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($loopentry['category']);

                foreach ($in_weblogs as $in_weblog) {
                    foreach ($units as $unit) {
                        $name = makeArchiveName($loopentry['date'], $in_weblog, $unit);
                        $Archive_array[$unit][$in_weblog][$name] = $loopentry['date'];
                    }
                }

            }

        }

        // sort the array, to maintain correct order..
        foreach ($units as $unit) {
            foreach ($Archive_array[$unit] as $key => $value) {
                krsort($Archive_array[$unit][$key]);
            }
        }

        if ($updated) {
            // save the archive_array, for easier retrieval next time..
            saveSerialize($PIVOTX['paths']['db_path'].'ser-archives.php', $Archive_array);
        }

        if ($unit_para == '') {
            return $Archive_array;
        } else {
            return $Archive_array[$unit_para];
        }
    }





    function disallow_write() {
        $this->allow_write=FALSE;
    }


    function allow_write() {
        $this->allow_write=TRUE;
    }

    /**
     * Gets the number of entries
     * 
     * @param mixed $params optional, the same as read_entries
     * @return int
     */
    function get_entries_count($params=false) {

        if($params) {
            // Ensuring that we don't fetch full entries, and unsetting 
            // params that don't make sense when only counting.
            $params['full'] = false;
            unset($params['order']);
            unset($params['orderby']);
            if (isset($params['show'])) {
                debug("Asking for an amount of entries ('show') doesn't make sense when counting - ignoring.");
                unset($params['show']);
            }
            return count($this->read_entries($params));
        }

        return count($this->date_index);

    }

    /**
     * Gets the code of the next entry - flat file implementation.
     *
     * @param int $num
     * @return int
     */
    function get_next_code($num) {

        $code = $this->entry['code'];
        $ok = TRUE;
        $found=0;

        // first we move the pointer to where we are at now..
        reset($this->date_index);
        while ($ok && (key($this->date_index) != $code)) {
            $ok = next($this->date_index);
        }

        // then step back to the previous one
        do {
            $ok = next($this->date_index);
            $found++;
        } while ($ok && ($found<$num) );

        // if $ok, that must mean the last one is the one.
        if ($ok) {
            return key($this->date_index);
        } else {
            return false;
        }

    }

    /**
     * Gets the code of the previous entry - flat file implementation.
     *
     * @param int $num
     * @return int
     */
    function get_previous_code($num) {

        $code = $this->entry['code'];
        $ok=TRUE;
        $found=0;

        // first we move the pointer to where we are at now..
        reset($this->date_index);
        while ($ok && (key($this->date_index) != $code)) {
            $ok = next($this->date_index);
        }

        // then step back to the previous one
        do {
            $ok = prev($this->date_index);
            $found++;
        } while ($ok && ($found<$num) );

        // if $ok, that must mean the last one is the one.
        if ($ok) {
            return key($this->date_index);
        } else {
            return false;
        }

    }


    function need_index() {

        // the flat file database needs an index.
        return TRUE;

    }

    // This will rebuild the index of the flatfile Database
    function generate_index() {
        global $PIVOTX;

        $this->global_reindex=TRUE;
        $this->update_mode=FALSE;
        $this->date_index = Array();
        $this->cat_index = Array();
        $this->all_cats = $PIVOTX['categories']->getCategorynames();
        $this->uri_index = Array();

        debug("Start rebuild index");


        $d = dir($PIVOTX['paths']['db_path']);

        while ($filename=$d->read()) {
            $ext=getExtension($filename);
            $pos=strpos($filename, $this->logname."-");
            if ( (!($pos===FALSE)) && ($pos==0) ) {
                $this->index_entries($filename);
            }
        }
        $d->close();

        debug("Finish rebuild index");

        $this->write_date_index();


    }

    /**
     * Tells if the entry exists - flat file implementation.
     *
     * @param int $code The code/id of the entry.
     * @return boolean
     */
    function entry_exists($code) {

        $filename=$this->set_filename($code);

        return file_exists($filename);

    }

    /**
     * Gets the date for an entry
     *
     * @param int $code
     * @return string
     */
    function get_date($code) {

        if (isset($this->date_index[$code])) {
            return $this->date_index[$code];
        } else {
            return 0;
        }

    }

    /**
     * Retrieves a full entry as an associative array, and returns it. The $code
     * parameter can be a code/uid or an URI. The optional $date parameter helps
     * to narrow it down, if there's more than one option.
     *
     * @param mixed $code
     * @param string $date
     * @return array
     */
    function read_entry($code, $date='') {
        global $PIVOTX;

        if (is_numeric($code)) {
            $filename=$this->set_filename($code);
        } else {
            $code = $this->get_code_from_uri($code, $date);
            $filename=$this->set_filename($code);
        }

        if (!$this->read_entry_filename($filename, FALSE, $force)) {
            return FALSE;
        }

        // Make sure the different uids are set ... These are
        // needed to be consistent (with the result from the SQL db).
        $this->entry['uid'] = $this->entry['code'];

        foreach ($this->entry['comments'] as $key => $value) {
            $this->entry['comments'][$key]['entry_uid'] = $this->entry['code'];
            $this->entry['comments'][$key]['uid'] = $key;
            $this->entry['comments'][$key]['allowedit'] = $PIVOTX['users']->allowEdit('comment', $this->entry['user']);
        }

        foreach ($this->entry['trackbacks'] as $key => $value) {
            $this->entry['trackbacks'][$key]['entry_uid'] = $this->entry['code'];
            $this->entry['trackbacks'][$key]['uid'] = $key;
            $this->entry['trackbacks'][$key]['allowedit'] = $PIVOTX['users']->allowEdit('trackback', $this->entry['user']);
        }

        // Set the link..
        $this->entry['link'] = makeFileLink($this->entry, '', '');

        return $this->entry;
    }
    
   /**
     * Read a bunch of entries
     *
     * @param array $params
     * @return array
     */
    function read_entries($params) {
        global $PIVOTX;

        // Indicator - is the entries requested by UID.
        $find_by_uid = false;

        // Filtering indicators
        $filteronextrafields = false;
        $filteroncategory = false;
        $filteronuser = false;
        $filteronstatus = false;

        $params['orderby'] = getDefault($params['orderby'], 'date'); 
        $params['order'] = getDefault($params['order'], 'asc'); 
        if (!empty($params['status'])) {
            $filteronstatus = true;
        }
  
        if (!empty($params['uid'])) {
            // If 'uid' is given, we ignore everything but 'status', 'order' and 'orderby'.
            if (is_array($params['uid'])) {
                $aUids = $params['uid'];
            } else {
                $aUids= explode(",",$params['uid']);
            }
            foreach($aUids as $k=>$uid) {
                if(!is_numeric($uid)) {
                    unset($aUids[$k]);
                }
            }
            if(!empty($aUids)) {
                $params['uid'] = $aUids;
                $find_by_uid = true;
            }
        } else {
            if(!empty($params['start'])) {
                $params['date'] = "";
                $params['start'] = explode("-", $params['start']);
                $params['start'] = sprintf("%s-%02s-%02s-%02s-%02s", $params['start'][0], $params['start'][1], 
                    $params['start'][2], $params['start'][3], $params['start'][4]);
            }
            if(!empty($params['end'])) {
                $params['date'] = "";
                $params['end'] = explode("-", $params['end']);
                $params['end'] = sprintf("%s-%02s-%02s-%02s-%02s", $params['end'][0], $params['end'][1], 
                    $params['end'][2], $params['end'][3], $params['end'][4]);
            }
            if(!empty($params['date'])) {
                $params['date'] = explode("-", $params['date']);
                $year = (int) $params['date'][0];
                if (count($params['date']) == 1) {
                    $start = sprintf("%s-%02s-%02s-00-00", $year, 1, 1);
                    $year++;
                    $end = sprintf("%s-%02s-%02s-00-00", $year, 1, 1);
                } elseif (count($params['date']) == 2) {
                    $month = (int) $params['date'][1];
                    $start = sprintf("%s-%02s-%02s-00-00", $year, $month, 1);
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                    $end = sprintf("%s-%02s-%02s-00-00", $year, $month, 1);
                } else {
                    $month = (int) $params['date'][1];
                    $day = (int) $params['date'][2];
                    $start = sprintf("%s-%02s-%02s-00-00", $year, $month, $day);
                    $end = sprintf("%s-%02s-%02s-23-59", $year, $month, $day);
                }
                $params['start'] = $start;
                $params['end'] = $end;
            }
             
            if (!empty($params['user'])) {
                $filteronuser = true;
                if (!is_array($params['user'])) {
                    $params['user'] = array_map('trim', explode(',', $params['user']));
                }
            }
            if (!empty($params['extrafields'])) {
                $filteronextrafields = true;
                if (!is_array($params['extrafields'])) {
                    $params['extrafields'] = array_map('trim', explode(',', $params['extrafields']));
                }
            }
             if (!empty($params['cats'])) {
                $filteroncategory = true;
                if (!is_array($params['cats'])) {
                    $params['cats'] = array_map('trim', explode(',', $params['cats']));
                }
            }
        }

        $entries_arr = array();

        // Build the array of entries, either by uid or by date range/amount
        if ($find_by_uid) {

            foreach($params['uid'] as $uid) {
                if ($this->entry_exists($uid)) {
                    $this->entry['code'] = $this->entry['uid'] = $uid;
                    $this->check_current_index();
                    $this->entry_from_index();
                    if ($filteronstatus && ($this->entry['status'] != $params['status'])) {
                        continue;
                    } else{
                        $entries_arr[] = $this->entry;
                    }
                }
            }

            // Sort the entries array
            $sort_arr = $this->_getSortArray($entries_arr, $params['orderby']);
            array_multisort($sort_arr, $entries_arr);

            // Order the entries according to 'order' by reversing if descending.
            if ($params['order'] != 'asc') {
                $entries_arr_reversed = true;
                $entries_arr = array_reverse($entries_arr);
            }

            $final_entries_arr = $entries_arr;
 
        } else {

            $entries_arr_expanded = false;
            $entries_arr_reversed = false;

            // Handling the special case of someone asking for zero entries. 
            if (isset($params['show']) && ($params['show'] == 0)) {
                return array();
            }

            // Handling the special case of no entries. 
            if (count($this->date_index) == 0) {
                return array();
            } 

            // Building a complete array with all entries if 'orderby' isn't 
            // date or filtering forces us to do so.
            if (($params['orderby'] != 'date') || $filteronextrafields || $filteronuser) {
                reset($this->date_index);
                foreach ($this->date_index as $code => $date) {
                    $this->entry['code'] = $code;
                    $this->check_current_index();
                    $this->entry_from_index();
                    if (
                            (!$filteronextrafields || $this->intersect($params['extrafields'], $this->entry['extrafields'])) &&
                            (!$filteronstatus || ($this->entry['status'] == $params['status'])) &&
                            (!$filteronuser || $this->intersect($params['user'], $this->entry['user']))
                        ) {
                        $entries_arr[] = $this->entry;
                    }
                }
                $entries_arr_expanded = true;
           } else {
                // Converting the date index to the same form as the complete array.
                // (Filtering only on published status.)
                reset($this->date_index);
                $current_date = date("Y-m-d-H-i", getCurrentDate());
                foreach ($this->date_index as $code => $date) {
                    if ($filteronstatus && ($params['status'] == 'publish') && ($date > $current_date)) {
                        continue;
                    }
                    $entries_arr[] = array('code'=> $code, 'uid'=> $code, 'date'=>$date);
                }
            }

            // Now filter on category if needed (using the cat_index to speed things up)
            if ($filteroncategory) {
                foreach ($entries_arr as $key => $entry) {
                    if (!$this->intersect($params['cats'], $this->cat_index[$entry['code']])) {
                        unset($entries_arr[$key]);
                    }
                }
            }

            // If no entries found so far, return an empty array immediately.
            if (count($entries_arr) == 0) {
                return array();
            }

            // Set random order or do a proper sort of the entries 
            // array (if not ordered by date)
            $sort_arr = array();
            if ($params['order'] == "random") {
                $sort_arr = range(1, count($entries_arr));
                shuffle($sort_arr);
                array_multisort($sort_arr, $entries_arr);
            } elseif ($params['orderby'] != 'date') {
                $sort_arr = $this->_getSortArray($entries_arr, $params['orderby']);
                array_multisort($sort_arr, $entries_arr);
            }

            // Currently ignoring 'end' if 'offset' given.
            if (!empty($params['offset'])) {
                if (!empty($params['end'])) {
                    debug("'end' and 'offset' given - ignoring 'end'.");
                    $params['end'] = '';
                }
            }

            // Skip to where we start if 'start' (date) given *before* 
            // potientially reversing if descending.
            $start_found = false;
            reset($entries_arr);
            if (!empty($params['start'])) {
                while (true) {
                    $entry = current($entries_arr);
                    if ($entry['date'] < $params['start']) {
                        if (!next($entries_arr)) {
                            // Found no entries
                            return array();
                        }
                    } else {
                        $start_found = true;
                        break;
                    }
                }
            }

            // skip to where we start if 'offset' given
            if (!$start_found) {
                // Order the entries according to 'order' by reversing if descending.
                if ($params['order'] != 'asc') {
                    $entries_arr_reversed = true;
                    $entries_arr = array_reverse($entries_arr);
                }
                reset($entries_arr);
                if (!empty($params['offset'])) {
                    for( $i=0; $i < $params['offset']; $i++ ) {
                        if (!next($entries_arr)) {
                            // Found no entries
                            return array();
                        }
                    }
                }
            }
           
            // get the wanted entries based on 'end' (date) or 'show'.    
            $count_entries = 0;
            $final_entries_arr = array();
            while (true) {
                $entry = current($entries_arr);
                if (!empty($params['end'])) {
                    /* If the array is reversed we skip all the entries
                     * that are newer than the end date. Else we stop
                     * when we get to the first entry that is newer than the 
                     * end date.
                     */
                    if ($entries_arr_reversed) {
                        if ($entry['date'] > $params['end']) {
                            $index = key($entries_arr);
                            unset($entries_arr[$index]);
                            if (!next($entries_arr)) {
                                break;
                            } else {
                                continue;
                            }
                        }
                    } else {
                        if ($entry['date'] > $params['end']) {
                            break;
                        }
                    }
                } 
                if (!empty($params['show']) && ($count_entries == $params['show'])) {
                    break;
                }
                if (!$entries_arr_expanded) {
                    $this->entry['code'] = $entry['code'];
                    $this->check_current_index();
                    $this->entry_from_index();
                    if (
                            (!$filteroncategory || $this->intersect($params['cats'], $this->entry['category'])) &&
                            (!$filteronstatus || ($this->entry['status'] == $params['status'])) &&
                            (!$filteronuser || $this->intersect($params['user'], $this->entry['user']))
                        ) {
                        $final_entries_arr[] = $this->entry;
                        $count_entries++;
                    }
                } else{
                    // If we are already having an complete array of entries 
                    // we have filtered and the entry should always be counted.
                    $final_entries_arr[] = $entry;
                    $count_entries++;
                }
                if (!next($entries_arr)) {
                    break;
                }
            }
        } // Finished building the array of entries.

        // Order the entries according to 'order' if not done earlier.
        if (!$entries_arr_reversed && ($params['order'] != 'asc')) {
            $final_entries_arr = array_reverse($final_entries_arr);
        }

        // Final treatment of entries to be returned. 
        if (!$params['full']) {
            // Create a list of compact/reduced entries
            $categorynames = $PIVOTX['categories']->getCategoryNames();
            foreach($final_entries_arr as $key => $row) {
                $final_entries_arr[$key]['link'] = makeFileLink($row, '', '');
                // Only return existing categories..
                $final_entries_arr[$key]['category'] = array_intersect($final_entries_arr[$key]['category'], $categorynames);
            }
        } else {
            // Create a list of full entries
            // TODO: Don't read the entries again if we already did it in _getSortArray.
            foreach($final_entries_arr as $key => $row) {
                $final_entries_arr[$key] = $this->read_entry($row['code']);
                $final_entries_arr[$key]['link'] = makeFileLink($row, '', '');
            }
        }

        return $final_entries_arr;
    }

  
    /**
     * Tries to guess an entry by it's (incomplete) URI and date (if 
     * necessary). The entry is returned as an associative array.
     *
     * @param string $uri
     * @param string $date
     * @return array
     */
    function guess_entry($uri, $date) {
        foreach ($this->uri_index as $code => $code_uri) {
            // Check if the given URI is incomplete or if
            // there are some trailing characters.
            if ((strpos($code_uri,$uri) === 0) || (strpos($uri,$code_uri) === 0)){
                return $this->read_entry($code);
            }
        }
        // TODO: Handle multiple matches. Use $date (if given) to select between them.
        return false;
    }

    /**
     * Get an entry by it's (complete) URI
     * The entry is returned as an associative array.
     *
     * @param string $uri
     * @return array
     */
    function get_entry_by_uri($uri) {
        foreach ($this->uri_index as $code => $code_uri) {
            // only match the full uri
            if ($code_uri == $uri){
                return $this->read_entry($code);
            }
        }
        // TODO: Handle multiple matches.
        return false;
    }


    /**
     * Read the latest comments
     *
     * @param array $params
     * @return array
     */
    function read_latestcomments($params) {
        global $PIVOTX;
       
        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

        $comments = array();
        
        // Get the 'latest comments'
        if (isset($params['moderated']) && $params['moderated']!=0) {
            if (file_exists($PIVOTX['paths']['db_path']."ser_lastcomm.php")) {
                $comments = array_reverse(loadSerialize($PIVOTX['paths']['db_path']."ser_lastcomm.php", true, true));
            }
        }        

        foreach($comments as $key=>$comment) {
            $comments[$key]['allowedit'] = ($currentuser['userlevel'] >= PIVOTX_UL_ADVANCED) ? "1" : "0";
        }

        return $comments;
        
    }

    /**
     * Read the last trackbacks
     *
     * @param array $params
     * @return array
     */
    function read_lasttrackbacks($params) {
        global $PIVOTX;
        
        $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );

        $trackbacks = array();
        
        // Get the 'latest trackbacks'
        if (file_exists($PIVOTX['paths']['db_path']."ser_lasttrack.php")) {
            $trackbacks = array_reverse(loadSerialize($PIVOTX['paths']['db_path']."ser_lasttrack.php", true, true));
        }

        foreach($trackbacks as $key=>$trackback) {
            $trackbacks[$key]['allowedit'] = ($currentuser['userlevel'] >= PIVOTX_UL_ADVANCED) ? "1" : "0";
        }

        
        return $trackbacks;
        
    }


    /**
     * Sets the current entry to the contents of $entry - flat file
     * implementation.
     *
     * Returns the inserted entry as it got stored in the database with
     * correct code/id.
     *
     * @param array $entry The entry to be inserted
     * @return array
     */
    function set_entry( $entry ) {

        $this->entry = $entry;

        if ( $this->entry['code'] == '>' ) {
            if (is_array ( $this->date_index )) {
                ksort( $this->date_index );
                $max = end( $this->date_index );
                $max = key( $this->date_index );
                $max = $max + 1;
                $this->entry['code'] = $max;
            } else {
                $this->entry['code'] = 1;
            }
        }
        // UID also needs to be set to be consistent with the 
        // data/result from the SQL db.
        $this->entry['uid'] = $this->entry['code'];

        $this->entry['link'] = makeFileLink($this->entry, '', '');

        $this->update_index();

        return $this->entry;
    }


    /**
     * Saves the current entry - flat file implementation.
     *
     * Returns true if successfully saved. Current implementation
     * seems to return true no matter what...
     *
     * @param boolean $update_index Whether to update the date index.
     * @return boolean
     */
    function save_entry($update_index=TRUE) {

        $filename=$this->set_filename();

        // Unsetting unneeded and empty keys to save storage on file.
        unset($this->entry['commnames']);
        unset($this->entry['commcount']);
        unset($this->entry['commcount_str']);
        unset($this->entry['tracknames']);
        unset($this->entry['trackcount']);
        unset($this->entry['trackcount_str']);
        unset($this->entry['filename']);
        unset($this->entry['oldstatus']);
        if (empty($this->entry['extrafields'])) {
            $this->entry['extrafields'] = array();
        }
        foreach ($this->entry['extrafields'] as $key => $value) {
            if ($value == '') {
                unset($this->entry['extrafields'][$key]);
            }
        }
        if (empty($this->entry['comments'])) {
            $this->entry['comments'] = array();
        }
        foreach ($this->entry['comments'] as $key => $value) {
            unset($this->entry['comments'][$key]['entry_uid']);
            unset($this->entry['comments'][$key]['uid']);
        }
        if (empty($this->entry['trackbacks'])) {
            $this->entry['trackbacks'] = array();
        }
        foreach ($this->entry['trackbacks'] as $key => $value) {
            unset($this->entry['trackbacks'][$key]['entry_uid']);
            unset($this->entry['trackbacks'][$key]['uid']);
        }

        // Get the old entry in case it's needed.
        $newentry = $this->entry;
        $oldentry = $this->read_entry($newentry['code']);
        $this->entry = $newentry;

        // Edit date is 'now'..
        $this->entry['edit_date'] = date("Y-m-d-H-i", getCurrentDate());

        makeDir(dirname($filename));

        saveSerialize($filename, $this->entry);

        debug("Saved entry '". $this->entry['title'] ."' (". $this->entry['code'] .")");

        $this->update_index();
        $this->write_entry_index();

        if ($update_index) {
            $this->write_date_index();
        }
            
        // Update the tags for this entry if it's published and remove the old tags if not
        if ($this->entry['status'] == 'publish') {
            writeTags($this->entry['keywords'], $oldentry['keywords'], $this->entry['code']);
        } else {
            deleteTags($oldentry['keywords'], $this->entry['code']);
        }            
            
        updateSearchIndex($this->entry);

        return TRUE;

    }

    
    /**
     * Deletes the current entry (and it's tags and comments).
     *
     * @return void
     */
    function delete_entry() {

        // Delete all tags, comments and trackbacks before deleting the actual entry
        deleteTags($this->entry['keywords'], $this->entry['code']);
        foreach ($this->entry['comments'] as $key => $value) {
            $this->delete_comment($key, false);
        }
        foreach ($this->entry['trackbacks'] as $key => $value) {
            $this->delete_trackback($key, false);
        }

        unlink($this->set_filename());

        unset ($this->date_index[$this->entry['code']]);
        unset ($this->cat_index[$this->entry['code']]);
        unset ($this->entry_index[$this->entry['code']]);
        unset ($this->uri_index[$this->entry['code']]);

        $this->write_entry_index(TRUE);
        $this->write_date_index();

    }


    /**
     * Delete one or more entries
     *
     * @param array $ids
     * @return boolean
     */
    function delete_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);

        foreach ($ids as $id) {

            $this->read_entry($id);
            $this->delete_entry();

        }

        return true;

    }


    /**
     * Set one or more entries to 'publish'
     *
     * @param array $ids
     * @return boolean
     */
    function publish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);

        foreach ($ids as $id) {

            $this->read_entry($id);

            if ($this->entry['status'] != "publish") {
                $this->entry['status'] = "publish";
                $this->save_entry(true);
            }

        }

        return true;

    }


    /**
     * Set one or more entries to 'hold'
     *
     * @param array $ids
     * @return boolean
     */
    function depublish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);

        foreach ($ids as $id) {

            $this->read_entry($id);

            if ($this->entry['status'] != "hold") {
                $this->entry['status'] = "hold";
                $this->save_entry(true);
            }

        }

        return true;

    }


    /**
     * Checks if any entries set to 'timed publish' should be published.
     *
     * @return void
     */
    function checkTimedPublish() {
        global $PIVOTX;
        $date = date("Y-m-d-H-i", getCurrentDate());

        $entries = $this->read_entries(array('full'=>true, 'status'=>'timed'));

        foreach ($entries as $entry) {
            if ($entry['publish_date'] <= $date) {
                $entry['date'] = $entry['publish_date'];
                $entry['status'] = "publish";
                $this->set_entry($entry);
                $this->save_entry(TRUE);
                if (!$PIVOTX['config']->get('disable_new_entry_notifications')) {
                    sendMailNotification('entry',$this->entry);
                }
                writeTags($this->entry['keywords'], '', $this->entry['code']);
                updateSearchIndex($this->entry);
            }
        }
    }


    /**
     * Deletes a comment from the current entry. Also deletes it from the
     * moderation queue and from latest comments.
     *
     * @param int $uid
     * @param boolean $save whether the entry should be saved.
     * @return void
     */
    function delete_comment($uid, $save=true) {
        global $PIVOTX;

        if (isset($this->entry['comments'][$uid])) {
            $comm = $this->entry['comments'][$uid];
        } else {
            // This should only happen when editing a comment from the last 
            // comments screen (or similar functions) which uses fake UIDs.
            require_once(dirname(__FILE__).'/module_comments.php');
            foreach ($this->entry['comments'] as $key => $value) {
                if ($uid == makeCommentUID($value)) {
                    $comm = $value;
                    // Setting the uid to the (real) array key
                    $uid = $key;
                    break;
                }
            }
        }

        $entry_uid = $this->entry['code'];

        // Delete comment from list of latest comments.
        $lastcomm_file = $PIVOTX['paths']['db_path'].'ser_lastcomm.php';
        if (file_exists($lastcomm_file)) {
            $lastcomm = loadSerialize($lastcomm_file, true, true);
            foreach ($lastcomm as $key => $loopcomm) {
                if (($loopcomm['entry_uid']==$entry_uid) && ($loopcomm['name']==$comm['name']) && ($loopcomm['date']==$comm['date'])) {
                    unset($lastcomm[$key]);
                    break;
                }
            }
            saveSerialize($lastcomm_file, $lastcomm );
        }

        // Delete comment from moderation queue.
        $modqueue_file = $PIVOTX['paths']['db_path'].'ser_modqueue.php';
        if (file_exists($modqueue_file)) {
            $modcomm = loadSerialize($modqueue_file, true, true);
            foreach ($modcomm as $key => $loopcomm) {
                if (($loopcomm['entry_uid']==$entry_uid) && ($loopcomm['name']==$comm['name']) && ($loopcomm['date']==$comm['date'])) {
                    unset($modcomm[$key]);
                    break;
                }
            }
            saveSerialize($modqueue_file, $modcomm );
        }

        // Actually delete the comment from entry    
        unset($this->entry['comments'][ $uid ]);

        if ($save) {
            $this->set_entry($this->entry);
            $this->save_entry();
        }

    }


    /**
     * Returns a comment from the current entry.
     *
     * @param int $uid
     * @return array
     */
    function get_comment($uid) {
        global $PIVOTX;

        if (isset($this->entry['comments'][$uid])) {
            $comm = $this->entry['comments'][$uid];
        } else {
            // This should only happen when editing a comment from the last 
            // comments screen (or similar functions) which uses fake UIDs.
            require_once(dirname(__FILE__).'/module_comments.php');
            foreach ($this->entry['comments'] as $key => $value) {
                if ($uid == makeCommentUID($value)) {
                    $comm = $value;
                    break;
                }
            }
        }

        return $comm;
    }


     /**
     * Deletes a trackback from the current entry. Also deletes it from the last trackbacks.
     *
     * @param int $uid
     * @param boolean $save whether the entry should be saved.
     * @return void
     */
    function delete_trackback($uid, $save=true) {
        require_once(dirname(__FILE__).'/module_trackbacks.php');
        global $PIVOTX;

        if (isset($this->entry['trackbacks'][$uid])) {
            $track = $this->entry['trackbacks'][$uid];
        } else {
            // This should only happen when editing a trackback from the last 
            // trackbacks screen (or similar functions) which uses fake UIDs.
            foreach ($this->entry['trackbacks'] as $key => $value) {
                if ($uid == makeTrackbackUID($value)) {
                    $track = $value;
                    // Setting the uid to the (real) array key
                    $uid = $key;
                    break;
                }
            }
        }

        $entry_uid = $this->entry['code'];

        // Delete trackback from list of last trackbacks.
        $lasttrack_file = $PIVOTX['paths']['db_path'].'ser_lasttrack.php';
        if (file_exists($lasttrack_file)) {
            $lasttrack = loadSerialize($lasttrack_file, true, true);
            foreach ($lasttrack as $key => $looptrack) {
                if (($looptrack['entry_uid']==$entry_uid) && ($looptrack['name']==$track['name']) && ($looptrack['date']==$track['date'])) {
                    unset($lasttrack[$key]);
                    break;
                }
            }
            saveSerialize($lasttrack_file, $lasttrack );
        }

        // Actually delete the trackback from entry    
        unset($this->entry['trackbacks'][ $uid ]);

        if ($save) {
            $this->set_entry($this->entry);
            $this->save_entry();
        }

    }


    /**
     * Returns a trackback from the current entry.
     *
     * @param int $uid
     * @return array
     */
    function get_trackback($uid) {
        global $PIVOTX;

        if (isset($this->entry['trackbacks'][$uid])) {
            $track = $this->entry['trackbacks'][$uid];
        } else {
            // This should only happen when editing a trackback from the last 
            // trackbacks screen (or similar functions) which uses fake UIDs.
            foreach ($this->entry['trackbacks'] as $key => $value) {
                if ($uid == makeTrackbackUID($value)) {
                    $track = $value;
                    break;
                }
            }
        }

        return $track;
    }


    // -----------------
    // private functions
    // ------------------



    // Convert a string, so that it only contains alphanumeric and a few others.
    function safestring($name) {
        return preg_replace("/[^-a-zA-Z0-9_.]/", "", $name);
    }



    // Read the date index.
    function read_date_index() {
        global $PIVOTX;

        if (count($this->date_index)<2) {

            // load and sort the date_index
            $this->date_index = loadSerialize($PIVOTX['paths']['db_path']."ser-dates.php", TRUE, TRUE);

            $this->cat_index = loadSerialize($PIVOTX['paths']['db_path']."ser-cats.php", TRUE, TRUE);
            $this->uri_index = loadSerialize($PIVOTX['paths']['db_path']."ser-uris.php", TRUE, TRUE);

            //debug("Read date index (". count($this->date_index) .",". count($this->cat_index) .")");

            $this->updated=FALSE;
        }


    }



    // Check if the current index file is the right one. If not
    // load it.
    function check_current_index() {

        $entry_index_file = $this->make_entry_index_filename();
        if ($entry_index_file != $this->entry_index_filename) {
            // ergo. the current dir's index is not in memory..
            $this->write_entry_index();
            $this->read_entry_index($entry_index_file);
        }
    }


    // Read an entry index file.
    function read_entry_index($filename) {
        $this->entry_index_filename = $filename;
        $this->entry_index = Array();

        if ( ($this->update_mode) && (file_exists($filename)) ) {
            $this->entry_index = loadSerialize($filename, TRUE, TRUE);
        }

        $this->updated=FALSE;
    }

    // Write an entry index file.
    function write_entry_index($force=FALSE) {

        $this->make_entry_index_filename();

        if ($this->global_reindex) {
            //debug("sort index");
            ksort($this->entry_index);
        }


        if ( ($this->entry_index_filename!="") && (($this->updated)||($force)) && ($this->allow_write==TRUE) ) {

            saveSerialize($this->entry_index_filename, $this->entry_index);
            debug("Save entry index (". count($this->entry_index) .",". basename($this->entry_index_filename) .")");
            $this->updated = FALSE;


        }

    }

    function write_date_index() {
        global $PIVOTX;

        asort($this->date_index);

        debug("Save date index (". count($this->date_index) .",". count($this->cat_index) .")");

        saveSerialize($PIVOTX['paths']['db_path']."ser-dates.php", $this->date_index);
        saveSerialize($PIVOTX['paths']['db_path']."ser-cats.php", $this->cat_index);
        saveSerialize($PIVOTX['paths']['db_path']."ser-uris.php", $this->uri_index);

    }


    // Figure out the filename of the current entry-index file to write to
    // based on the current $this->entry['code']
    function make_entry_index_filename() {
        global $PIVOTX;

        $entries_per_dir = $PIVOTX['config']->get('entries_per_dir');

        $code=$this->entry['code'];
        $dircount=floor($code / $entries_per_dir);
        //debug("code en dirc: $code - $dircount");
        $dir=sprintf("%s-%05d/", $this->logname, ( $entries_per_dir * $dircount) );
        $filename=sprintf("index-%s-%05d.php", $this->logname, ( $entries_per_dir * $dircount) );

        return $PIVOTX['paths']['db_path'].$dir.$filename;

    }


    function entry_from_index() {
        $this->entry = $this->entry_index[$this->entry['code']];

        $this->entry['uid'] = $this->entry['code'];
        $this->entry['id'] = $this->logname."-".$this->entry['code'];
        $this->entry['filename']= $this->set_filename();



        if ($this->entry['title']=='') { 
            $this->entry['title'] = __('No title..'); 
        }
        $this->entry['title']=stripslashes($this->entry['title']);
        if (strlen($this->entry['title'])>50) {
            $this->entry['title_short']=substr($this->entry['title'],0,50).'...';
        } else {
            $this->entry['title_short']=$this->entry['title'];
        }
        $this->entry['size']= (int) $this->entry['size'];
        $this->entry['commcount']= (int) $this->entry['commcount'];
        $this->entry['trackcount']= (int) $this->entry['trackcount'];

        if ($this->entry['size']>1024) {
            $this->entry['print_size']=sprintf("%01.1f Kb",  $this->entry['size']/1024.0);
        } else {
            $this->entry['print_size']=sprintf("%d B", $this->entry['size']);
        }

        $this->entry['title_safe']=str_replace("http://", "", $this->entry['title']);   
        $this->entry['title_safe']=str_replace("'", "", $this->entry['title_safe']);
        $this->entry['title_safe']=str_replace('"', "", $this->entry['title_safe']);
        $this->entry['title_safe']=preg_replace("#[^a-zA-Z0-9 :/_.,]#", "", $this->entry['title_safe']);
    }


    // Based on the $this->entry['code'], this function sets and
    // returns the filename of this entry
    function set_filename($code="") {
        global $PIVOTX;

        $entries_per_dir = $PIVOTX['config']->get('entries_per_dir');

        if ($code=="") {
            $code=$this->entry['code'];
        }

        if (strpos($code,"-")>0) {
            //  debug("ack");
            list($dummy,$code)=explode("-",$code);
        }

        if ($code==$this->logname) {
            $this->entry['filename']="ROOT";
            $this->entry['code']="ROOT";
            $this->entry['id']="ROOT";

        } else {
            $dircount=floor($code / $entries_per_dir);
            //echo " [ $code - $dircount ] ";
            $dir=sprintf("%s-%05d/", $this->logname, ( $entries_per_dir * $dircount) );
            $filename=sprintf("%05d.php", $code );
            // set it and return it as well..
            $this->entry['filename'] = $PIVOTX['paths']['db_path'].$dir.$filename;
        }
        return $this->entry['filename'];
    }

    /**
     * Gets a code from a given $uri and $date.
     *
     * @param string $code
     * @param string $date
     * @return integer
     */
    function get_code_from_uri($uri, $date) {

        foreach ($this->uri_index as $code => $indexuri) {

            if ($indexuri == $uri) {

                if (empty($date)) {
                    return $code;
                } else {

                    // make sure we're comparing two dates with the same length.
                    $indexdate = substr($this->date_index[$code], 0, strlen($date));

                    if ($indexdate == $date) {
                        return $code;
                    }

                }

            }

        }

        // if we get to here, we couldn't find the URI. :-(
            return 0;

    }


    // given a dirname, this will index the entries in that directory
    function index_entries($dirname) {
        global $PIVOTX;

        if (is_dir($PIVOTX['paths']['db_path'].$dirname)) {
            $d= dir($PIVOTX['paths']['db_path'].$dirname);

            while ($filename=$d->read()) {
                if (strlen($filename)==9) {
                    $filelist[] = $filename;
                }
            }


            foreach($filelist as $file) {

                $result = $this->read_entry_filename($PIVOTX['paths']['db_path'].$dirname."/".$file, TRUE);

                if($result) {
                    debug ("($file is ok: ".$this->entry['title']." - ".$this->entry['date'].")");
                } else {
                    debug ("(<b>$file is NOT ok: </b>".$this->entry['title']." - ".$this->entry['date'].")");
                }

                // Write the tags for this entry if it's published
                if ($this->entry['status'] == 'publish') {
                    $tags = getTags(false, $this->entry['introduction'].$this->entry['body'],
                    $this->entry['keywords']);
                    if (is_array($tags) && (count($tags)>0)) {
                        writeTags($tags, '', $this->entry['code']);
                    }
                }
            }

            $d->close();
            $this->write_entry_index(TRUE);
        }
    }




    // read an entry from disk. If no filename is given, it will
    // use what's in $this->entry['filename'].
    function read_entry_filename($filename, $updateindex=TRUE, $force=FALSE) {
        if ($entry=loadSerialize($filename, TRUE, $force)) {
            $this->entry = $entry;
            $this->update_index(FALSE);
            return TRUE;
        } else {
            return FALSE;
        }


    }


    // after indexing or updating an entry, this is used to update the
    // entry-index file.
    function update_index($update=TRUE) {
        global $PIVOTX;

        $this->check_current_index();

        if (strlen($this->entry['title'])>1) {
            $title=$this->entry['title'];
            $title=strip_tags($title);
        } else {
            $title=substr($this->entry['introduction'],0,300);
            $title=strip_tags($title);
            $title=str_replace("\n","",$title);
            $title=str_replace("\r","",$title);
            $title=substr($title,0,60);
        }

        // Make sure we have an URI. Old (converted from 1.x) entries don't have them, so we make them.
        if (empty($this->entry['uri'])) {
            $this->entry['uri'] = makeURI($this->entry['title']);
        }

        $size=strlen($this->entry['introduction'])+strlen($this->entry['body']);

        unset($commnames);
        if (isset($this->entry['comments'])) {

            // Initialise the IP blocklist.
            $blocklist = new IPBlock();

            foreach ($this->entry['comments'] as $comment) {
                if (!$blocklist->isBlocked($comment['ip'])) {
                    if ($comment['moderate']!=1) {
                        $commnames[]=stripslashes($comment['name']);
                    } else {
                        // if moderation is on, we add the name as '-'..
                        $commnames[]='-';
                    }
                }
            }

            if (isset($commnames) && (count($commnames)>0)) {
                $this->entry['commnames']=implode(", ",array_unique ($commnames));
                $commcount=count($commnames);
            } else {
                $this->entry['commnames'] = "";
                $commcount = 0;
            }
        } else {
            unset ($this->entry['comments']);
            $commcount=0;
            $this->entry['commnames']="";
        }

        $this->entry['commcount']=$commcount;

        if ($commcount==0) {
            $commcount_str=__('No comments');
        } else if ($commcount==1) {
            $commcount_str=__('%num% comment');
        } else {
            $commcount_str=__('%num% comments');
        }
        $this->entry['commcount_str']=str_replace("%num%", $PIVOTX['locale']->getNumber($commcount), $commcount_str);
        $this->entry['commcount_str']=str_replace("%n%", $commcount, $commcount_str);

        unset($tracknames);
        if (isset($this->entry['trackbacks'])) {

            foreach ($this->entry['trackbacks'] as $trackback) {
                $tracknames[]=stripslashes($trackback['name']);
            }

            if (isset($tracknames) && (count($tracknames)>0)) {
                $this->entry['tracknames']=implode(", ",array_unique ($tracknames));
                $trackcount=count($tracknames);
            } else {
                $this->entry['tracknames'] = "";
                $trackcount = 0;
            }
        } else {
            unset ($this->entry['trackbacks']);
            $trackcount=0;
            $this->entry['tracknames']="";
        }

        $this->entry['trackcount']=$trackcount;

        if ($trackcount==0) {
            $trackcount_str=__('No trackbacks');
        } else if ($trackcount==1) {
            $trackcount_str=__('%num% trackback');
        } else {
            $trackcount_str=__('%num% trackbacks');
        }
        $this->entry['trackcount_str']=str_replace("%num%", $PIVOTX['locale']->getNumber($trackcount), $trackcount_str);
        $this->entry['trackcount_str']=str_replace("%n%", $trackcount, $trackcount_str);

        if (!isset($this->entry['status'])) {
            $this->entry['status'] = 'publish';
        }

        $this->entry['excerpt'] = makeExcerpt($this->entry['introduction']);

        // Remove non-existing categories from entry before indexing
        if (count($this->all_cats) > 0) {
            $category = array_values(array_intersect($this->all_cats, $this->entry['category']));
        } else {
            $category = $this->entry['category'];
        }

        if (is_array($this->entry['extrafields'])) {
            $extrafields = array_keys($this->entry['extrafields']);
        } else {
            $extrafields = array();
        }

        $index_line = array(
            'code' => $this->entry['code'],
            'date' => addslashes($this->entry['date']),
            'user' => $this->entry['user'],
            'title' => addslashes($title),
            'uri' => $this->entry['uri'],
            'size' => $size,
            'commcount' => $this->entry['commcount'],
            'cnames' => $this->entry['commnames'],
            'trackcount' => $this->entry['trackcount'],
            'tnames' => $this->entry['tracknames'],
            'category' => $category,
            'extrafields' => $extrafields,
            'status' => $this->entry['status'],
            'excerpt' => $this->entry['excerpt']
        );

        if  ($this->entry['code'] != "") {
            $this->entry_index[ $this->entry['code'] ]=$index_line;
            $this->date_index[ $this->entry['code'] ]= $this->entry['date'];
            $this->cat_index[ $this->entry['code'] ]= $category;
            $this->uri_index[ $this->entry['code'] ]= $this->entry['uri'];

        }

        if ($update) {
            $this->updated=TRUE;
        }

    }

    // checks if two arrays have overlapping elements. mostly used to check
    // if a set of categories (in a subweblog) matches a set of categories in an entry.
    function intersect($arr1, $arr2) {

        // if $arr1 is not an array
        if (is_string($arr1)) {
            $arr1 = array($arr1);
        }

        // if $arr2 is not an array
        if (is_string($arr2)) {
            return in_array($arr2, $arr1);
        }

        // if both are arrays
        return (count(@array_intersect($arr1, $arr2))>0) ? TRUE : FALSE;

    }
    
    /**
     * Clears the index for searching or tags (by deleteing all files
     * whose filename don't starting with 'filtered_words' or 'index.html').
     *
     * @return void
     * @param string $type 
     */
    function clearIndex($type) {
        global $PIVOTX;

        if ($type == 'search') {
            $dir = $PIVOTX['paths']['db_path'].'search/';
        } elseif ($type == 'tags') {
            $dir = $PIVOTX['paths']['db_path'].'tagdata/';
        } else {
            debug("Unknown datatype");
            return;
        }

        $d = dir($dir);
        while ( false !== ( $entry = $d->read())) {
            if (( '.' != $entry ) && ( '..' != $entry ) && ( 'index.html' != $entry ) 
                    && ('filtered_words' != substr( $entry,0,14 ))) {
                unlink( $dir . $entry );
            }
        }
        $d->close();
    }

    /**
     * Gets the sort array for a given orderby parameter.
     *
     * It also updates $entries_arr so it contains the full entries.
     * if the orderby parameter is referencing an extrafield.
     */
    function _getSortArray(&$entries_arr, $orderby) {
        $sort_arr = array();
        if (substr($orderby,0,12) == "extrafields_") {
            $orderby = substr($orderby,12);
            foreach ($entries_arr as $row_key => $row) {
                $entries_arr[$row_key] = $this->read_entry($row['code']);
                $sort_arr[] = $entries_arr[$row_key]['extrafields'][$orderby];
            }
        } else {
            foreach ($entries_arr as $row_key => $row) {
                $sort_arr[] = $row[$orderby];
            }
        }
        return $sort_arr;
    }
        
    // end of class EntriesFlat
}

?>
