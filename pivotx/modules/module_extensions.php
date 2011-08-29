<?php
/**
 * Contains the Class that manages Extensions.
 *
 * @package pivotx
 * @subpackage extensions
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
if(!defined('INPIVOTX')){ die('not in pivotx'); }

// Lamer protection
$currentfile = basename(__FILE__);
require dirname(dirname(__FILE__))."/lamer_protection.php";


/**
 * The class that renders pages and handles caching, for all the pages that are
 * seen on the 'front side'.
 *
 */
class Extensions {

    var $active;
    var $filenames;
    var $list;
    var $full_list;
    var $hooks;
    var $hidearray;
    var $scanned;

    /**
     * Initialise the Extensions object.
     *
     * @return Extensions
     */
    function Extensions() {
        global $PIVOTX;

        // If there's a file called 'pivotxsafemode.txt', we'll disable all extensions.
        if (file_exists(dirname(dirname(__FILE__))."/pivotxsafemode.txt")) {
            $this->safemode = true;
        } else {
            $this->safemode = false;
        }

        // Initialize the array with registered Hooks.
        $this->hooks = array();

        // Get the list of activated extensions from Config.
        $this->active = explode("|", $PIVOTX['config']->get('extensions_active'));
        $this->filenames = unserialize($PIVOTX['config']->get('extensions_filenames'));

        // Keep track of the need to update the extension settings..
        $requireupdate = false;

        if (!$this->safemode) {

            // Now, read all activated extensions..
            if (is_array($this->filenames)) {
                foreach($this->filenames as $filenames) {

                    // Make sure we have an array, and not a single filename..
                    if (!is_array($filenames)) {
                        $filenames = array($filenames);
                    }

                    foreach($filenames as $filename) {

                        $filename = $PIVOTX['paths']['extensions_path'] . $filename;

                        if (file_exists($filename) && is_readable($filename)) {
                            ob_start();
                            include_once($filename);
                            $output = ob_get_clean();
                            if (strlen($output) > 0){
                                $name = str_replace($PIVOTX['paths']['pivotx_path'], '.../', $filename);
                                debug("The file $filename contains white-space outside PHP blocks " .
                                    "which can be fatal. Please fix.");
                                debug("generated output: '<em>" . trim($output) . "</em>'");
                            }
                        } else {
                            $requireupdate = true;
                        }
                    }
                }
            }

        }
        
        // If we couldn't load one of the extensions, update the list of
        // activated extensions.  
        if ($requireupdate) {
            $this->setActivated($this->active);
        }

        // No, we have not yet scanned the extensions folder for available extensions.
        $this->list = false;

        $this->hidearray = array();

    }

    /**
     * Get a list of the 'identifiers' as set in the extensions.
     *
     * @param mixed $type
     * @return array
     *
     */
    function getIdentifiers($neededtype="") {

        $identifiers = array();

        // Make sure $neededtype is an array..
        if (!empty($neededtype) && !is_array($neededtype)) {
            $neededtype = array($neededtype);
        }

        foreach($this->list as $type => $extensions) {

            // Skip this type, perhaps..
            if (!empty($neededtype) && !in_array($type, $neededtype) ) {
                continue;
            }
            
            if(is_array($extensions)) {
                foreach($extensions as $ext) {
                    $identifiers[] = $ext['identifier'];
                }
            }
        }

        return $identifiers;

    }

    /**
     * Scan the pivotx/extensions folder, and compile a list of all
     * available extensions.
     *
     * If all goes well, we'll get back a multidimensional array, with all
     * found extensions, grouped by type. Like so:
     *
     * [admin] => Array
     *    (
     *        [0] => Array
     *            (
     *                [extension] => Hello World admin page
     *                [version] => 0.1
     *                [author] => PivotX Team
     *                [email] => admin@pivotx.net
     *                [site] => http://www.pivotx.net
     *                [description] => A simple example to demonstrate the 'Hello world' administration page.
     *                [date] => 2007-05-20
     *                [type] => admin
     *            )
     *
     *    )
     *
     * [hook] => Array
     *    (
     *        [0] => Array
     *            (
     *                [extension] => Hello World hook
     *                [version] => 0.1
     *                [author] => PivotX Team
     *                [email] => admin@pivotx.net
     *                [site] => http://www.pivotx.net
     *                [description] => A simple example to demonstrate 'Hello world' as a Hook Extension.
     *                [date] => 2007-05-20
     *                [type] => hook
     *            )
     *
     *    )
     *
     * [snippet] => Array
     *    (
     *        [0] => Array
     *            (
     *                [extension] => Hello World snippet
     *                [version] => 0.1
     *                [author] => PivotX Team
     *                [email] => admin@pivotx.net
     *                [site] => http://www.pivotx.net
     *                [description] => A simple example to demonstrate 'Hello world' as a Snippet Extension.
     *                [date] => 2007-05-20
     *                [type] => snippet
     *            )
     *
     *    )
     *
     * [widget] => Array
     *    (
     *        [0] => Array
     *            (
     *                [extension] => Hello World widget
     *                [version] => 0.1
     *                [author] => PivotX Team
     *                [email] => admin@pivotx.net
     *                [site] => http://www.pivotx.net
     *                [description] => A simple example to demonstrate 'Hello world' as a Widget Extension.
     *                [date] => 2007-05-20
     *                [type] => widget
     *            )
     *
     *    )
     *
     * @return array
     */
    function scanExtensions() {
        global $PIVOTX;

        $this->list = array();
        $this->full_list = array(
            'admin' => array(),
            'hook' => array(),
            'snippet' => array(),
            'widget' => array()
        );

        $this->__scanFolder($PIVOTX['paths']['extensions_path']);

        // Create $this->list by filtering $this->full_list
        // Keep just one entry for each identifier.
        $identifiers = array_unique(array_merge(array_keys($this->full_list['snippet']),
            array_keys($this->full_list['hook']), array_keys($this->full_list['widget']),
            array_keys($this->full_list['admin'])));
        foreach ($identifiers as $identifier) {
            if (isset($this->full_list['snippet'][$identifier])) {
                $type = 'snippet';
            } elseif (isset($this->full_list['hook'][$identifier])) {
                $type = 'hook';
            } elseif (isset($this->full_list['widget'][$identifier])) {
                $type = 'widget';
            } elseif (isset($this->full_list['admin'][$identifier])) {
                $type = 'admin';
            } else {
                debug("No known extension type for identifier $identifier.");
                continue;
            } 
            $this->list[$type][$identifier] = $this->full_list[$type][$identifier];
        }

        // We have to sort the activated widgets, according to
        // how they were saved:
        if(is_array($this->list['widget'])) {
            usort($this->list['widget'], "widgetSort");
        }
        // We also sort the extensions alphabetically.
        
        foreach(array('widget', 'admin', 'hook') as $key) {
            if(is_array($this->list[$key])) {
                usort($this->list[$key], "extensionSort");
            }
        }

        return $this->list;

    }

    /**
     * Helper function for scanExtensions, to recursively scan folders for
     * available extensions.
     *
     * @param string $dir
     * @return void
     * @see Extensions::scanExtensions
     */
    function __scanFolder($dir) {

        if (!is_readable($dir)) {
                debug("Directory is not readable: $dir");
                return;
        }
	
        $d = dir($dir);

        while (false !== ($entry = $d->read())) {

            // Skip '.' and '..'
            if (($entry==".") || ($entry=="..")) {
                continue;
            }

            if (is_dir($dir.$entry) && (strpos($entry, ".bak")===false) ) {
                // Recursively enter directories, but not if the name contains '.bak'..
                $this->__scanFolder($dir.$entry."/");
            } else {

                // Check if the file is an extension..
                if ($result = $this->__scanFile($dir.$entry)) {
                    $this->full_list[ $result['type'] ][$result['identifier']] = $result;
                }
            }


        }
        $d->close();

    }

    /**
     * Helper function for scanExtensions, to check if files are extensions.
     *
     * @param string $file
     * @return array
     * @see Extensions::scanExtensions
     */
    function __scanFile($file) {
        global $PIVOTX;

        $base = basename($file);
        $ext = getExtension($base);

        // Only .php files can be extensions:
        if ($ext != "php") {
            return false;
        }

        // Check if the name matches one of the four types of extensions:
        if (strpos($base,"admin_")===0) {
            $type = "admin";
        } else if (strpos($base,"hook_")===0) {
            $type = "hook";
        } else if (strpos($base,"snippet")===0) {
            $type = "snippet";
        } else if (strpos($base,"widget_")===0) {
            $type = "widget";
        } else {
            // Nope, this is not an extension.
            return false;
        }

        // If we get to here, it's most likely an extension. See if
        // we can parse the info from it.

        $contents = implode("", file($file));

        // Do a regular expression match for "// - something: something"..
        if(preg_match_all('/\\/\/ - ([a-z]+):(.*)/i', $contents, $match)) {

            $info = array();

            foreach ($match[1] as $i => $key) {
                $info[ trim(strtolower($match[1][$i])) ] = trim($match[2][$i]);
            }

            // Do some last checks to see if we have at least a name, author and description..
            if (isset($info['extension']) && isset($info['author']) && isset($info['description']) ) {

                $basefile = str_replace( $PIVOTX['paths']['extensions_path'], "", $file);

                // Shizzle, we have an extension!
                $info['type'] = $type;
                $info['name'] = safeString($info['extension'], true);
                $info['file'] = $basefile;
                $info['active'] = ( (in_array($info['name'], $this->active) || in_array($info['identifier'], $this->active) ) ? 1 : 0);
                $info['description'] = $this->__scanDocumentation($info, $info['name']);
                if (!isset($info['priority'])) {
                    $info['priority'] = 100;
                }
                if (empty($info['identifier'])) {
                    $info['identifier'] = makeURI($info['extension']);
                } else {
                    $info['identifier'] = makeURI($info['identifier']);
                }

                return $info;

            }

        } else {

            // Nope, not an extension..
            return false;

        }

    }

    /**
     * Helper function for __scanFile, to check if the extension has any 
     * documentation.
     *
     * @param array $info
     * @return string
     * @see Extensions::__scanFile
     */
    function __scanDocumentation($info, $title) {
        global $PIVOTX;

        $title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");

        $docdir = dirname($PIVOTX['paths']['extensions_path'] . $info['file']) . '/docs/';
        if (file_exists($docdir)) {
            $docurl = str_replace($PIVOTX['paths']['extensions_path'], $PIVOTX['paths']['extensions_url'], $docdir);
            $pageurl = makeAdminPageLink('documentation');

            if (file_exists($docdir . 'summary.markdown') ) {
                $info['description'] .= ' [<a href="' . $pageurl . '&amp;file=' . basename(dirname($docurl)) . 
                    '/docs/summary.markdown" class="dialog editor" title="' . $title . '">' . __('quick docs') . '</a>]';
            } else if (file_exists($docdir . 'summary.textile') ) {
                $info['description'] .= ' [<a href="' . $pageurl . '&amp;file=' . basename(dirname($docurl)) . 
                    '/docs/summary.textile" class="dialog editor" title="' . $title . '">' . __('quick docs') . '</a>]';
            } else if (file_exists($docdir . 'summary.html')) {
                $info['description'] .= ' [<a href="' . $docurl . 'summary.html" class="dialog editor" title="' . 
                    $title . '">' . __('quick docs') . '</a>]';
            }

            if (file_exists($docdir . 'index.markdown')) {
                $info['description'] .= ' [<a href="' . $pageurl . '&amp;file=' . basename(dirname($docurl)) . 
                    '/docs/index.markdown" target="_blank" title="' . $title . '">' . __('full docs') . '</a>]';
            } else if (file_exists($docdir . 'index.textile')) {
                $info['description'] .= ' [<a href="' . $pageurl . '&amp;file=' . basename(dirname($docurl)) . 
                    '/docs/index.textile" target="_blank" title="' . $title . '">' . __('full docs') . '</a>]';
            } else if (file_exists($docdir . 'index.html')) {
                $info['description'] .= ' [<a href="' . $docurl . 'index.html" target="_blank" title="' . 
                    $title . '">' . __('full docs') . '</a>]';
            } 

        }

        return $info['description'];
    }

    /**
     * Set a list of extensions that are activated.
     *
     * @param array $identifiers
     */
    function setActivated($identifiers) {
        global $PIVOTX;

        if (!is_array($identifiers)) {
            return false;
        }

        $filenames = array();

        // Check if we already have the full list of all extensions, otherwise get it..
        if ($this->full_list == false) {
            $this->scanExtensions();
        }

        // Get the filenames for each of the extensions..
        foreach ($identifiers as $identifier) {

            // iterate through the $this->list
            foreach ($this->full_list as $type => $extensions) {
                foreach ($extensions as $extension) {
                    if ($extension['name'] == $identifier || $extension['identifier'] == $identifier) {
                        $filenames[$identifier][] = $extension['file'];
                    }
                }
            }

        }

        // Store in config.
        $PIVOTX['config']->set('extensions_active', implode('|', $identifiers));
        $PIVOTX['config']->set('extensions_filenames', serialize($filenames));

        // Clear the cache.
        wipeSmartyCache();

        $this->active = $identifiers;

    }


    /**
     * Get a list of extensions that are activated. The optional filter parameter
     * lets you select the type of extensions to return.
     *
     * @param string $filter
     */
    function getActivated($filter="") {

        $temp_list = array();

        if ($filter=="") {
            return $this->active;
        } else if (is_array($this->filenames)) {

            // Iterate through the filenames, compiling a list that matches the
            // filtering criteria.
            foreach($this->filenames as $identifier => $filenames) {

                // Make sure we have an array, and not a single filename..
                if (!is_array($filenames)) {
                    $filenames = array($filenames);
                }

                foreach($filenames as $filename) {

                    if (strpos($filename, $filter) !== false) {
                        $temp_list[] = $identifier;
                        break;
                    }
                }
            }
        }

        return $temp_list;

    }



    function addHook($type="", $action="", $parameters="") {

        $type = safeString($type, true);
        $action = safeString($action, true);

        // To simply prevent duplicates, we calculate a hash that we use as key
        // for the array of hooks.
        $hookkey = md5($type.$action.serialize($parameters));

        if ($type!="" && $action!="") {

            $this->hooks[$hookkey] =  array(
                'type' => $type,
                'action' => $action,
                'parameters' => $parameters
            );
        }
        
    }

    /**
     * Check if a particular hook has been set. $type can be a simple value like
     * 'before_parse', or a compound one like 'make_link#pages'.
     *
     * @param string $type
     * @return boolean
     */
    function hasHook($type) {

        // Don't do anything if safemode is enabled..
        if ($this->safemode) { return; }

        // If $type is something make_link#pages, we need to split the type and action.
        list($type, $action) = explode("#", $type);

        $my_hooks = $this->getHooks($type, $action);

        return (count($my_hooks)>0);
    }

    function haveHook($type, $action) {
        
    	$my_hooks = $this->getHooks($type, $action);

    	return count($my_hooks) > 0;
    }

    function executeHook($type, &$target, $value="") {

        // Don't do anything if safemode is enabled..
        if ($this->safemode) { return; }

        // Make sure $value is an array..
        if (!is_array($value)) { $value = array($value); }

        // Set the value in $this->value, so we can pass it to a callback, later.
        $this->value = $value;

        // If $type is something make_link#pages, we need to split the type and action.
        if (strpos($type, '#')!==false) {
            list($type, $action) = explode("#", $type);
        } else {
            $action = "";
        }

        // Choose where to go, depending on $type.
        switch($type) {

            case "before_parse":
                $this->executeBeforeParse($target);
                break;

            case "during_parse":
                $this->executeDuringParse($target);
                break;

            case "after_parse":
                $this->executeAfterParse($target);
                break;

            case "configuration_add":
                $this->executeConfigurationAdd($target);
                break;


            case "in_pivotx_template":
                return $this->executeInPivotxTemplate($target);
                break;

            case "widget":
                return $this->executeWidget($target);
                break;

            case "before_checkwarnings":
            case "after_checkwarnings":
            case "before_getwarnings":
            case "before_getmessages":
                return $this->executeMessages($type);
                break;

            default:
                return $this->executeGenericHook($target, $type, $action);    
                break;

            // Et cetera, et cetera..

        }


    }


    /**
     * Execute a 'generic hook'. Basically all hooks of this type work the same:
     * They take some input, call a callback function with that input, and return
     * the output of that function to the caller.
     *
     * Note that the $target is passed by reference, so the callback function can
     * modify the $target, as well as give some output. If this is useful depends on
     * how the hook is used.
     *
     * @param mixed $target
     * @param string $type
     * @param string $action
     */
    function executeGenericHook(&$target, $type, $action) {

        // TODO: For some reason calling debug() from inside this function causes PHP to crash. Figure out why,
        // fix it, and add some debug calls to track progress..

        $my_hooks = $this->getHooks($type, $action);

        $result = false;

        foreach($my_hooks as $hook) {

            if (function_exists($hook['parameters'])) {
                $functionname = $hook['parameters'];
                //echo("Extensions: Processing hook $type (action: $action), running " . $functionname . "().");
                $result = $functionname($target, $this->value);
            } else {
                echo("Extensions: While processing hook $type (action: $action), I couldn't run " . $hook['parameters'] . "(). Not defined.");
            }

        }

        return $result;

    }


    function executeBeforeParse(&$target) {

        $my_hooks = $this->getHooks('before_parse');

        foreach($my_hooks as $hook) {

            switch($hook['action']) {

                case "callback":
                    if (function_exists($hook['parameters'])) {
                        $functionname = $hook['parameters'];
                        $functionname($target);
                    } else {
                        debug("Extensions: Couldn't run " . $hook['parameters'] . "(). Not defined.");
                    }
                    break;

            }

        }

    }



    function executeDuringParse(&$target) {

        $my_hooks = $this->getHooks('during_parse');

        foreach($my_hooks as $hook) {

            switch($hook['action']) {

                case "callback":
                case $target:
                    if (function_exists($hook['parameters'])) {
                        $functionname = $hook['parameters'];
                        $functionname($target);
                    } else {
                        debug("Extensions: Couldn't run " . $hook['parameters'] . "(). Not defined.");
                    }

                    break;

            }

        }

    }




    function executeAfterParse(&$target) {

        $my_hooks = $this->getHooks('after_parse');

        // store outputfilters and do them at the very end
        $outputfilters = array();

        foreach($my_hooks as $hook) {

            $parameters = $hook['parameters'];
            $parameters = $this->fixPaths($parameters);

            switch($hook['action']) {

                case "insert_at_begin":
                    $target = $hook['parameters'] . $target;
                    break;

                case "insert_after_open_head":
                    $target = preg_replace("/<head([^>]*?)>/si", "<head$1>\n".$parameters, $target, 1);
                    break;

                case "insert_before_close_head":
                    $target = preg_replace("/<\/head>/si", $parameters."\n</head>", $target, 1);
                    break;

                case "insert_after_close_head":
                    $target = preg_replace("/<\/head>/si", "</head>\n".$parameters, $target, 1);
                    break;

                case "insert_before_open_body":
                    $target = preg_replace("/\<body([^>]*?)>/si", $parameters."\n<body$1>", $target, 1);
                    break;

                case "insert_after_open_body":
                    $target = preg_replace("/\<body([^>]*?)>/si", "<body$1>\n".$parameters, $target, 1);
                    break;

                case "insert_before_close_body":
                    $target = preg_replace("/<\/body>/si", $parameters."\n</body>", $target, 1);
                    break;

                case "insert_after_close_body":
                    $target = preg_replace("/<\/body>/si", "</body>\n".$parameters, $target, 1);
                    break;

                case "insert_at_end":
                    $target = $target . $parameters;
                    break;

                case "outputfilter":
                    if (function_exists($parameters)) {
                        $outputfilters[] = $parameters;
                    }
                    break;

                case "callback":
                    if (function_exists($parameters)) {
                        $parameters($target);
                    } else {
                        debug("Extensions: Couldn't run " . $parameters . "(). Not defined.");
                    }

                    break;

            }

        }

        // execute the outputfilters
        foreach($outputfilters as $filter) {
            $target = $filter($target);
        }

    }

    /**
     * Execute hooks encountered in PivotX templates. $action is the name of the
     * hook. I.E. when called after [[ hook name='foo' value='bar' ]], $action will
     * be 'foo', and $this->value will be 'bar'
     */ 
    function executeInPivotxTemplate(&$action) {

        // Get the hooks..
        $my_hooks = $this->getHooks('in_pivotx_template');

        // Make sure the $actionname is OK..
        $actionname = safeString($action, true);

        $output = "";
        
        foreach($my_hooks as $hook) {

            // If $hook['action'] == $actionname, this is (one of the) hook we're
            // looking for.
            if ($hook['action']==$actionname) {

                // print("<pre>\n"); print_r($hook); print("\n</pre>\n");

                $result = false;
                
                // Check if we need to do a callback..
                if (function_exists($hook['parameters']['callback'])) {
                    $functionname = $hook['parameters']['callback'];
                    $output .= $functionname($this->value);
                    $result = true;
                }
                
                // Check if we need to add some HTML..
                if (!empty($hook['parameters']['html'])) {
                    $output .= $hook['parameters']['html'];
                    $result = true;
                }
                
                if (!$result) {
                    debug("Extensions: Couldn't run in_pivotx_template->" . $hook['action'] . "(). Not defined.");
                }

            }

        }
        
        return $output;
        

    }



    function executeConfigurationAdd(&$action) {

        $my_hooks = $this->getHooks('configuration_add');

        foreach($my_hooks as $hook) {

            if (function_exists($hook['parameters'][0])) {
                $functionname = $hook['parameters'][0];
                $functionname($action);
            } else {
                debug("Extensions: Couldn't run " . $hook['parameters'] . "(). Not defined.");
            }

        }


    }




    function executeWidget(&$output) {

        $temp_output = array();

        $my_hooks = $this->getHooks('widget');

        foreach($my_hooks as $hook) {

            if (function_exists($hook['parameters'])) {
                $functionname = $hook['parameters'];
                $temp_output[] = $functionname($action);
            } else {
                debug("Extensions: Couldn't run " . $hook['parameters'] . "(). Not defined.");
            }
        }

        $output .= implode("\n", $temp_output);

    }



    function executeMessages($action) {

        $my_hooks = $this->getHooks('messages');

        foreach($my_hooks as $hook) {
            if ($action == $hook['action']) {

                if (function_exists($hook['parameters'])) {
                    $functionname = $hook['parameters'];
                    $functionname();
                } else {
                    debug("Extensions: Couldn't run " . $hook['parameters'] . "(). Not defined.");
                }

            }
        }

    }


    /**
     * Gets a list of hooks, filtered by $type or $action..
     *
     * @param string $type
     * @param string $action
     * @return array
     */
    function getHooks($type="", $action="") {

        if ($type=="") {
            // return them all
            return $this->hooks;
        }

        $my_hooks = array();

        foreach($this->hooks as $hook) {
            if ($hook['type']==$type) {

                // If $action is set, we filter on that as well.
                if (empty($action) || ($hook['action']==$action) ) {
                    $my_hooks[] = $hook;
                }

            }
        }

        return $my_hooks;

    }
    
    

    /**
     * Gets a hooks, identified by $key..
     *
     * @param string $key
     * @return array
     */
    function getHook($key) {

        foreach($this->hooks as $hook) {
            if( (strtolower($hook['action'])==strtolower($key)) || (safeString($hook['action'])==safeString($key)) ) {
                return $hook;
            }
        }

        return false;

    }
    

    /**
     * Translate some paths into the correct ones. We do this translation at the end,
     * because when the hooks are added, $PIVOTX may be not initialised yet.
     *
     * Note: These look like smarty tags but are not.
     *
     * @param string $str
     * @return string
     */
    function fixPaths($str) {
        global $PIVOTX;

        $str = str_replace("[[pivotx_dir]]", $PIVOTX['paths']['pivotx_url'], $str);
        $str = str_replace("[[log_dir]]", $PIVOTX['paths']['log_url'], $str);
        $str = str_replace("[[template_dir]]", $PIVOTX['paths']['templates_path'], $str);

        return $str;

    }


    /**
     * Widgets can slow down a page significantly. For instance if it includes a
     * lot of HTML or if it calls an external javascript file. In the second case,
     * if the server from which you're trying to include the javascript is down,
     * it'll prevent your page from displaying at all.
     *
     * This function will return the HTML code that can be inserted in your page,
     * by either including the requested widget immediately, or by doing so
     * after the page has loaded and is rendered. The four accepted modes are:
     *
     * immediate_file: Insert the file immediately
     * immediate_script: Insert the HTML code to load the script immediately
     * defer_file: Insert the file, after the page has loaded (via Ajax)
     * defer_script: Insert the HTML to load the script after the page is rendered.
     *
     * The deferred javascript method was made (mostly) by Mike Davidson, http://mikeindustries.com
     *
     * Note: if you use 'immediate_file', PHP will not be parsed if it's
     * present in the file. 'defer_file' will parse PHP, though. If you'd like
     * to use PHP in a 'direct' widget, use include() in your widget callback
     * function.
     *
     * @param string $mode
     * @param string $target
     * @param string $wrapstyle
     * @see http://mikeindustries.com/blog/archive/2007/06/widget-deployment-with-wedje
     */
    function getLoadCode($mode, $target, $wrapstyle="") {
        global $PIVOTX;

        // Using static number to ensure that the div's get unique ids.
        static $number = 0;
        $number++;

        if ($this->value['style']!="") {
            // We override the passed $wrapstyle, if the forcestyle='' attribute was used in [[widgets]]
            $wrapstyle = $this->value['style'];
        }

        // Creating a valid unique ID based on the target.        
        $id = str_replace(array('.', '/'), '-', safeString($target, true)) . '-' . $number;

        $output = "\n<!-- start of widget -->\n";

        if($wrapstyle!="") {

            $output .= sprintf("<div class='%s'><div id='%s'></div></div>\n", $wrapstyle, $id);

        } else {

            $output .= sprintf("<div id='%s'></div>\n", $id);

        }



        switch ($mode) {

            /**
             * Load a file immediately
             */
            case 'immediate_file':

                if (file_exists($PIVOTX['paths']['extensions_path'].$target)) {
                    $output .= implode("", file($PIVOTX['paths']['extensions_path'].$target));
                } else {
                    $output .= "'$target' does not exist.";
                }

                break;

            /**
             * Load a file (URL) after the page has finished loading
             */
            case 'defer_file':
            
                // Handling targets (URLs) with queries.
                list ($basetarget,$dummy) = explode('?',  $target);

                if (file_exists($PIVOTX['paths']['extensions_path'].$basetarget)) {

                    // Make sure jQuery is included;
                    $this->addHook('after_parse', 'callback', 'jqueryIncludeCallback');
                    
                    $output .= "<script type='text/javascript'>\n";
                    $output .= "jQuery(function($) {\n";
                    $output .= "\tjQuery('#$id').html('<img src=\"". $PIVOTX['paths']['pivotx_url'] . 
                        "pics/loadingAnimation.gif\" alt=\"\"/>');\n";
                    $output .= sprintf("\tjQuery.get('%s%s', ".
                            "function(data){ jQuery('#%s').html(data); } );\n",
                            $PIVOTX['paths']['extensions_url'],
                            $target,
                            $id
                        );
                    $output .= "});\n";
                    $output .= "</script>\n";

                } else {
                    $output .=  "'$target' does not exist.";
                }

                break;

            /**
             * Load a script immediately
             */
            case 'immediate_script':

                $output .= sprintf("<script type='text/javascript' src='%s'></script>\n", $target);

                break;


            /**
             * Load a script after the page has finished loading
             */
            case 'defer_script':

                $output .= sprintf("<script type=\"text/javascript\">
                (function(){document.write('<div id=\"%s\"></div>');
                s=document.createElement('script');
                s.type=\"text/javascript\";
                s.src=\"%s\";
                setTimeout(\"document.getElementById('%s').appendChild(s)\",1);})()
                </script>\n",
                   $id,
                   $target,
                   $id);

                break;



            default:
                $output .=  "'$mode' is not a valid mode.";
                break;
        }



        $output .= "\n<!-- end of widget -->\n";

        return $output;


    }


    /**
     * Get a form with some defaults set, so extension authors
     * can add forms easier.
     *
     * @param string $key
     * @param string $name
     * @return object Form
     *
     */
    function getAdminForm($key) {
        global $form_titles;

        $hook = $this->getHook($key);

        if ($hook==false) {
            debug("Extension $key is not initialised..");
            return "";
        }
        
        $key = safeString($key);

        /**
         * Give our new form a name.
         */
        $form_titles[$key] = $hook['parameters'][1];

        /**
         * We create the form to display
         */
        $form = new Form($key, "", "Save");

        // No border for this form:
        $form->html['start'] = <<< EOM
        <form  enctype='multipart/form-data'  name='%name%' id='%name%' action="%action%" method='post'>
        <table border='0' cellspacing='0' cellpadding='4' class='formclass' style="border-width: 0px !important;" width="700">
EOM;

        // Set an alternative format for some inputs
        $form->html['checkbox'] = <<< EOM
<tr>
    <td valign='top'>
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign='top' colspan="2">

       <input type='checkbox' name='%name%' id='%name%' value='1' %checked% id='%formname%_%name%' class="noborder" tabindex='%tabindex%' />

       %text%

       %error%
    </td>
</tr>
EOM;

        $form->html['text'] = <<< EOM
<tr>
    <td valign='top' style="white-space: nowrap; width: 150px">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign='top'>
        <input name='%name%' id='%name%' class='%haserror%' type='text' value='%value%' size='%size%' style='%style%' tabindex='%tabindex%' %extra% />

       <p>%text%</p>
       %error%

    </td>
</tr>
EOM;

        $form->html['password'] = <<< EOM
<tr>
    <td valign='top' style="white-space: nowrap">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign='top'>
        <input name='%name%' id='%name%' class='%haserror%' type='password' value='%value%' size='%size%' style='%style%' tabindex='%tabindex%' %extra% />

       <p>%text%</p>
       %error%

    </td>
</tr>
EOM;

        $form->html['select'] = <<< EOM
<tr>
    <td valign='top'>
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign='top'>
        <select name='%name%' id='%name%' size='%size%' class='%haserror%'  %multiple% %extra%  tabindex='%tabindex%' >
            %elements%
        </select>
       %error%
       %text%
    </td>
</tr>
EOM;


        // Skip the submit button
        $form->html['submit'] = "";


        return $form;


    }



    /**
     * Get a list of currently activated extensions, that have their own
     * administration screen.
     *
     * @return array
     */
    function getAdminScreenNames() {

        $names = array();
        
        $my_hooks = array_merge( $this->getHooks('configuration_add'), $this->getHooks('page_add') );
    
        foreach($my_hooks as $hook) {

            /** Each $hook looks something like:
             *  [1] => Array(
             *     [type] => configuration_add
             *     [action] => poll
             *     [parameters] => Array (
             *             [0] => pollAdmin
             *             [1] => Poll administration
             *     )
             *  )
            **/
            
            if ($hook['type']=="configuration_add" && is_array($hook['parameters'])) {
                $names[ 'configuration#section-'.$hook['action'] ] = $hook['parameters'][1];
            } else if ($hook['type']=="page_add" && is_array($hook['parameters'])) {
                $names[ $hook['action'] ] = $hook['parameters'][1];
            }

        }

        natcasesort($names);

        return $names;

    }



    /**
     * Get a single name of a currently activated extension, identified by $key
     *
     * @param string $key
     * @return array
     */
    function getAdminScreenName($key) {

        $my_hook = $this->getHook($key);

        /** The $hook looks something like:
         *  [1] => Array(
         *     [type] => configuration_add
         *     [action] => poll
         *     [parameters] => Array (
         *             [0] => pollAdmin
         *             [1] => Poll administration
         *     )
         *  )
        **/
        
        if (!empty($my_hook['action'])) {
            return $my_hook['action'];
        } else {
            return false;
        }

    }




    /**
     * Gets the HTML for the admin-screen form. It also sets the default
     * value, for ease of use.
     *
     * @param object $form
     * @param array $ext_config
     */
    function getAdminFormHtml($form, $ext_config = array()) {
        global $PIVOTX;

        $config_values = $PIVOTX['config']->getConfigArray();

        // Set some defaults for the form, in case they aren't yet set.
        if (!empty($ext_config)) {
            foreach ($ext_config as $key=>$value) {
                if (!isset($config_values[$key])) {
                    $config_values[$key] = $value;
                }
            }
        }

        $form->setValues($config_values);
        $output = $form->fetch();

        return $output;

    }


    /**
     * Hide a certain element in templates. For instance, call
     * $extension->hide('example'), to hide the contents of
     *
     * [[ if $hide.medialineimage ]]<!--[[/if x="-->"]]
     *     This will be hidden!
     * [[ if $hide.medialineimage ]]-->[[/if]]
     *
     * Note: the parameter in the [[/if]] is there solely to not confuse
     * editors that use HTML syntax coloring.
     *
     * @param string $name
     */
    function hide($name) {
        global $PIVOTX;

        $this->hidearray[$name] = true;

        $PIVOTX['template']->assign('hide', $this->hidearray);


    }

}


/**
 * Helper function to sort the extensions (admin, snippet and hook) alphabetically.
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function extensionSort($a, $b) {
    if ($a['priority'] == $b['priority']) {
        return strcmp( $a['extension'], $b['extension'] );
    }

    if ($a['priority'] < $b['priority']) {
    	return -1;
    }
    if ($a['priority'] > $b['priority']) {
    	return +1;
    }
    return 0;
}


/**
 * Helper function to keep the widgets in the order they were saved in.
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function widgetSort($a, $b) {
    global $PIVOTX;

    // Make an array with the names and their key, which we use to sort on
    $active = explode('|', $PIVOTX['config']->get('extensions_active'));
    $active = array_flip($active);

    // Make sure $a['name'] and $b['name'] have a value..
    if (!isset($active[ $a['identifier'] ] )) { $active[ $a['identifier'] ] = 999; }
    if (!isset($active[ $b['identifier'] ] )) { $active[ $b['identifier'] ] = 999; }


    return ( $active[$a['identifier']] < $active[$b['identifier']] ) ? -1 : 1;


}


/**
 * Try to insert the includes for thickbox in the <head> section of the HTML
 * that is to be outputted to the browser. Inserts Jquery if not already 
 * included.
 *
 * @param string $html
 */
function thickboxIncludeCallback(&$html) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        debug("JQuery is disabled by the 'never_jquery' config option. ThickBox won't work.");
        return;   
    }

    OutputSystem::instance()->enableCode('jquery');
    OutputSystem::instance()->addCode(
        'thickbox-javascript',
        OutputSystem::LOC_HEADEND,
        'script',
        array(),
        "\n\t\tvar tb_pathToImage = \"". $PIVOTX['paths']['pivotx_url'] ."pics/loadingAnimation.gif\";\n\t"
    );

    OutputSystem::instance()->addCode(
        'thickbox-jssrc',
        OutputSystem::LOC_HEADEND,
        'script',
        array('src'=>$PIVOTX['paths']['pivotx_url'].'includes/js/thickbox.js')
    );
    OutputSystem::instance()->addCode(
        'thickbox-stylehref',
        OutputSystem::LOC_HEADEND,
        'link',
        array('href'=>$PIVOTX['paths']['pivotx_url'].'templates_internal/assets/thickbox.css','media'=>'screen')
    );
}

/**
 * Try to insert the includes for JQuery in the <head> section of the HTML
 * that is to be outputted to the browser
 *
 * @param string $html
 */
function jqueryIncludeCallback(&$html) {
    global $PIVOTX;

    // If we've set the hidden config option for 'never_jquery', just return without doing anything.
    if ($PIVOTX['config']->get('never_jquery') == 1) {
        return;   
    }
   
    if (!preg_match("#<script [^>]*?/jquery[0-9_\.-]*\.(min.js|js)['\"][^>]*?>\s*</script>#i", $html)) { 
        OutputSystem::instance()->enableCode('jquery');
    }
}

?>
