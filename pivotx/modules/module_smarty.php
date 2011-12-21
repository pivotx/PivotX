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

// Lamer protection
$currentfile = basename(__FILE__);
require_once(dirname(dirname(__FILE__))."/lamer_protection.php");

// Include Smarty libraries
require_once($pivotx_path."modules/smarty/Smarty.class.php");


/**
 * For now we should keep all tags in one file, less clutter.
 *
 */

global $PIVOTX;


/**
 * PivotX specific for Smarty
 */
class PivotxSmarty extends Smarty {
    // by default a fetch won't automatically rewrite
    protected $allow_rewrite = false;

    /**
     * PivotX constructor, setup the defaults for Pivot
     */
    public function __construct() {
        global $pivotx_path;

        parent::__construct();

        if(defined('PIVOTX_INWEBLOG')) {
            $this->template_dir   = $pivotx_path.'templates/';
        } else {
            $this->template_dir   = $pivotx_path.'templates_internal/';
        }

        $this->secure_dir = array(
            $this->template_dir,
            $pivotx_path.'extensions/'
        ); 

        // When we get here, the $config object is not yet defined. So, we're going
        // to set $smarty to just do everything, and perhaps modify settings as caching,
        // compiling and directories later on, before it actually starts parsing.
        $this->caching = false;
        $this->force_compile = false; // Note: this is set in initializePivotX()

        $this->compile_dir    = $pivotx_path.'db/cache/';
        //$this->config_dir   = $pivotx_path.'includes/smarty/configs/';
        $this->cache_dir      = $pivotx_path.'db/cache/';
        $this->left_delimiter = "[[";
        $this->right_delimiter = "]]";

        $this->debugging = false;
        $this->cache_lifetime = 240; // 3 minutes.  

        /**
         * Register the resource name "db". This allows recursive parsing of templates..
         */
        $this->register_resource("db", array("dbGetTemplate", "dbGetTimestamp", "dbGetSecure", "dbGetTrusted"));

        /**
         * Set our own Smarty Cache Handlers. This allows us to:
         * - Have better control over when we purge items from the cache
         * - Execute our after_parse hooks, and cache the results
         */
        $this->cache_handler_func = 'pivotxCacheHandler'; 

        // Smarty functions..
        $this->register_function('archive_list', 'smarty_archive_list');
        $this->register_function('atombutton', 'smarty_atombutton');
        $this->register_function('backtrace', 'smarty_backtrace');
        $this->register_function('body', 'smarty_body');
        $this->register_function('cached_include', 'smarty_cached_include');
        $this->register_function('category', 'smarty_category');
        $this->register_function('category_link', 'smarty_category_link');
        $this->register_function('category_list', 'smarty_category_list');
        $this->register_function('chaptername', 'smarty_chaptername');
        $this->register_function('charset', 'smarty_charset');
        $this->register_function('code', 'smarty_uid'); 
        $this->register_function('commcount', 'smarty_commcount');
        $this->register_function('commentform', 'smarty_commentform');
        $this->register_function('commentlink', 'smarty_commentlink');
        $this->register_function('content', 'smarty_content');
        $this->register_function('count', 'smarty_count'); 
        $this->register_function('date', 'smarty_date');
        $this->register_function('download', 'smarty_download');
        $this->register_function('editlink', 'smarty_editlink');
        $this->register_function('emotpopup', 'smarty_emotpopup');
        $this->register_function('explode', 'smarty_explode');
        $this->register_function('extensions_dir', 'smarty_extensions_dir');
        $this->register_function('extensions_url', 'smarty_extensions_url');
        $this->register_function('filedescription', 'smarty_filedescription');
        $this->register_function('getentry', 'smarty_getentry');
        $this->register_function('getpagelist', 'smarty_getpagelist');
        $this->register_function('getpage', 'smarty_getpage');
        $this->register_function('home', 'smarty_home');
        $this->register_function('hook', 'smarty_hook');
        $this->register_function('id_anchor', 'smarty_id_anchor');
        $this->register_function('image', 'smarty_image');
        $this->register_function('implode', 'smarty_implode');
        $this->register_function('introduction', 'smarty_introduction');
        $this->register_function('lang', 'smarty_lang');
        $this->register_function('latest_comments', 'smarty_latest_comments');
        $this->register_function('link', 'smarty_link');
        $this->register_function('link_list', 'smarty_link_list');
        $this->register_function('live_title', 'smarty_live_title');
        $this->register_function('log_dir', 'smarty_log_dir');
        $this->register_function('log_url', 'smarty_log_url');
        $this->register_function('message', 'smarty_message');
        $this->register_function('moderate_message', 'smarty_moderate_message');
        $this->register_function('more', 'smarty_more');
        $this->register_function('nextentry', 'smarty_nextentry');
        $this->register_function('nextpage', 'smarty_nextpage');
        $this->register_function('pagelist', 'smarty_pagelist');
        $this->register_function('paging', 'smarty_paging');
        $this->register_function('permalink', 'smarty_permalink');
        $this->register_function('pivotxbutton', 'smarty_pivotxbutton');
        $this->register_function('pivotx_dir', 'smarty_pivotx_dir');
        $this->register_function('pivotx_path', 'smarty_pivotx_path');
        $this->register_function('pivotx_url', 'smarty_pivotx_url');
        $this->register_function('popup', 'smarty_popup');
        $this->register_function('previousentry', 'smarty_previousentry');
        $this->register_function('previouspage', 'smarty_previouspage');
        $this->register_function('print_r', 'smarty_print_r');
        $this->register_function('register_as_visitor_link', 'smarty_register_as_visitor_link');
        $this->register_function('registered', 'smarty_registered');
        $this->register_function('rssbutton', 'smarty_rssbutton');
        $this->register_function('remember', 'smarty_remember');
        $this->register_function('resetpage', 'smarty_resetpage');
        $this->register_function('search', 'smarty_search');
        $this->register_function('searchheading', 'smarty_searchheading');
        $this->register_function('self', 'smarty_self');
        $this->register_function('sitename', 'smarty_sitename');
        $this->register_function('sitedescription', 'smarty_sitedescription');
        $this->register_function('spamquiz', 'smarty_spamquiz');
        $this->register_function('subtitle', 'smarty_subtitle');
        $this->register_function('tagcloud', 'smarty_tagcloud');
        $this->register_function('tags', 'smarty_tags');
        $this->register_function('tt', 'smarty_tt');
        $this->register_function('template_dir', 'smarty_template_dir');
        $this->register_function('textilepopup', 'smarty_textilepopup');
        $this->register_function('title', 'smarty_title');
        $this->register_function('trackbacklink', 'smarty_trackbacklink');
        $this->register_function('tracklink', 'smarty_tracklink');
        $this->register_function('trackcount', 'smarty_trackcount');
        $this->register_function('tracknames', 'smarty_tracknames');
        $this->register_function('uid', 'smarty_uid'); 
        $this->register_function('upload_dir', 'smarty_upload_dir');
        $this->register_function('user', 'smarty_user');
        $this->register_function('user_list', 'smarty_user_list');
        $this->register_function('via', 'smarty_via');
        $this->register_function('weblog_list', 'smarty_weblog_list');
        $this->register_function('webloghome', 'smarty_log_dir');
        $this->register_function('weblogid', 'smarty_weblogid');
        $this->register_function('weblogsubtitle', 'smarty_weblogsubtitle');
        $this->register_function('weblogtitle', 'smarty_weblogtitle');
        $this->register_function('widgets', 'smarty_widgets');
        $this->register_function('yesno', 'smarty_yesno');

        // Block functions..
        $this->register_block('nocache', 'smarty_block_nocache', false);
        $this->register_block('button', 'smarty_button');
        $this->register_block('comments', 'smarty_comments');
        $this->register_block('searchresults', 'smarty_searchresults');        
        $this->register_block('trackbacks', 'smarty_trackbacks');
        $this->register_block('feed', 'smarty_feed');
        $this->register_block('subweblog', 'smarty_weblog');
        $this->register_block('weblog', 'smarty_weblog');

        // Some tags that are deprecated, but kept around for backwards compatibility
        $this->register_function('entrylink', 'smarty_entrylink'); 
        $this->register_function('singlepermalink', 'smarty_link'); 
        $this->register_function('weblogname', 'smarty_weblogtitle'); 
        $this->register_function('last_comments', 'smarty_latest_comments');
        $this->register_function('fulldate', 'smarty_date');

        // Modifiers
        $this->register_modifier('parse', 'smarty_parse');

        // Admin backend functions
        $this->register_function('adminentrylist', 'smarty_adminentrylist');


        $classes = array ( 'SmartyUpload' );
        foreach($classes as $classname) {
            $methods = get_class_methods($classname);
            foreach($methods as $method) {
                if (substr($method,0,7) == 'smarty_') {
                    $this->register_function(substr($method,7), array($classname,$method));
                }
            }
        }
    }

    /**
     * Rewrite after parent fetch
     */
    public function allowRewriteHtml() {
        $this->allow_rewrite = true;
    }

    /**
     * Don't rewrite after parent fetch
     */
    public function disallowRewriteHtml() {
        $this->allow_rewrite = false;
    }

    /**
     * Fetch, rewrite and display a template
     */
    public function display($resource_name, $cache_id=null, $compile_id=null) {
        $old_rewrite         = $this->allow_rewrite;
        $this->allow_rewrite = true;

        echo $this->fetch($resource_name,$cache_id,$compile_id);

        $this->allow_rewrite = $old_rewrite;
    }

    /**
     * Fetch a template and optionally rewrite
     */
    public function fetch($resource_name, $cache_id=null, $compile_id=null, $display=false) {
        $html = parent::fetch($resource_name,$cache_id,$compile_id,$display);

        if ($this->allow_rewrite) {
            $os   = OutputSystem::instance();
            $html = $os->rewriteHtml($html);
        }

        return $html;
    }
}


$PIVOTX['template'] = new PivotxSmarty;


/**
 * Inserts a linked list to the archives.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_archive_list($params, &$smarty) {
    global $Archive_array, $PIVOTX;

    $params = cleanParams($params);

    $Current_weblog = getDefault($params['weblog'], $PIVOTX['weblogs']->getCurrent());

    $format = getDefault($params['format'], 
        "<a href=\"%url%\">%st_day% %st_monname% - %en_day% %en_monname% %st_year% </a><br />");
    $unit = getDefault($params['unit'], "month");
    $separator = getDefault($params['separator'], "");

    // if not yet done, load / make the array of archive filenames (together
    // with at least one date)
    if (!isset($Archive_array)) { makeArchiveArray(false, $unit); }

    // If we use 'isactive', set up the $active_arc and $isactive vars.
    if (!empty($params['isactive'])) {
        $active_arc = $pagetype = $PIVOTX['parser']->modifier['archive'];
        $isactive = $params['isactive'];
    } else {
        $isactive = "";
    }


    $output = array();

    if( is_array( $Archive_array[$Current_weblog] )) {
        
        // maybe flip and reverse it.
        if($params['order'] == 'descending' || $params['order'] == 'desc') {
            $mylist = $Archive_array[$Current_weblog];
        } else {
            $mylist = array_reverse($Archive_array[$Current_weblog]);
        }

        // Iterate over the list, formatting output as we go.
        $counter = 0;
        foreach($mylist as $date) {
            $counter++;
            $filelink = makeArchiveLink($date, $unit, $params['weblog']);

            // Check if the current archive is the 'active' one.
            if (!empty($isactive) && (makeArchiveName($date,'',$unit)==$active_arc)) {
                $thisactive = $isactive;
            } else {
                $thisactive = "";
            }
 
            // fix the rest of the string..
            list($start_date, $stop_date) = getDateRange($date, $unit);
            $this_output = formatDateRange($start_date, $stop_date, $format);

            $this_output = str_replace("%counter%" , $counter, $this_output);
            $this_output = str_replace("%url%" , $filelink, $this_output);
            $this_output = str_replace("%active%" , $thisactive, $this_output);

            $output[] = "\n".$this_output;
        }
    }

    return implode($separator, $output);

}


/**
 * Insert a button with a link to the Atom XML feed.
 *
 * @return string
 */
function smarty_atombutton() {
    global $PIVOTX ;

    // if we've disabled the Atom feed for this weblog, return nothing.
    if ($PIVOTX['weblogs']->get('', 'rss')!=1) {
        return "";
    }

    // else we continue as usual..

    $filename = makeFeedLink("atom");

    $image    = $PIVOTX['paths']['pivotx_url'].'pics/atombutton.png' ;
    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/atombutton.png' ) ;
    $alttext  = __('XML: Atom Feed') ;

    $output   = '<a href="'.$filename.'" title="'.$alttext.'" rel="nofollow" class="badge">';
    $output  .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'"' ;
    $output  .= ' alt="'.$alttext.'" class="badge" longdesc="'.$filename.'" /></a>' ;

    return $output;
}


/**
 * Print a backtrace of called functions from smarty templates
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_backtrace($params, &$smarty) {
    global $PIVOTX;

    if(!function_exists('debug_backtrace')) {
        return 'function debug_backtrace does not exist.';
    }

    $MAXSTRLEN = 30;

    ob_start();

    $my_trace = array_reverse(debug_backtrace());

    foreach($my_trace as $t)    {
        echo '<pre>&raquo; ';
        if(isset($t['file'])) {
            $line = basename(dirname($t['file'])). '/' .basename($t['file']) .", line " . $t['line'];
            printf("%-30s", $line);
        } else {
            // if file was not set, I assumed the functioncall
            // was from PHP compiled source (ie XML-callbacks).
            $line = '<PHP inner-code>';
            printf("%-30s", $line);
        }

        echo "\n    - ";

        if(isset($t['class'])) echo $t['class'] . $t['type'];

        echo $t['function'];

        if (isset($t['args']) && sizeof($t['args']) > 0) {
            $args= array();
            foreach($t['args'] as $arg) {
                if (is_null($arg)) $args[] = 'null';
                else if (is_array($arg)) $args[] = 'Array['.sizeof($arg).']';
                else if (is_object($arg)) $args[] = 'Object:'.get_class($arg);
                else if (is_bool($arg)) $args[] = $arg ? 'TRUE' : 'FALSE';
                else if ( (is_int($arg))||(is_float($arg)) ) $args[] = $arg;
                else {
                    $arg = (string) @$arg;
                    $str = htmlspecialchars(substr($arg,0,$MAXSTRLEN));
                    $str = str_replace("\n", "", str_replace("\r", "", $str));
                    $str = preg_replace("/(\s)/i", " ", $str);
                    if (strlen($arg) > $MAXSTRLEN) $str .= '~';

                    $args[] = "'".$str."'";
                }
            }

            echo '( ' . implode(" , ", $args) .  ' )';
        }   else {
            echo '()';
        }

        echo "\r\n</pre>\n";
    }

    $output = ob_get_contents();
    ob_end_clean();

    echo "\n<div class='debug-backtrace'>\n";
    echo $output;
    echo "\n<br />--</div>&nbsp;\n";
}


/**
 * Smarty tag for [[ body ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_body($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    // Don't add an anchor if 'noanchor' is set or if body contains so little 
    // content that the [[more]] tag will not insert a link to the body.
    if (!$params['noanchor'] && (strlen($vars['body'])>5)) {
 
        $anchorname = getDefault($params['anchorname'], 'body-anchor');
        
        $body = sprintf('<a id="%s"></a>', $anchorname);
    }

    $body .= parse_intro_or_body($vars['body'], $params['strip'], $vars['convert_lb']);


    return $body;

}


/**
 * Creates a button with the given text.
 *
 * @param array $params
 * @param string $text
 * @param object $smarty
 * @return string
 */
function smarty_button($params, $text, &$smarty) {

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }
    
    $params = cleanParams($params);

    if ($params['icon']!="") {
        $img = "<img src=\"./pics/".$params['icon']."\" alt=\"\" />\n";
    } else {
        $img = "";
    }


    if ($params['tabindex']!="") {
        $tabindex = " tabindex=\"".$params['tabindex']."\"";
    } else {
        $tabindex = "";
    }

    if ($text!="") {
        $label = $text;
    } else {
        $label = __("ok");
    }

    if ($params['link']!="") {
        $link = str_replace("&", "&amp;", $params['link']);
    } else {
        $link = "#";
    }

    if ($params['class']!="") {
        $class = " class=\"".$params['class']."\"";
    } else {
        $class = "";
    }



    $output = sprintf("
        <a href=\"%s\"%s%s>
            %s%s
        </a>",
        $link,
        $tabindex,
        $class,
        $img,
        $label
    );

    return $output;

}


/**
 * Smarty tag for [[cached_include]].
 *
 * Works just like [[include]], but is able to cache the result for the time 'cache' (minutes).
 *
 * Parameters 'assign' and 'file' work just like with include.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_cached_include($params, &$smarty) {
	global $PIVOTX;

	$cache_dir = $PIVOTX['paths']['db_path'] . 'cache/';
	$jitter    = '';

    $cache_age = max(1, getDefault($params['cache'], 10) ) * 60;
    
	if (isset($params['jitter'])) {
		$jitter = '-' . preg_replace('|[^a-zA-Z0-9-_]|','_',$params['jitter']);
	}

	$template_file = $params['file'];
	$template_fid  = preg_replace('|[^a-zA-Z0-9-_]|','_',$template_file);
	$file_mask     = $cache_dir . 'ci-%d-%s%s.php';

	$time_index = floor(time() / $cache_age);

	$cache_file = sprintf($file_mask,$time_index,$template_fid,$jitter);

	if ( !$PIVOTX['config']->get('no_cached_include') && file_exists($cache_file) ) {
		// debug('cached_include: from cache ' . basename($cache_file) );

		// Set the template_log..
		if (empty($GLOBALS['template_log'])) { $GLOBALS['template_log'] = array(); }
		$GLOBALS['template_log'][] = '- ' . $params['file'] . " <em>(from cache, jitter: '$jitter')</em>";

		$html = file_get_contents($cache_file);
	}
	else {
		$old_tpl_vars = $smarty->_tpl_vars;
		$old_outputf  = $smarty->_plugins['outputfilter'];

		$smarty->_plugins['outputfilter'] = array();
		
		foreach($params as $key => $value) {
			if (!in_array($key,array('file','assign','cache','jitter'))) {
				$smarty->assign($key,$value);
			}
		}

		// debug('cached_include: to cache ' . basename($cache_file) );

		// Set the template_log..
		if (empty($GLOBALS['template_log'])) { $GLOBALS['template_log'] = array(); }
		$GLOBALS['template_log'][] = '- ' . $params['file'] . " <em>(compiled to cache, jitter: '$jitter')</em>";

		$html = $smarty->fetch($template_file);

		$smarty->_tpl_vars = $old_tpl_vars;
		$smarty->_plugins['outputfilter'] = $old_outputf;

		$fp = @fopen($cache_file,'w');
		if ($fp) {
			fputs($fp,$html);
			fclose($fp);
		}
        
		// Delete the previous cache file. Files left behind will be cleaned up by the scheduler.
		$previous_cache_file = sprintf($file_mask,$time_index-1,$template_fid,$jitter);
		if (file_exists($previous_cache_file)) {
		    @unlink($previous_cache_file);
		}        
        
	}

	if (isset($params['assign'])) {
		$smarty->assign($params['assign'],$html);
		$html = '';
	}

	return $html;
}



/**
 * List the names of the current entry's categories or just the names 
 * of the selected categories. Optionally links them to the matching 
 * pages with entries in that category. 
 *
 * Entries from the current entry is selected unless the 'name' parameter 
 * is set. You can use the 'ignore' parameter and the 'only' parameter to 
 * restrict the categories listed.  'name', 'ignore' and 'only' takes a comma 
 * separated list of category names. Finally, you can specify the separator
 * between the categories with the 'sep' parameter.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $title = getDefault($params['title'], "");

    // Get the internal name of the categories. The parameters
    // "ignore" and "only" is ignored if "name" is set.
    if (!empty($params['name'])) {

        if (!is_array($params['name'])) {
            $cats = explode(",", $params['name']);
            $cats = array_map("trim", $cats);
        } else {
            $cats = $params['name'];
        }
        $only = array();
        $ignore = array();

    } else {

        $vars = $smarty->get_template_vars();
        $entry = $vars['entry'];
        $cats = $entry['category'];

        // See if we need to ignore some categories..
        if (!empty($params['ignore'])) {
            $ignore = explode(",", $params['ignore']);
            $ignore = array_map("trim", $ignore);    
        } else {
            $ignore = array();
        }

        // See if we need to list just some of the categories..
        if (!empty($params['only'])) {
            $only = explode(",", $params['only']);
            $only = array_map("trim", $only);
        } else {
            $only = array();
        }    
    }

    if (is_array($cats)) {

        $output = array();
        $thistitle = "";

        foreach($cats as $key=>$value) {
            
            $thiscat = $PIVOTX['categories']->getCategory($value);

            // Skip it, if it's in $ignore..
            if (in_array($thiscat['name'], $ignore) || in_array($thiscat['display'], $ignore) ) {
                continue;
            }
            
            // Skip it, if it's not in $only
            if (!empty($only) && !(in_array($thiscat['name'], $only) || in_array($thiscat['display'], $only))) {
                continue;
            }
            
            // Skip it, if $thiscat['display'] is empty (likely, it has since been deleted)
            if (empty($thiscat['display'])){
                continue;
            }
            
            if (!$params['link']) {                
                $output[] = $thiscat['display'];
            } else {

                if (!empty($title)) {
                    $thistitle = 'title="' . htmlspecialchars(formatEntry($thiscat, $title), ENT_QUOTES, "utf-8") . '"';
                }

                $output[] = sprintf("<a href='%s' %s>%s</a>",
                    makeCategoryLink($value, $params['weblog']),
                    $thistitle,
                    htmlspecialchars($thiscat['display'], ENT_QUOTES, "utf-8"));
            }
            
        }
 
        $sep = getDefault($params['sep'], ", ");

        return implode($sep, $output);
    } else {
        return '';
    }
}



/**
 * Create a link to a given category.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category_link($params, &$smarty) {
    global $PIVOTX;

    $params['category'] = $params['name'];
    unset($params['name']);
    return _smarty_link_category($params, $smarty);
}


/**
 * Inserts a list of links to the different categories in the current weblog.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_category_list($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    // Set the categories. Either through the 'category' parameter, or through the current weblog.
    if (!empty($params['category']) && $params['category']=="*") {
        $mycats = $PIVOTX['categories']->getCategorynames();
    } else if (!empty($params['category'])) {
        $mycats = explode(",",safeString($params['category']));
        $mycats = array_map("trim", $cats); 
    } else {
        $mycats = $PIVOTX['weblogs']->getCategories($params['weblog']);
    }

    $modifier = $PIVOTX['parser']->modifier;
    $modifiercats = explode(",", $modifier['category']);
        
    $format = getDefault($params['format'], "<a href=\"%url%\">%display%</a><br />");

    $output = '';

    // See if we need to ignore some categories..
    if (!empty($params['ignore'])) {
        $ignore = explode(",", $params['ignore']);
        $ignore = array_map("trim", $ignore);    
    } else {
        $ignore = array();
    }
    
    // See if we need to list just some of the categories..
    if (!empty($params['only'])) {
        $only = explode(",", $params['only']);
        $only = array_map("trim", $only);    
    } else {
        $only = array();
    }

    if( is_array( $mycats )) {

        // Iterate over the list, formatting output as we go.
        foreach($mycats as $cat) {

            $catinfo = $PIVOTX['categories']->getCategory($cat);

            // Skip categories that no longer exists.
            if (count($catinfo) == 0) {
                continue;
            }

            // Skip categories that fall outside 'start' or 'end', if they are set..
            if (isset($params['start']) && ($catinfo['order'] <= $params['start']) ) {
                continue;
            }
            
            if (isset($params['end']) && ($catinfo['order'] > $params['end']) ) {
                continue;
            }
            
            $filelink = makeCategoryLink($cat, $params['weblog']);

            // Skip it, if it's in $ignore, or if it's marked 'hidden'
            if (in_array($catinfo['name'], $ignore) || in_array($catinfo['display'], $ignore) || ($catinfo['hidden']==1) ) {
                continue;
            }
			
            // Skip it, if it's not in $only
            if (!empty($only) && !(in_array($catinfo['name'], $only) || in_array($catinfo['display'], $only))) {
                continue;
            }

            // Check if it's the active one..
            if (in_array($catinfo['name'], $modifiercats)) {
                $active = $params['isactive'];
            } else {
                $active = "";
            }

            // fix the rest of the string..
            $this_output = str_replace("%url%" , $filelink, $format);
            $this_output = str_replace("%name%" , $catinfo['name'], $this_output);
            $this_output = str_replace("%display%" , $catinfo['display'], $this_output);
            $this_output = str_replace("%internal%" , $catinfo['name'], $this_output);
            $this_output = str_replace("%active%" , $active, $this_output);
            if (strpos($format, '%count%')>0) {
               $this_output = str_replace("%count%", $PIVOTX['db']->get_entries_count(
                   array('cats' => $catinfo['name'], 'status' => 'publish')), $this_output);
            }      
            
            $output .= "\n".$this_output;
        }
    }
    
    return stripslashes($output);

}





/**
 * Outputs the current charset of the weblog / page
 *
 * This function always return 'utf-8' since we
 * chosen that as the only charset.
 *
 * @return string
 */
function smarty_charset() {
    return "utf-8";
}

/**
 * Outputs a text string with the number of comments for the current entry 
 * whether it is in a subweblog or on an entry page.
 *
 * @return string
 */
function smarty_commcount($params, &$smarty) {
    global $temp_comment, $PIVOTX;

    $params = cleanParams($params);
    $vars = $smarty->get_template_vars();

    if (isset($vars['entry'])) {
        $entry = $vars['entry'];
    } else {
        debug("The commcount tag only works for entries");
        return "";
    }
    
    $commcount = getDefault($entry['commcount'], $entry['comment_count'], true);

    // if we have a $temp_comment, we have to add one
    if (!empty($temp_comment) && !isset($temp_comment['duplicate'])) { 
        $commcount++; 
    }

    $text0 = getDefault($params['text0'], __("No comments"), true);
    $text1 = getDefault($params['text1'], __("One comment"));
    $textmore = getDefault($params['textmore'], __("%num% comments"));

    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) &&  ($entry['allow_comments'] == 0) )  {
        return "";
    }

    $output_arr = array($text0, $text1, $textmore);
    $output = $output_arr[min(2,$commcount)];

    $output = str_replace("%num%", $PIVOTX['locale']->getNumber($commcount), $output);
    $output = str_replace("%n%", $commcount, $output);

    return $output;
}



/**
 * Displays a commentform if commenting is allowed and remote IP isn't blocked,
 * as long as we are in a subweblog or on an entry page.
 *
 * @param string $template
 * @return string
 */
function smarty_commentform($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();

    if (isset($vars['entry'])) {
        $entry = $vars['entry'];
    } else {
        debug("The commentform tag only works for entries");
        return "";
    }
     // This tag is only allowed on entrypages..
    // if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = cleanParams($params);

    $template = getDefault($params['template'], "_sub_commentform.html");

    // Initialise the IP blocklist.
    $blocklist = new IPBlock();

    // check for entry's allow_comments, blocked IP address or subweblog comments..
    if ( (isset($entry['allow_comments']) && ($entry['allow_comments']==0)) || 
            ($blocklist->isBlocked($_SERVER['REMOTE_ADDR']))  ) {

        // if allow_comments set to 0, or current visitor has his ip blocked, then
        // don't show the commentform. Instead we display a vague notice. If we
        // would output nothing, I expect we'll get a lot of people who accidentally
        // block themselves, and will have a hard time debugging the issue.
        $output = "<!-- No comments for you! -->";

    } else {
        // else show it
        if(file_exists($PIVOTX['paths']['templates_path'].$template)) {

            $output = parse_intro_or_body("[[include file='$template' ]]");

            if ($PIVOTX['config']->get('hashcash')==1) {
                $output = add_hashcash($output);
            }

        } else {

            debug("PivotX couldn't include '$template'.");
            $output = "<!-- PivotX couldn't include '$template' -->";

        }

    }

    return $output;

}


function smarty_commentlink($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();

    $link = makeFileLink($vars['entry'], $params['weblog']);

    $commcount=intval($vars['commcount']);

    $text0 = getDefault($params['text0'], __("No comments"), true);
    $text1 = getDefault($params['text1'], __("One comment"));
    $textmore = getDefault($params['textmore'], __("%num% comments"));

    // special case: If comments are disabled, and there are no
    // comments, just return an empty string..
    if ( ($commcount == 0) &&  ($vars['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$commcount)];
    $commcount = $PIVOTX['locale']->getNumber($commcount);
    $commcount = str_replace("%num%", $commcount, $text);
    $commcount = ucfirst(str_replace("%n%", $vars['commcount'], $commcount));

    $commnames=$vars['commnames'];

    $weblog = $PIVOTX['weblogs']->getWeblog();
    if ($weblog['comment_pop']==1) {

        $output = sprintf("<a href='%s' ", $link);
        $output.= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $link, $weblog['comment_width'], $weblog['comment_height']);
        $output.= sprintf(" title=\"%s\">%s</a>",$commnames, $commcount);

    } else {

        $output=sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, $commnames, $commcount);

    }

    return $output;
}


/**
 * Outputs the list of comments for an entry based on the supplied format
 * whether it is in a subweblog or on an entry page.
 */
function smarty_comments($params, $format, &$smarty) {
    global $PIVOTX, $temp_comment;

    if (isset($format)) {

        $params = cleanParams($params);

        if (trim($format)=="") {
            $format = "%anchor%
            <p class='comment'>%comment%</p>
            <cite class='comment'><strong>%name%</strong> %url% - %date% %editlink%</cite>";
        }

        $order = getDefault($params['order'], "ascending");
        $format_reply = getDefault($params['format_reply'], "Reply on %name%");
        $format_forward = getDefault($params['format_forward'], "Replied on %name%");
        $format_backward = getDefault($params['format_backward'], "This is a reply on %name%");
        $entrydate = getDefault($params['date'], "%day%-%month%-&rsquo;%ye% %hour24%:%minute%");
        $default_gravatar = getDefault($params['default_gravatar'], "http://pivotx.net/p64.gif");
        $gravatar_size = getDefault($params['gravatar_size'], 64);


        // If %editlink% is not present, insert it right after %date%..
        if (strpos($format, "%editlink%")==0){
            $format = str_replace("%date%", "%date% %editlink%", $format);
        }

        $last_comment="";

        $vars = $smarty->get_template_vars();
        $entry = $vars['entry'];
        $comments = $entry['comments'];

        // Make sure $comments is an array..
        if (!is_array($comments)) { $comments = array(); }

        // Perhaps we're previewing a comment. Add it..
        if ( !empty($temp_comment) &&  is_array($temp_comment) ) {
           $comments[] = $temp_comment;
        }

        if (count($comments)>0) {

            // first, make a list of comment-on-comments..
            $crosslink = array();

            $blocklist = new IPBlock();
            foreach ($comments as $count => $temp_row) {
                if(preg_match("/\[(.*):([0-9]*)\]/Ui", $temp_row['comment'], $matches)) {
                    $crosslink[$count+1] = $matches[2];
                    // remove [name:1] from comment..
                    $comments[$count]['comment'] = str_replace($matches[0], "", $comments[$count]['comment']);
                }
            }

            $last_count = count($comments);

            // $counter might seem redundant, when we already have a $count. This is because $count keeps track
            // of the id of the comment, and might not correspond to the actual number of comments we're displaying
            // or whether they're odd or even.
            $counter = 0;
            foreach ($comments as $count => $temp_row) {

                $counter++;

                /**
                 * If we get here, this is a record we have to output in some form..
                 */
                $temp_row['name'] = strip_tags($temp_row['name']);
                $temp_row['email'] = strip_tags($temp_row['email']);
                $temp_row['url'] = strip_tags($temp_row['url']);

                // Set the flag to display the 'awaiting moderation' text.
                if ($temp_row["moderate"]==1) {
                    $awaiting_moderation = true;
                }

                // Check if the comment is different than the last one, if the author's
                // IP isn't blocked, and if the comment isn't waiting for moderation.
                if ( ($temp_row["ip"].$temp_row["comment"]!=$last_comment) &&
                        (!($blocklist->isBlocked($temp_row["ip"]))) &&
                        ( ($temp_row["moderate"]!=1) || ($temp_row['showpreview']==1) )  ){

                    /**
                     * make email link..
                     */
                    if (isEmail($temp_row["email"]) && !$temp_row["discreet"]) {
                        $email_format = "(".encodeMailLink($temp_row["email"], __('Email'), $temp_row["name"])." )";
                        $emailtoname = encodeMailLink($temp_row["email"], $temp_row["name"], $temp_row["name"]);
                    } else {
                        $email_format = "";
                        $emailtoname = $temp_row["name"];
                    }

                    if (isEmail($temp_row["email"])) {
                        $gravatar = "http://www.gravatar.com/avatar/" . md5(strtolower($temp_row["email"])) .
                                            "?d=" . urlencode($default_gravatar) . "&amp;s=" . $gravatar_size;
                    } else {
                        $gravatar = $default_gravatar;
                    }

                    /**
                     * make url link..
                     */
                    if (isUrl($temp_row["url"])) {
                        if (strpos($temp_row["url"], "ttp://") < 1 ) {
                            $temp_row["url"]="http://".$temp_row["url"];
                        }

                        $target = "";

                        $temp_row["url_title"]= str_replace('http://', '', $temp_row["url"]);

                        //perhaps redirect the link..
                        if ($PIVOTX['weblogs']->get('', 'lastcomm_redirect')==1 ) {
                            $target .= " rel=\"nofollow\" ";
                        }

                        $url_format = sprintf("(<a href='%s' $target title='%s'>%s</a>)",
                                $temp_row["url"], cleanAttributes($temp_row["url_title"]), __('URL'));
                        $urltoname = sprintf("<a href='%s' $target title='%s'>%s</a>",
                                $temp_row["url"], cleanAttributes($temp_row["url_title"]), $temp_row['name']);
                    } else {
                        $url_format = "";
                        $urltoname = $temp_row["name"];
                    }


                    /**
                     * Make 'edit' and 'delete' links..
                     */
                    $editlink = getEditCommentLink($entry['code'], $count);


                    /**
                     * make a 'registered user' span..
                     */
                    if ($temp_row['registered']==1) {
                        $name = "<span class='registered'>" . $temp_row["name"] . "</span>";
                    } else {
                        $name = $temp_row["name"];
                    }

                    /**
                     * make quote link..
                     */
                    $quote = sprintf("<a href='#commentform' onclick='javascript:var pv=document.getElementsByName(\"piv_comment\");pv[0].value=\"[%s:%s] \"+pv[0].value;'>%s</a>",
                        $temp_row["name"], $count+1, $format_reply );

                    // make backward link..
                    if (isset($crosslink[$count+1])) {
                        $to = $comments[ ($crosslink[$count+1] - 1) ];
                        $backward_text = str_replace("%name%", $to['name'], $format_backward);
                        $backward_anchor = makeURI(html_entity_decode($to['name'], ENT_COMPAT, 'UTF-8')) ."-". formatDate($to["date"],"%ye%%month%%day%%hour24%%minute%");
                        $backward_link = sprintf("<a href='#%s'>%s</a>", $backward_anchor, $backward_text);
                    } else {
                        $backward_link = "";
                    }

                    /**
                     * make forward link..
                     */
                    $forward_link = "";
                    foreach ($crosslink as $key => $val) {
                        if (($val-1) == $count) {
                            $from = $comments[ ($key-1) ];
                            $forward_text = str_replace("%name%", $from['name'], $format_forward);
                            $forward_anchor = makeURI(html_entity_decode($from['name'], ENT_COMPAT, 'UTF-8')) ."-". formatDate($from["date"],"%ye%%month%%day%%hour24%%minute%");
                            $forward_link .= sprintf("<a href='#%s'>%s</a> ", $forward_anchor, $forward_text);
                        }
                    }

                    /**
                     * make anchors
                     */
                    $id = makeURI(html_entity_decode($temp_row['name'], ENT_COMPAT, 'UTF-8')) ."-". formatDate($temp_row["date"],"%ye%%month%%day%%hour24%%minute%");
                    $anchor = "<a id=\"$id\"></a>";

                    $date = formatDate($temp_row["date"],$entrydate);
                    $datelink = "<a href=\"#$id\">$date</a>"; 

                    /**
                     * substite all of the parameters into the comment, and add it to the output.
                     */
                    $this_tag = $format;
                    $this_tag = str_replace("%quote%", $quote, $this_tag);
                    $this_tag = str_replace("%quoted-back%", $backward_link, $this_tag);
                    $this_tag = str_replace("%quoted-forward%", $forward_link, $this_tag);
                    $this_tag = str_replace("%count%", $counter, $this_tag);
                    $this_tag = str_replace("%code%", $entry['code'], $this_tag);
                    $this_tag = str_replace("%even-odd%", ( (($counter)%2) ? 'odd' : 'even' ), $this_tag);
                    $this_tag = str_replace("%ip%", $temp_row["ip"], $this_tag);
                    $this_tag = str_replace("%date%", $date, $this_tag);
                    $this_tag = str_replace("%datelink%", $datelink, $this_tag);
                    $this_tag = str_replace("%comment%", commentFormat($temp_row["comment"]), $this_tag);
                    $this_tag = str_replace("%comment-nolinebreaks%", commentFormat($temp_row["comment"], true), $this_tag);
                    $this_tag = str_replace("%name%", $name, $this_tag);
                    $this_tag = str_replace("%name_attr%", cleanAttributes($name), $this_tag);
                    $this_tag = str_replace("%email%", $email_format, $this_tag);
                    $this_tag = str_replace("%url%", $url_format, $this_tag);
                    $this_tag = str_replace("%anchor%", $anchor, $this_tag);
                    $this_tag = str_replace("%url-to-name%", $urltoname, $this_tag);
                    $this_tag = str_replace("%email-to-name%", $emailtoname, $this_tag);
                    $this_tag = str_replace("%gravatar%", $gravatar, $this_tag);
                    $this_tag = str_replace("%editlink%", $editlink, $this_tag);
                    if ( ($counter==$last_count) && (!isset($params['skipanchor']))) {
                        $this_tag = '<a id="lastcomment"></a>'.$this_tag;
                    }

                    $last_comment = $temp_row["ip"].$temp_row["comment"];

                    // Execute hook for comment_after_parse
                    $commentinfo = array('format'=> $this_tag, 'comment'=>$temp_row);
                    $PIVOTX['extensions']->executeHook('comment_after_parse', $commentinfo);
                    $this_tag = $commentinfo['format'];

                    // Remove any unused formatting tags.
                    $this_tag = preg_replace("/%[^%]+%/", "", $this_tag);

                    // Outputting according to order:
                    if ($order == 'ascending') {
                        $output .= $this_tag."\n";
                    } elseif ($order == 'descending') {
                        $output = $this_tag."\n".$output;
                    }
                }
            }
        }

        // If there are comments waiting for moderation, append a note saying so.
        if ($awaiting_moderation) {
            $output .= sprintf("<p id='moderate_queue_waiting'>%s</p>", __('One or more comments are waiting for approval by an editor.'));
        }

        if (!isset($params['skipanchor'])) {
                $output = '<a id="comm"></a>'."\n".$output;
        }

        return $output;

    }


}

/**
 * Outputs it's content without caching.
 */
function smarty_block_nocache($param, $content, &$smarty) {
    return $content;
}

/**
 * 
 */
function smarty_searchheading($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $count = count($vars['searchresults']);
    $query = htmlspecialchars($vars['modifier']['uri']);

    $result0 = getDefault($params['result0'], __('Results for "%name%":') );
    $result1 = getDefault($params['result1'], __('Results for "%name%":') );
    $resultmore = getDefault($params['resultmore'], __('Results for "%name%":') );
    
    // Only output anything if we're on a search page.
    if ($vars['modifier']['pagetype']!="search") {
        return "";
    }
    
    if ($count==0) {
        $output = $result0;
    } else if ($count==1) {
        $output = $result1;
    } else {
        $output = $resultmore;        
    }
    
    $output = str_replace("%name%", $query, $output);
    $output = str_replace("%query%", $query, $output);
    $output = str_replace("%num%", $count, $output);
    
    return $output;
    
}

/**
 * 
 */
function smarty_searchresults($params, $format, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $results = $vars['searchresults'];
    
    $output = "";
    
    $titlelength = getDefault($params['titletrimlength'], 60);
    $excerptlength = getDefault($params['excerptlength'], 100);
    
    if (isset($format)) {

        $smarty->assign('content', "<!-- Searchresults were already output -->");

        if (count($results)>0) {
            $output .= $params['prefix'];
        }

        foreach($results as $result) {
        
            $temp_output = $format;

            $result['title'] = makeExcerpt($result['title'], $titlelength, true);
            $result['excerpt'] = makeExcerpt($result['introduction'] . $result['body'], $excerptlength, true);

            $temp_output = formatEntry($result, $temp_output);
        
            $output .= $temp_output;
        
        }

        if (count($results)>0) {
            $output .= $params['postfix'];
        }
        
        return $output;
        
    }
    
}


/**
 * Outputs the list of trackbacks for an entry based on the supplied format
 * whether it is in a subweblog or on an entry page.
 */
function smarty_trackbacks($params, $format, &$smarty) {
    global $PIVOTX;

    if (isset($format)) {

        $params = cleanParams($params);

        if (trim($format)=="") {
            $format = "%anchor%
            <p class='comment'><strong>%title%</strong><br />%excerpt%</p>
            <cite class='comment'>Sent on %date%, via %url% %editlink%</cite>";
        }

        $order = getDefault($params['order'], "ascending");
        $entrydate = getDefault($params['date'], "%day%-%month%-&rsquo;%ye% %hour24%:%minute%");
        // $entrydate=$PIVOTX['weblogs']->get('', 'fulldate_format');

        // If %editlink% is not present, insert it right after %date%..
        if (strpos($format, "%editlink%")==0){
            $format = str_replace("%date%", "%date% %editlink%", $format);
        }

        $last_trackback="";
        $output='';

        $vars = $smarty->get_template_vars();
        $entry = $vars['entry'];
        $trackbacks = $entry['trackbacks'];

        // Make sure $comments is an array..
        if (!is_array($trackbacks)) { $trackbacks = array(); }

        // Initialise the IP blocklist.
        $blocklist = new IPBlock();

        foreach ($trackbacks as $count => $temp_row) {
            
            // Skip all trackbacks from blocked IPs.
            if ($blocklist->isBlocked($temp_row["ip"])){
                continue;
            }

            if (isUrl($temp_row["url"])) {
                if (strpos($temp_row["url"], "ttp://") < 1 ) {
                    $temp_row["url"]="http://".$temp_row["url"];
                }
                $url = '<a href="'.$temp_row["url"].'" rel="nofollow">' . $temp_row["name"] . '</a>';
            } else {
                $url = $temp_row["url"];
            }

            $editlink = getEditTrackbackLink($entry['code'],$count);

            $id = safeString($temp_row["name"], true) ."-". formatDate($temp_row["date"],"%ye%%month%%day%%hour24%%minute%");
            $anchor = "<a id=\"$id\"></a>";

            $date = formatDate($temp_row["date"],$entrydate);
            $datelink = "<a href=\"#$id\">$date</a>"; 

            /**
             * substite all of the parameters into the trackback, and add it to the output.
             */
            $this_tag = $format;
            $this_tag = str_replace("%code%", $entry['code'], $this_tag);
            $this_tag = str_replace("%count%", $count+1, $this_tag);
            $this_tag = str_replace("%even-odd%", ( (($count)%2) ? 'even' : 'odd' ), $this_tag);
            $this_tag = str_replace("%ip%", $temp_row["ip"], $this_tag);
            $this_tag = str_replace("%date%", formatDate($temp_row["date"],$entrydate), $this_tag);
            $this_tag = str_replace("%datelink%", $datelink, $this_tag);
            $this_tag = str_replace("%excerpt%", commentFormat($temp_row["excerpt"]), $this_tag);
            $this_tag = str_replace("%title%", $temp_row["title"], $this_tag);
            $this_tag = str_replace("%url%", $url, $this_tag);
            $this_tag = str_replace("%anchor%", $anchor, $this_tag);
            $this_tag = str_replace("%editlink%", $editlink, $this_tag);

            // Outputting according to order:
            if ($order == 'ascending') {
                $output .= $this_tag."\n";
            } elseif ($order == 'descending') {
                $output = $this_tag."\n".$output;
            } else {
                debug("What?");
            }

            $last_trackback = $temp_row["ip"].$temp_row["excerpt"];
        }
    }

    if (!isset($params['skipanchor'])) {
        $output = '<a id="track"></a>'."\n".$output;
    }

    return $output;


}



/**
 * Replace the [[ content ]] tag in the 'extra template' with
 * the desired content from $smarty
 *
 * @return string
 */
function smarty_content($params, &$smarty) {
    global $PIVOTX, $oldpage;

    $vars = $smarty->get_template_vars();
    $content = $vars['content'];

    return $content;
}



/**
 * Smarty function to count the elements on which the modifier is applied.
 * 
 * @param array $params
 * @return string
 */
function smarty_count($params) {
    
    if (!is_array($params['array'])) {
        return false;
    } else {
        return count($params['array']);
    }
    
}



/**
 * Output the current date, or the date given in $params['date'].
 * The result is formatted according to $params['format'].
 *
 * if $params['use'] is set, we use that date to display, you can use
 * 'date', 'publish_date', 'edit_date', or an other variable that is known
 * from within the smarty scope, as long as it uses the mysql date format.
 * (ie: 2007-06-11 22:10:45)
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_date($params, &$smarty) {

    // Keep track of the last output date (used if we only want 'different dates').
    static $last_output;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();

    // Set the format.
    if (!empty($params['format'])) {
        $format = $params['format'];
    } else {
        $format = "%day% %month% %ye% - %hour24%:%minute%";
    }

    // If we have a $params['use'], we take that value from the smarty object,
    // else we check if $params['date'] is set, and use that.
    // Then we check if there's a 'date' set in the smarty object.
    // As a last resort we use '', which is evaluated as being 'now()'
    if (!empty($params['use'])) {
        $date = $vars[ $params['use'] ];
    } else if (!empty($params['date'])) {
        $date = $params['date'];
    } else if (!empty($vars['date'])) {
        $date = $vars['date'];
    } else {
        $date = '';
    }

    $output = formatDate($date, $format);

    // Check if we only want 'different dates':
    if ($params['diffonly']==1) {
        if ($output == $last_output) {
            return "";
        }

        // Store output for the next time.
        $last_output = $output;
    }

    return $output;

}


function smarty_editlink($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();

    $format = getDefault($params['format'], __('Edit'));
    $prefix = getDefault($params['prefix'], "");
    $postfix = getDefault($params['postfix'], "");

    $pagetype = $PIVOTX['parser']->modifier['pagetype'];

    if (($pagetype == "entry") || isset($vars['entry'])) {
        // We are on an entry page or in a subweblog 
        $output = getEditlink($format, $vars['entry']['uid'], $prefix, $postfix, "entry");        
    } else {
        $output = getEditlink($format, $vars['page']['uid'], $prefix, $postfix, "page");
    }

    return $output;

}


/**
 * Insert a link to open the emoticons reference window
 *
 * @param array $params
 * @return string
 */
function smarty_emotpopup($params) {
    global $PIVOTX, $emoticon_window, $emoticon_window_width, $emoticon_window_height;

    $params = cleanParams($params);

    $title = getDefault($params['title'], __('Emoticons') );

    if ($PIVOTX['weblogs']->get('', 'emoticons')==1) {

        if ($emoticon_window != '') {

            $url = $PIVOTX['paths']['pivotx_url']."includes/emoticons/".$emoticon_window;

            $onclick = sprintf("window.open('%s','emot','width=%s,height=%s,directories=no,location=no,menubar=no,scrollbars=no,status=yes,toolbar=no,resizable=yes');return false",
                        $url,
                        $emoticon_window_width,
                        $emoticon_window_height
                    );

            $output = sprintf("<a href='#' onmouseover=\"window.status='(Emoticons Reference)';return true;\" onmouseout=\"window.status='';return true;\" title='Open Emoticons Reference' onclick=\"%s\">%s</a>",
                        $onclick,
                        $title
                    );
        }

    } else {
        $output='';
    }

    return $output;

}

/**
 * Return an exploded version of $params['string'], using $params['glue']
 * as the separator for each item.
 *
 * If return is set, it will return the results as that smarty variable. Otherwise, 
 * it will just output the results.
 *
 * Example: 
 * [[ explode string="this, is, a, string" glue=", " return=explodedstring ]]
 * [[ print_r var=$explodedstring ]]
 *
 * @param string $params
 * @param object $smarty
 * @return string
 */
function smarty_explode($params, &$smarty) {

    $return = getDefault($params['return'],false);
    
    if (isset($params['glue'])) {
        $glue = $params['glue'];
    } else {
        $glue = ",";
    }

    if (is_string($params['string'])) {
        $output = explode($glue, $params['string']);
    } else {
        $output = $params['string'];
    }

    if($return && is_string($return)) {
        $smarty->assign($return, $output);
    } else {
        return $output;
    }
}

/**
 * This function is here to provide backwards compatibility for the [[ entrylink ]] tag.
 * In your templates you should use [[ link hrefonly=1 ]] to get the same results.
 *
 * THIS FUNCTION IS DEPRECATED, and will be removed eventually!!
 * 
 * @see smarty_link
 * 
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_entrylink($params, &$smarty) {

    $params['hrefonly'] = 1;
    return _smarty_link_entry($params, $smarty);

}



/**
 * Fetches an RSS or Atom feed, and displays it on a page.
 *
 * Example:
 * <pre>
 * [[ feed url="http://api.flickr.com/services/feeds/photos_public.gne?id=26205235@N02&lang=en-us&format=rss_200"
 *   amount=8 dateformat="%dayname% %day% %monthname%" allowtags="<img><a><strong><em>" ]]
 * <p><strong><a href="%link%">%title%</a></strong><br/></p>
 * <p>%description% (%date%)</p>
 * [[ /feed ]]
 * </pre>
 *
 * In addition to the standard formatting tags (%title%, %link%, %description%,
 * %content%, %author%, %date%, and %id%), you can use any key defined in feed 
 * (by using %keyname%). Upto two-level arrays with keys are supported (as 
 * "%keyname->subkeyname->subsubkeyname%")
 *
 * @param array $params
 * @param string $text
 * @param object $smarty
 * @return string
 */
function smarty_feed($params, $text, &$smarty) {
    global $PIVOTX;
    
    $params = cleanParams($params);

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($text)) { return ""; }
    
    if(!isset($params['url'])) { return __("You need to specify an URL to a feed"); }
    $amount = getDefault($params['amount'], 8);
    $dateformat = getDefault($params['dateformat'], "%dayname% %day% %monthname% %year%");
    $trimlength = getDefault($params['trimlength'], 10000);

    include_once($PIVOTX['paths']['pivotx_path'].'includes/magpie/rss_fetch.inc');
    
    // Parse it
    $rss = fetch_rss($params['url']);
    
    $output = "";


    if (count($rss->items)>0) {

        // Slice it, so no more than '$amount' items will be shown.
        $rss->items = array_slice($rss->items, 0, $amount);
    
        foreach($rss->items as $feeditem) {
            
            $item = $text;

            // If the feed has authors on an entry-level, override the author name..
            if ($author = $feeditem['author']) {
                $authorname = $feeditem['author'];
            }

            $date = formatDate(date("Y-m-d H-i-s", $feeditem['date_timestamp']), $dateformat);

            // Get the title, description and content, since we might want to do some
            // parsing on it..
            $title = $feeditem['title'];
            $description = $feeditem['description'];
            $content =getDefault($feeditem['atom_content'], $feeditem['summary']);
            
            // Do some parsing: stripping tags, trimming length, stuff like that.
            if (!empty($params['allowtags'])) {
                $title = stripTagsAttributes($title, $params['allowtags']);
                $description = stripTagsAttributes($description, $params['allowtags']);
                $content = stripTagsAttributes($content, $params['allowtags']);
            } else {
                $title = trimText(stripTagsAttributes($title, "<>"), $trimlength);
                $description = trimText(stripTagsAttributes($description, "<>"), $trimlength);
                $content = trimText(stripTagsAttributes($content, "<>"), $trimlength);
            } 

            $item = str_replace('%title%', $title, $item);
            $item = str_replace('%link%', $feeditem['link'], $item);
            $item = str_replace('%description%', $description, $item);
            $item = str_replace('%content%', $content, $item);
            $item = str_replace('%author%', $authorname, $item);
            $item = str_replace('%date%', $date, $item);
            $item = str_replace('%id%', $feeditem['id'], $item);

            // Supporting upto two level arrays in item elements.
            foreach ($feeditem as $key => $value) {
                if (is_string($value)) {
                    if ($key == "link" || $trimlength==-1) {
                        $value = trim($value);
                    } else {
                        $value = trimText(trim($value), $trimlength);
                    }
                    $item = str_replace("%$key%", $value, $item);
                } else if (is_array($value)) {
                    foreach ($value as $arrkey => $arrvalue) {
                        if (is_string($arrvalue)) {
                            $arrvalue = trim($arrvalue);
                            if ($trimlength!=-1) {
                                $arrvalue = trimText($arrvalue, $trimlength);
                            }
                            $item = str_replace("%$key".'->'."$arrkey%", $arrvalue, $item);
                        } else if (is_array($arrvalue)) {
                            foreach ($arrvalue as $subarrkey => $subarrvalue) {
                                if (is_string($subarrvalue)) {
                                    $subarrvalue = trim($subarrvalue);
                                    if ($trimlength!=-1) {
                                        $subarrvalue = trimText($subarrvalue, $trimlength);
                                    }
                                    $item = str_replace("%$key".'->'."$arrkey".'->'."$subarrkey%", 
                                        $subarrvalue, $item);
                                }
                            }
                        }
                    }
                }
            }

            // Remove any unused formatting tags.
            $item = preg_replace("/%[^%]+%/", "", $item);

            $output .= $item;

        }

    } else {
        debug("<p>Oops! I'm afraid I couldn't read the the feed.</p>");
        echo "<p>" . __("Oops! I'm afraid I couldn't read the feed.") . "</p>";
        debug(magpie_error());
    }

    return $output;
    
    
    
}



/**
 * Executes a hook, from within a template
 *
 * @param array $params
 * @param object $smarty
 * @return mixed
 */
function smarty_hook($params, &$smarty) {
    global $PIVOTX;

    if (!isset($PIVOTX['extensions'])) {
        return;
    }

    $params = cleanParams($params);

    $name = $params['name'];
    $value = $params['value'];

    // To show where the hooks go into the HTML..
    if ($_GET['showhooks']==1) {
        return "<span class='visiblehook'>" . $name ."</span>";
    }

    $output = $PIVOTX['extensions']->executeHook('in_pivotx_template', $name, $value );

    return $output;

}


/**
 * Gets the description of a file, to display in the template editor screens
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_filedescription($params, &$smarty) {

    $params = cleanParams($params);

    $filename = $params['filename'];
    $extension = strtolower(getExtension($filename));
    
    switch ($extension) {
        
        case "css":
            $output = __("CSS file (stylesheet)");
        
            break;
        
        case "theme":
            $output = __("PivotX weblog theme");
            break;
        
        case "xml":
            $output = __("XML Feed template");
            break;
        
        case "txt":
            $output = __("Text file");
            break;
        
        case "php":
            $output = __("PHP script");

            if (strpos($filename, '%%') !== false) {
                $output = __("PivotX cache file");
            }            

            break;
        
        case "js":
            $output = __("Javascript file");
            break;
        
        case "mpc":
            $output = __("MagpieRSS cache file");
            break;
        
        case "cache":
            $output = __("PivotX cache file");
            break;
        
        case "tag":
            $output = __("PivotX Tag file");
            break;
        
        case "rel":
            $output = __("PivotX Tag relations");
            break;
        
        case "zd":
        case "zg":
            $output = __("Minify cache file");
            break;        

        case "htm":
        case "html":
        case "tpl":
        case "";
            
            $output = __("HTML template");
            
            if (strpos($filename, '_sub_') !== false) {
                $output = __("Include template");
            }
            if (strpos($filename, 'frontpage_') !== false) {
                $output = __("Frontpage template");
            }
            if (strpos($filename, 'archivepage_') !== false) {
                $output = __("Archive template");
            }
            if (strpos($filename, 'entrypage_') !== false) {
                $output = __("Entry template");
            }
            if (strpos($filename, 'page_') !== false) {
                $output = __("Page template");
            }            
            if (strpos($filename, 'searchpage_') !== false) {
                $output = __("Searchpage template");
            }
            if (strpos($filename, 'minify') !== false) {
                $output = __("Minify cache file");
            }    

            break;
        
        default:
            $output = "";
        
    }

    // Some special cases
    switch ($filename) {
        
        case ".htaccess":
            $output = __("Apache configuration");
            break;
        
        case "404.html":
            $output = __("PivotX 'not found' template");
            break;
        
        case "error.html":
            $output = __("PivotX error page");
            break;
        
    }

    return $output;

}



/**
 * Gets a single entry, referenced by it's 'uid'. Set it in the templates,
 * so it can be used like [[ $var.title ]]
 *
 */
function smarty_getentry($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $uid = intval($params['uid']);
    $var = $params['var'];

    if (empty($uid)) {
        debug("[[getentry]] requires a uid.");
        return "";
    }

    if (empty($var)) {
        debug("[[getentry]] requires a var to set the results to.");
        return "";
    }

    // Get the entry from the DB..
    $entry = $PIVOTX['db']->read_entry($uid);

    // Parse the intro and body..
    $entry['introduction'] = parse_intro_or_body($entry['introduction'], false, $entry['convert_lb']);
    $entry['body'] = parse_intro_or_body($entry['body'], false, $entry['convert_lb']);

    $smarty->assign($var, $entry);

    return "";
}



/**
 * Retrieves a list of pages. Useful in combination with [[getpage]]. You can use this
 * To get an array with the URIs of all pages in a chapter, and then iterate
 * through them with [[getpage]]. See this example in the documentation:
 * http://book.pivotx.net/?page=app-b#anchor-getpagelist
 *
 * You can use the 'onlychapter' attribute to choose a chapter to get the pages
 * from. If omitted, it will return all pages.
 *
 * The 'var' attribute determines the var in the template that will have the
 * results. Defaults to 'pageslist'. Note: do _not_ include the $ in the var name:
 * var=pagelist is correct, var=$pagelist is not. This is because the 'var'
 * attribute is used a string to set the variable, if you use $pagelist, the
 * _value_ of $pagelist is used, instead of the string 'pagelist'
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_getpagelist($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $var = getDefault($params['var'], 'pagelist');

    $chapters = $PIVOTX['pages']->getIndex();

    $result = array();

    // Iterate through the chapters
    foreach ($chapters as $chapter) {

        // If 'onlychapter' is set, we should display only the pages in that chapter,
        // and skip all the others. You can use either the name or the uid.
        if (!empty($params['onlychapter']) && (strtolower($chapter['chaptername'])!=strtolower($params['onlychapter'])) &&
                ($chapter['uid']!=$params['onlychapter']) ) {
            continue; // skip it!
        }

        // Iterate through the pages
        foreach ($chapter['pages'] as $page) {
            if(in_array($page['uri'], explode(",",$params['exclude']))) {
                continue;
            }
            
            if ($page['status'] != 'publish') {
                continue; // skip it!
            }
            
            $result[] = $page['uri'];
        }
    }
    
    if($params['sort'] == "title") {
        asort($result);
    }

    $smarty->assign($var, $result);

    return "";

}


/**
 * Gets a single page, referenced by it's 'uri'. Set it in the templates,
 * so it can be used like [[ $page.title ]]
 *
 * @see smarty_resetpage
 * @return string
 */
function smarty_getpage($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    // Save the current '$page', so we can later reset it. Distinguish between entries and pages..
    $vars = $smarty->get_template_vars();
    $pagetype = $PIVOTX['parser']->modifier['pagetype'];
    
    if ($pagetype=="entry" || $pagetype=="weblog" || $pagetype=="archive" ) {
        $smarty->assign('oldpage', $vars['entry']);
    } else {
        $smarty->assign('oldpage', $vars['page']);
    }

    // get the new page, and set it in $smarty. First we set all variables, and
    // then we parse the introduction and body, and finally we set them again.
    // We do this, because some tags in the intro and body might rely on the
    // context of the template variables.
    if (isset($params['uid'])) {
        $page = $PIVOTX['pages']->getPage($params['uid']);
    }
    else {
        $page = $PIVOTX['pages']->getPageByUri($params['uri']);
    }
    foreach($page as $key=>$value) {
        $smarty->assign($key, $value);
    }
    $smarty->assign('page', $page);

    // Parse the intro and body..
    $page['introduction'] = parse_intro_or_body($page['introduction'], false, $page['convert_lb']);
    $page['body'] = parse_intro_or_body($page['body'], false, $page['convert_lb']);

    // Set the parsed variable again. 
    $smarty->assign('introduction', $page['introduction']);
    $smarty->assign('body', $page['body']);
    
    $smarty->assign('page', $page);

    return "";
}


/**
 * Returns the local absolute URL to the home/start page of the website.
 *
 * @return string
 */
function smarty_home() {
    global $PIVOTX;
    return $PIVOTX['paths']['site_url'];
}


/**
 * Inserts an anchor for the current entry (needed for permalink).
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_id_anchor($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $name = getDefault($params['name'], 'e');

    return '<span id="'.$name.$vars['uid'].'"></span>';
}


/**
 * Returns the link to the body (more-link) for the current entry (if there is a body).
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_more($params, &$smarty) {
    global $PIVOTX;
    
    $params = cleanParams($params);
    $params['html'] = true;

    if (isset($smarty)) {
        $data = $smarty->get_template_vars();
    } else {
        $data = $PIVOTX['db']->entry;
    }

    $output = makeMoreLink($data, '', $params);

    return $output;
}


/**
 * Smarty tag to insert an image.
 * 
 * <pre>
 *  [[image file="somedirectory/somefile.jpg" ]]
 * </pre>
 *
 * Valid parameters are "file", "alt", "align", "class", "id", "width" and 
 * "height". The inserted image will have CSS class "pivotx-image" unless the
 * "class" parameter is set.
 * 
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_image( $params ) {
    global $PIVOTX;

    $params = cleanParams($params);

    $filename = $params['file'];
    $alt = cleanAttributes($params['alt']);
    $align = getDefault($params['align'], 'center');
    //$border = getDefault($params['border'], 0); -- border is deprecated..
    $class = $params['class'];
    $id = $params['id'];
    $width = $params['width'];
    $height = $params['height'];

    $org_filename = $filename;

    if (empty($class)) {
        $class = "pivotx-image";
    }
    
    if ($align=="left" || $align=="right") {
    	$class .= " align-".$align; 
    }

    if (empty($id)) {
        $id = "";
    } else {
        $id = 'id="'.$id.'"';
    }

    // only continue if we have an image
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = fixpath( $PIVOTX['paths']['upload_base_url'].$filename );

        switch( $align) {
            case( 'left' ):
            case( 'right' ):
                $output   = '<img src="'.$filename.'" ';
                $output  .= 'title="'.$alt.'" alt="'.$alt.'"';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }                
                $output  .= ' class="'.$class.'"'.$id.' />';
                break;
                
            case( 'inline' ):
                $output  = '<img src="'.$filename.'" title="'.$alt.'"';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }
                $output .= ' alt="'.$alt.'" class="'.$class.'"'.$id.' />';
                break;

            default:
                $output  = '<div class="pivotx-wrapper">';
                $output .= '<img src="'.$filename.'" title="'.$alt.'" ';
                if (!empty($width)) { $output  .= ' width="'.$width.'"'; }
                if (!empty($height)) { $output  .= ' height="'.$height.'"'; }                
                $output .= ' alt="'.$alt.'" class="'.$class.'" '.$id.' />';
                $output .= '</div>';
        }
    } else {
        debug("Rendering error: could not display image '$org_filename'. File does not exist");
        $output = "<!-- Rendering error: could not display image '$org_filename'. File does not exist -->";
    }
    return $output;
}


/**
 * Smarty tag to insert a link to a downloadable file.
 *
 * <pre>
 *  [[download:filename:icon:text:title]]
 * </pre>
 * @param string $filename
 * @param string $icon Insert a suitable icon if set to "icon"
 * @param string $text The text of the download link.
 * @param string $title The text of the title attribue of the link.
 */
function smarty_download( $params ) {
    global $PIVOTX;

    $params = cleanParams($params);

    $filename = $params['file'];
    $icon = $params['icon'];
    $text = $params['text'];
    $title = cleanAttributes($params['title']);

    $org_filename = $filename;

    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = fixpath( $PIVOTX['paths']['upload_base_url'].$filename );
        $ext      = strtolower(getExtension( $filename ));
        $middle   = '';

        // We don't have icons for _all_ filetypes, so we group some together..
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'psd', 'eps', 'bmp', 'tiff', 'ai'))) {
            $iconext = "image";
        } else if (in_array($ext, array('doc', 'docx', 'rtf', 'dot', 'dotx'))) {
            $iconext = "doc";
        } else if (in_array($ext, array('mp3', 'aiff', 'ogg', 'wav'))) {
            $iconext = "audio";
        } else if (in_array($ext, array('wmv', 'mpg', 'mov', 'swf', 'flv'))) {
            $iconext = "movie";
        } else if (in_array($ext, array('zip', 'gz', 'tgz', 'rar', 'dmg'))) {
            $iconext = "zip";            
        } else {
            $iconext = $ext;
        }

        switch( $icon ) {
            case( 'icon' ):
                if( file_exists( $PIVOTX['paths']['pivotx_path'].'pics/icon_'.$iconext.'.gif' )) {
                    $image = fixpath( $PIVOTX['paths']['pivotx_url'].'pics/icon_'.$iconext.'.gif' );
                } else {
                    $image = fixpath( $PIVOTX['paths']['pivotx_url'].'pics/icon_generic.gif' );
                }

                if( '' != $image ) {
                    $width = 0; $height = 0;
                    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['host'].$image );
                    $middle = '<img src="'.$image;
                    if( 0 != $width )  { $middle .='" width="'.$width; }
                    if( 0 != $height ) { $middle .='" height="'.$height; }
                    $middle .= '" alt="'.$title.'" class="icon" style="border:0; margin-bottom: -3px;" />';
                }
                $middle .= ' '.$text;
                // all ok... leave
                break;

            case( 'text' ): // fall through
            default:
                $middle = $text;
        }
        
        // Refuse to insert a download without a clickable link. Just use the
        // filename in this case..
        if (empty($middle)) {
            $middle = basename($filename);
        }
        
        $code = '<a href="'.$filename.'" title="'.$title.'" class="pivotx-download">'.$middle.'</a>';
    } else {
        debug("Rendering error: did not make a download link for '$org_filename'. File does not exist");
        $code = '<!-- error: did not make a download link for '.$org_filename.'. File does not exist -->' ;
    }
    return $code;
}


/**
 * Return an imploded version of $params['array'], using $params['glue']
 * in between each item.
 * If 'return' is set, it will return the results as a smarty variable with that name.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_implode($params, &$smarty) {

    $return = getDefault($params['return'],false);

    if (isset($params['glue'])) {
        $glue = $params['glue'];
    } else {
        $glue = ", ";
    }

    if (is_array($params['array'])) {
        return implode($glue, $params['array']);
    } else {
        return $params['array'];
    }
    
    if($return && is_string($return)) {
        $smarty->assign($return, $output);
    } else {
        return $output;
    }
}




/**
 * Smarty tag for [[ introduction ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_introduction($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $introduction = parse_intro_or_body($vars['introduction'], $params['strip'], $vars['convert_lb']);

    return $introduction;

}


/**
 * Output the language code for the current weblog/language.
 *
 * The optional $type argument can be either 'html' or 'xml'. The output
 * will then be suitable to use in templates, in the html tag, to set
 * the correct language. (If you are using XHTML 1.0, which is the default
 * for PivotX, you should use both [[lang type='html']] and [[lang type='xml']].)
 *
 * @param array $params
 * @return string
 */
function smarty_lang( $params ) {
    global $PIVOTX;

    $params = cleanParams($params);

    $type = $params['type'];

    if (isset($PIVOTX['languages'])) {
        $lang = $PIVOTX['languages']->getCode();
    } else {
        $lang = '';
    }

    if( ''!=$lang ) {
        $output = $lang ;
    } else {
        $output = 'en' ;
    }

    if( !empty($type) ) {
        if ($type == 'html') {
            $output = 'lang="'.$output.'"' ;
        } elseif ($type == 'xml') {
            $output = 'xml:lang="'.$output.'"' ;
        } else {
            $output = '';
        }
    }
    return $output;
}




/**
 * Create a piece of HTML with links to the latest comments.
 *
 * @param array $params
 * @return string
 */
function smarty_latest_comments($params) {
    global $PIVOTX;

    $params = cleanParams($params);

    $latest_comments_format = getDefault($params['format'],
        "<a href='%url%' title='%date%'><b>%name%</b></a> (%title%): %comm%<br />" );
    $latest_comments_length = getDefault($params['length'], 100);
    $latest_comments_trim = getDefault($params['trim'], 16);
    $latest_comments_count = getDefault($params['count'], 6);

    if (!empty($params['category']) && ($params['category']!="*")) {
        $cats = explode(",",safeString($params['category']));
        $cats = array_map("trim", $cats);
    } else {
        if ($PIVOTX['db']->db_type == 'flat') {
            $cats = $PIVOTX['weblogs']->getCategories();
        } else {
            // Don't filter on cats by default, as it is _very_
            // bad for SQL performance. 
            $cats = array();
        }
    }

    $comments = $PIVOTX['db']->read_latestcomments(array(
        'cats' => $cats,
        'count' => $latest_comments_count*3,
        'moderated' => 1
    ));

    // Adding the filter that we ignored because of SQL performance problems.
    if (empty($params['category']) && ($PIVOTX['db']->db_type != 'flat')) {
        $cats = $PIVOTX['weblogs']->getCategories();
        $com_db = new db(false);
        foreach ($comments as $key => $comment) {
            $entry = $com_db->read_entry($comment['entry_uid']);
            $comments[$key]['category'] = $entry['category'];
        }
    }

    $output='';
    $count=0;
 
    // Initialise the IP blocklist.
    $blocklist = new IPBlock();
 
    $weblog = $PIVOTX['weblogs']->getWeblog();
    if (count($comments)>0) {
        foreach ($comments as $comment) {
            
            // if it's in a category that's published on the frontpage, and the user is not blocked, we display it.
            if ( ((empty($comment['category'])) || (count(array_intersect($comment['category'], $cats))>0) || !empty($params['category']) ) &&
                (!($blocklist->isBlocked(trim($comment['ip'])))) ) {

                $id = makeURI(html_entity_decode($comment['name'], ENT_COMPAT, 'UTF-8')) . "-" .
                    formatDate($comment["date"], "%ye%%month%%day%%hour24%%minute%");

                $url=makeFileLink($comment['entry_uid'], '', $id);

                $comment['name'] = trimText(stripslashes($comment['name']), $latest_comments_trim);
                $comment['title'] = trimText(stripslashes($comment['title']), $latest_comments_trim);
                $comment['comment'] = commentFormat($comment["comment"]);
                // Remove the [name:1] part in the 'latest comments'..
                $comment['comment'] = preg_replace("/\[(.*):([0-9]+)\]/iU", '', $comment['comment']);
                $comment['comment'] = trimText(stripslashes($comment['comment']), $latest_comments_length);
                $comment['comment'] = wordwrapHTMLEntities($comment['comment'], 26, ' ', true);

                if ($weblog['comment_pop']==1) {

                    $popup= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $url, $weblog['comment_width'], $weblog['comment_height']);

                } else {
                    $popup='';
                }

                $thisline=$latest_comments_format;
                $thisline=str_replace("%name%", $comment['name'], $thisline);
                $thisline=str_replace("%date%", $comment['date'], $thisline);
                $thisline=str_replace("%title%", $comment['title'], $thisline);
                $thisline=str_replace("%url%", $url, $thisline);
                $thisline=str_replace("%popup%", $popup, $thisline);
                $thisline=str_replace("%comm%", $comment['comment'], $thisline);

                $thisline=formatDate($comment["date"], $thisline);

                $output.= "\n".$thisline;

                $count++;
                if ($count>=$latest_comments_count) {
                    break;
                }
            }
        }
    }
    return entifyAmpersand($output);

}





/**
 * Create a link to an entry, a page or weblog.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_link($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);
    $pagetype = $PIVOTX['parser']->modifier['pagetype'];
    $vars = $smarty->get_template_vars();

    if (isset($params['page'])) {
        
        // If a page link is explicitly requested
        return _smarty_link_page($params, $smarty);
        
    } elseif (isset($params['entry'])) {
        
        // If an entry link is explicitly requested
        return _smarty_link_entry($params, $smarty);
        
    } elseif (isset($params['uri'])) {
        
        // If an uri is given, try to make a link to a page or entry
        // using that uri..
        $params['page'] = $params['uri'];
        $params['entry'] = $params['uri'];
        
        $link = _smarty_link_page($params, $smarty);
        
        if (!empty($link)) {
            return $link;
        }
        
        return _smarty_link_entry($params, $smarty);
        
    } elseif (isset($params['weblog'])) {
        
        // If a weblog link is explicitly requested
        return _smarty_link_weblog($params, $smarty);
        
    } elseif (isset($params['category'])) {
        
        // If a category link is explicitly requested
        return _smarty_link_category($params, $smarty);
        
     } elseif (isset($params['author'])) {
        
        // If a author link is explicitly requested
        return _smarty_link_author($params, $smarty);
        
    } elseif (isset($params['user'])) {
        
        // If a author link is explicitly requested
        $params['author'] = $params['user'];
        unset($params['user']);
        return _smarty_link_author($params, $smarty);
        
    } elseif (isset($params['mail'])) {
        
        // If a mail link is explicitly requested
        return _smarty_link_mail($params, $smarty);
        
    } elseif (isset($vars['entry'])) {
        
        // If we're in a subweblog - on a page or in a weblog.
        return _smarty_link_entry($params, $smarty);
        
    } elseif ($pagetype=="page") {
        
        // If we're in a page (and no page parameter is given).
        return _smarty_link_page($params, $smarty);
        
    } elseif (isset($PIVOTX['parser']->modifier['category'])) {
        
        // If a category is set
        return _smarty_link_category(array( 'category'=>$PIVOTX['parser']->modifier['category'], 'hrefonly'=>true ), $smarty);
        
    } else {
        
        // Default is link to entry
        return _smarty_link_entry($params, $smarty);
        
    }

}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_category($params, &$smarty) {
    global $PIVOTX;
 
    $params = cleanParams($params);

    if (is_array($params['category'])) {
        debug("The link tag is for single categories - not arrays.");
        return '';
    }     
    
    $cat = $PIVOTX['categories']->getCategory($params['category']);

    if (!empty($cat)) {

        $text = getDefault($params['text'], $cat['display']);
        $title = $params['title'];
        $catlink = makeCategoryLink($cat['name']);
        
        // Perhaps add the protocol and hostname, to create a full URL. 
        if (!empty($params['fullurl'])) {
            $catlink = getHost() . $catlink;
        }

        if (!empty($params['hrefonly'])) {
            $output = $catlink;
        } else {
            $output = sprintf("<a href='%s' title='%s'>%s</a>", $catlink, $title, $text);
        }
        return $output;

    } else {
        
        debug(sprintf("Can't create category link since the category '%s' doesn't exist.", $params['category']));
        return '';

    }    
  
}
 
/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_author($params, &$smarty) {
    global $PIVOTX;

    if ($author = $PIVOTX['users']->getUser($params['author'])) {

        $authorlink = makeAuthorLink($params['author']);
        
        // Perhaps add the protocol and hostname, to create a full URL. 
        if (!empty($params['fullurl'])) {
            $authorlink = getHost() . $authorlink;
        }
        
        if (!empty($params['hrefonly'])) {
            $output = $authorlink;
        } else {
            $text = getDefault($params['text'], '%nickname%');
            $title = getDefault($params['title'], '%nickname%');
            $output = sprintf("<a href='%s' title='%s'>%s</a>", $authorlink, $title, $text);
            $output = str_replace('%nickname%', $author['nickname'], $output);
            $output = str_replace('%username%', $author['username'], $output);
        }

        return $output;

    } else {
        
        debug(sprintf("Can't create author link since the author '%s' doesn't exist.", $params['author']));
        return '';

    }    
    
}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_mail($params, &$smarty) {
    global $PIVOTX;

    $text = getDefault($params['text'], __("Email"));
    $title = getDefault($params['title'], "");
    $encrypt = getDefault($params['encrypt'], false);
    $output = encodeMailLink( $params['mail'], $text, $title, $encrypt);

    return $output;

}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_page($params, &$smarty) {
    global $PIVOTX;
  
    if (!empty($params['page'])) {
        // Get the page from the DB..
        $page = $PIVOTX['pages']->getPageByUri($params['page']);     
    } else { 
        // Use the current page..
        $page = $PIVOTX['pages']->getCurrentPage();
    }

    if (!empty($page['uid'])) {

        $text = getDefault($params['text'], $page['title']);
        $title = getDefault($params['title'], cleanAttributes($page['title']));

        $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date']);
        
        // Perhaps add the protocol and hostname, to create a full URL. 
        if (!empty($params['fullurl'])) {
            $pagelink = getHost() . $pagelink;
        }
        
        if (!empty($params['hrefonly'])) {
            $output = $pagelink;
        } else {
            $output = sprintf("<a href='%s' title='%s'>%s</a>", $pagelink, $title, $text);
        }

        return $output;

    } else {
        debug(sprintf("Can't create page link since uid isn't set. (Page '%s')", $params['page']));
        return '';
    }    
    
}



/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_weblog($params, &$smarty) {
    global $PIVOTX;
  
    $weblog = $PIVOTX['weblogs']->getWeblog($params['weblog']);

    $text = getDefault($params['text'], $weblog['name']);
    $title = getDefault($params['title'], cleanAttributes($weblog['name']));

    $link = $weblog['link'];
    
    // Perhaps add the protocol and hostname, to create a full URL. 
    if (!empty($params['fullurl'])) {
        $link = getHost() . $link;
    }

    if (!empty($params['hrefonly'])) {
        $output = $link;
    } else {
        $output = sprintf("<a href='%s' title='%s'>%s</a>", $link, $title, $text);
    }
    
    return $output;

  
}


/**
 * Helper function for smarty_link()
 *
 * @see smarty_link()
 */
function _smarty_link_entry($params, &$smarty) {
    global $PIVOTX;
    
    $vars = $smarty->get_template_vars();

    if (!empty($params['entry'])) {
        $params['uid'] = $params['entry'];
    } else {
        $params['uid'] = getDefault($params['uid'], $vars['entry']['uid']);
    }

    // Abort immediately if uid isn't set or there is no entry.
    if (empty($params['uid'])) {
        debug("Can't create entry link since uid isn't set.");
        return '';
    } elseif (!$PIVOTX['db']->entry_exists($params['uid'])) {
        debug("Can't create entry link since there is no entry with uid ${params['uid']}.");
        return '';
    }

    $text = getDefault($params['text'], "%title%");
    $title = getDefault($params['title'], "%title%");

    $link = makeFileLink($params['uid'], $params['weblog'], "");
        
    // Perhaps add the protocol and hostname, to create a full URL. 
    if (!empty($params['fullurl'])) {
        $link = getHost() . $link;
    }

    if ($params['query'] !='' ) {
        if (strpos($link,"?")>0) {
            $link .= '&amp;'.$params['query'];
        } else {
            $link .= '?'.$params['query'];
        }
    }
    
    if (!empty($params['hrefonly'])) {
        
        $output = $link;
        
    } else {
       
        if (isset($vars['entry']) && ($vars['entry']['code'] == $params['uid'])) {
            $entry = $vars['entry'];
        } else {
            $temp_db = new db(false);
            $entry = $temp_db->read_entry($params['uid']);
        }
    
        $text = str_replace('%title%', $entry['title'], $text);
        $text = str_replace('%subtitle%', $entry['subtitle'], $text);
        $text = formatDate($entry['date'], $text );
    
        $title = trim($title);
        if (!empty($title)) {
            $title = str_replace('%title%', $entry['title'], $title);
            $title = str_replace('%subtitle%', $entry['subtitle'], $title);
            $title = formatDate($entry['date'], $title );
        }
    
        $output = sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, cleanAttributes($title) ,$text);

    }
    
    return $output;

}



/**
 * Insert the _sub_link_list.html sub-template. Test for older versions as well.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_link_list($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();

    $templatedir = $vars['templatedir'];

    if ($smarty->template_exists($templatedir."/link_list.html")) {
        $output = $smarty->fetch($templatedir."/link_list.html");
    } else if ($smarty->template_exists($templatedir."/_aux_link_list.html")) {
        $output = $smarty->fetch($templatedir."/_aux_link_list.html");
    } else if ($smarty->template_exists($templatedir."/_sub_link_list.html")) {
        $output = $smarty->fetch($templatedir."/_sub_link_list.html");
    } else {
        $output = "<!-- _sub_link_list.html doesn't exist! -->";
    }

    return $output;

}




/**
 * Returns the local absolute URL to the (current) weblog.
 *
 * @return string
 */
function smarty_log_dir() {
    global $PIVOTX;

    return $PIVOTX['weblogs']->get('','link');
}

/**
 * Returns the global absolute URL to the (current) weblog.
 *
 * $param string $weblog
 * @return string
 */
function smarty_log_url() {
    global $PIVOTX;

    return $PIVOTX['paths']['host'] . $PIVOTX['weblogs']->get('','link');
}

/**
 * Displays as message from PivotX to the user (normally when posting 
 * comments).
 *
 * @param array $params
 * @return string
 */
function smarty_message($params) {
    global $weblogmessage;

    if (empty($params['format'])) {
        $format="<a id='message'></a><p class='pivotx-message'>%message%</p>\n\n";
    } else {
        $format = $params['format'];
    }

    if (!empty($weblogmessage)) {
        $weblogmessage = strip_tags(stripslashes($weblogmessage));
        $output = str_replace("%message%", $weblogmessage, $format);
    } else if (!empty($_GET['weblogmessage'])) {
        $weblogmessage = strip_tags(stripslashes($_GET['weblogmessage']));
        $output = str_replace("%message%", $weblogmessage, $format);
    } else {
        $output = '';
    }

    return $output;
}

/**
 * Displays a message when the moderation queue is enabled.
 *
 */
function smarty_moderate_message($params) {
    global $PIVOTX;

    if (empty($params['output'])) {
        $output = sprintf("<p id='pivotx-moderate-queue-message'>%s</p>", 
            __('Comment moderation is enabled on this site. This means that your ' . 
            'comment will not be visible until it has been approved by an editor.'));
    } else {
        $output = $params['output'];
    }

    if ($PIVOTX['config']->get('moderate_comments')) {
        return $output;
    } else {
        return '';
    }

}

/**
 * Link to the next entry
 *
 * @param array $params
 * @return string
 */
function smarty_nextentry($params) {
    global $PIVOTX;

    // This tag is only allowed on entrypages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = cleanParams($params);

    // initialize a temporary db..
    $temp_db = new db(FALSE);

    // we fetch the next one, until we get one that is set to 'publish'
    $get_next_amount = 1;

    // If possible, we filter on the wanted categories, to make the loop more
    // efficient..
    if (isset($params['category'])) {
        $categories = explode(",",safeString($params['category']));
        $categories = array_map("trim", $categories);
    } else {
        $categories = ($params['incategory']==true ? $PIVOTX['db']->entry['category'] : $PIVOTX['weblogs']->getCategories() );
    }
    
    do {
        
        $next_code=$PIVOTX['db']->get_next_code($get_next_amount, $categories);
        
        if ($next_code) {
            $temp_entry = $temp_db->read_entry($next_code);

            // it's 'ok' if the entry shares at least one category with the current entry.
            // Note: It might seem redundant to do this check here, but the Flat File DB
            // currently returns entries, regardless of the requested categories.                         
            $ok = count(array_intersect($categories, $temp_entry['category']));

        }
        $get_next_amount++;

    } while ( !($next_code===FALSE) && !(($temp_entry['status']=="publish") && $ok) );

    unset($temp_db);

    $text = getDefault($params['text'], '&nbsp;&nbsp;&raquo; <a href="%link%">%title%</a>');
    $cutoff = getDefault($params['cutoff'], 20);

    if ($next_code) {
        $title= (strlen($temp_entry['title'])>2) ? $temp_entry['title'] : substr($temp_entry['introduction'],0,100);
        $link=makeFileLink($temp_entry, "", "");
        $output=$text;
        $output=str_replace("%link%", $link, $output);
        $output=str_replace("%code%", $next_code, $output);
        $output=str_replace("%title%", trimText($title,$cutoff), $output);
        $output=str_replace("%subtitle%", trimText($temp_entry['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);

    } else {
        return "";
    }

}


/**
 * Link to the next page in containing chapter
 *
 * @param array $params
 * @return string
 */
function smarty_nextpage($params, &$smarty) {
    global $PIVOTX;

    // This tag is only allowed on pages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "page" ) { return; }

    $vars = $smarty->get_template_vars();

    // The current page must exist/have an uid
    if (empty($vars['page']['uid'])) {
        debug("There is no uid for the current page.");
        return;
    }

    $params = cleanParams($params);

    $chapters = $PIVOTX['pages']->getIndex();
    $pages = $chapters[$vars['page']['chapter']]['pages'];

    // Handle sorting
    $sort = false;
    if (isset($params['sort'])) {
        $sort = true;
        if ($params['sort'] == 'title') {
            $pages_sort_key = 'title';
        } else if ($params['sort'] == 'uri') {
            $pages_sort_key = 'uri';
        } else if ($params['sort'] == 'date') {
            $pages_sort_key = 'date';
        } else {
            $sort = false;
        }
    }
    if ($sort) {
        foreach ($pages as $key => $page) {
            $pages[ $page[$pages_sort_key] ] = $page;
            unset($pages[$key]);
        }
        ksort($pages);
    }
    if ($params['sortorder'] == 'reverse') {
        $pages = array_reverse($pages, true);
    }

    $found = false;
    reset($pages);
    do {
        $page = current($pages);
        $nextpage = next($pages);
        if (!$found && ($page['uid'] == $vars['page']['uid'])) {
            $found = true;
        }
    } while (($nextpage !== false) && !(($nextpage['status'] == "publish") && $found));


    if ($nextpage) {
        $text = getDefault($params['text'], '&nbsp;&nbsp;&raquo; <a href="%link%">%title%</a>');
        $cutoff = getDefault($params['cutoff'], 20);
        $title  = (strlen($nextpage['title'])>2) ? $nextpage['title'] : substr($nextpage['introduction'],0,100);
        $link   = makePageLink($nextpage['uri'], $nextpage['title'], $nextpage['uid'], $nextpage['date'], $params['weblog']);
        $output = $text;
        $output = str_replace("%link%", $link, $output);
        $output = str_replace("%title%", trimText($title,$cutoff), $output);
        $output = str_replace("%subtitle%", trimText($nextpage['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);
 
    } else {
        return "";
    }

}




/**
 * Inserts a list of pages
 */
function smarty_pagelist($params, &$smarty) {
    global $PIVOTX;
    
    $params = cleanParams($params);

    $chapterbegin = getDefault($params['chapterbegin'], "<strong>%chaptername%</strong><br /><small>%description%</small><ul>", true);
    $pageshtml = getDefault($params['pages'], "<li %active%><a href='%link%' title='%subtitle%'>%title%</a></li>");
    $chapterend = getDefault($params['chapterend'], "</ul>", true);
    $dateformat = getDefault($params['dateformat'], "%day%-%month%-&rsquo;%ye% %hour24%:%minute%");

    // If we use 'isactive', set up the $pageuri and $isactive vars.
    if (!empty($params['isactive'])) {
        // Get the current page uri.
        $smartyvars = $smarty->get_template_vars();
        $pageuri = getDefault($smartyvars['pageuri'], "");
        $isactive = $params['isactive'];
    } else {
        $pageuri = "";
        $isactive = "";
    }

    if (isset($params['onlychapter']) && 
            (($params['onlychapter'] != '') || is_numeric($params['onlychapter']))) {
        $onlychapter_bool = true;
        $onlychapter_arr = explode(',', $params['onlychapter']);
        $onlychapter_arr = array_map('trim', $onlychapter_arr);
        $onlychapter_arr = array_map('strtolower', $onlychapter_arr);
    } elseif (isset($params['excludechapter']) &&
            (($params['excludechapter'] != '') || is_numeric($params['excludechapter']))) {
        $excludechapter_bool = true;
        $excludechapter_arr = explode(',', $params['excludechapter']);
        $excludechapter_arr = array_map('trim', $excludechapter_arr);
        $excludechapter_arr = array_map('strtolower', $excludechapter_arr);
    }


    $chapters = $PIVOTX['pages']->getIndex();
    $output = "";

    // Iterate through the chapters
    foreach ($chapters as $key => $chapter) {

        // If there is no pages, we skip this chapter
        if (count($chapter['pages']) == 0) {
            continue;
        }

        // We also skip any orphaned pages
        if (strcmp($key,"orphaned") == 0) {
            continue;
        }

        // If 'onlychapter' is set, we should display only the pages in one of those chapters,
        // and skip all the others. If 'excludechapter' is set, we should exclude all those 
        // chapters. You can use either the name or the uid.
        if ($onlychapter_bool) {
            $continue = true;
            foreach ($onlychapter_arr as $onlychapter) { 
                if ((strtolower($chapter['chaptername'])==$onlychapter) || 
                        (is_numeric($onlychapter) && ($key==$onlychapter))) {
                    $continue = false;
                    break;
                }
            }
            if ($continue) {
                continue; // skip it!
            }
        } elseif ($excludechapter_bool) {
            $continue = false;
            foreach ($excludechapter_arr as $excludechapter) { 
                if ((strtolower($chapter['chaptername'])==$excludechapter) || 
                        (is_numeric($excludechapter) && ($key==$excludechapter))) {
                    $continue = true;
                    break;
                }
            }
            if ($continue) {
                continue; // skip it!
            }
        }

        $pages = array();

        if ($params['sort'] == 'title') {
            $pages_sort_key = 'title';
        } else if ($params['sort'] == 'uri') {
            $pages_sort_key = 'uri';
        } else if ($params['sort'] == 'date') {
            $pages_sort_key = 'date';
        } else {
            // Just picking a unique key (when there is no sorting).
            unset($params['sort']);
            $pages_sort_key = 'uri';
        }

        // Iterate through the pages
        foreach ($chapter['pages'] as $page) {

            if(in_array($page['uri'], explode(",",$params['exclude']))) {
                continue;
            }

            if ($page['status'] != 'publish') {
                continue; // skip it!
            }

            // Check if the current page is the 'active' one.
            if (!empty($isactive) && ($page['uri']==$pageuri)) {
                $thisactive = $isactive;
            } else {
                $thisactive = "";
            }
            $pagelink = makePageLink($page['uri'], $page['title'], $page['uid'], $page['date'], $params['weblog']);

            // add the page to output
            $temp_output = $pageshtml;
            $temp_output = str_replace("%title%", cleanAttributes($page['title']), $temp_output);
            $temp_output = str_replace("%subtitle%", cleanAttributes($page['subtitle']), $temp_output);
            $temp_output = str_replace("%user%", cleanAttributes($page['user']), $temp_output); // To do: filter this to nickname, email, etc.
            $temp_output = str_replace("%date%", formatDate($page['date'], $dateformat), $temp_output); 
            $temp_output = str_replace("%link%", $pagelink, $temp_output);
            $temp_output = str_replace("%uri%", $page['uri'], $temp_output);
            $temp_output = str_replace("%active%", $thisactive, $temp_output);
            $pages[ $page[$pages_sort_key] ] = $temp_output;

        }

        // Only add the chapter if there are any published and (non-excluded) pages.
        if (count($pages) > 0) {        
            if (isset($params['sort'])) {
                ksort($pages);
            }
            if ($params['sortorder'] == 'reverse') {
                $pages = array_reverse($pages, true);
            }

            if ($params['pagelimit'] != '') {
                $pages = array_slice($pages, 0, $params['pagelimit']);
            }


            // Add the chapterbegin to output
            $temp_output = $chapterbegin;
            $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
            $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
            $output .= $temp_output;

            // Add the pages
            $output .= (implode("\n", $pages));      

            // Add the chapterend to output
            $temp_output = $chapterend;
            $temp_output = str_replace("%chaptername%", $chapter['chaptername'], $temp_output);
            $temp_output = str_replace("%description%", $chapter['description'], $temp_output);
            $output .= $temp_output;
        }
    }

    return entifyAmpersand($output);

}



/**
 * Creates a way to navigate between pages.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_paging($params, &$smarty) {
    global $PIVOTX, $modifier;

    $params = cleanParams($params);

    $action = getDefault($params['action'], "digg");
    $showalways = getDefault($params['showalways'], false);

    $funcs = new Paging("paging");

    // Check if we are called correctly and on the correct page
    $msg = $funcs->sanity_check($action);
    if ($msg != "" && $showalways==false) {
        return $msg;
    }

    // Currently only finds the offset
    $msg = $funcs->setup($action);
    if ($msg != "" && $showalways==false) {
        return $msg;
    }

    $subweblogs = $PIVOTX['weblogs']->getSubweblogs();

    $num_entries = 0;
    $cats = array();

    // Find the number of entries displayed on the page, as defined
    // in the weblog configuration, unless specified as a parameter
    if (!empty($params['amount'])) {
        $num_entries = intval($params['amount']);
    } else if (!empty($params['showme'])) {
        // Note: 'showme' is deprecated. included for backwards compatibility.
        $num_entries = intval($params['showme']);
    } else {
        foreach ($subweblogs as $subweblog) {
            $subweblog = $PIVOTX['weblogs']->getSubweblog('', $subweblog);
            $num_entries = max($subweblog['num_entries'], $num_entries);
        }
    }

    // Find the categories displayed on the page, as defined in
    // the weblog configuration, unless specified as a parameter
    //
    // If we have a 'c=' parameter, use the cats in that to display..
    // To prevent weird things from happening, we only allow changing weblogs
    // with a name like 'default', 'standard', 'main' or 'weblog'.
    // Alternatively, we check if the template specifies the categories to
    // display, like [[ weblog name='weblog' category="default, movies, music" ]]
    if (!empty($modifier['category'])) {
        $cats = explode(",",safeString($modifier['category']));
        $cats = array(array_map("trim", $cats));
    } else if (!empty($params['category']) && $params['category']=="*") {
        $cats = array($PIVOTX['categories']->getCategorynames());
        $params['catsinlink']=true;
    } else if (!empty($params['category'])) {
        $cats = explode(",",safeString($params['category']));
        $cats = array(array_map("trim", $cats));
        $params['catsinlink']=true;
    } else {
        // We have to keep the subweblogs separate, because we need to be able to figure
        // out which subweblog has the most entries, and _not_ the entries combined.
        foreach ($subweblogs as $subweblog) {
            $subweblog = $PIVOTX['weblogs']->getSubweblog('', $subweblog);
            // Only add categories from subweblogs that have any categories assigned.
            if (count($subweblog['categories']) > 0) {
                $cats[] = $subweblog['categories'];
            }
        }
    }

    return $funcs->doit($action, $text, $cats, $num_entries, $params);

}


/**
 * Modifier to parse (as Smarty/PivotX template) a given tag or variable
 *
 * example:
 * [[ $page.introduction|parse ]]
 * [[ body|parse ]] <- note that this second example is not very useful, as 'body' is already parsed.
 *
 * @param string $html
 * @return string
 * 
 */
function smarty_parse($html) {
    
    $html = parse_intro_or_body($html);
    
    return $html;
    
}


/**
 * Inserts a nice button with a link to the pivotx website.
 *
 * @return string
 */
function smarty_pivotxbutton() {
    global $PIVOTX, $build;

    list( $width,$height) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/pivotxbutton.png' ) ;
    $image   = $PIVOTX['paths']['pivotx_url'].'pics/pivotxbutton.png' ;
    $alttext = __('Powered by'). " " . strip_tags($build) ;

    $output  = '<a href="http://www.pivotx.net/" title="'.$alttext.'" class="badge">' ;
    $output .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'" alt="'.$alttext.'" ' ;
    $output .= 'class="badge" longdesc="http://www.pivotx.net/" /></a>';

    return $output;
}



/**
 * Creates a permanent link to the current entry (in the archives).
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_permalink($params, &$smarty) {
    
    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();
    $unit = getDefault($params['unit'], "month");
    $link = makeArchiveLink('', $unit)."#e".$vars['code'];

    $text = str_replace('%title%', $vars['title'], $params['text']);
    $text = formatDate($vars['date'], $text );
    $title = trim($params['title']);
    if (!empty($title)) {
        $title = str_replace('%title%', strip_tags($vars['title']), $title);
        $title = formatDate($vars['date'], $title );
    }

    $code = sprintf('<a href="%s" title="%s">%s</a>', $link, cleanAttributes($title), $text);

    return $code;
}




/**
 * Returns the local absolute URL to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['pivotx_url'];
}


/**
 * Returns the local path to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_path() {
    global $PIVOTX;

    return $PIVOTX['paths']['pivotx_path'];
}


/**
 * Returns the global absolute URL to the pivotx directory.
 *
 * @return string
 */
function smarty_pivotx_url() {
    global $PIVOTX;

    return $PIVOTX['paths']['host'].$PIVOTX['paths']['pivotx_url'];
}


/**
 * Returns the local absolute URL to the extensions directory.
 *
 * @return string
 */
function smarty_extensions_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['extensions_url'];
}


/**
 * Returns the global absolute URL to the extensions directory.
 *
 * @return string
 */
function smarty_extensions_url() {
    global $PIVOTX;

    return $PIVOTX['paths']['host'].$PIVOTX['paths']['extensions_url'];
}


/**
 * Smarty tag to insert a popup to an image..
 *
 * First we check if we can use Jquery and whether extensions/thickbox/ is
 * present. If so, we use Thickbox. If not, we use the 'old style' popup.
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_popup ($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $filename = $params['file'];
    $thumbname = $params['description'];
    $org_thumbname = $params['description'];
    $alt = cleanAttributes($params['alt']);
    $align = getDefault($params['align'], "center");
    // $border = getDefault($params['border'], 0); -- border is deprecated

    $vars = $smarty->get_template_vars();
    $entry = $vars['entry'];

    // Fix Thumbname, perhaps use a thumbname, instead of textual link
    if ( empty($thumbname) || ($thumbname=="(thumbnail)") ) {
        $thumbname = makeThumbname($filename);
    }


    // If the thumbnail exists, make the HTML for it, else just use the text for a link.
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$thumbname )) {

        $ext = strtolower(getExtension($thumbname));

        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
            if ($align=="center") {
                $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class='pivotx-popupimage'/>",
                    $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $alt
                );
            } else {
                $thumbname = sprintf("<img src=\"%s%s\" alt=\"%s\" title=\"%s\" class='pivotx-popupimage align-%s'/>",
                    $PIVOTX['paths']['upload_base_url'], $thumbname, $alt, $alt, $align
                );
            }
        } else {
            $thumbname = $org_thumbname;
        }

    } else {

        if (strlen($org_thumbname)>2) {
            $thumbname = $org_thumbname;
        } else {
            $thumbname = "popup";
        }
    }


    // Prepare the HMTL for the link to the popup..
    if( file_exists( $PIVOTX['paths']['upload_base_path'].$filename )) {

        $filename = $PIVOTX['paths']['upload_base_url'].$filename ;

        $code = sprintf( "<a href='%s' class=\"thickbox\" title=\"%s\" rel=\"entry-%s\" >%s</a>",
                $filename,
                $alt,
                intval($entry['code']),
                $thumbname
            );

        if( 'center'==$align ) {
            $code = '<div class="pivotx-wrapper">'.$code.'</div>' ;
        }
    } else {
        debug("Rendering error: could not popup '$filename'. File does not exist.");
        $code = "<!-- Rendering error: could not popup '$filename'. File does not exist. -->";
    }


    // If the hook for the thickbox includes in the header was not yet
    // installed, do so now..
    $PIVOTX['extensions']->addHook('after_parse', 'callback', 'thickboxIncludeCallback');

    return $code;

}



/**
 * Link to the previous entry
 *
 * @param array $params
 * @return string
 */
function smarty_previousentry($params) {
    global $PIVOTX;

    // This tag is only allowed on entrypages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "entry" ) { return; }

    $params = cleanParams($params);

    // initialize a temporary db..
    $temp_db = new db(FALSE);

    // we fetch the next one, until we get one that is set to 'publish'
    $get_prev_amount = 1;

    // If possible, we filter on the wanted categories, to make the loop more
    // efficient..
    if (isset($params['category'])) {
        $categories = explode(",",safeString($params['category']));
        $categories = array_map("trim", $categories);
    } else {
        $categories = ($params['incategory']==true ? $PIVOTX['db']->entry['category'] : $PIVOTX['weblogs']->getCategories() );
    }
    
    do {      
    
        $prev_code=$PIVOTX['db']->get_previous_code($get_prev_amount, $categories);
        
        if ($prev_code) {
            $temp_entry = $temp_db->read_entry($prev_code);

            // it's 'ok' if the entry shares at least one category with the current entry.
            // Note: It might seem redundant to do this check here, but the Flat File DB
            // currently returns entries, regardless of the requested categories.                         
            $ok = count(array_intersect($categories, $temp_entry['category']));

        }
        $get_prev_amount++;

    } while ( !($prev_code===FALSE) && !(($temp_entry['status']=="publish") && $ok) );

    unset($temp_db);

    $text = getDefault($params['text'], '&laquo; <a href="%link%">%title%</a>');
    $cutoff = getDefault($params['cutoff'], 20);

    if ($prev_code) {
        $title= (strlen($temp_entry['title'])>2) ? $temp_entry['title'] : substr($temp_entry['introduction'],0,100);
        $link = makeFileLink($temp_entry, "", "");
        $output=$text;
        $output=str_replace("%link%", $link, $output);
        $output=str_replace("%code%", $prev_code, $output);
        $output=str_replace("%title%", trimText($title,$cutoff), $output);
        $output=str_replace("%subtitle%", trimText($temp_entry['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);
    } else {
        return "";
    }
}

/**
 * Link to the previous page in the containing chapter.
 *
 * @param array $params
 * @return string
 */
function smarty_previouspage($params, &$smarty) {
    global $PIVOTX;

    // This tag is only allowed on pages..
    if ( $PIVOTX['parser']->modifier['pagetype'] != "page" ) { return; }

    $vars = $smarty->get_template_vars();

    // The current page must exist/have an uid
    if (empty($vars['page']['uid'])) {
        debug("There is no uid for the current page.");
        return;
    }

    $params = cleanParams($params);

    $chapters = $PIVOTX['pages']->getIndex();
    $pages = $chapters[$vars['page']['chapter']]['pages'];

    // Handle sorting
    $sort = false;
    if (isset($params['sort'])) {
        $sort = true;
        if ($params['sort'] == 'title') {
            $pages_sort_key = 'title';
        } else if ($params['sort'] == 'uri') {
            $pages_sort_key = 'uri';
        } else if ($params['sort'] == 'date') {
            $pages_sort_key = 'date';
        } else {
            $sort = false;
        }
    }
    if ($sort) {
        foreach ($pages as $key => $page) {
            $pages[ $page[$pages_sort_key] ] = $page;
            unset($pages[$key]);
        }
        ksort($pages);
    }

    // We will find the previous page by searching the array in reverse order.
    // This means that have to reverse it, if sortorder isn't reverse ;-)
    if ($params['sortorder'] != 'reverse') {
        $pages = array_reverse($pages, true);
    }

    $found = false;
    do {
        $page = current($pages);
        $prevpage = next($pages);
        if (!$found && ($page['uid'] == $vars['page']['uid'])) {
            $found = true;
        }
    } while (($prevpage !== false) && !(($prevpage['status'] == "publish") && $found));

    $text = getDefault($params['text'], '&laquo; <a href="%link%">%title%</a>');
    $cutoff = getDefault($params['cutoff'], 20);

    if ($prevpage) {
        $title  = (strlen($prevpage['title'])>2) ? $prevpage['title'] : substr($prevpage['introduction'],0,100);
        $link   = makePageLink($prevpage['uri'], $prevpage['title'], $prevpage['uid'], $prevpage['date'], $params['weblog']);
        $output = $text;
        $output = str_replace("%link%", $link, $output);
        $output = str_replace("%title%", trimText($title,$cutoff), $output);
        $output = str_replace("%subtitle%", trimText($prevpage['subtitle'],$cutoff), $output);
        return entifyAmpersand($output);
    } else {
        return "";
    }
}





/**
 * Print_r a variable/array from smarty templates
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_print_r($params, &$smarty) {

    $params = cleanParams($params);

    echo "\n<div class='debug-container'>\n<pre>\n";
    print_r($params['var']);
    echo "\n<br />--</pre>\n</div>&nbsp;\n";

}



/**
 * Returns the text 'registered' if the current visitor is (logged in and) registered.
 *
 * Useful in templates to set special classes for (logged in) registered 
 * visitors.
 * 
 * @param array $params
 * @return string
 */
function smarty_registered($params) {
    global $PIVOTX;
    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();
    if ($visitors->isLoggedIn()) {
        return 'registered';
    } else {
        return '';
    }
}

/**
 * Returns a link to the "comment user"/"registered visitor" page
 * (with the correct weblog selection). 
 *
 * It takes three (optional) smarty parameters - 'weblog', 
 * 'linktext' and 'linktext_logged_in'.
 *
 * @param array $params
 * @return string
 */
function smarty_register_as_visitor_link($params) {
    global $PIVOTX;

    $weblog = getDefault($params['weblog'], $PIVOTX['weblogs']->getCurrent());
    $url = makeVisitorPageLink('', $weblog);

    require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
    $visitors = new Visitors();
    if ($visitors->isLoggedIn()) {
        $linktext = getDefault($params['linktext_logged_in'], __('account'));
    } else {
        $linktext = getDefault($params['linktext'], __('register/login'));
    }
    return "<a href='$url' class='pivotx-system-links'>$linktext</a>";
}



/**
 * Inserts previously filled fields for commenting. They can come from either
 * a previous submit (when previewing, or when an error in the form occurred)
 * or from the cookie.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_remember($params, &$smarty) {
    global $PIVOTX, $temp_comment;
    static $default_values;

    $params = cleanParams($params);
    $name = $params['name'];

    // Only calculate previous fields once
    if (!is_array($default_values)) {
        $default_values = array();

        // Get the cookies in an array.. (Why aren't we just using $_COOKIE?)
        if (isset($_SERVER['HTTP_COOKIE']))  {
            foreach (explode(";", $_SERVER['HTTP_COOKIE']) as $cookie) {
                list ($key, $value)= explode("=", $cookie);
                $default_values[trim($key)] = urldecode(trim($value));
            }
        } 

        if ( !empty($temp_comment) && is_array($temp_comment) ) {

            $default_values = $temp_comment;

        } else if (!empty($_COOKIE['pivotxcomment'])) {

            $cookie = explode('|', $_COOKIE['pivotxcomment']);

            $default_values['name'] = $cookie[0];
            $default_values['email'] = $cookie[1];
            $default_values['url'] = $cookie[2];
            $default_values['reguser'] = $cookie[3];
            $default_values['notify'] = $cookie[4];
            $default_values['discreet'] = $cookie[5];
            $default_values['rememberinfo'] = 1;
        } else {
            // Check if this is a logged in registered visitor
            require_once $PIVOTX['paths']['pivotx_path'].'modules/module_userreg.php';
            $visitors = new Visitors();
            if ($visitor = $visitors->isLoggedIn()) {
                $default_values['name'] = $visitor['name'];
                $default_values['email'] = $visitor['email'];
                $default_values['url'] = $visitor['url'];
                $default_values['notify'] = $visitor['notify_default'];
                $default_values['discreet'] = (1 - $visitor['show_address']);
            }
        }

        // Posted values should override cookies since they are newer.
        // (The corresponding posted keys start with "piv_".)
        foreach ($_POST as $key => $value) {
            if (substr($key,0,4) == 'piv_') {
                $default_values[substr($key,4)] = urldecode(trim($value));
            }
        }

        // Execute hooks, if present, and (potentially) override existing values.
        $hookname = "remember"; 
        $hook_values = $PIVOTX['extensions']->executeHook('template', $hookname, $default_values );
        if (is_array($hook_values)) {
            $default_values = $hook_values;
        }
    }

    switch($name) {
        case 'all':
            echo "<h1>koekies</h1><pre>cookies:";
            print_r($_COOKIE);
            echo "</pre>";
            break;
        case 'name':
            return (!empty($default_values['name'])) ? encodeText($default_values['name']) : "";
            break;
        case 'email':
            return (!empty($default_values['email'])) ? encodeText($default_values['email']) : "";
            break;
        case 'url':
            return (!empty($default_values['url'])) ? encodeText($default_values['url']) : "";
            break;
        case 'comment':
            return (!empty($default_values['comment'])) ? $default_values['comment'] : "";
            break;
        case 'rememberinfo':
            return (!empty($default_values['rememberinfo'])) ? "checked='checked'" : "";
            break;
        case 'notify':
            return (!empty($default_values['notify'])) ? "checked='checked'" : "";
            break;
        case 'discreet':
            return (!empty($default_values['discreet'])) ? "checked='checked'" : "";
            break;
        case 'reguser':
            return (!empty($default_values['piv_reguser'])) ? $default_values['piv_reguser'] : "";
            break;
    }


}




/**
 * Resets the [[ $page ]] variable back to what it was, before it was
 * set by [[ getpage ]].
 *
 * @see smarty_getpage
 * @return string;
 */
function smarty_resetpage($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $oldpage = $vars['oldpage'];
        
    // Set the 'page' variable in smarty to 'oldpage', as it was before [[ getpage ]]
    if (is_array($oldpage)) {
        $smarty->assign('page', $oldpage);
        foreach($oldpage as $key=>$value) {
            $smarty->assign($key, $value);
        }
    }

    return "";
}



/**
 * Insert a button with a link to the RSS XML feed.
 *
 * @return string
 */
function smarty_rssbutton() {
    global $PIVOTX ;

    // if we've disabled the Atom feed for this weblog, return nothing.
    if ($PIVOTX['weblogs']->get('', 'rss')!=1) {
        return "";
    }

    $filename = makeFeedLink("rss");

    $image    = $PIVOTX['paths']['pivotx_url'].'pics/rssbutton.png' ;
    list( $width,$height ) = @getimagesize( $PIVOTX['paths']['pivotx_path'].'pics/rssbutton.png' ) ;
    $alttext  = __('XML: RSS Feed') ;

    $output   = '<a href="'.$filename.'" title="'.$alttext.'" rel="nofollow" class="badge">';
    $output  .= '<img src="'.$image.'" width="'.$width.'" height="'.$height.'"' ;
    $output  .= ' alt="'.$alttext.'" class="badge" longdesc="'.$filename.'" /></a>' ;

    return $output;
}


/**
 * Displays the search-box
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_search($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);
    $request_method = getDefault($params['request_method'], 'post');
    $request_method = ($request_method != 'post') ? 'get' : 'post'; // Anything not being 'post' is set to 'get'
    $formname = getDefault($params['formname'], __('Search for words used in entries and pages on this website'));
    $fieldname = getDefault($params['fieldname'], __('Enter the word[s] to search for here:'));
    $inputtype = getDefault($params['inputtype'], "text");
    $placeholder = getDefault($params['placeholder'], __('Enter search terms'));

    // Set the placeholder to what we're searching, if we're on a searchpage.
    if ($PIVOTX['parser']->modifier['pagetype'] == "search") {
        $placeholder = htmlspecialchars($PIVOTX['parser']->modifier['uri']);
    }
    
    $placeholder_js = addslashes($placeholder);

    if ($params['template']!='') {
        $url = makeSearchLink("t=".$params['template']);
    } else {
        $url = makeSearchLink();
    }

    $output  = '<form method="'.$request_method.'" action="'.$url.'"  class="pivotx-search">'."\n" ;
    $output .= '<fieldset><legend>'.$formname.'</legend>'."\n" ;
    $output .= '<label for="search">'.$fieldname.'</label>'."\n" ;
    $output .= '<input id="search" type="' . $inputtype . '" name="q" class="searchbox" value="' ;
    $output .= $placeholder.'" onblur="if(this.value==\'\') this.value=\'';
    $output .= $placeholder_js.'\';" onfocus="if(this.value==\'' .$placeholder_js;
    $output .= '\') this.value=\'\'; this.select();return true;" />'."\n" ;

    if($params['button'] !== false) {
        $button_name = getDefault($params['button'], __('Search!'));
        $output .= '<input type="submit" class="searchbutton" value="'.$button_name.'" />' ;
    }

    // If a weblog as been explicitly selected or we are not on a page, set the weblog.
    if (isset($params['weblog'])) {
        $weblog = $params['weblog'];
    } else {
        $weblog = trim(getDefault($_GET['w'], $_POST['w']));
    }
    if ( !empty($weblog) || ($PIVOTX['parser']->modifier['pagetype'] != "page")) { 
        $weblog = getDefault($weblog, $PIVOTX['weblogs']->getCurrent());
        $output .= '<input type="hidden" name="w" value="'.$weblog.'" />'."\n";
    }

    // If a category as been explicitly selected, set the category.
    if (!empty($params['category'])) {
        $output .= '<input type="hidden" name="c" value="'.$params['category'].'" />'."\n";
    }

    // Limit the search to entries or pages
    if (isset($params['only'])) { 
        $output .= '<input type="hidden" name="only" value="'.$params['only'].'" />'."\n";
    }

    $output .= '</fieldset></form>'."\n" ;

    return $output ;
}




/**
 * Returns a link to the current page.
 *
 * @param array $params
 * @return string
 */
function smarty_self($params) {
    global $PIVOTX;

    $params = cleanParams($params);

    if ($params['includehostname']==1) {
        $output = $PIVOTX['paths']['host'];
    } else {
        $output = "";
    }


    $output .= entifyAmpersand($_SERVER['REQUEST_URI']);

    return $output;


}




/**
 * Returns the sitename for the PivotX installation.
 *
 * @return string
 */
function smarty_sitename() {
    global $PIVOTX;

    $output = $PIVOTX['config']->get('sitename');

    return entifyAmpersand($output);
}



/**
 * Returns the sitedescription for the PivotX installation.
 *
 * @return string
 */
function smarty_sitedescription() {
    global $PIVOTX;

    $output = $PIVOTX['config']->get('sitedescription');

    return entifyAmpersand($output);
}



/**
 * Returns the HTML for the SpamQuiz (that should go inside the comment form).
 *
 * @return string
 */
function smarty_spamquiz($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    if ($PIVOTX['config']->get("spamquiz") != 1) {
        return "<!-- SpamQuiz spam protection is disabled in PivotX configuration -->";
    }

    $p_sIformat = getDefault($params['intro'], "\t<div id=\"spamquiz\">
\t\t<div class=\"commentform_row\">
\t\t\t<small>%intro%</small><br />");
    

    $p_sQformat = getDefault($params['format'], "
\t\t\t<label for=\"spamquiz_answer\"><b>%question%</b></label>
\t\t\t<input type=\"text\" class=\"commentinput\" name=\"spamquiz_answer\" id=\"spamquiz_answer\"  %name_value% />
\t\t</div>
\t</div>");

    // If the format parameter contains "%intro%", we skip the intro parameter.
    if (strpos($p_sQformat, "%intro%") !== false) {
        $p_sFormat = $p_sQformat;
    } else {
        $p_sFormat = $p_sIformat . $p_sQformat;
    }

    require_once($PIVOTX['paths']["pivotx_path"]."modules/module_spamkiller.php");

    // Is the entry old enough?
    $entryDate = substr($PIVOTX['db']->entry['date'], 0, 10);
    $then = strtotime($entryDate);
    $secsPerDay = 60*60*24;
    $now = strtotime('now');
    $diff = $now - $then;
    $dayDiff = ($diff/$secsPerDay);
    $numDaysOld = (int)$dayDiff;

    if($numDaysOld<$PIVOTX['config']->get("spamquiz_age")) {
        return "<!-- SpamQuiz disabled - not old enough entry -->";
    }

    $sTheAnswer = $_COOKIE["spamquiz_answer"];

    if(trim($PIVOTX['config']->get("spamquiz_answer")) != $_COOKIE["spamquiz_answer"]) {
        $sTheAnswer = '';
    }

    $sQuestion = $PIVOTX['config']->get("spamquiz_question");
    $sIntro = $PIVOTX['config']->get("spamquiz_explain");
    $p_sFormat = str_replace("%intro%", $sIntro, $p_sFormat);
    $p_sFormat = str_replace("%question%", $sQuestion, $p_sFormat);
    $p_sFormat = str_replace("%name_value%", "value=\"$sTheAnswer\"", $p_sFormat);

    return $p_sFormat;

}


/**
 * Smarty tag for [[ subtitle ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_subtitle($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $subtitle = parse_string($vars['subtitle']);

    // If 'strip=1', we strip html tags from the subtitle.
    if ($params['strip']==1) {
        $subtitle = strip_tags($subtitle);
    }

    return entifyAmpersand($subtitle);

}


/**
 * Smarty tag for [[ chaptername ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_chaptername($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $chaptername = getDefault($vars['page']['chaptername'], $vars['chaptername']);

    return entifyAmpersand($chaptername);

}


/**
 * Display a small tagcloud.
 *
 * @param array $params
 * @return string
 */
function smarty_tagcloud($params) {
    global $PIVOTX;

    $params = cleanParams($params);

    $minsize = getDefault($params['minsize'], $PIVOTX['config']->get('tag_min_font'));
    $maxsize = getDefault($params['maxsize'], $PIVOTX['config']->get('tag_max_font'));
    $amount = getDefault($params['amount'], $PIVOTX['config']->get('tag_cloud_amount'));
    $sep = getDefault($params['sep'], ", " );
    $template = $params['template'];

    $underscore = getDefault($params['underscore'], false);

    if (!empty($params['exclude'])) {
        $exclude = explode(',', $params['exclude']);
    } else { 
        $exclude = "";
    }

    $tagcosmos = getTagCosmos($amount, '', '', $exclude);

    // Abort immediately if there are no tags.
    if($tagcosmos['amount'] == 0) {
        return '';
    }

    // This is the factor we need to calculate the EM sizes. $minsize is 1 em,
    // $maxsize will be ($maxsize / $minsize) EM.. Take care if $tagcosmos['maxvalue'] == $tagcosmos['minvalue']
    if ($tagcosmos['maxvalue'] != $tagcosmos['minvalue']) {
        $factor = ($maxsize - $minsize) / ($tagcosmos['maxvalue'] - $tagcosmos['minvalue']) / $minsize;
    } else {
        $factor = 0;
    }

    $htmllinks = array();

    foreach($tagcosmos['tags'] as $key => $value)   {

        // Calculate the size, depending on value.
        $nSize = sprintf("%0.2f", (1 + ($value - $tagcosmos['minvalue']) * $factor));

        $out_key = $key;
        if ($underscore !== false) {
                $out_key = str_replace('_', $underscore, $key);
        }

        $htmllinks[$key] = sprintf("<a style=\"font-size:%sem;\" href=\"%s\" rel=\"tag\" title=\"%s: %s, %s %s\">%s</a>",
            $nSize,
            tagLink($key,$template),
            __('Tag'),
            $out_key,
            $value,
            __('Entries'),
            $out_key
        );
    }

    $output = "<div id='tagcloud' style='font-size: {$minsize}px;'>";
    $output .= implode($sep, $htmllinks);

    $link = tagLink('sometag', $template);
    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $link = str_replace('t=sometag', 'x=tagpage', $link);
    } else {
        $prefix = getDefault($PIVOTX['config']->get('localised_tag_prefix'), 'tag');
        $link = str_replace("$prefix/sometag", "tags", $link);
    }

    if($tagcosmos['amount']>$amount) {
        $output .= sprintf('<em>(<a href="%s">%s</a>)</em>', $link, __('all'));
    }
    
    $output .= "</div>";

    return $output;

}


/**
 * Get a concise list of the entry's tags.
 *
 * @return string The text to display.
 * @param string $text The output format. The default
 *  value is "Used tags: %tags%". (or the localised version thereof)
 * @param string $sep The separator between the tags.
 *  The default value is ", ".
 */
function smarty_tags($params, &$smarty) {

    $params = cleanParams($params);

    $text = getDefault($params['text'], __('Used tags').": %tags%" );
    $sep = getDefault($params['sep'], ", " );
    $prefix = getDefault($params['prefix'], "" );
    $postfix = getDefault($params['postfix'], "" );

    $underscore = getDefault($params['underscore'], false);
    if ($params['textonly']==true) {
        // Just the tags, no HTML, no links.. 
        $tags = getTags(false, "", false, $underscore);
    } else {
        // Output with links and stuff.
        $tags = getTags(true, "", false, $underscore);
    }

    if (count($tags)>0) {
        $output = implode($sep, $tags);
        $output = str_replace("%tags%", $output, $text);
        $output = $prefix.$output.$postfix;
    } else {
        $output = '';
    }

    return $output;

}


/**
 * Returns the local absolute URL to the template directory.
 *
 * @param array $params
 * @return string
 */
function smarty_template_dir($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();
    $templatedir = $vars['templatedir'];

    if ( empty($templatedir) || ($templatedir=="/") || ($templatedir==".") || ($params['base']==true) ) {
        $path = $PIVOTX['paths']['templates_url'];
    } else {
        $path = $PIVOTX['paths']['templates_url'] . $templatedir . '/' ;
    }

    return $path;
}



/**
 * Adds the textile editor thingamajig to the comments form. It's somewhat
 * confusingly called [[ textilepopup ]] for backwards compatibility.
 *
 * @return string
 */
function smarty_textilepopup() {
    global $PIVOTX;


    if ($PIVOTX['weblogs']->get('', 'comment_textile')==1) {

        $tageditorpath = $PIVOTX['paths']['pivotx_url']."includes/markitup/";

        // If the hook for the jquery includes in the header was not yet
        // installed, do so now..
        $PIVOTX['extensions']->addHook('after_parse', 'callback', 'jqueryIncludeCallback');

        // Insert the link to the CSS file as a Hook extension.
        $PIVOTX['extensions']->addHook(
            'after_parse',
            'insert_before_close_head',
            "\t<link rel='stylesheet' type='text/css' href='{$tageditorpath}markitup-mini.css'/>\n"
            );
        $output .= "<script type='text/javascript' src='{$tageditorpath}jquery.markitup.js'></script>\n";
        $output .= "<script type='text/javascript' src='{$tageditorpath}set.js'></script>\n";
        $output .= "<script language='javascript' type='text/javascript'>\n";
        $output .= "jQuery(function($) {
                        jQuery('#piv_comment').markItUp(markitupminitextile);             
                    });";
        $output .= "</script>";


    } else {
        $output='';
    }

    return $output;

}


/**
 * Smarty tag for [[ title ]].
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_title($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    $title = parse_string($vars['title']);

    // If 'strip=1', we strip html tags from the title.
    if ($params['strip']==1) {
        $title = strip_tags($title);
    }

    return entifyAmpersand($title);

}

/**
 * Outputs a "live" title depending on the type of page displayed.
 *
 * @param array $params
 * @param object $smarty
 */
function smarty_live_title($params, &$smarty) {

    // Possible pagetypes: 'tag', 'search', 'tagpage'
    
    $title = ''; // FIXME - just a placeholder

    return entifyAmpersand($title);

}


/**
 * Makes a link to the trackbacks on the current entry.
 */
function smarty_trackbacklink($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();

    $link = makeFileLink($vars['entry'], '', 'track');

    $trackcount=intval($vars['trackcount']);

    $text0 = getDefault($params['text0'], __("No trackbacks"));
    $text1 = getDefault($params['text1'], __("One trackback"));
    $textmore = getDefault($params['textmore'], __("%num% trackbacks"));

    // special case: If comments are disabled, and there are no
    // trackbacks, just return an empty string..
    if ( ($trackcount == 0) &&  ($vars['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$trackcount)];
    $trackcount = $PIVOTX['locale']->getNumber($trackcount);

    $trackcount = str_replace("%num%", $trackcount, $text);
    $trackcount = str_replace("%n%", $vars['trackcount'], $trackcount);

    $tracknames=$vars['tracknames'];

    $weblog = $PIVOTX['weblogs']->getWeblog();
    if ($weblog['comment_pop']==1) {
        $output = sprintf("<a href='%s' ", $link);
        $output.= sprintf("onclick=\"window.open('%s', 'popuplink', 'width=%s,height=%s,directories=no,location=no,scrollbars=yes,menubar=no,status=yes,toolbar=no,resizable=yes'); return false\"", $link, $weblog['comment_width'], $weblog['comment_height']);
        $output.= sprintf(" title=\"%s\" >%s</a>",$tracknames, $trackcount);
    } else {
        $output=sprintf("<a href=\"%s\" title=\"%s\">%s</a>", $link, $tracknames, $trackcount);

    }

    return $output;
}

/**
 * Inserts the trackback URL for the current entry.
 *
 * The classes "pivotx-tracklink-text" and "pivotx-tracklink-url" can be used to style
 * the output.
 */
function smarty_tracklink($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();

    if (isset($vars['entry'])) {
        $entry = $vars['entry'];
    } else {
        debug("The tracklink tag only works for entries");
        return "";
    }
 
    // Initialise the IP blocklist.
    $blocklist = new IPBlock();
 
    // check for entry's allow_comments, blocked IP address ...
    if ( (isset($entry['allow_comments']) && ($entry['allow_comments']==0)) ||
            ($blocklist->isBlocked($_SERVER['REMOTE_ADDR']))  ) {
        return "";
    }

    $params = cleanParams($params);

    $format = getDefault($params['format'], 
            '<p><span class="pivotx-tracklink-text">' . __('Trackback link') . ': </span>' . 
            '<span class="pivotx-tracklink-url">%url%</span></p>');

    $tb_url = $PIVOTX['paths']['host'] . makeFileLink($entry['code'], '', '');
    $trackback = getDefault($PIVOTX['config']->get('localised_trackback_name'), "trackback");
    if ($PIVOTX['config']->get('mod_rewrite')==0) {
        $tb_url .= "&amp;$trackback";
        $tb_getkey_url = $tb_url . "&amp;getkey";
    } else {
        $tb_url .= "/$trackback/";
        $tb_getkey_url = $tb_url . "?getkey";
    }
    if ($PIVOTX['config']->get('hardened_trackback') != 1)  {
        $output = str_replace("%url%", $tb_url, $format);
    } else {
        $tb_url = "<span id=\"tbgetter_%n%\">".__('Please enable javascript to generate a trackback url')."</span>";
        $tb_url .= "<script type=\"text/javascript\" src=\"$tb_getkey_url\"></script>\n";
        $tburl_gen = "<a href=\"#\"".
            " title=\"".__('Note: The url is valid for only 15 minutes after you opened this page!')."\"".
            " onclick=\"showTBURL_%n%(\'tbgetter_%n%\'); return false;\">".__('Click to view the trackback url')."</a>";
        $tb_url .= "\n<script type=\"text/javascript\">/*<![CDATA[*/\n".
            "showTBURLgen_%n%('tbgetter_%n%', '$tburl_gen');\n/*]]>*/</script>\n";

        $tb_url = str_replace("%n%", $entry['code'], $tb_url);
        $output = str_replace("%url%", $tb_url, $format);
    }

    return $output;

}

/**
 * Outputs a text string with the number of trackbacks for the current entry 
 * whether it is in a subweblog or on an entry page.
 */
function smarty_trackcount($params, &$smarty) {
    global $PIVOTX;

    $vars = $smarty->get_template_vars();

    if (isset($vars['entry'])) {
        $entry = $vars['entry'];
    } else {
        debug("The trackcount tag only works for entries");
        return "";
    }
    $trackcount = $entry['trackcount'];

    $text0 = getDefault($params['text0'], __("No trackbacks"));
    $text1 = getDefault($params['text1'], __("One trackback"));
    $textmore = getDefault($params['textmore'], __("%num% trackbacks"));

    // special case: If comments are disabled, and there are no
    // trackbacks, just return an empty string..
    if ( ($trackcount == 0) && ($entry['allow_comments'] == 0) )  {
        return "";
    }

    $text = array($text0, $text1, $textmore);
    $text = $text[min(2,$trackcount)];
    $trackcount = $PIVOTX['locale']->getNumber($trackcount);

    $trackcount = str_replace("%num%", $trackcount, $text);
    $trackcount = str_replace("%n%", $entry['trackcount'], $trackcount);

    return $trackcount;
}

/**
 * Inserts a list of the names of people who left a trackback to the current entry.
 */
function smarty_tracknames() {
    global $PIVOTX;

    $tracknames=$PIVOTX['db']->entry['tracknames'];

    return $tracknames;
}



/**
 * Display a Tag, as used in the introduction or body
 *
 * @param string $tag
 * @param string $link
 * @param string $template
 * @return string
 */
function smarty_tt($params) {

    $params = cleanParams($params);    
    
    $tag = $params['tag'];
    $link = $params['link'];
    $template = getDefault($params['template'],'');

    $underscore = getDefault($params['underscore'], false);

    if(strlen($link) > 0) {
        // If the external link doesn't have a protocol prefix, add it.
        if (strpos($link,"tp://") === false) {
            $link = "http://".$link;
        }
        $tag_url = $link;
        $title = __('Tagged external link');
    } else {
        $tag_url = tagLink($tag,$template);
        $title = __('Entries tagged with');
    }

    if (!empty($params['hrefonly'])) {
        $output = $tag_url;
    } else {
        
        if ($underscore != false) {
            $tag = str_replace("_", $underscore, $tag);
        }
    
        $output = sprintf("<a rel='tag' class='pivotx-taglink' href='%s' title='%s: %s'>%s</a>", 
            $tag_url, $title, $tag, $tag);
    }

    return $output;

}


/**
 * Returns the uid for the current entry
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_uid($params, &$smarty) {

    $vars = $smarty->get_template_vars();

    return $vars['uid'];
}



/**
 * Returns the local absolute URL to the upload directory.
 *
 * @return string
 */
function smarty_upload_dir() {
    global $PIVOTX;

    return $PIVOTX['paths']['upload_base_url'];
}



/**
 * Returns information about the author of the current entry or page. 
 *
 * It takes one optional parameter "field" to select what information to 
 * return. There is one special value "emailtonick" that will produce an 
 * encoded link to the email address.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_user($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    $vars = $smarty->get_template_vars();
    $user = $PIVOTX['users']->getUser($vars['user']);
    $field = $params['field'];

    if (!$user) {
        $output = $vars['user'];
    } else if ($field=="") {
        $output = $user['username'];
    } else if ($field=="emailtonick") {
        if ($user['nickname']!="") {
            $output = encodeMailLink($user['email'], $user['nickname'] );
        } else {
            $output = encodeMailLink($user['email'], $user['username']);
        }
    } else {
        if (isset($user[$field])) {
            $output = $user[$field];
        } else {
            $output = '';
        }

    }

    return $output;
}


/**
 * Returns a list of user.
 *
 * @param array $params
 * @return string
 */
function smarty_user_list($params) {
    global $PIVOTX;
    
    $params = cleanParams($params);
    
    $format = getDefault($params['format'], "%nickname%<br />");
    $list_separator = getDefault($params['list_separator'], ", ");
    if (!empty($params['show_level'])) {
        $show_level = explode(',',$params['show_level']);
        $show_level = array_map("trim", $show_level);
    } 
    if (!empty($params['ignore'])) {
        $ignore = explode(',',$params['ignore']);
        $ignore = array_map("trim", $ignore);
    }
    $output= '';
    
    // Get all users
    $users = $PIVOTX['users']->getUsers();
    foreach ($users as $user) {

        if (!empty($params['show_level']) && !in_array($user['userlevel'], $show_level)) {
            continue;
        }
        if (!empty($params['ignore']) && in_array($user['username'],$ignore)) { 
            continue;
        }

        // OK, let's display this user.

        if (strpos($format, '%userleveltext%')) {
            $user_lev = array(
                PIVOTX_UL_NOBODY => __('Inactive user'),
                PIVOTX_UL_MOBLOGGER => __('Moblogger'),
                PIVOTX_UL_NORMAL => __('Normal'),
                PIVOTX_UL_ADVANCED => __('Advanced'),
                PIVOTX_UL_ADMIN => __('Administrator'),
                PIVOTX_UL_SUPERADMIN => __('Superadmin')
            );
            $userleveltext = $user_lev[$user['userlevel']];
        }

        if (strpos($format, '%entrycount%')) { 
            $entrycount = $PIVOTX['db']->get_entries_count(array('user' => $user['username']));
        }

        if (strpos($format, '%categories%')) { 
            $categories = implode($list_separator,$PIVOTX['categories']->allowedCategories($user['username']));
        }

        // Comment from John Schop:
        // These next two lines could probably be replaced by one single function, but I can't figure it out. 
        // $PIVOTX['languages']->getName($user['language']) does not seem to work, cause it always returns English.
        $languages = $PIVOTX['languages']->getLangs();
        $language = $languages[$user['language']];

        $this_output = str_replace("%username%", $user['username'], $format);
        $this_output = str_replace("%email%", $user['email'], $this_output);
        $this_output = str_replace("%userlevel%", $user['userlevel'], $this_output);
        $this_output = str_replace("%userleveltext%", $userleveltext, $this_output);
        $this_output = str_replace("%entrycount%", $entrycount, $this_output);
        $this_output = str_replace("%nickname%", $user['nickname'], $this_output);
        $this_output = str_replace("%language%", $language, $this_output);
        $this_output = str_replace("%image%", $user['image'], $this_output);
        $this_output = str_replace("%text_processing%", $user['text_processing'], $this_output);
        $this_output = str_replace("%categories%", $categories, $this_output);

        $output .= "\n".$this_output;
    }    

    return $output;

}


/**
 * Returns the 'via' information from the extended entry form as a link with 
 * a title.
 *
 * @param array $params
 * @return string
 */
function smarty_via($params, &$smarty) {

    $params = cleanParams($params);
    
    $format = getDefault($params['format'], "[<a href='%link%' title='%title%'>via</a>]");
    
    $vars = $smarty->get_template_vars();

    if (strlen($vars['vialink']) > 4 ) {

        $output = $format;
        $output = str_replace("%link%", $vars['vialink'], $output);
        $output = str_replace("%title%", cleanAttributes($vars['viatitle']), $output);

        return $output;

    } else {

        return '';

    }

}


/**
 * Returns the text for a (sub)weblog.
 * 
 * The subweblog tag is a block tag, which means that it always has to 
 * have an accompanying closing subweblog tag. What's inside the tag is used to 
 * render the entries in that weblog. In fact, the contents can be seen as the 
 * template that is used for each entry. PivotX loops over the entries, and 
 * renders each one, using this 'sub template'. 
 *
 * @param array $params
 * @param string $format
 * @param object $smarty
 * @return string
 */
function smarty_weblog($params, $format, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);

    // This function gets called twice. Once when enter it, and once when
    // leaving the block. In the latter case we return an empty string.
    if (!isset($format)) { return ""; }

    // Store the template variables, so whatever happens in the subweblog
    // can't screw up the rest of the page.
    $templatevars = $smarty->get_template_vars();

    $output = cms_tag_weblog($params, $format);

    // Restore the saved template variables..
    $smarty->_tpl_vars = $templatevars;

    return $output;

}


/**
 * Inserts a linked list to the the different weblogs.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_weblog_list($params, &$smarty) {
    global $PIVOTX;

    $params = cleanParams($params);
    $aExclude= array();
    if(!empty($params['exclude'])) {
        $aExclude = explode(",", $params['exclude']);
	$aExclude = array_map("trim", $aExclude);
	$aExclude = array_map("safe_string", $aExclude);	
    }
    
    $Current_weblog = $PIVOTX['weblogs']->getCurrent();
    
    $format = getDefault($params['format'], "<li %active%><a href='%link%' title='%payoff%'>%display%</a></li>");
    $active = getDefault($params['current'], "class='activepage'");

    $output = array();

    $weblogs = $PIVOTX['weblogs']->getWeblogs();
    
    //echo "<pre>\n"; print_r($weblogs); echo "</pre>";

    foreach ($weblogs as $key=>$weblog) {

        if(in_array(safeString($weblog['name']), $aExclude)) {
            continue;
        }

        $this_output = $format;
        
        $this_output = str_replace("%link%" , $weblog['link'], $this_output);
        $this_output = str_replace("%name%" , $weblog['name'], $this_output);
        $this_output = str_replace("%display%" , $weblog['name'], $this_output);
        $this_output = str_replace("%payoff%" , cleanAttributes($weblog['payoff']), $this_output);
        $this_output = str_replace("%internal%" , $key, $this_output);

        if ($Current_weblog == $key) {
            $this_output = str_replace("%active%" , $active, $this_output); 
        } else {
            $this_output = str_replace("%active%" , "", $this_output);             
        }

        $output[$weblog['name']] .= $this_output;

    }
    
    if($params['sort'] == "title") {
        ksort($output);
    }  
    
    return stripslashes(implode("\n", $output));

}


/**
 * Returns the ID of the current weblog.
 *
 * @return string
 */
function smarty_weblogid() {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->getCurrent();

    return $output;
}


/**
 * Returns the subtitle (payoff) of the current weblog. It takes one optional 
 * parameter "strip" which if equal to one, will remove all HTML tags from the 
 * output.
 *
 * @param array $params
 * @return string
 */
function smarty_weblogsubtitle($params) {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->get('', 'payoff');

    if ($params['strip']==true) {
        $output = strip_tags($output);
    }

    return entifyAmpersand($output);
}


/**
 * Returns the title (name) of the current weblog. It takes one optional 
 * parameter "strip" which if equal to one, will remove all HTML tags from the 
 * output.
 *
 * @param array $params
 * @return string
 */
function smarty_weblogtitle($params) {
    global $PIVOTX;

    $output=$PIVOTX['weblogs']->get('', 'name');

    if ($params['strip']==true) {
        $output = strip_tags($output);
    } else if (!empty($params['internal'])) {
        $output = $PIVOTX['weblogs']->getCurrent();
    }

    return entifyAmpersand($output);
}



/**
 * Inserts a block with the enabled widgets.
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_widgets($params, &$smarty) {
    global $PIVOTX;

    $output = "";

    $PIVOTX['extensions']->executeHook('widget', $output, array('style'=>$params['forcestyle']));

    return $output;

}

/**
 * Return localised 'yes' or 'no' dependant on $params['value']
 *
 * @param array $params
 * @param object $smarty
 * @return string
 */
function smarty_yesno($params, &$smarty) {

    if ($params['value']==1) {
        return __('Yes');
    } else {
        return __('No');
    }

}

/**
 * Get a filtered list of entries with a pager - assign the list, filter and pager to smarty variables
 *
 * The $params array can have the following keys <br />
 * - 'full': Determines if the returned entries should be full (contain all fields), the default, or be reduced. (true/false) <br />
 * - 'show': Amount of entries to read. <br />
 * - 'offset': The offset from the beginning of the filtered and sorted/ordered array. <br />
 * - 'cats': Filter entries by category/ies. <br />
 * - 'extrafields': Filter entries by extrafields. <br />
 * - 'user': Filter entries by user(s). <br />
 * - 'status': Filter entries by status. <br />
 * - 'order': Select random, asc(ending) or des(cending).  <br />
 * - 'orderby': Default is date, but any entry field (e.g. code/uid) can be used. <br />
 * - 'date': A date range - day, month or year. <br />
 * - 'start'/'end': A start/end date. <br />
 *
 * 'cats', 'extrafields' and 'user' can either be (comma separated) strings or arrays.
 * 
 * @param array $params
 * @return array
 */
function smarty_adminentrylist($params, &$smarty) {
    global $PIVOTX;

    $base_params = cleanParams($params);
    
    $template_vars = $smarty->_tpl_vars;
    
    $dbmodel = '';
    $adminentrytype = '';

    // TODO: make this less dependent on the custom entrytypes
    // or alternatively make the custom entrytypes part of the core
    $PIVOTX['extensions']->executeHook('extension_dbmodel', $dbmodel, $template_vars);

    if(empty($dbmodel['et_name']) || $dbmodel['et_name']=='entries') {
        // this is for the normal case where we have the entrytype "entries"
        $dbmodel['et_name'] = 'entries';
        $db = &$PIVOTX['db'];
        $adminentrytype = array(
            'listpage' => 'entries',
            'editpage' => 'entry',
            'addpage' => 'entry',
            'deletepage' => 'entry',
            'entrytype' => array(
                'et_uid' => 0,
                'et_name' => 'entries',
                'et_displayname' => __('Entries'),
                'et_table' => 'entries',
                'et_description' => '',
                'status' => 0,
                'last_updated' => 0
            )
        );
    } else {
        // check if entrytype exists and is loaded
        // PLEASE NOTE: this is a three part logic check
        // that only has to fail when the extension does not exist
        // when it exists the model will be loaded if it's not there yet

        // TODO: make sure that the extension exists so this check can be simpler
        debug('checking for '. $dbmodel['et_name'] .' model');

        $extension_exists = class_exists('ETInstance');
        if(!array_key_exists($dbmodel['et_name'], $PIVOTX) && $extension_exists) {
            // the model is not loaded, but the extension exists
            // load the model to fix that
            $PIVOTX[$dbmodel['et_name']] = new ETInstance($dbmodel);
            debug('created model '. $dbmodel['et_name']);
        } elseif(!$extension_exists) {
            // the expected extension was not found
            // this is a fatal error
            echo("there's something wrong with smarty_adminentrylist - the expected extension for entrytypes is missing.");
            die();
        }
        // now it exists, so we can continue

        $db = &$PIVOTX[$dbmodel['et_name']];
        
        $adminentrytype = array(
            'listpage' => 'et'.$dbmodel['et_name'],
            'editpage' => 'etedit'.$dbmodel['et_name'],
            'addpage' => 'etadd'.$dbmodel['et_name'],
            'deletepage' => 'etdel'.$dbmodel['et_name'],
            'entrytype' => $dbmodel
        );
    }
    // TODO: end of the custom entrytypes dependency
    $entryfilter = array();
    $entrypager = array();
    $entrylist = array();


    if(!isset($base_params['full'])) {
        $base_params['full'] = false;
    } else {
        $base_params['full'] = true;        
    }
    $entryfilter['base_smarty_parms'] = $base_params;
    
    // reset filters
    if($_REQUEST['clear']=='clear') {
        // we don't want no leftovers
        unset($_REQUEST['code']);
        
        // unset search vars
        unset($_REQUEST['search']);

        // clear session search too  
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterSearch');

        // unset filter vars
        unset($_REQUEST['filterCategory']);   
        unset($_REQUEST['filterAuthor']);    
        unset($_REQUEST['filterStatus']);

        // clear session filters too
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterCategory');
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterAuthor');
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterStatus');
        
        // clear pager on any reset
        $_REQUEST['go'] = 1;
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPage');
        unset($entrypager);
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPager');
    }
    
    // load session filters if available
    $entryfilter['filtercategory']['selected'] = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterCategory');
    $entryfilter['filterauthor']['selected'] = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterAuthor');
    $entryfilter['filterstatus']['selected'] = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterStatus');
    
    // load session search if available
    $entryfilter['filtersearch']['search'] = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterSearch');

    // load previous pager if available
    $entrypager = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterPager');

    // prepare the filter and search queries - override the session if it's already set
    if (isset($_REQUEST['filterCategory']) && ($_REQUEST['filterCategory']!="" && ($_REQUEST['filterCategory']!="*"))) {
        $base_params['cats'] = $_REQUEST['filterCategory'];
        $entryfilter['filtercategory']['selected'] = $_REQUEST['filterCategory'];
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterCategory', $entryfilter['filtercategory']['selected']);
        $_REQUEST['go'] = 1;
    } elseif(isset($_REQUEST['filterCategory']) && ($_REQUEST['filterCategory']=="*")) {
        $base_params['cats'] = '';
        $entryfilter['filtercategory']['selected'] = '';
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterCategory');
        $_REQUEST['go'] = 1;
    } else {
        $base_params['cats'] = $entryfilter['filtercategory']['selected'];        
    }
    if (isset($_REQUEST['filterAuthor']) && ($_REQUEST['filterAuthor']!="") && ($_REQUEST['filterAuthor']!="*") && !$force_user) {
        $base_params['user'] = $_REQUEST['filterAuthor'];
        $entryfilter['filterauthor']['selected'] = $_REQUEST['filterAuthor'];    
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterAuthor', $entryfilter['filterauthor']['selected']);
        $_REQUEST['go'] = 1;
    } elseif(isset($_REQUEST['filterAuthor']) && ($_REQUEST['filterAuthor']=="*")) {
        $base_params['user'] = '';
        $entryfilter['filterauthor']['selected'] = '';
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterAuthor');
        $_REQUEST['go'] = 1;
    } else {
        $base_params['user'] = $entryfilter['filterauthor']['selected'];        
    }
    if (isset($_REQUEST['filterStatus']) && ($_REQUEST['filterStatus']!="") && ($_REQUEST['filterStatus']!="*")) {
        $base_params['status'] = $_REQUEST['filterStatus'];
        $entryfilter['filterstatus']['selected'] = $_REQUEST['filterStatus'];    
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterStatus', $entryfilter['filterstatus']['selected']);
        $_REQUEST['go'] = 1;
    } elseif(isset($_REQUEST['filterStatus']) && ($_REQUEST['filterStatus']=="*")) {
        $base_params['status'] = '';
        $entryfilter['filterstatus']['selected'] = '';
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterStatus');
        $_REQUEST['go'] = 1;
    } else {
        $base_params['status'] = $entryfilter['filterstatus']['selected'];        
    }

    if(empty($_REQUEST['search']) && !empty($entryfilter['filtersearch']['search'])) {
        $_REQUEST['search'] = $entryfilter['filtersearch']['search'];
    }

    $absmax = $db->get_entries_count();
    $entrypager['allentries'] = $absmax;
    
    if($_REQUEST['search'] || $entryfilter['filtercategory']['selected'] || $entryfilter['filterauthor']['selected'] || $entryfilter['filterstatus']['selected']) {
        // Read absworking from filter
        $entrypager['num_entries_params'] = $base_params;
        $absworking = $db->get_entries_count($base_params);
    } else {
        $absworking = $absmax;
    }
    
    $entrypager['numentries'] = $absworking;
    
    $show = (isset($_REQUEST['show']) && ($_REQUEST['show']!=0)) ? $_REQUEST['show'] : $PIVOTX['config']->get('overview_entriesperpage') ;
    
    $entrypager['show'] = $show;

    $numpages = (int)ceil(($absworking / abs($show)));
    $entrypager['numpages'] = $numpages;
    $offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0 ;

    if(isset($_REQUEST['go']) && is_numeric($_REQUEST['go'])) {
        $pagenr = (int)$_REQUEST['go'];
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPage', $pagenr);
    } elseif(isset($_REQUEST['go']) && in_array($_REQUEST['go'], array('first', 'last'))) {
        $pagenr = $_REQUEST['go'];
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPage', $pagenr);
    } elseif($tmppg = $PIVOTX['session']->getValue($dbmodel['et_name'].'-filterPage')) {
        $pagenr = $tmppg;
    } else {
        $pagenr = 1;
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPage', $pagenr);
    }
    
    if($pagenr == 'last') {
        $offset = ($numpages-1) * $show;
        $pagenr = $numpages;
    } elseif($pagenr == 'first' || $pagenr < 1) {
        $offset = 0;
    } elseif(is_numeric($pagenr)) {
        $offset = ($pagenr-1) * $show;
    }
    $entrypager['offset'] = $offset;
    $entrypager['lastpage'] = $numpages;
    $entrypager['currentpage'] = (is_numeric($pagenr))?$pagenr:1;

    if (isset($_REQUEST['first'])) {
        $offset = $absworking - $show;
    }

    $base_params['show'] = $show;
    $base_params['offset'] = $offset;

    //Sort entries change

    if(isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], array('uid', 'status', 'title', 'category', 'user', 'date', 'commment_count', 'trackback_count'))) {
        $base_params['orderby'] = $_REQUEST['sort'];
        $entrypager['orderby'] = $base_params['orderby'];
        if (isset($_REQUEST['reverse'])) {
            $base_params['order'] = 'asc';
            $entrypager['order'] = $base_params['order'];
        } else {
            $base_params['order'] = 'desc';
            $entrypager['order'] = $base_params['order'];
        }
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPager', $entrypager); 
    } elseif(!empty($entrypager['orderby'])) {
        $base_params['orderby'] = $entrypager['orderby'];
        $base_params['order'] = $entrypager['order'];
    } else {
        //set initial values for sort values
        $base_params['orderby'] = 'date';
        $base_params['order'] = 'desc';
        $entrypager['orderby'] = $base_params['orderby'];
        $entrypager['order'] = $base_params['order'];
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterPager', $entrypager); 
    }

    $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
    $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);

    // Check if we need to 'force' a user filter, based on the
    // 'show_only_own_userlevel' settings..
    if ( $currentuserlevel <= $PIVOTX['config']->get('show_only_own_userlevel') ) {
        $base_params['user'] = $currentuser['username'];
        $force_user = true;
    } else {
        $force_user = false;
    }
    
    //debug_printr($base_params);
    
    if ( (isset($_REQUEST['search'])) && (strlen($_REQUEST['search'])>1) ) {
        if($dbmodel['et_name']=='entries') {
            $overview_arr = searchEntries($_REQUEST['search']);
        } else {
            $overview_arr = $db->searchEntries($_REQUEST['search']);
        }
        
        $entryfilter['filtersearch']['search'] = $_REQUEST['search'];
        $PIVOTX['session']->setValue($dbmodel['et_name'].'-filterSearch', $entryfilter['filtersearch']['search']); 
        
        $offset =  0;
        $absmax = $show = 1;
        $entrypager['offset'] = $offset;
        $entrypager['show'] = $show;
        $entrypager['numpages'] = $show;
        if(!is_array($overview_arr)) {
            $overview_arr = array(); 
        }
    } else {
        $overview_arr = $db->read_entries($base_params);
    }

    // Add filters for the categories.
    $cats = $PIVOTX['categories']->getCategories();
    if(is_array($cats)) {
        foreach ($cats as $cat) {
            $entryfilter['filtercategory']['categories'][] = $cat;
        }
    }

    // Add filters for users, but only if we didn't 'force' a user. 
    if ($force_user=="") {
        $users = new Users();
        $usernames = $PIVOTX['users']->getUsernames();
        if(is_array($usernames)) {
            foreach ($usernames as $username) {
                $user = $PIVOTX['users']->getUser($username);
                $entryfilter['filterauthor']['users'][$username] = $user;
            }
        }
    }
    
    // add status filter
    $statuses = array(
        array('status' => 'publish', 'displaystatus' => 'Published'),
        array('status' =>'timed', 'displaystatus' => 'Timed'),
        array('status' =>'hold', 'displaystatus' => 'Held'),
    );
    if(is_array($statuses)) {
        $entryfilter['filterstatus']['statuses'] = $statuses;
    }

    foreach($overview_arr as $key => $entry) {
        // Get the author (user) of entry.
        $entryuser = $PIVOTX['users']->getUser($entry['user']);
        $entry['entryuser'] = $entryuser;
        $entry['author'] = (isset($entryuser['nickname']))?$entryuser['nickname']:$entryuser['user'];
        $entry['editable'] = $PIVOTX['users']->allowEdit('entry', $entry['user']);
        $entry['commeditable'] = $PIVOTX['users']->allowEdit('comment', $entry['user']);
        $entry['trackeditable'] = $PIVOTX['users']->allowEdit('trackback', $entry['user']);
       
        // Handle category display
        if (!is_array($entry['category'])) {
            $entry['category'] = array($entry['category']);
        } 
        $entry['categorynames'] = array();
        foreach($entry['category'] as $eachcat) {
            $cat = $PIVOTX['categories']->getCategory($eachcat);
            if (isset($cat['display'])) {
                $entry['categorynames'][] = $cat['display'];
            } else {
                if ($eachcat == '') {
                    $entry['categorynames'][] = __("(none)");
                } else {
                    $entry['categorynames'][] = $eachcat;
                }
            }
        }
        $entry['categorynames'] = implode(", ", $entry['categorynames']);
        $entry['categorycount'] = count($entry['category']);
        
        // The prepared entry for output
        $entrylist[$entry['uid']] = $entry;
    }

    $smarty->assign('adminentryfilter', $entryfilter);
    $smarty->assign('adminentrypager', $entrypager);
    $smarty->assign('adminentrylist', $entrylist);
    $smarty->assign('adminentrytype', $adminentrytype);
    
    $smarty->assign('adminentrycsrf', $PIVOTX['session']->getCSRF());

    //debug_printr($_SESSION);
}


/**
 * Check if a given string is available as smarty tag.
 *
 * Example: 
 * [[ if tag_exists('gallery') ]]
 *   (gallery extension is available)
 * [[/if]]
 * 
 */
function tag_exists($tagname) {
    global $PIVOTX;
        
    return ( !empty($PIVOTX['template']->_plugins['function'][ $tagname ]) ||
            !empty($PIVOTX['template']->_plugins['block'][ $tagname ])
        );
    
}



/**
 * @see $smarty->register_resource
 */
function dbGetTemplate($tpl_name, $tpl_source, &$smarty_obj) {

    if (isset($smarty_obj->custom_template[ $tpl_name ])) {
        $tpl_source = $smarty_obj->custom_template[ $tpl_name ];
        return true;
    } else {
        $tpl_source = "";
        return false;
    }

}

/**
 * @see $smarty->register_resource
 */
function dbGetTimestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
     return true;
}

/**
 * @see $smarty->register_resource
 */
function dbGetSecure($tpl_name, &$smarty_obj)
{
    // assume all templates are secure
    return true;
}

/**
 * @see $smarty->register_resource
 */
function dbGetTrusted($tpl_name, &$smarty_obj)
{
    // not used for templates
}




/**
 * Handle Caching.
 *
 * Note: Most of the hooks related to caching are called from module_parser.php,
 * parseTemplate(). This is because this function is a smarty callback, and gets
 * called multiple times for each page (separately for each include as wel as
 * recursive templates like [[body]], [[introduction]] and other block level tags)
 * The only exceptions is clear_cache(), for which we _do_ handle the hooks here.
 *
 * @see http://www.smarty.net/manual/en/section.template.cache.handler.func.php
 */
function pivotxCacheHandler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null) {
    global $PIVOTX, $compressor, $weblogmessage;
      
    // create unique cache key, if we don't have one yet.
    if (empty($cache_id)) {
        $cache_id = "tpl_" . substr(md5($tpl_file.$cache_id.$compile_id),0,10);
    }

    $basename = removeExtension(basename($tpl_file));

    // Set the filename of our cachefile..
    $cachefile = sprintf("%s%s_%s.cache",
        $PIVOTX['paths']['cache_path'],
        $cache_id,
        $basename
        );
        

    switch ($action) {
        
        case 'read':
            // Read a cached page from disk. This is also used for the is_cached() function.

            if (substr($tpl_file, 0, 3)!="db:") {

                if (file_exists($cachefile) && is_readable($cachefile) ) {
                    $cache_content = file_get_contents($cachefile);
                    // debug("read cache: $tpl_file, $cache_id");
                    $result = true;
                } else {
                    $result = false;            
                }

            } else {
                $result = false;
            }
    
            break;
        
        case 'write':
            // save cache to database

            // Do not cache pages with a 'weblogmessage'. 
            if (!empty($weblogmessage)) {
                return "";
            }

            if (substr($tpl_file, 0, 3)!="db:") {
                
                // We split what's to be written to the cache in a $meta and $html part
                list($meta, $html) = explode("}}", $cache_content);
                
                // Execute the hooks, if present.
                $PIVOTX['extensions']->executeHook('after_parse', $html);

                // We need to rewrite before we write to the cache
                $os   = OutputSystem::instance();
                $html = $os->rewriteHtml($html);
                
                // If speedy_frontend is enabled, we compress our output here.
                if ($PIVOTX['config']->get('minify_frontend')) {
                    $minify = new Minify($html);
                    $html = $minify->minifyURLS();
                }                
               
                // Put $meta and $html back together..
                $cache_content = $meta . "}}" . $html;
                
                // Save the file to disk..    
                $fp = fopen($cachefile, "wb");
                fwrite($fp, $cache_content);
                fclose($fp);
            
                // debug("write cache: $tpl_file, $cache_id");
                
                // We set the result to true, because regardless of whether we saved
                // successfully, we _did_ change the $cache_contents.
                $result = true; 
                
            } else {
                // Do not cache db:123456 (these are our own recursive templates)
                $result = false;
            }
            break;
        
        case 'clear':
            
            // debug("clear from cache: $tpl_file, $cache_id");
            
            // Execute the cache_clear hook, if present.
            $PIVOTX['extensions']->executeHook('cache_clear', $basename);
            
            $dir = dir($PIVOTX['paths']['cache_path']);
            while (false !== ($file = $dir->read())) {
                if (strpos($file, ".cache")>0) {  
                    unlink($PIVOTX['paths']['cache_path'].$file);
                }
            }            
            $dir->close();   

            break;
        
        default:
            // error, unknown action
            $return = false;
            break;
    }
    

    return $result;

}

?>
