<table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
    <tr>
        <!-- Logo shop -->
        <td style="width: 60%; text-align: left; vertical-align: top; margin-top: 8px;">
            {if isset($data.shop_logo)}
                <div style="text-align: left; margin-top: 16px;">
                    <img src="{$data.shop_logo}" style="max-width: 90%; max-height: 70px; display: block; margin: 16px auto;" />
                </div>
            {/if}
        </td>
        <!-- Dati ordine -->
        <td style="width: 40%; vertical-align: top; text-align: center;">
            <div style="text-align: left; margin-bottom: 12px;">
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 2px;">
                    <span>{l s='Ordine:'}</span>
                    <br>
                    <span>{$data.order.id_order}</span> del <span>{$data.order.date_add|date_format:"%d/%m/%Y"}</span>
                </div>
                <div style="font-size: 13px; font-weight: bold; margin-bottom: 2px;">
                    <span>{l s='Stato corrente:'}</span>
                    <br>
                    <span> {$data.current_state.name} </span>
                    <br>
                    <span style="font-size: 11px;"> {$data.current_state.date|date_format:"%d/%m/%Y %H:%M"} </span>
                </div>
                <div style="font-size: 13px; font-weight: bold;">
                    <span>{l s='Tipo di Pagamento:'}</span>
                    <br>
                    <span> {$data.order.payment} </span>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- Linea divisoria -->
<div style="width: 100%; border-bottom: 1px solid #888; margin-bottom: 10px;"></div>

<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <!-- Indirizzo di spedizione -->
        <td style="width: 33%; vertical-align: top; font-size: 12px;">
            <div style="text-align: left; background-color: #f7f7f7; border: 1px solid #888; font-size: 12px; text-align: left; padding-left: 7px; padding-right: 8px; margin-bottom: 4px;">
                <span><strong>{l s='Indirizzo di spedizione'}</strong></span>
                <br>
                {include file="./address.tpl" address=$data.delivery_address state=$data.delivery_state country=$data.delivery_country}
            </div>
        </td>
        <!-- Indirizzo di fatturazione -->
        <td style="width: 33%; vertical-align: top; font-size: 12px;">
            <div style="text-align: left; background-color: #f7f7f7; border: 1px solid #888; font-size: 12px; text-align: left; padding-left: 7px; padding-right: 8px; margin-bottom: 4px;">
                <span><strong>{l s='Indirizzo di fatturazione'}</strong></span>
                <br>
                {include file="./address.tpl" address=$data.invoice_address state=$data.invoice_state country=$data.invoice_country}
            </div>
        </td>
        <!-- Dati cliente -->
        <td style="width: 34%; vertical-align: top; font-size: 12px;">
            <div style="text-align: center; background-color: #f7f7f7; border: 1px solid #888; font-size: 12px; text-align: left; padding-left: 7px; padding-right: 8px; margin-bottom: 4px; width: 100%; height: 100%;">
                <span><strong>{l s='Codice Cliente:'}</strong></span>
                <br>
                <span style="font-size: 16px;">{$data.customer.id_customer}</span>
                {if !$data.is_new_customer}
                    <span style="color: #c00; font-size: 16px; font-weight: bold; margin-left: 10px;">V</span>
                {/if}
                <br><br>

                <span><strong>{l s='Data Ordine:'}</strong></span>
                <br>
                <span style="font-size: 16px;">{$data.order.date_add|date_format:"%d/%m/%Y %H:%M"}</span>
                <br><br>

                <span><strong>{l s='Totale ordine:'}</strong></span>
                <br>
                <span style="font-size: 16px;">{$data.order.total_order_currency}</span>
                <br>
            </div>
        </td>
    </tr>
</table>