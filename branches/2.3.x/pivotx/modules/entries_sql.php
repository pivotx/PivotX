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

require_once(dirname(__FILE__)."/module_sql.php");

/**
 * Class to work with Entries, using the MySQL storage model.
 */
class EntriesSql {

    // the name of the log
    var $logname;

    // the data for the current entry
    var $entry;

    // a nice and big array with all the dates.
    var $date_index;

    // a somewhat smaller array for the entries that share the same
    // directory as the current entry
    var $update_mode;
    var $updated;
    var $entry_index;
    var $entry_index_filename;

    // pointer to where we are..
    var $pointer;

    // some names and stuff..
    var $weblog;
    var $entriestable;
    var $commentstable;
    var $trackbackstable;
    var $tagstable;
    var $categoriestable;


    // public functions

    function EntriesSql($loadindex=TRUE, $allow_write=TRUE) {
        global $PIVOTX, $dbversion;

        static $initialisationchecks;

        //init vars..

        // Logname will be phased out eventually, since all will be based on categories.
        $this->logname = "standard";

        $this->entry = Array('code' => '', 'id' => '',  'template' => '',  'date' => '',  'user' => '',  'title' => '',  'subtitle' => '',  'introduction' => '',  'body' => '',  'media' => '',  'links' => '',  'url' => '',  'filename' => '',  'category' => '');

        $this->update_mode=TRUE;

        // Set the names for the tables we use.
        $this->entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
        $this->commentstable = safeString($PIVOTX['config']->get('db_prefix')."comments", true);
        $this->trackbackstable = safeString($PIVOTX['config']->get('db_prefix')."trackbacks", true);
        $this->tagstable = safeString($PIVOTX['config']->get('db_prefix')."tags", true);
        $this->categoriestable = safeString($PIVOTX['config']->get('db_prefix')."categories", true);
        $this->pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
        $this->chapterstable = safeString($PIVOTX['config']->get('db_prefix')."chapters", true);
        $this->extrafieldstable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

        // Set up DB connection
        $this->sql = new sql('mysql',
            $PIVOTX['config']->get('db_databasename'),
            $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'),
            $PIVOTX['config']->get('db_password')
        );

        // Verify that the entries database tables exist. If not, we create them.
        // We do this only once, regardles of how many $PIVOTX['db']->lowlevel objects
        // are initialised.
        if (!$initialisationchecks) {

            $this->sql->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
            $tables = $this->sql->fetch_all_rows('no_names');
            $tables = makeValuepairs($tables, '', '0');

            if (!in_array($this->entriestable, $tables)) {
                makeEntriesTable($this->sql);
                // If we make the table, we set the DB to the most recent version..
                $PIVOTX['config']->set('db_version', $dbversion);
            }

            if (!in_array($this->commentstable, $tables)) {
                makeCommentsTable($this->sql);
            }

            if (!in_array($this->trackbackstable, $tables)) {
                makeTrackbacksTable($this->sql);
            }

            if (!in_array($this->tagstable, $tables)) {
                makeTagsTable($this->sql);
            }

            if (!in_array($this->categoriestable, $tables)) {
                makeCategoriesTable($this->sql);
            }

            // We also Verify that the pages database tables exist. If not, we create them.
            // It would be slightly more logical to do this in Pages(), but if we do it
            // here, it saves a query on each and every pageview.
            if (!in_array($this->pagestable, $tables)) {
                makePagesTable($this->sql);
            }

            if (!in_array($this->chapterstable, $tables)) {
                makeChaptersTable($this->sql);
            }

            if (!in_array($this->extrafieldstable, $tables)) {
                makeExtrafieldsTable($this->sql);
            }

            $initialisationchecks = true;            
        }
        

    }



 

    /**
     * Gets an array of archives - mysql implementation.
     *
     * In contrast to the flat file implementation, the file 
     * "db/ser-archives.php" isn't used.
     *
     * @param boolean $force ignored, only used by flat file implementation.
     * @param string $unit the unit of the archives.
     * @return array
     */
    function getArchiveArray($force=FALSE, $unit) {
        global $PIVOTX;

        $Archive_array=array();

        // Get an array with the weblognames
        $weblognames = $PIVOTX['weblogs']->getWeblogNames();

        // .. which we'll iterate through to collect all archives
        foreach($weblognames as $weblogname) {

            // Get the categories published in the current weblog
            $categories = $PIVOTX['weblogs']->getCategories($weblogname);
            $categories = array_map('safe_string', $categories);
            $categories = "'".implode("', '", $categories)."'";

            if ($unit=="month" || $unit=="year") {
                $datelength = 7;
            } else {
                $datelength = 10;
            }
            
            // Select all dates of entries in this weblog..
            $this->sql->query("SELECT DISTINCT(LEFT(date, $datelength)) AS date
                FROM " . $this->entriestable . " AS e
                LEFT JOIN " . $this->categoriestable . " AS c ON (c.target_uid = e.uid)
                WHERE c.category IN ($categories) AND
                    c.contenttype = 'entry' AND
                    e.status='publish'
                ORDER BY date ASC");

            $date_index = $this->sql->fetch_all_rows();

            $date_index = makeValuepairs($date_index, '', 'date');

            // echo nl2br(htmlentities($this->sql->get_last_query()));

            foreach ($date_index as $date) {
                $name = makeArchiveName($date, $weblogname, $unit);
                $Archive_array[$weblogname][$name] = $date;
            }
        }

        // sort the array, to maintain correct order..
        foreach ($Archive_array as $key => $value) {
            krsort($Archive_array[$key]);
        }

        return $Archive_array;

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
     * @param array $params
     * @return int
     */
    function get_entries_count($params=false) {

        if($params) {
            return $this->read_entries_count($params);
        }

        $this->sql->query("SELECT COUNT(*) AS count FROM " . $this->entriestable . " WHERE 1;");

        $res = $this->sql->fetch_row();

        return $res['count'];

    }

    /**
     * Gets the code of the next entry - mysql implementation.
     *
     * @param int $num
     * @return int
     */
    function get_next_code($num, $category="") {

        $offset = max((intval($num)-1),0);

        $qry = array();
        $qry['select'] = "e.uid";
        $qry['from'] = $this->entriestable . " AS e";
        $qry['where'][] = "date>". $this->sql->quote($this->entry['date']);
        $qry['limit'] = "$offset, 1";
        $qry['order'] = "date ASC";

        // If we have a category, we use that to make the query more specific, reducing the number of loops.
        if (!empty($category) && is_array($category)) {
            $qry['leftjoin'][ $this->categoriestable . " AS c" ] = "e.uid = c.target_uid";
            $qry['where'][]= "c.category IN ('" . implode("', '", $category) . "')";
        }
        
        $query = $this->sql->build_select($qry);
        $this->sql->query();

        $res = $this->sql->fetch_row();

        if ($res['uid']>0) {
            return intval($res['uid']);
        } else {
            return false;
        }

    }

    /**
     * Gets the code of the previous entry - mysql implementation.
     *
     * @param int $num
     * @return int
     */
    function get_previous_code($num, $category="") {

        $offset = max((intval($num)-1),0);

        $qry = array();
        $qry['select'] = "e.uid";
        $qry['from'] = $this->entriestable . " AS e";
        $qry['where'][] = "date<". $this->sql->quote($this->entry['date']);
        $qry['limit'] = "$offset, 1";
        $qry['order'] = "date DESC";

        // If we have a category, we use that to make the query more specific, reducing the number of loops.
        if (!empty($category) && is_array($category)) {
            $qry['leftjoin'][ $this->categoriestable . " AS c" ] = "e.uid = c.target_uid";
            $qry['where'][]= "c.category IN ('" . implode("', '", $category) . "')";
        }
        
        $query = $this->sql->build_select($qry);
        $this->sql->query();

        $res = $this->sql->fetch_row();

        if ($res['uid']>0) {
            return intval($res['uid']);
        } else {
            return false;
        }

    }

    /**
     * Checks whether the current DB model needs to keep a separate index.
     * The flat file model does, but Mysql doesn't..
     *
     * @return boolean
     */
    function need_index() {

        // the sql file database needs no index.
        return false;

    }

    /**
     * rebuild the index of the Mysql Database. just here for compatibility.
     */
    function generate_index() {

        // Not needed

    }

    /**
     * Tells if the entry exists - mysql implementation.
     *
     * @param int $code The code/id of the entry.
     * @return boolean
     */
    function entry_exists($uid) {

        // Fetch the entry
        $qry = array();

        $qry['select'] = "uid";
        $qry['from'] = $this->entriestable;
        $qry['where'] = "uid=" . $this->sql->quote($uid);
        $qry['limit'] = 1;

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        return ($this->sql->fetch_row());

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
    function read_entry($code, $date="") {
        global $PIVOTX;

        // We need to fetch an entry, but first we see if it's in the entrycache
        // already, otherwise we get it from the DB.

        if ( $PIVOTX['cache']->get("entries", $code) && $PIVOTX['cache']->get("comments", $code) ) {

            // We've already got it!
            $this->entry = $PIVOTX['cache']->get("entries", $code);

            return $this->entry;

        }
        
        // Let's get it from the DB.

        $qry = array();

        $qry['select'] = "*, uid AS code";
        $qry['from'] = $this->entriestable;
        $qry['limit'] = 1;

        if (is_numeric($code)) {
            $qry['where'][] = "uid=" . $this->sql->quote($code);
        } else {
            $qry['where'][] = "uri=" . $this->sql->quote($code);
        }

        if (!empty($date)) {
           $qry['where'][] = "date like '" . $this->sql->quote($date, true) . "%'";
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();
        $this->entry = $this->sql->fetch_row();

        // Set the link..
        $this->entry['link'] = makeFileLink($this->entry, '', '');

        $this->entry['vialink'] = $this->entry['via_link'];
        $this->entry['viatitle'] = $this->entry['via_title'];

        // Next we need to get the categories for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("categories", $code) ) {
            
            $this->entry['category'] = $PIVOTX['cache']->get("categories", $code);
            
        } else {

            $this->sql->query("SELECT category FROM " . $this->categoriestable . " WHERE contenttype = 'entry' AND target_uid=". intval($this->entry['uid']));

            $category = $this->sql->fetch_all_rows();
            $category = makeValuepairs($category, '', 'category');
            $this->entry['category'] = $category;

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("categories", $code, $this->entry['category']);

        }

        // Next we need to get the comments for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("comments", $code) ) {

            $this->entry['comments'] = $PIVOTX['cache']->get("comments", $code);

        } else {

            $this->sql->query("SELECT * FROM " . $this->commentstable . " WHERE contenttype = 'entry' AND entry_uid=". intval($this->entry['uid']) . " ORDER BY date ASC");

            $temp_comments = $this->sql->fetch_all_rows();

            $this->entry['comments'] = array();

            if(is_array($temp_comments)) {
                foreach($temp_comments as $temp_comment) {

                    $temp_comment['allowedit'] = $PIVOTX['users']->allowEdit('comment', $this->entry['user']);

                    $this->entry['comments'][ $temp_comment['uid'] ] = $temp_comment;
                }
            }

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("comments", $code, $this->entry['comments']);

        }

        // Next we need to get the trackbacks for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("trackbacks", $code) ) {

            $this->entry['trackbacks'] = $PIVOTX['cache']->get("trackbacks", $code);

        } else {

            $this->sql->query("SELECT * FROM " . $this->trackbackstable . " WHERE entry_uid=". intval($this->entry['uid']) . " ORDER BY date ASC");

            $temp_trackbacks = $this->sql->fetch_all_rows();

            $this->entry['trackbacks'] = array();

            if(is_array($temp_trackbacks)) {
                foreach($temp_trackbacks as $temp_trackback) {

                    $temp_trackback['allowedit'] = $PIVOTX['users']->allowEdit('trackback', $this->entry['user']);

                    $this->entry['trackbacks'][ $temp_trackback['uid'] ] = $temp_trackback;
                }
            }

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("trackbacks", $code, $this->entry['trackbacks']);

        }

        // Next we need to get the extrafields for this entry. Again, we check
        // if they are already fetched.
        if ( $PIVOTX['cache']->get("extrafields", $code) ) {

            $this->entry['extrafields'] = $PIVOTX['cache']->get("extrafields", $code);

        } else {

            $this->sql->query("SELECT * FROM " . $this->extrafieldstable . " WHERE contenttype='entry' AND target_uid=". intval($this->entry['uid']) . " ORDER BY uid ASC");

            $temp_fields = $this->sql->fetch_all_rows();

            $this->entry['extrafields'] = array();

            if(is_array($temp_fields)) {
                foreach($temp_fields as $temp_field) {
                    
                    // Check if it's a serialised value..
                    if (is_array(unserialize($temp_field['value']))) {
                        $temp_field['value'] = unserialize($temp_field['value']);
                    }
                    
                    $this->entry['extrafields'][ $temp_field['fieldkey'] ] = $temp_field['value'];
                }
            }

            // Save it to the cache for later use..
            $PIVOTX['cache']->set("extrafields", $code, $this->entry['extrafields']);


        }

        $this->entry['commcount'] = count($this->entry['comments']);

        $PIVOTX['cache']->set("entries", $code, $this->entry);


        return $this->entry;
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

        $qry = array();

        $qry['select'] = "uid";
        $qry['from'] = $this->entriestable;
        $qry['limit'] = 1;

        $qry['where'] = "uri=" . $this->sql->quote($uri);

        $query = $this->sql->build_select($qry);
        $this->sql->query();
        $tempentry = $this->sql->fetch_row();

        // Try again, now with LIKE, and perhaps trailing characters..
        if (empty($tempentry['uid'])) {
            $uri = makeURI($uri);
            $qry['where'] = "uri LIKE '" . $this->sql->quote($uri, true) . "%'";
            
            $query = $this->sql->build_select($qry);
            $this->sql->query();
            $tempentry = $this->sql->fetch_row();
            
        }

        // TODO: Handle multiple matches. Use $date (if given) to select between them.

        if (!empty($tempentry['uid'])) {
            // Looks like we found one! Now get it properly!
            $this->read_entry($tempentry['uid']);
        }

        return $this->entry;

    }

    /**
     * Tries to get an entry by its (complete) URI.
     * The entry is returned as an associative array.
     *
     * @param string $uri
     * @return array
     */
    function get_entry_by_uri($uri) {
        //debug("get_entry_by_uri($uri)");
        $qry = array();

        $qry['select'] = "uid, uri";
        $qry['from'] = $this->entriestable;
        $qry['limit'] = 1;

        $qry['where'] = "uri=" . $this->sql->quote($uri);

        $query = $this->sql->build_select($qry);
        $this->sql->query();
        $entry = $this->sql->fetch_row();

        // TODO: Handle multiple matches.

        if (!empty($entry['uid']) && $entry['uri'] == $uri) {
            //debug("found");
            // Looks like we found one! Now get it properly!
            $this->read_entry($entry['uid']);
            //debug_printr($this->entry);
            return $this->entry;
        }
        
        return false;
    }

   /**
     * Count the number of entries that will be read
     *
     * @param array $params
     * @return array
     */
    function read_entries_count($params=false) {

        $params['count_only'] = true;

        $number = $this->read_entries($params);
  
        //debug_printr($number);   

        return $number['number'];

    }

    /**
     * Read a bunch of entries
     *
     * @param array $params
     * @return array
     */
    function read_entries($params) {
        global $PIVOTX;

        $qry = array();

        $qry['select'] = "e.*, e.uid AS code, e.comment_count AS commcount, e.comment_names AS commnames, e.trackback_count AS trackcount, e.trackback_names AS tracknames";
        $qry['from'] = $this->entriestable. " AS e";

        if(!empty($params['offset'])) {
            $params['date'] = "";
            $qry['limit'] = intval($params['offset']) . ", " . $params['show'];
        } else {
            $qry['limit'] = $params['show'];
        }

        if (substr($params['orderby'],0,12) == "extrafields_") {
            if(empty($params['extrafields']) ) {
                $qry['select'] .= ", ef.target_uid, ef.value";
                $qry['leftjoin'][$this->extrafieldstable." AS ef"] = "e.uid = ef.target_uid";
            }
            
            $qry['where'][]= "ef.contenttype = 'entry'";
            $qry['where'][]= "ef.fieldkey = '".safeString(substr($params['orderby'],12))."'";
            if($params['ordertype'] == "int") {
                $orderby = "CAST(ef.value as SIGNED)";
            } else {
                $orderby = "ef.value";
            }
            
        } elseif (!empty($params['orderby'])) {
            if($params['ordertype'] == "int") {
                $orderby = "CAST(e.".safeString($params['orderby'], true)." as SIGNED)";
            } else {
                $orderby = "e.".safeString($params['orderby'], true);
            }
        } else {
            $orderby = "e.date";
        }

        if ($params['order'] == "random") {
            $qry['order'] = "RAND()";
        } elseif($params['order']=="desc") {
            $qry['order'] = $orderby . " DESC";
        } else {
            $qry['order'] = $orderby . " ASC";

        }

        if(!empty($params['uid'])) {
            
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
                $uids = implode(', ',$aUids);
                $qry['where'][] = "e.uid in (".$uids.")";
            }
            
        } else {
    
            if(!empty($params['start'])) {
                $params['date'] = "";
                $params['start'] = explode("-", $params['start']);
                $start = sprintf("%s-%02s-%02s %02s:%02s:00", $params['start'][0], $params['start'][1], 
                    $params['start'][2], $params['start'][3], $params['start'][4]);
                $qry['where'][] = $orderby . " > " . $this->sql->quote($start);
            }
    
            if(!empty($params['end'])) {
                $params['date'] = "";
                $params['end'] = explode("-", $params['end']);
                $end = sprintf("%s-%02s-%02s %02s:%02s:00", $params['end'][0], $params['end'][1], 
                    $params['end'][2], $params['end'][3], $params['end'][4]);
                $qry['where'][] = $orderby . " < " . $this->sql->quote($end);
            }
     
            if(!empty($params['date'])) {
                $params['date'] = explode("-", $params['date']);
                $year = (int) $params['date'][0];
                if (count($params['date']) == 1) {
                    $start = sprintf("%s-%02s-%02s 00:00:00", $year, 1, 1);
                    $year++;
                    $end = sprintf("%s-%02s-%02s 00:00:00", $year, 1, 1);
                } elseif (count($params['date']) == 2) {
                    $month = (int) $params['date'][1];
                    $start = sprintf("%s-%02s-%02s 00:00:00", $year, $month, 1);
                    $month++;
                    if ($month > 12) {
                        $month = 1;
                        $year++;
                    }
                    $end = sprintf("%s-%02s-%02s 00:00:00", $year, $month, 1);
                } else {
                    $month = (int) $params['date'][1];
                    $day = (int) $params['date'][2];
                    $start = sprintf("%s-%02s-%02s 00:00:00", $year, $month, $day);
                    $end = sprintf("%s-%02s-%02s 23:59:00", $year, $month, $day);
                }
                $qry['where'][] = "$orderby > " . $this->sql->quote($start);
                $qry['where'][] = "$orderby < " . $this->sql->quote($end);
            }
             
            // Do not use a limit if a date range is given
            if((!empty($params['start']) && !empty($params['end'])) || !empty($params['date'])) {
                unset($qry['limit']);
            } 
    
            if(!empty($params['status'])) {
                $qry['where'][] = "e.status = " . $this->sql->quote($params['status']);
            }
    
    
            if(!empty($params['user'])) {
                $qry['where'][] = "e.user = " . $this->sql->quote($params['user']);
            }
    
            // Bob notes: This group seems unnecesary at first, and it used to mess up
            // the order on certain versions of MySQL that had a bug in it. This version,
            // with the explicit order seems to work on both MySQL versions with and
            // without the bug.
            $qry['group'] = "e.date DESC, e.uid DESC";
    
            if( !empty($params['cats']) ) {
                $qry['select'] .= ", c.category";
                $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
                if (is_array($params['cats'])) {
                    $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
                } else {
                    $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
                }
                $qry['where'][] = "c.contenttype= 'entry'";
            }
            if( !empty($params['tags']) ) {
                $qry['select'] .= ", t.tag";
                $qry['leftjoin'][$this->tagstable . " AS t"] = "e.uid = t.target_uid";
                
                if(strpos($params['tags'],",") !== false) {
                    $aTags= explode(",",str_replace(" ","",$params['tags']));
                    $tags= implode("', '", $aTags);
                    
                    $qry['where'][] = "t.tag IN ('" . $tags. "')";
                } else {
                    $qry['where'][] = "t.tag= " . $this->sql->quote($params['tags']);
                }
                $qry['where'][] = "t.contenttype= 'entry'";

            }
            if( !empty($params['extrafields']) ) {
                $qry['select'] .= ", ef.target_uid";
                $qry['leftjoin'][$this->extrafieldstable." AS ef"] = "e.uid = ef.target_uid";
                
                
                foreach($params['extrafields'] as $k=>$v) {
                    $qry['where_or'][]= "(ef.contenttype='entry' AND ef.fieldkey = '".$k."' AND ef.value = '".$v."')";
                }
            }
        }
        
        if($params['count_only']===true) {
            // if we only want to count - override the select, group and order
            $qry['select'] = 'count(e.uid) as number';
            unset($qry['order']);
            unset($qry['group']);
            
            //debug_printr($qry);
            $query = $this->sql->build_select($qry);
            
            //debug(nl2br($query));            
            $this->sql->query();
            
            $result = $this->sql->fetch_row();
            // return the result and skip the recht if read_entries
            return $result;
        }
        
        $query = $this->sql->build_select($qry);
        $this->sql->query();

        // echo nl2br(htmlentities($query));

        $rows = $this->sql->fetch_all_rows();
        $entries = array();

        if(!is_array($rows)){$rows=array();}
        foreach ($rows as $entry) {
            $entries[ $entry['uid'] ] = $entry;
            
            // Make the 'excerpts'..
            $entries[ $entry['uid'] ]['excerpt'] = makeExcerpt($entry['introduction']);
            
            // Set the link..
            $entries[ $entry['uid'] ]['link'] = makeFileLink($entry, '', '');
        }



        if (is_array($entries)) {

            $ids = makeValuepairs($entries, '', 'uid');
            $ids = "'". implode("', '", $ids) . "'";
            
            // Ok, now we need to do a second query to get the correct arrays with all of the categories.
            $this->sql->query("SELECT * FROM ". $this->categoriestable ." AS c WHERE contenttype = 'entry' AND target_uid IN ($ids)");

            $tempcats = $this->sql->fetch_all_rows();

            if($tempcats) {
                // group them together by entry.
                foreach($tempcats as $cat) {
                    $cats[ $cat['target_uid'] ][] = $cat['category'];
                }

                // Add them to our simple cache, for later retrieval..
                $PIVOTX['cache']->setMultiple("categories", $cats);

                // Now, attach the categories to the entries..
                foreach($cats as $uid=>$cat) {
                    foreach($entries as $key=>$entry) {
                        if ($entries[$key]['uid'] == $uid) {
                            $entries[$key]['category'] = $cat;
                            continue;
                        }
                    }
                }
            }
            // And a third query to get the correct records with all of the extra fields.            
            $this->sql->query("SELECT * FROM ". $this->extrafieldstable ." AS e WHERE contenttype='entry' AND target_uid IN ($ids)");

            $tempfields = $this->sql->fetch_all_rows();

            // Now, attach the tempfields to the entries..
            if (!empty($tempfields)) {
                foreach($tempfields as $tempfield) {
                    foreach($entries as $key=>$entry) {
                        if ($entries[$key]['uid'] == $tempfield['target_uid']) {
                            if (!is_array($entries[ $key ]['extrafields'])) {
                                $entries[ $key ]['extrafields'] = array();
                            }
                            
                            // Check if it's a serialised value..
                            if (is_array(unserialize($temp_field['value']))) {
                                $temp_field['value'] = unserialize($temp_field['value']);
                            }
                        
                            $entries[ $key ]['extrafields'][ $tempfield['fieldkey'] ] = $tempfield['value'];
                        }
                    }
                }            
            }
        }

        // Add them to our simple cache, for later retrieval..
        $PIVOTX['cache']->setMultiple("entries", $entries);

        return $entries;


    }

    /**
     * Read the latest comments
     *
     * @param array $params
     * @return array
     */
    function read_latestcomments($params) {
        global $PIVOTX;

        $count = getDefault($params['count'], 10);

        $qry = array();
        $qry['select'] = "co.*, e.title, e.uid as entry_uid, e.user";
        $qry['from'] = $this->commentstable. " AS co";
        $qry['leftjoin'][$this->entriestable. " AS e"] = "e.status = 'publish'";
        $qry['where'][] = "co.contenttype = 'entry'";
        $qry['where'][] = "co.entry_uid = e.uid";
        $qry['where'][] = "co.moderate = 0";
        $qry['order'] = "co.date DESC";
        $qry['limit'] = intval($count);
        
        if( !empty($params['cats']) ) {
            $qry['select'] .= ", c.category";
            $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
            if (is_array($params['cats'])) {
                $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
            } else {
                $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
            }
            $qry['where'][] = "c.contenttype = 'entry'";
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        // echo nl2br(htmlentities($query));

        $comments = $this->sql->fetch_all_rows();

        foreach($comments as $key=>$comment) {
            $comments[$key]['allowedit'] = $PIVOTX['users']->allowEdit('comment', $comment['user']);
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

        $count = getDefault($params['count'], 10);

        $qry = array();
        $qry['select'] = "tb.*, e.title, e.user";
        $qry['from'] = $this->trackbackstable. " AS tb";
        $qry['leftjoin'][$this->entriestable. " AS e"] = "e.status = 'publish'";
        $qry['where'][] = "tb.entry_uid = e.uid";
        $qry['where'][] = "tb.moderate = 0";
        $qry['order'] = "tb.date DESC";
        $qry['limit'] = intval($count);
        
        if( !empty($params['cats']) ) {
            $qry['select'] .= ", c.category";
            $qry['leftjoin'][$this->categoriestable . " AS c"] = "e.uid = c.target_uid";
            if (is_array($params['cats'])) {
                $qry['where'][] = "c.category IN('" . implode("', '", $params['cats']). "')";
            } else {
                $qry['where'][] = "c.category= " . $this->sql->quote($params['cats']);
            }
        }

        $query = $this->sql->build_select($qry);
        $this->sql->query();

        $trackbacks = $this->sql->fetch_all_rows();
                
        foreach($trackbacks as $key=>$trackback) {
            $trackbacks[$key]['allowedit'] = $PIVOTX['users']->allowEdit('trackback', $trackback['user']);
        }

        return $trackbacks;
        
    }




    /**
     * Sets the current entry to the contents of $entry - mysql
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
           $this->entry['code'] = '';
        }

        $this->entry['uid'] = $this->entry['code'];

        return $this->entry;
    }

    /**
     * Saves the current entry - mysql implementation.
     *
     * Returns true if successfully saved. Current implementation
     * seems to return true no matter what...
     *
     * @param boolean $update_index Whether to update the date index.
     * @return boolean
     */
    function save_entry($update_index=TRUE) {

        // Set the 'commcount', 'commnames'..
        unset($commnames);
        if (isset($this->entry['comments'])) {

            // Initialise the IP blocklist.
            $blocklist = new IPBlock();

            foreach ($this->entry['comments'] as $comment) {
                if (!$blocklist->isBlocked($comment['ip'])) {
                    if ($comment[moderate]!=1) {
                        $commnames[]=stripslashes($comment['name']);
                    } else {
                        // if moderation is on, we add the name as '-'..
                        $commnames[]='-';
                    }
                }
            }

            if (isset($commnames) && (count($commnames)>0)) {
                $this->entry['comment_names'] = implode(", ",array_unique ($commnames));
                $this->entry['comment_count'] = count($commnames);
            } else {
                $this->entry['comment_names'] = "";
                $this->entry['comment_count'] = 0;
            }

        } else {
            unset ($this->entry['comments']);
            $this->entry['comment_names'] = "";
            $this->entry['comment_count'] = 0;
        }


        // Set the 'trackcount', 'tracknames'..
        unset($tracknames);
        if (isset($this->entry['trackbacks'])) {

            foreach ($this->entry['trackbacks'] as $trackback) {
                $tracknames[]=stripslashes($trackback['name']);
            }

            if (isset($tracknames) && (count($tracknames)>0)) {
                $this->entry['trackback_names'] = implode(", ",array_unique ($tracknames));
                $this->entry['trackback_count'] = count($tracknames);
            } else {
                $this->entry['trackback_names'] = "";
                $this->entry['trackback_count'] = 0;
            }
        } else {
            unset ($this->entry['trackbacks']);
            $this->entry['trackback_names']="";
            $this->entry['trackback_count'] = 0;
        }

        // Make sure we have an URI
        if (empty($this->entry['uri'])) {
            $this->entry['uri'] = makeURI($this->entry['title']); 
        }

        $values = array(
            'title' => $this->entry['title'],
            'uri' => $this->entry['uri'],
            'subtitle' => $this->entry['subtitle'],
            'introduction' => $this->entry['introduction'],
            'body' => $this->entry['body'],
            'convert_lb' => intval($this->entry['convert_lb']),
            'status' => $this->entry['status'],
            'date' => $this->entry['date'],
            'publish_date' => $this->entry['publish_date'],
            'edit_date' => date("Y-m-d H:i:s", getCurrentDate()),
            'user' => $this->entry['user'],
            'allow_comments' => $this->entry['allow_comments'],
            'keywords' => $this->entry['keywords'],
            'via_link' => $this->entry['vialink'],
            'via_title' => $this->entry['viatitle'],
            'comment_count' => $this->entry['comment_count'],
            'comment_names' => $this->entry['comment_names'],
            'trackback_count' => $this->entry['trackback_count'],
            'trackback_names' => $this->entry['trackback_names']
        );


        // Check if the entry exists
        $this->sql->query("SELECT uid FROM " . $this->entriestable . " WHERE uid=" . intval($this->entry['uid']));

        if (is_array($this->sql->fetch_row())) {

            // It exists, we do an update..

            $qry=array();
            $qry['update'] = $this->entriestable;
            $qry['value'] = $values;
            $qry['where'] = "uid=" . intval($this->entry['uid']);

            $this->sql->build_update($qry);
            $this->sql->query();


        } else {

            // New entry.

            // Add the UID to the values array if it is already set (for 
            // example when importing entries).
            if ($this->entry['uid'] != '') {
                $values['uid'] = $this->entry['uid'];
            }

            $qry=array();
            $qry['into'] = $this->entriestable;
            $qry['value'] = $values;

            $this->sql->build_insert($qry);
            
            $this->sql->query();
        
            // Set the UID to the last inserted ID if it isn't already set 
            // (which is normally the case for new entries).
            if ($this->entry['uid'] == '') {    
                $this->entry['uid'] = $this->sql->get_last_id();
            }
            
            // A bit of a nasty hack, but needed when we have to insert tags for a new entry,
            // and $db is not yet aware of the new $uid.
            $GLOBALS['db']->entry['uid'] = $this->entry['uid'];

        }


        // We will also need to save the comments and trackbacks.. We should
        // try to prevent doing unneeded queries, so we only insert comments
        // and trackbacks which have no ['uid'] yet. (because these are either
        // new, or are being converted from flat files)
        if (!empty($this->entry['comments'])) {
            foreach ($this->entry['comments'] as $comment) {

                if ($comment['uid']=="") {

                    // Ah, let's insert it.
                    $comment['entry_uid'] = $this->entry['uid'];

                    $comment['contenttype'] = 'entry';

                    // make sure we don't try to add the 'remember info' or 'allowedit' fields..
                    if (isset($comment['rememberinfo'])) { unset($comment['rememberinfo']); }
                    if (isset($comment['allowedit'])) { unset($comment['allowedit']); }

                    // Registered, Notify, etc. have to be integer values.
                    $comment['registered'] = intval($comment['registered']);
                    $comment['notify'] = intval($comment['notify']);
                    $comment['discreet'] = intval($comment['discreet']);
                    $comment['moderate'] = intval($comment['moderate']);
                    $comment['entry_uid'] = intval($comment['entry_uid']);

                    $qry=array();
                    $qry['into'] = $this->commentstable;
                    $qry['value'] = $comment;

                    $this->sql->build_insert($qry);
                    $this->sql->query();

                }

            }
        }

        if (!empty($this->entry['comments'])) {
            foreach ($this->entry['trackbacks'] as $trackback) {

                if ($trackback['uid']=="") {

                    // Ah, let's insert it.
                    $trackback['entry_uid'] = $this->entry['uid'];

                    $qry=array();
                    $qry['into'] = $this->trackbackstable;
                    $qry['value'] = $trackback;

                    $this->sql->build_insert($qry);
                    $this->sql->query();

                }
            }
        }

        // Delete the keywords / tags..
        $qry=array();
        $qry['delete'] = $this->tagstable;
        $qry['where'] = "contenttype='entry' AND target_uid=" . intval($this->entry['uid']);

        $this->sql->build_delete($qry);
        $this->sql->query();

        $tags = getTags(false, $this->entry['introduction'].$this->entry['body'], $this->entry['keywords']);

        // Add the keywords / tags..
        foreach ($tags as $tag) {
            $qry=array();
            $qry['into'] = $this->tagstable;
            $qry['value'] = array(
                'tag' => $tag,
                'contenttype' => 'entry',
                'target_uid' => $this->entry['uid']
            );

            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        // Delete the categories..
        $qry=array();
        $qry['delete'] = $this->categoriestable;

        $qry['where'][] = "contenttype='entry'";
        $qry['where'][] = "target_uid=" . intval($this->entry['uid']);

        $this->sql->build_delete($qry);
        $this->sql->query();

        // Add the Categories..
        foreach ($this->entry['category'] as $cat) {
            $qry=array();
            $qry['into'] = $this->categoriestable;
            $qry['value'] = array(
                'category' => safeString($cat, true),
                'contenttype' => 'entry',
                'target_uid' => $this->entry['uid']
            );

            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        // Store the 'extra fields'
        if (!is_array($this->entry['extrafields'])) { $this->entry['extrafields'] = array(); }
        $extrakeys = array();
        foreach ($this->entry['extrafields'] as $key => $value) {
            $extrakeys[] = $this->sql->quote($key);        
            
            // No need to store empty values
            if (empty($value)) { unset ($this->entry['extrafields'][$key]); }
            
            // Serialize any arrays..
            if (is_array($value)) {
                $this->entry['extrafields'][$key] = serialize($value);
            }
        }
        
        if (count($extrakeys)>0) {
            $qry=array();
            $qry['delete'] = $this->extrafieldstable;
            $qry['where'][] = "target_uid=" . intval($this->entry['uid']);
            $qry['where'][] = "contenttype='entry'";
            $qry['where'][] = "fieldkey IN (" . implode(", ", $extrakeys) . ")";
            $this->sql->build_delete($qry);
            $this->sql->query();        
        }
        
        foreach ($this->entry['extrafields'] as $key => $value) {
            $qry=array();
            $qry['into'] = $this->extrafieldstable;
            $qry['value'] = array(
                'fieldkey' => safeString($key, true),
                'value' => $value,
                'contenttype' => 'entry',
                'target_uid' => $this->entry['uid']
            );
            $this->sql->build_insert($qry);
            $this->sql->query();
        }
        
        //echo "<pre>\n"; print_r($extrakeys); echo "</pre>\n";
        //echo "<pre>\n"; print_r($this->entry['extrafields']); echo "</pre>\n";

        return TRUE;

    }


    function delete_entry() {


        $uid = intval($this->entry['uid']);

        $this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid=$uid LIMIT 1;");
        $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE contenttype='entry' AND entry_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE entry_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->tagstable . " WHERE contenttype='entry' AND target_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->categoriestable . " WHERE contenttype='entry' AND  target_uid=$uid;");
        $this->sql->query("DELETE FROM " . $this->extrafieldstable . " WHERE contenttype='entry' AND target_uid=$uid;");

    }





    /**
     * Delete one or more entries
     *
     * @param array $ids
     */
    function delete_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE contenttype='entry' AND  entry_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE entry_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->tagstable . " WHERE contenttype='entry' AND  target_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->categoriestable . " WHERE contenttype='entry' AND  target_uid IN ($ids);");
        $this->sql->query("DELETE FROM " . $this->extrafieldstable . " WHERE contenttype='entry' AND  target_uid IN ($ids);");

        return true;

    }


    /**
     * Set one or more entries to 'publish'
     *
     * @param array $ids
     */
    function publish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $qry=array();
        $qry['update'] = $this->entriestable;
        $qry['value'] = array('status' => 'publish');
        $qry['where'] = "uid IN ($ids)";

        $query = $this->sql->build_update($qry);

        $this->sql->query();

        return true;


    }


    /**
     * Set one or more entries to 'hold'
     *
     * @param array $ids
     */
    function depublish_entries($ids) {

        if (!is_array($ids) || count($ids) == 0 ) {
            return false;
        }

        // Make sure we just have integers.
        $ids = array_map("intval", $ids);
        $ids = "'" . implode("', '", $ids) . "'";

        $qry=array();
        $qry['update'] = $this->entriestable;
        $qry['value'] = array('status' => 'hold');
        $qry['where'] = "uid IN ($ids)";

        $query = $this->sql->build_update($qry);

        $this->sql->query();

        return true;

    }


    /**
     * Checks if any entries set to 'timed publish' should be published.
     *
     */
    function checkTimedPublish() {
        global $PIVOTX;
        
        $date = formatDate('', "%year%-%month%-%day% %hour24%:%minute%:%sec%");
        
        $this->sql->query("UPDATE `".$this->entriestable."` SET status='publish', date=publish_date
            WHERE status='timed' AND publish_date<'$date';");
    }


    /**
     * Deletes the comment with the given comment ID (uid), updates the 
     * comment count for the associated entry and clears the 
     * related cache items.
     *
     * @param int $uid
     */
    function delete_comment( $uid ) {
        global $PIVOTX;

        // Find the associated entries so we can update comment count and clear the cache.
        $this->sql->query("SELECT entry_uid FROM " . $this->commentstable . " WHERE contenttype = 'entry' AND uid=$uid;");
        $comment = $this->sql->fetch_row();
        
        if (!empty($comment['entry_uid'])) {
            $entry_uid = $comment['entry_uid'];
        
            $PIVOTX['cache']->set("comments", $entry_uid, array());
            $PIVOTX['cache']->set("entries", $entry_uid, array());
            
            $this->sql->query("UPDATE " . $this->entriestable . " SET comment_count = comment_count -1 WHERE uid=$entry_uid;");
            $this->sql->query("DELETE FROM " . $this->commentstable . " WHERE contenttype = 'entry' AND uid=$uid;");
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

        $comm = $this->entry['comments'][$uid];

        return $comm;
    }
 

    /**
     * Deletes the trackback with the given trackback ID (uid), updates the 
     * trackback count for the associated entry and clears the 
     * related cache items.
     *
     * @param int $uid
     */
    function delete_trackback( $uid ) {
        global $PIVOTX;

        // Find the associated entries so we can update trackback count and clear the cache.
        $this->sql->query("SELECT entry_uid FROM " . $this->trackbackstable . " WHERE uid=$uid;");
        $trackback = $this->sql->fetch_row();
        $entry_uid = $trackback['entry_uid'];
        $PIVOTX['cache']->set("trackbacks", $entry_uid, array());
        $PIVOTX['cache']->set("entries", $entry_uid, array());
        $this->sql->query("UPDATE " . $this->entriestable . " SET trackback_count = trackback_count -1 WHERE uid=$entry_uid;");

        $this->sql->query("DELETE FROM " . $this->trackbackstable . " WHERE uid=$uid;");

    }

    /**
     * Returns a trackback from the current entry.
     *
     * @param int $uid
     * @return array
     */
    function get_trackback($uid) {
        global $PIVOTX;

        $track = $this->entry['trackbacks'][$uid];

        return $track;
    }
 



    // -----------------
    // private functions
    // ------------------



    // Convert a string, so that it only contains alphanumeric and a few others.
    function safestring($name) {
        return preg_replace("/[^-a-zA-Z0-9_.]/", "", $name);
    }

}


?>
