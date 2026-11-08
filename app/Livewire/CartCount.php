<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    public int $count = 0;

    public function mount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    #[On('cart-updated')]
    public function refreshCount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    public function render()
    {
        return <<<'BLADE'
        <span x-data="{ n: @entangle('count') }" x-on:cart:updated.window="n = $event.detail.count"
              x-show="n > 0" x-cloak x-text="n"
              class="absolute -top-1 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-ink text-paper text-[11px] font-semibold grid place-items-center num"></span>
        BLADE;
    }
}
