<div class="filter">
  {include file="../func_search.tpl" link="?part=resource&method=list"}
  {include file="../func_filter.tpl" link="?part=resource&method=list" name="search" value="" text=$l->s('search')}
</div>

{include file="../func_header_line.tpl" func="resource_list" page_link="?part=resource&method=list"}
<a href="?part=resource&method=add">{$l->s('add')}</a><br><br>

<table>
        <tr class="th">
            {include file="../func_tableheader.tpl" link="?part=resource&method=list" value="filename"}
            {include file="../func_tableheader.tpl" link="?part=resource&method=list" value="hash"}
        </tr>
    {foreach item=resource from=$resources name="resources"}
        <tr>
            <td>{$resource.filename|escape}</td>
            <td><a href="?part=resource&method=edit&resource[hash]={$resource.hash}">{$resource.hash|escape}</a></td>
        </tr>
    {/foreach}
</table>