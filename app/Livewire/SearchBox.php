<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class SearchBox extends Component
{
    public string $q = '';

    public function go()
    {
        return $this->redirectRoute('shop', ['q' => trim($this->q)]);
    }

    public function render()
    {
        $term = trim($this->q);
        $results = mb_strlen($term) >= 2
            ? Product::active()->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('tags', 'like', "%{$term}%"))
                ->orderByDesc('sales_count')->take(6)->get()
            : collect();
        $cats = mb_strlen($term) >= 2 ? Category::active()->where('name', 'like', "%{$term}%")->take(3)->get() : collect();

        return view('livewire.search-box', compact('results', 'cats', 'term'));
    }
}
