[[ include file="`$templatedir`/_sub_header.tpl" ]]
<p id="navigation_top">
  [[prevpage text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] |
  <a href="[[home]]">[[t]]Home[[/t]]</a> |
  [[nextpage text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
</p>
<hr size="1" noshade="noshade" />
<!-- page '[[title]]' -->
<div class="content">
  <div class="titleholder"><h2><a href="[[ link hrefonly=1 ]]">[[ $title ]]&nbsp;</a></h2></div>
  [[ introduction|stripimages ]]
  [[ body|stripimages ]]
  [[ findimages ]]
  [[ if $imagelist.0 != "" ]]
    <div class="entryphoto">
      [[foreach from=$imagelist item="image" ]]
        [[ thumbnail src=$image link=1 linkmaxsize=480 w=146 h=146 htmlwrapper=1 ]]
      [[/foreach]]
    </div>
  [[ /if ]]
  <p class="entryfooter">
    [[ chaptername ]] &bull;
    <!-- [[ user field="emailtonick" ]] &bull; -->
    [[ date format="%dayname% %day%-%month%-&rsquo;%ye%  %hour24%&#58;%minute%" ]]
  </p>
</div>
<hr size="1" noshade="noshade" />
<p id="navigation_bot">
  [[prevpage text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] |
  <a href="[[home]]">[[t]]Home[[/t]]</a> |
  [[nextpage text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
</p>
[[ include file="`$templatedir`/_sub_footer.tpl" ]]