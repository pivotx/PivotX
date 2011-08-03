[[include file="inc_header.tpl" ]]


<p>[[t]]Add a new configuration option:[[/t]] </p>

<form id="addValueForm" action="index.php?page=advconfiguration">
<table border='0' cellspacing='0' cellpadding='0' class="formclass">
    <tr><th>[[t]]Key[[/t]]</th><th>=</th><th>[[t]]Value[[/t]]</th><th>&nbsp;</th></tr>
    <tr>

        <td><input id="addkey" name="addkey" class="input" /></td>
        <td>=</td>
        <td><input id="addvalue" name="addvalue" class="input" style="width:400px;" /></td>
        <td class="buttons_small">
            <a href="#" id="addbutton">
                <img src="pics/add.png" alt=""/> [[t]]Add[[/t]]
            </a>
        </td>
    </tr>
</table>
</form>


<p>[[t]]Edit options[[/t]]: </p>


<table id="config" border='0' cellspacing='0' cellpadding='0' class="formclass">
    
    
    <tr id="insertRowHere"><th>[[t]]Key[[/t]]</th><th>=</th><th>[[t]]Value[[/t]]</th><th>&nbsp;</th></tr>
    
    
[[ foreach from=$advconfig key=key item=item]]

        <tr id="row-[[$key]]">

            <td>[[ $key ]]</td>
            <td>=</td>
            <td><span class="edit" id='[[$key]]'>[[ $item ]]</span></td>
            <td class="buttons_small">
                <a href="javascript:delConfig('[[$key]]')">
                <img src="pics/delete.png" alt="" /> [[t]]Delete[[/t]]
                </a>
            </td>
        </tr>
[[/foreach]]
 </table>



<script type='text/javascript'>
//<![CDATA[

jQuery(function($) {
    
    $(".edit").editable("ajaxhelper.php?function=setConfig", { 
        type : 'textarea', 
        width: '400', 
        indicator: "<img src='pics/cog.png' width='16' height='16' alt='x' />", 
        onblur: 'submit',
        submitdata: { csrfcheck: $.cookie("pivotxsession"), unentify: 1 }
    });
    
    $("#addbutton").click(function() {
  
        var csrfcheck =  $.cookie("pivotxsession");
        
        var html = "<tr id='row-" + $('#addkey').val() + "'><td>" + $('#addkey').val() + "</td>"+ 
            "<td>=</td>" +
            "<td><span class='edit' id='" + $('#addkey').val() + "'>" + $('#addvalue').val() + "</span></td>"+
            "<td class='buttons_small'><a href=\"javascript:delConfig('" + $('#addkey').val() + "')\">"+
            "<img src='pics/delete.png' /> Delete</a></td></tr>";
            
        var key = $('#addkey').val();
        var val = $('#addvalue').val();
    
        $.ajax({
            type: "POST",
            url: "ajaxhelper.php",
            data: "function=addConfig&key=" + encodeURIComponent(key) + "&value=" + encodeURIComponent(val)  +
                "&csrfcheck=" + escape(csrfcheck),
            success: function(fetchedhtml) { 
                $("#insertRowHere").after(html); 
                $('#addkey').val('');
                $('#addvalue').val('')
            },
            error: function() { alert("Error adding values.") }
        });
  
    });
    
    
    
});

function delConfig(key) {
    
    if (confirm("Are you sure you wish to delete key '"+key+"'?")) {
    
        var csrfcheck =  $.cookie("pivotxsession");
    
        $.ajax({
            type: "POST",
            url: "ajaxhelper.php",
            data: "function=delConfig&key=" + encodeURIComponent(key) + "&csrfcheck=" + escape(csrfcheck),
            success: function(fetchedhtml) { $("#row-"+key).hide(); },
            error: function() { alert("Error deleting value.") }
        });
    
        
        
    }
    
}

//]]>
</script>

[[include file="inc_footer.tpl" ]]
