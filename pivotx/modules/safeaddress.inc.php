<?php
// Version 2.5 by Dan Benjamin - http://hivelogic.com/
// Thanks to the A List Apart (http://www.alistapart.com/) community for their
// ideas and suggestions.
// set $isItSafe to 1 for escaped HTML, 0 for normal HTML
// set $xhtml to 1 if you want your page to be valid for XHTML 1.x

function safeAddress($emailAddress, $theText, $theTitle, $xhtml, $isItSafe) {

    static $count;

    $count++;
    
    $ent = "";
    $userName = "";
    $domainName = "";
    
    for ($i = 0; $i < strlen($emailAddress); $i++) {
        $c = substr($emailAddress, $i, 1);
        if ($c == "@") {
            $userName = $ent;
            $ent = "";
        } else {
                $ent .= "&#" . ord($c) . ";";
        }
    }

    $domainName = $ent;
    
    if ($xhtml == 1) {
        $prefix = "<script type=\"text/javascript\">";
    } else {
        $prefix = "<script language=\"JavaScript\" type=\"text/javascript\">";
    }

    $noscript_text = __('Please enable Javascript (and reload this page) to view the email address.');
    
    $endResult = "
<span id='safe_address_$count' title='$noscript_text'>$theText</span>
$prefix
<!--
    var first = 'ma';
    var second = 'il';
    var third = 'to:';
    var address = '$userName';
    var domain = '$domainName';
    var el = document.getElementById('safe_address_$count');
    el.removeAttribute('title');
    el.innerHTML = '<a href=\"' + first + second + third + address + '&#64;' + domain +
        '\" title=\"$theTitle\">' + '$theText<\/a>';
// -->
</script>";

    if ($isItSafe) {
        return(htmlspecialchars($endResult));
    } else {
        return($endResult);
    }
}

function safeAddressPlain($emailAddress, $xhtml, $isItSafe) {

    
    $ent = "";
    $userName = "";
    $domainName = "";
    
    for ($i = 0; $i < strlen($emailAddress); $i++) {
        $c = substr($emailAddress, $i, 1);
        if ($c == "@") {
            $userName = $ent;
            $ent = "";
        } else {
                $ent .= "&#" . ord($c) . ";";
        }
    }

    $domainName = $ent;
    
    if ($xhtml == 1) {
        $prefix = "<script type=\"text/javascript\">";
    } else {
        $prefix = "<script language=\"JavaScript\" type=\"text/javascript\">";
    }

    $endResult = $prefix . "
<!--
    var address = '$userName';
    var domain = '$domainName';
    document.write(address);
    document.write('&#64;');
    document.write(domain);
// -->
</script>";

    if ($isItSafe) {
        return(htmlspecialchars($endResult));
    } else {
        return($endResult);
    }
}


?>
