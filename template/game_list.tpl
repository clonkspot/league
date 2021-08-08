<div class="filter">
  {*{include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="melee"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="noleague"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.type" value="" text=$l->s('leagues')}
  <img class="vrbar" src="{$base_path}images/vr_bar.gif">*}
  {foreach from=$leagues item=league}
      {include file="func_filter.tpl" link="{url part="game" method="list"}" name="league_id" value=$league.id text=$league.name icon_on=$league.filter_icon_on icon_off=$league.filter_icon_off}
  {/foreach}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="league_id" value="" text=$l->s('leagues')}
  <img class="vrbar" src="{$base_path}images/vr_bar.gif">
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="lobby"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="running"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="g.status" value="" text=$l->s('games')}
  <img class="vrbar" src="{$base_path}images/vr_bar.gif">
  {include file="func_search.tpl" link="{url part="game" method="list"}"}
  {include file="func_filter.tpl" link="{url part="game" method="list"}" name="search" value="" text=$l->s('search')}
</div>

{include file="func_header_line.tpl" func="games" text_array=$filter_text_array page_link="{url part="game" method="list"}"}

{assign var="show_settle_scores" value=0}
{if $games.0.scenario_type=='settle' &&
    $smarty.request.filter.scenario_id 
    || ($smarty.request.filter.league_id && $smarty.request.filter.user_name)}
    {assign var="show_settle_scores" value=1}
{/if}

<table>
        <tr class="th">
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="g.type" text=$l->s('leagues')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="g.status" text=$l->s('status')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="scenario_name" text=$l->s('scenario')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="date_created" text=$l->s('date_start')}
            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="duration" text=$l->s('duration')}
            
            {if $show_settle_scores}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_rank" text=$l->s('duration_equivalent')}
            {/if}

            {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="player_count" text=$l->s('players')}
            {if $show_settle_scores}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_rank" text=$l->s('rank')}
                {include file="func_tableheader.tpl" link="{url part="game" method="list"}" value="settle_score" text=$l->s('score')}
            {/if}
        </tr>
    {foreach item=game from=$games name="game"}
        <tr {if $game.is_revoked}class="revoked"{/if}>
            {$game.game_list_html}
            <td align="right">
                {include file="game_duration.tpl"}
            </td>
            
            {if $show_settle_scores}
              <td>
                {assign var="duration" value=$game.frame/36}
                {*hacked time myself because smarty-date_format returns 01:xx:xx if the hours should be 0...*}
                {assign var="hours" value=$duration/3600}{assign var="hours" value=$hours|string_format:"%d"}
                {assign var="minutes" value=$duration/60-$hours*60}{assign var="minutes" value=$minutes|string_format:"%02d"}
                {assign var="seconds" value=$duration-$hours*3600-$minutes*60}{assign var="seconds" value=$seconds|string_format:"%02d"}
                {$hours}:{$minutes}:{$seconds}
              </td>
            {/if}

            {$game.game_list_html_2}

            {if $show_settle_scores}
                <td>{if $game.settle_rank < 999999999 && $game.settle_rank > 0}{$game.settle_rank}{/if}</td>
                <td>{if $game.settle_score}{$game.settle_score}{/if}</td>
            {/if}
        </tr>
    {/foreach}
</table>
<script>
    /*
    This script is calculating the references by local browser's time. It's ES5-compliant.
    */
    /*
    for (let dateField of document.getElementsByClassName('date_field')) {
        if (dateField.textContent == null) continue;
        //u00a0 represents &nbsp;
        const inputArray = dateField.textContent.replace(/\u00a0-\u00a0/, '.').replace(' ', '').split(/[.:]/);
        //Date month is indexed -> 0 represents january. Converting offset to milliseconds.
        const date = new Date(new Date(20 + inputArray[2], inputArray[1] - 1, inputArray[0], inputArray[3], inputArray[4]).getTime() - new Date().getTimezoneOffset() * 60000);
        //Adding leading zero to month, day, hour and minute but only keeping the last two numbers
        dateField.textContent = ("0" + date.getDate()).slice(-2) + '.' + ("0" + (date.getMonth() + 1)).slice(-2) + '.' + date.getFullYear().toString().slice(2) + "\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2) + ':' + ("0" + date.getMinutes()).slice(-2);
    }
    */

    var __values = (this && this.__values) || function(o) {
        var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
        if (m) return m.call(o);
        if (o && typeof o.length === "number") return {
            next: function () {
                if (o && i >= o.length) o = void 0;
                return { value: o && o[i++], done: !o };
            }
        };
        throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
    };
    var e_1, _a;
    try {
        for (var _b = __values(document.getElementsByClassName('date_field')), _c = _b.next(); !_c.done; _c = _b.next()) {
            var dateField = _c.value;
            if (dateField.textContent == null)
                continue;
            //u00a0 represents &nbsp;
            var inputArray = dateField.textContent.replace(/\u00a0-\u00a0/, '.').replace(' ', '').split(/[.:]/);
            //Date month is indexed -> 0 represents january. Converting offset to milliseconds.
            var date = new Date(new Date(20 + inputArray[2], inputArray[1] - 1, inputArray[0], inputArray[3], inputArray[4]).getTime() - new Date().getTimezoneOffset() * 60000);
            //Adding leading zero to month, day, hour and minute but only keeping the last two numbers
            dateField.textContent = ("0" + date.getDate()).slice(-2) + '.' + ("0" + (date.getMonth() + 1)).slice(-2) + '.' + date.getFullYear().toString().slice(2) + "\u00a0-\u00a0" + ("0" + date.getHours()).slice(-2) + ':' + ("0" + date.getMinutes()).slice(-2);
        }
    }
    catch (e_1_1) { e_1 = { error: e_1_1 }; }
    finally {
        try {
            if (_c && !_c.done && (_a = _b.return)) _a.call(_b);
        }
        finally { if (e_1) throw e_1.error; }
    }
</script>
