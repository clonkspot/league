{*first part of game-list-row*}
{strip}
            <td>
                {if $game.leagues}
                  {foreach from=$game.leagues item=league}
                      <a href="{url part="league" method="ranking" q="league[id]={$league.id}"}"><img src="{$base_path}{$league.icon}" alt="{$league.name}" title="{$league.name}"></a>
                  {/foreach}
                {/if}
            </td>
            <td>
                {if $game.status == 'running'}
                    <img src="{$base_path}images/icons/status_running_16.gif" title="{$l->s('running')}">
                    {if $game.is_join_allowed}
                        <a href="clonk://league.clonkspot.org:80/?action=query&game_id={$game.id}"><img src="{$base_path}images/icons/status_runtimejoin_16.gif" title="{$l->s('is_join_allowed')}"></a>
                    {/if}
                {elseif $game.status == 'lobby'}
                    <a href="clonk://league.clonkspot.org:80/?action=query&game_id={$game.id}"><img src="{$base_path}images/icons/status_lobby_16.gif" title="{$l->s('lobby')}"></a>
                {/if}
                {if $game.is_official_server}<img src="{$base_path}images/icons/official_server_16.png" title="{$l->s('official_server')}">{/if}
                {if $game.is_password_needed}<img src="{$base_path}images/icons/password_needed_16.png" title="{$l->s('password_needed')}">{/if}
                {if $game.is_fair_crew_strength}<img src="{$base_path}images/icons/fair_crew_strength_16.png" title="{$l->s('fair_crew_strength')}">{/if}
            </td>
            <td>
                {*{if $game.icon_number >= 0 && $game.icon_number != ''}<img src="{$base_path}images/icons/scenarios/{$game.icon_number}.png">{/if}*}
                {if $game.type=='noleague'}
                    {$game.scenario_name|escape}
                {else}
                    <a href="{url part="game" method="details" q="game[id]={$game.id}"}">{$game.scenario_name|escape}</a>
                {/if}
            </td>
            <td align="right" data-iso-timestamp="{$game.date_created|date_format:"%Y-%m-%dT%H:%M:%SZ"}" data-timestamp-meticulousness="MINUTES">
                {*{if $smarty.now|date_format:"%d.%m.%Y" == $game.date_created|date_format:"%d.%m.%Y"}
                    {$game.date_created|date_format:"%H:%M:%S"}
                {else}
                    {$game.date_created|date_format:"%d.%m.%Y"}
                {/if}*}
                  {$game.date_created|date_format:"%d.%m.%y&nbsp;-&nbsp;%H:%M"}
            </td>
{/strip}
