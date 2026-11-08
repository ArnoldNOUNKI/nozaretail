<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class ProductBuy extends Component
{
    public Product $product;

    /** ["Taille" => "M", ...] */
    public array $selected = [];

    public int $qty = 1;

    public bool $wished = false;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['variants' => fn ($q) => $q->where('is_active', true)]);
        // pré-sélectionne les options à valeur unique
        foreach ($this->product->optionGroups() as $name => $values) {
            if (count($values) === 1) {
                $this->selected[$name] = $values[0];
            }
        }
        $this->wished = (bool) auth()->user()?->wishlist()->where('product_id', $product->id)->exists();
    }

    public function select(string $option, string $value): void
    {
        $this->selected[$option] = ($this->selected[$option] ?? null) === $value ? null : $value;
        $this->selected = array_filter($this->selected);
        $this->qty = min($this->qty, max(1, $this->maxQty()));
    }

    public function variant()
    {
        if ($this->product->variants->isEmpty()) {
            return null;
        }
        $groups = $this->product->optionGroups();
        if (count(array_filter($this->selected)) < count($groups)) {
            return null;
        }

        return $this->product->variants->first(fn ($v) => collect($v->options)->every(fn ($val, $k) => ($this->selected[$k] ?? null) === $val));
    }

    /** Une valeur est-elle disponible compte tenu des autres options choisies ? */
    public function isAvailable(string $option, string $value): bool
    {
        $others = collect($this->selected)->except($option);

        return $this->product->variants->contains(fn ($v) => ($v->options[$option] ?? null) === $value
            && $others->every(fn ($val, $k) => ($v->options[$k] ?? null) === $val)
            && (! $this->product->track_stock || $v->stock > 0));
    }

    public function maxQty(): int
    {
        if (! $this->product->track_stock) {
            return CartService::MAX_QTY;
        }
        $v = $this->variant();

        return $this->product->variants->isEmpty() ? (int) $this->product->stock : (int) ($v?->stock ?? CartService::MAX_QTY);
    }

    public function inc(): void
    {
        $this->qty = min($this->qty + 1, max(1, min($this->maxQty(), CartService::MAX_QTY)));
    }

    public function dec(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function add(CartService $cart, bool $buyNow = false)
    {
        $variant = $this->variant();
        if ($this->product->variants->isNotEmpty() && ! $variant) {
            $missing = collect($this->product->optionGroups())->keys()->diff(array_keys(array_filter($this->selected)))->first();
            $this->addError('options', 'Choisissez '.mb_strtolower($missing ?? 'une option').'.');

            return null;
        }
        $error = $cart->add($this->product->id, $variant?->id, $this->qty);
        if ($error && ! str_contains($error, 'ajustée')) {
            $this->addError('options', $error);

            return null;
        }
        $this->dispatch('cart-updated');
        if ($buyNow) {
            return $this->redirectRoute('checkout', navigate: false);
        }
        $this->dispatch('toast', message: $error ?? 'Ajouté au panier', product: [
            'name' => $this->product->name.($variant ? ' — '.$variant->name : ''),
            'image' => $this->product->imageUrl(),
            'price' => money($variant?->effectivePrice() ?? $this->product->price),
        ]);

        return null;
    }

    public function toggleWish()
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('product.show', $this->product));

            return $this->redirectRoute('login');
        }
        $r = auth()->user()->wishlist()->toggle($this->product->id);
        $this->wished = ! empty($r['attached']);

        return null;
    }

    public function render()
    {
        $variant = $this->variant();

        return view('livewire.product-buy', [
            'variant' => $variant,
            'price' => $variant?->effectivePrice() ?? $this->product->price,
            'groups' => $this->product->optionGroups(),
            'stock' => $variant ? $variant->stock : ($this->product->variants->isEmpty() ? $this->product->stock : null),
        ]);
    }
}
