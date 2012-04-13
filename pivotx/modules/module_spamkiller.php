<?php
/**
 * Contains all the functions needed for SpamQuiz and HashCash comment 
 * spam protection. Based on the excellent Pivot Blacklist (version 0.9.3)
 * written by Marco van Hylckama Vlieg.
 *   marco@i-marco.nl - http://www.i-marco.nl/
 *
 * Includes a modified/stripped down version of Elliott Back's WP-Hashcash
 * version 2.3 http://www.elliottback.com/
 *
 * Also contains some code to block/log trackback spam.
 *
 * @package pivotx
 * @subpackage modules
 */

/**
 * Log file for spamkiller module.
 */
$GLOBALS['spamkiller_log'] = $spamkiller_log = dirname(dirname(__FILE__))."/db/spamkiller.".date("Y-m").".log";


/**
 * Hashcash Class
 */
class pivotx_hashcash
{
    protected static $keys = false;
    protected $current_form = false;
    protected $current_key = false;
    protected $current_name = false;

    protected $debug = false;

    const MAX_KEY_RETENTION = 3600;     /* maximum age of the keys we keep */
    const MAX_KEY_PER_IP = 5;           /* maximum keys per ip we keep */
    const MAX_KEYS = 1000;              /* maximum number of keys we keep (approx. 280 bytes per key) */
    const MAX_KEYFILESIZE = 524288;     /* maxmum keyfile size, 512kb */
    const MAX_LOGFILESIZE = 1049600;    /* maximum logfile size, 1024kb */

    /**
     * Taken from WP-HashCash
     */
    protected function get_jscomputation($funcname,$val)
    {
        $js = 'function wphc_compute(){';

        switch(rand(0, 3)){
            /* Addition of n times of field value / n, + modulus:
             Time guarantee:  100 iterations or less */
            case 0:
                $inc = rand($val / 100, $val - 1);
                $n = floor($val / $inc);
                $r = $val % $inc;
            
                $js .= "var wphc_eax = $inc; ";
                for($i = 0; $i < $n - 1; $i++){
                    $js .= "wphc_eax += $inc; ";
                }
                
                $js .= "wphc_eax += $r; ";
                $js .= 'return wphc_eax; ';
                break;

                /* Conversion from binary:
            Time guarantee:  log(n) iterations or less */
            case 1:
                $binval = strrev(base_convert($val, 10, 2));
                $js .= "var wphc_eax = \"$binval\"; ";
                $js .= 'var wphc_ebx = 0; ';
                $js .= 'var wphc_ecx = 0; ';
                $js .= 'while(wphc_ecx < wphc_eax.length){ ';
                $js .= 'if(wphc_eax.charAt(wphc_ecx) == "1") { ';
                $js .= 'wphc_ebx += Math.pow(2, wphc_ecx); ';
                $js .= '} ';
                $js .= 'wphc_ecx++; ';
                $js .= '} ';
                $js .= 'return wphc_ebx;';
                
            break;
            
            /* Multiplication of square roots:
            Time guarantee:  constant time */
            case 2:
                $sqrt = floor(sqrt($val));
                $r = $val - ($sqrt * $sqrt);
                $js .= "return $sqrt * $sqrt + $r; ";
            break;
            
            /* Sum of random numbers to the final value:
            Time guarantee:  log(n) expected value */
            case 3:
                $js .= 'return ';
        
                $i = 0;
                while($val > 0){
                    if($i++ > 0)
                        $js .= '+';
                    
                    $temp = rand(1, $val);
                    $val -= $temp;
                    $js .= $temp;
                }
        
                $js .= ';';
            break;
        }
            
        $js .= '} wphc_compute();';
        
        // pack bytes
        if( !function_exists( 'strToLongs' ) ) {
        function strToLongs($s) {
            $l = array();
            
            // pad $s to some multiple of 4
            $s = preg_split('//', $s, -1, PREG_SPLIT_NO_EMPTY);
            
            while(count($s) % 4 != 0){
                $s [] = ' ';
            }
        
            for ($i = 0; $i < ceil(count($s)/4); $i++) {
                $l[$i] = ord($s[$i*4]) + (ord($s[$i*4+1]) << 8) + (ord($s[$i*4+2]) << 16) + (ord($s[$i*4+3]) << 24);
                }
        
            return $l;
        }
        }
        
        // xor all the bytes with a random key
        $key = rand(21474836, 2126008810);
        $js = strToLongs($js);
        
        for($i = 0; $i < count($js); $i++){
            $js[$i] = $js[$i] ^ $key;
        }
        
        // libs function encapsulation
        $libs = "function ".$funcname."(){\n";
        
        // write bytes to javascript, xor with key
        $libs .= "\tvar wphc_data = [".join(',',$js)."]; \n";
        
        // do the xor with key
        $libs .= "\n\tfor (var i=0; i<wphc_data.length; i++){\n";
        $libs .= "\t\twphc_data[i]=wphc_data[i]^$key;\n";
        $libs .= "\t}\n";
        
        // convert bytes back to string
        $libs .= "\n\tvar a = new Array(wphc_data.length); \n";
        $libs .= "\tfor (var i=0; i<wphc_data.length; i++) { \n";
        $libs .= "\t\ta[i] = String.fromCharCode(wphc_data[i] & 0xFF, wphc_data[i]>>>8 & 0xFF, ";
        $libs .= "wphc_data[i]>>>16 & 0xFF, wphc_data[i]>>>24 & 0xFF);\n";
        $libs .= "\t}\n";
        
        $libs .= "\n\treturn eval(a.join('')); \n";
        
        // call libs function
        $libs .= "}";
        
        // return code
        return $libs;
    }

    /**
     * Clean up the keys
     *
     * Keys get removed if older than an hour
     */
    protected function clean_keys($in_keys)
    {
        $keys = array();
        $now  = time();
        
        foreach($in_keys as $key) {
            $diff = $now - $key['time'];

            if (($diff > 0) && ($diff < self::MAX_KEY_RETENTION)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Load all keys
     */
    protected function load_keys()
    {
        global $PIVOTX;

        self::$keys = false;

        $fname = $PIVOTX['paths']['db_path'].'ser_spamkiller.php';

        if (is_file($fname) && (filesize($fname) > self::MAX_KEYFILESIZE)) {
            // we assume something is terribly wrong if the maximum filesize is reached
            @unlink($fname);
        }

        self::$keys = loadSerialize($fname, true);

        if (!is_array(self::$keys)) {
            self::$keys = array();
        }

        if (count(self::$keys) > self::MAX_KEYS) {
            // first we clean the keys
            self::$keys = $this->clean_keys(self::$keys);

            if (count(self::$keys) > self::MAX_KEYS) {
                // still have too many keys, we just throw away the old ones

                array_splice(self::$keys,self::MAX_KEYS);
            }

            // note: we only save keys when we get a store request
        }
    }
    
    /**
     * Save keys
     */
    protected function save_keys()
    {
        global $PIVOTX;

        saveSerialize($PIVOTX['paths']['db_path'].'ser_spamkiller.php',self::$keys);

        if (count(self::$keys) >= self::MAX_KEYS) {
            $this->logpost('Maximum number of keys are stored ('.count(self::$keys).')');
        }
    }

    /**
     * A simple HTML table overview of all the 'outstanding' keys
     */
    public function render_keys()
    {
        if (self::$keys === false) {
            $this->load_keys();
        }

        $cols = array('remote_addr','http_user_agent','form','time');

        $html  = '';
        $html .= '<table class="formclass" cellspacing="0" border="0">'."\n";

        $html .= "\t<thead>\n";
        $html .= "\t<tr>\n";
        foreach($cols as $c) {
            $html .= "\t\t<th>".$c."</th>\n";
        }
        $html .= "\t</tr>\n";
        $html .= "\t</thead>\n";

        $html .= "\t</body>\n";
        foreach(self::$keys as $key) {
            $html .= "\t".'<tr>'."\n";

            foreach($cols as $c) {
                $v = $key[$c];

                if ($c == 'time') {
                    $v = date('Y-m-d H:i:s',$v);
                }

                $html .= "\t\t<td>".htmlspecialchars($v).'</td>'."\n";
            }

            $html .= "\t".'</tr>'."\n";
        }
        $html .= "\t</tbody>\n";
        $html .= '</table>'."\n";

        return $html;
    }

    /**
     * Render the logfile in simple HTML
     *
     * The line order is reversed and each access is coloured differently
     *
     * @return string
     */
    public function render_log()
    {
        global $PIVOTX;

        $fname = $PIVOTX['paths']['db_path'].'spamkiller.'.date("Y-m").'.log';

        $html = '';

        if (is_file($fname)) {
            $lines = file($fname);
            $lines = array_reverse($lines);

            $html .= '<pre>';

            $last_date = false;
            foreach($lines as $line) {
                $ldate = substr($line,0,10);

                if ($ldate != $last_date) {
                    $last_date = $ldate;

                    $line = '<span style="display:inline-block;padding-top:10px"><strong>'.$ldate.'</strong> ' . trim(substr($line,10)) . '</span>'."\n";
                }

                $html .= $line;
            }

            $html .= '</pre>';
        }
        else {
            $html .= '<p>Logfile is empty.</p>';
        }

        return $html;
    }

    /**
     * Log something
     */
    protected function logpost($message)
    {
        global $PIVOTX;

        $fname = $PIVOTX['paths']['db_path'].'spamkiller.'.date("Y-m").'.log';
        if (is_file($fname) && (filesize($fname) > self::MAX_LOGFILESIZE)) {
            @unlink($fname);
        }

        $message .= ' {'.count(self::$keys).'/'.self::MAX_KEYS.'}';

        $msg = date('Y-m-d H:i:s').' ['.$_SERVER['REMOTE_ADDR'].'] '.$message.' ('.$_SERVER['HTTP_USER_AGENT'].')';

        $fp = fopen($fname,'a');
        fputs($fp,$msg . "\n");
        fclose($fp);
    }

    /**
     * Store key
     */
    public function store_key($key = false, $name = false)
    {
        if ($key === false) {
            $key = $this->current_key;
        }
        if ($name === false) {
            $name = $this->current_name;
        }

        if (!is_array(self::$keys)) {
            $this->load_keys();

            self::$keys = $this->clean_keys(self::$keys);
        }

        if (!is_array(self::$keys)) {
            self::$keys = array();
        }

        // check our ip limit
        // @todo Won't work with reverse proxies!
        $indexes = array();
        $cnt     = count(self::$keys);
        for($i=0; $i < $cnt; $i++) {
            if (self::$keys[$i]['remote_addr'] == $_SERVER['REMOTE_ADDR']) {
                $indexes[] = $i;
            }
        }
        if (count($indexes)+1 > self::MAX_KEY_PER_IP) {
            $this->logpost('Ip "'.$_SERVER['REMOTE_ADDR'].'" has too many outstanding hashcashes (#'.count($indexes).'), trimming');

            for($idx=count($indexes)-1; $idx >= self::MAX_KEY_PER_IP-1; $idx--) {
                array_splice(self::$keys,$indexes[$idx],1);
            }
            array_splice($indexes,self::MAX_KEY_PER_IP-1);

            //$this->logpost('Ip "'.$_SERVER['REMOTE_ADDR'].'" has '.count($indexes).' outstanding hashcashes');
        }

        // newer keys are always in the front of the array
        array_unshift(self::$keys,array(
            'form' => $this->current_form,
            'key' => $key,
            'name' => $name,
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'time' => time()
        ));

        $this->save_keys();

        if ($this->debug) {
            $this->logpost('Storing key for form "'.$this->current_form.'", key '.$this->current_key.', name '.$this->current_name);
        }
    }

    /**
     * Delete the current key
     *
     * Call this to don't allow the form to be submitted twice
     */
    public function delete_current_key()
    {
        $keys = array();
        foreach(self::$keys as $key) {
            if (($key['remote_addr'] == $_SERVER['REMOTE_ADDR']) &&
                ($key['http_user_agent'] == $_SERVER['HTTP_USER_AGENT']) &&
                ($key['key'] == $this->current_key) &&
                ($key['name'] == $this->current_name)) {
                // don't add this one

                if ($this->debug) {
                    $this->logpost('Deleted key '.$this->current_key.', name '.$this->current_name);
                }
            }
            else {
                $keys[] = $key;
            }
        }

        if ($this->debug && (count(self::$keys) == count($keys))) {
            $this->logpost('Cannot delete current key');
        }

        self::$keys = $keys;

        $this->save_keys();
    }

    /**
     * Validate a form
     *
     * If valid, the current key is set the one in the form and it is deleted.
     * If invalid, we log it.
     */
    public function validate_form($form_name, $postvalues)
    {
        if (self::$keys === false) {
            $this->load_keys();
        }

        // this routine is not optimal for a large number of keys
        foreach(self::$keys as $key) {
            if (($key['remote_addr'] == $_SERVER['REMOTE_ADDR']) &&
                ($key['http_user_agent'] == $_SERVER['HTTP_USER_AGENT'])) {
                if (isset($postvalues[$key['name']]) && ($postvalues[$key['name']] == $key['key'])) {
                    $this->current_key  = $key['key'];
                    $this->current_name = $key['name'];

                    if ($this->debug) {
                        $this->logpost('Form was valid');
                    }

                    $this->delete_current_key();

                    return true;
                }
            }
        }

        $message = 'Form submit for "'.$form_name.'" failed hashcash check';
        $this->logpost($message);

        return false;
    }

    /**
     * Get the generated key value
     */
    public function get_current_key()
    {
        if ($this->current_key === false) {
    		$this->current_key = rand(21474836, 2126008810);
        }

        return $this->current_key;
    }

    /**
     * Get the current form name
     */
    public function get_current_name()
    {
        if ($this->current_name === false) {
            $this->current_name = 'hc' . rand(1000,9999);
        }

        return $this->current_name;
    }

    /**
     * Get the javascript
     */
    public function get_javascript($form_name, $post_callback=false)
    {
        $func = 'hashcash_'.rand(1000,9999);
        $key  = $this->get_current_key();
        $hcid = $this->get_current_name();

        $this->current_form = $form_name;

        $bfhcjs = $this->get_jscomputation($func,$key);

        $cb_html = '';
        if ($post_callback !== false) {
            $cb_html = $post_callback . "\n";
        }

        $html = <<<THEEND
function addLoadEvent(func) {
  if( typeof jQuery != 'undefined' ) {
    jQuery(document).ready( func );
  } else if( typeof Prototype != 'undefined' ) {
    Event.observe( window, 'load', func );
  } else {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
      window.onload = func;
    } else {
      window.onload = function() {
        if (oldonload)
          oldonload();
        
        func();
      }
    }
  }
}

$bfhcjs

addLoadEvent(function(){
    var el = document.getElementById('$hcid');
    if (el) {
        el.value = $func();
        $cb_html
    }
});

THEEND;

        return $html;
    }
}


/**
 * Add hashcash to a form
 *
 * 1. Disable the form by default
 * 2. Add <noscript>-warning
 * 3. Add hidden variable
 * 4. Add javascript (the actual hashcash)
 *
 * @return boolean
 */
function add_hashcash($page, $form_name='anonymous') {
    $hc   = new pivotx_hashcash();
    $hcid = $hc->get_current_name();

    // 1 & 2
    //$page = str_replace('<form', '<noscript><p class="pivotx-message">'.__('Please enable Javascript (and reload this page) to add any comments.').'</p></noscript>'."\n".'<form disabled="disabled" id="submit-'.$hcid.'"',$page);
    $page = str_replace('<form', '<noscript><p class="pivotx-message">'.__('Please enable Javascript (and reload this page) to add any comments.').'</p></noscript>'."\n".'<form ',$page);
    $page = str_replace(' type="submit" name="post"',' type="submit" name="post" id="submit-'.$hcid.'" disabled="disabled"',$page);

    // 3
    $page = str_replace('</form>','<input type="hidden" id="'.$hcid.'" name="'.$hcid.'" value="" /></form>',$page);

    // 4
    $html  = '';
    $html .= <<< THEEND
<!--

function enableform_$hcid()
{
    el = document.getElementById("submit-$hcid");
    if (el) {
        el.disabled = '';
    }
}


THEEND;
    $html .= $hc->get_javascript($form_name,'enableform_'.$hcid.'();');
    $html .= '// -->'."\n";

    $hc->store_key();

    OutputSystem::instance()->addCode('hashcash-'.$hcid,OutputSystem::LOC_HEADEND,'script',array(),$html);


    // 

    return $page;
}



/**
 * Returns true if it matches the hidden md5'ed tag.
 *
 * @return boolean
 */
function hashcash_check_hidden_tag($form_name='anonymous') {
    $hc   = new pivotx_hashcash();

    if (!$hc->validate_form($form_name,$_POST)) {
        logspammer($_SERVER["REMOTE_ADDR"], 'hashcash');

        return false;
    }

    return true;
}


/**
 * Check the trackback for spam (currently using Hardened Trackback if enabled).
 *
 * @return void
 */
function killtrackbackspam() {
    global $PIVOTX;

    // Do nothing if hardened trackback isn't enabled.
    if ($PIVOTX['config']->get('hardened_trackback') != 1)  {
        return true;
    }
    $keydir = $PIVOTX['paths']["db_path"]."tbkeys/";
    $key = $_GET["key"];
    if(strlen($key) < 32) {
        logspammer('tampered key: invalid length', "htrackback", $_POST['url']);
        exit;
    } else {
        if(!preg_match('/^[a-f0-9]{32}$/',$_GET["key"])) {
            logspammer('tampered key: invalid characters found', "htrackback", $_POST['url']);
            exit;
        }
        if(file_exists($keydir.$key)) {
            $offset = timediffwebfile();
            if((time()-filectime($keydir.$key)) > (900 + $offset)) {
                @unlink($keydir.$key);
                // pbl_suspectIP($aConfig["blockstrikes"]);
                logspammer(stripslashes($_POST['excerpt']), "htrackback", $_POST['url']);
                exit;
            }
        } else {
            logspammer('key not found', "htrackback");
            exit;
        }
        unlink($keydir.$key);
    }
}


/**
 * Logs the blocked spam.
 *
 * @return void
 */
function logspammer($p_sSpam, $p_sType, $p_sAdditional="")  {
    global $spamkiller_log;

    // Check if we need to trim the spam logfile..
    trim_spamlog();

    $date = date("F j, Y, g:i a");
    $p_sSpam = trim(stripslashes(strip_tags($p_sSpam)));
    $p_sAdditional = trim(stripslashes(strip_tags($p_sAdditional)));
    foreach (array('piv_name', 'piv_email', 'piv_url') as $var) {
        if (isset($_POST[$var])) {
            $$var = $_POST[$var];
        } else {
            $$var = '';
        }
    }
    if (isset($_POST["piv_comment"])) {
        $p_comment = trim(stripslashes(strip_tags($piv_name . ", " .
            $piv_email . ", " . $piv_url . ", " . $_POST["piv_comment"])));
    }
    $fpHandle = fopen($spamkiller_log, "a");
    $p_sUrl = $piv_url;

    switch($p_sType)  {
        case "trackback":
            $desc = "Trackback blocked";
            $text = $p_sSpam;
            break;
        case "htrackback":
            $desc = "Trackback blocked (Hardened)";
            $text = $p_sSpam;
            $p_sUrl = $p_sAdditional;
            break;
        case "hashcash":
            $desc = "Hashcash violation";
            $text = sprintf($p_comment);
            break;
        case "spamquiz":
            $desc = "Wrong Quiz Answer";
            $text = $p_comment;
            break;
        case "sskc":
            $desc = "Server Key check";
            $text = $p_comment;
            break;
        case "bpcomment":
            $desc = "Blocked Phrases comment";
            $text = $p_sSpam;
            break;
        case "bpreferer":
            $desc = "Blocked Phrases referer";
            $text = $p_sSpam;
            break;
        case "closedcomments":
            $desc = "Comment on closed entry";
            $text = $p_comment;
            break;
        default:
            if ($p_sAdditional) {
                $desc = $p_sAdditional;
            } else {
                $desc = "Comment blocked";
            }
            $text = $p_comment;
            if (!$text) {
                $text = $p_sSpam;
            }
            break;
    }
    // $text must not contain any newlines.
    $text = str_replace(array("\r\n", "\n", "\r"), " ",$text);
    $info = array($date, $desc, $_SERVER["REMOTE_ADDR"], $p_sUrl , $text);
    $sLogLine = implode(" #### ", $info)."\n";
    fwrite($fpHandle, $sLogLine);
    fclose($fpHandle);

    // Keeping a global spamcounter seemed like a nice idea at the time, but
    // apparently, it can cause the pv_cfg file to become corrupted. If there's
    // demand for this feature, we'll implement it properly..
    // $Cfg['spamcount']++;
    // SaveSettings();

}

/**
 * Fetches the spam log inserted into a table.
 *
 * @return string
 */
function get_spamlog()  {
    global $spamkiller_log;

    // Check if we need to trim the spam logfile..
    trim_spamlog();

    if (file_exists($spamkiller_log))  {

        $fpHandle = fopen($spamkiller_log, "r");
        $nTotalLines = count(file($spamkiller_log));
        $sLogRows = "";
        $nNumb=0;
        $bLimit = false;
        $nLineCount = 0;
        if(isset($_GET["limit"]))   {
            $bLimit = true;
        }
        while (!feof($fpHandle)) {
            $sBuffer = fgets($fpHandle, 4096);
            $nLineCount++;
            $sThisLine = explode("####", $sBuffer);

            // Output the line..
            if (($sThisLine[0] != "") && (count($sThisLine)>2)) {
                if(($bLimit) && ($nLineCount <= ($nTotalLines-$_GET["limit"]))) {
                    $sLogRows = "";
                } else  {

                    if (($nLineCount % 2)==0) {
                        $bg_color="even";
                    } else {
                        $bg_color="odd";
                    }

                    $sLogRows .= "<tr class=\"$bg_color\">";
                    $sLogRows .= "<td>".$sThisLine[0]."&nbsp; &nbsp;</td><td>";
                    $sLogRows .= "<a target=\"_blank\" href=\"http://centralops.net/co/DomainDossier.".
                            "aspx?dom_whois=1&amp;net_whois=1&amp;dom_dns=1&amp;addr=".
                            trim($sThisLine[2])."\">".$sThisLine[2]."</a>&nbsp; &nbsp;</td>";
                    $sLogRows .= "<td>".$sThisLine[1]."&nbsp; &nbsp;</td></tr>";                            
                    $sLogRows .= "<tr class=\"$bg_color\">";
                    $sLogRows .= "<td colspan=\"3\" valign='top' >".htmlspecialchars($sThisLine[3])." ";
                    if (strlen($sThisLine[3])>4) { $sLogRows .= "<br />"; }
                    $sLogRows .= htmlspecialchars(substr($sThisLine[4],0,100))."</td>";
                    $sLogRows .= "</tr>\n";
                    
                }
            }
            if($nNumb == 0)
                $nNumb=1;
            else
                $nNumb=0;
        }
        fclose($fpHandle);
        if (!empty($sLogRows)) {
            return "<table class=\"tabular\" border='0' cellspacing='0' cellpadding='0'>\n".
            "<tr class='tabular_nav'><th>Date/Time</th><th>IP</th><th>Rule</th></tr>\n".
            $sLogRows."</table>\n";
        } else {
            return "";
        }
    }
}

/**
 * Set the content of the spam log.
 *
 * @param string $text
 * @return void
 */
function set_spamlog($text="")  {
    global $spamkiller_log;
    if(file_exists($spamkiller_log))    {
        $fpHandle = fopen($spamkiller_log, "w");
        fwrite($fpHandle, $text);
        fclose($fpHandle);
    }
}


/**
 * This function trims the spamlog file down, if needed.
 *
 * The spam logfile can easily become very large.. So large that it can't be
 * read by common PHP installs without going over the amount of allocated
 * memory. This function checks the filesize, and trims the file if it
 * becomes to large.
 *
 * It trims off about 1/3 of the entire file. This is to prevent that it has to
 * be trimmed too often, since that would be very server-unfriendly.
 *
 * @return void
 */
function trim_spamlog() {
    global $spamkiller_log;

    // If it exists, and is larger than ~ 1mb, we will have to remove it,
    // because trying to read it, may cause a Fatal error..
    if( file_exists($spamkiller_log) && (filesize($spamkiller_log)>1000000) ) {
        unlink($spamkiller_log);
    }

    // If it exists, and is larger than ~ 250kb.
    if( file_exists($spamkiller_log) && (filesize($spamkiller_log)>250000) ) {

        // Read the file.
        $logfile = file($spamkiller_log);

        // Slice off 1/3 of the file.
        $lines = count($logfile);
        $logfile = array_slice($logfile, intval($lines/3));

        // Save it again.
        set_spamlog(implode("", $logfile));

    }
}

?>
