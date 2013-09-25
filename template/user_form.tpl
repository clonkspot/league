<form action="?part=user" method="post">
<input type="hidden" name="method" value="edit2">
<table class="simple">
<tr>
  <td>
      <b>{$l->s('name')}:</b>
  </td>
  <td>
      {if $user.date_last_rename + 2*30*24*60*60 < $smarty.now}
      <input type="text" name="user[name]" value="{$user.name}" size="32">
      {else}
      {$user.name|escape}
      {/if}
  </td>
  <td>
	({$l->s('rename_limit')})
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('password')}:</b>
  </td>
  <td>
      <input type="password" name="user[password]" value="" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('repeat')}:</b>
  </td>
  <td>
      <input type="password" name="user[password2]" value="" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('realname')}:</b>
  </td>
  <td>
      <input type="text" name="user[real_name]" value="{$user.real_name}" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('email')}:</b>
  </td>
  <td>
      <input type="text" name="user[email]" value="{$user.email}" size="32">
  </td>
</tr>

<tr><td></br></td><td></td></tr>
<tr>
    <td>
    <input type="submit" value="{$l->s('save')}">
    </td>
    <td></td>
</tr>

</table>

</form>