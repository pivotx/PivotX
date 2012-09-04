<div id="sidebar">
  <div id="sidebar-inner">

    [[ include file="`$templatedir`/_sub_about.tpl" ]]
    <hr noshade="noshade" />
            
    [[ pagelist
      chapterbegin="<h3>%chaptername%</h3><small>%description%</small><ul>"
      pages="<li %active%><a href='%link%' title='%subtitle%'>%title%</a></li>"
      chapterend="</ul>"
      isactive="id='active'"
    ]]          
    <hr noshade="noshade" />
    
    <h3>[[t]]Latest Comments[[/t]]</h3>
    [[latest_comments
      format="<a href='%url%' title='%date%'><b>%name%</b></a>: %comm%<br />"
      length=100
      trim=16
      count=8
    ]]
    <hr noshade="noshade" />

    <h3>[[t]]Stuff[[/t]]</h3>
    [[pivotxbutton]]<br />
    [[rssbutton]]<br />
    [[atombutton]]    
    <hr noshade="noshade" />

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
    <hr noshade="noshade" />
    
    <h3>[[t]]Categories[[/t]]</h3>
    <ul>
      [[category_list format="<li><a href='%url%'>%display%</a></li>"]]
    </ul>
    <hr noshade="noshade" />
        
    <h3>[[t]]Links[[/t]]</h3>
    [[ include file="`$templatedir`/_sub_link_list.tpl" ]]
    <hr noshade="noshade" />
    
    [[ if $pagetype!="search" ]]
      <h3>[[t]]Search[[/t]]</h3>
      [[ search ]]
    [[ /if ]]

  </div>
</div>


