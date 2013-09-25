<div class="filter">
  {foreach from=$products item=product}
      {include file="func_filter.tpl" link="?part=league&method=list" name="p.name" value=$product.name text=$product.name}
  {/foreach}
  {include file="func_filter.tpl" link="?part=league&method=list" name="p.name" value="" text=$l->s('products')}
</div>

{include file="func_header_line.tpl" func="leagues" page_link="?part=game&method=list"}

<table>
        <tr class="th">
            <td></td><td></td>
            {include file="func_tableheader.tpl" link="?part=league&method=list" value="name"}
            {include file="func_tableheader.tpl" link="?part=league&method=list" value="description"}
            {include file="func_tableheader.tpl" link="?part=league&method=list" value="type"}
            {include file="func_tableheader.tpl" link="?part=league&method=list" value="date_end"}
            {include file="func_tableheader.tpl" link="?part=league&method=list" value="scenarios"}
        </tr>
    {foreach item=league from=$leagues name="league"}
        <tr {if !$league.is_current}class="revoked"{/if}>
            <td><img src="{$league.product_icon}" alt="{$league.product_name}"></td>
            <td><a href="?part=league&method=ranking&league[id]={$league.id}"><img src="{$league.filter_icon_on}" alt=""></a></td>
            <td><a href="?part=league&method=ranking&league[id]={$league.id}">{$league.name}</a></td>
            <td>{$league.description}</td>
            <td>{$l->s($league.type)}{if $league.scenario_restriction == 'N'}, {$l->s('open_league')}{/if}</td>
            <td>{$league.date_end|date_format:"%d.%m.%y"}</td>
            <td>
                {foreach from=$league.scenarios item="scenario" name="scenarios"}
                    <a href="?part=game&method=list&filter[scenario_name][]={$scenario.name|escape}&filter[scenario_id][]={$scenario.id}&sort[col]=settle_rank&sort[dir]=asc">{$scenario.name|escape}</a>{if !$smarty.foreach.scenarios.last},{/if}
                {/foreach}
            </td>
        </tr>
    {/foreach}
</table>