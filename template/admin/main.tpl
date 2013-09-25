<html>
<head>
    <link rel="stylesheet" type="text/css" href="league.css">
</head>
<body>




{if $user_logged_in}
  {include file='header.tpl'}
{/if}

{include file='message_box.tpl'}

{if $user_logged_in}
  {if 'scenario'==$part}
      {if 'list'==$method || 'add2'==$method || 'edit2'==$method || 'delete2'==$method}
          {include file='scenario_list.tpl'}
      {elseif 'add'==$method || 'edit'==$method}
          {include file='scenario_add_edit.tpl'}
      {/if}
  {elseif 'league'==$part}
      {if 'list'==$method || 'add2'==$method || 'edit2'==$method || 'delete2'==$method}
          {include file='league_list.tpl'}
      {elseif 'add'==$method || 'edit'==$method}
          {include file='league_add_edit.tpl'}
      {/if}
  {elseif 'log'==$part}
      {if 'list'==$method}
          {include file='log.tpl'}
      {elseif 'statistics'==$method || 'reset_statistics2'==$method}
          {include file='statistics.tpl'}
      {/if}
  {elseif 'user'==$part}
      {if 'details'==$method}
          {include file='user_details.tpl'}
      {elseif 'list'==$method || 'delete'==$method || 'reset_password'==$method || 'rename'==$method}
          {include file='user_list.tpl'}
      {/if}
  {elseif 'clan'==$part}
      {if 'details'==$method}
          {include file='clan_details.tpl'}
      {elseif 'list'==$method || 'delete'==$method}
          {include file='clan_list.tpl'}
      {/if}
  {elseif 'game'==$part}
      {if 'revoke'==$method}
          {include file='log.tpl'}
      {elseif 'add'==$method}
          {include file='game_add.tpl'}
      {elseif 'add2'==$method}
          {include file='log.tpl'}
      {/if} 
  {elseif 'cuid_ban'==$part}
      {if 'list'==$method || 'add2'==$method || 'edit2'==$method || 'delete2'==$method}
          {include file='cuid_ban_list.tpl'}
      {elseif 'add'==$method || 'edit'==$method}
          {include file='cuid_ban_add_edit.tpl'}
      {/if}
  {elseif 'resource'==$part}
      {if 'list'==$method || 'add2'==$method || 'edit2'==$method || 'delete2'==$method}
          {include file='resource_list.tpl'}
      {elseif 'add'==$method || 'edit'==$method}
          {include file='resource_add_edit.tpl'}
      {/if}
  {/if}
{else}
  {include file='login.tpl'}
{/if}


</body>
</html>