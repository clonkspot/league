<form action="?part=resource" method="post">
{if $edit_type=="edit"}
<input type="hidden" name="method" value="edit2">
<input type="hidden" name="old_hash" value="{$resource.hash}">
{else}
<input type="hidden" name="method" value="add2">
{/if}

<table>

<tr>
    <td>{$l->s('filename')}:</td>
    <td>
        <input type="text" name="resource[filename]" value="{$resource.filename}" size="50">
    </td>
</tr>
<tr>
    <td>{$l->s('hash_sha')}:</td>
    <td>
        <input type="text" name="resource[hash]" value="{$resource.hash}" size="40">
    </td>
</tr>


<tr><td></br></td></tr>
    <td>
    <input type="submit" value="{$l->s('save')}">
      </td><td>
        {if $edit_type=="edit"}
        </form>
     </td></tr><tr><td>
        <form action="?part=resource" method="post">
            <input type="hidden" name="method" value="delete2">
            <input type="hidden" name="resource[hash]" value="{$resource.hash}">
            <input onClick="return confirm('{$l->s('delete_confirm')}')" type="submit" value="{$l->s('delete')}">
        </form>
        {/if}
    </td>
</tr>
</table>

</form>