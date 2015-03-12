{if $game.is_revoked}<br><span class="revoked">{$l->s('game_revoked')}</span><br><br>
<div class="revoked">{/if}<div class="textleft">
<b>Szenario:</b>
{if $game.icon_number >= 0 && $game.icon_number != ''}<img src="images/icons/scenarios/{$game.icon_number}.png">{/if}
{if $game.type!='noleague'}
    <a href="{url part="game" method="list"}filter[scenario_name][]={$game.scenario_name|escape}&filter[scenario_id][]={$game.scenario_id}&sort[col]=settle_rank&sort[dir]=asc">{$game.scenario_name|escape}</a>
{else}
    {$game.scenario_name|escape}
{/if}
<br><b>{$l->s('date_start')}:</b> {$game.date_created|date_format:"%d.%m.%y - %H:%M:%S"}
<br><b>{$l->s('duration')}:</b> {include file="game_duration.tpl"}
{if $game.type == 'settle'}
  <br><b>{$l->s('duration_equivalent')}:</b>
                {assign var="duration" value=$game.frame/36}
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$duration/3600}{assign var="hours" value=$hours|string_format:"%02d"}
                {assign var="minutes" value=$duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {assign var="seconds" value=$duration-$hours*3600-$minutes*60}{assign var="seconds" value=$seconds|string_format:"%02d"}
                {$hours}:{$minutes}:{$seconds}
{/if}

<br><b>{$l->s('date_last_update')}:</b> {$game.date_last_update|date_format:"%d.%m.%y - %H:%M:%S"}
<br><b>{$l->s('status')}:</b>
                {if $game.status == 'running'}
                    <img src="images/icons/status_running_16.gif" title="{$l->s('running')}">
                    {if $game.is_join_allowed}<img src="images/icons/status_runtimejoin_16.gif" title="{$l->s('is_join_allowed')}">{/if}
                {elseif $game.status == 'lobby'}
                    <img src="images/icons/status_lobby_16.gif" title="{$l->s('lobby')}">
                {/if}
                {if $game.is_official_server}<img src="images/icons/official_server_16.png" title="{$l->s('official_server')}">{/if}
                {if $game.is_password_needed}<img src="images/icons/password_needed_16.png" title="{$l->s('password_needed')}">{/if}
                {if $game.is_fair_crew_strength}<img src="images/icons/fair_crew_strength_16.png" title="{$l->s('fair_crew_strength')}">{/if}

				{if $u->check_operator_permission("game","download_record", $leagues) && !$game.is_revoked }
					{include file="admin/game_download_record.tpl"}
				{/if}
				{if $u->check_operator_permission("game","revoke", $leagues) && !$game.is_revoked }
					{include file="admin/game_revoke.tpl"}
				{/if}
</div>
<div class="textleft">
<table>
    <tr class="th">
        <th>{$l->s('teams')}</th>
        <th>{$l->s('players')}</th>
        {if $game.type=='melee'}<th>{$l->s('old_player_score')}</th>{/if}
        {if $game.status=='ended'}
          <th>{$l->s('game_score')}</th>
          {if $game.type=='melee'}<th>{$l->s('bonus')}</th>{/if}
          {if $game.type=='melee'}<th>{$l->s('new_player_score')}</th>{/if}
        {/if}
    </tr>
    {foreach from=$teams item=team name="teams"}
        {if !$game.is_randominv_teamdistribution || $game.status!='lobby'}
          <tr>
              <td {if $team.players|@count>1}rowspan="{$team.players|@count}"{/if}>
                  <font style="color:{$team.color|string_format:"%06X"}; font-weight:bold;">&bull;</font> <b>{$team.name|escape}</b> {*(<font color="{$team.color|string_format:"%06X"}">FARBE</font>)*}
              </td>
        {/if}
              {foreach from=$team.players item="player" name="players"}
                  {if !$smarty.foreach.players.first && (!$game.is_randominv_teamdistribution || $game.status!='lobby')}<tr>{/if}
                  {if $game.is_randominv_teamdistribution && $game.status=='lobby'}<tr><td>{$l->s('random_team')}</td>{/if}

                  <td>
                    <font style="color:{$player.color|string_format:"%06X"}; font-weight:bold;">&bull;</font>
                    <span class="{if $team.team_status == 'won' && $game.status=='ended'}scorewon{elseif $team.team_status == 'lost' && $game.status=='ended'}scorelost{else}score{/if}"> 
                        <a href="{url part="user" method="details"}user[id]={$player.user_id}">{if $player.status=='active' && $game.status=='running'}({if $player.clan_tag}[{$player.clan_tag}]{/if}{$player.name|escape}){else}{if $player.clan_tag}[{$player.clan_tag}]{/if}{$player.name|escape}{/if}</a>{if $player.is_disconnected} ({$l->s('disconnected')}){/if}
						{if $player.reg_uid && $u->is_any_operator() && $player.reg_uid != $player.user_id}
                        (@ <a href="{url part="user" method="details"}user[id]={$player.reg_uid}">{$player.reg_name|escape}</a>)
                        {/if}
                    </span>
                  </td>

                  {if $game.type=='melee'}
                    <td>
                      {foreach from=$player.scores item=score name="score"}
                          <a href="{url part="league" method="ranking"}league[id]={$score.league_id}">{if $score.league_icon}<img src="{$score.league_icon}" title="{$score.league_name}">{else}{$score.league_name}{/if}</a>
                              {$score.old_player_score}
                          {if !$smarty.foreach.score.last && $smarty.foreach.score.total>1} | {/if}
                      {/foreach}
                    </td>
                  {/if}

                  {if $game.status=='ended'}
                  <td>
                    {foreach from=$player.scores item=score name="score"}
                        <a href="{url part="league" method="ranking"}league[id]={$score.league_id}">{if $score.league_icon}<img src="{$score.league_icon}" title="{$score.league_name}">{else}{$score.league_name}{/if}</a>
                        <span class="{if $team.team_status == 'won' && $game.status=='ended'}scorewon{elseif $team.team_status == 'lost' && $game.status=='ended'}scorelost{else}score{/if}">{if $score.score > 0}+{/if}{$score.score}{if $game.type=='settle' && $score.score && $score.settle_rank} ({$l->s('rank')} {$score.settle_rank}){/if}</span>
                        {if !$smarty.foreach.score.last && $smarty.foreach.score.total>1} | {/if}
                    {/foreach}
                  </td>
                  
                  <td>
                    {foreach from=$player.scores item=bonus name="score"}
                        {if $score.bonus > 0}<span class="scorewon">+{$score.bonus}</span>{/if}
                        {if !$smarty.foreach.score.last && $smarty.foreach.score.total>1} | {/if}
                    {/foreach}
                  </td> 

                  {if $game.type=='melee'}
                    <td>
                      {foreach from=$player.scores item=score name="score"}
                          <a href="{url part="league" method="ranking"}league[id]={$score.league_id}">{if $score.league_icon}<img src="{$score.league_icon}" title="{$score.league_name}">{else}{$score.league_name}{/if}</a>
                              {$score.old_player_score+$score.score+$score.bonus}
                          {if !$smarty.foreach.score.last && $smarty.foreach.score.total>1} | {/if}
                      {/foreach}
                        </td>
                  {/if}
                 {/if}
                </tr>
              {/foreach}
    {/foreach}

</table>
<!--<br>
<pre>{$game_reference|escape}</pre>-->
</div>
<div style="clear:both"></div>
{if $game.is_revoked}</div>{/if}
