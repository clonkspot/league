<div class="navbar">
  <ul>
    <li><a href="?part=league&method=list">{$l->s('leagues')}</a></li>
    <li><a href="?part=league&method=ranking">{$l->s('rankings')}</a></li>
    <li><a href="?part=game&method=list">{$l->s('games')}</a></li>
    <li><a href="?part=scenario&method=list">{$l->s('scenarios')}</a></li>
    <li><a href="?part=user&method=list">{$l->s('players')}</a></li>
    <li><a href="?part=clan&method=list">{$l->s('clans')}</a></li>
    {if $user_logged_in}<li><a href="?part=user&method=edit">{$l->s('profile')}</a></li>{/if}
    {if $user_logged_in && !$user_logged_in_via_cookie}<li><a href="?part=user&method=logout">{$l->s('logout')}</a></li>{/if}
    {if !$user_logged_in}<li><a href="?part=user&method=login">{$l->s('login')}</a></li>{/if}
    {if $helplink}<li><a href="{$helplink}">{$l->s('help')}</a></li>{/if}
    {if $user_is_admin}<li><a href="admin.php?part=log&method=list">{$l->s('admin')}</a></li>{/if}
  </ul>
</div>
