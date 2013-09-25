<form action="?part=cuid_ban" method="post">
{if $edit_type=="edit"}
<input type="hidden" name="method" value="edit2">
{else}
<input type="hidden" name="method" value="add2">
{/if}

<table>

<tr>
    <td>{$l->s('cuid')}:</td>
    <td>
         {if $edit_type=="edit"}
            {$cuid_ban.cuid}<input type="hidden" name="cuid_ban[cuid]" value="{$cuid_ban.cuid}">
         {else}
            <input type="text" name="cuid_ban[cuid]" value="{$cuid_ban.cuid}" size="8">
         {/if}
    </td>
</tr>
<tr>
    <td>{$l->s('is_league_only')}:</td>
    <td>
        <select name="cuid_ban[is_league_only]">      
            <option value="1" {if $cuid_ban.is_league_only}selected="selected"{/if}>{$l->s('yes')}</option>
            <option value="0" {if !$cuid_ban.is_league_only}selected="selected"{/if}>{$l->s('no')}</option>
        </select>
    </td>
</tr>
<tr>
  <td>
      {$l->s('date_until')} ({$l->s('date_us_format_info')}):
  </td><td>
      <input type="text" name="cuid_ban[date_until]" value="{$cuid_ban.date_until|date_format:"%Y-%m-%d"}" size="32">
  </td>
</tr>

<tr>
  <td>
      {$l->s('reason')}:
  </td><td>
      <input type="text" name="cuid_ban[reason]" value="{$cuid_ban.reason}" size="50">
  </td>
</tr>
<tr>
  <td>
      {$l->s('comment')}:
  </td><td>
      <input type="text" name="cuid_ban[comment]" value="{$cuid_ban.comment}" size="50">
  </td>
</tr>



<tr><td></br></td></tr>
    <td>
    <input type="submit" value="{$l->s('save')}">
      </td><td>
        {if $edit_type=="edit"}
        </form>
     </td></tr><tr><td>
        <form action="?part=cuid_ban" method="post">
            <input type="hidden" name="method" value="delete2">
            <input type="hidden" name="cuid_ban[cuid]" value="{$cuid_ban.cuid}">
            <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
        </form>
        {/if}
    </td>
</tr>
</table>

</form>