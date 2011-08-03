<?php
/**
 * Contains the functions we use to search entries and pages, and
 * indexing entries and pages for flat file database.
 *
 * @package pivotx
 * @subpackage modules
 * @todo Support Asian languages word-splitting.
 */

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

@set_time_limit(0);

$tmp_filtered_words = getFilteredWords();
$filtered_words = array();
foreach($tmp_filtered_words as $word) {
    // TODO - do something or not?
    $filtered_words[] = px_strtolower($word);
}


/**
 * Calculates a search index key (used for file names). Only used be 
 * flat file database.
 *
 * @param string $str
 * @return string 
 */
function searchIndexKey($str) {

    $char = $str{0};

    $char = trim(strtr($char, "\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD".
        "\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF". 
        "\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF".
        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF",
        "AAAAAAACEEEEIIIIDNOOOOOOUUUYYTS"."
        aaaaaaaceeeeiiiidnoooooouuuyyty")
    );

    // Make sure we don't allow characters that are illegal in filenames.
    // Replacing non-alphanumeric characters with "z". Hackish ...
    $char = preg_replace('/[^a-zA-Z0-9]/', 'z', $char);
    
    return px_strtolower($char);
}

// ---------- functions for indexing ------------- //

/**
 * Indexes entries and pages in the PivotX database and returns true
 * if there are more entries to index.
 *
 * @param int $start Code for first entry to index
 * @param int $stop Code for last entry to index
 * @param int $time Indexing time.
 * @return boolean
 */
function createSearchIndex ($start, $stop, $time) {
    global $PIVOTX, $output;

    $entries = $PIVOTX['db']->db_lowlevel->date_index;

    $count = 0;

    $date = date( 'Y-m-d-H-i' );

    $searchcats = $PIVOTX['categories']->getSearchCategorynames();

    foreach($entries as $key => $value) {

        if(($count++)<($start)) { continue; }
        if(($count)>($stop)) { break; }

        $entry = $PIVOTX['db']->read_entry( $key );

        // rules: index if all are true:
        // - ( status == 'publish' )or(( status == 'timed')&&( publish_date <= date ))
        // - at least one category is in array of 'not hidden' categories..

        // check status and date
        if(( 'publish'==$entry['status'] )
        ||(( 'timed'==$entry['status'] )&&( $entry['publish_date'] <= $date ))) {

            // Only index the entry if it is in one or more categories that are not hidden.
            if( count(array_intersect($entry['category'], $searchcats))>0 ) {
                if (($count % 50) == 0) {
                    $output .= sprintf(__("%1.2f sec: Processed %d entries...")."<br />\n", (timeTaken('int')+$time), $count);
                }
                addToSearchIndex( $entry);
            }
        }
    }

    // decide if we need to do some more.
    if(count($entries) > ($stop)) {
        return true;
    }

    // When we are done with the entries, index the pages (assuming there are much less pages than entries).
    $chapters = $PIVOTX['pages']->getIndex();
    $count = 0;

    foreach($chapters as $chapter) {
        foreach($chapter['pages'] as $page) {

            // rules: index if all are true:
            // - ( status == 'publish' )or(( status == 'timed')&&( publish_date <= date ))
            // - at least one category is in array of 'not hidden' categories..

            // check status and date
            if(( 'publish'==$page['status'] ) || (( 'timed'==$page['status'] )&&( $page['publish_date'] <= $date ))) {

                $page = $PIVOTX['pages']->getPage($page['uid']);
                addToSearchIndex( $page, 'p');
                $count++;
            }
        }
    }
    $output .= sprintf(__("Processed %d pages...")."<br />\n", $count);
    flush();

    return false;

}


/**
 * Updates the search index for a single entry or page.
 *
 * @param array $item The entry or page to get indexed/updated.
 * @param string $type Tells if we are indexing an entry or a page. 
 * @return void
 */
function updateSearchIndex($item, $type='e') {

    removeFromSearchIndex($item, $type);
    addToSearchIndex($item, $type);
    writeSearchIndex(true, false);

}


/**
 * Parses the entry/page, strips punctuation and stop/non-words, lower cases 
 * and adds to the global master index array.
 *
 * @uses filterWords The function that strips the stop/non-words
 * @return void
 */
function addToSearchIndex ($arr, $type='e') {
    global $master_index, $master_index_unaltered;

    require_once $PIVOTX['paths']['pivotx_path'].'includes/strip_punctuation.php';

    $words = $arr['title']." ".$arr['subtitle']." ".$arr['keywords']." ".
        parse_intro_or_body($arr['introduction']." ".$arr['body'], false, $arr['convert_lb']);
    // Remove (Java)script and PHP code. (In a perfect world
    // we would run the scripts and PHP code... but not even Google
    // parses the Javascript.)
    $regexp = "#(<script[ >].*?</script>)|(<\?php\s.*?\?>)#is";
    $words = preg_replace($regexp, "", $words);

    $words = unentify(strip_tags(str_replace(">", "> ", str_replace("<", " <",$words))));
    $words = px_strtolower($words);
    $words = strip_punctuation($words);

    // TODO: Base splitting on spaces (or allowed multibyte character/chinese).
    $result = preg_split('/\s+/', $words);

    $type_code = $type . $arr['uid'];
    $filter = filterWords($result);
    $filter = array_unique($filter);

    foreach($filter as $string) {
        $key = searchIndexKey($string);
        // See removeFromSearchIndex to understand why the following code works.
        if (!isset($master_index[ $key ]) && isset($master_index_unaltered[ $key ])) {
            $master_index[ $key ] = $master_index_unaltered[ $key ];
        }
        if(!isset($master_index[ $key ][ $string ])) {
            $master_index[ $key ][ $string ] = "$type_code";
        } else {
            $master_index[ $key ][ $string ] .= "|$type_code";
        }
    }
    unset($master_index_unaltered);
}

/**
 * For a given entry/page, removes the related key from the content of the index.
 * The global master index array contains the altered content (keys).
 *
 * @return void
 */
function removeFromSearchIndex ($item, $type='e') {
    global $PIVOTX, $master_index, $master_index_unaltered;

    $type_code = $type . $item['uid'];
    $dir = $PIVOTX['paths']['db_path'].'search/';
    $d = dir($dir);
    while ( false !== ( $entry = $d->read())) {
        $extension = getExtension($entry);
        if ($extension == 'php') {
            $key_altered = false;
            $key = preg_replace("/.$extension$/", "", $entry);
            $key_content = loadSerialize($dir . $entry);
            foreach ($key_content as $word => $item_str) {
                $items = explode('|', $item_str);
                $ind = array_search($type_code, $items);
                if ($ind !== false) {
                    unset($items[$ind]);
                    $key_altered = true;
                }
                if (count($items) > 0) {
                    $key_content[$word] = implode('|', $items); 
                } else {
                    unset($key_content[$word]);
                }
            }
            if ($key_altered) {
                $master_index[ $key ] = $key_content;
            } else {
                $master_index_unaltered[ $key ] = $key_content;
            }
        }
    }
    $d->close();
}

/**
 * Strips stop/non-words from an array of words.
 *
 * @param array $arr Words to be filtered.
 * @return array
 */
function filterWords ($arr) {
    global $filtered_words;

    $clean = array();

    foreach($arr as $value) {

        // Do not include same word several times or very short words
        if (in_array($value,$clean) || (strlen($value) <= 2)) {
            continue;
        }
        // Filtering out common (or just unwanted words)
        if (is_array($filtered_words)) {
            if (!in_array($value, $filtered_words)) {
                $clean[] = $value;
            }
        } else {
            $clean[] = $value;
        }
    }

    return $clean;
}

/**
 * Write the index to file (using the global variable $master_index.
 *
 * @param boolean $silent
 * @return void
 */
function writeSearchIndex ($silent=false, $loadindex=true) {
    global $master_index, $output, $PIVOTX;

    if( is_array( $master_index )) {

        debug("saving ".count($master_index)." indices.");

        if( 0 != count( $master_index )) {

            $wordcount = 0;

            foreach($master_index as $key => $index) {
                $filename = $PIVOTX['paths']['db_path'].'search/' . $key . '.php';

                // load the index if it exists..
                if ($loadindex && file_exists($filename)) {
                    $temp = loadSerialize($filename);
                } else {
                    $temp = array();
                }

                // add the new stuff..
                foreach($index as $key=>$val) {
                    if(isset($temp[$key])) {
                        $occurr = explode("|", $temp[$key]);
                        $occurr[] = $val;
                        $val = implode("|", array_unique($occurr));
                        $temp[$key] = $val;
                    } else {
                        $temp[$key] = $val;
                    }
                }

                saveSerialize($filename, $temp);
                $wordcount += count($index);
            }

            if($silent!=TRUE) {
                $output .= "<p>" . sprintf(__('A total of %s different words have been indexed.'), $wordcount) . "</p>";
            }
        }
    } else {
        debug("nothing to save");
    }
}

/**
 * Compare weights of search results
 *
 * @param	assoc.array		search result a
 * @param	assoc.array		search result b
 * @return	integer			-1,0,+1 depending on comparison
 */
function cmp_weights(&$a, &$b) {
    if ($a['weight'] > $b['weight']) {
        return -1;
    }
    if ($a['weight'] < $b['weight']) {
        return +1;
    }
    return 0;
}

/**
 * Weight a text part relative to some other part
 *
 * @param	string		the subject to search in
 * @param	string		the complete search term (lowercased)
 * @param	array		all the individuele search terms (lowercased)
 * @param	integer		maximum number of points to return
 * @return	integer		the weight
 */
function weighText($subject, $complete, $words, $max) {
    $low_subject = px_strtolower(trim($subject));

    if ($low_subject == $complete) {
            return round((100/100) * $max);
    }
    if (strstr($low_subject,$complete)) {
            return round((70/100) * $max);
    }

    $word_matches = 0;
    $cnt_words    = count($words);
    for($i=0; $i < $cnt_words; $i++) {
            if (strstr(' '.$low_subject.' ',' '.$words[$i].' ')) {
                    $word_matches++;
            }
    }
    if ($word_matches > 0) {
            return round(($word_matches/$cnt_words) * (50/100) * $max);
    }

    return 0;
}

/**
 * Default callback for weighing a search result
 *
 * @param	assoc.array		a single result
 * @param	assoc.array		an array containts: complete=complete-search-text, words=array-of-search-words
 * @return	integer			the weight
 */
function weighSearchResult(&$result, $params) {
    $complete  = $params['complete'];
    $words     = $params['words'];

    $weight = 0;
    $text   = ',';

    $weight += $w = weighText($result['title'], $complete, $words, 150);
    $text   .= 'title=' . $w . ',';

    $weight += $w = weighText($result['subtitle'], $complete, $words, 120);
    $text   .= 'subtitle=' . $w . ',';

    $weight += $w = weighText($result['introduction'], $complete, $words, 80);
    $text   .= 'introduction=' . $w . ',';

    $weight += $w = weighText($result['body'], $complete, $words, 70);
    $text   .= 'body=' . $w . ',';
    
    $weight += $w = weighText($result['keywords'], $complete, $words, 120);
    $text   .= 'keywords=' . $w . ',';

    // Get the 'age' of the entry/page in seconds.
    $age = date("U") - date("U", strtotime($result['date']));
    
    // Subtract 1 weight point for every 300000 seconds. (about 2 point per week, with a maximum of 100 points)
    $agepenalty = min( floor($age / 300000), 100);
    $weight -= $agepenalty;
    $text   .= 'age=-' . $agepenalty . ',';

    $result['weight'] = $weight;
    $result['weight_explanation'] = substr($text,1,-1);
    
}

/**
 * Searches the index for words.
 *
 * @param array $str_a Contains the display text (index 0) and the search text (index 1).
 * @param string $only Selects if you want to search "entries" or "pages".
 * @param string $weblog Selects if you want to search inside a specific weblog.
 * @param string $category Selects if you want to search inside a specific category.
 * @return string The search results as a list (in HTML code).
 */
function searchIndex($str_a, $only='', $weblog='', $category='', &$searchresults) {
    global $index_file, $matches_entries, $matches_pages, $PIVOTX;

    require_once $PIVOTX['paths']['pivotx_path'].'includes/strip_punctuation.php';

    $str_a[1] = trim(strip_punctuation($str_a[1]));
    $str_a[1] = px_strtolower($str_a[1]);
    $words = preg_split('/\s+/', $str_a[1]);
    $orig_words = preg_split('/\s+/', trim($str_a[0]));
    // Ignoring empty strings from words
    foreach($words as $key=>$val) {
        if(trim($val)=="") {
            unset($words[$key]);
        }
    }

    // Load the indices for the $words, if we're using flat files..
    loadSearchIndex($words);

    $n = count($words);
    for($i=0; $i < $n; $i++) {
        // getWord sets $matches_entries and $matches_pages used below.
        $res = getWord($words[$i]);
        if($res) {
            $found_words[] = $orig_words[$i];
        }
    }

    $do_or_if_nand = getDefault($PIVOTX['config']->get('search_result_or_if_nand'),'1',true);

    // mix 'n match.. If the result set for 'AND' is empty, just lump
    // them together, so we have an 'OR'..
    if ((count($matches_entries) > 0) && ($only != 'pages')) {
        if (count($matches_entries)==1) {
            $result_entries = $matches_entries[0];
        } else if (count($matches_entries)==2) {
            list($word1,$word2) = $matches_entries;
            $result_entries = array_intersect($word1, $word2);
            if ($do_or_if_nand) {
                if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2); }
            }
        } else if (count($matches_entries)==3) {
            list($word1, $word2, $word3) = $matches_entries;
            $result_entries = array_intersect($word1, $word2, $word3);
            if ($do_or_if_nand) {
                if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2, $word3); }
            }
        } else if (count($matches_entries)>3) {
            list($word1, $word2, $word3, $word4) = $matches_entries;
            $result_entries = array_intersect($word1, $word2, $word3, $word4);
            if ($do_or_if_nand) {
                if(count($result_entries)==0) { $result_entries = array_merge($word1, $word2, $word3, $word4); }
            }
        }
    }

    // Do the same for the results from pages
    if ((count($matches_pages) > 0) && ($only != 'entries')) {
        if(count($matches_pages)==1) {
            $result_pages = $matches_pages[0];
        } else if(count($matches_pages)==2) {
            list($word1,$word2) = $matches_pages;
            $result_pages = array_intersect($word1, $word2);
            if ($do_or_if_nand) {
                if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2); }
            }
        } else if(count($matches_pages)==3) {
            list($word1, $word2, $word3) = $matches_pages;
            $result_pages = array_intersect($word1, $word2, $word3);
            if ($do_or_if_nand) {
                if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2, $word3); }
            }
        } else if(count($matches_pages)>3) {
            list($word1, $word2, $word3, $word4) = $matches_pages;
            $result_pages = array_intersect($word1, $word2, $word3, $word4);
            if(count($result_pages)==0) { $result_pages = array_merge($word1, $word2, $word3, $word4); }
        }

    }


    $title = __('Search Results');
    $format_top = getDefault($PIVOTX['config']->get('search_result_format_top'),
        "<h2>%search_title%</h2>\n%search_form%\n");
    $format_summary = getDefault($PIVOTX['config']->get('search_result_format_summary'),
        "<p>%search_summary%</p>\n");
    $format_start = getDefault($PIVOTX['config']->get('search_result_format_start'),
        "<ul id='search-results'>\n");
    $format_entry = getDefault($PIVOTX['config']->get('search_result_format_item'),
        "<li><a href='%link%'>%title%</a> (%percentage%%)<br /><span>%description%</span></li>\n");
    $format_end = getDefault($PIVOTX['config']->get('search_result_format_end'),
        "</ul>\n");

    $output = $format_top;

    // collect all the results
    $results = array();

    // First get the results for the pages..
    if(!empty($result_pages)) {
        rsort($result_pages);
        $result_pages = array_unique($result_pages);

        foreach($result_pages as $hit) {
            $page = $PIVOTX['pages']->getPage($hit);

            $page['link'] = makePagelink($page['uri']);
            $page['introduction'] = parse_intro_or_body($page['introduction'], false, $page['convert_lb']);
            $page['body'] = parse_intro_or_body($page['body'], false, $page['convert_lb']);

            // We make a 'description' to get a quick overview to see what the
            // found page is about..
            $page['description'] = strip_tags($page['introduction']." ".$page['body']);
            $page['description'] = trimText($page['description'], 200);

            if ($page['title']=="") {
                $page['title'] = substr(strip_tags($page['introduction']),0,50);
            }

	    $results[] = $page;
        }
    }


    // Then get the results for the entries..
    if(!empty($result_entries)) {
        rsort($result_entries);
        $result_entries = array_unique($result_entries);

        if (($category != '') && ($category != '_all_')) {
            $cats = explode(",",safeString($category));
            $cats = array_map("trim", $cats);
        }

        $allcats_raw = $PIVOTX['categories']->getCategories();
        $allcats = array();
        foreach($allcats_raw as $cat) {
            $allcats[$cat['display']] = $cat['name'];
        }

        foreach($result_entries as $hit) {
            if($PIVOTX['db']->entry_exists($hit)) {
                $entry = $PIVOTX['db']->read_entry($hit);
                $weblogs = $PIVOTX['weblogs']->getWeblogsWithCat($entry['category']);

                // Check category and weblog restrictions
                if (($category != '') && ($category != '_all_')) {
                    if (count(array_intersect($entry['category'], $cats)) == 0) {
                        debug_printr($entry['category']);
                        continue;
                    }
                } else if ($weblog != '') {
                    // Check if we are restricted to one specific weblog or all weblogs.
                    if (empty($weblogs)) {
                        // The entry's category isn't assigned to any weblog.
                        continue;
                    } else if ($weblog != '_all_') {
                        if (!in_array($weblog, $weblogs)) {
                            continue;
                        }
                    }
                }

                foreach ($weblogs as $key => $value) {
                    $weblogs[$key] = $PIVOTX['weblogs']->get($value, 'name');
                }
                $entry['weblogs'] = implode(', ',$weblogs);
                $entry['categories'] = implode(', ',array_keys(array_intersect($allcats,$entry['category'])));
                $entry['link'] = makeFileLink($entry['code'], "", "");
                $entry['introduction'] = parse_intro_or_body($entry['introduction'], false, $entry['convert_lb']);
                $entry['body'] = parse_intro_or_body($entry['body'], false, $entry['convert_lb']);

                // We make a 'description' to get a quick overview to see what the
                // found entry is about..
                $entry['description'] = strip_tags($entry['introduction']." ".$entry['body']);
                $entry['description'] = trimText($entry['description'], 200);

                if ($entry['title']=="") {
                    $entry['title'] = substr(strip_tags($entry['introduction']),0,50);
                }

                $results[] = $entry;
            }
        }
    }

    // Then weigh/sort the results and print them out
    if (!empty($results)) {
        $cnt    = count($results);
        $params = array ( 'complete' => $str_a[1], 'words' => $words);

        if ($PIVOTX['extensions']->haveHook('weigh_search_result')) {
            for($i=0; $i < $cnt; $i++) {
                $PIVOTX['extensions']->executeHook('weigh_search_result', $results[$i], $params);
            }
        } else {
            for($i=0; $i < $cnt; $i++) {
                weighSearchResult($results[$i], $params);
            }
        }

        usort($results, 'cmp_weights');

        $max_weight = $results[0]['weight'];
        
        if ($max_weight > 0) {
            for($i=0; $i < $cnt; $i++) {
                $percentage = round(($results[$i]['weight'] * 100) / $max_weight);
                $results[$i]['percentage'] = $percentage;
                debug($results[$i]['uid'].'/'.$results[$i]['title'].': ' . $results[$i]['weight'] . '/' . $results[$i]['weight_explanation']);
            }
        } else {
            for($i=0; $i < $cnt; $i++) {
                $results[$i]['percentage'] = 0;
            }
        }

        $count = count($results);
        $name = implode(', ',$found_words);
        $summary = str_replace('%name%', $name, __('Results for "%name%":') );
        $output .= str_replace('%search_summary%', $summary, $format_summary);
        $output .= $format_start;

        foreach($results as $hit) {
            $output .= formatEntry($hit,$format_entry);
        }
        $output .= "$format_end\n";
        // Removing formatting tags that aren't present in both entries and pages.
        $output = str_replace('%categories%', '', $output);
    }

    if (empty($result_pages) && empty($result_entries)) {
        if ($str_a[1] != "") {
            $count = 0;
            $name = $str_a[0];
            $summary = str_replace('%name%', $name, __('No matches found for "%name%". Try something else.')) ;
            $output .= str_replace('%search_summary%', $summary, $format_summary);

        }
    }

    $output = str_replace("%search_term%", $name, $output);
    $output = str_replace("%search_count%", $count, $output);
    $output = str_replace("%search_title%", $title, $output);
    
    // Set $searchresults to $results, so it will be available in the calling
    // function, since it was passed by reference.
    $searchresults = $results;
    
    return $output;

}


/**
 * Searches the index and returns the matching entries in an array.
 *
 * Used in the entries screen/overview search.
 *
 * @param string $str Text/words to search for
 * @return array
 */
function searchEntries ($str) {
    global $index_file, $matches_entries, $search_all, $PIVOTX;

    // Determine if all entries and pages should be search independent of status
    if (defined('PIVOTX_INADMIN')) {
        $search_all = true;
    } else {
        $search_all = false;
    }

    require_once $PIVOTX['paths']['pivotx_path'].'includes/strip_punctuation.php';

    $str = trim(strip_punctuation($str));
    $str = px_strtolower($str);
    $words = preg_split('/\s+/', $str);
    foreach($words as $key=>$val) {
        if(trim($val)=="") {
            unset($words[$key]);
        } else {
            $words[$key] = trim($val);
        }
    }

    // Load the indices for the $words, if we're using flat files..
    loadSearchIndex($words);

    foreach($words as $word) {
        $res = getWord($word);
        if($res) {
            $found_words[]=$word;
        }
    }

    $do_or_if_nand = getDefault($PIVOTX['config']->get('search_result_or_if_nand'),'1',true);

    // mix 'n match.. If the result set for 'AND' is empty, just lump
    // them together, so we have an 'OR'..
    if(count($matches_entries)==1) {
        $result = $matches_entries[0];
    } else if(count($matches_entries)==2) {
        list($word1,$word2) = $matches_entries;
        $result = array_intersect($word1, $word2);
        if ($do_or_if_nand) {
            if(count($result)==0) { $result = array_merge($word1, $word2); }
        }
    } else if(count($matches_entries)==3) {
        list($word1, $word2, $word3) = $matches_entries;
        $result = array_intersect($word1, $word2, $word3);
        if ($do_or_if_nand) {
            if(count($result)==0) { $result = array_merge($word1, $word2, $word3); }
        }
    } else if(count($matches_entries)>3) {
        list($word1, $word2, $word3, $word4) = $matches_entries;
        $result = array_intersect($word1, $word2, $word3, $word4);
        if ($do_or_if_nand) {
            if(count($result)==0) { $result = array_merge($word1, $word2, $word3, $word4); }
        }
    }

    if(isset($found_words) && (count($found_words)>0)) {

        foreach($result as $hit) {

            $entry = $PIVOTX['db']->read_entry($hit);
            if ($entry['title']=="") {
                $entry['title'] = trimText(strip_tags($entry['introduction']),50);
            }
            $entry['excerpt'] = makeExcerpt($entry['introduction']);
            unset($entry['comments']);
            unset($entry['introduction']);
            unset($entry['body']);
            $output[]=$entry;

        }

        return $output;
    } else {
        return array();
    }
}

/**
 * Loads (the needed part of) the search index for the given array of words.
 *
 * @param array $words
 * @return void
 */
function loadSearchIndex($words) {
    global $index_file, $PIVOTX;

    if ( ($PIVOTX['config']->get('db_model')=="flat") && (count($words)>0) ) {
        foreach ($words as $word) {
            $key = searchIndexKey($word);
            $file = $PIVOTX['paths']['db_path'].'search/'.$key.'.php';
            if (file_exists($file)) {
                $index_file[ $key ] = loadSerialize($file);
            }
        }
    }

}


/**
 * Search for a given word. It branches depending on the selected database model
 *
 * @param string $word Word to search for.
 * @return boolean false if not found, else true.
 */
function getWord($word) {
    global $PIVOTX;

    if ($PIVOTX['config']->get('db_model')=="flat") {
        return getWordFlat($word);
    } else {
        return getWordSql($word);
    }

}


/**
 * Checks if a word is part of the search index and if so sets the global variable
 * $matches_entries to the matching entry codes and the global variable
 * $matches_pages to the matching page codes.
 * 
 * @param string $word Word to search for.
 * @return boolean False if not found, else true.
 */
function getWordFlat($word) {
    global $search_all, $index_file, $PIVOTX, $matches_pages, $matches_entries;

    $found = false;

    $key = searchIndexKey($word);
    if(isset($index_file[ $key ][ $word ])) {
        $tmp_matches = explode("|", $index_file[ $key ][ $word ]);

        if (count($tmp_matches) == 0) {
            return FALSE;
        } else if ($search_all) {
            foreach($tmp_matches as $match) {
                $type = substr($match,0,1);
                $match = substr($match,1);
                if ($type == 'e') {
                    $valid_matches_entries[] = $match;
                } elseif ($type == 'p') {
                    $valid_matches_pages[] = $match;
                }
            }
        } else {
            foreach($tmp_matches as $match) {
                $type = substr($match,0,1);
                $match = substr($match,1);
                // Handling entries
                if ($type == 'e') {
                    $PIVOTX['db']->read_entry($match);
                    if ($PIVOTX['db']->entry['status'] == "publish") { 
                        $valid_matches_entries[] = $match;
                    }
                } else if ($type == 'p') {
                    $page = $PIVOTX['pages']->getPage($match);
                    if ($page['status'] == "publish") {  
                        $valid_matches_pages[] = $match;
                    }
                } else {
                    debug('Unknown type in search index - this can\'t happen ...');
                }
            }
        }
        if (count($valid_matches_pages)>0) {
            $matches_pages[] = $valid_matches_pages;
            $found = true;
        }
        if (count($valid_matches_entries)>0) {
            $matches_entries[] = $valid_matches_entries;
            $found = true;
        }
    }
    return $found;
}


/**
 * Checks if a word is part of the search index and if so sets the global variable
 * $matches_entries to the matching entry codes and the global variable $matches_pages 
 * to the matching page codes.
 *
 * If this function returns no results when they are expected, keep in mind that:
 * # Words called stopwords are ignored, you can specify your own stopwords, but
 *   default words include the, have, some - see default stopwords list.
 * # If a word is present in more than 50% of the rows it will have a weight of
 *   zero. This has advantages on large datasets, but can make testing difficult on
 *   small ones.
 * http://www.petefreitag.com/item/477.cfm
 *
 * @param string $word Word to search for.
 * @return boolean false if not found, else true.
 */
function getWordSql($word) {
    global $PIVOTX, $matches_entries, $matches_pages, $search_all;

    $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
    $pagestable = safeString($PIVOTX['config']->get('db_prefix')."pages", true);

    // Set up DB connection
    $database = new sql('mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );

    // Make sure we don't inject unwanted stuff..
    $word = $database->quote($word);

    $query = "SELECT uid FROM $entriestable WHERE MATCH(title, subtitle, introduction, body, keywords) AGAINST ($word);";
    if (!$search_all) {
        $query = str_replace(";"," AND status='publish';", $query);
    } 

    $database->query($query);

    $do_or_if_nand = getDefault($PIVOTX['config']->get('search_result_or_if_nand'),'1',true);

    $result_entries = $database->fetch_all_rows();
    if (!empty($result_entries)) {
        $matches_entries[] = makeValuepairs($result_entries, '', 'uid');
    }
    else {
        if (!$do_or_if_nand) {
            $matches_entries[] = array();
        }
    }

    $query = "SELECT uid FROM $pagestable WHERE MATCH(title, subtitle, introduction, body, keywords) AGAINST ($word) ;";
    if (!$search_all) {
        $query = str_replace(";"," AND status='publish';", $query);
    } 

    $database->query($query);

    $result_pages = $database->fetch_all_rows();
    if(!empty($result_pages)) {
        $matches_pages[] = makeValuepairs($result_pages, '', 'uid');
    }
    else {
        if (!$do_or_if_nand) {
            $matches_pages[] = array();
        }
    }

    if (empty($result_entries) && empty($result_pages)) {
        // If we get here, we have no results. See if we can put a reasonable 
        // explanation in the debug logs.
        $database->query("SELECT COUNT('uid') AS count FROM $entriestable");
        $countentries = $database->fetch_row();
        $database->query("SELECT COUNT('uid') AS count FROM $pagestable");
        $countpages = $database->fetch_row();
        
        if ( $countpages<5 || $countentries<5 ) {
            debug("Your search provided no results. Since you have few entries/pages, it ".
                "could be that the search returned nothing, because there are too few records".
                "to search, or that your search term is present in 50% or more of the pages/entries");
        } else if (strlen($word)<6) {
            // we use '6' for length, because 'quotes' were added, so it triggers if the
            // original $word was shorter than 4 characters.
            debug("Your search provided no results, probably because the search term is too ".
                "short, causing MySQL to ignore it.");            
        } else {
            debug("Your search provided no results. Either your term is really not present, ".
                "or your search term is in the list of MySQL stop words, which are ignored by default");            
        }
        
    }

    return true;
}


/**
 * Returns the search form and (possibly) the search results.
 *
 * @uses searchIndex
 * @return string
 */
function searchResult(&$searchresults) {
    global $PIVOTX;
    
    static $counter;
    $counter++;
    
    // search is an array
    // 0 -> for display
    // 1 -> for search
    if (!empty($_POST['q'])) {
        $request_method = 'post';
        $search_str = trim($_POST['q']);
    } else {
        $request_method = 'get';
        $search_str = trim($_GET['q']);
    }

    $search_a[0] = htmlspecialchars($search_str); // Avoiding XSS attacks in display string
    $search_a[1] = $search_str;

    $search_formname    = __('Search for words used in entries and pages on this website') ;
    $search_fldname     = __('Enter the word[s] to search for here:') ;
    $search_idname      = 'search-'.$counter ;
    $search_placeholder = __('Enter search terms') ;

    $search_url = makeSearchLink();

    // build up accessible form, keeping track of current weblog (if multiple)
    $form = '<form method="'.$request_method.'" action="'.$search_url.'" class="pivotx-search-result">'."\n" ;
    $form .= '<fieldset><legend>'.$search_formname.'</legend>'."\n" ;
    $form .= '<label for="'.$search_idname.'">'.$search_fldname.'</label>'."\n" ;
    $form .= '<input id="'.$search_idname.'" type="text" name="q" class="result-searchbox" value="';
    $form .= $search_a[0].'" onfocus="this.select();" />'."\n" ;
    $form .= '<input type="submit" class="result-searchbutton" value="'.__('Search!').'" />'."\n" ;

    // If a weblog as been explicitly selected or we are not on a page, set the weblog.
    $weblog = trim( getDefault($_GET['w'], $_POST['w']));
    if ( !empty($weblog) || ($PIVOTX['parser']->modifier['pagetype'] != "page")) { 
        $weblog = getDefault($weblog, $PIVOTX['weblogs']->getCurrent());
        $form .= '<input type="hidden" name="w" value="'.$weblog.'" />'."\n";
    }

    // If a category as been explicitly selected, set the category.
    $category = trim( getDefault($_GET['c'], $_POST['c']));
    if ( !empty($category)) { 
        $form .= '<input type="hidden" name="c" value="'.$category.'" />'."\n";
    }

    // Limit the search to entries or pages
    $only = trim( getDefault($_GET['only'], $_POST['only']));
    if (!empty($only)) { 
        $checked = array();
        foreach (array('pages','entries','both') as $var) {
            if ($only == $var) {
                $checked[$var] = 'checked';
            }
        }
        $form .= '<input type="radio" name="only" value="entries" ' . $checked['entries'] . '/>' . __('Entries') . "\n";
        $form .= '<input type="radio" name="only" value="pages" ' . $checked['pages'] . '/>' . __('Pages') . "\n";
        $form .= '<input type="radio" name="only" value="both" ' . $checked['both'] . '/>' . __('Both') . "\n";
    }

    $form .= '</fieldset></form>'."\n" ;
    // add search results - if any
    $output = searchIndex( $search_a, $only, $weblog, $category, $searchresults ) ;
    $output = str_replace("%search_form%", $form, $output);

    return $output;
}



?>
