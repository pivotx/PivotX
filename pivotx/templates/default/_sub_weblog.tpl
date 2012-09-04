        [[ literal ]]
        <!-- entry '[[title]]' -->
        <div class="entry">
                <h2><a href="[[ link hrefonly=1 ]]">[[title]]</a></h2>
          <span class="date">
            [[ date ]]
            [[ editlink format="Edit" prefix=" - " ]]
          </span>
          [[introduction]] 
          [[more]]
          <div class="entryfooter">
                    <span class="meta">
                [[ user field=emailtonick]] | 
              [[ permalink text="&para;" title="Permanent link to '%title%' in the archives" ]] |
              [[ category link=true ]] 
            </span>
      [[ if ($entry.allow_comments == 1) ]]
            <span class="comments">[[commentlink]]</span> 
      [[ /if ]]
            <span class="tags">[[tags]]</span>
          </div>
        </div>
        [[ /literal ]]