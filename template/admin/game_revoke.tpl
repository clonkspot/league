<br><br>
<form action="index.php" method="post">
    <input type="hidden" name="part" value="game">
    <input type="hidden" name="method" value="revoke">
    <input type="hidden" name="game[id]" value="{$game.id}">
    <input type="submit" onClick="return confirm('{$l->s('delete_confirm')}')" value="{$l->s('game_revoke')}">
</form>