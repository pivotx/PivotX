[[ include file="`$templatedir`/_sub_header.tpl" ]]
<p id="navigation_top">
  [[previousentry text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] |
    <a href="[[home]]">[[t]]Home[[/t]]</a> |
  [[nextentry text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
</p>
<hr size="1" noshade="noshade" />
<!-- entry '[[title]]' -->
<h2><a href="[[ link hrefonly=1 ]]">[[ $title ]]</a></h2>
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
  [[ category link=false ]] &bull;
  <!-- [[ user field="emailtonick" ]] &bull; -->
  [[ date format="%dayname% %day%-%month%-&rsquo;%ye%  %hour24%&#58;%minute%" ]]
  [[ if ($entry.allow_comments == 1) ]]
     &bull; [[ commentlink ]]
  [[ /if ]]
</p>
[[ if ($entry.allow_comments == 1 || $entry.comment_count > 0) ]]
  <p class="divider">[[ commcount ]]</p>
  <div class="commentblock">
    [[ comments date="%dayname% %day%.%month%.%ye% // %hour24%&#58;%minute%" ]]
      %anchor%
      <div class="comment925">
        %comment%
        <br/><cite><strong>%name%</strong> %email% %url% // %datelink% %editlink%</cite>
      </div>
    [[ /comments ]]
  </div>
[[ /if ]]
[[ if ($entry.allow_comments == 1) ]]
<br /><br />
[[message]]
[[commentform template="`$templatedir`/_sub_commentform.tpl" ]]
<br /><br />
[[ /if ]]
<hr size="1" noshade="noshade" />
<p id="navigation_bot">
  [[previousentry text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] |
  <a href="[[home]]">[[t]]Home[[/t]]</a> |
  [[nextentry text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
</p>
[[ include file="`$templatedir`/_sub_footer.tpl" ]]