<form action="?part=user" method="post">
<input type="hidden" name="method" value="edit2">
<table class="simple">
<tr>
  <td>
      <b>{$l->s('name')}:</b>
  </td>
  <td>
      {$user.name|escape}
  </td>
  <td>
      
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('forumaccount')}:</b>
  </td>
  <td>
      {$user.cuid|escape}
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

</table>

</form>