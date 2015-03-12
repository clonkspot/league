<div class="filter">
  {foreach from=$leagues item=league}
      {include file="func_filter.tpl" link="{url part="scenario" method="list"}" name="league_id" value=$league.id text=$league.name icon_on=$league.filter_icon_on icon_off=$league.filter_icon_off}
  {/foreach}
  {include file="func_filter.tpl" link="{url part="scenario" method="list"}" name="league_id" value="" text=$l->s('leagues')}
  <img class="vrbar" src="{$base_path}images/vr_bar.gif">
  {include file="func_search.tpl" link="{url part="scenario" method="list"}"}
  {include file="func_filter.tpl" link="{url part="scenario" method="list"}" name="search" value="" text=$l->s('search')}
</div>

{include file="func_header_line.tpl" func="scenarios" text_array=$filter_text_array page_link="{url part="scenario" method="list"}"}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="leagues"}
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="name"}
            {*{include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="active"}*}
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="type"}
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="games_count" text=$l->s('game_count')}
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="duration" text=$l->s('duration')}
            {include file="func_tableheader.tpl" link="{url part="scenario" method="list"}" value="settle_base_score"}
        </tr>
    {foreach item=scenario from=$scenarios name="scenario"}
        <tr>
            <td>
            	{foreach from=$leagues item=league}
            		{if $u->check_operator_permission("scenario", "league_toggle", $league.id)}
            		(<a href="{url part="scenario" method="toggle_league" q="scenario={$scenario.id}&league={$league.id}"}"><img src="{$base_path}{$league.icon}" alt="{$league.name}" title="{$league.name}"></a>)
            		{/if}
            	{/foreach}
            	{foreach from=$scenario.leagues item=league}
            		<a href="{url part="league" method="ranking" q="league[id]={$league.id}"}"><img src="{$base_path}{$league.icon}" alt="{$league.name}" title="{$league.name}"></a>
            	{/foreach}
            </td>
            <td>
                {*{if $scenario.icon_number >= 0 && $scenario.icon_number != ''}<img src="{$base_path}images/icons/scenarios/{$scenario.icon_number}.png">{/if}*}
                <b><a href="{url part="game" method="list" q="filter[scenario_name][]={$scenario.name|escape}&filter[scenario_id][]={$scenario.id}&sort[col]=settle_rank&sort[dir]=asc"}">{$scenario.name|escape}</a></b>
            </td>
            {*<td>{$scenario.active}</td>*}
            <td>{if $scenario.type == 'melee'}{$l->s('melee')}
                {elseif $scenario.type == 'team_melee'}{$l->s('team_melee')}
                {elseif $scenario.type == 'settle'}{$l->s('settle')}
                {/if}
            <td>{$scenario.games_count}</td>
            <td>
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$scenario.duration/3600}{assign var="hours" value=$hours|string_format:"%02d"}
                {assign var="minutes" value=$scenario.duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {$hours}:{$minutes}
            </td>
            <td>{if $scenario.settle_base_score > 0 || $scenario.settle_time_bonus_score > 0}{$scenario.settle_base_score} (+{$scenario.settle_time_bonus_score}){else}-{/if}</td>
        </tr>
    {/foreach}
</table>