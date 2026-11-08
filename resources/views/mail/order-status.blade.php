<!doctype html>
<html lang="fr">
<body style="margin:0;background:#f2f2f0;font-family:Helvetica,Arial,sans-serif;color:#000">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;max-width:560px">
    <tr><td style="background:#000;padding:22px 28px;color:#fff;font-family:Georgia,serif;font-size:22px;letter-spacing:.04em">NOZA RETAIL</td></tr>
    <tr><td style="padding:28px">
        <p style="margin:0 0 6px;color:#555;font-size:14px">Bonjour {{ $order->customer_name }},</p>
        <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-weight:normal;font-size:26px">
            @switch($order->status->value)
                @case('pending') Nous avons bien reçu votre commande. @break
                @case('confirmed') Votre commande est confirmée. @break
                @case('processing') Votre commande est en préparation. @break
                @case('shipped') Votre commande est en route. @break
                @case('delivered') Votre commande a été livrée. @break
                @default Votre commande est {{ mb_strtolower($order->status->getLabel()) }}.
            @endswitch
        </h1>
        <p style="font-size:14px;color:#555;margin:0 0 20px">Commande <strong style="color:#000">{{ $order->number }}</strong> · {{ $order->payment_method->getLabel() }}</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid #e5e5e5">
            @foreach($order->items as $item)
            <tr><td style="padding:10px 0;border-bottom:1px solid #e5e5e5">{{ $item->quantity }} × {{ $item->name }}@if($item->variant_label) <span style="color:#777">({{ $item->variant_label }})</span>@endif</td>
                <td align="right" style="padding:10px 0;border-bottom:1px solid #e5e5e5">{{ money($item->total) }}</td></tr>
            @endforeach
            <tr><td style="padding:8px 0;color:#555">Livraison</td><td align="right">{{ money($order->shipping_fee) }}</td></tr>
            @if($order->discount)<tr><td style="padding:8px 0;color:#555">Réduction</td><td align="right">-{{ money($order->discount) }}</td></tr>@endif
            <tr><td style="padding:8px 0;font-weight:bold">Total</td><td align="right" style="font-weight:bold">{{ money($order->total) }}</td></tr>
        </table>
        <p style="margin:24px 0 0"><a href="{{ route('orders.track', [$order->number, $order->tracking_token]) }}" style="background:#000;color:#fff;padding:12px 20px;text-decoration:none;display:inline-block;font-size:14px">Suivre ma commande</a></p>
    </td></tr>
    <tr><td style="padding:18px 28px;font-size:12px;color:#777;border-top:1px solid #eee">{{ setting('store_name') }} · {{ setting('phone') }} · {{ setting('address') }}</td></tr>
</table>
</td></tr></table>
</body>
</html>
