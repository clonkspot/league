{$l->s('login')}:
</br></br>

<form action="?part=login&method=login" method="post">
{$l->s('name')}: <input type="text" name="login_name" value="" size="32">
</br>
{$l->s('password')}: <input type="password" name="login_password" value="" size="32">
</br>
<input type="submit" value="{$l->s('login')}">
</form>