{include file="../func_header_line.tpl" func="player"}

<b>{$l->s('name')}:</b> <a href="index.php?part=user&method=details&user[id]={$user.id}">{if $user.is_deleted}[{/if}{$user.name|escape}{if $user.is_deleted}]{/if}</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<form style="display:inline;" method="post" action="?part=user">
    <input type="hidden" name="method" value="rename">
    <input type="hidden" name="user[id]" value="{$user.id}">
    <input type="text" name="user[name]" value="{$user.name|escape}">
    <input type="submit" value="{$l->s('rename')}">
</form>
<br>
<b>{$l->s('realname')}:</b> {$user.real_name|escape}
<br>
<b>{$l->s('email')}:</b> {$user.email|escape}
<br>
<b>{$l->s('cuid')}:</b> {$user.cuid|escape}
<br>
<b>{$l->s('date_created')}:</b> {$user.date_created|date_format:"%d.%m.%Y"}
<br>
<b>{$l->s('scenario_data')}:</b>
<table class="simple" width="250">
{foreach from=$scenario_data item=scen_data}
	<tr><td><b><a href="?part=scenario&method=edit&scenario[id]={$scen_data.scenario_id}">{$scen_data.scenario_id}</a>:</b></td><td>{$scen_data.data|escape:"htmlall"}</td></tr>
{/foreach}
</table>
<br>
<br>
<form method="post" action="?part=user">
    <input type="hidden" name="method" value="reset_password">
    <input type="hidden" name="user[id]" value="{$user.id}">
    <input type="submit" value="{$l->s('reset_password')}">
</form>
<br>
<form method="post" action="?part=user">
    <input type="hidden" name="method" value="delete">
    <input type="hidden" name="user[id]" value="{$user.id}">
    <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
</form>
<br><br>