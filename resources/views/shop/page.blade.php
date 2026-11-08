@php
$titles = ['livraison' => 'Livraison', 'retours' => 'Retours et échanges', 'paiement' => 'Paiement', 'contact' => 'Nous contacter', 'conditions' => 'Conditions générales de vente'];
@endphp
<x-layouts.shop :title="$titles[$page].' — Noza Retail'">
    <div class="wrap pt-12 lg:pt-16 grid lg:grid-cols-12 gap-10">
        <nav class="lg:col-span-3 flex lg:flex-col gap-1 overflow-x-auto no-scrollbar text-[15px]" aria-label="Aide">
            @foreach($titles as $k => $t)
                <a href="{{ route('page', $k) }}" class="whitespace-nowrap px-3 py-2 rounded-[4px] {{ $page === $k ? 'bg-ink text-paper' : 'hover:bg-mist' }}">{{ $t }}</a>
            @endforeach
        </nav>
        <article class="lg:col-span-7">
            <h1 class="display text-[clamp(2.2rem,5vw,3.6rem)]">{{ $titles[$page] }}</h1>
            <div class="prose-noza mt-6">
            @switch($page)
                @case('livraison')
                    <p>Nous livrons partout au Cameroun. Le livreur vous appelle avant de passer : gardez votre téléphone à portée de main.</p>
                    <table class="w-full text-sm my-6 border-t border-line">
                        <thead><tr class="text-left text-ink"><th class="py-3 font-medium">Zone</th><th class="py-3 font-medium">Délai</th><th class="py-3 font-medium">Frais</th><th class="py-3 font-medium">Offerte dès</th></tr></thead>
                        <tbody>
                        @foreach($zones as $z)
                            <tr class="border-t border-line"><td class="py-3 pr-3">{{ $z->name }}<br><span class="text-xs">{{ collect($z->cities)->reject(fn($c) => $c === '*')->join(', ') }}</span></td><td class="py-3 pr-3">{{ $z->delivery_delay }}</td><td class="py-3 pr-3 num">{{ money($z->fee) }}</td><td class="py-3 num">{{ $z->free_above ? money($z->free_above) : '—' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p>Hors Douala et Yaoundé, les colis voyagent par agence partenaire ; vous les retirez à l'agence de votre ville avec votre pièce d'identité.</p>
                    @break
                @case('retours')
                    <p>Vous disposez de 7 jours après réception pour retourner ou échanger un article non porté, non utilisé et dans son emballage d'origine.</p>
                    <ol><li>Écrivez-nous sur WhatsApp avec votre numéro de commande.</li><li>Nous organisons l'enlèvement à Douala et Yaoundé, ou vous déposez le colis en agence.</li><li>Le remboursement part sur votre compte Mobile Money sous 72 h après contrôle.</li></ol>
                    <p>Les produits d'hygiène et de beauté ouverts ne peuvent pas être repris.</p>
                    @break
                @case('paiement')
                    <p><strong>Mobile Money</strong> — MTN MoMo et Orange Money. Au moment de payer, une demande s'affiche sur votre téléphone : vous la validez avec votre code secret. Nous ne voyons jamais ce code.</p>
                    <p><strong>Paiement à la livraison</strong> — à Douala et Yaoundé, vous réglez le livreur en espèces ou par Mobile Money en recevant le colis.</p>
                    <p>Une commande Mobile Money non payée sous 2 heures est annulée automatiquement pour libérer le stock.</p>
                    @break
                @case('contact')
                    <p>Notre équipe répond du lundi au samedi, de 8 h à 19 h.</p>
                    <ul><li>WhatsApp : <a href="https://wa.me/{{ setting('whatsapp') }}">{{ setting('phone') }}</a></li><li>E-mail : <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li><li>Showroom : {{ setting('address') }}</li></ul>
                    @break
                @default
                    <p>Les présentes conditions régissent les ventes conclues sur {{ setting('store_name') }}. Elles sont à compléter par votre conseil juridique avant la mise en ligne : identité de l'entreprise (RCCM, NIU), prix et taxes, modalités de paiement et de livraison, droit de retour, garanties, données personnelles et règlement des litiges.</p>
            @endswitch
            </div>
        </article>
    </div>
</x-layouts.shop>
