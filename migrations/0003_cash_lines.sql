--> statement-breakpoint
CREATE TABLE `cash_lines` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`store_id` integer NOT NULL,
	`report_date` integer NOT NULL,
	`side` text NOT NULL,
	`sort` integer DEFAULT 0 NOT NULL,
	`detail` text NOT NULL,
	`amount_cents` integer DEFAULT 0 NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
	CONSTRAINT "cash_lines_side_chk" CHECK(side in ('entrada','salida')),
	CONSTRAINT "cash_lines_amount_chk" CHECK(amount_cents >= 0),
	CONSTRAINT "cash_lines_detail_chk" CHECK(length(trim(detail)) > 0)
);
--> statement-breakpoint
CREATE INDEX `cash_lines_store_idx` ON `cash_lines` (`store_id`,`report_date`,`side`,`sort`);
