<x-layouts.shop title="Connexion — Noza Retail">
    <div class="wrap pt-12 lg:pt-20 grid lg:grid-cols-2 gap-12 max-w-5xl">
        <div>
            <h1 class="display text-5xl">Bon retour.</h1>
            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4 max-w-sm">
                @csrf
                <div><label class="label" for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="email" required autofocus>
                    @error('email')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="password">Mot de passe</label><input id="password" name="password" type="password" class="field" autocomplete="current-password" required></div>
                <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="remember" class="size-4 accent-ink" checked> Rester connecté</label>
                <button class="btn-ink w-full">Se connecter</button>
            </form>
        </div>
        <div class="bg-mist p-8 self-start">
            <p class="text-lg font-medium">Nouveau chez Noza ?</p>
            <ul class="mt-3 text-graphite text-sm space-y-2">
                <li>Vos adresses retenues pour commander en un clic</li>
                <li>L'historique et le suivi de toutes vos commandes</li>
                <li>Vos favoris, sur tous vos appareils</li>
            </ul>
            <a href="{{ route('register') }}" class="btn-line mt-6">Créer un compte</a>
            <p class="mt-4 text-sm text-graphite">Vous pouvez aussi commander sans compte.</p>
        </div>
    </div>
</x-layouts.shop>
