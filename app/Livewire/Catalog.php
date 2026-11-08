<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
class Catalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'categorie', except: '')]
    public string $category = '';

    #[Url(as: 'marques', except: [])]
    public array $brands = [];

    #[Url(as: 'min', except: null)]
    public ?int $minPrice = null;

    #[Url(as: 'max', except: null)]
    public ?int $maxPrice = null;

    #[Url(as: 'promo', except: false)]
    public bool $onSale = false;

    #[Url(as: 'dispo', except: false)]
    public bool $inStock = false;

    #[Url(as: 'note', except: 0)]
    public int $minRating = 0;

    #[Url(as: 'tri', except: 'pertinence')]
    public string $sort = 'pertinence';

    public const SORTS = [
        'pertinence' => 'Pertinence',
        'ventes' => 'Meilleures ventes',
        'nouveautes' => 'Nouveautés',
        'prix-asc' => 'Prix croissant',
        'prix-desc' => 'Prix décroissant',
        'note' => 'Mieux notés',
    ];

    public function updating($name): void
    {
        if ($name !== 'page' && ! str_starts_with($name, 'paginators')) {
            $this->resetPage();
        }
    }

    public function setCategory(string $slug): void
    {
        $this->category = $this->category === $slug ? '' : $slug;
        $this->brands = [];
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'brands', 'minPrice', 'maxPrice', 'onSale', 'inStock', 'minRating', 'sort']);
        $this->resetPage();
    }

    private function currentCategory(): ?Category
    {
        return $this->category ? Category::active()->where('slug', $this->category)->with('children', 'parent')->first() : null;
    }

    private function baseQuery(?Category $cat): Builder
    {
        return Product::active()
            ->when($cat, fn ($q) => $q->whereIn('category_id', $cat->familyIds()))
            ->when(trim($this->search) !== '', function ($q) {
                foreach (preg_split('/\s+/', trim($this->search)) as $word) {
                    $q->where(fn ($w) => $w->where('name', 'like', "%{$word}%")
                        ->orWhere('short_description', 'like', "%{$word}%")
                        ->orWhere('tags', 'like', "%{$word}%")
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$word}%"))
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$word}%")));
                }
            });
    }

    public function render()
    {
        $cat = $this->currentCategory();
        $base = $this->baseQuery($cat);

        $products = (clone $base)
            ->when($this->brands, fn ($q) => $q->whereIn('brand_id', Brand::whereIn('slug', $this->brands)->pluck('id')))
            ->when($this->minPrice, fn ($q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn ($q) => $q->where('price', '<=', $this->maxPrice))
            ->when($this->onSale, fn ($q) => $q->onSale())
            ->when($this->inStock, fn ($q) => $q->where(fn ($s) => $s->where('track_stock', false)->orWhere('stock', '>', 0)))
            ->when($this->minRating, fn ($q) => $q->where('rating_avg', '>=', $this->minRating))
            ->with('variants')
            ->when($this->sort, fn ($q) => match ($this->sort) {
                'ventes' => $q->orderByDesc('sales_count'),
                'nouveautes' => $q->latest(),
                'prix-asc' => $q->orderBy('price'),
                'prix-desc' => $q->orderByDesc('price'),
                'note' => $q->orderByDesc('rating_avg')->orderByDesc('rating_count'),
                default => $q->orderByDesc('is_featured')->orderByDesc('sales_count'),
            })
            ->paginate(16);

        // Facettes calculées sur le périmètre courant (catégorie + recherche)
        $brandFacets = Brand::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->whereIn('products.id', (clone $base)->select('id'))])
            ->get()->where('products_count', '>', 0)->sortByDesc('products_count');
        $range = (clone $base)->selectRaw('min(price) as lo, max(price) as hi')->first();

        $categories = Category::active()->whereNull('parent_id')->orderBy('sort')->get();

        return view('livewire.catalog', compact('products', 'cat', 'brandFacets', 'range', 'categories'))
            ->title(($cat?->name ?? ($this->search ? 'Recherche « '.$this->search.' »' : 'Boutique')).' — '.setting('store_name'));
    }
}
