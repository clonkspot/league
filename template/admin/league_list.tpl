{include file="../func_header_line.tpl" func="leagues" page_link="?part=league&method=list"}
<a href="?part=league&method=add">{$l->s('add')}</a><br><br>

<table>
        <tr class="th">
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="name"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="description"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="type"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="recurrent"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="open_league"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="scenarios"}
            {include file="../func_tableheader.tpl" link="?part=league&method=list" value="date_end"}
        </tr>
    {foreach item=league from=$leagues name="league"}
        <tr>
            <td><a href="?part=league&method=edit&league[id]={$league.id}">{$league.name}</a></td>
            <td>{$league.description}</td>
            <td>{$league.type}</td>
            <td>{$league.recurrent}</td>
            <td>{if $league.scenario_restriction == 'Y'}{$l->s('no')}{else}{$l->s('yes')}{/if}</td>
            <td>
                {foreach from=$league.scenarios item="scenario" name="scenarios"}
                    <a href="?part=scenario&method=edit&scenario[id]={$scenario.id}">{$scenario.name}</a>{if !$smarty.foreach.scenarios.last},{/if}
                {/foreach}
            </td>
            <td>{$league.date_end|date_format:"%d.%m.%y - %H:%M:%S"}</td>
        </tr>
    {/foreach}
</table>