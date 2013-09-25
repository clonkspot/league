<br>
{$l->s('statistics')}:
<br>
<form action="?part=log&method=reset_statistics2" method="post">
    <b>{$l->s('revision')}:</b> <input type="text" value="" name="revision" size="5">
    <input onClick="return confirm('{$l->s('reset_confirm')}')" type="submit" value="{$l->s('reset')}">
</form>