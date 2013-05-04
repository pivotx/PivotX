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
if(!defined('INPIVOTX')){ die('not in pivotx'); }

/**
 * OPS class
 */
class OutputSystem {
    const PRI_HIGH   = 100;
    const PRI_NORMAL = 500;
    const PRI_LOW    = 900;
    const LOC_HEADSTART = 'head-start';
    const LOC_TITLEEND = 'title-end';
    const LOC_HEADEND = 'head-end';
    const LOC_BODYSTART = 'body-start';
    const LOC_BODYEND = 'body-end';

    protected static $instance = false;

    protected $codes;
    protected $filters;
    protected $defaults;

    /**
     * Construct the class
     */
    protected function __construct() {
        $this->codes   = array();
        $this->filters = array();
        
        $this->defaults = array(
            'script' => array(
                'type'=>'text/javascript'
            ),

            'link' => array(
                'rel' => 'stylesheet',
                'type' => 'text/css'
            ),

            'style' => array(
                'type' => 'text/css'
            )
        );

        $this->addDefaultOptions();
    }

    /**
     * Instantiate output system
     */
    public static function instance() {
        if (self::$instance === false) {
            self::$instance = new OutputSystem;
        }
        return self::$instance;
    }

    /**
     * Add a bunch of default options
     *
     * Maybe this shouldn't be here, but it's ok for now
     */
    public function addDefaultOptions() {
        global $PIVOTX;

        $this->addOptionalCode(
            'jquery',
            self::LOC_HEADSTART,
            'script',
            array(
                'src' => $PIVOTX['paths']['jquery_url'],
                '_priority' => self::PRI_HIGH-1
            ),
            '</script>'."\n".
            '<script type="text/javascript">'."\n".
            'jQuery.noConflict();'."\n"
        );

        $this->addOptionalFilter(
            'minify',
            array('Minify','osFilter')
        );
        if (($PIVOTX['config']->get('minify_frontend')) && (defined('PIVOTX_INWEBLOG'))) {
            $this->enableFilter('minify');
        }
        if (($PIVOTX['config']->get('minify_backend')) && (defined('PIVOTX_INADMIN'))) {
            $this->enableFilter('minify');
        }
    }

    /**
     * (internal) Compare two codes
     */
    public static function _cmpCodes(&$a,&$b) {
        if ($a['_priority'] < $b['_priority']) {
            return -1;
        }
        if ($a['_priority'] > $b['_priority']) {
            return +1;
        }
        $ret = strcasecmp($a['tag'],$b['tag']);
        if ($ret != 0) {
            return $ret;
        }
        return 0;
    }

    /**
     * (internal) Compare two filters
     */
    public static function _cmpFilters(&$a,&$b) {
        if ($a['_priority'] < $b['_priority']) {
            return -1;
        }
        if ($a['_priority'] > $b['_priority']) {
            return +1;
        }
        $ret = strcasecmp($a['id'],$b['id']);
        if ($ret != 0) {
            return $ret;
        }
        return 0;
    }

    /**
     * Add optional code, which can be enabled
     *
     * This is a shortcut method to quickly add common tags.
     * For <script> en <style> tags certain defaults are set whenever
     * they are not given in the $_params.
     * Params is an array of attributes that get added to the tag. It
     * also contains a few hidden attributes. An overview:
     * - _priority        priority of adding the code, use the constants
     *                     PRI_HIGH, PRI_NORMAL or PRI_LOW (don't forget
     *                     to add OutputSystem::). The changes the order
     *                     in which the codes are added.
     * - _ms-expression   if you want to add something only for a particular
     *                     IE, you can use this to add a '<!--[(expression)]>
     *                     ....<![endif]-->' around the tag.
     *
     * @param string $id        id
     * @param string $location  where to add the code
     * @param string $tag       tag to add
     * @param array  $_params   tag attributes to add
     * @param string $innerhtml inner html in the tag to add
     */
    public function addOptionalCode($id, $location, $tag, $_params, $innerhtml='') {
        if (!isset($this->codes[$id])) {
            $params = $_params;
            $params['_enabled']   = false;
            $params['_tag']       = $tag;
            $params['_location']  = $location;
            $params['_innerhtml'] = $innerhtml;
            $params['_callback']  = false;

            if (!isset($params['_priority'])) {
                $params['_priority'] = self::PRI_NORMAL;
            }

            $this->codes[$id] = $params;
        }
    }

    /**
     * Add code and enable it
     *
     * This is a shortcut method to quickly add common tags.
     * For <script> en <style> tags certain defaults are set whenever
     * they are not given in the $_params
     *
     * @param string $id        id
     * @param string $location  where to add the code
     * @param string $tag       tag to add
     * @param array  $_params   tag attributes to add
     * @param string $innerhtml inner html in the tag to add
     */
    public function addCode($id, $location, $tag, $_params, $innerhtml='') {
        // do we override optional code or not?
        if (true) {
            $params = $_params;
            $params['_tag']       = $tag;
            $params['_location']  = $location;
            $params['_innerhtml'] = $innerhtml;
            $params['_callback']  = false;
            if (!isset($params['_priority'])) {
                $params['_priority'] = self::PRI_NORMAL;
            }

            $this->codes[$id] = $params;
        }
 
        $this->codes[$id]['_enabled'] = true;
    }

    /**
     * Render a template, add the code and enable it
     *
     * @param string $id        id 
     * @param string $location  where to add the template code
     * @param string $vars      vars given to the template
     */
    public function addTemplate($id, $location, $template, $vars) {
        global $PIVOTX;

        if (!isset($this->codes[$id])) {
            $params = $_params;
            $params['_template'] = $template;
            $params['_vars']     = $vars;
            $params['_location'] = $location;
            if (!isset($params['_priority'])) {
                $params['_priority'] = self::PRI_NORMAL;
            }

            $this->codes[$id] = $params;
        }
 
        $this->codes[$id]['_enabled'] = true;
    }

    /**
     * Enable or disable a certain code
     *
     * @param string $id    regex string for codes to enable or disable
     */
    protected function setCodeEnabled($id, $enable=true) {
        $keys = array_keys($this->codes);

        $rxchars = '|/#@';

        do {
            $rxchar  = substr($rxchars,0,1);
            $rxchars = substr($rxchars,1);

            if (strpos($id,$rxchar) === false) {
                break;
            }

            $rxchar = false;
        }
        while (strlen($rxchars) > 0);

        if ($rxchar === false) {
            die('Cannot choose regular expression character! Please change your OutputSystem code-id.');
        }

        foreach($keys as $key) {
            if (preg_match($rxchar.$id.$rxchar,$key)) {
                $this->codes[$key]['_enabled'] = true;
            }
        }
    }

    /**
     * Enable a certain code
     *
     * @param string $id    regex string for codes to enable
     */
    public function enableCode($id) {
        return $this->setCodeEnabled($id,true);
    }

    /**
     * Verify and optionally enable code
     *
     * Add a callback which will be called on the html. If true is
     * returned than the code will be enabled.
     *
     * @param string $id           id
     * @param callback $callback   callback to verify if it should be enabled
     */
    public function verifyIfEnableCode($id, $callback) {
        if (isset($this->codes[$id])) {
            $this->codes[$id]['_callback'] = $callback;
        }
    }

    /**
     * Disable a certain code
     *
     * @param string $id    regex string for codes to disable
     */
    public function disableCode($id) {
        return $this->setCodeEnabled($id,false);
    }

    /**
     * Add a filter
     *
     * @param array $filter   
     */
    public function addFilter($id, $callback) {
        $params = array();

        $params['_enabled']  = true;
        $params['_priority'] = self::PRI_LOW;
        $params['_callback'] = $callback;

        $this->filters[$id] = $params;
    }

    /**
     * Optionally add a filter
     *
     * @param array $filter   
     */
    public function addOptionalFilter($id, $callback) {
        if (!isset($this->filters[$id])) {
            $params = array();

            $params['_enabled']  = false;
            $params['_priority'] = self::PRI_LOW;
            $params['_callback'] = $callback;

            $this->filters[$id] = $params;
        }
    }

    /**
     * Enable a filter
     */
    public function enableFilter($id) {
        if (isset($this->filters[$id])) {
            $this->filters[$id]['_enabled'] = true;
        }
    }

    /**
     * Disable a filter
     */
    public function disableFilter($id) {
        if (isset($this->filters[$id])) {
            $this->filters[$id] = false;
        }
    }

    /**
     * Rewrite html
     *
     * @param string $_html     the input html
     * @return string           the rewritten html
     */
    public function rewriteHtml($_html) {
        if ((count($this->codes) == 0) && (count($this->filters) == 0)) {
            return $_html;
        }

        // filter codes and sort the codes based on priority
        $codes = array();
        foreach($this->codes as $id=>$params) {
            if ($params['_enabled'] == true) {
                $codes[] = $params;
            }
            else if ($params['_callback'] != false) {
                if (call_user_func($params['_callback'], $_html)) {
                    $codes[] = $params;
                    $codes[count($codes)-1]['_enabled'] = true;
                }
            }
        }
        usort($codes, array('OutputSystem','_cmpCodes'));

        $filters = array();
        foreach($this->filters as $id=>$params) {
            if ($params['_enabled'] == true) {
                $filters[] = $params;
            }
        }
        usort($filters, array('OutputSystem','_cmpFilters'));

        if ((count($codes) == 0) && (count($filters) == 0)) {
            return $_html;
        }
        // is it HTML5? Try to deduce by looking for the doctype
        if (strpos($_html, '<!DOCTYPE html>') !== false) {
            $itshtml5 = true;
        } else {
            $itshtml5 = false;
        }

        $html = $_html;

        if (count($codes) > 0) {
            $line_pre  = "\t";
            $line_post = "\n";

            $part = array();
            $part[self::LOC_HEADSTART] = '';
            $part[self::LOC_TITLEEND]  = '';
            $part[self::LOC_HEADEND]   = '';
            $part[self::LOC_BODYSTART] = '';
            $part[self::LOC_BODYEND]   = '';

            foreach($codes as $params) {
                if (isset($params['_tag'])) {
                    $tag = $params['_tag'];
                    $loc = $params['_location'];
                    $def = array();
                    $msc = false;
                    if (isset($this->defaults[$tag])) {
                        $def = $this->defaults[$tag];
                    }
                    if (isset($params['_ms-expression'])) {
                        $msc = $params['_ms-expression'];
                    }

                    unset($params['_tag']);
                    unset($params['_enabled']);
                    unset($params['_priority']);
                    unset($params['_location']);
                    unset($params['_ms-expression']);
                    unset($params['_callback']);

                    $part[$loc] .= $line_pre;
                    if ($msc !== false) {
                        $part[$loc] .= '<!--['.$msc.']>';
                    }
                    $part[$loc] .= '<'.$tag;

                    $innerhtml = '';
                    if (isset($params['_innerhtml'])) {
                        $innerhtml = $params['_innerhtml'];
                        unset($params['_innerhtml']);
                    }

                    $attr = array_merge($def,$params);
                    foreach($attr as $k=>$v) {
                        $part[$loc] .= ' '.$k.'="'.htmlspecialchars($v).'"';
                    }

                    if (empty($innerhtml) && $tag!="script") {
                        if ($itshtml5 && $tag == 'meta') {
                            $part[$loc] .= ' >';
                        } else {
                            $part[$loc] .= ' />';
                        }
                    } else {
                        $part[$loc] .= '>'.$innerhtml.'</'.$tag.'>';
                    }

                    if ($msc !== false) {
                        $part[$loc] .= '<![endif]-->';
                    }
                    $part[$loc] .= $line_post;
                }
                else if (isset($params['_template'])) {
                    $loc      = $params['_location'];
                    $template = $params['_template'];
                    $vars     = $params['_vars'];
                    
                    $smarty = new PivotxSmarty;
                    $smarty->disallowRewriteHtml();
                    foreach($vars as $k=>$v) {
                        $smarty->assign($k,$v);
                    }

                    $part[$loc] .= $smarty->fetch($template);
                }
            }

            // !! this part needs to be improved and soon
            if ($part[self::LOC_HEADSTART] != '') {
                if (preg_match('|(<meta http-equiv=[\'"]content-type[^>]+>[ \t\r\n]*)|i',$html)) {
                    $html = preg_replace('|(<meta http-equiv=[\'"]content-type[^>]+>[ \t\r\n]*)|i','$1'.$part[self::LOC_HEADSTART],$html);
                }
                else if (preg_match('|(<head[^>]*>[ \t\r\n]*)|i',$html)) {
                    $html = preg_replace('|(<head[^>]*>[ \t\r\n]*)|i', '$1'.$part[self::LOC_HEADSTART], $html, 1);
                }
            }
            if ($part[self::LOC_TITLEEND] != '') {
                $html = str_replace('</title>', '</title>'.$part[self::LOC_TITLEEND].'', $html);
            }
            if ($part[self::LOC_HEADEND] != '') {
                $html = str_replace('</head>', $part[self::LOC_HEADEND].'</head>', $html);
            }
            if ($part[self::LOC_BODYSTART] != '') {
                $html = preg_replace('|(<body[^>]*>)|i', '$1'.$part[self::LOC_BODYSTART], $html, 1);
            }
            if ($part[self::LOC_BODYEND] != '') {
                $html = str_replace('</body>', $part[self::LOC_BODYEND].'</body>', $html);
            }
        }

        if (count($filters) > 0) {
            foreach($filters as $filter) {
                $ret = call_user_func($filter['_callback'],$html);
                if ($ret !== false) {
                    $html = $ret;
                }
            }
        }

        return $html;
    }
}

?>
