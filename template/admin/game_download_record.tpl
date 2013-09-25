<br>
<b><a href="records/{$game.record_filename}">
{if 'incomplete'==$game.record_status}
{$l->s('download_record_incomplete')}
{elseif 'complete'==$game.record_status}
{$l->s('download_record')}
{/if}
</a></b>