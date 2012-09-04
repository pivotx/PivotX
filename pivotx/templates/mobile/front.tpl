[[ include file="`$templatedir`/_sub_header.tpl" ]]
  <p id="navigation">
    [[ paging action="prev" format="&laquo; previous page" ]] |
    <a href="[[ home ]]">Home</a> |
    [[ paging action="next" format="next page &raquo; " ]]
  </p>
  <hr size="1" noshade="noshade" />
  <!-- warning if 'imagetools' extension is not enabled.. -->
  [[ if !tag_exists('findimages') ]]
     <strong>Note: </strong> This theme requires that the 'imagetools' extension is enabled.
  [[/if]]
  <!-- begin of weblog 'standard' -->
  [[ subweblog name="standard" category="*" amount="10" ]]
    [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
  [[ /subweblog ]]
  <!-- end of weblog 'standard' -->
  <p id="navigation">
    [[ paging action="prev" format="&laquo; previous page" ]] |
    <a href="[[ home ]]">Home</a> |
    [[ paging action="next" format="next page &raquo; " ]]
  </p>
[[ include file="`$templatedir`/_sub_footer.tpl" ]]