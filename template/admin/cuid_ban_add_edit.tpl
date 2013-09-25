{if $edit_type=="edit"}
    {assign var=headline value=$l->s('edit')}
{else}
    {assign var=headline value=$l->s('add')}
{/if}
{$l->s('cuid_ban')} {$headline}

{include file="cuid_ban_form.tpl"}