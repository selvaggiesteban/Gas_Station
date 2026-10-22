# Gas Station

Plataforma white-label mixta (clientes + empleados) para gasolinera — reescrita sobre **Cloudflare Workers Free** (`$0/mes`).

Su código fuente original (Laravel 11, clon de Academicus) está preservado en la rama **`legacy/laravel`**.

## Stack

| Capa | Tecnología |
|---|---|
| Runtime | Cloudflare Workers (Free) |
| HTTP | Hono |
| DB | D1 (SQLite) + Drizzle ORM |
| Storage | R2 (`gas-station-assets`) |
| Cron | Cron Trigger `*/5 * * * *` (runner de `jobs`) |
| Email | Gmail API (OAuth2) — pendiente F1 |
| CI/CD | Workers Builds (GitHub → deploy automático) |

Recursos en la cuenta Cloudflare `793d012a405417ee4382f1ef1869753e`:

- Worker: `gas-station` → <https://gas-station.selvaggi-esteban.workers.dev>
- D1: `gas-station-db` (ENAM)
- R2: `gas-station-assets`

## Comandos

```bash
npm install
npm run dev               # wrangler dev (local)
npm run typecheck         # tsc --noEmit
npm test                  # vitest
npm run db:generate       # drizzle-kit generate (schema.ts -> migrations/)
npm run db:migrate:local  # aplicar migraciones en D1 local
npm run db:migrate:remote # aplicar migraciones en D1 remoto
npm run deploy            # wrangler deploy
npm run types             # regenerar worker-configuration.d.ts
```

## Estructura

```
src/
  index.ts        # Hono app + handler scheduled (cron)
  db/schema.ts    # esquema Drizzle (fuente de verdad)
  db/index.ts     # factory db sobre D1
migrations/       # SQL generado por drizzle-kit, aplicado por wrangler d1
test/             # vitest
wrangler.jsonc    # bindings D1/R2/cron
```

## Modelo de dominio (v1)

20 tablas: usuarios (4 roles: admin, content_manager, store_manager, customer), sesiones, **tiendas + gestores**, productos con stock nullable, cupones, órdenes (con `store_id`/`gestor_id` para atribución de ventas), **batallas por ventas**, desafíos semanales (goals: points, battles_won, login_days, sales_amount), point logs, avatares, anuncios, notificaciones, cotidianas, actividad de usuario, jobs/outbox, settings.

## Entrega

Fases F0–F6 según el *Plan de Refactorización v3* (21/10/2026): auth+marca es-AR, tienda con transferencia/MP-stub, gamificación v2 con batallas por ventas, paneles, cierre.
