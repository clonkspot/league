<div class="filter">
{* links to all leagues: *}
  {foreach from=$leagues item=lg}
    <a href="?part=league&method={if $clan_ranking}clan_{/if}ranking&league[id]={$lg.id}">{if $league.id==$lg.id}<img src="{$lg.filter_icon_on}" title="{$lg.name}"  alt="{$lg.name}">{else}<img src="{$lg.filter_icon_off}" title="{$lg.name}" alt="{$lg.name}">{/if}</a>
  {/foreach}
  &nbsp;&nbsp;
  {foreach from=$old_leagues item=lg}
    <a href="?part=league&method={if $clan_ranking}clan_{/if}ranking&league[id]={$lg.id}">{if $league.id==$lg.id}<img src="{$lg.filter_icon_on}" title="{$lg.name}"  alt="{$lg.name}">{else}<img src="{$lg.filter_icon_off}" title="{$lg.name}" alt="{$lg.name}">{/if}</a>
  {/foreach}
  <img class="vrbar" src="images/vr_bar.gif">
  <a href="?part=league&method=ranking&league[id]={$smarty.request.league.id}">{if $clan_ranking}<img src="images/icons/filter_player_off.png" title="{$l->s('filter_player_ranking')}">{else}<img src="images/icons/filter_player.png" title="{$l->s('filter_player_ranking')}">{/if}</a>
  <a href="?part=league&method=clan_ranking&league[id]={$smarty.request.league.id}">{if $clan_ranking}<img src="images/icons/filter_team.png" title="{$l->s('filter_clan_ranking')}">{else}<img src="images/icons/filter_team_off.png" title="{$l->s('filter_clan_ranking')}">{/if}</a>
</div>

{if $clan_ranking}
    {assign var="link" value="?part=league&method=clan_ranking&league[id]=`$smarty.request.league.id`"}
{else}
    {assign var="link" value="?part=league&method=ranking&league[id]=`$smarty.request.league.id`"}
{/if}
{include file="func_header_line.tpl" func="ranking" text=$league.name page_link=$link}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link=$link value="rank"}
            {if $clan_ranking==0}<th></th>{/if}
            {include file="func_tableheader.tpl" link=$link value="score"}
{*            {include file="func_tableheader.tpl" link=$link value="trend"} *}
            {if $league.bonus_account_max>0}
            {include file="func_tableheader.tpl" link=$link value="bonus_account" text=$l->s('bonus')}
            {/if}
            {if $clan_ranking==0}
              {include file="func_tableheader.tpl" link=$link value="clan_tag" text=$l->s('clan')}
            {/if}
            {if $clan_ranking}
                {include file="func_tableheader.tpl" link=$link value="clan_tag"}
            {/if}
            {if $clan_ranking}
                {include file="func_tableheader.tpl" link=$link value="c.name" text=$l->s('name')}
            {else}
                {include file="func_tableheader.tpl" link=$link value="u.name" text=$l->s('name')}
            {/if}
            {if $clan_ranking}
                {include file="func_tableheader.tpl" link=$link value="user_count" text=$l->s('players')}
            {/if}
            {*{include file="func_tableheader.tpl" link=$link value="games_won"}
            {include file="func_tableheader.tpl" link=$link value="games_lost"}*}
            {include file="func_tableheader.tpl" link=$link value="games_count"}
            {include file="func_tableheader.tpl" link=$link value="duration" text=$l->s('league_playing_time')}
            {include file="func_tableheader.tpl" link=$link value="favorite_scenario"}

        </tr>
    {foreach item=score from=$scores name="score"}
        <tr {if ($highlight == $score.user_id && !$clan_ranking) || ($highlight == $score.clan_id && $clan_ranking)}style="background-color:#AAEEEE;"{elseif ($user_id == $score.user_id && $user_id != 0) || ($user_clan_id == $score.clan_id && $user_clan_id != 0 && $clan_ranking) }style="background-color:#CCCCFF;"{/if}>
            <td>
                <b>{$score.rank}</b>
            </td>
            {if $clan_ranking==0}
                <td>
                    {if $score.rank_icon}
                        <img src="{$score.rank_icon}">
                    {/if}
                </td>
            {/if}
            <td>
                {if $clan_ranking && ($score.user_count < 3 || $score.score<0)}
                    -
                {else}
                    {if $clan_ranking}
                        <a href="?part=league&method=ranking&league[id]={$league.id}&filter[clan_name][]={$score.name}">{$score.score}</a>
                    {else}
                        {if $league.type=='settle'}<a href="?part=game&method=list&filter[league_id][]={$league.id}&filter[user_name][]={$score.name}&sort[col]=settle_rank&sort[dir]=asc">{$score.score}</a>
                        {else}
                           {$score.score}
                        {/if}
                    {/if}
                {/if}
            </td>
{* -- Trend disabled for now
            <td>
              {if $score.trend=='up'}
                   <img src="images/icons/icon_trend_up_16.png" title="{$l->s('trend_up')}">
              {elseif $score.trend=='down'}
                   <img src="images/icons/icon_trend_down_16.png" title="{$l->s('trend_down')}">
              {/if}
            </td>
*}
            {if $league.bonus_account_max>0}
            <td>(+{$score.bonus_account})</td>
            {/if}
            {if $clan_ranking==0}
              <td><a href="?part=clan&method=details&clan[id]={$score.clan_id}">{$score.clan_tag}</a></td>
              <td><a href="?part=user&method=details&user[id]={$score.user_id}">{$score.name}</a></td>
            {else}
                <td>{$score.clan_tag}</td>
                <td><a href="?part=clan&method=details&clan[id]={$score.clan_id}">{$score.name}</a></td>
                <td>{$score.user_count}{if $score.user_count<3} (!){/if}</td>
            {/if}
             <td>{$score.games_count}</td>
            <td>
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$score.duration/3600}{assign var="hours" value=$hours|string_format:"%02d"}
                {assign var="minutes" value=$score.duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {$hours}:{$minutes}
            </td>
            <td>{$score.favorite_scenario|escape}</td>
            {*<td>{$score.games_won}</td>
            <td>{$score.games_lost}</td>*}
        </tr>
    {/foreach}
</table>