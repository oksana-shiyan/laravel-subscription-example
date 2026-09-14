# Nexus — Laravel Stripe Subscription Example

Portfolio sample: **Laravel 13** + **Breeze (Blade)** + raw **`stripe/stripe-php`**. Public home page with three plans, Stripe Checkout, Customer Portal, and webhooks.

## Plans

| Plan       | Price  | Trial                            |
|------------|--------|----------------------------------|
| Basic      | $1/mo  | 14 days (card required up front) |
| Pro        | $5/mo  | —                                |
| Enterprise | $10/mo | —                                |

Plans are defined in `config/plans.php`. Stripe Price IDs come from `.env`.

## Stack

- Laravel Breeze (Blade + Tailwind)
- Stripe Checkout (hosted) for subscribe
- Stripe Customer Portal for payment method, cancel, and plan switches
- Local subscription fields on `users` (no Cashier)
- Docker Compose (`nginx`, `php-fpm`, `mysql`, `node`, `mailhog`)

## Quick start (Docker)

```bash
docker compose up -d php-fpm database nginx node
docker compose exec php-fpm composer install
docker compose exec php-fpm php artisan migrate
docker compose run --rm node npm install
docker compose run --rm node npm run build
```

App URL: [http://localhost:82](http://localhost:82) (see `COMPOSE_NGINX_PORT` / `APP_URL`).

### Demo login

After `php artisan db:seed`:

| Field    | Value              |
|----------|--------------------|
| Email    | `test@example.com` |
| Password | `password`         |

Login: [http://localhost:82/login](http://localhost:82/login)

Run Artisan and Composer inside the container:

```bash
docker compose exec php-fpm php artisan ...
docker compose exec php-fpm composer ...
docker compose exec php-fpm php artisan test
```

## Stripe Dashboard setup (test mode)

1. Create an account and switch to **Test mode**.
2. **Developers → API keys** — copy Publishable and Secret keys into `.env`:
   - `STRIPE_KEY`
   - `STRIPE_SECRET`
3. **Products** — create three products with monthly prices ($9 / $29 / $99). Copy each Price ID into:
   - `STRIPE_PRICE_BASIC`
   - `STRIPE_PRICE_PRO`
   - `STRIPE_PRICE_ENTERPRISE`
4. **Settings → Billing → Customer portal**
   - Enable portal
   - Allow customers to update payment methods
   - Allow cancellation (at period end recommended)
   - Allow switching among the three prices (products)
5. Save `.env` and clear config cache if needed:
   ```bash
   docker compose exec php-fpm php artisan config:clear
   ```

## Local webhooks (Stripe CLI)

```bash
stripe login
stripe listen --forward-to localhost:82/stripe/webhook
```

Copy the webhook signing secret (`whsec_...`) into `STRIPE_WEBHOOK_SECRET`.

Events handled:

- `checkout.session.completed`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.paid`
- `invoice.payment_failed`

Checkout success also syncs the session immediately so the dashboard updates without waiting on the webhook.

## Demo flow

1. Open `/` — three plan cards.
2. Click **Subscribe** → register/login (intended URL preserved) → Stripe Checkout.
3. Use test card `4242 4242 4242 4242`.
4. Return to **Dashboard** — plan status + feature panels (widgets / analytics / team).
5. **Manage billing** opens the Customer Portal (change plan, cancel, update card).

Access rules:

- Features require `active` or `trialing`
- `past_due` / `unpaid` / `incomplete` / `canceled` → billing blocked page + portal link
- Cancel at period end keeps access until the period ends

## Tests

```bash
docker compose exec php-fpm php artisan test
```

Stripe API calls are mocked in feature tests; webhook processing uses Stripe object fixtures.

## Key files

- `config/plans.php` — plan metadata + feature flags
- `app/Services/StripeService.php` — Checkout, Portal, webhook sync
- `app/Http/Middleware/EnsureUserIsSubscribed.php` — paywall
- `routes/web.php` — pricing, checkout, portal, webhook, dashboard
