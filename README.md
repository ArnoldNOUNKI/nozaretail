# Noza Retail — plateforme e-commerce

Boutique multi-catégories pour le Cameroun, paiement **MTN MoMo / Orange Money** (XAF) et paiement à la livraison, avec un tableau de bord d'administration complet.

**Stack :** Laravel 12 · Filament 4 (admin) · Livewire 3 + Alpine (boutique) · Tailwind 4 · MySQL 8 (SQLite en local)

---

## Démarrage en local

```bash
composer install
npm install && npm run build
cp .env.example .env            # puis en local : DB_CONNECTION=sqlite, APP_ENV=local, APP_DEBUG=true
php artisan key:generate
php artisan migrate --seed      # données de démonstration : 36 produits, 159 commandes, avis, coupons
php artisan storage:link
php artisan serve
```

- Boutique : http://127.0.0.1:8000
- Admin : http://127.0.0.1:8000/admin — `admin@nozaretail.cm` / `NozaAdmin2026!` (**à changer**)
- Clients de démo : `aicha.mbarga@example.cm` / `password`
- Tests : `php artisan test` (12 tests, parcours d'achat complet)

`PAYMENT_DRIVER=sandbox` simule le paiement Mobile Money : un écran permet de valider ou refuser. Les numéros finissant par `000` échouent toujours.

---

## Déploiement Coolify

1. Nouveau projet → **Docker Compose** → dépôt Git contenant ce dossier.
2. Variables d'environnement : reprendre `.env.example` (au minimum `APP_KEY`, `APP_URL`, `DB_*`, `MAIL_*`, `PAYMENT_*`).
   Générer la clé : `php artisan key:generate --show`.
3. Associer le domaine au service **app** (port **8080**).
4. Premier déploiement : les migrations tournent automatiquement. Puis, dans le terminal du conteneur `app` :
   ```bash
   SEED_DEMO=false php artisan db:seed --force   # admin, paramètres, zones de livraison, coupons, catégories
   ```
   Sans `ADMIN_PASSWORD`, un mot de passe aléatoire est affiché dans la console.

Le compose lance aussi `scheduler` (annule les commandes Mobile Money impayées après 2 h et libère le stock) et `queue` (e-mails de commande).

---

## Paiement Mobile Money

| Driver | Fonctionnement | Variables |
|---|---|---|
| `notchpay` | Redirection vers la page de paiement Notch Pay (MTN, Orange, carte) | `NOTCHPAY_PUBLIC_KEY`, `NOTCHPAY_HASH_KEY` |
| `campay` | Demande envoyée directement sur le téléphone du client (USSD), la page se met à jour seule | `CAMPAY_USERNAME`, `CAMPAY_PASSWORD`, `CAMPAY_WEBHOOK_KEY`, `CAMPAY_BASE_URL` |
| `sandbox` | Simulateur local | — |

**Webhooks à déclarer chez le prestataire :**
- Notch Pay : `https://VOTRE-DOMAINE/webhooks/notchpay` (signature `x-notch-signature`, HMAC-SHA256)
- CamPay : `https://VOTRE-DOMAINE/webhooks/campay`

Le statut est toujours revérifié auprès de l'API de la passerelle, jamais cru sur la seule notification. Le traitement est idempotent : une notification reçue deux fois ne confirme pas la commande deux fois.

> ⚠️ Avant la mise en production, faites une transaction réelle de faible montant avec chaque opérateur. Les endpoints CamPay (`/token/`, `/collect/`, `/transaction/{ref}/`) et le format du webhook sont à valider avec votre compte marchand. Le passage en production se fait via `CAMPAY_BASE_URL=https://www.campay.net/api`.

---

## Fonctionnalités

**Boutique**
- Accueil : carrousel de bannières, rayons, ventes flash avec compte à rebours, sélection, meilleures ventes.
- Catalogue Livewire sans rechargement : recherche, catégories et sous-catégories, marques (facettes avec compteurs), prix, promo, disponibilité, note. Six tris possibles, filtres dans l'URL (liens partageables).
- Recherche instantanée dans l'en-tête.
- Fiche produit :
  - galerie (swipe sur mobile) et variantes (taille, couleur…) avec disponibilité par combinaison ;
  - stock en temps réel, favoris, « Acheter maintenant » ;
  - rubriques « Souvent achetés avec » (calculée sur les vraies commandes), « Dans le même rayon » et « Vus récemment » ;
  - avis clients avec modération et badge « Achat vérifié » ;
  - balisage SEO JSON-LD.
- Panier :
  - fonctionne aussi pour les invités ;
  - barre de progression vers la livraison offerte ;
  - codes promo (pourcentage, montant fixe, livraison offerte, plafonds, dates, limites par client).
- Checkout en une page :
  - frais calculés selon la ville ;
  - opérateur MTN ou Orange détecté à la saisie du numéro ;
  - paiement à la livraison selon la zone ;
  - carnet d'adresses.
- Suivi de commande sans compte (lien sécurisé), facture imprimable / PDF, contact WhatsApp prérempli.
- Compte client : commandes, favoris, adresses, profil.
- Responsive avec navigation basse sur mobile, sitemap.xml, robots.txt, page 404 utile.

**Administration (`/admin`)**
- Tableau de bord :
  - chiffre d'affaires et nombre de commandes sur 30 jours, comparés aux 30 jours précédents ;
  - CA par jour, avec répartition Mobile Money / paiement à la livraison ;
  - commandes par statut, dernières commandes, produits à réapprovisionner ;
  - meilleures ventes avec marge brute.
- Commandes :
  - onglets de travail : À traiter, En préparation, En livraison… ;
  - bouton « étape suivante », changement de statut avec note et e-mail au client ;
  - message WhatsApp prérempli, facture, revérification du paiement ;
  - export CSV, actions groupées.
- Produits :
  - photos multiples avec recadrage, variantes, caractéristiques ;
  - calcul de marge en direct, SEO, ventes flash ;
  - stock tracé (chaque entrée et sortie est historisée) ;
  - remise groupée en %, duplication.
- Catégories (ordre par glisser-déposer), marques, clients (valeur client, clients fidèles ou inactifs), avis, codes promo (avec CA généré), bannières programmables.
- Zones de livraison, transactions Mobile Money, équipe, paramètres de la boutique.
- Recherche globale `Ctrl+K`.

**Règles métier**
- Le stock est réservé au moment de la commande, sous verrou, pour éviter la survente.
- Il est restitué automatiquement en cas d'annulation, une seule fois même si l'annulation est répétée.
- Une commande Mobile Money impayée est annulée au bout de `PAYMENT_UNPAID_TIMEOUT` minutes.
- Montants stockés en entiers XAF.
- Charte : noir et blanc du logo, Gloock (titres) et Instrument Sans (texte), polices auto-hébergées.

---

## À compléter avant le lancement
- Photos produits : la boutique affiche une vignette au monogramme tant qu'aucune photo n'est ajoutée.
- Textes légaux (CGV, RCCM, NIU) dans `resources/views/shop/page.blade.php`.
- Téléphone, WhatsApp et adresse réels dans **Admin → Configuration → Paramètres**.
- Serveur SMTP pour les e-mails de commande.
