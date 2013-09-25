<div class="filter">
  {include file="func_search.tpl" link="?part=clan&method=list"}
  {include file="func_filter.tpl" link="?part=clan&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="func_header_line.tpl" func="clans" page_link="?part=clan&method=list"}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link="?part=clan&method=list" value="name"}
            {include file="func_tableheader.tpl" link="?part=clan&method=list" value="tag" text=$l->s('clan_tag')}
            {include file="func_tableheader.tpl" link="?part=clan&method=list" value="link" text=$l->s('clan_website')}
            {include file="func_tableheader.tpl" link="?part=clan&method=list" value="date_created" text=$l->s('clan_date_created')}
            {include file="func_tableheader.tpl" link="?part=clan&method=list" value="user_count" text=$l->s('players')}
        </tr>
    {foreach item=clan from=$clans name="clans"}
        <tr>
            <td><a href="?part=clan&method=details&clan[id]={$clan.id}">{$clan.name|escape}</a></td>
            <td>{$clan.tag|escape}</td>
            <td><a target="_blank" href="http://{$clan.link|escape}">{$clan.link|escape}</a></td>
            <td>{$clan.date_created|date_format:"%d.%m.%Y"}</td>
            <td>
                {foreach item=user from=$clan.users name="users"} <b><a href="?part=user&method=details&user[id]={$user.id}">{$user.name|escape}</a></b>{if !$smarty.foreach.users.last},{/if}{/foreach}
            </td>
        </tr>
    {/foreach}
</table>