  [[ include file="`$templatedir`/_sub_header.tpl" ]]
  <div id="content">
    <div id="content-inner">
      <!-- begin of weblog 'archive' -->
      [[ subweblog name="standard" ]]
      [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
      [[ /subweblog ]]
      <!-- end of weblog 'archive' -->
      [[ paging action="digg" ]]
    </div>
  </div>
  [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
  [[ include file="`$templatedir`/_sub_footer.tpl" ]]