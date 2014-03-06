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



/**
 * Base Configuration Class
 *
 * Handle the loading and saving configuration data.
 * It contains defaults flows of reading, verifying/fixing and saving of configuration data.
 *
 * Description of the load call:
 * - __construct
 *   - loadConfig
 *     - verifyConfig
 *     - fixConfig
 *     - saveConfig
 *       - organizeConfig
 *   - organizeConfig
 *   - initConfig
 *
 * Save call:
 * - saveConfig
 *   - organizeConfig
 */
class BaseConfig {

    var $configfile = '';
    var $backup_configfile = '';
    var $data = array();
    var $changed = false;
    var $upgraded = false;

    /**
     * Constructor
     *
     * @param filename      configuration filename
     * @param db_path       path to pivotx db directory, when false we assume config has been loaded
     */
    function __construct($filename, $db_path = false) {
        if ($db_path === false) {
            global $PIVOTX;

            $db_path = $PIVOTX['paths']['db_path'];
        }
        
        $this->configfile        = $db_path . $filename;
        $this->backup_configfile = $db_path . 'backup/' . $filename;

        $this->loadConfig();

        $this->organizeConfig();

        $this->initConfig();
    }

    /**
     * Set upgraded
     */
    protected function setUpgraded($upgraded=true) {
        $this->upgraded = $upgraded;
    }

    /**
     * Set changed flag
     */
    protected function setChanged($changed=true) {
        $this->changed = $changed;
    }

    /**
     * Load and verify config
     */
    protected function loadConfig() {
        $this->data = loadSerialize($this->configfile, true);

        if ((!isset($this->data)) || ($this->data === false)) {
            $this->data = loadSerialize($this->backup_configfile, true);
        }

        if (!$this->verifyConfig()) {
            $this->fixConfig();

            $this->saveConfig(true);
        }
    }

    /**
     * Verify configuration (this should be overwritten in subclass)
     *
     * @return boolean    true if configuration is ok
     */
    protected function verifyConfig() {
        return true;
    }

    /**
     * Fix configuration (this should be overwritten in subclass)
     *
     * This is called when verifyConfig() fails.
     */
    protected function fixConfig() {
    }

    /**
     * Organize configuration
     *
     * @return true, if configuration can be saved
     *
     * This is called when just loaded and before configuration is saved.
     */
    protected function organizeConfig() {
        if (is_array($this->data)) {
            ksort($this->data);
        }

        if (count($this->data) <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Initialise configuration after has been read, fixed and organized.
     */
    protected function initConfig() {
    }


    /**
     * Save configuration if 'safe' to do so
     */
    protected function saveConfig($force_changed=false) {
        if ($force_changed) {
            $this->setChanged();
        }

        if ($this->changed) {
            $writable = $this->organizeConfig();
            
            if ((defined('PIVOTX_INADMIN') || defined('PIVOTX_INAJAXHELPER')) && (is_array($this->data)) && ($writable)) {
                saveSerialize($this->configfile, $this->data);
            }
        }
    }

    /**
     * Return configuration-array size
     */
    public function count() {
        if (!is_array($this->data)) {
            return 0;
        }
        return count($this->data);
    }

    /**
     * Print a comprehensible representation of the users
     *
     */
    function print_r() {
        echo "<pre>\n";
        print_r($this->data);
        echo "</pre>\n";
    }

    /**
     * Old save version
     *
     * This function force a save anyway.
     */
    public function save()
    {
        return $this->saveConfig(true);
    }
}

/**
 * Takes care of all configuration settings. The configuration is stored in
 * pivotx/db/ser_config.php, but is completely accessible through this object.
 * Saving is automagical and only when something has changed.
 *
 */
class Config extends BaseConfig {
    public function __construct($sites_path = '') {
        $db_path = dirname(__FILE__) . '/' . $sites_path . 'db/';

        parent::__construct('ser_config.php',$db_path);
    }

    public function Config($sites_path = '') {
        $this->__construct($sites_path);
    }

    protected function verifyConfig() {
        // If there's a file called 'pivotxdebugmode.txt', we'll enable debugging 
        if (file_exists(dirname(__FILE__)."/pivotxdebugmode.txt")) {
            $this->data['debug'] = 1;
        }

        if ($this->count() < 5) {
            return false;
        }

        $default = getDefaultConfig();
        foreach($default as $key=>$value) {
            if (!isset($this->data[$key])) {
                return false;
            }
        }

        if (!isset($this->data['server_spam_key']) || empty($this->data['server_spam_key'])) {
            return false;
        }

        return true;
    }

    protected function fixConfig() {
        if ($this->count() < 5) {
            $this->readOld();

            $this->setChanged();
        }

        $default = getDefaultConfig();
        foreach($default as $key=>$value) {

            if (!isset($this->data[$key])) {
                $this->data[$key] = $value;

                $this->setChanged();
            }
        }

        // Seperate check for 'server_spam_key' since it is different for all PivotX install
        if (!isset($this->data['server_spam_key']) || empty($this->data['server_spam_key'])) {
            $server_spam_key = '';
            $possible_server_keys = array('SERVER_SIGNATURE','SERVER_ADDR','PHP_SELF','DOCUMENT_ROOT');
            foreach ($possible_server_keys as $key) {
                if (isset($_SERVER[$key])) {
                    $server_spam_key .= $_SERVER[$key];
                }
            }
            $server_spam_key .= time();
            $this->data['server_spam_key'] = md5($server_spam_key);
            
            $this->setChanged();
        }

    }

    /**
     * If the config file is missing, we check if there's a pivot 1.x config
     * file that we can use. This function does some comversions to get it up
     * to date, and sets it in $this->data
     *
     */
    protected function readOld() {
        global $pivotx_path;

        $this->setUpgraded();

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = explode("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }
        //GetUserInfo();
        //ExpandSessions();

        @$Cfg['ping_urls']=str_replace("|", "\n", $Cfg['ping_urls']);
        @$Cfg['default_introduction']=str_replace("|", "\n", $Cfg['default_introduction']);

        if (!isset($Cfg['selfreg'])) { $Cfg['selfreg']= 0; }
        if (!isset($Cfg['xmlrpc'])) { $Cfg['xmlrpc']= 0; }
        if (!isset($Cfg['hashcash'])) { $Cfg['hashcash']= 0; }
        if (!isset($Cfg['spamquiz'])) { $Cfg['spamquiz']= 0; }
        if (!isset($Cfg['hardened_trackback'])) { $Cfg['hardened_trackback']= 0; }
        if (!isset($Cfg['moderate_comments'])) { $Cfg['moderate_comments']= 0; }
        if (!isset($Cfg['lastcomm_amount_max'])) { $Cfg['lastcomm_amount_max'] = 60; }

        if (!isset($Cfg['tag_cache_timeout'])) { $Cfg['tag_cache_timeout'] = 60; }
        if (!isset($Cfg['tag_flickr_enabled'])) { $Cfg['tag_flickr_enabled'] = 1; }
        if (!isset($Cfg['tag_flickr_amount'])) { $Cfg['tag_flickr_amount'] = 6; }
        if (!isset($Cfg['tag_fetcher_enabled'])) { $Cfg['tag_fetcher_enabled'] = 1; }
        if (!isset($Cfg['tag_fetcher_amount'])) { $Cfg['tag_fetcher_amount'] = 10; }
        if (!isset($Cfg['tag_min_font'])) { $Cfg['tag_min_font'] = 9; }
        if (!isset($Cfg['tag_max_font'])) { $Cfg['tag_max_font'] = 42; }

        if(!isset($Cfg['server_spam_key']))  {
            $key = $_SERVER['SERVER_SIGNATURE'].$_SERVER['SERVER_ADDR'].$_SERVER['SCRIPT_URI'].$_SERVER['DOCUMENT_ROOT'].time();
            $Cfg['server_spam_key'] = md5($key);
        }

        // Remove stuff we don't need:
        unset($Cfg['session_length']);
        unset($Cfg['sessions']);
        unset($Cfg['users']);
        unset($Cfg['userfields']);
        unset($Cfg['<?php']);
        unset($Cfg['?>']);


        foreach ($Cfg as $key => $val) {
            if ( (strpos($key,'uf-')===0) || (strpos($key,'user-')===0) ) {
                unset($Cfg[$key]);
            }
        }

        $this->data = $Cfg;
    }

    /**
     * Return the entire config as a big array.. It's probable better to use
     * $PIVOTX['config']->get() if you only need one or few items.
     *
     * @see $this->get
     * @return array
     */
    function getConfigArray() {

        return $this->data;

    }

    /**
     * Sets a configuration value, and then saves it.
     *
     * @param string $key
     * @param unknown_type $value
     */
    function set($key, $value) {

        // Empty checkboxes are passed by jQuery as string 'undefined', but we want to store them as integer '0'
        if ($value==="undefined") { $value=0; }

        // Offline configuration is not saved in the normal configuration file
        if (substr($key,0,8) == 'offline_') {
            PivotxOffline::setConfig(substr($key,8),$value);
            return;
        }

        // Only set (and save) if the value has actually changed.
        if (empty($this->data[safeString($key)]) || $value !== $this->data[safeString($key)] ) {

            $this->data[safeString($key)] = $value;

            $this->saveConfig(true);
        }
    }

    /**
     * Delete a configuration value. Use with extreme caution. Saves the
     * configuration afterwards
     *
     * @param string $key
     */
    function del($key) {
        // Old pre PivotX 2.0 configuration didn't use safe_string
        // on the key - we are handling it here.
        if (isset($this->data[safeString($key)])) {
            unset($this->data[safeString($key)]);
        } else {
            unset($this->data[$key]);
        }

        $this->saveConfig(true);
    }

    /**
     * Gets a single value from the configuration.
     *
     * @param string $key
     * @return string
     */
    function get($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return false;
        }
    }
}

/**
 * Since PHP4 doesn't allow class constants, we define the userlevels as 
 * global constants.
 */
define("PIVOTX_UL_NOBODY", -1);
define("PIVOTX_UL_MOBLOGGER", 0);
define("PIVOTX_UL_NORMAL", 1);
define("PIVOTX_UL_ADVANCED", 2);
define("PIVOTX_UL_ADMIN", 3);
define("PIVOTX_UL_SUPERADMIN", 4);

/**
 * Portable PHP password hashing framework (phpass) for PivotX:
 *
 * The framework can be completely disabled by setting "disable_phpass" to 1
 * in the advanced configuration. This is not recommended. If it is disabled,
 * a salted md5 sum is used for password hashing.
 * 
 * 1) The standard log2 number of iterations for password stretching. This 
 * should be increased from time to time to counteract increases in the speed 
 * and power of computers available to crack the hashes. However, since the 
 * current hashing algorithms aren't capable of running in parallell in PHP, 
 * it shouldn't be increased too often. (It should never exceed 31.)
 */
define('PIVOTX_PASSWORD_HASH_COUNT', 9);
/**
 * 2) By default, portable hashes are used for maximum portability. Portable 
 * hashes can be disabled by setting "password_non_portable_hashes"
 * to 1 in the advanced configuration. Non-portable hashes are more secure, 
 * but can be a problem on shared hosting or if you need to move your site 
 * between different servers. 
 */
define('PIVOTX_PASSWORD_PORTABLE_HASHES', true);

/**
 * This Class handles all operations with regard to users: adding, deleting,
 * getting info, etc.
 *
 */
class Users extends BaseConfig {

    public function __construct() {
        parent::__construct('ser_users.php');
    }

    public function Users() {
        $this->__construct();
    }

    protected function verifyConfig() {
        if ($this->count() < 1) {
            return false;
        }

        return true;
    }

    protected function fixConfig() {
        if (count($this->data) < 5) {
            $this->readOld();

            $this->setChanged();
        }
    }

    protected function organizeConfig() {
        // Make sure the users are sorted as intended.
        uasort($this->data, array($this, 'sort'));

        if ($this->count() < 1) {
            return false;
        }

        return true;
    }

    protected function readOld() {
        global $pivotx_path;

        $this->setUpgraded();

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = explode("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }

        if(isset($Cfg['users']))  {
            foreach(explode('|', trim($Cfg['users'])) as $inc => $user){
                $userdata = array();
                $userdata['username'] = $user;
                foreach(explode('|-|' , $Cfg['user-' . $user]) as $var => $val){
                    list($Nvar, $Nval) = explode('|', $val);
                    if ($Nvar == 'nick') {
                        $userdata['nickname'] = $Nval;
                    } elseif ($Nvar == 'pass') {
                        $userdata['md5_pass'] = $Nval;
                    } else {
                        $userdata[$Nvar] = $Nval;
                    }
                }
                list($userdata['language']) = explode("_",$userdata['language']);
                $this->addUser($userdata);
            }
        }
    }

    /**
     * Add a user to Pivot
     *
     * @param array $user
     */
    function addUser($user) {
        global $PIVOTX;

        // Make sure the username is OK..
        $user['username'] = strtolower(safeString($user['username']));

        if ($this->getUser($user['username'])!==false) {
            // this username is already taken..
            return false;
        }

        $newuser = array(
            'username' => $user['username'],
            'email' => $user['email'],
            'userlevel' => $user['userlevel'],
            'nickname' => $user['nickname'],
            'language' => $user['language'],
            'image' => $user['image'],
            'text_processing' => $user['text_processing']
        );

        if (!isset($user['pass1']) && isset($user['md5_pass'])) {
            // User comes from old (1.x) config so we don't have the clear text password.
            $newuser['password'] = $user['md5_pass'];
            $newuser['salt'] = '';
        } else {
            $newuser = $this->hashPassword($newuser, $user['pass1']);
        }

        $this->data[] = $newuser;

        $this->saveConfig(true);

    }

    function deleteUser($username) {

        if ($this->count() > 1) {
            foreach($this->data as $key=>$user) {
                if ($username == $user['username']) {
                    unset($this->data[$key]);
                }
            }
        }

        $this->saveConfig(true);

    }

    /**
     * Update a given property of a user
     *
     * @param string $username
     * @param array $properties
     * @see $this->save
     */
    function updateUser($username, $properties) {

        // Select the correct user
        foreach ($this->data as $key=>$user) {
            if ($username == $user['username']) {

                // Set the properties
                foreach($properties as $property => $value) {

                    switch ($property) {
                        case "email":
                        case "nickname":
                        case "language":
                        case "text_processing":
                        case "lastseen":
                        case "userlevel":
                        case "image":
                            $this->data[$key][$property] = $value;
                            break;

                        case "reset_id":
                            if ($value!="") {
                                $this->data[$key][$property] = $value;
                            } else {
                                unset($this->data[$key][$property]);
                            }
                            break;

                        case "pass1":
                            if ( ($value!="") && ($value!="******")) {
                                $this->data[$key] = $this->hashPassword($user, $value);
                            }

                        default:
                            break;
                    }

                }

            }

        }

        $this->saveConfig(true);
    }

    /**
     * Check if a given password matches the one stored.
     *
     * @param string $username
     * @param string $password
     * @return boolean
     */
    function checkPassword($username, $password) {
        global $PIVOTX;

        foreach($this->data as $user) {

            if ($username==$user['username']) {
                if ($user['salt'] == 'phpass') {
                    require_once($PIVOTX['paths']['pivotx_path'] . 'includes/PasswordHash.php');
                    // We don't really need to set portability correctly when checking 
                    // the password (since the hashing method is stored in the hash), 
                    // but it's clearer to use the same code everywhere.
                    if ($PIVOTX['config']->get('password_non_portable_hashes')) {
                        $portable = false;
                    } else {
                        $portable = PIVOTX_PASSWORD_PORTABLE_HASHES;
                    }
                    $phpass = new PasswordHash(PIVOTX_PASSWORD_HASH_COUNT, $portable);
                    return $phpass->CheckPassword($password, $user['password']);
                } else {
                    if (md5($password . $user['salt']) == $user['password']) {
                        return true;
                    }
                }
                break;
            }

        }

        return false;

    }

    /**
     * Hash a given password (for a given user).
     *
     * @param array $user
     * @param string $password
     * @return boolean
     */
    function hashPassword($user, $password) {
        global $PIVOTX;

        if (!$PIVOTX['config']->get('disable_phpass')) {
            require_once($PIVOTX['paths']['pivotx_path'] . 'includes/PasswordHash.php');
            if ($PIVOTX['config']->get('password_non_portable_hashes')) {
                $portable = false;
            } else {
                $portable = PIVOTX_PASSWORD_PORTABLE_HASHES;
            }
            $phpass = new PasswordHash(PIVOTX_PASSWORD_HASH_COUNT, $portable);
            $user['salt'] = 'phpass';
            $user['password'] =  $phpass->HashPassword($password);
        } else {
            $user['salt'] = md5(rand(1,999999) . time());  
            $user['password'] = md5( $password . $user['salt']);
        }

        return $user;

    }

    /**
     * Check if a given $username is a user.
     *
     * @param string $name
     * @return boolean
     */
    function isUser($username) {

        if ($this->getUser($username) === false) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Get the specifics for a given user by its username.
     *
     * @param string $username
     * @return array
     */
    function getUser($username) {

        foreach($this->data as $user) {

            if ( ($username==$user['username']) ) {
                return $user;
            }

        }

        return false;

    }

    /**
     * Get the specifics for a given user by its nickname.
     *
     * @param string $username
     * @return array
     */
    function getUserByNickname($username) {

        foreach($this->data as $user) {

            if ( strtolower($username) == strtolower($user['nickname']) ) {
                return $user;
            }

        }

        return false;

    }

    /**
     * Get a list of the Usernames
     *
     * @return array
     */
    function getUsernames() {

        $res = array();

        foreach($this->data as $user) {
            $res[]=$user['username'];
        }

        return $res;

    }

    /**
     * Get a list of the Users Nicknames
     *
     * @return array
     */
    function getUserNicknames() {

        $res = array();

        foreach($this->data as $user) {
            $res[ $user['username'] ] = $user['nickname'];
        }

        return $res;

    }

    /**
     * Get a list of the Users Email adresses
     *
     * @return array
     */
    function getUserEmail() {

        $res = array();

        foreach($this->data as $user) {
            $res[ $user['username'] ] = $user['email'];
        }

        return $res;

    }

    /**
     * Get all users as an array
     *
     * @return array
     */
    function getUsers() {

        return $this->data;

    }
    
    /**
     * Determines if $currentuser (or 'the current user', if left empty) is allowed
     * to edit a page or entry that's owned by $contentowner.
     *
     * @param string $contentowner
     * @param string $currentuser
     * @return boolean
     */
    function allowEdit($contenttype, $contentowner="", $currentuser="") {
        global $PIVOTX;

        // Default to the current logged in user.
        if (empty($currentuser)) {
            $currentuser = $PIVOTX['session']->currentUsername();
        }

        // Fetch the current user..
        $currentuser = $PIVOTX['users']->getUser( $currentuser );
        $currentuserlevel = (!$currentuser?PIVOTX_UL_NOBODY:$currentuser['userlevel']);
        
        // Always allow editing for superadmins - no matter content type.
        if ($currentuserlevel==PIVOTX_UL_SUPERADMIN) {
            return true;
        } 

        // Fetch the owner..
        $contentowner = $PIVOTX['users']->getUser( $contentowner );
        $contentownerlevel = (!$contentowner?PIVOTX_UL_NOBODY:$contentowner['userlevel']);

        // Now run the checks for different content types
        if ($contenttype == 'chapter') {

            // Only sdministrator and superadmins can add, edit and delete chapters.
            if ($currentuserlevel>=PIVOTX_UL_ADMIN) {
                return true;
            } 

        } else if (($contenttype == 'entry') || ($contenttype == 'page')) {

            // Get the value (if any) of allow_edit_for_own_userlevel setting
            $allowsamelevel = getDefault( $PIVOTX['config']->get('allow_edit_for_own_userlevel'), PIVOTX_UL_SUPERADMIN);

            if ($contentowner['username']==$currentuser['username']) {
                // Always allow editing of your own content..
                return true;
            } else  if ($currentuserlevel > $contentownerlevel) {
                // Allow editing content for items owned by lower levels.
                return true;
            } else if ( ($currentuserlevel == $contentownerlevel) && ( $currentuserlevel >= $allowsamelevel) ) {
                // Allow if userlevel is the same, and greater than or equal to $allowsamelevel
                return true;
            }

        } else if (($contenttype == 'comment') || ($contenttype == 'trackback')) {

            if ($contentowner['username']==$currentuser['username']) {
                // Always allow editing of comments/trackback on your own entries.
                return true;
            } else  if ($currentuserlevel >= PIVOTX_UL_ADVANCED) {
                return true;
            }

        } else {
            debug('Unknown content type');
        }

        // Disallow editing
        return false;
    }
    
    /**
     * Sort the users based on string comparison of username.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        return strcmp($a['username'],$b['username']);
    }
}

/**
 * This class deals with the Weblogs.
 *
 */
class Weblogs extends BaseConfig {

    var $default;
    var $current;

    public function __construct() {
        parent::__construct('ser_weblogs.php');
    }

    public function Weblogs() {
        $this->__construct();
    }

    public function verifyConfig() {
        if ($this->count() < 1) {
            return false;
        }
    }

    public function fixConfig() {
        if ($this->count() < 1) {
            $this->readOld();

            $this->setChanged();
        }

        if ($this->count() < 1) {
            // No weblogs, create one from scratch
            $this->add('weblog', __('My weblog'), 'pivotxdefault');
        }
    }

    protected function initConfig() {
        global $PIVOTX;

        foreach ($this->data as $key => $weblog) {
                   
            // Unset '$subkey' weblog -> compensates for an old bug
            if (!empty($this->data[$key]['sub_weblog']['$subkey'])) {
                unset($this->data[$key]['sub_weblog']['$subkey']);
            }
            
            // Make sure all categories are arrays.
            if (is_array($weblog['sub_weblog'])) {
                foreach ($weblog['sub_weblog'] as $subkey => $subweblog) {
                    if (!is_array($subweblog['categories'])) {
                        $this->data[$key]['sub_weblog'][$subkey]['categories'] = array($subweblog['categories']);
                    }
                }
            }
 
            // Set the correct link to the weblog.
            if (empty($this->data[$key]['site_url'])) {
                $this->data[$key]['site_url'] = "";
            }
            $this->data[$key]['link'] = $this->_getLink($key, $this->data[$key]['site_url']);
 
            // Set the 'categories' for the combined subweblogs..
            $this->data[$key]['categories'] = $this->getCategories($key);

        }

        // Make sure the weblogs are sorted as intended.
        $this->organizeConfig();

        // Set default weblog either as specified by the root in the config
        // or just by selecting the first in the weblo
        $reset = true;
        if (strpos($PIVOTX['config']->get('root'), ':') !== false) {
            list($type, $root) = explode(":", $PIVOTX['config']->get('root'));
            if ($type=="w" && !empty($root) && isset($this->data[$root]) ) {
                $this->default = $root;
                $reset = false;
            }
        }
        if ($reset) {
            // Nothing to do but fall back to the first available weblog..
            reset($this->data);
            $this->default = key($this->data);
        }
    }

    protected function organizeConfig() {
        uasort($this->data, array($this, 'sort'));

        if ($this->count() < 1) {
            return false;
        }

        return true;
    }

    /**
     * Read old weblogs data..
     */
    function readOld() {

        $this->setUpgraded();

        $oldweblogs = loadSerialize(dirname(__FILE__)."/pv_cfg_weblogs.php", true);

        // Looping over old weblogs. For each old weblog, add a new one with
        // defaults values and then override the ones already set in the 
        // old config. This way we remove settings no longer present in 
        // PivotX. We also make sure the categories are all 'safe strings'..
        if(is_array($oldweblogs)) {
            foreach($oldweblogs as $weblogkey => $weblog) {
                $newweblogkey = safeString($weblogkey,true);
                $this->add($newweblogkey, $oldweblogs[$weblogkey]['name'], 'pivotxdefault');
                foreach ($this->data[$newweblogkey] as $key => $value) {
                    if (isset($weblog[$key])) {
                        $this->data[$newweblogkey][$key] = $weblog[$key];
                    }
                }
                foreach($this->data[$newweblogkey]['sub_weblog'] as $subweblogkey => $subweblog) {
                    foreach($subweblog['categories'] as $categorykey => $category) {
                        $this->data[$newweblogkey]['sub_weblog'][$subweblogkey]['categories'][$categorykey] = 
                            safeString($category, true);
                    }
                }
                foreach($this->data[$newweblogkey]['categories'] as $categorykey => $category) {
                    $this->data[$newweblogkey]['categories'][$categorykey] = safeString($category, true);
                }
            }
        }

    }

    /**
     * Sort the weblogs based on string comparison of name.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        if ( (empty($a['sortorder']) && empty($b['sortorder'])) || ($a['sortorder'] == $b['sortorder']) ) {
            return strcmp($a['name'],$b['name']);
        } else {
            return ($a['sortorder'] < $b['sortorder']) ? -1 : 1;
        }

    }

    /**
     * Return all weblogs as an array
     *
     * @return array
     */
    function getWeblogs() {

        return $this->data;

    }

    /**
     * Returns an array with the weblog names.
     *
     * @return array
     */
    function getWeblogNames() {

        $names = array();

        foreach($this->data as $name=>$data) {
            $names[] = $name;
        }

        return $names;

    }

    /**
     * Check if a given $name is a weblog.
     *
     * @param string $name
     * @return boolean
     */
    function isWeblog($weblogname) {

        foreach ($this->data as $name=>$data) {
            if ($weblogname==$name) { return true; }
        }

        return false;
    }


    /**
     * Return the weblogs that have the given category or categories assigned
     * to them.
     *
     * @param array $categories
     */
    function getWeblogsWithCat($categories) {

        // $cats might be a string with one cat, if so, convert to array
        if (is_string($categories)) {
            $categories= array($categories);
        }

        $res=array();

        // search every weblog for all cats
        foreach ($this->data as $key => $weblog) {

            $weblogcategories = $this->getCategories($key);

            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $res[]=$key;
                }
            }

        }

        return array_unique($res);
    }

    /**
     * Get the categories from a certain weblog.
     *
     * @param string $weblogname
     * @return array
     */
    function getCategories($weblogname='') {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        $results = array();

        // Group the categories from the subweblogs together..
        if (is_array($this->data[$weblogname]['sub_weblog'])) {
            foreach ($this->data[$weblogname]['sub_weblog'] as $key=>$sub) {

                $cats = $sub['categories'];
                // $cats might be a string with one cat, if so, convert to array
                if (is_string($cats)) {
                  $cats= array($cats);
                }

                // Add them to results
                foreach($cats as $cat) {
                    $results[] = $cat;
                }
            }
        }

        return array_unique($results);

    }

    /**
     * Returns the given weblog as an array. If no weblogname was given, use
     * the current weblog.
     *
     * @param string $weblogname
     * @return array
     */
    function getWeblog($weblogname='') {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        return $this->data[$weblogname];

    }

    /**
     * Return a subweblog as an array
     *
     * @param string $weblogname
     * @return array
     */
    function getSubweblog($weblogname='', $subweblogname) {

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) { $weblogname = $this->getCurrent(); }

        return $this->data[$weblogname]['sub_weblog'][$subweblogname];

    }

    /**
     * Return the subweblogs of a given weblog as an array. It does this
     * by grabbing all [[weblog]] and [[ subweblog ]] tags from the templates
     * in the same folder as the template that was selected as the frontpage
     * template. Updates the subweblog info in the weblogs object.
     *
     * @param string $weblogname
     * @return array
     */
    function getSubweblogs($weblogname='') {
        global $PIVOTX;

        // if no weblogname was given, use the 'current'..
        if (empty($weblogname)) {
            $weblogname = $this->getCurrent();
        }

        $results = array();

        $weblog = $this->getWeblog($weblogname);
        $dirname = dirname($weblog['front_template']);

        if ( !is_dir($PIVOTX['paths']['templates_path'] . $dirname) || !is_readable($PIVOTX['paths']['templates_path'] . $dirname) ) {
            debug("Template folder $dirname doesn't exist or isn't readable");
            return array();
        }

        $dir = dir($PIVOTX['paths']['templates_path'] . $dirname);

        // Iterate through the files in the folder..
        while (false !== ($filename = $dir->read())) {
            $ext = getExtension($filename);
            if (in_array($ext, array('html', 'htm', 'tpl'))) {

                $template_html = loadTemplate($dirname . "/" . $filename);

                preg_match_all("/\[\[\s?(sub)?weblog([: ])(.*)?\]\]/mUi", $template_html, $matches);

                foreach($matches[3] as $key=>$match) {

                    // if $matches[2][$key] was a ':', we know it's an old pivot 1.x style [[ subweblog:name ]]
                    // We also must handle optional arguments to the subweblog.
                    if ($matches[2][$key]==":") {
                        $name = explode(':',$match);
                        $results[] = trim($name[0]);
                    } else {
                        preg_match("/name=['\"]([^'\"]*)/mi", $match, $name);
                        // subweblog 'archive' has a special role so skip it here (not when in the dashboard)
                        // this is to disregard its number of entries e.g. for the pager display and initial building of the front page
                        // as no pager is allowed for archive display the number is only irrelevant.
                        if ($name[1]=='archive' && isset($PIVOTX['parser'])) { 
                            $name[1] = '';
                        }
                        if ($name[1]!="") {
                            $results[] = $name[1];
                        }

                    }

                }

            }
        }
        $dir->close();

        $results = array_unique($results);

        // Remove any subweblogs that no longer exists from the weblog data.
        $updated = false;
        foreach ($this->data[$weblogname]['sub_weblog'] as $name => $value) {
            if (!in_array($name,$results)) {
                unset($this->data[$weblogname]['sub_weblog'][$name]);
                $updated = true;
            }
        }
        if ($updated) {
            $this->saveConfig(true);
        }

        return $results;

    }

    /**
     * Sets a given weblog as 'current' and returns false if the weblog
     * doesn't exist.
     *
     * @param string $weblogname
     * @return boolean
     */
    function setCurrent($weblogname='') {
        global $PIVOTX;
        
        $exists = true;

        if ( !isset($this->data[$weblogname]) ) {
            $exists = false;
            $weblogname = '';
        }

        if (empty($weblogname)) {
            $this->current = $this->default;
        } else  {
            $this->current = $weblogname;
        }

        return $exists;

    }

    /**
     * Sets a given weblog as 'current' based on a given category and returns false
     * if no matching weblog could be set.
     *
     * @param mixed $categories
     * @return boolean
     */
    function setCurrentFromCategory($categories) {

        // $categories might be a string (with comma seperated categories).
        if (is_string($categories)) {
            $categories = explode(",", $categories);
            $categories = array_map('trim', $categories);
        }
                
        // Check categories in current weblog first (if set) and then the 
        // default weblog
        if (!empty($this->current)) {
            $weblogcategories = $this->data[$this->current]['categories'];
            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    return true;
                }
            }
        } else {
            $weblogcategories = $this->data[$this->default]['categories'];
            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $this->setCurrent($this->default);
                    return true;
                }
            }
        }

        $skip_weblogs = array($this->current, $this->default);

        // search every weblog for all cats
        foreach ($this->data as $key => $weblog) {

            // Skip current and default since we checked them above
            if (in_array($key, $skip_weblogs)) {
                continue;
            }

            $weblogcategories = $this->getCategories($key);

            foreach ($categories as $cat) {
                if (in_array($cat, $weblogcategories)) {
                    $this->setCurrent($key);
                    return true;
                }
            }
        }

        return false;
        
    }

    /**
     * Gets the currently active weblog.
     *
     * @return
     */
    function getCurrent() {

        // Set the current weblog, just to be sure.
        if (empty($this->current)) { $this->setCurrent(""); }

        return $this->current;

    }

    /**
     * Gets the default weblog.
     *
     * @return
     */
    function getDefault() {

        return $this->default;

    }

    /**
     * Add a new weblog, based on $theme. returns the internal name used for
     * the weblog.
     *
     * @param string $internal
     * @param string $name
     * @param string $theme
     * @return string
     */
    function add($internal, $name, $theme) {

        if ( ($internal=="") || isset($this->data[$internal])) {
            // Make a new 'name'..
            for($i=1;$i<1000;$i++) {
                if (!isset($this->data[$internal . "_" . $i])) {
                    $internal = $internal . "_" . $i;
                    break;
                }
            }
        }

        if ($theme=="blank") {

            $this->data[$internal]['name']=$name;

            $this->saveConfig(true);

        } else if ($theme=="pivotxdefault") {

            $weblog = getDefaultWeblog();

            $weblog['name'] = $name;

            if (empty($weblog['sortorder'])) { $weblog['sortorder'] = 10; }
            $this->data[$internal] = $weblog;

            $this->saveConfig(true);


        } else {

            $weblog = loadSerialize($theme, true);

            $weblog['name'] = $name;

            if (empty($weblog['sortorder'])) { $weblog['sortorder'] = 10; }

            $this->data[$internal] = $weblog;

            $this->saveConfig(true);

        }

        return $internal;

    }

    /**
     * Delete a weblog
     *
     * @param string $weblogname
     */
    function delete($weblogname) {

        unset($this->data[$weblogname]);

        $this->saveConfig(true);

    }

    /**
     * Export a weblog as a theme file. The file is saved in the same folder as
     * the weblog's frontpage template.
     *
     * @param string $weblogname
     */
    function export($weblogname) {

        $weblog = $this->data[$weblogname];
        $filename = dirname("./templates/".$weblog['front_template'])."/".$weblogname.".theme";

        saveSerialize($filename, $weblog);

    }

    /**
     * Sets a property of a given weblog
     *
     * @param string $weblogname
     * @param string $key
     * @param string $value
     */
    function set($weblogname, $key, $value) {

        if (isset($this->data[$weblogname])) {

            if (strpos($key, "#")>0) {
                // we're setting something in a subweblog
                // we get these as linkdump#categories = linkdump,books,movies
                list($sub, $key) = explode("#", str_replace("[]", "", $key));


                if (strpos($value, ",")>0) {
                    $value = explode(",", $value);
                }

                $this->data[$weblogname]['sub_weblog'][$sub][$key] = $value;
                
                // we must update the list of categories for the weblog
                $categories = array();
                foreach ($this->data[$weblogname]['sub_weblog'] as $subweblog) {
                    $categories = array_merge($categories,$subweblog['categories']);
                } 
                $this->data[$weblogname]['categories'] = array_unique($categories);

            } else {

                if ($key == 'site_url') {
                    $this->data[$weblogname]['link'] = $this->_getLink($weblogname, $value);
                }

                $this->data[$weblogname][$key] = $value;

            }

            $this->saveConfig(true);

        } else {

            debug('tried to set a setting without passing a weblogname, or non-existing weblog');

        }

    }

    /**
     * Gets a property of a given weblog
     *
     * @param string $weblogname
     * @param string $key
     */
    function get($weblogname, $key) {

        if ($weblogname=="") {
            $weblogname = $this->getCurrent();
        }

        if (empty($this->data[$weblogname])) {
            static $noted = array();
            if (!isset($noted[$weblogname])) {
                // only show this message once in the lifetime of the request
                debug("Weblog '$weblogname' doesn't exist!");

                $noted[$weblogname] = true;
            }
            $weblogname = key($this->data);
        }

        return $this->data[$weblogname][$key];

    }

    /**
     * Calculates the link for a given weblog
     *
     * @param string $value
     * @param string $weblogname
     */
    function _getLink($weblogname, $value) {
        global $PIVOTX;
        
        $link = trim($value);
        if ($link == '') {
            if ($PIVOTX['config']->get('mod_rewrite')==0) {
                $link = $PIVOTX['paths']['site_url'] . '?w=' . $weblogname;
            } else {
                $prefix = getDefault( $PIVOTX['config']->get('localised_weblog_prefix'), "weblog");
                $link = $PIVOTX['paths']['site_url'] . $prefix . "/" . $weblogname;
            }
        } else {
            $ext = getExtension(basename($link));
            if ($ext == '') {
                $link = addTrailingSlash($link);
            }
        }

        return $link;
    }

    function deleteCategoryFromWeblogs($name) {
        
        // Remove it from all weblogs as well.
        $weblogs = $this->data;

        foreach($weblogs as $weblogkey=>$weblog) {
            foreach($weblog['sub_weblog'] as $subweblogkey=>$subweblog) {
                foreach($subweblog['categories'] as $catkey => $cat) {
                    if ($cat==$name) {
                        unset($weblogs[$weblogkey]['sub_weblog'][$subweblogkey]['categories'][$catkey]);
                    }
                }
                
            }
            foreach($weblogs[$weblogkey]['categories'] as $catkey => $cat) {
                if ($cat==$name) {
                    unset($weblogs[$weblogkey]['categories'][$catkey]);
                }
            }
        }
        
        $this->data = $weblogs;

        $this->saveConfig(true);
    }
}

/**
 * This class deals with the categories
 *
 */
class Categories extends BaseConfig {
    public function __construct() {
        parent::__construct('ser_categories.php');
    }

    public function Categories() {
        $this->__construct();
    }

    protected function verifyConfig() {
        if ($this->count() < 1) {
            return false;
        }

        return true;
    }

    protected function fixConfig() {
        $save = false;

        if ($this->count() < 1) {
            // hmm, couldn't find the data.. Perhaps try to import it from old Pivot 1.x
            $this->readOld();
            $save = true;
        }

        if ($this->count()<1) {
            // if there still are no categories, load the defaults
            $this->data = getDefaultCategories();
            $save = true;
        }

        if ($save) {
            $this->saveConfig(true);
        }
    }

    protected function organizeConfig() {
        usort($this->data, array($this, 'sort'));

        if (count($this->data) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Sort the categories based on the order and string comparison
     * of display name if order is identical.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function sort($a, $b) {
        global $PIVOTX;

        if ($PIVOTX['config']->get('sort_categories_by_alphabet')==true) {
            // If we set 'sort_categories_by_alphabet' to true, always sort by alphabet..
            return strcmp($a['display'],$b['display']);
        } else if ($a['order'] == $b['order']) {
            // Else sort by alphabet, if order is the same..
            return strcmp($a['display'],$b['display']);
        } else {
            // else sort by order..
            return ($a['order'] < $b['order']) ? -1 : 1;
        }

    }

    /**
     * Old save function
     */
    public function saveCategories() {
        return $this->saveConfig(true);
    }

    protected function readOld() {
        global $pivotx_path;

        $this->setUpgraded();

        // If the old config file doesn't exist or it isn't readable, we return false..
        if (!file_exists($pivotx_path.'pv_cfg_settings.php') || (!is_readable($pivotx_path.'pv_cfg_settings.php'))) {
            return false;
        }
        // get the config file
        $fh = file($pivotx_path.'pv_cfg_settings.php');

        foreach ($fh as $fh_this) {
            @list($name, $val) = explode("!", $fh_this);
            $Cfg[trim($name)] = trim($val);
        }
        //GetUserInfo();
        //ExpandSessions();

        $catnames = explode("|",$Cfg['cats']);

        // Check which categories are "hidden"..
        if (isset($Cfg['cats-searchexclusion'])) {
            $hiddenarray = explode('|', strtolower($Cfg['cats-searchexclusion']));
        } else {
            $hiddenarray = array();
        }

        // Check the category order..
        if (isset($Cfg['cats-order'])) {
            $temp = explode('|-|', strtolower($Cfg['cats-order']));

            foreach($temp as $item) {
                list($catname, $order) = explode("|", $item);
                $orderarray[strtolower($catname)] = $order;
            }
        } else {
            $orderarray = array();
        }


        $cats = array();

        foreach ($catnames as $cat) {

            // Skip empty category names.
            $catname = trim($cat);
            if ($catname == '') {
                continue;
            }
            
            $catname = strtolower($catname);

            if (isset($Cfg['cat-'.$cat])) {
                $users = explode('|', strtolower($Cfg['cat-'.$cat]));
            } else {
                $users = array();
            }

            // Make sure the users are 'safe strings'
            foreach($users as $key=>$user) {
                $users[$key] = safeString($user, true);
            }

            $cats[] = array (
                'name' => safeString($catname, true),
                'display' => $cat,
                'users' => $users,
                'hidden' => (in_array($catname, $hiddenarray)) ? 1 : 0,
                'order' => (isset($orderarray[$catname])) ? $orderarray[$catname] : 110,
                );

        }


        $this->data = $cats;

    }

    /**
     * change the settings for an existing category, or modify an existing one.
     *
     * @param string $name
     * @param array $cat
     */
    function setCategory($name, $cat) {

        $name = strtolower(safeString($name));
        $cat['name'] = strtolower(safeString($cat['name']));

        foreach($this->data as $key=>$val) {

            if ($name==$val['name']) {

                $this->data[$key] = $cat;
                $this->saveConfig(true);
                return;
            }

        }

        // Otherwise it must be a new one, let's add it:
        if(!empty($cat['name'])){
            $this->data[] = $cat;
            $this->saveConfig(true);
        }


    }

    /**
     * Get an array with all the categories. We filter the users to make sure we only
     * return users that still exist
     *
     * @return array
     */
    function getCategories() {
        global $PIVOTX;

        $results = $this->data;
        
        $users = $PIVOTX['users']->getUsernames();
        
        // Filter only existing users..
        foreach ($results as $key=>$value) {
            // Categories doesn't have to be assigned to any users.
            if (isset($results[$key]['users']) && is_array($results[$key]['users'])) {
                $results[$key]['users'] = array_intersect($results[$key]['users'], $users);
            } else {
                $results[$key]['users'] = array();
            }

        }

        return $results;

    }
    


    

    /**
     * Get a list of categories the user is allowed to post into
     */
    function allowedCategories($username) {

        $allowed = array();

        foreach($this->data as $cat) {

            if (in_array($username, $cat['users'])) {
                $allowed[$cat['name']] = $cat['name'];
            }

        }

        return $allowed;

    }

    /**
     * Allow a user to post in this category
     *
     * @param string $catname
     * @param string $username
     */
    function allowUser($catname, $username) {

        // Loop through all available categories
        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$catname) {

                // Add the username
                $this->data[$key]['users'][] = $username;

                // But remove duplicates
                $this->data[$key]['users'] = array_unique($this->data[$key]['users']);

            }

        }

    }


    /**
     * Disallow a user to post in this category
     *
     * @param string $catname
     * @param string $username
     */
    function disallowUser($catname, $username) {

        // Loop through all available categories
        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$catname) {

                // Loop through the users, and remove $username if present.
                foreach($cat['users'] as $userkey=>$catuser){
                    if ($catuser==$username) {
                        unset($this->data[$key]['users'][$userkey]);
                    }
                }

            }

        }

    }


    /**
     * Get a single category
     *
     * @param string $name
     * @return array
     */
    function getCategory($name) {

        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$name) {
                return $cat;
            }

        }

        return array();

    }

    /**
     * Get a list of all category names
     *
     * @return array
     */
    function getCategorynames() {

        $names = array();

        foreach($this->data as $cat) {
            $names[]=$cat['name'];
        }
        return $names;

    }


    /**
     * Check if a given $name is a category.
     *
     * @param string $name
     * @return boolean
     */
    function isCategory($name) {

        foreach($this->data as $cat) {
            if($name==$cat['name']) { return true; }
        }

        return false;

    }



    /**
     * Get a list of all category names in which we should NOT search
     *
     * @return array
     */
    function getSearchCategorynames() {

        $names = array();

        foreach($this->data as $cat) {
            if ($cat['hidden']!=1) {
                $names[]=$cat['name'];
            }
        }

        return $names;



    }


    /**
     * Delete a single category
     *
     * @param string $name
     */
    function deleteCategory($name) {
        global $PIVOTX;

        foreach($this->data as $key=>$cat) {

            if ($cat['name']==$name) {
                unset($this->data[$key]);
                $this->saveConfig(true);
                break;
            }

        }

        $PIVOTX['weblogs']->deleteCategoryFromWeblogs($name);

    }

}

/**
 * This class deals with Sessions: logging in, logging out, saving sessions
 * and performing checks for required userlevels.
 * 
 * This class protects the cookie/session against standard XSS attacks and 
 * sidejacking.
 *
 */
class Session {

    var $permsessions, $logins, $maxlogins, $message;
    /**
     * Initialisation
     *
     * @return Session
     */
    function Session() {
        global $PIVOTX;

        $this->cookie_lifespan = 60*60*24*30;  // 30 days
        $this->cookie_name = "pivotxsession"; 

        $this->maxlogins = getDefault($PIVOTX['config']->get('loginlog_length'), 200);
        if (intval($this->maxlogins) < 10) {
            $this->maxlogins = 200;
        }

        // Select the secure bit for the session cookie. Setting it to true if
        // using HTTPS which stops sidejacking / session hijacking.
        // If we're on regular HTTP, $_SERVER['HTTPS'] will be 'empty' on Apache 
        // servers, and have a value of 'off' on IIS servers.  
        if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS'])=="off" ) {
            $this->cookie_secure = false;
        } else {
            $this->cookie_secure = true;
        }
        
        // Force cookie to be "HTTP only" to make cookie stealing harder - stops
        // standard XSS attacks. (Introduced in PHP 5.2.0.)
        if (checkVersion(phpversion(), '5.2.0')) {
            $this->cookie_httponly = true;
        } else {
            $this->cookie_httponly = false;
        }

        // On second thought, our CSRF check (that uses the double cookie submit 
        // test) needs to access the cookie ... We just can't use "HTTP only".
        $this->cookie_httponly = false;

        // Set to 'site url' instead of 'pivotx_url', because then we
        // can use 'edit this entry' and the like.
        $this->cookie_path = $PIVOTX['paths']['site_url'];

        // Don't set the domain for a cookie on a "TLD" - like localhost ...
        // PS! We don't use $_SERVER["SERVER_NAME"] since we might be on an alias domain.
        if (strpos($_SERVER["HTTP_HOST"], ".") > 0) {
            // Split off any port numbers (if the server is running on a non-standard port).
            list($domain) = explode(':', $_SERVER["HTTP_HOST"]);
            if (preg_match("/^www\./",$domain)) {
                $this->cookie_domain = preg_replace("/^www/", "", $domain);
            } else {
                $this->cookie_domain = $domain;
            }
        } else {
            $this->cookie_domain = "";
        }

        // Only set "HTTP only" if supported
        if ($this->cookie_httponly) {
            session_set_cookie_params($this->cookie_lifespan, 
                $this->cookie_path, $this->cookie_domain, $this->cookie_secure, $this->cookie_httponly); 
        } else {
            session_set_cookie_params($this->cookie_lifespan, 
                $this->cookie_path, $this->cookie_domain, $this->cookie_secure); 
        }

        session_start();

    }

    /**
     * Sets a cookie taking into account the path, domain, secure connection 
     * and if "HTTP only" is supported. Basically a wrapper around setcookie.
     *
     * @param string $name
     * @param string $value
     * @param string $time
     */
    function setCookie($name, $value, $time='') {
        if ($time == '') {
            $time = time() + $this->cookie_lifespan;
        }
        if ($this->cookie_httponly) {
            $res = setcookie($name, $value, $time, $this->cookie_path, 
                $this->cookie_domain, $this->cookie_secure, $this->cookie_httponly );
        } else {
            $res = setcookie($name, $value, $time, $this->cookie_path, 
                $this->cookie_domain, $this->cookie_secure );
        }
        
        // Add some debug output, if we couldn't set the cookie.
        if ($res==false) {
            debug("Couldn't set cookies! (probably because output has already started)");
            if (headers_sent($filename, $linenum)) {
                debug("Headers already sent in $filename on line $linenum");
            } else {
                debug("Headers have not been sent yet. Something's wonky.");
            }
        }
        
    }

    /**
     * Verify if whomever requested the current page is logged in as a user,
     * or else attempt to (transparently) continue from a saved session.
     *
     * @return boolean
     */
    function isLoggedIn() {
        global $PIVOTX;
        
        $this->loadPermsessions();

        $sessioncookie = (!empty($_COOKIE['pivotxsession'])) ? $_COOKIE['pivotxsession'] : $_POST['pivotxsession'];

        if (isset($_SESSION['user']) && isset($_SESSION['user']['username']) && ($_SESSION['user']['username']!="") ) {

            // User is logged in!
            
            // Check if we're in the saved sessions.. 
            if (!empty($sessioncookie) && !isset($this->permsessions[$sessioncookie])) {
            
                $this->permsessions[ $sessioncookie ] = array(
                    'username' => $_SESSION['user']['username'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'lastseen' => time()
                );
                $this->savePermsessions();         
            }
            
            return true;

        } else {

            // See if we can continue a stored session..

            // Check if we have a pivotxsession cookie that matches a saved session..
            if ( (!empty($sessioncookie)) && (isset($this->permsessions[$sessioncookie])) ) {

                $savedsess = $this->permsessions[ $sessioncookie ];
                
                // Check if the IP in the saved session matches the IP of the user..
                if ($_SERVER['REMOTE_ADDR'] == $savedsess['ip']) {

                    // Check if the 'lastseen' wasn't too long ago..
                    if (time() < ($savedsess['lastseen'] + $this->cookie_lifespan) ) {

                        // Finally check if the user in the stored session still exists.
                        if (!$PIVOTX['users']->isUser($savedsess['username'])) {
                            return false;
                        }

                        // If we get here, we can restore the session!

                        $_SESSION['user']= $PIVOTX['users']->getUser($savedsess['username']);

                        // Update the 'lastseen' in permanent sessions.
                        $this->permsessions[ $sessioncookie ]['lastseen'] = time();
                        $this->savePermsessions();

                        // Add the 'lastseen' to the user settings.
                        $PIVOTX['users']->updateUser($savedsess['username'], array('lastseen'=>time()) );
                        $_SESSION['user']['lastseen'] = time();

                        // Set the session cookie as session variable.
                        $_SESSION['pivotxsession'] = $sessioncookie;

                        return true;

                    }

                }

            }
            return false;

        }

    }

    /**
     * Attempt to log in a user, using the passed credentials. If succesfull,
     * the session info is updated and 'true' is returned. When unsuccesful
     * the session remains unaltered, and false is returned
     *
     *
     * @param string $username
     * @param string $password
     * @param int $stay
     * @return boolean
     */
    function login($username, $password, $stay) {
        global $PIVOTX;

        $this->loadLogins();

        if (!$this->checkFailedLogins()) {
            debug(sprintf(__("Blocked login attempt from '%s'."), $_SERVER['REMOTE_ADDR']));
            $this->message = __('Too many failed login attempts from this IP address. ' . 
                'Please contact your site administrator to unblock your account.');
            return false;
        }

        $username = strtolower($username);

        $match = $PIVOTX['users']->checkPassword($username, $password);

        if (!$match) {

            $this->message = __('Incorrect username/password');
            $this->logFailedLogin();
            return false;

        } else {

            $this->message = __('Successfully logged in');
            $key = makeKey(16);
            $_SESSION['pivotxsession'] = $key;

            // Add the 'lastseen' to the user settings and remove and reset_ids.
            $PIVOTX['users']->updateUser($username, array('lastseen'=>time(), 'reset_id'=>'') );

            // Keep track of people logging in (and remove any failed logins 
            // for IP if any).
            $this->logins['succeeded'][] = array(
                'username' => $username,
                'time' => time(),
                'ip' => $_SERVER['REMOTE_ADDR']
            );
            unset($this->logins['failed'][$_SERVER['REMOTE_ADDR']]);
            $this->saveLogins();

            $_SESSION['user']= $PIVOTX['users']->getUser($username);

            $path = $PIVOTX['paths']['site_url']; // Set to 'site url' instead of 'pivotx_url', because then we
                                        // can use 'edit this entry' and the like.

            if ($stay==1) {

                $this->setCookie($this->cookie_name, $key);

            } else {

                $this->setCookie($this->cookie_name, $key, 0);

            }

            $this->permsessions[ $key ] = array(
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'lastseen' => time()
            );

            $this->savePermsessions();

            return true;
        }

    }

    /**
     * Logs failed login attempts so PivotX can block brute force attacks.
     * 
     */
    function logFailedLogin() {
        global $PIVOTX;

        $ip = $_SERVER['REMOTE_ADDR'];
        

        $this->logins['failed'][ $ip ] = array(
          'attempts' => $this->logins['failed'][ $ip ]['attempts'] + 1,
          'time' => time()      
        );
            
        $this->saveLogins();
        debug(sprintf(__("Failed login attempt from '%s'."), $_SERVER['REMOTE_ADDR']));
    }

    /**
     * Checks failed login attempts so PivotX can block brute force attacks.
     * 
     */
    function checkFailedLogins() {
        global $PIVOTX;
        
        $limit = getDefault($PIVOTX['config']->get('failed_logins_limit'), 8);
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if ($this->logins['failed'][ $ip ]['attempts'] > $limit) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Log out a user: clear the session, and delete the cookie
     *
     */
    function logout() {
        global $PIVOTX;

        $this->loadPermsessions();

        // remove current session (by username, so if the user logs out on
        // one location, he logs out everywhere)..
        foreach ($this->permsessions as $key => $session) {
            if ($session['username']==$_SESSION['user']['username']) {
                unset($this->permsessions[$key]);
            }
        }

        $PIVOTX['events']->add('logout');
        $this->savePermsessions();

        // End the session..
        unset($_SESSION['user']);
        $this->setCookie($this->cookie_name, '', time()-10000 );

        session_destroy();

    }

    /**
     * Returns the latest/current message.
     *
     * @return array
     */
    function getMessage() {

        return $this->message;

    }

    /**
     * Returns the current user.
     *
     * @return array
     */
    function currentUser() {

        return $_SESSION['user'];

    }


    /**
     * Sets the specifics for the current user..
     *
     * @param array $user
     */
    function setUser($user) {

        $_SESSION['user'] = $user;

    }


    /**
     * Returns the username of the current user.
     *
     * @return array
     */
    function currentUsername() {

        return $_SESSION['user']['username'];

    }


    /**
     * Checks if the currently logged in user has at least the required level
     * to view the page he/she is trying to access.
     *
     * If not, the user is logged out of the system.
     *
     * @param int $level
     */
    function minLevel($level) {
        global $PIVOTX;

        $this->isLoggedIn();

        if ($level>$_SESSION['user']['userlevel']) {
            debug("logged out because the user's level was too low, or not logged in at all");
            
            // If $PIVOTX['paths'] is set and the headers aren't sent yet, redirect
            // to the login page, via the logout page.
            if (!empty($PIVOTX['paths']['pivotx_url']) && !headers_sent() ) {
                header("Location: ". $PIVOTX['paths']['pivotx_url']."?page=logout");
            } else {
                // otherwise, just display it, as good as we can. 
                pageLogout();
            }
            die();                    
        }

    }


    /**
     * Checks if the current request is accompanied by the correct
     * CSRF check.
     *
     * If not, the user is logged out of the system.
     *
     * @param int $value
     */
    function checkCSRF($value) {

        if ($value != $_SESSION['pivotxsession']) {
            debug( sprintf("CSRF check failed: '%s..' vs. '%s..'",
                substr($value,0,8), substr($_SESSION['pivotxsession'],0,8) ));
            pageLogout();
            die();
        }

    }

    /**
     * Get the key to use in the CSRF checks.
     *
     */
    function getCSRF() {

        return $_SESSION['pivotxsession'];

    }


    /**
     * Save permanent sessions to the filesystem, for users that check 'keep
     * me logged in'.
     *
     * The sessions are saved in db/ser_sessions.php, and they look somewhat like
     * Array
     * (
     *     [8nkvr62i3s37] => Array
     *         (
     *             [username] => admin
     *             [ip] => 127.0.0.1
     *             [lastseen] => 1168177821
     *         )
     * )
     *
     */
    function savePermsessions() {
        global $PIVOTX;

        saveSerialize($PIVOTX['paths']['db_path'] . "ser_sessions.php", $this->permsessions);

    }


    /**
     * Load the permanent sessions from the filesystem.
     *
     */
    function loadPermsessions() {
        global $PIVOTX;

        $this->permsessions = loadSerialize($PIVOTX['paths']['db_path'] . "ser_sessions.php", true);

        // Remove stale sessions after loading.
        foreach ($this->permsessions as $key=>$session) {
            if(($session['lastseen']+ $this->cookie_lifespan) < time() ) {
                unset($this->permsessions[$key]);
            }
        }

    }

    /**
     * Save login attempts from the filesystem.
     */
    function saveLogins() {
        global $PIVOTX;

        // Trim the logins log, if it's too long.
        if (count($this->logins['failed']) > $this->maxlogins) {
            $this->logins['failed'] = array_slice($this->logins['failed'], -$this->maxlogins);
        }
        if (count($this->logins['succeeded']) > $this->maxlogins) {
            $this->logins['succeeded'] = array_slice($this->logins['succeeded'], -$this->maxlogins);
        }

        saveSerialize($PIVOTX['paths']['db_path'] . "ser_logins.php", $this->logins);

    }


    /**
     * Load stored login attempts from the filesystem.
     */
    function loadLogins() {
        global $PIVOTX;

        $timeout = getDefault($PIVOTX['config']->get('failed_logins_timeout'), 24);
    
        $this->logins = loadSerialize($PIVOTX['paths']['db_path'] . "ser_logins.php", true);

        // Set timeout to the timestamp at which the block needs to be dropped.
        $timeout = time() - ($timeout*3600);

        // Iterate over the failed attempts, to see if they need to be dropped.
        foreach ($this->logins['failed'] as $ip => $item) {
            if ($item['time']<$timeout) {
                unset($this->logins['failed'][$ip]);
            }
        }

    }


    /**
     * Sets a session value, and then saves it.
     *
     * @param string $key
     * @param unknown_type $value
     */
    function setValue($key, $value=false) {
        if($value) {
            $_SESSION[$key] = $value;
        } else {
            unset($_SESSION[$key]);
            
        }

    }


    /**
     * Gets a single session value
     *
     * @param string $key
     * @return string
     */
    function getValue($key) {
        return $_SESSION[$key];

    }
}




/**
 * This class deals with Pages.
 *
 */
class Pages {

    var $index;
    var $currentpage;

    /**
     * Initialisation
     *
     * @return Pages
     */
    function Pages() {
        global $PIVOTX;

        if ($PIVOTX['config']->get('db_model')=="flat") {
            require_once("modules/pages_flat.php");
            $this->db = new PagesFlat();
        } else if ( ($PIVOTX['config']->get('db_model')=="mysql") ||
                ($PIVOTX['config']->get('db_model')=="sqlite") ||
                ($PIVOTX['config']->get('db_model')=="postgresql") ) {
            require_once("modules/pages_sql.php");
            $this->db = new PagesSql();
        } else {
            // TODO: In case of a fatal error, we should give the user the chance to reset the
            // Config to the default state, and try again.
            die("Unknown DB Model! PivotX can not continue!");
        }

        $this->currentpage = array();

        $this->getIndex();

    }

    /**
     * Get the index of the available chapters and pages.
     *
     * @return array
     */
    function getIndex($excerpts=false, $links=false) {
        global $PIVOTX;

        $filteruser = "";

        // Check if we need to filter for a user, based on the 'show_only_own_userlevel'
        // settings.. We do this only when not rendering a weblog, otherwise the
        // pages that are filtered out won't show up on the site. 
        if (!defined('PIVOTX_INWEBLOG')) {
            $currentuser = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
            $currentuserlevel = (!$currentuser?1:$currentuser['userlevel']);
    
            if ( $currentuserlevel <= $PIVOTX['config']->get('show_only_own_userlevel') ) {
                $filteruser = $currentuser['username'];
            }    
        }

        $this->index = $this->db->getIndex($filteruser, $excerpts, $links);

        return $this->index;

    }

    /**
     * Get the information for a specific Chapter
     *
     * @param integer $id
     * @return array
     */
    function getChapter($id) {

        return $this->index[$id];

    }

    /**
     * Add a chapter, and save the index
     *
     * @param array $chapter
     */
    function addChapter($chapter) {

        $this->index = $this->db->addChapter($chapter);

        $this->saveIndex(false);

    }


    /**
     * Delete a chapter, and save the index
     *
     * @param integer $uid
     */
    function delChapter($uid) {

        $this->index = $this->db->delChapter($uid);

        $this->saveIndex(false);

    }


    /**
     * Update the information for a chapter, and save the index
     *
     * @param integer $uid
     * @param array $chapter
     */
    function updateChapter($uid,$chapter) {

        $this->index = $this->db->updateChapter($uid,$chapter);

        $this->saveIndex(false);
    }


    /**
     * Save the index to the DB, using the selected model.
     *
     * @param boolean $reindex
     */
    function saveIndex($reindex=true) {

        uasort($this->index, array($this, 'chapSort'));

        $this->db->setIndex($this->index);

        $this->db->saveIndex($reindex);

    }


    /**
     * Get a single page
     *
     * @param integer $uid
     * @return array
     */
    function getPage($uid) {

        $page = $this->db->getPage($uid);

        $this->currentpage = $page;

        return $page;

    }

    /**
     * Get a single page, as defined by its URI
     *
     * @param string $uri
     * @return array
     */
    function getPageByUri($uri) {

        $page = $this->db->getPageByUri($uri);

        $this->currentpage = $page;
        
        return $page;

    }

    /**
     * Gets the current page
     */
    function getCurrentPage() {
        
        return $this->currentpage;
        
    }

    /**
     * Gets the $amount latest pages as an array, suitable for displaying an
     * overview
     *
     * @param integer $amount
     */
    function getLatestPages($amount, $filter_user="") {

        $pages = $this->db->getLatestPages($amount, $filter_user);

        return $pages;

    }

    /**
     * Delete a single page
     *
     * @param integer $uid
     */
    function delPage($uid) {

        $this->db->delPage($uid);

    }


    /**
     * Save a single page. Returns the uid of the inserted page.
     *
     * @param array $page
     * @return integer.
     */
    function savePage($page) {

        $this->currentpage = $page;

        return $this->db->savePage($page);
    

    }

    /**
     * Sort the chapters based on the order and string comparison
     * of chapter name if order is identical.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    function chapSort($a, $b) {
        global $PIVOTX;

        if ($PIVOTX['config']->get('sort_chapters_by_alphabet')==true) {
            // If we set 'sort_chapters_by_alphabet' to true, always sort by alphabet..
            return strcmp($a['chaptername'],$b['chaptername']);
        } else if ($a['sortorder'] == $b['sortorder']) {
            // Else sort by alphabet, if order is the same..
            return strcmp($a['chaptername'],$b['chaptername']);
        } else {
            // else sort by order..
            return ($a['sortorder'] < $b['sortorder']) ? -1 : 1;
        }

    }


    /**
     * Checks if any pages set to 'timed publish' should be published.
     *
     */
    function checkTimedPublish() {
        $this->db->checkTimedPublish();
    }
    
}



/**
 * The class that does the work for the paging and paging_subweblog snippets.
 *
 * @author Hans Fredrik Nordhaug <hansfn@gmail.com>, The PivotX dev. Team.
 */
class Paging {
    var $offset;
    var $name;

    function Paging($name) {
        $this->name = $name;
    }

    function sanity_check($action) {
        global $PIVOTX;
        list($action,$dummy) = explode('|',$action);
        if (($action != "next") && ($action != "prev") &&
            ($action != "curr") && ($action != "digg")) {
            return "<!-- snippet {$this->name} error: unknow action '$action' -->\n";
        }

        // Only display the paging snippet on weblog pages
        $modifier = $PIVOTX['parser']->get('modifier');
        if (($modifier['action'] != 'weblog') || !empty($modifier['archive'])) {
            return "<!-- snippet {$this->name} ($action): only output on weblog pages -->\n";
        }
        return;
    }

    function setup() {
        // Determine the offset
        if (!isset($_GET['o'])) {
            $this->offset = 0;
        } elseif (is_numeric($_GET['o'])) {
            $this->offset = $_GET['o'];
        } else {
            return "<!-- snippet {$this->name} error: offset isn't numeric -->\n";
        }


        return;
    }


    function doit($action, $text, $cats, $amountperpage, $params) {
        global $PIVOTX;

        $Current_weblog = $PIVOTX['weblogs']->getCurrent();
        $modifier = $PIVOTX['parser']->get('modifier');

        // $amountperpage must be numeric, one or larger
        if (!is_numeric($amountperpage) || ($amountperpage<1)) {
            return "<!-- snippet {$this->name} error: invalid number of entries to skip ($amountperpage) -->\n";
        }

        // Preserving some query parameters
        $query = array();
        if (isset($_GET['w']) && (empty($_GET['rewrite']) || ($_GET['rewrite'] == 'offset'))) {
            $query['w'] = 'w=' . $_GET['w'];
        }
        if (isset($_GET['t'])) {
            $query['t'] = 't=' . $_GET['t'];
        }
        if (!empty($_GET['u'])) {
            $query['u'] = 'u=' . $_GET['u'];
        }

        // Setting the text for the links
        if ($action == "next") {
            $text = getDefault($params['format'], __("Next page")." &#187;" );
        } elseif ($action == "prev") {
            $text = getDefault($params['format'], "&#171; ".__("Previous page"));
        } elseif ($action == "digg") {
            $text_prev = getDefault($params['format_prev'], "&#171; ".__("Previous page"));
            $text_next = getDefault($params['format_next'], __("Next page")." &#187;" );
        } else {
            $text = getDefault($params['format'], __("Displaying entries %num_from%-%num_to% of %num_tot%") );
        }

        // Get the maximum amount of pages to show.
        $max_digg_pages = getDefault($params['maxpages'], 9);

        // Get the id to attach to the <ul> for Digg style navigation.
        $digg_id = getDefault($params['id'], "pages");

        // Start the real work.
        $eachcatshash = md5(implodeDeep("", $cats));
        
        if ($PIVOTX['cache']->get('paging', $eachcatshash)) {
            // Check if this is in our simple cache?
            list($temp_tot, $num_tot) = $PIVOTX['cache']->get('paging', $eachcatshash); 
        } else {

            // Get the total amount of entries. How we do this depends on the used DB-model..
            // What we do is we get the amount of entries for each item in $cats.
            // For example, let's say we have 10 entries per page and 90 entries in one subweblog, and
            // 65 in the other. In this case we don't need (90+65)/10 pages, but (max(90,65))/10 pages.
            if ($PIVOTX['db']->db_type == "flat" ) {
                // Get the amount from the Flat files DB..
                $tot = $PIVOTX['db']->get_entries_count();
                foreach ($cats as $eachcats) {
                    if (!is_array($eachcats) && (trim($eachcats) == '')) {
                        continue;
                    }
                    $temp_tot = count($PIVOTX['db']->read_entries(array(
                        'show'=>$tot, 'cats'=>$eachcats, 'user'=>$_GET['u'], 'status'=>'publish')));
                    $num_tot = max( $num_tot, $temp_tot);
                }
            } else {
                // Get the amount from our SQL db..
                $sql = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
                $PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
                $entriestable = safeString($PIVOTX['config']->get('db_prefix')."entries", true);
                $categoriestable = safeString($PIVOTX['config']->get('db_prefix')."categories", true);

                    foreach ($cats as $eachcats) {
                        if (is_array($eachcats)) {
                            $eachcats = implode("','", $eachcats);
                        } else if (trim($eachcats) == '') { 
                            continue; 
                        }
                        $sql->query("SELECT COUNT(DISTINCT(e.uid)) FROM $entriestable AS e, $categoriestable as c
                        WHERE e.status='publish' AND e.uid=c.target_uid AND c.category IN ('$eachcats');");
                        $temp_tot = current($sql->fetch_row());
                        $num_tot = max( $num_tot, $temp_tot);
                }
            }
            $PIVOTX['cache']->set('paging', $eachcatshash, array($temp_tot, $num_tot));
        }

        $offset = intval($modifier['offset']);
        $num_pages = ceil($num_tot / $amountperpage);

        if ($num_tot == 0) {
            return "<!-- snippet {$this->name}: no entries -->\n";
        } elseif ($offset >= $num_pages) {
            return "<!-- snippet {$this->name}: no more entries -->\n";
        }

        if ($action == "next") {

            $offset++;

            if ($offset >= $num_pages) {
                return "<!-- snippet {$this->name} (next): no more entries -->\n";
            }

        } elseif ($action == "prev")  {

            if ($offset == 0) {
                return "<!-- snippet {$this->name} (previous): no previous entries -->\n";
            } else {
                $offset--;
            }

        } else {
            if ($num_tot == 0) {
                return "<!-- snippet {$this->name} (curr): no current entries -->\n";
            } else {
                $num = min($num,$num_tot);
            }

        }

        $num_from = $offset * $amountperpage + 1;
        $num_to = min($num_tot, ($offset+1) * $amountperpage);

        $text = str_replace("%num%", min($num_tot, $amountperpage), $text);
        $text = str_replace("%num_tot%", $num_tot, $text);
        $text = str_replace("%num_from%", $num_from, $text);
        $text = str_replace("%num_to%", $num_to, $text);

        if ($action == "curr") {
            return $text;
        }

        $site_url = getDefault($PIVOTX['weblogs']->get($Current_weblog, 'site_url'), $PIVOTX['paths']['site_url']);
        
        if ( (!empty($modifier['category']) || $params['catsinlink']==true) && $params['category']!="*" ) {
            // Ensure that we get a sorted list of unique categories in 
            // the URL - better SEO, one unique URL.
            $catslink = implodeDeep(",",$cats);
            $catslink = array_unique(explode(",",$catslink));
            sort($catslink, SORT_STRING);
            $catslink = implode(",",$catslink);
        }
 
        if ($PIVOTX['config']->get('mod_rewrite')==0) {
            if ( (!empty($modifier['category']) || $params['catsinlink']==true) && $params['category']!="*" ) {
                $link = $site_url . "?c=" . $catslink . "&amp;o=";
            } else {
                $link = $site_url . "?o=";
            }
        } else {
            if ( (!empty($modifier['category']) || $params['catsinlink']==true) && $params['category']!="*" ) {
                $categoryname = getDefault( $PIVOTX['config']->get('localised_category_prefix'), "category");
                $link = $site_url . $categoryname . "/" . $catslink . "/";
            } else {
                $pagesname = getDefault( $PIVOTX['config']->get('localised_browse_prefix'), "browse");
                $link = $site_url. $pagesname . "/";
            }
        }


        if ($action == 'digg') {
            $link .= '%offset%';
        } else {
            $link .= $offset;
        }

        if (!isset($query['w']) && paraWeblogNeeded($Current_weblog)) {
            if ($PIVOTX['config']->get('mod_rewrite')==0) {
                $link .= "&amp;w=".para_weblog($Current_weblog);
            } else {
                $link .= "/".para_weblog($Current_weblog);
            }
        }

        // Add the query parameters (if any)
        if (count($query) > 0) {
            $query = implode('&amp;', $query);
            if ($PIVOTX['config']->get('mod_rewrite')==0) {
                $link .= '&amp;' . $query;
            } else {
                $link .= '?' . $query;
            }
        }

        $link = str_replace(array('"',"'"), "", $link);

        if ($action != 'digg') {

            // Perhaps add some extra attributes to the <a> tag
            $extra = "";
            if (!empty($params['target'])) { $extra .= " target='" . $params['target'] ."'"; }
            if (!empty($params['class'])) { $extra .= " class='" . $params['class'] ."'"; }
            if (!empty($params['id'])) { $extra .= " id='" . $params['id'] ."'"; }
            if (!empty($params['datarole'])) { $extra .= " data-role='" . $params['datarole'] ."'"; }

            $output = sprintf('<a href="%s" %s>%s</a>', $link, $extra, $text);

            return $output;

        } else {

            $output ="
<div id=\"{$digg_id}\">
    <ul>
    %links%
    </ul>
</div>";
            $links = '';

            // Adding the previous link
            if ($offset == 0) {
                $links .= '<li class="nolink">%text_prev%</li>';
            } else {
                $links .= '<li><a href="%url%">%text_prev%</a></li>';

                $url = str_replace('%offset%',max(0,$offset-1),$link);
                $links = str_replace('%url%',$url,$links);
            }

            if ($num_pages > $max_digg_pages ) {
                // Limit the number of links/listed pages.

                $max_digg_pages = intval($max_digg_pages);

                $start = (int) ($offset - 0.5 * ($max_digg_pages-1));
                $start = max(0,$start) + 1;
                $stop = (int) ($offset + 0.5 * ($max_digg_pages-1));
                $stop = max(min(1000,$stop),3);
                $page = $offset;

                if ($offset==0) {
                    $links .= '<li class="current">1</li>';
                } else if ($start>=1) {
                    $links .= '<li><a href="%url%">1</a></li>';
                    if ($start>=2) {
                        $links .= '<li class="skip">&#8230;</li>';
                    }
                    $url = str_replace('%offset%',0,$link);
                    $links = str_replace('%url%',$url,$links);
                }
            } else {
                // Display all links/listed pages.
                $start = 0;
                $stop = 100;
            }


            // Adding all links before the current page
            while ($start < $offset) {
                $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                $url = str_replace('%offset%', $start, $link);
                $links = str_replace('%url%', $url, $links);
                $start++;
            }

            // Current page..
            if ($start == $offset) {
                $links .= '<li class="current">' . ($start+1) . '</li>';
                $start++;
            }

            // Adding all links after the current page
            while ($start < $num_pages) {
                if ($start < $stop) {
                    $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                    $url = str_replace('%offset%', $start, $link);
                    $links = str_replace('%url%', $url, $links);
                } else if ($start == ($num_pages-2) ) {
                    $links .= '<li class="skip">&#8230;</li>';
                } else if ($start == ($num_pages-1) ) {
                    $links .= '<li><a href="%url%">'.($start+1).'</a></li>';
                    $url = str_replace('%offset%', $start, $link);
                    $links = str_replace('%url%', $url, $links);
                }
                $page++;
                $start++;
            }


            // Adding the next link
            if ( ($offset+1) >= $num_pages) {
                $links .= '<li class="nolink">%text_next%</li>';
            } else {
                $links .= '<li><a href="%url%">%text_next%</a></li>';
                $url = str_replace('%offset%', $offset + 1, $link);
                $links = str_replace('%url%', $url, $links);
            }
            $output = str_replace('%links%', $links, $output);
            $output = str_replace('%text_prev%', $text_prev, $output);
            $output = str_replace('%text_next%', $text_next, $output);
            return $output;
        }
    }

}


/**
 * A Class that provides for very simple, in-memory caching. 
 *
 * @author Bob, The PivotX dev. Team.
 */
class Simplecache {
    
    var $cache;
    var $stats;
    var $keepstats;
    var $itemlimit;
    var $memlimit;

    function SimpleCache() {
        global $PIVOTX;
        
        $this->cache = array();
        $this->stats = array(
            'hits' => 0,
            'misses' => 0,
            'items' => 0,
            'size' => 0,
            'flushed' => 0
        );
        
        // Set the maximum number of items in the simple cache.
        $this->itemlimit = 400;   
        
        // Set the maximum amount of memory available.
        $this->memlimit = ini_get('memory_limit');
        list($this->memlimit) = sscanf($this->memlimit, "%dM");
        if (!empty($this->memlimit)) {
            $this->memlimit = $this->memlimit * 1048576;
        } else {
            $this->memlimit = 64 * 1048576;
        }
     
        $this->keepstats = $PIVOTX['config']->get('debug_cachestats');
        
    }
    
    /**
     * Set a single item in the cache
     *
     * @param string $type
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    function set($type="general", $key, $value) {
        
        // Check if the $type and $key are OK
        if (empty($key) || (!is_string($key) && !is_integer($key) ) || !is_string($type)) {
            // debug("Not Set: $type - $key");
            return false;
        }
        
        if ($this->keepstats) {
            $this->stats['sets'][$type][$key]++;
        }
        
        if (!isset($this->cache[$type][$key])) {
            $this->stats['items']++;
        }
        
        $this->cache[$type][$key] = $value;
        
        $this->trim();
        
        return true;
        
    }

    /**
     * Set multiple items in the cache
     *
     * @param string $type
     * @param array $values
     * @return bool
     */
    function setMultiple($type="general", $values) {
        
        // Check if the $type and $key are OK
        if (empty($values) || !is_array($values) || !is_string($type)) {
            return false;
        }
        
        foreach($values as $key=>$value) {
            $this->set($type, $key, $value);    
        }
        
        return true;
        
    }

    /**
     * Get a single item from the cache. Returns the value on success, or false
     * when it's a miss. So, storing booleans in the cache isn't very convenient.
     *
     * @param string $type
     * @param string $key
     * @return mixed
     */
    function get($type="general", $key) {
    
        if ($this->keepstats) {
            $this->stats['gets'][$type][$key]++;
        }
    
        if (!empty($this->cache[$type][$key])) {
            // debug("Get(hit): $type - $key");
            $this->stats['hits']++;
            return $this->cache[$type][$key];
        } else {
            // debug("Get(miss): $type - $key");
            $this->stats['misses']++;
            return false;
        }
    
    }
    
    
    /**
     * Trims the cache, if it's getting too large.
     */
    function trim() {
        
        // Get the percentage of used memory..
        if (function_exists('memory_get_usage')) { 
            $mem = memory_get_usage();
        }
        
        if (!empty($mem) ) {
            $percentage = $mem / $this->memlimit;
        } else {
            $percentage = 0;
        }
                
        // check if we need to trim items. 
        if ( ($this->stats['items'] > $this->itemlimit) || ($percentage > 0.8) ) {
        
            reset($this->cache);

            // Remove one item from each cached type.
            // Note: we don't use foreach, because it uses more memory, which is
            // exactly what we don't want, if we have to trim the cache..
            while ($key = key($this->cache)) {

                if ($this->keepstats) {
                    debug('Simple cache flush: $key - ' . key($this->cache[$key]) );
                }
                
                array_shift($this->cache[$key]);
                $this->stats['items']--;
                $this->stats['flushed']++;
                
                next($this->cache);
            }
    

        }        
        
    }
    
    /**
     * Return some basic statistics for the cache..
     *
     * @return array
     */
    function stats() {
        
        $this->stats['size'] = strlen(serialize($this->cache));
        
        return $this->stats;
        
    }
    
    function clear() {
        $this->cache = array();
    }

}

class Minify {
    
    var $html;
    var $head;
    var $jsfiles;
    var $cssfiles;
    var $base;
    
    function Minify($html) {
        global $PIVOTX;
        
        $this->html = $html;
        
        // Set the base path..
        if (defined('PIVOTX_INWEBLOG')) {
            $this->base = $PIVOTX['paths']['site_url'];
        } else {
            $this->base = $PIVOTX['paths']['pivotx_url'];
        }

    }
    
    function minifyURLS() {

        // if the PHP version is too low, we return the HTML, without doing anything.
        if (!checkVersion(phpversion(), '5.1.6')) {
            debug('PHPversion too low to us Minify: ' . phpversion() );
            return $this->html;
        }
        
        $head = $this->_getHead();
        
        if (empty($this->head)) {
            debug("Couldn't find a <head> section to minify");
        } else {
            $this->_getScripts();
            $this->_minifyScriptURLs();        
        }
        
        $this->_getStylesheets();
        $this->_minifyStylesheetURLs();

        return $this->html;
    }
    
    /**
     * Get the head section.
     **/			
    function _getHead() {

        preg_match("/<head([^>]+)?".">.*?<\/head>/is", $this->html, $matches);

        if(!empty($matches[0])) {

            $head = $matches[0];

            // Pull out the comment blocks, so as to avoid touching conditional comments
            $head = preg_replace("/<!-- .*? -->/is", '', $head);		
        
        } else {         
            $head = "";
        }
        
        $this->head = $head;
        
    }    
    
    
    /**
     * Get the scripts from the head section.
     **/			
    function _getScripts() {
    
        $scripts = array();
        
        $regex = "/<script[^>]+type=(['\"])(text\/javascript)\\1([^>]+)?".">(.*)<\/script>/iUs";
        preg_match_all($regex, $this->head, $matches);


        if (!empty($matches[0])) {
            
            
            $scripts = $matches[0];
         
            // remove 'inline' js, and links to external resources..
            // We also skip files with an '?', because they have extra paremeters, indicating
            // that they are generated, so we shouldn't minify them.
            foreach ($scripts as $key => $script) {
                preg_match('/src=([\'"])(.*)\1/iUs', $script, $res);
                
                $res = $res[2];
                $ext = getExtension($res);
                
                if ( empty($res) || ($ext!="js") || (strpos($res, "ttp://")==1) || (strpos($res, "ttps://")==1) || (strpos($res, "?")>0) ) {
                    unset($scripts[$key]);
                    continue;
                }
                
                
            }
            
        }
        
        $this->jsfiles = $scripts;
        
    }
    
    /**
     * convert the found js files into one minify-link..
     */
    function _minifyScriptURLs() {
        global $PIVOTX;
        
        $sources = array();
        
        foreach ($this->jsfiles as $jsfile) {
            preg_match('/src=([\'"])(.*)\1/iUs', $jsfile, $res);
           
            $res = $res[2]; 
            // Add file paths to relative URLs..
            if (strpos($res, "/") !== 0) {
                $res = $this->base . $res;
            }
            $source = preg_replace('#'.$PIVOTX['paths']['site_url'].'#', '', $res, 1);
            // Only add a source once
            if (!in_array($source, $sources)) {
                $sources[] = $source;
            }
        }

        
        if (!empty($sources)) {

            $minifylink = sprintf("<scr"."ipt type=\"text/javascript\" src=\"%sincludes/minify/?f=%s\"></scr"."ipt>" ,
                    $PIVOTX['paths']['pivotx_url'],
                    implode(",", $sources)
                );
        
            // Replace the first link to PivotX JS file with the minify link and remove 
            // all other links to PivotX JS files:
            $this->html = str_replace(array_shift($this->jsfiles), $minifylink, $this->html);
            $this->html = str_replace($this->jsfiles, "", $this->html);
        
        }
        
    }
    
    
   /**
     * Get the stylesheets from the entire document.
     **/			
    function _getStylesheets() {
    
        $stylesheets = array();
        
        $regex = "/<link[^>]+text\/css[^>]+>/iUs";
        preg_match_all($regex, $this->html, $matches);

        if (!empty($matches[0])) {
            
            // remove links to external resources, and organize by 'media' type..
            foreach ($matches[0] as $key => $stylesheet) {
                preg_match('/href=[\'"](.*)[\'"]/iUs', $stylesheet, $res);
                
                $href = $res[1];
                $ext = getExtension($href);
                
                // We also skip files with an '?', because they have extra paremeters, indicating
                // that they are generated, so we shouldn't minify them.                
                if ( empty($href) || ($ext!="css") || (strpos($href, "ttp://")==1) || (strpos($href, "ttps://")==1) || (strpos($href, "?")>0) ) {
                    continue;
                }
                
                preg_match('/media=[\'"](.*)[\'"]/iUs', $stylesheet, $res);
                
                $media = $res[1];
                
                if ( empty($media) || ($media=="screen") ) {
                    $stylesheets['screen'][] = $stylesheet;
                } else {
                    $stylesheets[$media][] = $stylesheet;
                }
                
            }
            
        }
          
        $this->cssfiles = $stylesheets;
        
    }    
    
    
    /**
     * convert the found css files into one minify-link..
     */
    function _minifyStylesheetURLs() {
        global $PIVOTX;

        // Loop for each separate mediatype..
        foreach($this->cssfiles as $mediatype => $cssfiles) {
            
            $sources = array();
             
            foreach ($cssfiles as $cssfile) {
                preg_match('/href=[\'"](.*)[\'"]/iUs', $cssfile, $res);
                 
                // Add file paths to relative URLs..
                if (strpos($res[1], "/") !== 0) {
                    $res[1] = $this->base . $res[1];
                }                  
                $source = preg_replace('#'.$PIVOTX['paths']['site_url'].'#', '', $res[1], 1);
                // Only add a source once
                if (!in_array($source, $sources)) {
                    $sources[] = $source;
                }
 
            }
             
            if (!empty($sources)) {
           
            
                $minifylink = sprintf('<link href="%sincludes/minify/?url=%s&amp;f=%s" ' .
                    ' rel="stylesheet" type="text/css" media="%s" />' ,
                        $PIVOTX['paths']['pivotx_url'],
                        substr($PIVOTX['paths']['site_url'],0,strlen($PIVOTX['paths']['site_url'])-1),
                        implode(",", $sources),
                        $mediatype
                    );
               
                // Replace the javascript links in the source with the minify-link:
                $this->html = str_replace($cssfiles[0], $minifylink, $this->html);
            
                foreach($cssfiles as $cssfile) {
                    $this->html = str_replace($cssfile, "", $this->html);
                }
            
            }            
            
        }
         
    }
        
    /**
     * OutputSystem Filter Method
     */
    public static function osFilter($html)
    {
        $minify = new Minify($html);
        return $minify->minifyURLS();
    }
}


/**
 * Takes care of the systemwide events, such as "Mike logged in." or "Pablo changed
 * the config setting 'xxx'."
 *
 */
class Events {

    var $data;
    var $filename;
    var $edit_timeout;
    var $maxevents;

    function Events() {
        global $PIVOTX;
        
        $this->filename = "ser_events.php";
        
        $this->edittimeout = 60;
        $this->maxevents = getDefault($PIVOTX['config']->get('eventlog_length'), 200);
        
        $this->data = loadSerialize($PIVOTX['paths']['db_path'] . $this->filename, true);

        // Make sure we have a proper $this->maxevents..
        if (intval($this->maxevents) < 10) {
            $this->maxevents = 200;
        }

        // Make sure $this->data is set.
        if (empty($this->data) || !is_array($this->data)) {
            $this->data = array();
        }
        
    }

    function add($what, $uid, $extrainfo="") {
        global $PIVOTX;
        
        $timestamp = formatDate("", "%year%-%month%-%day%-%hour24%-%minute%");
        if (defined('PIVOTX_INADMIN') || defined('PIVOTX_INAJAXHELPER')) {
            $user = $PIVOTX['users']->getUser( $PIVOTX['session']->currentUsername() );
            $username = $user['username'];
        } else {
            $username = __('A visitor');
        }
        
        $event = array($timestamp, $username, $what, $uid, $extrainfo);

        array_push($this->data, $event);
        
        $this->save();
        
    }

    function save() {
        global $PIVOTX;

        // Trim the event log, if it's too long.
        if (count($this->data) > ($this->maxevents+10)) {
            $this->data = array_slice($this->data, -$this->maxevents);
        }

        saveSerialize($PIVOTX['paths']['db_path'] . $this->filename, $this->data);
        
    }


    /**
     * Get the last $amount events..
     */
    function get($amount=8) {
        global $PIVOTX;
       
        for ($i = count($this->data)-1; ($i>0 && $amount>0) ; $i-- ) {
        
            $event = $this->data[$i];
            
            // If $event[3] holds more than one uid, implode it to a string for printing.
            if (is_array($event[3])) {
                $event[3] = implode(", ", $event[3]);
            }

            // If $event[4] is set, escape it just to be sure.
            if (!empty($event[4])) {
                $event[4] = htmlspecialchars($event[4]);
            }
            
            $name = "<strong>" . $event[1] ."</strong>";
        
            $format = "";
            
            switch ($event[2]) {
                
                case 'edit_entry':
                    if (!$saved['entry'][$event[1]][$event[3]]) {
                        $format = sprintf( __("%s started editing entry '%s'."), $name, $event[4] );
                        $saved['entry'][$event[1]][$event[3]] = true;
                    }
                    break;

                case 'edit_page':
                    if (!$saved['page'][$event[1]][$event[3]]) {
                        $format = sprintf( __("%s started editing page '%s'."), $name, $event[4] );
                        $saved['page'][$event[1]][$event[3]] = true;
                    }
                    break;
                
                case 'save_entry':
                    $saved['entry'][$event[1]][$event[3]] = true;
                    $format = sprintf( __("%s saved entry '%s'."), $name, $event[4] );
                    break;
                
                case 'save_page':
                    $saved['page'][$event[1]][$event[3]] = true;
                    $format = sprintf( __("%s saved page '%s'."), $name, $event[4] );
                    break;
                
                case 'login':
                    $format = sprintf( __("%s logged in."), $name );
                    break;
                
                case 'logout':
                    $format = sprintf( __("%s logged out."), $name );
                    break;

                case 'failed_login':
                    $format = sprintf( __("Failed login attempt for '%s'."), $event[4] );
                    break;
                
                case 'edit_config':
                    $format = sprintf( __("%s edited the setting for '%s'."), $name, $event[3] );
                    break;                
                
                case 'add_weblog':
                    $format = sprintf( __("%s added weblog '%s'."), $name, $event[4] );
                    break;                
                
                case 'edit_weblog':
                    $format = sprintf( __("%s edited a weblog setting for '%s'."), $name, $event[4] );
                    break;                
                
                case 'delete_weblog':
                    $format = sprintf( __("%s deleted weblog '%s'."), $name, $event[4] );
                    break;                
                
                case 'save_file':
                    $format = sprintf( __("%s saved the file '%s'."), $name, $event[4] );
                    break;                
                
                case 'add_user':
                    $format = sprintf( __("%s added user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_user':
                    $format = sprintf( __("%s edited user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'delete_user':
                    $format = sprintf( __("%s deleted user '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_category':
                    $format = sprintf( __("%s edited category '%s'."), $name, $event[4] );
                    break;                
                              
                case 'delete_category':
                    $format = sprintf( __("%s deleted category '%s'."), $name, $event[4] );
                    break;
                
                case 'add_chapter':
                    $format = sprintf( __("%s added chapter '%s'."), $name, $event[4] );
                    break;                
                                
                case 'edit_chapter':
                    $format = sprintf( __("%s edited chapter '%s'."), $name, $event[4] );
                    break;                     
                              
                
                default:
                    if (!empty($event[4])) {
                        // Note: should we add a specific format for generic events with four parameters?
                        // I think not. If it's important enough, we should add a specific notice.
                        $format = sprintf( __("%s did '%s' on '%s'."), $name, $event[2], $event[4] );
                    } else if (!empty($event[3])) {
                        $format = sprintf( __("%s did '%s' on '%s'."), $name, $event[2], $event[3] );
                    } else {
                        $format = sprintf( __("%s did '%s'."), $name, $event[2] );  
                    }  
                    break;
                
            }
            
            
            if (!empty($format)) {
            
                $output[] = sprintf("<acronym title=\"%s\">%s</acronym>: %s",
                        formatDate($event[0], $PIVOTX['config']->get('fulldate_format')),
                        formatDateFuzzy($event[0]),
                        $format
                    );

                $amount--;
            }
            
        }
        
        return $output;
        
    }


}

?>
