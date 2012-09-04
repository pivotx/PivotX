<div id="sidebar">

    [[ if $pagetype!="search" ]]
        [[ search request_method=get ]]
    [[ /if ]]

    [[ if $modifier.pagetype == 'page' && $modifier.uri == 'about' ]]
        [[* skip this block because the page itself is displayed *]]
    [[ else ]]
        <div class="sidebar-block">
            [[ getpage uri="about" ]]
            <h4>[[ title ]]</h4>
            [[ introduction ]]
            [[ if strlen($page.body)>10 ]]<p><a href="[[$page.link]]">[[t]]More[[/t]]</a></p>[[/if]]
            [[ resetpage ]]
        </div>
    [[ /if ]]

    <div class="sidebar-block">
        <h4>[[t]]Tag Cloud[[/t]]</h4>
        [[ tagcloud ]]
    </div>

    <div class="sidebar-block">
        <h4>[[t]]Latest Comments[[/t]]</h4>
        <ul>
        [[ latest_comments
        format="<li><a href='%url%' title='%url%'>%name% - %title%:</a> %comm%</li>"
        length=100
        trim=16
        count=6 ]]
        </ul>
    </div>

    <div class="sidebar-block">
        <h4>[[t]]Pages[[/t]]</h4>
        [[ pagelist chapterbegin="<ul>"
        pages="<li %active%><a href='%link%' title='%subtitle%'>%title%</a></li>"
        chapterend="</ul>"
        onlychapter="pages"
        isactive="id='active'"
        exclude=""
        sort="title" ]]
    </div>

    <div class="sidebar-block">
        <h4>[[t]]Archives[[/t]]</h4>
        [[* Javascript enabled Jumpmenu for the archives *]] 
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

        [[* Accessible version, for users without Javascript *]]  
        <noscript>  
            <ul>  
                [[archive_list  
                    unit='month'  
                    order='desc'  
                    format='<li><a href="%url%">%st_monname% %st_year%</a></li>'       
                ]]  
            </ul>   
        </noscript> 
    </div>

    <div class="sidebar-block">
        [[ widgets ]]
    </div>

    <div class="sidebar-block">
        <h4>[[t]]Categories[[/t]]</h4>
        <ul>
        [[ category_list format="<li><a href='%url%'>%display%</a></li>" ]]
        </ul>
    </div>

    <div class="sidebar-block">
        <h4>[[t]]Meta[[/t]]</h4>
        [[ pivotxbutton ]]
        [[ rssbutton ]]
        [[ atombutton ]]
    </div>

    [[ if $modifier.pagetype == 'page' && $modifier.uri == 'links' ]]
        [[* skip this block because the page itself is displayed *]]
    [[ else ]]
        <div class="sidebar-block">
            [[ getpage uri="links" ]]
            <h4>[[ title ]]</h4>
            [[ introduction ]]
            [[ if strlen($page.body)>10 ]]<p><a href="[[$page.link]]">[[t]]More[[/t]]</a></p>[[/if]]
            [[ resetpage ]]
        </div>
    [[ /if ]]
        
</div><!-- #sidebar -->
