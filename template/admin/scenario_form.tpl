<form action="?part=scenario" method="post">
{if $edit_type=="edit"}
<input type="hidden" name="method" value="edit2">
{else}
<input type="hidden" name="method" value="add2">
{/if}

<table>
{foreach from=$languages item=language}
<tr>
  <td>
      {$l->s('name')} ({$language.name}):
  </td><td>
      <input type="text" name="scenario[name][{$language.id}]" value="{$scenario.name[$language.id]|escape}" size="25">
  </td>
</tr>
{/foreach}

<tr>
  <td>
      {$l->s('icon_number')}:
  </td><td>
      {section loop=45 name="icons"}
        <input type="radio" name="scenario[icon_number]" value="{$smarty.section.icons.iteration-1}"
        {if $smarty.section.icons.iteration-1==$scenario.icon_number} checked="checked"{/if}><img src="images/icons/scenarios/{$smarty.section.icons.iteration-1}.png">
      {/section}
  </td>
</tr>
<tr>

<tr>
    <td>{$l->s('game')}:</td>
    <td>
        <select name="scenario[product_id]" size="1">
            {foreach from=$products item=product}
                <option {if $scenario.product_id == $product.id}selected="selected"{/if} value="{$product.id}">{$product.name}</option>
            {/foreach}
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('active')}
    </td><td>
        <select name="scenario[active]" size="1">
            <option {if $scenario.active == 'Y'}selected="selected"{/if} value="Y">{$l->s('yes')}</option>
            <option {if $scenario.active == 'N'}selected="selected"{/if} value="N">{$l->s('no')}</option>
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('type')}
    </td><td>
        <select name="scenario[type]" size="1">
            <option {if $scenario.type == 'melee'}selected="selected"{/if} value="melee">{$l->s('melee')}</option>
            <option {if $scenario.type == 'team_melee'}selected="selected"{/if} value="team_melee">{$l->s('team_melee')}</option>
            <option {if $scenario.type == 'settle'}selected="selected"{/if} value="settle">{$l->s('settle')}</option>
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('settle_base_score')}
    </td><td>
        <input type="text" name="scenario[settle_base_score]" size="3" value="{$scenario.settle_base_score}">
    </td>
</tr>
<tr>
    <td>
      {$l->s('settle_time_bonus_score')}
    </td><td>
        <input type="text" name="scenario[settle_time_bonus_score]" size="3" value="{$scenario.settle_time_bonus_score}">
    </td>
</tr>

<tr><td><br></td><td><br></td></tr>

  <tr>
    <td>{$l->s('versions')}</td>
    <td>
       <table>
        <tr class="th">
            <th>{$l->s('version_date_created')}</th>
            <th>{$l->s('hash')}</th>
            <th>{$l->s('hash_sha')}</th>
            <th>{$l->s('filename')}</th>
            <th>{$l->s('author')}</th>
            <th>{$l->s('comment')}</th>
        </tr>
        {assign var="i" value="0"}
        {foreach from=$versions item="version"}

          <tr>
              <td><input type="hidden" name="versions[{$i}][date_created]" value="{$version.date_created}">{$version.date_created|date_format:"%d.%m.%Y"}</td>
              <td><input type="text" name="versions[{$i}][hash]" value="{$version.hash|escape}" size="32"></td>
              <td><input type="text" name="versions[{$i}][hash_sha]" value="{$version.hash_sha|escape}" size="40"></td>
              <td><input type="text" name="versions[{$i}][filename]" value="{$version.filename|escape}" size="32"></td>
              <td><input type="text" name="versions[{$i}][author]" value="{$version.author|escape}" size="32"></td>
              <td><input type="text" name="versions[{$i}][comment]" value="{$version.comment|escape}" size="32"></td>
          </tr>
          {assign var="i" value=$i+1}
        {/foreach}
        {section loop=2 name="versions"}
          <tr>
              <td></td>
              <td><input type="text" name="versions[{$i}][hash]" value="" size="32"></td>
              <td><input type="text" name="versions[{$i}][hash_sha]" value="" size="40"></td>
              <td><input type="text" name="versions[{$i}][filename]" value="" size="32"></td>
              <td><input type="text" name="versions[{$i}][author]" value="" size="32"></td>
              <td><input type="text" name="versions[{$i}][comment]" value="" size="32"></td>
          </tr>
          {assign var="i" value=$i+1}
        {/section}
       </table>
    </td>
  </tr>
  
  <tr>
    <td>{$l->s('leagues')}</td>
    <td>
       <table>
        <tr class="th">
            <th>{$l->s('name')}</th>
            <th>{$l->s('allowed')}</th>
            <th>{$l->s('max_player_count')}</th>
        </tr>
        {assign var="i" value="0"}
        {foreach from=$leagues item="league"}

          <tr>
              <td {if $league.scenario_restriction=='Y'}style="font-weight:bold;"{/if}>{$league.name}</td>
              <td><input type="checkbox" name="leagues[{$league.id}][checked]" value="1" {if $league.active}checked="checked"{/if}></td>
              <td><input type="text" name="leagues[{$league.id}][max_player_count]" value="{$league.max_player_count}" size="3"></td>
          </tr>
        {/foreach}
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
                    <option value="{$scen.id}">{$scen.id}: {$scen.name}</option>
                {/if}
            {/foreach}
          </select>
      </td>
    </tr>
{/if}

<tr><td><br></td><td><br></td></tr>

<tr><td></br></td></tr>
    <td>
      {if $edit_type=="edit"}
          <input type="hidden" name="scenario[id]" value="{$scenario.id}">
      {/if}
    <input type="submit" value="{$l->s('save')}">
      </td><td>
        {if $edit_type=="edit"}
        </form>
     </td></tr><tr><td>
        <form action="?part=scenario" method="post">
            <input type="hidden" name="method" value="delete2">
            <input type="hidden" name="scenario[id]" value="{$scenario.id}">
            <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
        {/if}
    </td>
</tr>

</table>

</form>