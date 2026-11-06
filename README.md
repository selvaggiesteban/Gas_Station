# LESCO GNC — Gas Station Operations Platform

[![Deploy to Cloudflare](https://github.com/selvaggiesteban/Gas_Station/actions/workflows/deploy.yml/badge.svg)](https://github.com/selvaggiesteban/Gas_Station/actions/workflows/deploy.yml)
![Node.js](https://img.shields.io/badge/node-%E2%89%A522.12-brightgreen)
![Tests](https://img.shields.io/badge/tests-68%20passed-brightgreen)
![Cloudflare Workers](https://img.shields.io/badge/Cloudflare-Workers%20Free-black)

An all-in-one management platform for gas station operations — online storefront, loyalty program, shift operations and back-office administration — running entirely on the **Cloudflare Workers Free** tier (**$0/month**).

**Live environment:** <https://app.lescognc.workers.dev>

> The product UI is written in Spanish (es-AR, *voseo*) and ships under the **LESCO GNC** brand. Branding is configurable via environment variable (white-label).

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Scripts](#scripts)
- [Configuration](#configuration)
- [Database & Migrations](#database--migrations)
- [Testing](#testing)
- [CI/CD](#cicd)
- [Project Structure](#project-structure)
- [Domain Model](#domain-model)
- [Legacy Codebase](#legacy-codebase)

## Features

### Storefront & Loyalty (customers)

- Product catalog with pricing and stock management.
- Shopping cart and checkout via **bank transfer**, with payment proof upload to R2 and admin approval (Mercado Pago integration stubbed as *coming soon*).
- Coupons and order attribution to the serving store manager (*gestor*).
- Points program (sign-ins, purchases, challenges, manual adjustments) with **weekly challenges** and a global leaderboard.
- Periodic raffles (*sorteos*) with coupon-based entries.

### Operations (store managers & admins)

- **Shift reports** (*rendición de turno*) across four shifts: morning, afternoon, night and *additional*.
- Envelope reconciliation (*ensobrado*) with six envelope slots, cash float (*fondo de cambio*) and voucher tracking with a **$20,000 alert threshold**.
- Lubricant stock control (received / sold / delivered).
- Per-shift cash register (*caja*) sourced from sales summaries, including the additional shift.
- **Daily summary** (admin-only): recaudación breakdown, facturado playeros, manual revenue entry for services and minimercado, and a totals column.

### Administration

- User and role management (`admin`, `store_manager`, `customer`).
- Product, stock and coupon administration.
- Order approval workflow with R2 receipt storage.
- Announcements and notifications with audience targeting (all / customers / store managers).
- Branding and platform settings.

### Platform

- Email delivery via **Gmail SMTP** (app password), with a graceful logging stub when not configured — used for welcome mails and password recovery.
- Database-backed sessions with automatic expiry cleanup.
- Cron trigger (`*/5 * * * *`) for session cleanup and job-queue polling.
- Server-rendered responsive UI (monochrome black/white/grey, no animations), fully mobile-friendly.

## Tech Stack

| Layer | Technology |
|---|---|
| Runtime | [Cloudflare Workers](https://workers.cloudflare.com/) (Free tier) |
| HTTP framework | [Hono](https://hono.dev/) |
| Database | [Cloudflare D1](https://developers.cloudflare.com/d1/) (SQLite) + [Drizzle ORM](https://orm.drizzle.team/) |
| Object storage | [Cloudflare R2](https://developers.cloudflare.com/r2/) (`gas-station-assets`) |
| Scheduling | Cron Trigger `*/5 * * * *` |
| Email | Gmail SMTP over Cloudflare sockets |
| Language | TypeScript (strict), Node.js ≥ 22.12 |
| Testing | Vitest + `@cloudflare/vitest-plugin` |
| CI/CD | GitHub Actions → `wrangler` |

## Getting Started

**Prerequisites:** Node.js ≥ 22.12, npm, and a Cloudflare account with [Wrangler authenticated](https://developers.cloudflare.com/workers/wrangler/install-and-authenticate/) (`npx wrangler login`) for deploys.

```bash
git clone https://github.com/selvaggiesteban/Gas_Station.git
cd Gas_Station
npm ci

# Prepare the local database
npm run db:migrate:local

# Start the dev server (http://localhost:8787)
npm run dev
```

Quality gates before pushing:

```bash
npm run typecheck   # tsc --noEmit
npm test            # vitest run (68 tests, 9 suites)
```

Deploy to Cloudflare:

```bash
npm run db:migrate:remote   # apply pending D1 migrations
npm run deploy              # wrangler deploy
```

## Scripts

| Command | Description |
|---|---|
| `npm run dev` | Local development server (`wrangler dev`) |
| `npm run deploy` | Deploy the Worker to Cloudflare |
| `npm run typecheck` | TypeScript type-check (`tsc --noEmit`) |
| `npm test` | Run the full test suite (Vitest) |
| `npm run db:generate` | Generate migrations from `src/db/schema.ts` (drizzle-kit) |
| `npm run db:migrate:local` | Apply migrations to the local D1 database |
| `npm run db:migrate:remote` | Apply migrations to the production D1 database |
| `npm run types` | Regenerate `worker-configuration.d.ts` |

## Configuration

Bindings, cron triggers and variables live in [`wrangler.jsonc`](wrangler.jsonc).

| Name | Type | Description |
|---|---|---|
| `BRAND_NAME` | var | Display brand for the white-label UI (default: `LESCO GNC`) |
| `PBKDF2_ITERATIONS` | var | Password hashing iterations (default: `30000`) |
| `GMAIL_USER` | secret | Gmail address used as SMTP account and default `From` |
| `GMAIL_APP_PASSWORD` | secret | [Gmail app password](https://support.google.com/accounts/answer/185833) for SMTP auth |
| `GMAIL_SMTP_HOST` / `GMAIL_SMTP_PORT` | secret (optional) | SMTP overrides (default: `smtp.gmail.com:465`) |
| `EMAIL_FROM` | secret (optional) | Custom `From` header |

Email features degrade gracefully to a console stub when the Gmail secrets are absent.

## Database & Migrations

- **Source of truth:** [`src/db/schema.ts`](src/db/schema.ts) (Drizzle schema).
- Migrations are generated with `npm run db:generate` into [`migrations/`](migrations/) and applied by Wrangler.
- Production database: **`gas-station-db`** — CI applies pending migrations automatically on every push to `main`.

## Testing

```bash
npm test
```

9 suites / **68 tests** covering authentication, store, shop, admin, operations (*operativa*), panels, ranking and email, running on the Cloudflare `workerd` runtime via `@cloudflare/vitest-plugin`.

## CI/CD

Every push to `main` triggers [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml):

1. Checkout + Node.js 22 with npm caching
2. `npm ci`
3. `npm run typecheck`
4. `npm test`
5. `wrangler d1 migrations apply gas-station-db --remote`
6. `wrangler deploy`

The workflow authenticates with the repository secrets `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID` (scoped to Workers + D1 + R2 edit permissions).

## Project Structure

```
├── src/
│   ├── index.ts            # Hono app entrypoint + cron handler
│   ├── env.ts              # Binding/environment types
│   ├── db/
│   │   ├── schema.ts       # Drizzle schema (single source of truth)
│   │   └── index.ts        # D1 client factory
│   ├── routes/
│   │   ├── auth.ts         # Register, login, password recovery
│   │   ├── app.ts          # Customer panel
│   │   ├── shop.ts         # Catalog, cart, checkout, orders
│   │   ├── operativa.ts    # Shift reports, cash register, daily summary
│   │   ├── admin.ts        # Administration panel
│   │   └── ranking.ts      # Points, leaderboard, challenges
│   ├── services/           # Cart, orders, points, challenges
│   ├── lib/                # Email (SMTP), sessions, crypto, HTML rendering
│   └── middleware/         # Auth/session middleware, branding
├── migrations/             # SQL migrations (drizzle-kit)
├── test/                   # Vitest suites
├── web/                    # Standalone Astro marketing site (Astro + Tailwind)
├── public/                 # Static assets served by the Worker
├── .github/workflows/      # CI/CD pipeline
└── wrangler.jsonc          # Workers configuration (bindings, cron, vars)
```

## Domain Model

28 tables across five domains:

| Domain | Tables |
|---|---|
| Identity & access | `users`, `sessions`, `password_reset_tokens`, `avatars` |
| Commerce | `stores`, `store_user`, `products`, `coupons`, `orders`, `order_items` |
| Loyalty & engagement | `point_logs`, `weekly_challenges`, `challenge_participations`, `announcements`, `notifications`, `motivational_quotes`, `user_activity`, `raffle_coupons` |
| Operations | `cash_days`, `cash_lines`, `sales_summaries`, `daily_activities`, `daily_activity_summary`, `lubricant_stocks`, `shift_reports`, `shift_report_readings` |
| Platform | `settings`, `jobs` |

Roles: **`admin`** · **`store_manager`** (gestor) · **`customer`**.

See [`PLAN.md`](PLAN.md) for the full architecture plan and decision log.

## Legacy Codebase

The platform's origin — a Laravel 11 monolith — is preserved on the [`legacy/laravel`](https://github.com/selvaggiesteban/Gas_Station/tree/legacy/laravel) branch.
