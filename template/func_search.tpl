{*params: search: {$link}*}

<form method="post" class="search" action="{$link}{foreach from=$smarty.request.filter item=f key=fn}{foreach from=$f item=fv}{if $fv}&filter[{$fn|escape}][]={$fv|escape}{/if}{/foreach}{/foreach}&sort[col]={$smarty.request.sort.col|escape}&sort[dir]={$smarty.request.sort.dir|escape}">
  <input type="text" class="search" name="filter[search][]" value="{$smarty.request.filter.search.0|escape}">
  <input class="searchbutton" type="image" 
    {if isset($smarty.request.filter) && $smarty.request.filter.search.0}
        src="images/icons/filter_search.gif"
    {else}
        src="images/icons/filter_search_off.gif"
    {/if}
  title="{$l->s('search')}">
</form>
