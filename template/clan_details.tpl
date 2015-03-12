{include file="func_header_line.tpl" func="clan"}

<b>{$l->s('name')}:</b> {$clan.name|escape}
<br>
<b>{$l->s('clan_tag')}:</b> {$clan.tag|escape}
<br>
<b>{$l->s('clan_website')}:</b> <a target="_blank" href="http://{$clan.link|escape}">{$clan.link|escape}</a>
<br>
<b>{$l->s('clan_date_created')}:</b> {$clan.date_created|date_format:"%d.%m.%Y"}
<br>
<b>{$l->s('players')}:</b> {foreach item=user from=$clan.users name="users"} <b><a href="{url part="user" method="details" q="user[id]={$user.id}"}">{$user.name}</a></b>{if !$smarty.foreach.users.last},{/if}{/foreach}
<br>
<b>{$l->s('clan_rankings')}:</b>
{foreach from=$leagues item="league" name="leagues"}<a href="{url part="league" method="clan_ranking" q="league[id]={$league.id}"}">{if $league.icon}<img src="{$league.icon}" title="{$league.name}">{/if}</a> <a href="{url part="league" method="clan_ranking" q="highlight={$clan.id}&league[id]={$league.id}"}">{$league.name}: <b>{$league.rank}.</b></a> <a href="{url part="league" method="ranking" q="league[id]={$league.id}&filter[clan_name][]={$clan.name|escape}"}">{$League.name}</a>{if !$smarty.foreach.leagues.last},{/if}{/foreach}
<br>
<b>{$l->s('clan_member_rankings')}:</b>
{foreach from=$leagues item="league" name="leagues"}<a href="{url part="league" method="ranking" q="league[id]={$league.id}&filter[clan_name][]={$clan.name|escape}"}">{if $league.icon}<img src="{$league.icon}" title="{$league.name}">{/if}</a> <a href="{url part="league" method="ranking" q="league[id]={$league.id}&filter[clan_name][]={$clan.name|escape}"}">{$league.name}</a> <a href="{url part="league" method="ranking" q="league[id]={$league.id}&filter[clan_name][]={$clan.name|escape}"}">{$League.name}</a>{if !$smarty.foreach.leagues.last},{/if}{/foreach}
<br>
<b>{$l->s('description')}:</b><br>
{$clan.description}
