<?php
/**
 *
 * Contains all of the old snippets. Most will be moved/ported to module_smarty.php
 * but some will remain here as fallback for people using old 1.x templates
 *
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
if(!defined('INPIVOTX')){ exit('not in pivotx'); }


/**
 * Inserts an image. Just a wrapper for backwards compatibility.
 */
function snippet_image( $filename,$alt='',$meta='',$compl=0 ) {
    $class = '';
    $id = '';
    $border = '';

    // do we need to clean compl?
    if ( $meta=='id' ) {
       $id = $compl;
    } else if ( $meta == 'class' ) {
       $class = $compl;
    } else {
        // Preserve left/right alignment
        if (($meta == 'left') || ($meta == 'right')) {
            $align = $meta;
        }
        if(( '' == $compl )||( !is_numeric( $compl ))) {
            $border = 0;
        } else {
            $border = $compl;
        }
    }

    return smarty_image(array(
        'file' => $filename,
        'alt' => $alt,
        'align' => $align,
        'border' => $border,
        'class' => $class,
        'id' => $id
    ));


}


/**
 * Insert a popup to an image.. Just a wrapper for backwards compatibility.
 */
function snippet_popup ($filename, $thumbname='', $alt='', $align='center', $border='') {
    global $PIVOTX;

    // To avoid forcing people, who are switching from Pivot to PivotX, to edit all
    // their entries with wrong syntax in the popup tag, we have kept these 
    // lines (which were part of Pivot too).
    if (is_numeric($align)) {
        // the border and align properties were swapped, so we need
        // to compensate for the wrong ones.
        $tmp = $border;
        $border = $align;
        $align = $tmp;
    }
    
    return smarty_popup( array(
        'file' => $filename,
        'description' => $thumbname,
        'alt' => $alt,
        'align' => $align,
        'border' => $border
    ), $PIVOTX['template']);


}


/**
 * Inserts a link to a downloadable file... Just a wrapper for backwards 
 * compatibility.
 *
 * @param string $filename
 * @param string $icon Insert a suitable icon if set to "icon"
 * @param string $text The text of the download link.
 * @param string $title The text of the title attribue of the link.
 */
function snippet_download( $filename,$icon,$text,$title ) {
    global $PIVOTX;
    
    return smarty_download( array(
        'file' => $filename,
        'icon' => $icon,
        'text' => $text,
        'title' => $title,
    ), $PIVOTX['template']);

}


/**
 * Creates a link to a file. This snippet has changed meaning in PivotX.
 *
 * The snippet will check the parent driectory of Pivot
 * and the upload directory for a file with the given name.
 *
 * @param string $filename
 * @param string $name (Link text)
 * @return string
 */
function snippet_link($filename, $name) {
    global $PIVOTX;

    debug("The 'link' template tag in PivotX don't link to a file - use 'download' in stead.");

    return smarty_download( array(
        'file' => $filename,
        'icon' => 'text',
        'text' => $name,
    ), $PIVOTX['template']);

}



/**
 * Returns the title (name) of the current weblog. Just a wrapper for 
 * backwards compatibility.
 *
 * @param string $strip if equal to 'strip' all HTML tags will be removed.
 * @return string
 */
function snippet_weblogtitle($strip = '') {

    return smarty_weblogtitle(array('strip' => $strip));

}


function snippet_subweblog ($sub='', $count='', $order='lasttofirst') {
    renderErrorpage("'subweblog' is a block in PivotX", 
        "Read the <a href='http://book.pivotx.net/?page=4-1#anchor-step-4-setting-up-templates'>documentation</a>.");
}


function snippet_weblog ($sub='', $count='') {
    renderErrorpage("'weblog' is a block in PivotX", 
        "Read the <a href='http://book.pivotx.net/?page=4-1#anchor-step-4-setting-up-templates'>documentation</a>.");
}


// Displays information about an entry. Can only be used in an entry.
// [[entry_data:word:image:download]]
// bob's function changed by JM
// 2004/11/25 =*=*= JM - minor corrections
function snippet_entry_data( $word='',$image='',$download='' ) {
  global $PIVOTX;
  $output = array();
  // count words - only if OK
  if( '' != $word ) {
    $total = str_word_count(strip_tags($PIVOTX['db']->entry['title']." ".$PIVOTX['db']->entry['introduction']." ".$PIVOTX['db']->entry['body'])) ;
    if( '*' == $word ) {
       $output[] = ' '.$total.' '.__('words');
    } else {
      $output[] = $total.' '.$word;
    }
  }
  // count images - only if OK
  if( '' != $image ) {
    preg_match_all("/(<img|\[\[image|\[\[popup)/mi", $PIVOTX['db']->entry['introduction'].$PIVOTX['db']->entry['body'], $match );
    $total = count( $match[0] );
    if( $total > 0 ) {
      if( '*' == $image ) {
        // single/plural
        if( 1 == $total ) {
          $output[] = '1 '.__('image');
        } else {
          $output[] = $total.' '.__('images');
        }
      } else {
        $output[] = $total.' '.$image;
      }
    }
  }
  // count downloads - only if OK
  if( '' != $download ) {
    preg_match_all("/(\[\[download)/mi", $PIVOTX['db']->entry['introduction'].$PIVOTX['db']->entry['body'], $match );
    $total = count( $match[0] );
    if( $total > 0 ) {
      if( '*' == $download ) {
      // single/plural
        if( 1 == $total ) {
          $output[] = '1 '.__('file');
        } else {
          $output[] = $total.' '.__('files');
        }
      } else {
        $output[] = $total.' '.$download;
      }
    }
  }
  return implode( ', ',$output );
}


function snippet_jscookies() {

    $output = "<script type='text/javascript'>
//<![CDATA[
function readCookie(name) { var cookieValue = ''; var search = name + '='; if(document.cookie.length > 0) {  offset = document.cookie.indexOf(search); if (offset != -1) {  offset += search.length; end =  document.cookie.indexOf(';', offset); if (end == -1) end = document.cookie.length; cookieValue = unescape(document.cookie.substring(offset, end)) } } return cookieValue.replace(/\+/gi, ' '); }
function getNames() { if (document.getElementsByName('piv_name')) { elt = document.getElementsByName('piv_name'); elt[0].value=readCookie('piv_name'); } if (document.getElementsByName('piv_email')) { elt = document.getElementsByName('piv_email'); elt[0].value=readCookie('piv_email');  } if (document.getElementsByName('piv_url')) { elt = document.getElementsByName('piv_url'); elt[0].value=readCookie('piv_url');  } if (document.getElementsByName('piv_rememberinfo')) { elt = document.getElementsByName('piv_rememberinfo'); if (readCookie('piv_rememberinfo') == 'yes') { elt[0].checked = true; } } }
var oldEvt_readCookie = window.onload; window.onload = function() { if (oldEvt_readCookie) oldEvt_readCookie(); setTimeout('getNames()', 500); }
//]]>
</script>";

    return $output;

}

/**
 * Encrypts the given email address using JavaScript. Wrapper for smarty_link.
 *
 * If "Encode Email Address" is not selected for the current
 * weblog, the output will be a plain mailto-link.
 *
 * @uses smarty_link
 * @param string $email
 * @param string $display Text of the mailto-link.
 * @param string $title Title of the mailto-link.
 * @return string
 */
function snippet_encrypt_mail($email, $display, $title='' ) {
    global $PIVOTX;

    return smarty_link(array(
        'mail' => $email,
        'text' => $display,
        'title' => $title
    ), $PIVOTX['template']);

}


/**
 * Displays the keywords for the entry.
 *
 * @return string The text to display.
 * @param string $text The output format. The default
 *  values is "%keywords%".
 * @param string $sep The separator between the keywords.
 *  The default value is comma. The value "clear" will output
 * the keywords exactly as it was inserted with the entry.
 */
function snippet_keywords($text='',$sep='') {
    global $PIVOTX;

    if ($text=='') { $text = "%keywords%"; }
    if ($sep=='') { $sep = ", "; }

    $keywords = stripslashes($PIVOTX['db']->entry['keywords']);

    if( $sep == 'clear'  ) {
        $output = $keywords;
    } elseif( strlen( trim( $keywords )) > 2 ) {
        // format output..
        preg_match_all('/[^"\', ]+|"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'/i', $keywords, $matches);
        foreach($matches[0] as $match) {
            $output[] = trim(str_replace('"','', str_replace("'",'', $match)));
        }
        $output = implode( $sep,$output );
        $output = str_replace( '%keywords%',$output,$text );
    }    else {
        $output = $PIVOTX['db']->entry['keywords'];
    }
    return $output;
}

/**
 * Wrapper for smarty_comments.
 *
 * @param string $order
 * @return string
 */
function snippet_comments($order = 'ascending') {
    global $PIVOTX;

    return smarty_comments(array('order'=>$order),'',$PIVOTX['template']);
}



/**
 * Placeholder for backwards compatibility
 */
function snippet_nextentry($text='', $cutoff=20) {

    return smarty_nextentry(array('text'=>$text, 'cutoff'=>$cutoff));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_previousentry($text='', $cutoff=20) {

    return smarty_previousentry(array('text'=>$text, 'cutoff'=>$cutoff));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_nextentryincategory($text='', $cutoff='') {

    return smarty_nextentry(array('text'=>$text, 'cutoff'=>$cutoff, 'incategory'=>true));

}


/**
 * Placeholder for backwards compatibility
 */
function snippet_previousentryincategory($text='', $cutoff='') {

    return smarty_previousentry(array('text'=>$text, 'cutoff'=>$cutoff, 'incategory'=>true));

}


/**
 * deprecate this!! We need to add these via a hook!
 *
 */
function snippet_trackback_autodiscovery() {

    return snippet_trackautodiscovery();

}


function snippet_last_comments() {

    return smarty_latest_comments();
}


function snippet_close_on_esc() {
    return "<script type='text/javascript'>\ndocument.onkeypress = function esc(e) {\nif(typeof(e) == 'undefined') { e=event; }\nif (e.keyCode == 27) { self.close(); }\n}\n</script>\n";
}


/**
 * wrapper for smarty_lang
 *
 * @uses smarty_lang
 */
function snippet_lang( $type='' ) {

    return smarty_lang( array('type'=>$type));

}


function snippet_editlink($name='') {

    return smarty_editlink( array('name'=>$name) );

}


/**
 * Wrapper for smarty_introduction
 *
 * @uses smarty_tags
 */
function snippet_tags($text='', $sep='') {
    global $PIVOTX;

    return smarty_tags(array('text'=>$text, 'sep'=>$sep),$PIVOTX['template']);

}


/**
 * Get detailed info for tags used in an entry
 *
 * @param string $template
 * @return string
 */
function snippet_ttaglist($template='') {

    global $PIVOTX;

    $aTagsList = getTags(false);

    if(sizeof($aTagsList) > 0)  {
        $output = "<div id='tagpage'>\n";
        $output .= "<h3>".__('Tags used in this posting')."</h3>\n";

        $tagLinks = array();
        foreach($aTagsList as $sTag)    {
            makeRelatedTags($sTag, $aTagsList);
            $tagLinks[] = sprintf('<a rel="tag" href="%s" title="tag: %s">%s</a>',
                    tagLink($sTag,$template),
                    $sTag,
                    $sTag
                );
        }

        $output .= "<p>" . implode(", ", $tagLinks) . "</p>\n";

        reset($aTagsList);
        foreach($aTagsList as $sRelated)    {
            $sTheRelatedLinks = getEntriesWithTag($sRelated, $PIVOTX['db']->entry["code"]);
            if(!strlen($sTheRelatedLinks) == 0) {
                $output .= "\n<h3>";
                $output .= __('Other entries about')." '".$sRelated."'</h3>\n";
                $output .=  $sTheRelatedLinks;
            }
        }
        $output .= "\n</div>\n";

    } else  {
        $output = '';
    }

    return $output;
}


/**
 * Display a Tag, as used in the introduction or body. Just a wrapper for 
 * backwards compatibility.
 *
 * @param string $tagName
 * @param string $externalLink
 * @param string $template
 * @return string
 */
function snippet_tt($tagName, $externalLink='', $template='') {
    return smarty_tt( array(
        'tag' => $tagName,
        'link' => $externalLink,
        'template' => $template,));
}

?>
