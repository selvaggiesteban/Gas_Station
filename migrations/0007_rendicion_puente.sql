--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `opening_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `closing_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `lubricantes_qty`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `lubricantes_price_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `fondo_cambio_saliente_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `fondo_cambio_entrante_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `vales_empleados_cents`;
--> statement-breakpoint
ALTER TABLE `shift_reports` DROP COLUMN `vales_alerta`;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `puente_entrante` real DEFAULT 0 NOT NULL;
--> statement-breakpoint
ALTER TABLE `shift_reports` ADD COLUMN `puente_saliente` real DEFAULT 0 NOT NULL;
--> statement-breakpoint
DROP INDEX `shift_report_readings_nozzle_uq`;
--> statement-breakpoint
ALTER TABLE `shift_report_readings` DROP COLUMN `nozzle`;
--> statement-breakpoint
CREATE UNIQUE INDEX `shift_report_readings_surtidor_uq` ON `shift_report_readings` (`report_id`,`surtidor`);
