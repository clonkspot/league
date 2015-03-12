{include file="func_header_line.tpl" func="profile"}

{include file="func_header_line.tpl" func="clan"}

<br>
{if !$user.clan_id}
  <br>
	    <form method='post' action='?part=clan'>
    <input type="hidden" name="method" value="join">
      <b>{$l->s('clan')}:</b>
      <select name="clan[id]">
        <option value="0">{$l->s('clan_choose')}</option>      
        {foreach from=$clans item=clan}
            <option value="{$clan.id}">{$clan.name}</option>
        {/foreach}
      </select>
      {$l->s('password')}: <input type="password" size="10" name="clan[password]">
      <input type="submit" value="{$l->s('clan_join')}">
  </form>
  <br>
  <form method='post' action='?part=clan'>
      <input type="hidden" name="method" value="add">
      <input type="submit" value="{$l->s('clan_found')}">
  </form>

{else}
  <b>{$l->s('clan')}: {$clan.name}</b><br/><br/>

  <table>
      <tr class="th">
          <th>{$l->s('player')}</th>
          <th></th>
          <th></th>
      </tr>
  {foreach item=clan_user from=$clan.users name="users"}
      <tr>
          <td>
            <b><a href="{url part="user" method="details" q="user[id]={$clan_user.id}"}">{$clan_user.name}</a></b>
            {if $clan_user.id == $clan.founder_user_id}({$l->s('clan_founder')}){/if}
          </td>
          <td>
              {if $user.id != $clan_user.id && $clan.founder_user_id == $user.id}
                  <form style="display:inline;" action="?part=clan" method="post">
                    <input type="hidden" name="method" value="kick">
                    <input type="hidden" name="user[id]" value="{$clan_user.id}">
                    <input type="submit" value="{$l->s('clan_kick_member')}">
                  </form>
              {/if}
          </td>
          <td>
              {if $user.id != $clan_user.id &&  $clan.founder_user_id == $user.id}
                  <form style="display:inline;" action="?part=clan" method="post">
                    <input type="hidden" name="method" value="transfer_founder">
                    <input type="hidden" name="user[id]" value="{$clan_user.id}">
                    <input type="submit" value="{$l->s('clan_transfer_founder')}">
                  </form>
              {/if}
          </td>
      </tr>
  {/foreach}
  </table>

  {if $clan.founder_user_id == $user.id}
    <br>
    <form method='post' action='?part=clan'>
        <input type="hidden" name="method" value="edit">
        <input type="submit" value="{$l->s('clan')} {$l->s('edit')}">
    </form>
  {else}
    <br>
    <form method='post' action='?part=clan'>
        <input type="hidden" name="method" value="leave">
        <input type="submit" value="{$l->s('clan_leave')}">
    </form>
  {/if}

  <br>
{/if}