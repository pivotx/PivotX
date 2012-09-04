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
    <p class="comments">[[ commcount ]]</p>
    [[ if ($entry.allow_comments == 1) ]]
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
      <br />
      [[message]]
      [[commentform]]
    [[ /if ]]
  </div>
</div>

[[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
[[ include file="`$templatedir`/_sub_footer.tpl" ]]
