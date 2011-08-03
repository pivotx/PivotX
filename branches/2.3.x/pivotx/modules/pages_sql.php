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

require_once(dirname(__FILE__)."/module_sql.php");


/**
 * Class to work with Pages, using the flat file storage model.
 *
 */
class PagesSql {

    /**
     * Initialisation.
     *
     * @return PagesFlat
     */
    function PagesSql() {
        global $PIVOTX;

        // Set the names for the tables we use.
        $this->pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);
        $this->chapterstable = safeString($PIVOTX['config']->get('db_prefix')."chapters", true);
        $this->tagstable = safeString($PIVOTX['config']->get('db_prefix')."tags", true);
        $this->extrafieldstable = safeString($PIVOTX['config']->get('db_prefix')."extrafields", true);

        // Set up DB connection
        $this->sql = new sql(
                'mysql',
                $PIVOTX['config']->get('db_databasename'),
                $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'),
                $PIVOTX['config']->get('db_password')
            );

    }

    /**
     * Get the current index of the pages.
     *
     * @return array
     */
    function getIndex($filter_user="", $excerpts=false, $links=false) {
        global $PIVOTX;

        // If we've already built the index, and we don't need to filter
        // on user or to create excerpts.
        if (!empty($this->index) && is_array($this->index) && empty($filter_user) && ($excerpts==false)) {
            return $this->index;
        }

        // First get the chapters
        $qry = array();
        $qry['select'] = "*";
        $qry['from'] = $this->chapterstable;
        $qry['order'] = 'sortorder';

        $this->sql->build_select($qry);
        $this->sql->query();

        $rows = $this->sql->fetch_all_rows();

        // Then get the pages..
        $qry = array();
        $qry['select'] = "uid, uri, title, subtitle, user, date, chapter, sortorder, status, template, SUBSTRING(introduction, 1, 200) as introduction";
        $qry['from'] = $this->pagestable;

        if ($PIVOTX['config']->get('sort_pages_by_alphabet')==true) {
            $qry['order'] = 'uri';
        } else {
            $qry['order'] = 'sortorder';
        }

        if (!empty($filter_user)) {
            $qry['where'][] = "user=" . $this->sql->quote($filter_user); 
        }

        $this->sql->build_select($qry);
        $this->sql->query();

        $pages = $this->sql->fetch_all_rows();

        // Make the 'excerpts', if required..
        if ($excerpts && !empty($pages)) {
            foreach($pages as $key=>$page) {
                $pages[$key]['excerpt'] = makeExcerpt($page['subtitle'].$page['introduction']);
            }
        }

        // Make the links, if required..
        if ($links && !empty($pages)) {
            foreach($pages as $key=>$page) {
                $pages[$key]['link'] = makePageLink($pages[$key]['uri']);
            }
        }

        // Mark the pages as editable or not..
        foreach ($pages as $key=>$page) { 
            $pages[$key]['editable'] = $PIVOTX['users']->allowEdit('page', $page['user']);
        }

        $this->index = array();

        // Aggregate the chapters into the index.
        foreach ($rows as $row) {
            $this->index[ $row['uid'] ] = $row;
            $this->index[ $row['uid'] ]['editable'] = $PIVOTX['users']->allowEdit('chapter');
        }

        // Aggregate the pages into the index.
        foreach ($pages as $page) {
            if (isset($this->index[ $page['chapter'] ])) {
                $this->index[ $page['chapter'] ]['pages'][] = $page;
            } else {
                $this->index['orphaned']['pages'][] = $page;
                $this->index['orphaned']['editable'] = $PIVOTX['users']->allowEdit('chapter');
            }
        }

        return $this->index;

    }

    /**
     * Save the index to the file system
     *
     */
    function saveIndex() {

        return;

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
     * Add a chapter, and save the index
     *
     * @param array $chapter
     */
    function addChapter($chapter) {

        $qry = array();
        $qry['into'] = $this->chapterstable;
        $qry['value'] = array(
                'chaptername' => $chapter['chaptername'],
                'description' => $chapter['description'],
                'sortorder' => $chapter['sortorder']
            );

        $this->sql->build_insert($qry);
        $this->sql->query();

        // Updating the index too.
        $this->index[] = $qry['value'];

        return $this->index;


    }



    /**
     * Delete a chapter, and save the index
     *
     * @param integer $uid
     */
    function delChapter($uid) {

        $qry = array();
        $qry['delete'] = $this->chapterstable;
        $qry['where'] = "uid=".intval($uid);
        $qry['limit'] = "1";

        $this->sql->build_delete($qry);
        $this->sql->query();

        // Updating the index too.
        unset($this->index[$uid]);

        return $this->index;


    }



    /**
     * Update the information for a chapter, and save the index
     *
     * @param integer $id
     * @param array $chapter
     */
    function updateChapter($id,$chapter) {

        $qry = array();
        $qry['update'] = $this->chapterstable;
        $qry['value'] = array(
                'chaptername' => $chapter['chaptername'],
                'description' => $chapter['description'],
                'sortorder' => $chapter['sortorder']
            );
        $qry['where'] = "uid=" . intval($id);
        $qry['limit'] = "1";

        $this->sql->build_update($qry);
        $this->sql->query();
        
        // Updating the index too.
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

        $qry = array();
        $qry['select'] = "*";
        $qry['from'] = $this->pagestable;
        $qry['limit'] = 1;
        $qry['where'] = "uid=" . intval($uid);

        $this->sql->build_select($qry);
        $this->sql->query();

        $page = $this->sql->fetch_row();

        $page['link'] = makePageLink($page['uri']);

        // get the 'extra fields'
        $this->sql->query("SELECT * FROM " . $this->extrafieldstable . " WHERE contenttype='page' and target_uid=". intval($page['uid']) . " ORDER BY uid ASC");
        $temp_fields = $this->sql->fetch_all_rows();
        $page['extrafields'] = array();
        if(is_array($temp_fields)) {
            foreach($temp_fields as $temp_field) {
                $page['extrafields'][ $temp_field['fieldkey'] ] = $temp_field['value'];
            }
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

        $qry = array();
        $qry['select'] = "*";
        $qry['from'] = $this->pagestable;
        $qry['limit'] = 1;
        $qry['where'] = "uri LIKE " . $this->sql->quote($uri);

        $this->sql->build_select($qry);
        $this->sql->query();

        $page = $this->sql->fetch_row();

        if (is_array($page)) {
            $page['link'] = makePageLink($page['uri']);
            
            // get the 'extra fields'
            $this->sql->query("SELECT * FROM " . $this->extrafieldstable . " WHERE contenttype='page' and target_uid=". intval($page['uid']) . " ORDER BY uid ASC");
            $temp_fields = $this->sql->fetch_all_rows();
            $page['extrafields'] = array();
            if(is_array($temp_fields)) {
                foreach($temp_fields as $temp_field) {
                    $page['extrafields'][ $temp_field['fieldkey'] ] = $temp_field['value'];
                }
            }
            
            // Set the chaptername (in addition to just the chapter's ID)
            $chapters = $PIVOTX['pages']->getIndex();
            $page['chaptername'] = $chapters[ $page['chapter'] ]['chaptername'];
            
            return $page;
        } else {
            // we couldn't find the page. Bummer!
            return array();
        }

    }

    /**
     * Gets a list of the $amount latest pages
     *
     * @param integer $amount
     */
    function getLatestPages($amount, $filter_user="") {


        $qry = array();
        $qry['select'] = "p.uid, p.title, p.uri, p.subtitle, p.template, p.status, p.date, p.publish_date, p.edit_date, p.user, SUBSTRING(p.introduction, 1, 200) as introduction, c.chaptername";
        $qry['from'] = $this->pagestable . " AS p";
        $qry['limit'] = intval($amount);
        $qry['order'] = "date DESC";
        $qry['leftjoin'][$this->chapterstable . " AS c"] = "p.chapter=c.uid";

        if (!empty($filter_user)) {
            $qry['where'][] = "user=" . $this->sql->quote($filter_user); 
        }

        $query = $this->sql->build_select($qry);

        $this->sql->query();

        $pages = $this->sql->fetch_all_rows();

        // Make the 'excerpts'..
        foreach($pages as $key=>$page) {
            $pages[$key]['excerpt'] = makeExcerpt($page['subtitle'].$page['introduction']);
        }


        return $pages;

    }

    /**
     * Delete a single page
     *
     * @param integer $uid
     */
    function delPage($uid) {

        $qry = array();
        $qry['delete'] = $this->pagestable;
        $qry['limit'] = 1;
        $qry['where'] = "uid=" . intval($uid);

        $this->sql->build_delete($qry);
        $this->sql->query();

        unset($this->index);
        $this->getIndex();

    }


    /**
     * Save a single page
     *
     * @param integer $id
     * @param array $page
     */
    function savePage($page) {


        $value = array(
            'title' => $page['title'],
            'uri' => $page['uri'],
            'subtitle' => $page['subtitle'],
            'introduction' => $page['introduction'],
            'body' => $page['body'],
            'user' => $page['user'],
            'sortorder' => $page['sortorder'],
            'allow_comments' => $page['allow_comments'],
            'date' => $page['date'],
            'chapter' => $page['chapter'],
            'publish_date' => $page['publish_date'],
            'edit_date' => date("Y-m-d H:i:s", getCurrentDate()),
            'convert_lb' => intval($page['convert_lb']),
            'status' => $page['status'],
            'keywords' => $page['keywords'],
            'template' => $page['template']
        );


        if ($page['uid']=="" || $page['uid']==">") {
            // New page!

            $qry=array();
            $qry['into'] = $this->pagestable;
            $qry['value'] = $value;

            $this->sql->build_insert($qry);

            $this->sql->query();

            $uid = $this->sql->get_last_id();


        } else {

            $uid = intval($page['uid']);

            $qry=array();
            $qry['update'] = $this->pagestable;
            $qry['value'] = $value;
            $qry['where'] = "uid=" . $uid;

            $this->sql->build_update($qry);

            $this->sql->query();

        }



        // Delete the keywords / tags..
        $qry=array();
        $qry['delete'] = $this->tagstable;
        $qry['where'] = "contenttype='page' AND target_uid=" . intval($uid);

        $this->sql->build_delete($qry);
        $this->sql->query();

        $tags = getTags(false, '', $page['keywords']);

        // Add the keywords / tags..
        foreach ($tags as $tag) {
            $qry=array();
            $qry['into'] = $this->tagstable;
            $qry['value'] = array(
                'tag' => $tag,
                'contenttype' => 'page',
                'target_uid' => intval($uid)
            );

            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        // Store the 'extra fields'
        if (!is_array($page['extrafields'])) { $page['extrafields'] = array(); }
        $extrakeys = array();
        foreach ($page['extrafields'] as $key => $value) {
            $extrakeys[] = $this->sql->quote($key);        
            if (empty($value)) { unset ($page['extrafields'][$key]); }  
        }
        
        if (count($extrakeys)>0) {
            $qry=array();
            $qry['delete'] = $this->extrafieldstable;
            $qry['where'][] = "target_uid=" . intval($uid);
            $qry['where'][] = "contenttype='page'";
            $qry['where'][] = "fieldkey IN (" . implode(", ", $extrakeys) . ")";
            $this->sql->build_delete($qry);
            $this->sql->query();        
        }

        foreach ($page['extrafields'] as $key => $value) {
            $qry=array();
            $qry['into'] = $this->extrafieldstable;
            $qry['value'] = array(
                'fieldkey' => safeString($key, true),
                'value' => $value,
                'contenttype' => 'page',
                'target_uid' => $uid
            );
            $this->sql->build_insert($qry);
            $this->sql->query();
        }


        unset($this->index);
        $this->getIndex();

        // Return the uid of the page we just inserted / updated..
        return $uid;

    }


    /**
     * Checks if any pages set to 'timed publish' should be published.
     *
     */
    function checkTimedPublish() {
        global $PIVOTX;
        
        $date = formatDate('', "%year%-%month%-%day% %hour24%:%minute%:%sec%");
        
        $this->sql->query("UPDATE `".$this->pagestable."` SET status='publish', date=publish_date
            WHERE status='timed' AND publish_date<'$date';");
    }

}


?>
