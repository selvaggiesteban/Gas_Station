-- Migración 0012: método de pago 'efectivo' en órdenes
-- El CHECK orders_payment_chk no se puede alterar en SQLite: rebuild de orders.
-- Ojo: order_items tiene FK a orders con ON DELETE cascade, por eso se renombra
-- en vez de dropear para no arrastrar los ítems, y se recrea order_items al final.

ALTER TABLE `orders` RENAME TO `orders_old`;
--> statement-breakpoint
CREATE TABLE `orders` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`number` text NOT NULL,
	`user_id` integer NOT NULL,
	`store_id` integer NOT NULL,
	`gestor_id` integer,
	`status` text DEFAULT 'pending' NOT NULL,
	`payment_method` text NOT NULL,
	`subtotal_cents` integer NOT NULL,
	`discount_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer NOT NULL,
	`coupon_id` integer,
	`proof_key` text,
	`notes` text,
	`paid_at` integer,
	`approved_at` integer,
	`approved_by` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`gestor_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null,
	CONSTRAINT "orders_status_chk" CHECK(status in ('pending','paid','cancelled','refunded')),
	CONSTRAINT "orders_payment_chk" CHECK(payment_method in ('efectivo','transferencia','mercadopago')),
	CONSTRAINT "orders_totals_chk" CHECK(subtotal_cents >= 0 and discount_cents >= 0 and total_cents >= 0)
);
--> statement-breakpoint
INSERT INTO `orders` (`id`, `number`, `user_id`, `store_id`, `gestor_id`, `status`, `payment_method`, `subtotal_cents`, `discount_cents`, `total_cents`, `coupon_id`, `proof_key`, `notes`, `paid_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
	SELECT `id`, `number`, `user_id`, `store_id`, `gestor_id`, `status`, `payment_method`, `subtotal_cents`, `discount_cents`, `total_cents`, `coupon_id`, `proof_key`, `notes`, `paid_at`, `approved_at`, `approved_by`, `created_at`, `updated_at` FROM `orders_old`;
--> statement-breakpoint
ALTER TABLE `order_items` RENAME TO `order_items_old`;
--> statement-breakpoint
CREATE TABLE `order_items` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`order_id` integer NOT NULL,
	`product_id` integer,
	`qty` integer NOT NULL,
	`unit_price_cents` integer NOT NULL,
	`name_snapshot` text NOT NULL,
	FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null,
	CONSTRAINT "order_items_qty_chk" CHECK(qty > 0)
);
--> statement-breakpoint
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `unit_price_cents`, `name_snapshot`)
	SELECT `id`, `order_id`, `product_id`, `qty`, `unit_price_cents`, `name_snapshot` FROM `order_items_old`;
--> statement-breakpoint
DROP TABLE `order_items_old`;
--> statement-breakpoint
DROP TABLE `orders_old`;
--> statement-breakpoint
CREATE UNIQUE INDEX `orders_number_unique` ON `orders` (`number`);--> statement-breakpoint
CREATE INDEX `orders_user_idx` ON `orders` (`user_id`,`created_at`);--> statement-breakpoint
CREATE INDEX `orders_store_status_idx` ON `orders` (`store_id`,`status`);--> statement-breakpoint
CREATE INDEX `orders_gestor_idx` ON `orders` (`gestor_id`);--> statement-breakpoint
CREATE INDEX `order_items_order_idx` ON `order_items` (`order_id`);
