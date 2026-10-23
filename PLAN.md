# Gas Station — Plan de migración (v3)

## 1. Objetivo

- Migrar la app Laravel "Academicus" a `github.com/selvaggiesteban/Gas_Station` como app **white-label** (default **"Gas Station")**, reescrita de cero sobre **Cloudflare Free** (Workers + D1 + R2), en **es-AR** (voseo, ARS), recortando el dominio académico.
- App **mixta** (clientes + empleados) de gasolinera: catálogo de productos, órdenes con checkout por transferencia, desafíos semanales, y batallas 1:1 entre gestores de tienda.

## 2. Decisiones (D1–D17)

- **D1**: deploy 100% Cloudflare Free, reescritura aprobada.
- **D2**: sin dominio propio, se usa `*.workers.dev`.
- **D3**: email vía Gmail API (pendiente de credenciales; hoy stub que loguea).
- **D4**: checkout = transferencia (comprobante a R2 + aprobación admin) + Mercado Pago stub "próximamente".
- **D5**: precios ARS visibles.
- **D6**: suscripciones/planes eliminadas; la tienda vende productos sueltos.
- **D7**: rol Profesor eliminado.
- **D8**: Progreso/Certificados/Técnicas de estudio eliminados.
- **D10**: batallas rediseñadas = competencia 1:1 entre gestores por ventas de la tienda online (Orders).
- **D11**: entidad Store + gestores.
- **D12**: 4 roles: `admin`, `content_manager`, `store_manager`, `customer`.
- **D13**: batallas solo visibles para gestores (clientes NO las ven).
- **D14**: puntos = batallas ganadas + desafíos + login diario.
- **D16**: es-AR completo (voseo, ARS, formatos locales).
- **D17**: marca configurable (`var BRAND_NAME`, default "Gas Station").

## 3. Respuestas del §10

- **Atribución de ventas**: `orders.store_id` + `orders.gestor_id`. Auto-asignación si la tienda tiene 1 solo gestor; si hay varios, el gestor "reclama" la orden; admin puede reasignar. Batallas entre gestores de la misma tienda SÍ son válidas.
- **Goal `sales_amount` EN v1.** Total 4 tipos de meta: `points`, `battles_won`, `login_days`, `sales_amount`. Para gestores = ventas atribuidas (`gestor_id`) en la ventana; para clientes = monto de sus órdenes pagas.
- **Framework HTTP**: Hono.
- **Marcador de batallas en vivo**: polling cada 30s + Cache API `smax-age=30` (parcial).
- **Stock**: nullable en v1 (= ilimitado). Decremento con UPDATE condicional anti-race (`WHERE stock IS NULL OR stock >= :qty`).

## 4. Stack

- **Runtime**: Cloudflare Workers, Hono, Drizzle ORM + D1, R2 para assets/comprobantes.
- **Cron Trigger** `*/5 * * * *` con tabla `jobs`/outbox para trabajos diferidos.
- **Tests**: Vitest + `@cloudflare/vitest-plugin` (pool Workers, migraciones vía `globalSetup`/`provide`).
- **CI/CD**: Workers Builds (pendiente de scope `workers-ci.write` en el MCP).
- **Públicas (marketing/landing)**: Astro + Tailwind en fases posteriores.
- **Auth**: PBKDF2-SHA256 30 000 iteraciones (configurable `PBKDF2_ITERATIONS`; se verificó que no choca con el límite de CPU de 10 ms del plan Free).

## 5. Esquema de datos

Resumen de las 20 tablas:

- `users` (4 roles), `sessions`, `password_reset_tokens`
- `stores`, `store_user`
- `products` (stock nullable), `coupons`
- `orders` (`store_id`, `gestor_id`, status `pending`/`paid`/`cancelled`/`refunded`, payment `transferencia`|`mercadopago`, proofKey en R2), `order_items`
- `battles`
- `weekly_challenges` (4 `goal_type`), `challenge_participations`
- `point_logs`
- `avatars`, `announcements`, `notifications`, `motivational_quotes`
- `user_activity`, `jobs`, `settings`

## 6. Estado actual

- **F0** (scaffold Hono+Drizzle+D1+R2+esquema) — **COMPLETADO**, commit `ccc8b26`.
- **F1a** (auth core: login/registro/logout/recuperación, sesiones cookie, roles, puntos de login diario, landing, `/app` placeholder, `/admin` placeholder) — **COMPLETADO**, commit `002d412`. Tests 9/9 verdes. Deploy verificado en producción incl. smoke de registro.

## 7. Fases restantes (orden tentativo)

- **F1b**: panel `/app` (dashboard puntos/racha, perfil editable, desafíos semanales con unirse/progreso/recompensa, anuncios).
- **F2**: tienda (catálogo, carrito cookie, checkout transferencia con comprobante a R2, cupones, órdenes del cliente, stock condicional, Mercado Pago stub).
- **F3**: batallas 1:1 entre gestores (challenge/accept/decline, ventana, cierre por cron, puntos) + ranking.
- **F4**: email real vía Gmail API (reemplaza stub).
- **F5**: panel admin (usuarios/roles, tiendas+gestores, desafíos CRUD, aprobación de órdenes/comprobantes, anuncios).
- **F6**: públicas con Astro + Tailwind (landing/precios).
- **F7**: CI/CD Workers Builds + limpieza de endpoints de diagnóstico (`/__bench`, `/__time`, `/__cpu`).

## 8. Pendientes del usuario

1. Reconectar MCP de Cloudflare con scope `workers-ci.write` (desbloquea Workers Builds).
2. Hacer el repo privado (Settings → Danger Zone).
3. Cambiar default branch a `main` (hoy `legacy/laravel`).
4. Rotar el PAT de GitHub que quedó expuesto en el chat.

## 9. Recursos Cloudflare

- **Account id**: `793d012a405417ee4382f1ef1869753e`.
- **D1** `gas-station-db` (id `77eb09cf-d0c0-495b-af8a-c7e0107398ea`); **R2** `gas-station-assets`.
- **Worker** `gas-station`, URL: https://gas-station.selvaggi-esteban.workers.dev.
- **Repo**: github.com/selvaggiesteban/Gas_Station (rama `main` = código nuevo; `legacy/laravel` = baseline Laravel).


---

## 10. Actualización de alcance (post-F1b/F2/F5)

Decisiones nuevas que reemplazan lo definido arriba:

1. **D10 / F3 — batallas 1:1 ELIMINADAS.** No existe el feature de batallas
   entre gestores. La gamificacación se mantiene y el **ranking / competencia
   única es la cantidad de puntos acumulados**.
   - Eliminado: tabla `battles` (migración `0001_remove_battles.sql`),
     `BATTLE_STATUS_VALUES`, el valor `battles_won` de `GOAL_TYPE_VALUES`, el
     valor `battle_win` de `POINT_REASON_VALUES`, el progreso de desafíos
     vinculado a batallas y los labels en admin/panel.
   - D13 pasa a: batallas no existen para ningún rol. D14: puntos =
     desafíos + login diario + órdenes + ajustes admin.
   - Los CHECK de la base se mantienen como superconjunto (recrear tablas en
     SQLite con foreign_keys activas borraría filas hijas en cascada); la app
     ya no escribe esos valores.
   - Nuevo F3': **ranking de puntos** (`/ranking`), stub ya montado.
2. **Operativa (Atomic Design, nuevos módulos).** Del desglose de los 6
   formularios en papel de la gasolinera derivan: Caja diaria, Resumen de
   ventas / Ensobrado, Resumen diario de actividades (matriz GNC por turnos),
   Stock de lubricantes, Rendición de turno (surtidores 1-6) y Cupón de
   sorteo. Stub ya montado en `/operativa`.
3. **F7**: los endpoints de diagnóstico (`/__bench`, `/__time`, `/__cpu`)
   **ya fueron eliminados** en `src/index.ts`.
4. **Pendientes del usuario actualizados**:
   - ✅ Repo: se queda **público** (decisión del usuario, no hacer nada).
   - ✅ PAT: se usa tal cual, **no rotar**.
   - ❌ Default branch → `main`: la PAT no tiene scope `Administration: write`
     (GitHub responde "Resource not accessible"). Requiere acción manual del
     usuario en la UI o ampliar el scope de la PAT.
   - ⏳ Reconectar MCP Cloudflare con `workers-ci.write` (Workers Builds).
   - ✅ Usuario admin de producción creado: `admin@gas-station.test`.
   - ⏳ Gmail real (F4) con las credenciales provistas.
