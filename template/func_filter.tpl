{*params: filter: {$name},{$value},{$link},optional: {$text},{$icon_on},{$icon_off}*}
{* use value="" for "all"*}

{* is filter on? *}
{assign var="on" value=0}
{assign var="all_on" value=1}
{if isset($smarty.request.filter.$name)}
    {foreach from=$smarty.request.filter.$name item=fv}
        {if $fv==$value}
            {assign var="on" value=1}
        {/if}
        {assign var="all_on" value=0}
    {/foreach}
{/if}

<a href="{append_query url="{$link}" q="{foreach from=$smarty.request.filter item=f key=fn}{foreach from=$f item=fv}{if $fv && (($fv!=$value && $value)|| $fn!=$name)}&filter[{$fn|escape}][]={$fv|escape}{/if}{/foreach}{/foreach}{if !$on && $value}&filter[{$name|escape}][]={$value|escape}{/if}&sort[col]={$smarty.request.sort.col|escape}&sort[dir]={$smarty.request.sort.dir|escape}"}">
    {if $value}{if $on && ( isset($icon_on) || $l->s("filter_icon_`$value`_on", false))}<img src="{$base_path}{if isset($icon_on)}{$icon_on}{else}images/icons/{$l->s("filter_icon_`$value`_on")}{/if}" title="{$l->s('filter')}: {if isset($text)}{$text|escape}{else}{$l->s("filter_`$value`")}{/if}">{elseif !$on && (isset($icon_off) || $l->s("filter_icon_`$value`_off", false))}<img src="{$base_path}{if isset($icon_off)}{$icon_off}{else}images/icons/{$l->s("filter_icon_`$value`_off")}{/if}" title="{$l->s('filter')}: {if isset($text)}{$text|escape}{else}{$l->s("filter_`$value`")}{/if}">{else}{if isset($text)}{$text|escape}{else}{$l->s("filter_`$value`")}{/if} {if $on}{$l->s('on')}{else}{$l->s('off')}{/if}{/if}{else}{if $all_on}<img src="{$base_path}images/icons/{$l->s("filter_icon_all_on")}" title="{$l->s('filter')}: {$l->s("all")} {if isset($text)}{$text|escape}{/if}">{else}<img src="{$base_path}images/icons/{$l->s("filter_icon_all_off")}" title="{$l->s('filter')}: {$l->s("all")} {if isset($text)}{$text|escape}{/if}">{/if}{/if}
</a>
