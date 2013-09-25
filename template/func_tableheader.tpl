{*params: {$link},{text},{$value}*}

<th>

<a href="{$link}{foreach from=$smarty.request.filter item=f key=fn}{foreach from=$f item=fv}{if $fv}&filter[{$fn|escape}][]={$fv|escape}{/if}{/foreach}{/foreach}&sort[col]={$value|escape}&sort[dir]={if $smarty.request.sort.dir == 'desc' && $smarty.request.sort.col==$value}asc{else}desc{/if}"
title="{if $smarty.request.sort.dir == 'desc'}{$l->s('sort_down')}{else}{$l->s('sort_up')}{/if} {$l->s('by')} '{if $text}{$text|escape}{else}{$l->s($value)}{/if}'">
{if $text}{$text|escape}{else}{$l->s($value)}{/if}

{if ($smarty.request.sort.dir == 'desc' && $smarty.request.sort.col==$value) || ($smarty.request.sort.col=='' && $default_sort_col==$value && $default_sort_dir=='desc')}
    <img src="images/sort_down.gif" border="0" height="11" width="9">
{elseif $smarty.request.sort.dir == 'asc' && $smarty.request.sort.col==$value || ($smarty.request.sort.col=='' && $default_sort_col==$value && $default_sort_dir=='asc')}
    <img src="images/sort_up.gif" border="0" height="11" width="9">
{else}
    <img src="images/sort_off.gif" border="0" height="11" width="9">
{/if}
</a>
</th>                                                 