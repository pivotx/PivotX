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



/**
 * class db
 * The API for accessing the database.
 *
 * @package pivotx
 */
class db {

    var $db_type;

    /**
     * Initialises the db.
     *
     * @param boolean $loadindex Whether the index should be loaded.
     */
    function db($loadindex=TRUE) {
        global $PIVOTX, $pivotx_path;

        if ($PIVOTX['config']->get('db_model')=="flat") {
            $this->db_type = "flat";
            include_once( realpath($pivotx_path). '/modules/entries_flat.php');
            $this->db_lowlevel = new EntriesFlat($loadindex);
        } else if ( ($PIVOTX['config']->get('db_model')=="mysql") ||
                ($PIVOTX['config']->get('db_model')=="sqlite") ||
                ($PIVOTX['config']->get('db_model')=="postgresql") ) {
            $this->db_type = "sql";
            include_once( realpath($pivotx_path). '/modules/entries_sql.php');
            $this->db_lowlevel = new EntriesSql($loadindex);
        } else {
            // TODO: In case of a fatal error, we should give the user the chance to reset the
            // Config to the default state, and try again.
            echo("Unknown DB Model! It will be reset to 'flat files'. Please refresh this page, go to configuration and set it as you prefer.");
            $PIVOTX['config']->set('db_model', 'flat');
            die();
        }

    }

    /**
     * Gets a list of entries by date.  This function is really deprecated and
     * acts like a wrapper around read_entries.
     *
     * @param int $amount
     * @param int $offset
     * @param mixed $filteronuser
     * @param mixed $filteroncat
     * @param boolean $order Defines whether the results are in chronological
     *    order (false means reverse order).
     * @param string $field The field to order by.
     * @param string $status Return only entries with this status.
     * @return array
     */
    function getlist($amount, $offset=0, $filteronuser="", $filteroncat="", $order=TRUE, $field="", $status="") {

        if ($order == TRUE) {
            $order = 'asc';
        } else {
            $order = 'desc';
        }

        return $this->read_entries(array(
            'full'=>false, 'show'=>$amount, 'offset'=>$offset, 
            'cats'=>$filteroncat, 'user'=>$filteronuser,
            'status'=>$status, 'order'=>$order, 'orderby'=>$field)
        );

    }

    /**
     * Gets an array of archives.
     *
     * @param boolean $force tells if the cache (if any) should be updated.
     * @param string $unit the unit of the archives.
     * @return array
     */
    function getArchiveArray($force=FALSE, $unit) {

        return $this->db_lowlevel->getArchiveArray($force, $unit);

    }

    /**
     * Gets the number of entries. 
     *
     * The $params array can take the same keys as in the read_entries 
     * function, but only the following keys give meaning when counting:
     * 
     * - 'offset': The offset from the beginning of the filtered and sorted/ordered array. 
     * - 'cats': Filter entries by category/ies.
     * - 'extrafields': Filter entries by extrafields.
     * - 'user': Filter entries by user(s). 
     * - 'status': Filter entries by status. 
     * - 'date': A date range - day, month or year. 
     * - 'start'/'end': A start/end date. 
     *
     * 'cats', 'extrafields' and 'user' can either be (comma separated) strings or arrays.
     *
     * @param array $params
     * @return int
     */
    function get_entries_count($params=false) {

        return $this->db_lowlevel->get_entries_count($params);

    }


    /**
     * Gets the code of the next entry.
     *
     * @param int $num
     * @return int
     */
    function get_next_code($num=1, $category="") {
        return $this->db_lowlevel->get_next_code($num, $category);
    }


    /**
     * Gets the code of the previous entry.
     *
     * @param int $num
     * @return int
     */
    function get_previous_code($num=1, $category="") {
        return $this->db_lowlevel->get_previous_code($num, $category);
    }



    /**
     * Rebuilds the index, if necessary.
     */
    function generate_index() {

        if ($this->db_lowlevel->need_index()) {

            $this->db_lowlevel->generate_index();

        } else {

            echo "this database does not need an index.<br />";

        }

    }

    /**
     * Tells if the entry exists.
     *
     * @param int $code The code/id of the entry.
     * @return boolean
     */
    function entry_exists($code) {
        return $this->db_lowlevel->entry_exists($code);
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
    function read_entry($code, $date="") {
        global $loadcount, $PIVOTX;

        $entry = $this->db_lowlevel->read_entry($code, $date);

        if (!empty($PIVOTX['extensions'])) {
            $PIVOTX['extensions']->executeHook('entry_afterload', $entry);
        }

        $this->entry = $entry;

        return $this->entry;

    }


    /**
     * Read a bunch of entries.
     *
     * The $params array can have the following keys:
     *
     * - 'full': Determines if the returned entries should be full (contain all fields), the default, or be reduced. (true/false)
     * - 'show': Amount of entries to read. 
     * - 'offset': The offset from the beginning of the filtered and sorted/ordered array.
     * - 'cats': Filter entries by category/ies. 
     * - 'extrafields': Filter entries by extrafields. 
     * - 'user': Filter entries by user(s). 
     * - 'status': Filter entries by status. 
     * - 'order': Select random, asc(ending) or des(cending). 
     * - 'orderby': Default is date, but any entry field (e.g. code/uid) can be used. 
     * - 'date': A date range - day, month or year. 
     * - 'start'/'end': A start/end date. 
     *
     * 'cats', 'extrafields' and 'user' can either be (comma separated) strings or arrays.
     *
     * @param array $params
     * @return array
     */
    function read_entries($params) {
        global $PIVOTX;

        // Sanctifying the params:
        if (!isset($params['full'])) { $params['full'] = true; } 

        // Negative amounts are not supported.
        if (!empty($params['show']) && ($params['show'] < 0)) {
            $params['show'] = -$params['show'];
            debug("Negative amount of entries ('show') is not supported - using abs value.");
        }
        // Do not limit number of entries if an interval or a date range is given
        if ((!empty($params['start']) && !empty($params['end'])) || !empty($params['date'])) {
            if (!empty($params['show'])) {
                unset($params['show']);
                debug("Both an interval or a date range and 'show' given - ignoring 'show'");
            }
        } else {
            $params['show'] = intval(getDefault($params['show'], 20)); // 20 seems like a sane default..
        }
        // Fix reversed 'start' and 'end'.
        if (!empty($params['start']) && !empty($params['end'])) {
            if ($params['start'] > $params['end']) {
                $temp = $params['start'];
                $params['start'] = $params['end'];
                $params['end'] = $temp;
            }
        }
        // Do not use a date range or start if an offset is given
        if (!empty($params['offset'])) {
            $params['offset'] = abs(intval($params['offset']));
            if (!empty($params['start'])) {
                $params['start'] = '';
                debug("Both 'offset' and a start value given - ignoring 'start'");
            }
            if (!empty($params['date'])) {
                $params['date'] = '';
                debug("Both 'offset' and a date (range) given - ignoring 'date'");
            }
        }
        // Do not use offset, a date range or start/end if random order is chosen.
        if ($params['order'] == "random") {
            if (!empty($params['offset'])) {
                $params['offset'] = '';
                debug("Both 'random' and a start value given - ignoring 'start'");
            }
            if (!empty($params['date'])) {
                $params['date'] = '';
                debug("Both 'random' and a date (range) given - ignoring 'date'");
            }
            if (!empty($params['start'])) {
                $params['start'] = '';
                debug("Both 'random' and a start value given - ignoring 'start'");
            }
            if (!empty($params['end'])) {
                $params['end'] = '';
                debug("Both 'random' and a end value given - ignoring 'end'");
            }
        }

        return $this->db_lowlevel->read_entries($params);

    }


    /**
     * Tries to guess an entry by it's (incomplete) URI and date (if 
     * available). The entry is returned as an associative array.
     *
     * @param string $uri
     * @param string $date
     * @return array
     */
    function guess_entry($uri, $date = '') {
        global $loadcount;

        $this->entry = $this->db_lowlevel->guess_entry($uri, $date);

        return $this->entry;

    }


    /**
     * Get an entry by its specific URI.
     *
     * @param string $uri
     * @param string $date
     * @return array
     */
    function get_entry_by_uri($uri) {
        
        return $this->db_lowlevel->get_entry_by_uri($uri);
        
    }
    


    /**
     * Read the latest comments
     *
     * @param array $params
     * @return array
     */
    function read_latestcomments($params) {
        
        return $this->db_lowlevel->read_latestcomments($params);
        
    }


    /**
     * Read the last trackbacks
     *
     * @param array $params
     * @return array
     */
    function read_lasttrackbacks($params) {
        
        return $this->db_lowlevel->read_lasttrackbacks($params);
        
    }


    /**
     * Sets the current entry to the contents of $entry.
     *
     * Returns the inserted entry as it got stored in the database with
     * correct code/id and Word HTML stripped off.
     *
     * @param array $entry The entry to be inserted
     * @return array
     */
    function set_entry($entry) {

        $iswordhtml = false;
        if (isWordHtml($entry['introduction'])) {
            $entry['introduction'] = stripWordHtml($entry['introduction']);
            $iswordhtml = true;
        }
        if (isWordHtml($entry['body'])) {
            $entry['body'] = stripWordHtml($entry['body']);
            $iswordhtml = true;
        }
        if ($iswordhtml) {
            debug(__('Text pasted directly from Microsoft Word. Some of the markup might be lost.'));
        }

        $this->entry = $this->db_lowlevel->set_entry($entry);

        return $this->entry;

    }

    /**
     * Deletes the current entry
     */
    function delete_entry() {
        global $PIVOTX;

        $PIVOTX['events']->add('delete_entry', intval($this->entry['uid']), $this->entry['title']);

        $this->db_lowlevel->delete_entry();

    }

    /**
     * Delete one or more entries.
     *
     * @param array $ids
     */
    function delete_entries($ids) {
        global $PIVOTX;

        $PIVOTX['events']->add('delete_entries', $ids);

        return $this->db_lowlevel->delete_entries($ids);

    }


    /**
     * Deletes a comment from the current entry.
     *
     * @param integer uid
     */
    function delete_comment($uid) {
        global $PIVOTX;

        $PIVOTX['events']->add('delete_comment', $uid);

        $this->db_lowlevel->delete_comment($uid);


    }

    /**
     * Returns a comment from the current entry.
     *
     * @param integer uid
     */
    function get_comment($uid) {
        global $PIVOTX;

        return $this->db_lowlevel->get_comment($uid);

    }

     
    /**
     * Deletes a trackback from the current entry.
     *
     * @param integer uid
     */
    function delete_trackback($uid) {
        global $PIVOTX;

        $PIVOTX['events']->add('delete_trackback', intval($this->entry['uid']), $this->entry['title']);

        $this->db_lowlevel->delete_trackback($uid);

    }

    /**
     * Returns a trackback from the current entry.
     *
     * @param integer uid
     */
    function get_trackback($uid) {
        global $PIVOTX;

        return $this->db_lowlevel->get_trackback($uid);

    }

    /**
     * Saves the current entry.
     *
     * Returns true if successfully saved. Current implementation
     * (in module_db_xml.php) seems to return true no matter what.
     *
     * @param boolean $update_index Whether to update the date index.
     * @return boolean
     */
    function save_entry($update_index=TRUE) {
        global $PIVOTX;

        $PIVOTX['events']->add('save_entry', intval($this->entry['uid']), $this->entry['title']);

        $this->db_lowlevel->save_entry($update_index);

        $this->entry = $this->db_lowlevel->entry;

        return true;



    }

    /**
    * Gets the date for an entry
    *
    * @param int $code
    * @return string
    */
    function get_date($code) {

        return $this->db_lowlevel->get_date($code);

    }

    /**
    * Switches to writing-disallowed mode.
    */
    function disallow_write() {
        $this->db_lowlevel->disallow_write();
    }


    /**
    * Switches to writing-allowed mode.
    */
    function allow_write() {
        $this->db_lowlevel->allow_write();
    }


    /**
     * Set one or more entries to 'publish'
     *
     * @param array $ids
     */
    function publish_entries($ids) {
        global $PIVOTX;

        $PIVOTX['events']->add('publish_entries', $ids);        
        
        return $this->db_lowlevel->publish_entries($ids);
    }


    /**
     * Set one or more entries to 'hold'
     *
     * @param array $ids
     */
    function depublish_entries($ids) {
        global $PIVOTX;

        $PIVOTX['events']->add('depublish_entries', $ids);
        
        return $this->db_lowlevel->depublish_entries($ids);
    }

    /**
     * Checks if any entries set to 'timed publish' should be published.
     *
     */
    function checkTimedPublish() {
        return $this->db_lowlevel->checkTimedPublish();
    }

    /**
     * Clears the index for searching or tags.
     *
     * @return void
     * @param string $type 
     */
    function clearIndex($type) {
        if ($this->db_lowlevel->need_index()) {
            $this->db_lowlevel->clearIndex($type);
        } else {
            echo "this database does not use an index.<br />";
        }
    }

    // end of class
}



?>
