--> statement-breakpoint
CREATE TABLE `cash_days` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`store_id` integer NOT NULL,
	`report_date` integer NOT NULL,
	`opening_cents` integer DEFAULT 0 NOT NULL,
	`closing_declared_cents` integer,
	`closing_counted_cents` integer,
	`difference_cents` integer,
	`notes` text,
	`status` text DEFAULT 'open' NOT NULL,
	`opened_by` integer,
	`closed_by` integer,
	`closed_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`opened_by`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`closed_by`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `cash_days_store_date_uq` ON `cash_days` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE INDEX `cash_days_store_idx` ON `cash_days` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE TABLE `sales_summaries` (
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
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `sales_summaries_store_date_shift_uq` ON `sales_summaries` (`store_id`,`report_date`,`shift`);
--> statement-breakpoint
CREATE INDEX `sales_summaries_store_idx` ON `sales_summaries` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE TABLE `daily_activities` (
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
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `daily_activities_store_date_shift_uq` ON `daily_activities` (`store_id`,`report_date`,`shift`);
--> statement-breakpoint
CREATE INDEX `daily_activities_store_idx` ON `daily_activities` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE TABLE `lubricant_stocks` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`store_id` integer NOT NULL,
	`report_date` integer NOT NULL,
	`product_id` integer,
	`brand` text NOT NULL,
	`grade` text NOT NULL,
	`container_l` real NOT NULL,
	`opening_qty` integer DEFAULT 0 NOT NULL,
	`entries_qty` integer DEFAULT 0 NOT NULL,
	`exits_qty` integer DEFAULT 0 NOT NULL,
	`closing_qty` integer DEFAULT 0 NOT NULL,
	`unit_price_cents` integer DEFAULT 0 NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `lubricant_stocks_store_idx` ON `lubricant_stocks` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE TABLE `shift_reports` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`store_id` integer NOT NULL,
	`report_date` integer NOT NULL,
	`shift` text NOT NULL,
	`shift_start` integer NOT NULL,
	`shift_end` integer NOT NULL,
	`cashier_id` integer,
	`opening_cents` integer DEFAULT 0 NOT NULL,
	`closing_cents` integer DEFAULT 0 NOT NULL,
	`notes` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `shift_reports_store_date_shift_uq` ON `shift_reports` (`store_id`,`report_date`,`shift`);
--> statement-breakpoint
CREATE INDEX `shift_reports_store_idx` ON `shift_reports` (`store_id`,`report_date`);
--> statement-breakpoint
CREATE INDEX `shift_reports_cashier_idx` ON `shift_reports` (`cashier_id`);
--> statement-breakpoint
CREATE TABLE `shift_report_readings` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`report_id` integer NOT NULL,
	`surtidor` integer NOT NULL,
	`nozzle` integer NOT NULL,
	`product` text NOT NULL,
	`opening_reading` real DEFAULT 0 NOT NULL,
	`closing_reading` real DEFAULT 0 NOT NULL,
	`volume` real DEFAULT 0 NOT NULL,
	`price_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	FOREIGN KEY (`report_id`) REFERENCES `shift_reports`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `shift_report_readings_report_idx` ON `shift_report_readings` (`report_id`);
--> statement-breakpoint
CREATE UNIQUE INDEX `shift_report_readings_nozzle_uq` ON `shift_report_readings` (`report_id`,`surtidor`,`nozzle`);
--> statement-breakpoint
CREATE TABLE `raffle_coupons` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`code` text NOT NULL,
	`store_id` integer NOT NULL,
	`holder_name` text NOT NULL,
	`holder_email` text,
	`holder_phone` text,
	`issued_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`drawn_at` integer,
	`winning` integer DEFAULT false NOT NULL,
	`notes` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `raffle_coupons_code_uq` ON `raffle_coupons` (`code`);
--> statement-breakpoint
CREATE INDEX `raffle_coupons_store_idx` ON `raffle_coupons` (`store_id`,`issued_at`);
