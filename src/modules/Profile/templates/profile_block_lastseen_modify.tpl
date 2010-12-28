{if $savelastlogindate neq true}
<div class="z-errormsg">
    {gt text="Error! This block cannot be used because saving of a user's last log-in date has been disabled."}
</div>
{/if}

<div class="z-formrow">
    <label for="profile_block_amount">{gt text='Number of recent visitors to display'}</label>
    <input id="profile_block_amount" type="text" name="amount" value="{$amount|safehtml}" size="5" maxlength="20" />
</div>