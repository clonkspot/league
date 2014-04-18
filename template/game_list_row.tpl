{*first part of game-list-row*}
{strip}
            <td>
                {if $game.leagues}
                  {foreach from=$game.leagues item=league}
                      <a href="?part=league&method=ranking&league[id]={$league.id}"><img src="{$league.icon}" alt="{$league.name}" title="{$league.name}"></a>
                  {/foreach}
                {/if}
            </td>
            <td>
                {if $game.status == 'running'}
                    <img src="images/icons/status_running_16.png" title="{$l->s('running')}">
                    {if $game.is_join_allowed}
                    <img src="images/icons/status_runtimejoin_16.png" title="{$l->s('is_join_allowed')}">
                    {/if}
                {elseif $game.status == 'lobby'}
                    <img src="images/icons/status_lobby_16.png" title="{$l->s('lobby')}">
                {/if}
                {if $game.is_official_server}<img src="images/icons/official_server_16.png" title="{$l->s('official_server')}">{/if}
                {if $game.is_password_needed}<img src="images/icons/password_needed_16.png" title="{$l->s('password_needed')}">{/if}
                {if $game.is_fair_crew_strength}<img src="images/icons/fair_crew_strength_16.png" title="{$l->s('fair_crew_strength')}">{/if}
            </td>
            <td>
                {*{if $game.icon_number >= 0 && $game.icon_number != ''}<img src="images/icons/scenarios/{$game.icon_number}.png">{/if}*}
                {if $game.type=='noleague'}
                    {$game.scenario_name|escape}
                {else}
                    <a href="?part=game&method=details&game[id]={$game.id}">{$game.scenario_name|escape}</a>
                {/if}
            </td>
            <td align="right">
                {*{if $smarty.now|date_format:"%d.%m.%Y" == $game.date_created|date_format:"%d.%m.%Y"}
                    {$game.date_created|date_format:"%H:%M:%S"}
                {else}
                    {$game.date_created|date_format:"%d.%m.%Y"}
                {/if}*}
                  {$game.date_created|date_format:"%d.%m.%y&nbsp;-&nbsp;%H:%M"}
            </td>
{/strip}