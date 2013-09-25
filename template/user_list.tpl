<div class="filter">
  {include file="func_search.tpl" link="?part=user&method=list"}
  {include file="func_filter.tpl" link="?part=user&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="func_header_line.tpl" func="players" page_link="?part=user&method=list"}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="clan_tag"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="name"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="real_name" text=$l->s('realname')}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="games_melee_won"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="games_melee_lost"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="games_settle_won"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="games_settle_lost"}
            {include file="func_tableheader.tpl" link="?part=user&method=list" value="date_created"}
        </tr>
    {foreach item=user from=$users name="users"}
        <tr>
            <td><a href="?part=clan&method=details&clan[id]={$user.clan_id}">{$user.clan_tag|escape}</a></td>
            <td><a href="?part=user&method=details&user[id]={$user.id}">{$user.name|escape}</a></td>
            <td>{$user.real_name|escape}</td>
            <td>{$user.games_melee_won}</td>
            <td>{$user.games_melee_lost}</td>
            <td>{$user.games_settle_won}</td>
            <td>{$user.games_settle_lost}</td>
            <td>{$user.date_created|date_format:"%d.%m.%Y"}</td>
        </tr>
    {/foreach}
</table>