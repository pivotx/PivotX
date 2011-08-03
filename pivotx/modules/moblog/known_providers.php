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

// These are the current known providers. 
// You can add content to skip for any new unknown providers,
// but please consider letting us know about it.
$cfg['known_carriers'] = array(
    't-mobile', 'vodafone', 'hi', 'kpn', 'orange', 'tele2.no', 'virgin mobile', 'telfort'
);

$cfg['skipcontent']['t-mobile']['content-disposition'] = 'inline; filename="text.txt"';
$cfg['skipcontent']['vodafone']['content-type'] = 'text/html; charset=utf-8';
$cfg['skipcontent']['vodafone']['filename'] = array( 
    'reply2.gif', 'title_bar.gif', 'vodafone_logo.gif', 'pixel.gif', 
    'h_left.jpg', 'h_background.gif', 'sender.gif', 'subject.gif', 
    'button_answer.gif', 'h_right.gif', 'corner_11.gif', 'dot_line_h1.gif', 
    'corner_12.gif', 'dot_line_v.gif','corner_21.gif', 'dot_line_h2.gif', 
    'corner_22.gif', 'vodafone_footer.gif', 'images/vfpm1.gif', 
    'images/vf2.gif', 'images/vf3.jpg', 'images/vf4.jpg', 'images/vf5.gif', 
    'images/vf6.jpg', 'images/vf7.gif', 'images/vf8.gif', 'images/vf9.gif', 
    'met:vodafone_logo.gif', 'met:title_bar.gif','met:pixel.gif','met:h_left.jpg',
    'met:h_background.gif', 'met:sender.gif','met:subject.gif', 'met:button_answer.gif', 
    'met:reply2.gif', 'met:h_right.gif', 'met:corner_11.gif', 
    'met:dot_line_h1.gif', 'met:corner_12.gif', 'met:dot_line_v.gif', 
    'met:corner_21.gif', 'met:dot_line_h2.gif', 'met:corner_22.gif', 'met:vodafone_footer.gif'
);
$cfg['skipcontent']['hi']['body'] = array(
    'Dit bericht ontvang je van een Hi gebruiker.',
    'Je kunt niet antwoorden op dit bericht.',
    'Klik voor meer informatie op www.hi.nl/mms.'
);
$cfg['skipcontent']['kpn']['body'] = array(
    'Dit bericht ontvang je van een Hi gebruiker.', 
    'Je kunt niet antwoorden op dit bericht.',
    'Klik voor meer informatie op www.hi.nl/mms.'
);
$cfg['skipcontent']['vodafone']['body'] = array(
    'Wil je een gewoon tekstberichtje sturen naar de mobiel van de afzender, klik dan op \'Antwoorden\' of \'Reply\' in je e-mail programma. Dit kan eenmaal tot en met ',
    'Let op!', 'Je kunt in je antwoord maximaal 500 karakters versturen en geen attachments.',
    'Meer weten over MMS? Kijk op <a href="http://www.vodafone.nl">www.vodafone.nl</a> of loop binnen bij een van onze Citypoints.'
);
$cfg['skipcontent']['all']['body'] = array(
    '- cameraphone upload by ShoZu'
);

?>
