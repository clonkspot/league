{foreach from=$lang_codes item=lang}
    <div class="language-section">
        <h2>Language: {$lang|upper}</h2>

        <h3>Current MOTDs:</h3>
        {if $motds[$lang]|default:array()|@count > 0}
            {foreach from=$motds[$lang]|default:array() item=motd}
                <div class="motd-item">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="method" value="remove">
                        <input type="hidden" name="language" value="{$lang}">
                        <input type="hidden" name="motd" value="{$motd|escape}">
                        <span>{$motd|escape}</span>
                        <button type="submit">Remove</button>
                    </form>
                </div>
            {/foreach}
        {else}
            <p>No MOTDs for this language.</p>
        {/if}

        <div class="add-form">
            <h3>Add New MOTD:</h3>
            <form method="post">
                <input type="hidden" name="method" value="add">
                <input type="hidden" name="language" value="{$lang}">
                <input type="text" name="motd" placeholder="Enter new MOTD" required>
                <button type="submit">Add MOTD</button>
            </form>
        </div>
    </div>
{/foreach}
