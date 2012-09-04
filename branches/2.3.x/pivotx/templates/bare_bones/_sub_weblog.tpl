[[ literal ]]
  <!-- entry '[[title]]' -->
  <div class="entry">
    <h2><a href="[[ link hrefonly=1 ]]">[[title]]</a></h2>
    <p class="date">
      [[ date ]]
       [[ editlink format="Edit" prefix=" - " ]]
    </p>
    [[introduction]]
    <div style='clear:both; height:0px;'>&nbsp;</div>
    [[more]]
    <p class="entryfooter">
       <span class="meta">
         [[ user field=emailtonick ]] |
        [[* [[trackbacklink]] | *]]
        [[ permalink text="&para;" title="Permanent link to '%title%' in the archives" ]] |
        [[ category link=true ]]
         [[ if ($entry.allow_comments == 1) ]]
           | [[commentlink]]
         [[ /if ]]
        [[tags prefix=" | " ]]
      </span>
    </p>
  </div>
[[ /literal ]]