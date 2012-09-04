[[ literal ]]
  <!-- entry '[[title]]' -->
  <h2><a href="[[ link hrefonly=1 ]]">[[ $title|trimlen:70:"&hellip;" ]]&nbsp;</a></h2>
  [[ findimages ]]
  [[ if $imagelist.0 != "" ]]
    <div class="frontphoto">
    [[ thumbnail src=$imagelist.0 link=1 linkmaxsize=480 w=120 h=120 htmlwrapper=1 ]]
  </div>
  [[ /if ]]
  [[ introduction|stripimages ]]
  <p class="entryfooter">
    [[ category link=false ]] &bull;
    <!-- [[ user field="emailtonick" ]] &bull; -->
    [[ date ]]
    [[ if ($entry.allow_comments == 1) ]]
      &bull; [[ commentlink ]]
    [[ /if ]]
  </p>
[[ /literal ]]
