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


class IPBlock {
    
    var $blocklist;
    var $blockedips;
    
    /**
     * Initialise the IP block.
     */
    function IPBlock() {
        global $PIVOTX;
       
        if (file_exists($PIVOTX['paths']['db_path'] . "blocked_ips.txt.php")) {
            $this->blocklist = file($PIVOTX['paths']['db_path'] . "blocked_ips.txt.php");
        } else {
            $this->blocklist[] = "0.0.0.0 # PivotX Blocklist";
        }        
        
        $this->blockedips = array();
        
        // Split the ip's and the comments..
        foreach($this->blocklist as $line) {
            list($ip, $comm) = explode("#", $line);
            $this->blockedips[] = trim($ip);    
        }
        
        
    }
    
    
    /**
     * Check if a given IP address is blocked..
     *
     * @param string $ip
     * @return boolean
     * 
     */
    function isBlocked($ip) {
       
        $original = explode(".", $ip);

        foreach ($this->blockedips as $testip) {

            $testip = trim($testip);

            // simple test if there are no ranges or wildcards..
            if ( (strpos($testip, "*") === false) && (strpos($testip, "-") === false)) {
                if ($testip==$ip) {
                    return true;
                }
            } else {
                // elaborate test
                $testip=explode(".", $testip);

                if ( $this->compareOctet($original[0], $testip[0]) &&
                    $this->compareOctet($original[1], $testip[1]) &&
                    $this->compareOctet($original[2], $testip[2]) &&
                    $this->compareOctet($original[3], $testip[3]) ) {
                    return true;
                }
            }
        }
    
        return FALSE;
        
    }
    
    /**
     * Add a given $ip to the blocklist..
     *
     * @param string $ip
     * @param string $comment
     * @param boolean $adddate
     * 
     */
    function add($ip, $comment, $adddate=true) {
        
        if (empty($comment)) {
            $comment = __("Added by PivotX on:") . " " . date("Y-m-d H:i");    
        } else if ($adddate) {
            $comment .= " - " . __("Added on:") . " " . date("Y-m-d H:i");
        }
        
        $this->blocklist[] = sprintf("%s # %s", trim($ip), trim($comment) );
        
        $this->save();
        
    }
    
    
    /**
     * Remove a blocked $ip from the list..
     *
     * @param string $ip
     * 
     */
    function remove($ip) {

        foreach($this->blocklist as $key=>$line) {
            
            list($lineip, $comm) = explode("#", $line);
            
            if (trim($ip) == trim($lineip)) {
                unset($this->blocklist[$key]);
                $this->save();
            }
            
        }
        
    }    
    
    
    /**
     * Save the blocklist to file..
     */
    function save() {
        global $PIVOTX;
    
        $fp=fopen($PIVOTX['paths']['db_path'] . "blocked_ips.txt.php", "w");
    
        if ($fp) {
            
            // Sort and remove double lines.
            $this->blocklist = array_unique($this->blocklist);
            natsort($this->blocklist);
    
            foreach ($this->blocklist as $line) {
                if (strlen($line)>3) {
                    fwrite($fp,trim($line)."\n");
                }
            }
            fclose($fp);
    
        } else {
            debug("couldn't write db/blocked_ips.txt.php");
            die();
        }        
        
    }
    
    
    /**
     * loose checking on octets. Allows for comparing ranges like 127.128.129.* or 127.128.129.40-80
     *
     * @param mixed $oct1
     * @param mixed $oct2
     * @return boolean
     */
    function compareOctet($oct1, $oct2) {
    
        if ($oct1==$oct2) {
            return true;
        }
        
        if ( ($oct1=="*") || ($oct2=="*") ) {
            return true;
        }
        
        if ( strpos($oct2,"-") > 0 ) {
            list($oct2_min, $oct2_max) = explode("-", $oct2);
            if ( ($oct1>=$oct2_min) && ($oct1<=$oct2_max) ) {
                return true;
            }
        }
    
        return false;
    
    }

}




?>
