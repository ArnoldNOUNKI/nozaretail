<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\OrderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.shop')]
#[Title('Finaliser la commande — Noza Retail')]
class Checkout extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $city = '';

    public string $district = '';

    public string $landmark = '';

    public string $notes = '';

    public string $method = 'mobile_money';

    public bool $saveAddress = true;

    public ?int $addressId = null;

    public string $coupon = '';

    public function mount(CartService $cart)
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart');
        }
        if ($user = auth()->user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
            if ($addr = $user->addresses()->orderByDesc('is_default')->first()) {
                $this->useAddress($addr->id);
            }
        }

        return null;
    }

    public function useAddress(int $id): void
    {
        $a = auth()->user()?->addresses()->find($id);
        if (! $a) {
            return;
        }
        $this->addressId = $a->id;
        $this->fill(['name' => $a->full_name, 'phone' => $a->phone, 'city' => $a->city, 'district' => $a->district, 'landmark' => (string) $a->landmark]);
        $this->saveAddress = false;
    }

    public function updatedCity(): void
    {
        $this->addressId = null;
        $zone = ShippingZone::forCity($this->city);
        if ($this->method === 'cod' && $zone && ! $zone->allows_cash_on_delivery) {
            $this->method = 'mobile_money';
        }
    }

    public function applyCoupon(CartService $cart): void
    {
        $this->resetErrorBag('coupon');
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

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'phone' => ['required', fn ($a, $v, $fail) => normalize_cm_phone($v) ? null : $fail('Entrez un numéro camerounais valide (ex : 6 77 12 34 56).')],
            'email' => 'nullable|email|max:190',
            'city' => 'required|string',
            'district' => 'required|string|max:120',
            'landmark' => 'nullable|string|max:160',
            'notes' => 'nullable|string|max:500',
            'method' => 'required|in:mobile_money,cod',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['name' => 'nom', 'phone' => 'téléphone', 'city' => 'ville', 'district' => 'quartier', 'landmark' => 'point de repère'];
    }

    public function placeOrder(CartService $cart, OrderService $orders)
    {
        $data = $this->validate();
        try {
            $order = $orders->placeFromCart($cart, $data, PaymentMethod::from($this->method));
        } catch (RuntimeException $e) {
            $this->addError('order', $e->getMessage());

            return null;
        }

        if (auth()->check() && $this->saveAddress && ! $this->addressId) {
            $user = auth()->user();
            $user->addresses()->create(['label' => 'Adresse '.($user->addresses()->count() + 1), 'full_name' => $data['name'], 'phone' => $data['phone'],
                'city' => $data['city'], 'district' => $data['district'], 'landmark' => $data['landmark'] ?: null, 'is_default' => ! $user->addresses()->exists()]);
            if (! $user->phone) {
                $user->update(['phone' => $data['phone']]);
            }
        }
        session()->push('my_orders', $order->number);
        $this->dispatch('cart-updated');

        return $order->payment_method === PaymentMethod::MobileMoney
            ? $this->redirectRoute('payment.show', $order)
            : $this->redirectRoute('orders.track', [$order->number, $order->tracking_token]);
    }

    public function render(CartService $cart)
    {
        $totals = $cart->totals($this->city ?: null);

        return view('livewire.checkout', [
            'lines' => $cart->lines(),
            'totals' => $totals,
            'cities' => ShippingZone::cityOptions(),
            'codAllowed' => ! $totals['zone'] || $totals['zone']->allows_cash_on_delivery,
            'addresses' => auth()->user()?->addresses()->orderByDesc('is_default')->get() ?? collect(),
            'operator' => cm_operator($this->phone),
        ]);
    }
}
