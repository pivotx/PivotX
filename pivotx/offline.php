<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id: offline.php 3226 2010-11-29 14:50:00Z marcel $
//
// ---------------------------------------------------------------------------


class PivotxOffline {
    /**
     * Show the offline page or passthrough if the user is allowed to see the site anyway
     *
     * @param string $config_dir    configuration directory
     */
    public static function showOffline($config_dir) {
        $template_dir = 'pivotx/templates';

        $config = self::getOfflineConfiguration($config_dir);

        if (self::allowOfflineAccess($config_dir,$config)) {
            // user is allowed to see the site anyway
            return;
        }

        $html = false;
        if (file_exists($template_dir.'/offline.html')) {
            $html = file_get_contents($template_dir.'/offline.html');
        }
        elseif (file_exists($template_dir.'/default_offline.html')) {
            $html = file_get_contents($template_dir.'/default_offline.html');
        }
        else {
            // the very short version as backup
            $html = '<html><head><title>Website is offline</title></head><body><h1>Website is offline</h1></body></html>';
        }

        $html = str_replace('%custom_text%',$config['custom_text'],$html);

        Header('HTTP/1.0 '.$config['http_status'].' Temporary unavailable');
        echo $html;
        exit();
    }

    /**
     * Return true if website is online
     */
    public static function isOnline($config_dir = false) {
        global $PIVOTX;

        if ($config_dir === false) {
            $config_dir = $PIVOTX['paths']['db_path'];
        }

        $config = self::getOfflineConfiguration($config_dir);

        return $config['online'];
    }

    /**
     * Get the offline configuration parameters
     *
     * @return array
     */
    public static function getOfflineConfiguration($config_dir) {
        $raw_data = false;
        $online   = true;
        if (is_file($config_dir.'ser_offline.php')) {
            $raw_data = file_get_contents($config_dir.'ser_offline.php');
            $online   = false;
        }
        else if (is_file($config_dir.'ser_online.php')) {
            $raw_data = file_get_contents($config_dir.'ser_online.php');
        }


        $config = array(
            'online' => $online,
            'http_status' => 503,
            'custom_text' => '',
            'allow_pivotx_users' => true,
        );

       
        if (($raw_data !== false) && (strlen($raw_data) > 27)) {
            $data = @unserialize(substr($raw_data,27));

            if (is_array($data)) {
                foreach($data as $k=>$v) {
                    $config[$k] = $v;
                }
            }
        }

        return $config;
    }

    /**
     * Write the configuration
     */
    protected static function writeConfiguration($config_dir, $config) {
        $tmp_fname = $config_dir.'ser_tmpline.php';
        $on_fname  = $config_dir.'ser_online.php';
        $off_fname = $config_dir.'ser_offline.php';

        $realconfig = $config;
        unset($realconfig['online']);

        @unlink($tmp_fname);
        $fp = fopen($tmp_fname,'w');
        if ($fp) {
            fputs($fp,'<'.'?php /* pivot */ die(); ?'.'>'.serialize($realconfig));
            fclose($fp);
            chmod($tmp_fname,0666);
        }

        @unlink($on_fname);
        @unlink($off_fname);

        if ($config['online']) {
            rename($tmp_fname,$on_fname);
        }
        else {
            rename($tmp_fname,$off_fname);
        }
    }


    /**
     * Check if user is allowed to see an offline site anyway
     */
    public static function allowOfflineAccess($config_dir, $config) {
        // chek ser_sessions if configured
        if (($config['allow_pivotx_users']) && (is_file($config_dir.'ser_sessions.php'))) {
            $data     = file_get_contents($config_dir.'ser_sessions.php');
            $sessions = false;
            if (is_scalar($data) && (strlen($data) > 27)) {
                $sessions = unserialize(substr($data,27));
            }
            if (is_array($sessions)) {
                foreach($sessions as $k => $info) {
                    if ($_SERVER['REMOTE_ADDR'] == $info['ip']) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Set configuration item
     */
    public static function setConfig($key,$value) {
        global $PIVOTX;

        $config = self::getOfflineConfiguration($PIVOTX['paths']['db_path']);

        $clear_cache = false;

        if ($key == 'online') {
            if ($value == '') {
                $value = false;
            }
            else {
                $value = true;

                if (!$config['online']) {
                    $clear_cache = true;
                }
            }
        }
        $config[$key] = $value;

        if ($clear_cache) {
            $deletecounter = wipeSmartyCache();

            $message = sprintf(__('deleted %s cache files in %s seconds.'), $deletecounter, timeTaken() );

            debug($message);
        }

        self::writeConfiguration($PIVOTX['paths']['db_path'],$config);
    }
}
?>
