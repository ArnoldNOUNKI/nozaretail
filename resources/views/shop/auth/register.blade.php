<x-layouts.shop title="Créer un compte — Noza Retail">
    <div class="wrap pt-12 lg:pt-20 max-w-md">
        <h1 class="display text-5xl">Créer un compte</h1>
        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
            @csrf
            <div><label class="label" for="name">Nom complet</label><input id="name" name="name" value="{{ old('name') }}" class="field" autocomplete="name" required>@error('name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="email" required>@error('email')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="phone">Téléphone</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="field" placeholder="6 77 12 34 56" autocomplete="tel" required>@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="password">Mot de passe <span class="text-graphite font-normal">(8 caractères minimum)</span></label><input id="password" name="password" type="password" class="field" autocomplete="new-password" required>@error('password')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" class="field" autocomplete="new-password" required></div>
            <button class="btn-ink w-full">Créer mon compte</button>
            <p class="text-sm text-graphite text-center">Déjà inscrit ? <a href="{{ route('login') }}" class="text-ink ulink">Se connecter</a></p>
        </form>
    </div>
</x-layouts.shop>
