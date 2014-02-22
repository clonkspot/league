<div class="navbar">
  <ul>
    <li><a href="?part=league&method=list">{$l->s('leagues')}</a></li>
    <li><a href="?part=league&method=ranking">{$l->s('rankings')}</a></li>
    <li><a href="?part=game&method=list">{$l->s('games')}</a></li>
    <li><a href="?part=scenario&method=list">{$l->s('scenarios')}</a></li>
    <li><a href="?part=user&method=list">{$l->s('players')}</a></li>
    <li><a href="?part=clan&method=list">{$l->s('clans')}</a></li>
    {if $user_logged_in}<li><a href="?part=user&method=edit">{$l->s('profile')}</a></li>{/if}
    {if $user_logged_in}<li><a href="?part=user&method=logout">{$l->s('logout')}</a></li>
    {else}<li><a href="?part=user&method=login">{$l->s('login')}</a></li>{/if}
    <li><a href="/forum/{$l->get_current_language_code()}/board_show.pl?bid={if $l->get_current_language_code()=='de'}28{else}16{/if}">{$l->s('help')}</a></li>
    {if $user_is_admin}<li><a href="admin.php?part=log&method=list">{$l->s('admin')}</a></li>{/if}
  </ul>
</div>