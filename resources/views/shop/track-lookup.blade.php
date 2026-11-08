<x-layouts.shop title="Suivre une commande — Noza Retail">
    <div class="wrap pt-12 max-w-md">
        <h1 class="display text-4xl">Suivre une commande</h1>
        <p class="mt-2 text-graphite">Le numéro de commande figure dans l'e-mail ou le SMS de confirmation.</p>
        <form method="POST" action="{{ route('orders.lookup') }}" class="mt-8 space-y-4">
            @csrf
            <div><label class="label" for="number">Numéro de commande</label><input id="number" name="number" value="{{ old('number') }}" class="field uppercase" placeholder="NZ261108-XXXXX" required></div>
            <div><label class="label" for="tphone">Téléphone utilisé</label><input id="tphone" name="phone" type="tel" value="{{ old('phone') }}" class="field" required></div>
            @error('number')<p class="error">{{ $message }}</p>@enderror
            <button class="btn-ink w-full">Voir ma commande</button>
        </form>
    </div>
</x-layouts.shop>
