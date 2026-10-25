# Sovereign Space ATLAS on Laravel Cloud

Production address: https://atlas.emmaconsulting.space
Operator: EMMA Aerospace Consulting LLP (British Columbia, LL03251)

A Laravel 13 application that serves the atlas, its accounts and plans, Paddle billing, Teams and the API.

## Plans the code enforces

| Plan | What the server sends | Price (USD, excl. tax) |
|---|---|---|
| Open atlas (no account) | Open edition: map, metrics, community, briefing headline, country file overviews. No cookies. | $0 |
| Free account | Open edition plus 3 full country files a month (`/app/unlock`) | $0 |
| Personal | Full edition, full briefings with audio, 21-day archive, watchlist on the account | $228 a year or $29 a month |
| Teams | Personal for every seat, team watchlist, team notes, CSV export | $300 per user a year or $35 a month, from 3 users |
| API | `/v1/*` with a bearer key, quota per key | $4,990 a year, Order Form |

The data is filtered on the server (`app/Support/AtlasData.php`, `app/Support/Plans.php`). The open edition HTML (`resources/atlas/index.open.html`) does not contain the programs, registry, engagements or full country files at all.

## Layout

| Path | What it is |
|---|---|
| `resources/atlas/index.full.html`, `index.open.html` | The atlas page, full and open editions (built by `package_site.py`) |
| `resources/atlas/data/atlas.json` | The full dataset, for the API and the filters |
| `resources/atlas/data/live.json` | Daily collections: briefings, country files, states, hubs, headlines |
| `resources/atlas/audio/` | Narrated briefings, served to paid plans at `/app/audio/...` |
| `resources/atlas/legal.html` | Imprint, terms of service, refund policy, AI notice, privacy policy |
| `resources/views/` | Pricing, sign-in, account, checkout, team, admin, withdrawal, error pages |
| `routes/atlas.php` | `/`, `/app/live`, `/app/audio`: no session unless the reader is signed in |
| `routes/web.php` | Accounts, billing, team, admin |
| `routes/api.php` | `/v1/countries`, `/v1/countries/{iso3}/file`, `/v1/programs`, `/v1/organizations`, `/v1/engagements`, `/v1/briefings`, `/v1/events` |
| `tools/update_live.py` | Rebuilds `live.json` and the audio folder from an export of the ATLAS database, with a leak scan |
| `tests/Feature/` | 29 tests: plan filtering, free allowance, watchlist, team notes, seats, invitations, withdrawal, API, admin |

## 1. Paddle

1. Create a Paddle Billing account for EMMA Aerospace Consulting LLP and a **sandbox** account for testing.
2. In the live account, request **domain approval** for `atlas.emmaconsulting.space`. Paddle checks that the site shows the product, the prices (`/pricing`), the Terms, the refund policy and the privacy policy (`/legal`, also at `/terms`, `/refunds`, `/privacy`), with the company name. Deploy first, so the reviewers can see it.
3. In each account (Developer tools):
   - **Client-side token** → `PADDLE_CLIENT_SIDE_TOKEN`
   - **API key** with write access to products, prices, customers, subscriptions, transactions → `PADDLE_API_KEY`
   - **Notification destination**: URL `https://atlas.emmaconsulting.space/paddle/webhook`, events: customer.updated, transaction.completed, transaction.updated, subscription.created, subscription.updated, subscription.paused, subscription.canceled. Copy its secret → `PADDLE_WEBHOOK_SECRET`.
   - **Default payment link** (Checkout settings): `https://atlas.emmaconsulting.space/pricing`.
4. Create the products and prices from the Laravel Cloud command line:
   ```
   php artisan atlas:paddle-setup
   ```
   It prints four `PADDLE_PRICE_*` lines for the environment. Use `--tax-category=saas` once Paddle has approved that category for the account. Run it once per account (sandbox, then live).

## 2. Laravel Cloud

1. Push this folder to a private GitHub repository and create the application from it, region **EU Central (Frankfurt)**.
2. Attach a **database** (Laravel Serverless Postgres or MySQL, same region). Laravel Cloud fills the `DB_*` variables.
3. Environment variables:
   ```
   APP_NAME="Sovereign Space ATLAS"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://atlas.emmaconsulting.space
   SESSION_DRIVER=database
   SESSION_LIFETIME=720
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   LOG_LEVEL=warning
   MAIL_MAILER=smtp
   MAIL_HOST=...            (your email delivery provider)
   MAIL_PORT=587
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS="atlas@emmaconsulting.space"
   MAIL_FROM_NAME="Sovereign Space ATLAS"
   PADDLE_SANDBOX=false
   PADDLE_CLIENT_SIDE_TOKEN=...
   PADDLE_API_KEY=...
   PADDLE_WEBHOOK_SECRET=...
   PADDLE_PRICE_PERSONAL_YEAR=pri_...
   PADDLE_PRICE_PERSONAL_MONTH=pri_...
   PADDLE_PRICE_TEAMS_YEAR=pri_...
   PADDLE_PRICE_TEAMS_MONTH=pri_...
   ATLAS_CONTACT=office@emmaconsulting.space
   ```
   Any SMTP provider works (Resend, Postmark, Amazon SES in eu-central-1, or the mail host of emmaconsulting.space). Set up SPF and DKIM for the sending domain so verification emails arrive.
4. Build command:
   ```
   composer install --no-dev --optimize-autoloader && php artisan optimize
   ```
   Deploy command:
   ```
   php artisan migrate --force
   ```
5. Turn on the **scheduler** for the app. It prunes free-allowance records after 13 months and expired invitations, as the privacy policy says.
6. Domain: add `atlas.emmaconsulting.space` and create the DNS records Laravel Cloud shows.
7. Make yourself admin: register at `/register` with your EMMA address, confirm it, then run `php artisan atlas:admin make-admin office@emmaconsulting.space`. `/admin` shows the year-one scoreboard.

## 3. Test before going live

With `PADDLE_SANDBOX=true` and sandbox keys and prices:
1. Register, confirm the email, open three country files, check the fourth is refused.
2. Buy Personal yearly with a Paddle test card. `/billing/done` turns to active within seconds (webhook). Check the full edition, audio and the 21-day archive.
3. Switch a monthly plan to yearly from `/account`, cancel, resume.
4. Start Teams with 3 seats, invite a colleague, accept, write a team note, export CSV from Compare.
5. Refund a payment in the Paddle dashboard and check the account.

Then switch to the live keys and prices.

## Operating

Staff roles on `/admin`:

| Role | What they can do |
|---|---|
| Admin | Everything, including Paddle and AI connectors |
| Manager | Everything except those connectors |
| Sales | Users, teams, organizations, withdrawals, and coupons |
| Data analyst | The organization directory |

A reader who names an organization from the directory must use a company email address and give their title. They can choose whether that name and title are shown on the organization. Coupon codes (1 month, 3 months, or the first year free) are created in the admin and entered on the account page.

| Task | How |
|---|---|
| Teams pilot, or a team that pays by invoice | `/admin`, Team pilot: owner email, seats, days |
| Complimentary Personal (press, partners) | `/admin` or `php artisan atlas:admin grant someone@org.com --days=90` |
| API customer after the Order Form is signed | `/admin`, Issue an API key (shown once), or `php artisan atlas:admin api-key contact@org.com --name="Org"` |
| Refund under the 14-day guarantee | Paddle dashboard, Transactions, Refund |
| Withdrawals | Arrive by email to `ATLAS_CONTACT` and are stored in the `withdrawals` table |
| KPIs | `/admin` or `php artisan atlas:admin kpi` |

## Keeping the data current

1. Export the ATLAS database collections (`briefings`, `country`, `states`, `hubs`, `feeds`, `blackhat`, `orgs`, `projects`) to a folder, one sub-folder per collection.
2. Download each briefing's audio asset into a second folder, named `<asset id>.mp4`.
3. Run `python3 tools/update_live.py <export folder> <audio folder> .` It refuses to write if it finds internal material.
4. Commit `resources/atlas` and push. Laravel Cloud redeploys. The page re-reads `/app/live` every 10 minutes.

When the atlas itself is rebuilt, its packaging step regenerates both editions, `atlas.json` and the fonts in this folder. Commit and push as above.

## Local development

```
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan serve
php artisan test
```
