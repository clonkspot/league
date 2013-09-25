<div class="filter">
  {foreach from=$products item=product}
      {include file="../func_filter.tpl" link="?part=scenario&method=list" name="product_name" value=$product.name text=$product.name}
  {/foreach}
  {include file="../func_filter.tpl" link="?part=scenario&method=list" name="product_name" value=""}
  <img class="vrbar" src="images/vr_bar.gif">
  {include file="../func_search.tpl" link="?part=scenario&method=list"}
  {include file="../func_filter.tpl" link="?part=scenario&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="../func_header_line.tpl" func="scenarios" page_link="?part=scenario&method=list"}
<a href="?part=scenario&method=add">{$l->s('add')}</a><br><br>

<form action="?part=scenario" method="post">
    <input type="hidden" name="method" value="delete_never_played2">
    <input type="submit" value="{$l->s('delete_never_played_scenarios')}">
</form>

<table>
        <tr class="th">
            <td></td>
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="name"}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="active"}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="type"}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="games_count" text=$l->s('game_count')}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="autocreated"}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="settle_base_score"}
            {include file="../func_tableheader.tpl" link="?part=scenario&method=list" value="settle_time_bonus_score"}
        </tr>
    {foreach item=scenario from=$scenarios name="scenario"}
        <tr>
            <td><img src="{$scenario.product_icon}" alt="{$scenario.product_name}"></td>
            <td>
                {*{if $scenario.icon_number >= 0 && $scenario.icon_number != ''}<img src="images/icons/scenarios/{$scenario.icon_number}.png">{/if}*}
                <a href="?part=scenario&method=edit&scenario[id]={$scenario.id}">{$scenario.name|escape}</a>
            </td>
            <td>{$scenario.active}</td>
            <td>{if $scenario.type == 'melee'}{$l->s('melee')}
                {elseif $scenario.type == 'team_melee'}{$l->s('team_melee')}
                {elseif $scenario.type == 'settle'}{$l->s('settle')}
                {/if}
            <td>{$scenario.games_count}</td>
            <td>{$scenario.autocreated}</td>
            <td>{$scenario.settle_base_score}</td>
            <td>{$scenario.settle_time_bonus_score}</td>
        </tr>
    {/foreach}
</table>