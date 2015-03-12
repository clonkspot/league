<div class="filter">
  {*{include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="melee"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="noleague"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="" text=$l->s('leagues')}
  <img class="vrbar" src="images/vr_bar.gif">*}
  {foreach from=$leagues item=league}
      {include file="func_filter.tpl" link="{url part="game" method="list"}" name="league_id" value=$league.id text=$league.name icon_on=$league.filter_icon_on icon_off=$league.filter_icon_off}
  {/foreach}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="league_id" value="" text=$l->s('leagues')}
  <img class="vrbar" src="images/vr_bar.gif">
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="lobby"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="running"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="" text=$l->s('games')}
  <img class="vrbar" src="images/vr_bar.gif">
  {include file="func_search.tpl" link="{url part="game" method="list"}"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="search" value="" text=$l->s('search')}
</div>

{include file="func_header_line.tpl" func="games" text_array=$filter_text_array page_link="{url part="game" method="list"}"}

{assign var="show_settle_scores" value=0}
{if $games.0.scenario_type=='settle' &&
    $smarty.request.filter.scenario_id 
    || ($smarty.request.filter.league_id && $smarty.request.filter.user_name)}
    {assign var="show_settle_scores" value=1}
{/if}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="g.type" text=$l->s('leagues')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="g.status" text=$l->s('status')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="scenario_name" text=$l->s('scenario')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="date_created" text=$l->s('date_start')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="duration" text=$l->s('duration')}
            
            {if $show_settle_scores}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_rank" text=$l->s('duration_equivalent')}
            {/if}

            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="player_count" text=$l->s('players')}
            {if $show_settle_scores}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_rank" text=$l->s('rank')}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_score" text=$l->s('score')}
            {/if}
        </tr>
    {foreach item=game from=$games name="game"}
        <tr {if $game.is_revoked}class="revoked"{/if}>
            {$game.game_list_html}
            <td align="right">
                {include file="game_duration.tpl"}
            </td>
            
            {if $show_settle_scores}
              <td>
                {assign var="duration" value=$game.frame/36}
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$duration/3600}{assign var="hours" value=$hours|string_format:"%d"}
                {assign var="minutes" value=$duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {assign var="seconds" value=$duration-$hours*3600-$minutes*60}{assign var="seconds" value=$seconds|string_format:"%02d"}
                {$hours}:{$minutes}:{$seconds}
              </td>
            {/if}

            {$game.game_list_html_2}

            {if $show_settle_scores}
                <td>{if $game.settle_rank < 999999999 && $game.settle_rank > 0}{$game.settle_rank}{/if}</td>
                <td>{if $game.settle_score}{$game.settle_score}{/if}</td>
            {/if}
        </tr>
    {/foreach}
</table>