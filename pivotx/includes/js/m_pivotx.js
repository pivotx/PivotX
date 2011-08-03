

// Don't break on browsers without console.log();
if (typeof(console) === 'undefined') { console = { log: function() {}, assert: function() {} }; }

/**
 * Some events that are initialized during page load.
 */
jQuery(function($) {

    // Clicking on of the main menu items should open the submenu..
    $('#mainmenu ul li').bind('click', function(e) {
              
        var rel = $(this).find('a').attr('rel');
        
        if (rel != "") {
            e.preventDefault();
                        
            $('#submenu div').hide();
            $('#menu-'+rel).show();

            if ($(this).hasClass('active') && $('#submenu').hasClass('visible') ) {
                // SlideUp is already active..
                $('#submenu').slideUp('fast'); 
            } else {
                // SlideDown to show it.
                $('#submenu').slideDown('fast', function(){
                	$('#submenu').addClass('visible'); 
                });
            }
            
        }
                
        // higlight the clicked option.
        $('#mainmenu ul li').not($(this)).removeClass('active');
        $(this).addClass('active');
        
    });


    // Simple tabs for editing body / introduction..
    $('#edit-simpletabs span').bind('click', function() {

        $('#edit-simpletabs span').not(this).removeClass('active');
        $(this).addClass('active');     
    
        $('.edit-simpletab').hide();
        $('.edit-simpletab textarea').removeClass('shown');
        $('#edit-' + $(this).attr('id') ).show();
        $('#edit-' + $(this).attr('id') + ' textarea' ).addClass('shown');
    
    });
    
    $('#edit-simpletabs .first').trigger('click');
    
    // Initialise the buttons
    $('.button').bind('click', function(e){
        buttonClick(this);
    });

    // Make the message-box go away after clicking on it.
    $('#messages').bind('click', function() {
        $(this).slideUp(); 
    });

    // Make the entire 'dashboardlist' clickable..
    $('div.dashboardlist').bind('click', function(e) {
        //e.preventDefault();
        var location = $(this).find('a:first').attr('href');
        if (typeof(location) != "undefined") {
            document.location = location;
        }
    });

});


/**
 * When clicking an editor button, modify the selection in the currently
 * shown textarea.
 *
 */
function buttonClick(thisbutton) {

    var textarea = $('textarea.shown');    
    var range = $(textarea).getSelection();

    if ($(thisbutton).hasClass('strong')) { 
        var replacement = "*" + range.text + "*";
    }

    if ($(thisbutton).hasClass('em')) {
        var replacement = "_" + range.text + "_";
    }

    if ($(thisbutton).hasClass('link')) {
        var link = prompt("Link to", "http://");
        if (range.text == "") { range.text = "link"; }
        var replacement = '"' + range.text + '":' + link;
    }

    if ($(thisbutton).hasClass('h1')) {
        var replacement = "\nh1). " + range.text;
    }

    if ($(thisbutton).hasClass('h2')) {
        var replacement = "\nh2). " + range.text;
    }

    if ($(thisbutton).hasClass('h3')) {
        var replacement = "\nh1). " + range.text;
    }

    // alert('range: ' + range.start + " - len: " + range.length);
    textarea.replaceSelection(replacement);
    
}




/*
 * jQuery plugin: fieldSelection - v0.1.0 - last change: 2006-12-16
 * (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 */
(function() {
	var fieldSelection = {
		getSelection: function() {

			var e = this.jquery ? this[0] : this;

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					var l = e.selectionEnd - e.selectionStart;
					return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
				}) ||

				/* exploder */
				(document.selection && function() {

					e.focus();

					var r = document.selection.createRange();
					if (r == null) {
						return { start: 0, end: e.value.length, length: 0 }
					}

					var re = e.createTextRange();
					var rc = re.duplicate();
					re.moveToBookmark(r.getBookmark());
					rc.setEndPoint('EndToStart', re);

					return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
				}) ||

				/* browser not supported */
				function() {
					return { start: 0, end: e.value.length, length: 0 };
				}

			)();

		},

		replaceSelection: function() {

			var e = this.jquery ? this[0] : this;
			var text = arguments[0] || '';

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);
					return this;
				}) ||

				/* exploder */
				(document.selection && function() {
					e.focus();
					document.selection.createRange().text = text;
					return this;
				}) ||

				/* browser not supported */
				function() {
					e.value += text;
					return this;
				}

			)();

		}

	};

	jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });

})();


/**
 * Check if cookies are enabled or disabled.
 *
 * @return boolean
 */
function cookieEnabled() {
    
	var cookieEnabled = (navigator.cookieEnabled) ? true : false;

	if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) { 
		document.cookie="testcookie";
		cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
	}
    
	return (cookieEnabled);
}

