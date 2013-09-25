{$l->s('log')}:
<a href="logs/sql_error_log.txt">{$l->s('sql_error_log')}</a> <a href="logs/sql_slow_log.txt">{$l->s('sql_slow_log')}</a>
<div class="filter">
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="info"}  
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="game_info"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="auth_join"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="game_start"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="error"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="user_error"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="type" value="" }
<img class="vrbar" src="images/vr_bar.gif">
{include file="../func_search.tpl" link="?part=log&method=list"}
{include file="../func_filter.tpl" link="?part=log&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="../func_header_line.tpl" func="log" page_link="?part=log&method=list"}

<table>
        <tr class="th">
            {include file="../func_tableheader.tpl" link="?part=log&method=list" value="date"}
            {include file="../func_tableheader.tpl" link="?part=log&method=list" value="type"}
            {include file="../func_tableheader.tpl" link="?part=log&method=list" value="csid"}
            {include file="../func_tableheader.tpl" link="?part=log&method=list" value="string"}
        </tr>
    {foreach item=log_line from=$log_data name="log"}
        <tr>
            <td>[{$log_line.date|date_format:'%d.%m.%y&nbsp;-&nbsp;%H:%M:%S'}]</td>
            <td>{$log_line.type}</td>
            <td>{$log_line.csid}</td>
            <td>{$log_line.string|escape}</td>
        </tr>
    {/foreach}
</table>