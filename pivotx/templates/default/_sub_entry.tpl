            <div class="entry">
                <p class="entrynavigation">
            [[previousentry text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] | 
            <a href="[[home]]">[[t]]Home[[/t]]</a> | 
            [[nextentry text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
                </p>    
                <h2><a href="[[ link hrefonly=1 ]]">[[title]]</a></h2>
                <h3>[[subtitle]]</h3>
                <span class="date">
            [[ date ]]
            [[ tags ]]
            [[ editlink format="Edit" prefix=" - " ]]
                </span>
                [[ introduction ]]
                [[ body ]]

      [[ if ($entry.allow_comments == 1) ]]
                <p class="comments">[[ commcount ]]</p>
                <div class="commentblock">        
              [[ comments ]]
              <div class="comment">
                %anchor%
                <img class="gravatar" src="%gravatar%" alt="%name%" />
                <div class="comment-text">
                    %comment%
                  <cite><strong>%name%</strong> %email% %url% - %date% %editlink%</cite>
                        </div>
                    </div>
                    [[ /comments ]]
                </div>
                <br />
                [[message]]
                <br />
                [[commentform]]
      [[ /if ]]
            </div>