<tr style="background-color: {$rowColor}; border-bottom: 1px solid #ccc; padding: 8px;">
    <!-- Thumbnail -->
    <td rowspan="3" style="padding: 5px; text-align: center; border: 1px solid #e0e0e0; width: 48px;">
        {if $product.image_url}
            <img src="{$product.image_url}" style="width: 48px; height: auto;" />
        {else}
            <span style="color: #bbb;">-</span>
        {/if}
    </td>
    <!-- Riferimento -->
    <td style="border: 1px solid #e0e0e0; width: 140px; margin: 8px;">{$product.reference}</td>
    <!-- Nome prodotto -->
    <td style="border: 1px solid #e0e0e0; width: 220px; font-size: 10px; margin: 8px;">{$product.product_name}</td>
    <!-- Quantità -->
    <td style="text-align: right; border: 1px solid #e0e0e0; width: 64px; margin: 8px;">{$product.product_quantity}</td>
    <!-- Prezzo -->
    <td style="text-align: right; border: 1px solid #e0e0e0; width: 75px; margin: 8px;">{$product.price_currency}</td>
</tr>
<tr style="background-color: {$rowColor};">
    <!-- Locazione -->
    <td style="font-size: 10px; color: #666; border: 1px solid #e0e0e0; margin: 8px;">{$product.location}</td>
    <!-- Combinazione -->
    <td style="font-size: 10px; color: #666; border: 1px solid #e0e0e0; margin: 8px;">{$product.combination}</td>
    <!-- Stock Service -->
    <td style="font-size: 10px; color: #3581b4; text-align: right; border: 1px solid #e0e0e0; margin: 8px;"><strong>{$product.stock_service}</strong></td>
    <!-- Sconto -->
    <td style="font-size: 10px; color: #c00; text-align: right; border: 1px solid #e0e0e0; margin: 8px;">
        {if $product.reduction_percent}
            ({$product.reduction_percent} %)
        {/if}
    </td>
</tr>
<tr style="background-color: {$rowColor};">
    <!-- Data verifica, colspan 4 -->
    <td colspan="4" style="font-size: 10px; color: #3581b4; border: 1px solid #e0e0e0; text-align: right; margin: 8px auto;">
        {if $product.check_date}
            <span style="font-weight: bold;">{l s='Data verifica:'}</span> {$product.check_date|date_format:'%d/%m/%Y %H:%M'}
        {/if}
    </td>
</tr>