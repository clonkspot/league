{*params: filter: {$name},{$value},{$link},optional: {$text},{$icon_on},{$icon_off}*}
{* use value="" for "all"*}

{* is filter on? *}
{assign var="on" value=0}
{assign var="all_on" value=1}
{foreach from=$smarty.request.filter.$name item=fv}
    {if $fv==$value}
        {assign var="on" value=1}
    {/if}
    {assign var="all_on" value=0}
{/foreach}

<a href="{$link}{foreach from=$smarty.request.filter item=f key=fn}{foreach from=$f item=fv}{if $fv && (($fv!=$value && $value)|| $fn!=$name)}&filter[{$fn|escape}][]={$fv|escape}{/if}{/foreach}{/foreach}{if !$on && $value}&filter[{$name|escape}][]={$value|escape}{/if}&sort[col]={$smarty.request.sort.col|escape}&sort[dir]={$smarty.request.sort.dir|escape}">{if $value}{if $on && ( $icon_on || $l->s("filter_icon_`$value`_on", false))}<img src="{if $icon_on}{$icon_on}{else}images/icons/{$l->s("filter_icon_`$value`_on")}{/if}" title="{$l->s('filter')}: {if $text}{$text|escape}{else}{$l->s("filter_`$value`")}{/if}">{elseif !$on && ($icon_off || $l->s("filter_icon_`$value`_off", false))}<img src="{if $icon_off}{$icon_off}{else}images/icons/{$l->s("filter_icon_`$value`_off")}{/if}" title="{$l->s('filter')}: {if $text}{$text|escape}{else}{$l->s("filter_`$value`")}{/if}">{else}{if $text}{$text|escape}{else}{$l->s("filter_`$value`")}{/if} {if $on}{$l->s('on')}{else}{$l->s('off')}{/if}{/if}{else}{if $all_on}<img src="images/icons/{$l->s("filter_icon_all_on")}" title="{$l->s('filter')}: {$l->s("all")} {if $text}{$text|escape}{/if}">{else}<img src="images/icons/{$l->s("filter_icon_all_off")}" title="{$l->s('filter')}: {$l->s("all")} {if $text}{$text|escape}{/if}">{/if}{/if}</a>