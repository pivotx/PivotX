This is theme Skinny.

Because this one is meant to also be a learning theme the following:
front.tpl   = template meant for the front page of your weblog
archive.tpl = template meant for the display of all entries of a specific archive (no paging allowed) and 
              is also used when paging from the front page
entry.tpl   = template meant for the detail display of an entry
page.tpl    = template meant for the detail display of a page
search.tpl  = template meant for the display of search results, tags and visitorpage

For simplicity there is only 1 subweblogname in this theme: standard.
Subweblognames are normally used in front.tpl and archive.tpl.

Warning!
If you add subweblognames or change them be aware that if you do not directly specify categories in
the parm of the [[ subweblog ]] then the number of entries to page is deduced from the highest number 
specified in the configuration dashboard of all found subweblognames in all templates in the template folder.
The collection of categories to select the entries from will likewise be the total collection of 
all specified categories found in the dashboard.
Obviously this can give peculiar results.
So the advise is that as soon as you are going to define your own subweblognames to always specify
the parm categories!

Another warning when using different subweblognames (also valid for archive):
Special template tags like category_list, archive_list and latest_comments deduce the collection of
categories to get their information from, from the active subweblogname so their lists will follow
the categories allocated to them (either on the dashboard or with the subweblog template tag).
This means for example that if subweblog 'standard' has 2 categories allocated to it and another 
subweblog has 3 categories allocated to it, the archive_list for the first will only show links to
archives for entries of these 2 categories and the archive_list for the second will show links to
archives out of the 3 categories. So to bypass this you need to specify the same categories to
both subweblognames!

Redirects used in this set of templates:
None. This is just to keep this theme as simple as possible. Other themes used this functionality
so if you need the category list not to go to front.tpl you could add code to redirect it to another template.

For guidelines on how to construct a proper theme see: http://themes.pivotx.net/page/guidelines/weblog

PivotX development team.
