<div class="filter">
  {include file="../func_search.tpl" link="?part=cuid_ban&method=list"}
  {include file="../func_filter.tpl" link="?part=cuid_ban&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="../func_header_line.tpl" func="cuid_ban_list" page_link="?part=cuid_ban&method=list"}
<a href="?part=cuid_ban&method=add">{$l->s('add')}</a><br><br>

<table>
        <tr class="th">
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="user_name"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="cuid"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="is_league_only"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="date_created"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="date_until"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="reason"}
            {include file="../func_tableheader.tpl" link="?part=cuid_ban&method=list" value="comment"}
        </tr>
    {foreach item=cuid_ban from=$cuid_bans name="cuid_bans"}
        <tr {if $cuid_ban.date_until < $smarty.now}class="revoked"{/if}>
            <td><a href="?part=user&method=details&user[id]={$cuid_ban.user_id}">{if $cuid_ban.user_is_deleted}[{/if}{$cuid_ban.user_name|escape}{if $cuid_ban.user_is_deleted}]{/if}</a></td>
            <td><a href="?part=cuid_ban&method=edit&cuid_ban[cuid]={$cuid_ban.cuid}">{$cuid_ban.cuid|default:"___"}</a></td>
            <td>{if $cuid_ban.is_league_only}{$l->s('yes')}{else}{$l->s('no')}{/if}</td>
            <td>{$cuid_ban.date_created|date_format:"%d.%m.%Y"}</td>
            <td>{$cuid_ban.date_until|date_format:"%d.%m.%Y"}</td>
            <td>{$cuid_ban.reason|escape}</td>
            <td>{$cuid_ban.comment|escape}</td>
        </tr>
    {/foreach}
</table>