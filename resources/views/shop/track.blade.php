@php
    use App\Enums\OrderStatus;
    $steps = OrderStatus::timeline();
    $cancelled = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded]);
    $current = array_search($order->status, $steps, true);
    $reached = $order->histories->pluck('created_at', 'status.value');
@endphp
<x-layouts.shop :title="'Commande '.$order->number.' — Noza Retail'">
    <div class="wrap pt-10 lg:pt-14">
        <p class="text-sm text-graphite">Commande {{ $order->number }} · {{ $order->created_at->translatedFormat('d F Y à H:i') }}</p>
        <h1 class="display text-[clamp(2.2rem,5.5vw,4rem)] mt-1 max-w-3xl">
            @if(session('paid')) Paiement reçu, merci {{ strtok($order->customer_name, ' ') }}.
            @elseif($cancelled) Cette commande est {{ mb_strtolower($order->status->getLabel()) }}.
            @elseif($order->status === OrderStatus::Delivered) Votre commande a été livrée.
            @elseif($order->status === OrderStatus::Shipped) Votre commande est en route.
            @else Merci, votre commande est enregistrée.
            @endif
        </h1>

        @if(! $cancelled)
        {{-- Frise de suivi --}}
        <ol class="mt-10 grid grid-cols-5 relative" aria-label="Avancement de la commande">
            <div class="absolute top-[11px] left-[10%] right-[10%] h-px bg-line" aria-hidden="true"></div>
            <div class="absolute top-[11px] left-[10%] h-px bg-ink transition-all" style="width: {{ $current === false ? 0 : $current * 20 }}%" aria-hidden="true"></div>
            @foreach($steps as $k => $s)
                @php $done = $current !== false && $k <= $current; @endphp
                <li class="relative flex flex-col items-center text-center px-1">
                    <span class="size-[23px] rounded-full border-2 grid place-items-center bg-paper {{ $done ? 'border-ink bg-ink text-paper' : 'border-line' }}">
                        @if($done)<x-nz-icon name="check" class="size-3" />@endif
                    </span>
                    <span class="mt-2 text-xs sm:text-sm {{ $done ? '' : 'text-graphite' }}">{{ $s->getLabel() }}</span>
                    @if($reached->get($s->value))<span class="text-[11px] text-graphite num hidden sm:block">{{ \Illuminate\Support\Carbon::parse($reached->get($s->value))->format('d/m H:i') }}</span>@endif
                </li>
            @endforeach
        </ol>
        @endif

        <div class="mt-12 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-7">
                @if($order->payment_method->value === 'mobile_money' && $order->payment_status->value !== 'paid' && ! $cancelled)
                    <div class="mb-8 p-5 border border-ink flex flex-wrap items-center justify-between gap-4">
                        <p>Le paiement n'est pas encore reçu.</p>
                        <a href="{{ route('payment.show', $order) }}" class="btn-ink btn-sm">Payer maintenant</a>
                    </div>
                @endif
                <h2 class="font-medium text-lg">Articles</h2>
                <ul class="mt-3 divide-y divide-line border-y border-line">
                    @foreach($order->items as $i)
                        <li class="py-4 flex justify-between gap-4">
                            <div><p>{{ $i->name }}</p><p class="text-sm text-graphite">@if($i->variant_label){{ $i->variant_label }} · @endif{{ $i->quantity }} × {{ money($i->unit_price) }}</p></div>
                            <p class="num">{{ money($i->total) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2 text-[15px] num max-w-sm ml-auto">
                    <div class="flex justify-between"><dt class="text-graphite">Sous-total</dt><dd>{{ money($order->subtotal) }}</dd></div>
                    @if($order->discount)<div class="flex justify-between text-sanaga"><dt>Réduction {{ $order->coupon_code }}</dt><dd>-{{ money($order->discount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-graphite">Livraison</dt><dd>{{ $order->shipping_fee ? money($order->shipping_fee) : 'Offerte' }}</dd></div>
                    <div class="flex justify-between font-semibold text-lg pt-2 border-t border-line"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div>
                </dl>
            </div>
            <aside class="lg:col-span-5 space-y-6">
                <div class="bg-mist p-6 space-y-4 text-sm">
                    <div><p class="text-graphite">Livraison</p><p class="mt-1">{{ $order->customer_name }}<br>{{ $order->district }}, {{ $order->city }}@if($order->landmark)<br>{{ $order->landmark }}@endif<br>{{ $order->customer_phone }}</p>
                        @if($order->shippingZone)<p class="mt-1 text-graphite">Délai : {{ $order->shippingZone->delivery_delay }}</p>@endif</div>
                    <div><p class="text-graphite">Paiement</p><p class="mt-1">{{ $order->payment_method->getLabel() }} · {{ $order->payment_status->getLabel() }}</p></div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('orders.invoice', [$order->number, $order->tracking_token]) }}" target="_blank" class="btn-line btn-sm"><x-nz-icon name="printer" class="size-4" /> Facture</a>
                    <a href="https://wa.me/{{ setting('whatsapp') }}?text={{ rawurlencode('Bonjour, une question sur ma commande '.$order->number) }}" target="_blank" rel="noopener" class="btn-line btn-sm"><x-nz-icon name="whatsapp" class="size-4" /> Une question ?</a>
                </div>
                <p class="text-xs text-graphite">Gardez ce lien : il vous permet de suivre votre commande sans compte.</p>
            </aside>
        </div>
    </div>
</x-layouts.shop>
