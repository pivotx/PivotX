        [[ pagelist
        chapterbegin="<h3>%chaptername%</h3><small>%description%</small><ul>"
        pages="<li><a href='%link%' title='%subtitle%'>%title%</a></li>"
        chapterend="</ul>"
        ]]
        [[ widgets ]]
        <h3>[[t]]Linkdump[[/t]]</h3>
        <!-- begin of weblog 'linkdump' -->
      [[ subweblog name="linkdump" ]][[ literal ]]
    <!-- entry '[[title]]' -->
  <div class="linkdumpentry">
    <span class="title">
        &raquo; <a href="[[ link hrefonly=1 ]]">[[ title ]]</a>
        </span>
        [[ introduction ]]
        [[ more ]]
        &nbsp;
        <span class="linkdumpcomments">
      [[ if ($entry.allow_comments == 1) ]]
            [[ commentlink ]] |
        [[ /if ]]
      [[ link text="&para;" title="Permanent link to entry '%title%'" ]]
      [[ editlink format="Edit" prefix=" - " ]]
        </span>
    </div>
[[ /literal ]][[ /subweblog ]]