<div class="navbar">
  <ul>
    <li><a href="{url part="league" method="list"}">{$l->s('leagues')}</a></li>
    <li><a href="{url part="league" method="ranking"}">{$l->s('rankings')}</a></li>
    <li><a href="{url part="game" method="list"}">{$l->s('games')}</a></li>
    <li><a href="{url part="scenario" method="list"}">{$l->s('scenarios')}</a></li>
    <li><a href="{url part="user" method="list"}">{$l->s('players')}</a></li>
    <li><a href="{url part="clan" method="list"}">{$l->s('clans')}</a></li>
    {if $user_logged_in}<li><a href="{url part="user" method="edit"}">{$l->s('profile')}</a></li>{/if}
    {if $user_logged_in && !$user_logged_in_via_cookie}<li><a href="{url part="user" method="logout"}">{$l->s('logout')}</a></li>{/if}
    {if !$user_logged_in}<li><a href="{url part="user" method="login"}">{$l->s('login')}</a></li>{/if}
    {if isset($helplink)}<li><a href="{$helplink}">{$l->s('help')}</a></li>{/if}
    {if $user_is_admin}<li><a href="{$base_path}admin.php?part=log&method=list">{$l->s('admin')}</a></li>{/if}
  </ul>
</div>
