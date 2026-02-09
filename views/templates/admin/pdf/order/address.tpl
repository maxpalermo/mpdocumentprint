<div style="text-align: left; font-size: 10px;">
    {if $address.company}
        <strong>{$address.company}</strong><br>
    {/if}
    <strong>{$address.firstname} {$address.lastname}</strong><br>
    {$address.address1}<br>
    {$address.address2}<br>
    {$address.postcode} {$address.city} {if isset($state.iso_code)}{$state.iso_code}{/if} <strong>{$country.iso_code}</strong>
</div>