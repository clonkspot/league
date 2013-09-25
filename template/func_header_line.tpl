{*params: $func, $text_array, $page_link, optional: additional text: $text*}

{assign var="maxpages" value="5"}
{assign var="last3dots" value=0}

<div class="filterstatus">
{$l->s($func)}
{if $smarty.request.filter|@count && !($smarty.request.filter|@count==1 && $smarty.request.filter.search|@count && $smarty.request.filter.search.0=="")}
 -
{assign var=notused value=$smarty.request.filter|@ksort} {* sort array *}
{/if}
{foreach from=$smarty.request.filter item=f key=fn name="fn"}
{if $fn!='scenario_id'} {*scenario-name used for display*}
  {foreach from=$f item=fv name="fv"}
  {if !$smarty.foreach.fv.first && !$smarty.foreach.fv.last},{elseif !$smarty.foreach.fv.first} {$l->s('and')}{/if}
  
  {*exception:*}
  {if $text_array.$fn.$fv}
      {$text_array.$fn.$fv|escape}
  {elseif $fn == 'product_name'}
      {$fv|escape}
  {elseif $fn == 'search' && $fv}
      {$l->s('search')}: '{$fv|escape}'
  {elseif $fn == 'user_name'}
      {$l->s('player')}: {$fv|escape}
  {elseif $fn == 'clan_name'}
      {$l->s('clan')}: {$fv|escape}
  {elseif $fn == 'host_ip'}
      {$l->s('host_ip')}: {$fv|escape}
  {elseif $fn == 'scenario_name'}
      {$l->s('scenario')}: {$fv|escape}
  {else}
      {if $fv}{$l->s("filter_`$fv`")}{/if}
  {/if}
  
  {/foreach}{if !$smarty.foreach.fn.last && $fv} |{/if}
{/if}
{/foreach}
{if $text}- {$text|escape}{/if}
{if $total_items_count} | {$page_start} - {$page_start+$page_items_count-1} {$l->s('of')} {$total_items_count}{/if}

{if $page_count>1}
<span style="float:right">
    {$l->s('pages')}:
  {section name=pages loop=$page_count start=0 step=1}
    {if $smarty.section.pages.index < $maxpages ||
        ($smarty.section.pages.index < $page+$maxpages && $smarty.section.pages.index > $page-$maxpages)
        || $smarty.section.pages.index >= $page_count - $maxpages}
      {if $smarty.section.pages.index == $page}<b>{/if}
        <a href="{$page_link}{foreach from=$smarty.request.filter item=f key=fn}{foreach from=$f item=fv}{if $fv}&filter[{$fn|escape}][]={$fv|escape}{/if}{/foreach}{/foreach}&sort[col]={$smarty.request.sort.col|escape}&sort[dir]={$smarty.request.sort.dir|escape}&page={$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a>
      {if $smarty.section.pages.index == $page}</b>{/if}
      {assign var="last3dots" value=0}
    {elseif $last3dots==0 && ($smarty.section.pages.index == $maxpages
        || $smarty.section.pages.index == $page+$maxpages || $smarty.section.pages.index == $page-$maxpages
        || $smarty.section.pages.index == $page_count - $maxpages)}
        [...]
        {assign var="last3dots" value=1}
    {/if}
  {/section}
<br>
</span>
{/if}

</div>
