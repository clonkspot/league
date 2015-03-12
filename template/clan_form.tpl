<form action="{url part="clan" method="edit2"}" method="post">
{if $edit_type=="edit"}
    <input type="hidden" name="method" value="edit2">
{else}
    <input type="hidden" name="method" value="add2">
    <br><b>{$l->s('clan_autodelete_note')}</b><br><br>
{/if}

<table class="simple">
<tr>
  <td>
      <b>{$l->s('name')}:</b>
  </td>
  <td>
      <input type="text" name="clan[name]" value="{$clan.name}" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('clan_tag')}:</b>
  </td>
  <td>
      <input type="text" name="clan[tag]" value="{$clan.tag}" size="5">
  </td>
</tr>
<tr>
<tr>
  <td>
      <b>{$l->s('description')}:</b>
  </td>
  <td>
      <textarea type="text" name="clan[description]" cols="60" rows="10" maxlength="500">{$clan.description}</textarea>
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('clan_website')}:</b>
  </td>
  <td>
      <input type="text" name="clan[link]" value="{$clan.link}" size="60" maxlength="100">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('password')}:</b>
  </td>
  <td>
      <input type="password" name="clan[password]" value="" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('repeat')}:</b>
  </td>
  <td>
      <input type="password" name="clan[password2]" value="" size="32">
  </td>
</tr>
<tr>
  <td>
      <b>{$l->s('clan_join_disabled')}:</b>
  </td>
    </td><td>
        <select name="clan[join_disabled]" size="1">
            <option {if $clan.join_disabled == 'Y'}selected="selected"{/if} value="Y">{$l->s('yes')}</option>
            <option {if $clan.join_disabled == 'N'}selected="selected"{/if} value="N">{$l->s('no')}</option>
        </select>
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

{if $edit_type=='edit'}
 <form action="?part=clan" method="post">
 <input type="hidden" name="method" value="delete2">
<table class="simple">
<tr>
  <td>
      <input type="hidden" name="clan[id]" value="{$clan.id}">
      <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
  </td>
</tr>
</table>
</form>
{/if}