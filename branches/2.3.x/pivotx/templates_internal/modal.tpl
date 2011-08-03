


[[ $html ]]
[[ $form ]]


<script type='text/javascript'>
//<![CDATA[

// 'modern' browsers try to be helpful by filling out passwords for you. This is very
// annoying if you're adding/editing users. We 'fix' this by manually setting the
// password fields to '******' (if the second one is '******', that is)
jQuery(function($) {
    if (jQuery('#pass2').is('*')) {
        jQuery('#pass1').val(jQuery('#pass2').val());
    }
});
//]]>
</script>
