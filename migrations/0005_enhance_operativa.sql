--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `gnc_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `gnc_price_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `gnc_sales_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `lubricantes_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `lubricantes_price_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `lubricantes_sales_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `varios_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `varios_price_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `varios_sales_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `total_sales_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_1_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_1_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_2_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_2_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_3_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_3_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_4_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_4_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_5_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_5_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_6_hora` text;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `sobre_6_importe_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `total_ensobrado_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `fondo_cambio_saliente_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `fondo_cambio_entrante_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `vales_empleados_cents` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `vales_alerta` integer DEFAULT 1 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `observaciones` text;
--> statement-breakpoint
ALTER TABLE `lubricant_stocks` ADD COLUMN `recibidos_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `lubricant_stocks` ADD COLUMN `vendidos_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `lubricant_stocks` ADD COLUMN `entregados_qty` integer DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `raffle_coupons` ADD COLUMN `holder_dni` text;
--> statement-breakpoint
ALTER TABLE `raffle_coupons` ADD COLUMN `holder_address` text;
--> statement-breakpoint
CREATE TABLE `daily_activity_summary` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`store_id` integer NOT NULL,
	`report_date` integer NOT NULL,
	`manana_gnc_ingreso` integer DEFAULT 0 NOT NULL,
	`manana_gnc_mermas_excedencias` integer DEFAULT 0 NOT NULL,
	`manana_gnc_porcentaje` real DEFAULT 0 NOT NULL,
	`manana_gnc_ventas_m3` integer DEFAULT 0 NOT NULL,
	`manana_gnc_precio_unitario` integer DEFAULT 0 NOT NULL,
	`manana_gnc_importe_total` integer DEFAULT 0 NOT NULL,
	`manana_lubricantes_varios_importe` integer DEFAULT 0 NOT NULL,
	`manana_facturado_playeros` integer DEFAULT 0 NOT NULL,
	`manana_facturacion_servicios` integer DEFAULT 0 NOT NULL,
	`manana_facturacion_mini_mercado` integer DEFAULT 0 NOT NULL,
	`manana_total_recaudacion` integer DEFAULT 0 NOT NULL,
	`tarde_gnc_ingreso` integer DEFAULT 0 NOT NULL,
	`tarde_gnc_mermas_excedencias` integer DEFAULT 0 NOT NULL,
	`tarde_gnc_porcentaje` real DEFAULT 0 NOT NULL,
	`tarde_gnc_ventas_m3` integer DEFAULT 0 NOT NULL,
	`tarde_gnc_precio_unitario` integer DEFAULT 0 NOT NULL,
	`tarde_gnc_importe_total` integer DEFAULT 0 NOT NULL,
	`tarde_lubricantes_varios_importe` integer DEFAULT 0 NOT NULL,
	`tarde_facturado_playeros` integer DEFAULT 0 NOT NULL,
	`tarde_facturacion_servicios` integer DEFAULT 0 NOT NULL,
	`tarde_facturacion_mini_mercado` integer DEFAULT 0 NOT NULL,
	`tarde_total_recaudacion` integer DEFAULT 0 NOT NULL,
	`noche_gnc_ingreso` integer DEFAULT 0 NOT NULL,
	`noche_gnc_mermas_excedencias` integer DEFAULT 0 NOT NULL,
	`noche_gnc_porcentaje` real DEFAULT 0 NOT NULL,
	`noche_gnc_ventas_m3` integer DEFAULT 0 NOT NULL,
	`noche_gnc_precio_unitario` integer DEFAULT 0 NOT NULL,
	`noche_gnc_importe_total` integer DEFAULT 0 NOT NULL,
	`noche_lubricantes_varios_importe` integer DEFAULT 0 NOT NULL,
	`noche_facturado_playeros` integer DEFAULT 0 NOT NULL,
	`noche_facturacion_servicios` integer DEFAULT 0 NOT NULL,
	`noche_facturacion_mini_mercado` integer DEFAULT 0 NOT NULL,
	`noche_total_recaudacion` integer DEFAULT 0 NOT NULL,
	`adicional_gnc_ingreso` integer DEFAULT 0 NOT NULL,
	`adicional_gnc_mermas_excedencias` integer DEFAULT 0 NOT NULL,
	`adicional_gnc_porcentaje` real DEFAULT 0 NOT NULL,
	`adicional_gnc_ventas_m3` integer DEFAULT 0 NOT NULL,
	`adicional_gnc_precio_unitario` integer DEFAULT 0 NOT NULL,
	`adicional_gnc_importe_total` integer DEFAULT 0 NOT NULL,
	`adicional_lubricantes_varios_importe` integer DEFAULT 0 NOT NULL,
	`adicional_facturado_playeros` integer DEFAULT 0 NOT NULL,
	`adicional_facturacion_servicios` integer DEFAULT 0 NOT NULL,
	`adicional_facturacion_mini_mercado` integer DEFAULT 0 NOT NULL,
	`adicional_total_recaudacion` integer DEFAULT 0 NOT NULL,
	`observaciones` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `daily_activity_summary_store_date_uq` ON `daily_activity_summary` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE INDEX `daily_activity_summary_store_idx` ON `daily_activity_summary` (`store_id`,`report_date`);