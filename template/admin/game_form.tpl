<form action="?part=game" method="post">
<input type="hidden" name="method" value="add2">

<table>
<tr>
    <td>{$l->s('game')}:</td>
    <td>
        <select name="game[product_id]" size="1">
            {foreach from=$products item=product}
                <option value="{$product.id}">{$product.name}</option>
            {/foreach}
        </select>
    </td>
</tr>
<tr>
    <td>{$l->s('leagues')}:</td>
    <td>
        <select name="leagues[id][]" size="5" MULTIPLE>
            {foreach from=$leagues item=league}
                <option value="{$league.id}">{$league.name}</option>
            {/foreach}
        </select>
    </td>
</tr>
<tr>
    <td>{$l->s('scenario')}:</td>
    <td>
        {*<select name="game[scenario_id]" size="1">
            {foreach from=$scenarios item=scenario}
                <option value="{$scenario.id}">{$scenario.name}</option>
            {/foreach}
        </select>*}
        <input type="text" name="game[scenario_id]" size="6" value="{$scenario.id}">
    </td>
</tr>

<tr>
    <td>
      {$l->s('duration')}:
    </td><td>
        <input type="text" name="game[duration]" size="5" value="300">
    </td>
</tr>
<tr>
    <td>
      {$l->s('frame')}:
    </td><td>
        <input type="text" name="game[frame]" size="6" value="5000">
    </td>
</tr>
<tr>
    <td>
      {$l->s('date_start')} ({$l->s('date_us_format_info')}):
    </td><td>
        {assign var="date_created" value=$smarty.now-300}
        <input type="text" name="game[date_created]" size="32" value="{$date_created|date_format:"%Y-%m-%d %H:%M"}">
    </td>
</tr>


<tr><td><br></td><td><br></td></tr>

  <tr>
    <td>{$l->s('players')}</td>
    <td>
       <table>
        <tr class="th">
        {section name="clans" loop=5}
            <th>{$l->s('clan')} {$smarty.section.clans.index}</th>
        {/section}
        </tr>
        {section loop=8 name="players"}
          <tr>
              {section name="clans" loop=5}
                   <td>
                      <input name="players[{$smarty.section.clans.index}][{$smarty.section.players.index}][id]" size="5" value=""></input>
                      <input name="players[{$smarty.section.clans.index}][{$smarty.section.players.index}][performance]" size="5" value="0"></input>
                      {*<select name="players[{$smarty.section.clans.index}][{$smarty.section.players.index}][id]" size="1">
                           <option value="0">-</option>
                          {foreach from=$users item=player}
                              <option value="{$player.id}">{$player.name}</option>
                          {/foreach}
                      </select>*}
                      <select name="players[{$smarty.section.clans.index}][{$smarty.section.players.index}][status]" size="1">
                        <option value="lost">lost</option>
                        <option value="won">won</option>
                      </select>
                   </td>
              {/section}
          </tr>
        {/section}
       </table>
    </td>
  </tr>

{if $edit_type=="edit"}
  <tr><td><br></td><td><br></td></tr>
  
    <tr>
      <td>{$l->s('merge')}</td>
      <td>
          <select multiple size="20" name="scenarios_merge[]">
            {foreach from=$scenarios item="scen"}
                {if $scen.id != $scenario.id}
                    <option value="{$scen.id}">{$scen.name}</option>
                {/if}
            {/foreach}
          </select>
      </td>
    </tr>
{/if}

<tr><td><br></td><td><br></td></tr>

<tr><td></br></td></tr>
    <td>
    <input type="submit" value="{$l->s('save')}">
      </td><td>
    </td>
</tr>

</table>

</form>