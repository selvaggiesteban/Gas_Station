-- Migración 0011: Limpieza de restos del "turno adicional" (sistema viejo, ya no existe)
-- 1. sales_summaries: se recrea la tabla para quitar 'adicional' del CHECK sales_summaries_shift_chk
--    (SQLite no permite modificar un CHECK con ALTER TABLE; obliga a rebuild de la tabla).
-- 2. daily_activities: ídem, conservando el CHECK daily_activities_gnc_chk.
-- 3. daily_activity_summary: se eliminan las 11 columnas adicional_* huérfanas.
-- Se borran además las filas con shift fuera de ('morning','evening','night') (residuo del
-- turno adicional), igual que hizo la migración 0010 con shift_reports (shift = 4), para que
-- la copia de datos respete los CHECK de la tabla nueva.

DELETE FROM sales_summaries WHERE shift NOT IN ('morning','evening','night');
--> statement-breakpoint
CREATE TABLE `sales_summaries_new` (
    `id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
    `store_id` integer NOT NULL,
    `report_date` integer NOT NULL,
    `shift` text NOT NULL,
    `cash_cents` integer DEFAULT 0 NOT NULL,
    `card_cents` integer DEFAULT 0 NOT NULL,
    `transfer_cents` integer DEFAULT 0 NOT NULL,
    `credit_cents` integer DEFAULT 0 NOT NULL,
    `total_cents` integer DEFAULT 0 NOT NULL,
    `notes` text,
    `created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
    `updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
    CONSTRAINT `sales_summaries_shift_chk` CHECK(shift in ('morning','evening','night')),
    CONSTRAINT `sales_summaries_amounts_chk` CHECK(cash_cents >= 0 and card_cents >= 0 and transfer_cents >= 0 and credit_cents >= 0 and total_cents >= 0)
);
--> statement-breakpoint
INSERT INTO `sales_summaries_new` (`id`, `store_id`, `report_date`, `shift`, `cash_cents`, `card_cents`, `transfer_cents`, `credit_cents`, `total_cents`, `notes`, `created_at`, `updated_at`) SELECT `id`, `store_id`, `report_date`, `shift`, `cash_cents`, `card_cents`, `transfer_cents`, `credit_cents`, `total_cents`, `notes`, `created_at`, `updated_at` FROM `sales_summaries`;
--> statement-breakpoint
DROP TABLE `sales_summaries`;
--> statement-breakpoint
ALTER TABLE `sales_summaries_new` RENAME TO `sales_summaries`;
--> statement-breakpoint
CREATE UNIQUE INDEX IF NOT EXISTS `sales_summaries_store_date_shift_uq` ON `sales_summaries` (`store_id`,`report_date`,`shift`);
--> statement-breakpoint
CREATE INDEX IF NOT EXISTS `sales_summaries_store_idx` ON `sales_summaries` (`store_id`,`report_date`);
--> statement-breakpoint
DELETE FROM daily_activities WHERE shift NOT IN ('morning','evening','night');
--> statement-breakpoint
CREATE TABLE `daily_activities_new` (
    `id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
    `store_id` integer NOT NULL,
    `report_date` integer NOT NULL,
    `shift` text NOT NULL,
    `gnc_opening` integer DEFAULT 0 NOT NULL,
    `gnc_closing` integer DEFAULT 0 NOT NULL,
    `gnc_volume` integer DEFAULT 0 NOT NULL,
    `gnc_sales_cents` integer DEFAULT 0 NOT NULL,
    `pump_check` integer DEFAULT false NOT NULL,
    `notes` text,
    `created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
    `updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
    CONSTRAINT `daily_activities_shift_chk` CHECK(shift in ('morning','evening','night')),
    CONSTRAINT `daily_activities_gnc_chk` CHECK(gnc_opening >= 0 and gnc_closing >= 0 and gnc_volume >= 0 and gnc_sales_cents >= 0)
);
--> statement-breakpoint
INSERT INTO `daily_activities_new` (`id`, `store_id`, `report_date`, `shift`, `gnc_opening`, `gnc_closing`, `gnc_volume`, `gnc_sales_cents`, `pump_check`, `notes`, `created_at`, `updated_at`) SELECT `id`, `store_id`, `report_date`, `shift`, `gnc_opening`, `gnc_closing`, `gnc_volume`, `gnc_sales_cents`, `pump_check`, `notes`, `created_at`, `updated_at` FROM `daily_activities`;
--> statement-breakpoint
DROP TABLE `daily_activities`;
--> statement-breakpoint
ALTER TABLE `daily_activities_new` RENAME TO `daily_activities`;
--> statement-breakpoint
CREATE UNIQUE INDEX IF NOT EXISTS `daily_activities_store_date_shift_uq` ON `daily_activities` (`store_id`,`report_date`,`shift`);
--> statement-breakpoint
CREATE INDEX IF NOT EXISTS `daily_activities_store_idx` ON `daily_activities` (`store_id`,`report_date`);
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_ingreso`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_mermas_excedencias`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_porcentaje`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_ventas_m3`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_precio_unitario`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_gnc_importe_total`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_lubricantes_varios_importe`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_facturado_playeros`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_facturacion_servicios`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_facturacion_mini_mercado`;
--> statement-breakpoint
ALTER TABLE `daily_activity_summary` DROP COLUMN `adicional_total_recaudacion`;
