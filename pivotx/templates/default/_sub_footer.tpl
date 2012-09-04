<div id="secondary2">

    <div class="block">
        [[ include file="`$templatedir`/_sub_about.tpl" ]]

    <h3>[[t]]Tag Cloud[[/t]]</h3>
    [[ tagcloud ]]
    </div>

  <div class="block">
      <h3>[[t]]Archives[[/t]]</h3>
        <!-- Javascript enabled Jumpmenu for the archives --> 
      <select id="archivemenu" style='display:none;'> 
        <option>[[t]]Archives[[/t]]</option>
        [[archive_list unit='month' order='desc' format='<option value="%url%">%st_monname% %st_year%</option>' ]] 
        </select>   
    
      <script type='text/javascript'>  
        jQuery(document).ready(function() {  
          jQuery("#archivemenu").show();  
          jQuery("#archivemenu").bind("change", function(){  
            document.location = jQuery("#archivemenu").val();  
            });  
          });  
      </script>  
    
      <!-- Accessible version, for users without Javascript -->  
      <noscript>  
        <ul>  
          [[archive_list  
            unit='month'  
            order='desc'  
            format='<li><a href="%url%">%st_monname% %st_year%</a></li>'       
          ]]  
        </ul>   
      </noscript> 

        <h3>[[t]]Categories[[/t]]</h3>
      <ul>[[category_list format="<li><a href='%url%'>%display%</a></li>"]]</ul>
    </div>

    <div class="block">
        <h3>[[t]]Latest Comments[[/t]]</h3>
    [[latest_comments ]]

    <h3>[[t]]Links[[/t]]</h3>
    [[link_list]]
    </div>

    <div class="block">
      [[ if $pagetype!="search" ]]
            <h3>[[t]]Search[[/t]]</h3>
        [[ search ]]
      [[ /if ]]

    <h3>[[t]]Stuff[[/t]]</h3>
    [[pivotxbutton]]<br />
    [[rssbutton]]<br />
    [[atombutton]]
    </div>

    <div style="clear:both">&nbsp;</div>
</div>
<br />
</body>
</html>