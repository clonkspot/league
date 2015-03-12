{*second part of game-list-row*}
{strip}
            <td>
              {foreach from=$game.teams item="team" name="teams"}
                {foreach from=$team.players item="player" name="players"}
                    <span class="{if $team.team_status == 'won' && $game.status=='ended'}scorewon{elseif $team.team_status == 'lost' && $game.status=='ended'}scorelost{else}score{/if}">{if $game.type!='noleague'}<a href="{url part="user" method="details" q="user[id]={$player.user_id}"}">{/if}{if $player.status=='active' && $game.status=='running'}({if $player.clan_tag}[{$player.clan_tag}]{/if}{$player.name|escape}){else}{if $player.clan_tag}[{$player.clan_tag}]{/if}{$player.name|escape}{/if}{if $game.type!='noleague'}</a>{/if}</span>{if !$smarty.foreach.players.last || ($game.is_randominv_teamdistribution && !$smarty.foreach.teams.last && $game.status=='lobby')},{/if}
                {/foreach}
                {if !$game.is_randominv_teamdistribution || $game.status!='lobby'}{if !$smarty.foreach.teams.last}{" vs. "}{/if}{/if}
              {/foreach}
            </td>
{/strip}