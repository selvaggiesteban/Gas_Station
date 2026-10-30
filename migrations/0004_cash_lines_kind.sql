--> statement-breakpoint
ALTER TABLE `cash_lines` ADD COLUMN `kind` text NOT NULL DEFAULT 'movimiento';
--> statement-breakpoint
UPDATE `cash_lines` SET `kind` = 'movimiento';
--> statement-breakpoint
CREATE INDEX `cash_lines_kind_idx` ON `cash_lines` (`kind`);