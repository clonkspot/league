{if $new_user}
    {include file="func_header_line.tpl" func="new_user"}
{else}
    {include file="func_header_line.tpl" func="login"}
{/if}

<form action="?part=login&method=login" method="post">
<table class="simple">
<tr>
    <td>
        <b>{$l->s('name_or_userid')}:</b>
    </td>
    <td>
        {if !$new_user}
            <input type="text" name="login_name" value="" size="32">
        {else}
            {$smarty.request.login_name}
            <input type="hidden" name="login_name" value="{$smarty.request.login_name}">
            <input type="hidden" name="login_password" value="{$smarty.request.login_password}">
        {/if}
    </td>
</tr>

{if !$new_user}
  <tr>
      <td>
          <b>{$l->s('password_or_webcode')}:</b>
      </td>
      <td>
          <input type="password" name="login_password" value="" size="32">
      </td>
  </tr>
{/if}

{if $new_user}
  <tr>
      <td>
          <b>{$l->s('new_username')}:</b>
      </td>
      <td>
          <input type="text" name="login_new_name" value="" size="32">
      </td>
  </tr>
{/if}

<tr>
    <td colspan="2">
        <input type="submit" value="{$l->s('login')}">
    </td>
</tr>

</table>
</form>