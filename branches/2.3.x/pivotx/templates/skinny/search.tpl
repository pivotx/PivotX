[[ include file="`$templatedir`/_sub_header.tpl" ]]
<div id="content">
    <div id="main">
        [[ if $modifier.pagetype == 'search' ]]
            <div id="search-results-list">
            [[ content ]][[* simple display of search results *]]
            [[* display of search results where you create it yourself *]]
            [[*
                <h2>[[t]]Search Results[[/t]]</h2>
                [[ search ]]
                <p>[[ searchheading
                result0="No results for '%query%'."
                result1="There's one result for '%query%'."
                resultmore="There are %num% results for '%query%'." ]]</p>
                [[ searchresults prefix="<ul>" postfix="</ul>" titletrimlength=50 excerptlength=200 ]]
                    <li><a href="%link%">%title%</a> (%percentage%%)<br />
                    %excerpt%
                    </li>
                [[ /searchresults ]]
            *]]
            </div>
        [[ else ]]
            <div id="content-listing">
            [[ content ]][[* for example a tag list or visitorpage *]]
            </div>
        [[ /if ]]
    </div><!-- #main -->
    [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
</div><!-- #content -->
[[ include file="`$templatedir`/_sub_footer.tpl" ]]