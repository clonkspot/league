<form action="?part=league" method="post">
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
      <input type="text" name="league[name][{$language.id}]" value="{$league.name[$language.id]|escape}" size="25">
  </td>
</tr>
{/foreach}

{foreach from=$languages item=language}
<tr>
  <td>
      {$l->s('description')} ({$language.name}):
  </td><td>
      <input type="text" name="league[description][{$language.id}]" value="{$league.description[$language.id]|escape}" size="250">
  </td>
</tr>
{/foreach}

<tr>
    <td>{$l->s('game')}:</td>
    <td>
        <select name="league[product_id]" size="1">
            {foreach from=$products item=product}
                <option {if $league.product_id == $product.id}selected="selected"{/if} value="{$product.id}">{$product.name}</option>
            {/foreach}
        </select>
    </td>
</tr>

<tr>
    <td>
      {$l->s('type')}
    </td><td>
        <select name="league[type]" size="1">
            <option {if $league.type == 'melee'}selected="selected"{/if} value="melee">{$l->s('melee')}</option>
            <option {if $league.type == 'settle'}selected="selected"{/if} value="settle">{$l->s('settle')}</option>
        </select>
    </td>
</tr>
<tr>
  <td>
      {$l->s('date_start')} ({$l->s('date_us_format_info')}):
  </td><td>
      <input type="text" name="league[date_start]" value="{$league.date_start}" size="32">
  </td>
</tr>
<tr>
  <td>
      {$l->s('date_end')} ({$l->s('date_us_format_info')}):
  </td><td>
      <input type="text" name="league[date_end]" value="{$league.date_end}" size="32">
  </td>
</tr>
<tr>
  <td>
      {$l->s('ranking_timeout')}:
  </td><td>
      <input type="text" name="league[ranking_timeout]" value="{$league.ranking_timeout}" size="5">
  </td>
</tr>
<tr>
  <td>
      {$l->s('score_decay')}:
  </td><td>
      <input type="text" name="league[score_decay]" value="{$league.score_decay}" size="5">
  </td>
</tr>
<tr>
  <td>
      {$l->s('bonus_max')}:
  </td><td>
      <input type="text" name="league[bonus_max]" value="{$league.bonus_max}" size="5">
  </td>
</tr>
<tr>
  <td>
      {$l->s('bonus_account_max')}:
  </td><td>
      <input type="text" name="league[bonus_account_max]" value="{$league.bonus_account_max}" size="5">
  </td>
</tr>
<tr>
    <td>
      {$l->s('recurrent')}:
    </td><td>
        <select name="league[recurrent]" size="1">
            <option {if $league.recurrent == 'Y'}selected="selected"{/if} value="Y">{$l->s('yes')}</option>
            <option {if $league.recurrent == 'N'}selected="selected"{/if} value="N">{$l->s('no')}</option>
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('open_league')}:
    </td><td>
        <select name="league[scenario_restriction]" size="1">
            <option {if $league.scenario_restriction == 'Y'}selected="selected"{/if} value="Y">{$l->s('no')}</option>
            <option {if $league.scenario_restriction == 'N'}selected="selected"{/if} value="N">{$l->s('yes')}</option>
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('custom_scoring')}:
    </td><td>
        <select name="league[custom_scoring]" size="1">
            <option {if $league.custom_scoring == 'Y'}selected="selected"{/if} value="Y">{$l->s('yes')}</option>
            <option {if $league.custom_scoring == 'N'}selected="selected"{/if} value="N">{$l->s('no')}</option>
        </select>
    </td>
</tr>
<tr>
    <td>
      {$l->s('scenarios')}:
    </td><td>
        {foreach from=$scenarios item="scenario" name="scenarios"}
          <a href="?part=scenario&method=edit&scenario[id]={$scenario.id}">{$scenario.name}</a>{if !$smarty.foreach.scenarios.last},{/if}
        {/foreach}
    </td>
</tr>
<tr>
  <td>
      {$l->s('icon_path')}:
  </td><td>
      <input type="text" name="league[icon]" value="{$league.icon}" size="50">
  </td>
</tr>
<tr>
<tr>
  <td>
      {$l->s('trophies_paths')}:
  </td><td>
      <input type="text" name="league[trophies]" value="{$league.trophies}" size="50">
  </td>
</tr>
<tr>
  <td>
      {$l->s('filter_icon_on')}:
  </td><td>
      <input type="text" name="league[filter_icon_on]" value="{$league.filter_icon_on}" size="50">
  </td>
</tr>
<tr>
  <td>
      {$l->s('filter_icon_off')}:
  </td><td>
      <input type="text" name="league[filter_icon_off]" value="{$league.filter_icon_off}" size="50">
  </td>
</tr>
<tr>
  <td>
      {$l->s('priority')}:
  </td><td>
      <input type="text" name="league[priority]" value="{$league.priority}" size="3">
  </td>
</tr>
<tr>

<tr><td></br></td></tr>
    <td>
      {if $edit_type=="edit"}
          <input type="hidden" name="league[id]" value="{$league.id}">
      {/if}
    <input type="submit" value="{$l->s('save')}">
      </td><td>
        {if $edit_type=="edit"}
        </form>
     </td></tr><tr><td>
        <form action="?part=league" method="post">
            <input type="hidden" name="method" value="delete2">
            <input type="hidden" name="league[id]" value="{$league.id}">
            <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
        </form>
        {/if}
    </td>
</tr>
</form>
<tr><td></br></td></tr>
<tr><td></br></td></tr>
<tr><td>
    <form method="post" action="?part=league">
        <input type="hidden" name="method" value="calculate_ranks2">
        <input type="hidden" name="league[id]" value="{$league.id}">
        <input type="submit" value="{$l->s('calculate_ranks')}">
    </form>
    </td></tr>
{if $league.type=='settle'}
<tr><td></br></td></tr>
<tr><td>
    <form method="post" action="?part=league">
        <input type="hidden" name="method" value="calculate_scores2">
        <input type="hidden" name="league[id]" value="{$league.id}">
        <input type="submit" value="{$l->s('calculate_scores')}">
    </form>
    <form method="post" action="?part=league">
        <input type="hidden" name="method" value="restore_all_player_scores">
        <input type="hidden" name="league[id]" value="{$league.id}">
        <input type="submit" value="{$l->s('restore_all_player_scores')}">
    </form>
    </td></tr>
{/if}
</table>

