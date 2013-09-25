
<h1><img src='images/tl_clonk.gif' alt='{$l->s('clonk')}'><img src='images/tl_league_{$l->get_current_language_code()}.gif' alt='{$l->s('league')}'></h1>

<div class="navbar">
<table class="navbar">
<tr>
    <td><a href="?part=league&method=list"><img src="images/icons/main_leagues.gif" title="{$l->s('leagues')}"></a></td>
    <td><a href="?part=league&method=ranking"><img src="images/icons/main_rankings.gif" title="{$l->s('rankings')}"></a></td>
    <td><a href="?part=game&method=list"><img src="images/icons/main_games.gif" title="{$l->s('games')}"></a></td>
    <td><a href="?part=scenario&method=list"><img src="images/icons/main_scenarios.gif" title="{$l->s('scenarios')}"></a></td>
    <td><a href="?part=user&method=list"><img src="images/icons/main_players.gif" title="{$l->s('players')}"></a></td>
    <td><a href="?part=clan&method=list"><img src="images/icons/main_teams.gif" title="{$l->s('clans')}"></a></td>
    {if $user_logged_in}<td><a href="?part=user&method=edit"><img src="images/icons/main_profile.gif" title="{$l->s('profile')}"></a></td>{/if}
    {if $user_logged_in}<td><a href="?part=user&method=logout"><img src="images/icons/main_logout.gif" title="{$l->s('logout')}"></td>
    {else}<td><a href="?part=user&method=login"><img src="images/icons/main_login.gif" title="{$l->s('login')}"></td>{/if}
    <td><a href="/forum/{$l->get_current_language_code()}/board_show.pl?bid={if $l->get_current_language_code()=='de'}28{else}16{/if}"><img src="images/icons/main_help.gif" title="{$l->s('help')}"></a></td>
    {if $user_is_admin}<td><a href="admin.php?part=log&method=list"><img src="images/icons/main_admin.gif" title="{$l->s('admin')}"></a></td>{/if}
</tr>
<tr>
    <td>[&nbsp;<a href="?part=league&method=list">{$l->s('leagues')}</a>&nbsp;]</td>
    <td>[&nbsp;<a href="?part=league&method=ranking">{$l->s('rankings')}</a>&nbsp;]</td>
    <td>[&nbsp;<a href="?part=game&method=list">{$l->s('games')}</a>&nbsp;]</td>
    <td>[&nbsp;<a href="?part=scenario&method=list">{$l->s('scenarios')}</a>&nbsp;]</td>
    <td>[&nbsp;<a href="?part=user&method=list">{$l->s('players')}</a>&nbsp;]</td>
    <td>[&nbsp;<a href="?part=clan&method=list">{$l->s('clans')}</a>&nbsp;]</td>
    {if $user_logged_in}<td>[&nbsp;<a href="?part=user&method=edit">{$l->s('profile')}</a>&nbsp;]</td>{/if}
    {if $user_logged_in}<td>[&nbsp;<a href="?part=user&method=logout">{$l->s('logout')}</a>&nbsp;]</td>
    {else}<td>[&nbsp;<a href="?part=user&method=login">{$l->s('login')}</a>&nbsp;]</td>{/if}
    <td>[&nbsp;<a href="/forum/{$l->get_current_language_code()}/board_show.pl?bid={if $l->get_current_language_code()=='de'}28{else}16{/if}">{$l->s('help')}</a>&nbsp;]</td>
    {if $user_is_admin}<td>[&nbsp;<a href="admin.php?part=log&method=list">{$l->s('admin')}</a>&nbsp;]</td>{/if}
</tr>
</table>
<div class="navbarbg">
    <div class="navbarbgleft"></div>
    <div class="navbarbgright"></div>
</div>

</div>