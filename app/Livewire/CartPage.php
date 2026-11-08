<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Panier — Noza Retail')]
class CartPage extends Component
{
    public string $coupon = '';

    public function updateQty(CartService $cart, string $key, int $qty): void
    {
        $cart->update($key, $qty);
        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cart, string $key): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
    }

    public function applyCoupon(CartService $cart): void
    {
        $this->resetErrorBag();
        if ($error = $cart->applyCoupon($this->coupon)) {
            $this->addError('coupon', $error);
        } else {
            $this->coupon = '';
        }
    }

    public function removeCoupon(CartService $cart): void
    {
        $cart->removeCoupon();
    }

    public function render(CartService $cart)
    {
        $lines = $cart->lines();
        $suggestions = $lines->isEmpty()
            ? Product::active()->with('variants')->orderByDesc('sales_count')->take(4)->get()
            : Product::active()->with('variants')->whereIn('category_id', $lines->pluck('product.category_id'))
                ->whereNotIn('id', $lines->pluck('product.id'))->orderByDesc('sales_count')->take(4)->get();

        return view('livewire.cart-page', [
            'lines' => $lines,
            'totals' => $cart->totals(),
            'suggestions' => $suggestions,
            'freeShippingAt' => 50000,
        ]);
    }
}
