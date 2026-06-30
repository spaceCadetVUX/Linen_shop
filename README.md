# Linen — Fashion E-commerce

E-commerce and blog platform for **Linen**, a fashion clothing store. Built on Laravel 13 with Blade templating and Filament admin panel.

---

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Blade templates, HTML/CSS/JS |
| Admin | Filament v3 |
| Database | PostgreSQL |
| Cache / Queue | Redis + Laravel Horizon |
| Search | Meilisearch + Laravel Scout |
| Auth | Laravel Sanctum + Google OAuth |

---

## Requirements

- PHP 8.3+
- Composer
- PostgreSQL
- Redis
- Meilisearch (optional, for search)

---

## Installation

```bash
git clone <repo-url>
cd linen

composer install

cp .env.example .env
php artisan key:generate

# Configure .env: DB, Redis, Meilisearch, Google OAuth

php artisan migrate --seed
php artisan storage:link
php artisan filament:assets
```

---

## Local Development

```bash
php artisan serve        # http://localhost:8000
php artisan horizon      # queue worker
```

---

## Admin Panel

```
URL:      http://localhost:8000/admin
Email:    admin@example.com
Password: password
```

---

## Frontend Structure (Blade)

```
resources/views/
  layouts/          ← app, auth, checkout
  partials/         ← header, footer, nav, cart-drawer, search-overlay...
  pages/
    home
    shop/           ← listing, category
    product/        ← product detail
    collection/     ← lookbook / collections
    cart/
    checkout/
    account/        ← profile, orders, wishlist, addresses
    blog/
    static/         ← about, contact, size-guide, returns
    search/
  components/
    product/        ← card, grid, gallery, variant-selector, badge, review
    collection/     ← card
    blog/           ← card
    cart/           ← item, summary
    ui/             ← pagination, quantity-input, rating-stars, breadcrumb

public/assets/
  css/              ← app.css + vendor/
  js/               ← app.js + pages/ + vendor/
  images/           ← banners, products, collections, blog, brand, ui
  fonts/
  icons/
```

---

## Useful Commands

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan horizon
php artisan scribe:generate    # generate API docs
php artisan sitemap:generate
./vendor/bin/pint              # code formatter
./vendor/bin/phpstan analyse   # static analysis
```

---

## Docker

```bash
docker compose up -d
```

See `docker-compose.yml` for service definitions.
