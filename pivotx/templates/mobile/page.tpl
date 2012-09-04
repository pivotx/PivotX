[[ include file="`$templatedir`/_sub_header.tpl" ]]
<!-- entry '[[title]]' -->
<div class="bericht">
  <div class="titelholder"><h2><a href="[[ link hrefonly=1 ]]">[[ $title ]]&nbsp;</a></h2></div>
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
    <!-- [[ user field="emailtonick" ]] &bull; -->
    [[ date ]]
  </p>
</div>
[[ include file="`$templatedir`/_sub_footer.tpl" ]]
