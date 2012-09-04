[[ include file="`$templatedir`/_sub_header.tpl" ]]

<div id="content">
  <div id="content-inner">
    <h2><a href="[[ link hrefonly=1 ]]">[[title]]</a></h2>
    <h3>[[subtitle]]</h3>
    <p class="date">
      [[ date ]]
      [[ tags ]]
      [[ editlink format="Edit" prefix=" - " ]]
    </p>
    [[ introduction ]]
    [[ body ]]
   </div>
</div>

[[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
[[ include file="`$templatedir`/_sub_footer.tpl" ]]
