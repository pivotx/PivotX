<?php

    /*********************************************************************************
    * J's trackback lib <J@hacked.it>                                                *
    * (c) 2003                                                                           *
    *********************************************************************************/
    
    // Sends a trackback ping.
    function trackback_send($t, $url, $title="", $blog_name="", $excerpt="") {
        
        // Parse the target
        $target = parse_url($t);
        if ($target["query"] != "") $target["query"] = "?".$target["query"];
        if (!is_numeric($target["port"])) $target["port"] = 80;
        
        // Open the socket
        $sock = fsockopen($target["host"], $target["port"]);
        
        // Something didn't work out, return
        if (!is_resource($sock)) return "trackback_send: Couldn't connect to $t.";

        // Put together the things we want to send
        $toSend = "url=".rawurlencode($url)."&title=".rawurlencode($title).
                  "&blog_name=".rawurlencode($blog_name)."&excerpt=".rawurlencode(strip_tags($excerpt));
        
        
        // Send the trackback
        fputs($sock, "POST ".$target["path"].$target["query"]." HTTP/1.1\n");
          fputs($sock, "Host: ".$target["host"]."\n");
          fputs($sock, "Content-type: application/x-www-form-urlencoded\n");
          fputs($sock, "Content-length: ". strlen($toSend)."\n");
          fputs($sock, "Connection: close\n\n");
        fputs($sock, $toSend);
        
        // Gather result
          while(!feof($sock)) {
                  $res .= fgets($sock, 128);
          }
        
        // Did the trackback ping work?
        
        
        // We don't need you anymore
        fclose($sock);        
        
        // Return success
        return 1;
    }
    
    
    
?>