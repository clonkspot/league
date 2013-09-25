{if $edit_type=="edit"}
    {assign var=headline value=$l->s('edit')}
{else}
    {assign var=headline value=$l->s('add')}
{/if}
{$l->s('resource')} {$headline}

{include file="resource_form.tpl"}