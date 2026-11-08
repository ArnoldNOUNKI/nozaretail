<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD') ?: (app()->isProduction() ? Str::password(16) : 'NozaAdmin2026!');
        User::create([
            'name' => 'Administrateur Noza',
            'email' => env('ADMIN_EMAIL', 'admin@nozaretail.cm'),
            'phone' => '+237 690 00 00 00',
            'password' => $password,
            'is_admin' => true,
        ]);
        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn('Mot de passe admin : '.$password.' (à changer après connexion)');
        }

        Setting::put(Setting::DEFAULTS);

        // ---- Livraison ----
        ShippingZone::create(['name' => 'Douala', 'cities' => ['Douala'], 'fee' => 1500, 'free_above' => 50000, 'delivery_delay' => 'Sous 24 h', 'sort' => 1]);
        ShippingZone::create(['name' => 'Yaoundé', 'cities' => ['Yaoundé'], 'fee' => 1500, 'free_above' => 50000, 'delivery_delay' => 'Sous 24 h', 'sort' => 2]);
        ShippingZone::create(['name' => 'Grandes villes', 'cities' => ['Bafoussam', 'Bamenda', 'Buea', 'Limbé', 'Kribi', 'Garoua', 'Maroua', 'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Dschang', 'Nkongsamba', 'Edéa'], 'fee' => 3500, 'free_above' => 100000, 'delivery_delay' => '48 à 72 h', 'allows_cash_on_delivery' => false, 'sort' => 3]);

        // ---- Coupons ----
        Coupon::create(['code' => 'BIENVENUE10', 'description' => 'Première commande', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 15000, 'max_discount' => 10000, 'max_uses_per_user' => 1]);
        Coupon::create(['code' => 'LIVRAISON', 'description' => 'Livraison offerte', 'type' => 'free_shipping', 'value' => 0, 'min_subtotal' => 25000]);
        Coupon::create(['code' => 'NOZA5000', 'description' => '5 000 FCFA dès 60 000', 'type' => 'fixed', 'value' => 5000, 'min_subtotal' => 60000, 'expires_at' => now()->addMonths(2)]);

        // Sans données de démonstration : boutique vide prête à remplir depuis l'admin
        if (! filter_var(env('SEED_DEMO', true), FILTER_VALIDATE_BOOL)) {
            return;
        }

        // ---- Catalogue (démonstration) ----
        $tree = [
            'Mode' => ['Femme', 'Homme', 'Chaussures', 'Sacs & accessoires'],
            'Beauté' => ['Soins visage', 'Cheveux', 'Parfums'],
            'Maison' => ['Cuisine', 'Décoration', 'Linge de maison'],
            'High-tech' => ['Téléphones', 'Audio', 'Accessoires'],
            'Électroménager' => ['Petit électroménager', 'Climatisation & ventilation'],
            'Bébé & enfants' => ['Puériculture', 'Jouets'],
        ];
        $cats = [];
        $i = 0;
        foreach ($tree as $parent => $children) {
            $p = Category::create(['name' => $parent, 'is_featured' => true, 'sort' => $i++,
                'description' => "Notre sélection {$parent} : des produits choisis, des prix justes."]);
            foreach ($children as $j => $child) {
                $cats[$child] = Category::create(['name' => $child, 'parent_id' => $p->id, 'sort' => $j, 'slug' => Str::slug($parent.'-'.$child)]);
            }
        }

        $brands = collect(['Noza Essentials', 'Tecno', 'Samsung', 'Itel', 'Oraimo', 'Binatone', 'Nivea', 'Shea Moisture', 'Kaba Atelier', 'Moulinex', 'Philips', 'Ndop Studio'])
            ->mapWithKeys(fn ($b) => [$b => Brand::create(['name' => $b])]);

        // [nom, sous-catégorie, marque, prix, prix barré, coût, stock, variantes?]
        $catalog = [
            ['Robe longue en wax « Sanaga »', 'Femme', 'Kaba Atelier', 24500, 32000, 11000, 0, ['Taille' => ['S', 'M', 'L', 'XL'], 'Motif' => ['Indigo', 'Terre']]],
            ['Kaba moderne brodé', 'Femme', 'Kaba Atelier', 18000, null, 8000, 0, ['Taille' => ['M', 'L', 'XL', 'XXL']]],
            ['Ensemble pagne deux pièces', 'Femme', 'Ndop Studio', 29500, 35000, 14000, 0, ['Taille' => ['S', 'M', 'L']]],
            ['Chemise en lin col mao', 'Homme', 'Noza Essentials', 15500, null, 6500, 0, ['Taille' => ['M', 'L', 'XL'], 'Couleur' => ['Blanc', 'Sable', 'Noir']]],
            ['Boubou brodé cérémonie', 'Homme', 'Ndop Studio', 45000, 55000, 21000, 0, ['Taille' => ['L', 'XL', 'XXL']]],
            ['Polo piqué coton', 'Homme', 'Noza Essentials', 9500, 12000, 3800, 0, ['Taille' => ['S', 'M', 'L', 'XL'], 'Couleur' => ['Noir', 'Blanc', 'Marine']]],
            ['Baskets cuir blanches', 'Chaussures', 'Noza Essentials', 27500, null, 12500, 0, ['Pointure' => ['40', '41', '42', '43', '44']]],
            ['Sandales tressées artisanales', 'Chaussures', 'Kaba Atelier', 12000, null, 5000, 0, ['Pointure' => ['37', '38', '39', '40', '41']]],
            ['Sac cabas tissé', 'Sacs & accessoires', 'Kaba Atelier', 16500, 19500, 7000, 25, null],
            ['Montre minimaliste acier', 'Sacs & accessoires', 'Noza Essentials', 22000, 29000, 9000, 14, null],
            ['Lunettes de soleil polarisées', 'Sacs & accessoires', 'Noza Essentials', 8500, null, 3000, 40, null],
            ['Beurre de karité pur 250 g', 'Soins visage', 'Shea Moisture', 4500, null, 1800, 120, null],
            ['Crème hydratante Nivea Soft 300 ml', 'Soins visage', 'Nivea', 5200, 6000, 3200, 80, null],
            ['Sérum vitamine C', 'Soins visage', 'Noza Essentials', 11500, 14000, 4200, 3, null],
            ['Huile de ricin noire 120 ml', 'Cheveux', 'Shea Moisture', 6500, null, 2600, 60, null],
            ['Kit soin cheveux crépus', 'Cheveux', 'Shea Moisture', 19500, 24000, 9500, 18, null],
            ['Eau de parfum « Bois de Mbam » 50 ml', 'Parfums', 'Noza Essentials', 26000, null, 9000, 22, null],
            ['Marmite en fonte émaillée 5 L', 'Cuisine', 'Noza Essentials', 38000, 45000, 19000, 9, null],
            ['Set de 3 couteaux de chef', 'Cuisine', 'Noza Essentials', 14500, null, 6000, 30, null],
            ['Vase en terre cuite de Foumban', 'Décoration', 'Ndop Studio', 17500, null, 7000, 11, null],
            ['Coussin tissu ndop 45×45', 'Décoration', 'Ndop Studio', 9000, null, 3500, 0, ['Couleur' => ['Indigo', 'Ocre']]],
            ['Parure de lit coton 2 places', 'Linge de maison', 'Noza Essentials', 23500, 28000, 10500, 16, null],
            ['Tecno Spark 30 128 Go', 'Téléphones', 'Tecno', 89000, 99000, 72000, 0, ['Couleur' => ['Noir', 'Bleu']]],
            ['Samsung Galaxy A16 128 Go', 'Téléphones', 'Samsung', 125000, null, 104000, 7, null],
            ['Itel A80 64 Go', 'Téléphones', 'Itel', 54000, 59000, 43000, 20, null],
            ['Écouteurs sans fil FreePods', 'Audio', 'Oraimo', 16500, 19900, 9000, 45, null],
            ['Enceinte Bluetooth étanche', 'Audio', 'Oraimo', 21000, null, 12000, 4, null],
            ['Batterie externe 20 000 mAh', 'Accessoires', 'Oraimo', 13500, 16000, 7500, 70, null],
            ['Chargeur rapide 33 W USB-C', 'Accessoires', 'Oraimo', 7500, null, 3500, 90, null],
            ['Mixeur blender 1,5 L', 'Petit électroménager', 'Moulinex', 32000, 38000, 21000, 12, null],
            ['Fer à repasser vapeur', 'Petit électroménager', 'Philips', 19500, null, 12000, 15, null],
            ['Ventilateur sur pied 18"', 'Climatisation & ventilation', 'Binatone', 29000, 34000, 18000, 2, null],
            ['Ventilateur rechargeable solaire', 'Climatisation & ventilation', 'Binatone', 42000, null, 27000, 10, null],
            ['Porte-bébé ergonomique', 'Puériculture', 'Noza Essentials', 27000, null, 12000, 8, null],
            ['Lot de 6 bodies coton bio', 'Puériculture', 'Noza Essentials', 12500, 15000, 5000, 0, ['Âge' => ['0-3 mois', '3-6 mois', '6-12 mois']]],
            ['Jeu d\'awalé en bois sculpté', 'Jouets', 'Ndop Studio', 15000, null, 5500, 13, null],
        ];

        $products = collect();
        foreach ($catalog as $k => [$name, $cat, $brand, $price, $compare, $cost, $stock, $options]) {
            $product = Product::create([
                'name' => $name,
                'category_id' => $cats[$cat]->id,
                'brand_id' => $brands[$brand]->id,
                'price' => $price,
                'compare_price' => $compare,
                'cost' => $cost,
                'stock' => $stock,
                'low_stock_threshold' => 5,
                'short_description' => "{$name}, sélectionné par Noza Retail pour sa qualité et sa durabilité.",
                'description' => "<p>{$name} — un produit choisi avec soin par notre équipe.</p><ul><li>Qualité contrôlée avant expédition</li><li>Livraison rapide à Douala et Yaoundé</li><li>Retour possible sous 7 jours</li></ul>",
                'specs' => ['Marque' => $brand, 'Garantie' => in_array($cat, ['Téléphones', 'Audio', 'Petit électroménager', 'Climatisation & ventilation', 'Accessoires']) ? '12 mois' : '—', 'Origine' => in_array($brand, ['Kaba Atelier', 'Ndop Studio']) ? 'Fait au Cameroun' : 'Importé'],
                'tags' => [Str::slug($cat)],
                'badge' => match (true) { in_array($brand, ['Kaba Atelier', 'Ndop Studio']) => 'Fait au Cameroun', $k % 9 === 0 => 'Nouveau', default => null },
                'is_featured' => $k % 3 === 0,
                'sale_ends_at' => $compare && $k % 4 === 0 ? now()->addDays(3)->endOfDay() : null,
                'meta_description' => "Achetez {$name} sur Noza Retail. Paiement Mobile Money, livraison rapide au Cameroun.",
            ]);
            if ($options) {
                $combos = [[]];
                foreach ($options as $label => $values) {
                    $next = [];
                    foreach ($combos as $c) {
                        foreach ($values as $v) {
                            $next[] = $c + [$label => $v];
                        }
                    }
                    $combos = $next;
                }
                foreach ($combos as $n => $combo) {
                    $product->variants()->create(['options' => $combo, 'stock' => ($n * 7 + $k) % 11 === 0 ? 0 : 3 + ($n * 5 + $k) % 14]);
                }
                $product->update(['stock' => $product->variants()->sum('stock')]);
            }
            $products->push($product->fresh('variants'));
        }

        // ---- Bannières ----
        Banner::create(['title' => 'La saison des pagnes', 'subtitle' => 'Kabas, robes et ensembles taillés à Douala.', 'cta_label' => 'Voir la mode', 'cta_url' => '/boutique?categorie=mode', 'placement' => 'home_hero', 'sort' => 1]);
        Banner::create(['title' => 'Restez au frais', 'subtitle' => 'Ventilateurs rechargeables et solaires, livrés en 24 h.', 'cta_label' => 'Voir les ventilateurs', 'cta_url' => '/boutique?categorie=electromenager', 'placement' => 'home_hero', 'sort' => 2]);
        Banner::create(['title' => '-10 % sur votre première commande', 'subtitle' => 'Avec le code BIENVENUE10 dès 15 000 FCFA.', 'cta_label' => 'J\'en profite', 'cta_url' => '/boutique', 'placement' => 'home_strip']);

        // ---- Clients, commandes, avis ----
        $first = ['Aïcha', 'Brice', 'Carine', 'Daniel', 'Estelle', 'Franck', 'Grâce', 'Hervé', 'Inès', 'Junior', 'Kevine', 'Landry', 'Mireille', 'Nadège', 'Olivier', 'Patricia', 'Rodrigue', 'Sandrine', 'Thierry', 'Vanessa'];
        $last = ['Mbarga', 'Ngo Bassa', 'Fotso', 'Tchoumi', 'Eyenga', 'Nkwenti', 'Abena', 'Kamga', 'Ndzana', 'Talla'];
        $districts = ['Douala' => ['Akwa', 'Bonamoussadi', 'Makepe', 'Bonapriso', 'Deïdo', 'Logpom'], 'Yaoundé' => ['Bastos', 'Biyem-Assi', 'Mvan', 'Essos', 'Omnisport'], 'Bafoussam' => ['Tamdja'], 'Kribi' => ['Mpangou']];
        $customers = collect();
        foreach ($first as $n => $fn) {
            $name = $fn.' '.$last[$n % count($last)];
            $city = Arr::random(['Douala', 'Douala', 'Yaoundé', 'Yaoundé', 'Bafoussam', 'Kribi']);
            $phone = '+237 6'.Arr::random(['7', '9', '5', '8']).rand(1, 9).' '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99);
            $u = User::create(['name' => $name, 'email' => Str::slug($fn).'.'.Str::slug($last[$n % count($last)]).'@example.cm', 'phone' => $phone, 'password' => 'password', 'created_at' => now()->subDays(rand(5, 120))]);
            Address::create(['user_id' => $u->id, 'full_name' => $name, 'phone' => $phone, 'city' => $city, 'district' => Arr::random($districts[$city]), 'landmark' => 'Près de la pharmacie', 'is_default' => true]);
            $customers->push($u);
        }

        $zones = ShippingZone::all();
        for ($d = 75; $d >= 0; $d--) {
            $count = rand(0, 3) + ($d < 30 ? rand(0, 3) : 0);
            for ($o = 0; $o < $count; $o++) {
                $u = $customers->random();
                $addr = $u->addresses->first();
                $date = now()->subDays($d)->setTime(rand(8, 22), rand(0, 59));
                if ($date->isFuture()) {
                    $date = now()->subMinutes(rand(5, 300));
                }
                $picked = $products->random(rand(1, 3));
                $subtotal = 0;
                $lines = [];
                foreach ($picked as $p) {
                    $v = $p->variants->isNotEmpty() ? $p->variants->random() : null;
                    $qty = rand(1, 2);
                    $unit = $v ? $v->effectivePrice() : $p->price;
                    $subtotal += $unit * $qty;
                    $lines[] = [$p, $v, $qty, $unit];
                }
                $zone = ShippingZone::forCity($addr->city) ?? $zones->first();
                $fee = $zone->feeFor($subtotal);
                $method = $zone->allows_cash_on_delivery && rand(0, 2) === 0 ? PaymentMethod::CashOnDelivery : PaymentMethod::MobileMoney;
                $status = match (true) {
                    $d > 6 => Arr::random([OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Cancelled]),
                    $d > 2 => Arr::random([OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Processing]),
                    default => Arr::random([OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing]),
                };
                $paid = $status !== OrderStatus::Cancelled && ($method === PaymentMethod::MobileMoney ? $status !== OrderStatus::Pending : $status === OrderStatus::Delivered);
                $order = Order::create([
                    'user_id' => $u->id, 'shipping_zone_id' => $zone->id,
                    'customer_name' => $u->name, 'customer_phone' => $u->phone, 'customer_email' => $u->email,
                    'city' => $addr->city, 'district' => $addr->district, 'landmark' => $addr->landmark,
                    'subtotal' => $subtotal, 'discount' => 0, 'shipping_fee' => $fee, 'total' => $subtotal + $fee,
                    'status' => $status, 'payment_method' => $method,
                    'payment_status' => $paid ? PaymentStatus::Paid : ($status === OrderStatus::Cancelled ? PaymentStatus::Failed : ($method === PaymentMethod::MobileMoney ? PaymentStatus::Pending : PaymentStatus::Unpaid)),
                    'paid_at' => $paid ? $date->copy()->addMinutes(5) : null,
                    'shipped_at' => in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered]) ? $date->copy()->addDay() : null,
                    'delivered_at' => $status === OrderStatus::Delivered ? $date->copy()->addDays(2) : null,
                    'created_at' => $date, 'updated_at' => $date,
                ]);
                foreach ($lines as [$p, $v, $qty, $unit]) {
                    $order->items()->create(['product_id' => $p->id, 'product_variant_id' => $v?->id, 'name' => $p->name, 'variant_label' => $v?->name,
                        'sku' => $v?->sku ?? $p->sku, 'unit_price' => $unit, 'unit_cost' => $p->cost, 'quantity' => $qty, 'total' => $unit * $qty]);
                    if ($status !== OrderStatus::Cancelled) {
                        $p->increment('sales_count', $qty);
                    }
                }
                $order->histories()->create(['status' => OrderStatus::Pending, 'note' => 'Commande passée sur la boutique', 'created_at' => $date]);
                if ($status !== OrderStatus::Pending) {
                    $order->histories()->create(['status' => $status, 'created_at' => $date->copy()->addDay()]);
                }
                if ($method === PaymentMethod::MobileMoney) {
                    $order->payments()->create(['gateway' => 'notchpay', 'reference' => 'PAY-'.$order->number, 'phone' => normalize_cm_phone($u->phone),
                        'operator' => cm_operator($u->phone), 'amount' => $order->total, 'status' => $paid ? 'success' : ($status === OrderStatus::Cancelled ? 'failed' : 'pending'),
                        'completed_at' => $paid ? $order->paid_at : null, 'created_at' => $date]);
                }
            }
        }

        $comments = [
            5 => ['Parfait', 'Conforme à la description, livré le lendemain à Bonamoussadi.'],
            4 => ['Très bien', 'Bonne qualité pour le prix. Le livreur a appelé avant de passer.'],
            3 => ['Correct', 'Produit correct, un peu plus petit que ce que j\'imaginais.'],
        ];
        foreach ($products->random(24) as $p) {
            foreach ($customers->random(rand(1, 4)) as $u) {
                $r = Arr::random([5, 5, 5, 4, 4, 3]);
                Review::create(['product_id' => $p->id, 'user_id' => $u->id, 'author_name' => $u->name, 'rating' => $r,
                    'title' => $comments[$r][0], 'body' => $comments[$r][1], 'is_verified_purchase' => true, 'is_approved' => rand(0, 5) > 0]);
            }
        }
    }
}
