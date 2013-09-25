{if $message_box->get_message_count() > 0}
  <div class="messagebox">
  {foreach from=$message_box->get_messages() item="message" name="messages"}
      {if $message.type == 'error'}
          {$l->s('error')}:
      {elseif $message.type == 'info'}
          {$l->s('info')}:
      {/if}
      {$message.text|escape}
  {/foreach}
  </div>
{/if}