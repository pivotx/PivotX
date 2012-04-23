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
 * Upload module
 *
 * Configuration options:
 * plupload_runtimes    plupload runtime selection, defaults to "flash,html5,silverlight,gears,browserplus"
 */



/**
 * Upload element class
 */
class UploadElement {
    public function __construct() {
    }

    /**
     */
    protected function _completeTemplateVars($_params) {
        global $PIVOTX;
        $pivotx_url = $PIVOTX['paths']['pivotx_url'];
        $params = $_params;
        if (!isset($params['max_file_size'])) {
            $ini_size = strtolower(ini_get('upload_max_filesize'));
            if (strpos('kmg',substr($ini_size,-1)) !== false) {
                $ini_size .= 'b';  // add a "b" to get kb/mb/gb
            }
            if (is_numeric($ini_size)) {  
                $ini_size = floor($ini_size/1024).'kb'; // changed to kb from k
            }
            // get config option and compare to server value
            $upl_size = getDefault($PIVOTX['config']->get('upload_max_filesize'), -1);
            if ($upl_size > 0) {
                $ini_unit = substr($ini_size,-2);
                if ($ini_unit == 'gb') { $upl_size = floor($upl_size/1024/1024/1024).'gb'; }
                if ($ini_unit == 'mb') { $upl_size = floor($upl_size/1024/1024).'mb'; }
                if ($ini_unit == 'kb') { $upl_size = floor($upl_size/1024).'kb'; }
                if (substr($upl_size,0,-2) < substr($ini_size,0,-2)) { $ini_size = $upl_size; }
            }
            $params['max_file_size'] = $ini_size;
        }
        if (!isset($params['url'])) {
            $params['url'] = $pivotx_url . 'fileupload.php';
        }
        if (!isset($params['jsdir'])) {
            $params['jsdir'] = $pivotx_url . 'includes/js/plupload';
        }
        if (!isset($params['filters'])) {
            $params['filters'] = array();
            /* No filters for any file upload
            $params['filters'] = array(
                array ( 'title'=>'Images files', 'extensions'=>'jpg,jpeg,gif,png' ),
                array ( 'title'=>'Archive files', 'extensions'=>'zip,tgz,gz,bz2,dmg,7z,sit,iso' ),
                array ( 'title'=>'Document files', 'extensions'=>'doc,docx,rtf,pdf,txt' ),
                array ( 'title'=>'Office files', 'extensions'=>'doc,xls,csv' ),
                array ( 'title'=>'Text files', 'extensions'=>'txt' ),
            );
            */
        }
        if (!isset($params['progress_selector'])) {
            $params['progress_selector'] = '#plupload-progress';
        }
        if (!isset($params['runtimes'])) {
            $params['runtimes'] = trim($PIVOTX['config']->get('plupload_runtimes'));
            if ($params['runtimes'] == '') {
                if (isChrome()) {
                    $params['runtimes'] = 'html5,flash,silverlight,gears,browserplus';
                } else {
                    $params['runtimes'] = 'flash,html5,silverlight,gears,browserplus';
                }
            }
        }
        debug("Plupload runtime order: " . $params['runtimes'] );
        $params['upload_var'] = 'uploader'.rand(10000,99999);
        $params['sessionid']  = session_id();
        $params['paths']      = $PIVOTX['paths'];
        return $params;
    }

    /**
     * Output a little bit of HTML
     */
    public function render($params) {
        global $PIVOTX;
        $pivotx_url = $PIVOTX['paths']['pivotx_url'];
        $os = OutputSystem::instance();

        $os->addCode('jquery_ui',           OutputSystem::LOC_HEADEND,'script',array('_priority'=>OutputSystem::PRI_HIGH,'
            src'=>$PIVOTX['paths']['jquery_ui_url']));
        $os->addCode('plupload_browserplus',OutputSystem::LOC_HEADEND,'script',array('src'=>'http://bp.yahooapis.com/2.4.21/browserplus-min.js'));
        $os->addCode('plupload_full',       OutputSystem::LOC_HEADEND,'script',array('src'=>$pivotx_url . 'includes/js/plupload/plupload.full.js'));

        if (is_array($params)) {
            $vars = $params;
        }
        else {
            $vars = array();
        }

        switch ($vars['filters']) {
            case 'image':
                $vars['filters'] = array(
                    array ( 'title'=>'Image files', 'extensions'=>'jpeg,jpg,gif,png' ),
                );
                break;

            case 'document':
                $vars['filters'] = array(
                    array ( 'title'=>'Document files', 'extensions'=>'doc,docx,rtf,pdf' ),
                );
                break;

            case 'any':
                unset($vars['filters']);

            default:
                if (!is_array($vars['filters'])) {
                    unset($vars['filters']);
                }
                break;
        }

        $vars['name']        = 'test-file-upload';
        $vars['upload_path'] = getUploadFolderUrl();
        $vars                = $this->_completeTemplateVars($vars);
        $os->addTemplate('plupload_render', OutputSystem::LOC_BODYEND,'inc_plupload_element.tpl',$vars);

        return '';
    }

    /**
     */
    public function processUpload() {
    }
}

class SmartyUpload {
    /**
     * Smarty generic upload button
     */
    public static function smarty_upload_create_button($params, &$smarty) {
        $u = new UploadElement;
        return $u->render($params);
    }
}

?>
