// ----------------------------------------------------------------------------
// Mark It Up!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// Html tags
// http://en.wikipedia.org/wiki/html
// ----------------------------------------------------------------------------
// Basic set. Feel free to add more tags
// ----------------------------------------------------------------------------

// Note: This file is heavily modified for PivotX. PLease see the original site for
// Unspoiled files.

pivotxtemplates = [
    { 
        name:'<strong>[[ subweblog ]]</strong> - Wrapper for a (sub)weblog.',     
        replaceWith: '[[ subweblog name="" category="" order="" ]][[ literal ]]\n\n[[ /literal ]][[ /subweblog ]]'
    },
    { 
        name:'<strong>[[ include ]]</strong> - Include a file',   
        replaceWith: '[[ include file="(filename)" ]]'
    },  
    { 
        name:'<strong>[[ date ]]</strong> - Insert a nicely formatted date', 
        replaceWith: '[[ date format="%dayname% %day% %monthname% %year% at %hour12%&#58;%minute% %ampm%" ]]'
    },
    { 
        name:'<strong>[[ entrylink ]]</strong> - Link to the current entry/Page',     
        replaceWith: '[[entrylink]]'
    },
    { 
        name:'<strong>[[ title ]]</strong> - The title of the Entry/Page',     
        replaceWith: '[[title]]'
    },
    { 
        name:'<strong>[[ subtitle ]]</strong> - The subtitle of the Entry/Page',     
        replaceWith: '[[subtitle]]'
    },    
    { 
        name:'<strong>[[ introduction ]]</strong> - The introduction text',     
        replaceWith: '[[ introduction ]]'
    },
    { 
        name:'<strong>[[ body ]]</strong> - The Body text',     
        replaceWith: '[[ body ]]'
    },
    { 
        name:'<strong>[[ more ]]</strong> - Link to continue reading the full entry',     
        replaceWith: '[[ more]]'
    },
    { 
        name:'<strong>[[ tags ]]</strong> - Insert the used Tags',     
        replaceWith: '[[ tags ]]'
    }, /*
    { 
        name:'<strong>[[ editlink ]]</strong> - Insert a link to edit the Entry/Page',     
        replaceWith: '[[ editlink format="Edit" prefix=" - " ]]'
    }, */
    { 
        name:'<strong>[[ user ]]</strong> - The user that created this Entry/Page',     
        replaceWith: '[[ user field="emailtonick" ]]'
    },
    { 
        name:'<strong>[[ permalink ]]</strong> - Permanent link to this Entry/Page',     
        replaceWith: '[[ permalink format="&para;" title="Permanent link to \'%title%\' in the archives" ]]'
    },
    { 
        name:'<strong>[[ category ]]</strong> - The Entries\' categories',     
        replaceWith: '[[ category link=true ]]'
    },
    { 
        name:'<strong>[[ commentlink ]]</strong> - Go to the Entries\' comments',     
        replaceWith: '[[ commentlink ]]'
    },

    { 
        name:'<strong>[[ comments ]]</strong> - The Entries\' comments ',     
        replaceWith: '[[ comments ]]\n\t%anchor%\n\t<img src="%gravatar%" align="left" style="margin-top: 14px;" width="48" height="48" alt="gravatar for %name%" />\n\t<div class="comment">\n\t%comment%\n\t<cite><strong>%name%</strong> %email% %url% - %datelink% %editlink%</cite>\n\t</div>\n\t<br />\n[[ /comments ]]'
    },
    { 
        name:'<strong>[[ commentform ]]</strong> - Display the form for commenting',     
        replaceWith: '[[ commentform ]]'
    },    
/*    { 
        name:'<strong>[[ trackbacklink ]]</strong> - Go to the Entries\' trackbacks',     
        replaceWith: '[[ trackbacklink ]]'
    },    
    { 
        name:'<strong>[[ trackbacks ]]</strong> - Display the trackbacks ',     
        replaceWith: '[[ trackbacks ]]'
    },
    { 
        name:'<strong>[[ tracklink ]]</strong> - The URL to ping fot Trackbacks ',     
        replaceWith: '[[ tracklink ]]'
    }, 
    { 
        name:'<strong>[[ paging ]]</strong> - Insert paging mechanism ',     
        replaceWith: '[[ paging action="digg" ]]'
    },*/
    { 
        name:'<strong>[[ feed ]]</strong> - Fetch and display an RSS/Atom feed.',     
        replaceWith: '[[ feed url="http://pivotx.net/rss.xml" amount=8 dateformat="%dayname% %day% %monthname%" trimlength=100 ]]\n\t<p><strong><a href="%link%">%title%</a></strong><br/>\n\t%description% (%author% - %date%)</p>\n[[ /feed ]]'
    }
];



markitupdefault = {	onEnter:   		{},
    onShiftEnter:  	{keepDefault:false, replaceWith:'<br />\n'},
    onCtrlEnter:  	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>\n'},
    onTab:    		{keepDefault:false, openWith:'     ', placeHolder:''},
    placeHolder:	'Your text here...',
    markupSet:  [ 	{name:'Bold', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)' },
                    {name:'Italic', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)'  },
                    {name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>' },
                    {separator:'---------------' },
                    {name:'Picture', key:'P', replaceWith:'<img src="[![Source:!:http://]!]" alt="[![Alternative text]!]" />' },
                    {name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...' },
                    {separator:'---------------' },
                    {name:'Clean', replaceWith:function(h) { return h.selection.replace(/<(.*?)>/g, "") } },				
                    {name:'Preview', call:'preview', className:'preview' }
    ]
};

markituphtml = {
	onShiftEnter:	{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>\n'},
	onTab:			{keepDefault:false, openWith:'	 ', placeHolder:''},
	placeHolder:	"Your text here...",
	markupSet:  [	 
		{name:'Heading 1', key:'1', openWith:'<h1(!( class="[![Class]!]")!)>', closeWith:'</h1>', placeHolder:'Your title here...', className: 'markitup-h1' },
		{name:'Heading 2', key:'2', openWith:'<h2(!( class="[![Class]!]")!)>', closeWith:'</h2>', placeHolder:'Your title here...', className: 'markitup-h2' },
		{name:'Heading 3', key:'3', openWith:'<h3(!( class="[![Class]!]")!)>', closeWith:'</h3>', placeHolder:'Your title here...', className: 'markitup-h3' },
		//{name:'Heading 4', key:'4', openWith:'<h4(!( class="[![Class]!]")!)>', closeWith:'</h4>', placeHolder:'Your title here...', className: 'markitup-h4' },
		//{name:'Heading 5', key:'5', openWith:'<h5(!( class="[![Class]!]")!)>', closeWith:'</h5>', placeHolder:'Your title here...', className: 'markitup-h5' },
		//{name:'Heading 6', key:'6', openWith:'<h6(!( class="[![Class]!]")!)>', closeWith:'</h6>', placeHolder:'Your title here...', className: 'markitup-h6' },
		{name:'Paragraph', openWith:'<p(!( class="[![Class]!]")!)>', closeWith:'</p>', className: 'markitup-p' }, 
		{separator:'---------------' },
		{name:'Bold', key:'B', openWith:'<strong>', closeWith:'</strong>', className: 'markitup-strong' },
		{name:'Italic', key:'I', openWith:'<em>', closeWith:'</em>', className: 'markitup-em'  },
		{name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>', className: 'markitup-del' },
                { name:'Superscript', openWith:'<sup>', closeWith:'</sup>', className:'markitup-sup' },
                { name:'Subscript', openWith:'<sub>', closeWith:'</sub>', className:'markitup-sub' },
		{separator:'---------------' },
                {name:"Image", replaceWith:function(h){ openImageWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotximg' },
                {name:"Popup", replaceWith:function(h){ openImagePopupWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxpopup' },
                {name:"Download", replaceWith:function(h){ openDownloadWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxdownload' },
                {name:"Tag", replaceWith:function(h){ openTagWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxtag' },
		{name:'pivotx', dropMenu: pivotxtemplates, className: 'markitup-pivotxtemplates' },	        
		{separator:'---------------' },
                {name:"Blockquote", closeWith:"</blockquote>\n", openWith:"\n<blockquote>", className:'markitup-blockquote' },
		{name:'Ul', openWith:'<ul>\n', closeWith:'</ul>\n', className: 'markitup-ul' },
		{name:'Ol', openWith:'<ol>\n', closeWith:'</ol>\n', className: 'markitup-ol' },
		{name:'Li', openWith:'<li>', closeWith:'</li>', className: 'markitup-li' },
		{separator:'---------------' },
		{name:'Table', openWith:'<table>', closeWith:'</table>', className:'markitup-table' },
                {name:'Tr', openWith:'<tr>', closeWith:'</tr>', className:'markitup-tr' },
                {name:'Td/Th', openWith:'<(!(td||th)!)>', closeWith:'</(!(td||th)!)>', className:'markitup-td' },		
		{separator:'---------------' },
		{name:'Picture', key:'P', replaceWith:'<img src="[![Source:!:http://]!]" alt="[![Alternative text]!]" />', className: 'markitup-img' },
		{name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...', className: 'markitup-a' },
                {name:"Code", closeWith:"</code>", openWith:"<code>", className:'markitup-code' },
                {name:"Comment", closeWith:"-->", openWith:"<!--", className:'markitup-comment' },		
		{name:'Clean', replaceWith:function(o) { return o.selection.replace(/<(.*?)>/g, "") }, className: 'markitup-clean' }
		// {name:'Preview', call:'preview', className:'preview', className: 'markitup-preview' }
	]
};


markituptextile = {
	nameSpace:		    "textile", // not required but useful to prevent multi-instances CSS conflict
    previewParserPath:  "../markitup/sets/textile/preview.php",
    onShiftEnter:       {keepDefault:false, replaceWith:'\n\n'},
    placeHolder:        "Your text here...",
    markupSet: [     
        {name:'Heading 1', key:'1', openWith:'\nh1(!(([![Class]!])!)). ', className: 'markitup-h1' }, 
        {name:'Heading 2', key:'2', openWith:'\nh2(!(([![Class]!])!)). ', className: 'markitup-h2' }, 
        {name:'Heading 3', key:'3', openWith:'\nh3(!(([![Class]!])!)). ', className: 'markitup-h3' }, 
//        {name:'Heading 4', key:'4', openWith:'\nh4(!(([![Class]!])!)). ', className: 'markitup-h4' }, 
//        {name:'Heading 5', key:'5', openWith:'\nh5(!(([![Class]!])!)). ', className: 'markitup-h5' }, 
//        {name:'Heading 6', key:'6', openWith:'\nh6(!(([![Class]!])!)). ', className: 'markitup-h6' }, 
        {name:'Paragraph', key:'P', openWith:'\np(!(([![Class]!])!)). ', className: 'markitup-p' }, 
        {separator:'---------------' },
        {name:'Bold', key:'B', closeWith:'*', openWith:'*', className: 'markitup-strong' }, 
        {name:'Italic', key:'I', closeWith:'_', openWith:'_', className: 'markitup-em' }, 
        {name:'Stroke through', key:'S', closeWith:'-', openWith:'-', className: 'markitup-del' }, 
        {separator:'---------------' },
        {name:"Image", replaceWith:function(h){ openImageWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotximg' },
        {name:"Popup", replaceWith:function(h){ openImagePopupWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxpopup' },
        {name:"Download", replaceWith:function(h){ openDownloadWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxdownload' },
        {name:"Tag", replaceWith:function(h){ openTagWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxtag' },
        {name:'pivotx', dropMenu: pivotxtemplates, className: 'markitup-pivotxtemplates' },	           
        {separator:'---------------' },
        {name:'Bulleted list', openWith:'(!(* |!|*)!)', className: 'markitup-ul' }, 
        {name:'Numeric list', openWith:'(!(# |!|#)!)', className: 'markitup-ol' }, 
        {separator:'---------------' },
        {name:'Picture', replaceWith:'![![Source:!:http://]!]([![Alternative text]!])!', className: 'markitup-img' }, 
        {name:'Link', openWith:'"', closeWith:'([![Title]!])":[![Link:!:http://]!]', className: 'markitup-a' }, 
        {separator:'---------------' },
        {name:'Quotes', openWith:'\nbq(!(([![Class]!])!)). ', className: 'markitup-blockquote' }, 
        {name:'Code', openWith:'@', closeWith:'@', className: 'markitup-code' }
        //{separator:'---------------' },       
        //{name:'Preview', call:'preview', className:'preview'}
    ]
};


markitupminitextile = {
	nameSpace:		    "textile", // not required but useful to prevent multi-instances CSS conflict
    previewParserPath:  "../markitup/sets/textile/preview.php",
    onShiftEnter:       {keepDefault:false, replaceWith:'\n\n'},
    placeHolder:        "Your text here...",
    markupSet: [     
        {name:'Heading 1', key:'1', openWith:'\nh1(!(([![Class]!])!)). ', className: 'markitup-h1' }, 
        {name:'Heading 2', key:'2', openWith:'\nh2(!(([![Class]!])!)). ', className: 'markitup-h2' }, 
        {name:'Heading 3', key:'3', openWith:'\nh3(!(([![Class]!])!)). ', className: 'markitup-h3' }, 
//        {name:'Heading 4', key:'4', openWith:'\nh4(!(([![Class]!])!)). ', className: 'markitup-h4' }, 
//        {name:'Heading 5', key:'5', openWith:'\nh5(!(([![Class]!])!)). ', className: 'markitup-h5' }, 
//        {name:'Heading 6', key:'6', openWith:'\nh6(!(([![Class]!])!)). ', className: 'markitup-h6' }, 
        {name:'Paragraph', key:'P', openWith:'\np(!(([![Class]!])!)). ', className: 'markitup-p' }, 
        {separator:'---------------' },
        {name:'Bold', key:'B', closeWith:'*', openWith:'*', className: 'markitup-strong' }, 
        {name:'Italic', key:'I', closeWith:'_', openWith:'_', className: 'markitup-em' }, 
        {name:'Stroke through', key:'S', closeWith:'-', openWith:'-', className: 'markitup-del' }, 
        {separator:'---------------' },
        {name:'Bulleted list', openWith:'(!(* |!|*)!)', className: 'markitup-ul' }, 
        {name:'Numeric list', openWith:'(!(# |!|#)!)', className: 'markitup-ol' }, 
        {separator:'---------------' },
        {name:'Picture', replaceWith:'![![Source:!:http://]!]([![Alternative text]!])!', className: 'markitup-img' }, 
        {name:'Link', openWith:'"', closeWith:'([![Title]!])":[![Link:!:http://]!]', className: 'markitup-a' }, 
        {separator:'---------------' },
        {name:'Quotes', openWith:'\nbq(!(([![Class]!])!)). ', className: 'markitup-blockquote' }, 
        {name:'Code', openWith:'@', closeWith:'@', className: 'markitup-code' }
        //{separator:'---------------' },    
        //{name:'Preview', call:'preview', className:'preview'}
    ]
};


markitupmarkdown = {
	previewParserPath:	"", // path to your Markdown parser
	onShiftEnter:		{keepDefault:false,	openWith:'\n\n'},
	placeHolder:		"Your text here...",
	markupSet: [		 
		{name:'First Level Heading', key:"1", placeHolder:'Your title here...', openWith:"\n", 
		 className: 'markitup-h1',
		 closeWith:function(h) {
			heading1 = '';
			n = $.trim(h.selection||h.placeHolder).length;
			for(i = 0; i < n; i++)	{
				heading1 += '=';	
			}
			return '\n'+heading1+'\n';
		}},
		{name:'Second Level Heading', key:"2", placeHolder:'Your title here...', openWith:"\n", 
		 className: 'markitup-h2',
		 closeWith:function(h) {
			heading2 = '';
			n = $.trim(h.selection||h.placeHolder).length;
			for(i = 0; i < n; i++)	{
				heading2 += '-';	
			}
			return '\n'+heading2+'\n';
		}},
		{name:'Heading 3', key:"3", openWith:'### ', placeHolder:'Your title here...', className: 'markitup-h3'  },
//		{name:'Heading 4', key:"4", openWith:'#### ', placeHolder:'Your title here...', className: 'markitup-h4'  },
//		{name:'Heading 5', key:"5", openWith:'##### ', placeHolder:'Your title here...', className: 'markitup-h5'  },
//		{name:'Heading 6', key:"6", openWith:'###### ', placeHolder:'Your title here...', className: 'markitup-h6'  },							
		{separator:'---------------' },		
		{name:'Bold', key:"B", openWith:'**', closeWith:'**', className: 'markitup-strong' },
		{name:'Italic', key:"I", openWith:'_', closeWith:'_', className: 'markitup-em' },
		{separator:'---------------' },
                {name:"Image", replaceWith:function(h){ openImageWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotximg' },
                {name:"Popup", replaceWith:function(h){ openImagePopupWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxpopup' },
                {name:"Download", replaceWith:function(h){ openDownloadWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxdownload' },
                {name:"Tag", replaceWith:function(h){ openTagWindow($(h.textarea).attr('name')) }, className: 'markitup-pivotxtag' },
		{name:'pivotx', dropMenu: pivotxtemplates, className: 'markitup-pivotxtemplates' },	           
                {separator:'---------------' },		
		{name:'Bulleted List', openWith:'- ', className: 'markitup-ul'  },
		{name:'Numeric List', openWith:function(h) {
			return h.line+'. ';
		}, className: 'markitup-ol' },
		{separator:'---------------' },
		{name:'Picture', key:"P", replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")', className: 'markitup-img' },
		{name:'Link', key:"L", openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', className: 'markitup-a' },
		{separator:'---------------'},	
		{name:'Quotes', openWith:'> ', className: 'markitup-blockquote' },
		{name:'Code Block / Code', openWith:'(!(\t|!|`)!)', closeWith:'(!(`)!)', className: 'markitup-code' },
		{separator:'---------------'},
		{name:'Preview', call:'preview', className:"preview" }
	]
};
