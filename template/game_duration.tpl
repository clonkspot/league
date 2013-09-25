
{assign var="duration_timeout" value=180}
{if $game.is_paused || $game.status != "running"}
    {assign var="duration" value=$game.duration}
{else}
    {if $smarty.now-$game.date_last_update<$duration_timeout}
        {assign var="duration" value=$game.duration+$smarty.now-$game.date_last_update}
    {else}
        {assign var="duration" value=$game.duration+$duration_timeout}
    {/if}
{/if}
{*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
{assign var="hours" value=$duration/3600}{assign var="hours" value=$hours|string_format:"%d"}
{assign var="minutes" value=$duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
{assign var="seconds" value=$duration-$hours*3600-$minutes*60}{assign var="seconds" value=$seconds|string_format:"%02d"}
{$hours}:{$minutes}:{$seconds}
{if !$game.is_paused && $game.status == "running" && ($smarty.now-$game.date_last_update)>$duration_timeout}[?]{/if}