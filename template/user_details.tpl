{include file="func_header_line.tpl" func="player"}

<div class="textleft">
<table class="simple" width="250">
<tr><td><b>{$l->s('name')}:</b></td><td width="150">
{if $user.is_deleted}[{/if}{$user.name|escape}{if $user.is_deleted}]{/if}
</td></tr>
{if $user.old_names}
<tr><td></td><td>({$user.old_names|escape})</td></tr>
{/if}
{if $user.clan_id}
<tr>
<td><b>{$l->s('clan')}:</b></td>
<td><a href="{url part="clan" method="details" q="clan[id]={$user.clan_id}"}">{$user.clan_name|escape}</a>
</td></tr>
{/if}
{if $user.real_name}
<tr><td><b>{$l->s('realname')}:</b></td><td>{$user.real_name|escape}
</td></tr>
{/if}
<tr><td><b>{$l->s('date_created')}:</b></td><td>{$user.date_created|date_format:"%d.%m.%Y"}
</td></tr>
<tr><td colspan="2">
<b><a href="{url part="game" method="list" q="filter[user_name][]={$user.name|escape}"}">{$l->s('all_games_by_user')}</a></b>
</td></tr>
</table>
</div>

<p>
<table>
    <tr class="th">
        <th>{$l->s('league')}</th>
        <th>{$l->s('rank')}</th>
        <th></th>
        <th>{$l->s('score')}</th>
        <th>{$l->s('bonus_account')}</th>
        <th>{$l->s('date_last_game')}</th>
        <th>{$l->s('league_playing_time')}</th>
        {if $u->get_id() == $user.id}<th></th>{/if}
    </tr>
    {foreach from=$scores item=score}
      <tr>
          <td>
            <a href="{url part="league" method="ranking" q="league[id]={$score.league_id}"}">{if $score.league_icon}<img src="{$score.league_icon}" title="{$score.league_name}">{/if} {$score.league_name}</a>
          </td>
          <td>
            <a href="{url part="league" method="ranking" q="league[id]={$score.league_id}&highlight={$score.user_id}"}"><b>{$score.rank}</b></a>
          </td>
          <td>
            {if $score.rank_icon}
                 <img src="{$score.rank_icon}">
            {/if}
          </td>
          <td>
            {if $score.league.type=='settle'}
                <a href="{url part="game" method="list" q="filter[league_id][]={$score.league.id}&filter[user_name][]={$score.name}&sort[col]=settle_rank&sort[dir]=asc"}">{$score.score}</a>
            {elseif $u->check_operator_permission("score","set", $score.league_id)}
                <form method='post' action='{url part="user" method="set_score" q="user[id]={$user.id}'>"}
                   <input name="league[id]" type="hidden" value="{$score.league_id}"/>
                   <input name="score" size="5" value="{$score.score}"/>
                </form>
            {else}
               {$score.score}
            {/if}
          </td>
          <td>
            {if $score.league.bonus_account_max>0}
            	(+{$score.bonus_account})
            {/if}
          </td>
          <td>{$score.date_last_game|date_format:"%d.%m.%Y"}</td>
          <td>
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$score.duration/3600}{assign var="hours" value=$hours|string_format:"%02d"}
                {assign var="minutes" value=$score.duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {$hours}:{$minutes}
          </td>
          {if $u->get_id() == $user.id}
          <td>
          {if $score.league.type!='settle' && $score.score > 0 && $score.league.date_start <= $smarty.now && $score.league.date_end >= $smarty.now}
          <form action="index.php{url part="user" method="suicide"}" method="post">
          <input type="hidden" name="user[id]" value="{$user.id}">
          <input type="hidden" name="league[id]" value="{$score.league_id}">
          <input type="submit" onClick="return confirm('{$l->s('suicide_confirm')}')" value="{$l->s('suicide')}">
          </form>
          {/if}
          </td>
          {/if}
      </tr>
    {/foreach}
</table>
</p>

<br />
