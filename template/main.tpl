<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head>
    <meta http-equiv='content-type' content='text/html; charset=iso-8859-1'>
    <title>Clonk Liga</title>
    <link rel="stylesheet" type="text/css" href="league.css">
    {literal}<!--[if lt IE 7]>
    <style type='text/css'>
      div#wrapper { float: left; width: 100%; }
      div#navbar { float: left; margin-left: -100%; }
    </style>
    <![endif]-->{/literal}
</head>
<body>

{fetch file='../www/header/header.html'}

<div id='wrapper'>
<div id='content'>

{include file='header.tpl'}

{include file='message_box.tpl'}

{if 'scenario'==$part}
    {if 'list'==$method || 'toggle_league'==$method}
        {include file='scenario_list.tpl'}
    {/if}
{elseif 'league'==$part}
    {if 'list'==$method}
        {include file='league_list.tpl'}
    {else if 'ranking'==$method}
        {include file='league_ranking.tpl'}
    {/if}
{elseif 'game'==$part}
    {if 'list'==$method}
        {include file='game_list.tpl'}
    {else if 'details'==$method}
        {include file='game_details.tpl'}
    {/if}
{elseif 'clan'==$part}
    {if 'list'==$method}
        {include file='clan_list.tpl'}
    {elseif 'details'==$method}
        {include file='clan_details.tpl'}
    {elseif 'add'==$method}
        {include file='clan_add_edit.tpl'}
    {elseif 'add2'==$method}
        {include file='clan_list.tpl'}
    {elseif 'edit'==$method}
        {include file='clan_add_edit.tpl'}
    {elseif 'edit2'==$method}
        {include file='clan_add_edit.tpl'}
    {elseif 'delete2'==$method}
        {include file='clan_list.tpl'}
    {elseif 'join'==$method}
        {include file='clan_list.tpl'}
    {elseif 'leave'==$method}
        {include file='clan_list.tpl'}
    {elseif 'kick'==$method}
        {include file='clan_list.tpl'}
    {elseif 'transfer_founder'==$method}
        {include file='clan_list.tpl'}
    {/if}
{elseif 'user'==$part}
    {if 'details'==$method || 'set_score'==$method}
        {include file='user_details.tpl'}
    {elseif 'list'==$method}
        {include file='user_list.tpl'}
    {elseif 'login'==$method || !$user_logged_in}
        {include file='login.tpl'}
    {elseif 'edit'==$method || 'suicide'==$method}
    	<div>
        {include file='user_details.tpl'}
        {include file='user_edit.tpl'}
        </div>
    {elseif 'edit2'==$method}
        {include file='user_edit.tpl'}
    {/if}
{elseif 'login'==$part}
    {if 'error' == $method}
        {include file='login.tpl'}
    {elseif 'new_user' == $method}
        {include file='login.tpl' new_user=1}
    {/if}
{/if}

</div>
</div>

<div id='navbar'>
<div class='languages'>
    [ <a href='/league2/index.php?lang=de'><img src='/deco/dco_de.gif' alt='Deutsch'></a>
    | <a href='/league2/index.php?lang=en'><img src='/deco/dco_en.gif' alt='English'></a> ]
</div>
</div>

{fetch file='../www/header/botter.html'}

</body>
</html>
