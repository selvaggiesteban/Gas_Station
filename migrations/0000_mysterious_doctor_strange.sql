CREATE TABLE `announcements` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`title` text NOT NULL,
	`body` text NOT NULL,
	`author_id` integer NOT NULL,
	`audience` text DEFAULT 'all' NOT NULL,
	`published_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE no action,
	CONSTRAINT "announcements_audience_chk" CHECK(audience in ('all','customers','store_managers'))
);
--> statement-breakpoint
CREATE INDEX `announcements_published_idx` ON `announcements` (`published_at`);--> statement-breakpoint
CREATE TABLE `avatars` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`name` text NOT NULL,
	`image_key` text,
	`min_level` integer DEFAULT 0 NOT NULL,
	`price_points` integer DEFAULT 0 NOT NULL,
	`active` integer DEFAULT true NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE TABLE `battles` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`challenger_id` integer NOT NULL,
	`opponent_id` integer NOT NULL,
	`challenger_store_id` integer NOT NULL,
	`opponent_store_id` integer NOT NULL,
	`metric` text DEFAULT 'sales' NOT NULL,
	`window_start` integer NOT NULL,
	`window_end` integer NOT NULL,
	`status` text DEFAULT 'pending' NOT NULL,
	`challenger_score` integer DEFAULT 0 NOT NULL,
	`opponent_score` integer DEFAULT 0 NOT NULL,
	`winner_id` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`completed_at` integer,
	FOREIGN KEY (`challenger_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`opponent_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`challenger_store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`opponent_store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`winner_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null,
	CONSTRAINT "battles_window_chk" CHECK(window_end > window_start),
	CONSTRAINT "battles_status_chk" CHECK(status in ('pending','active','declined','completed','cancelled'))
);
--> statement-breakpoint
CREATE INDEX `battles_close_idx` ON `battles` (`status`,`window_end`);--> statement-breakpoint
CREATE INDEX `battles_challenger_idx` ON `battles` (`challenger_id`);--> statement-breakpoint
CREATE INDEX `battles_opponent_idx` ON `battles` (`opponent_id`);--> statement-breakpoint
CREATE TABLE `challenge_participations` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`challenge_id` integer NOT NULL,
	`user_id` integer NOT NULL,
	`progress` integer DEFAULT 0 NOT NULL,
	`completed_at` integer,
	`rewarded_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`challenge_id`) REFERENCES `weekly_challenges`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `challenge_participations_uq` ON `challenge_participations` (`challenge_id`,`user_id`);--> statement-breakpoint
CREATE INDEX `challenge_participations_user_idx` ON `challenge_participations` (`user_id`);--> statement-breakpoint
CREATE TABLE `coupons` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`code` text NOT NULL,
	`type` text NOT NULL,
	`value` integer NOT NULL,
	`min_order_cents` integer DEFAULT 0 NOT NULL,
	`max_uses` integer,
	`used_count` integer DEFAULT 0 NOT NULL,
	`active` integer DEFAULT true NOT NULL,
	`expires_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	CONSTRAINT "coupons_type_chk" CHECK(type in ('percent','fixed')),
	CONSTRAINT "coupons_value_chk" CHECK(value > 0)
);
--> statement-breakpoint
CREATE UNIQUE INDEX `coupons_code_unique` ON `coupons` (`code`);--> statement-breakpoint
CREATE TABLE `jobs` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`type` text NOT NULL,
	`payload` text DEFAULT '{}' NOT NULL,
	`run_at` integer NOT NULL,
	`attempts` integer DEFAULT 0 NOT NULL,
	`status` text DEFAULT 'pending' NOT NULL,
	`last_error` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE INDEX `jobs_ready_idx` ON `jobs` (`status`,`run_at`);--> statement-breakpoint
CREATE TABLE `motivational_quotes` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`text` text NOT NULL,
	`author` text,
	`active` integer DEFAULT true NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE TABLE `notifications` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` integer NOT NULL,
	`type` text NOT NULL,
	`title` text NOT NULL,
	`body` text DEFAULT '' NOT NULL,
	`data` text DEFAULT '{}',
	`read_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `notifications_user_idx` ON `notifications` (`user_id`,`created_at`);--> statement-breakpoint
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
CREATE INDEX `order_items_order_idx` ON `order_items` (`order_id`);--> statement-breakpoint
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
	CONSTRAINT "orders_payment_chk" CHECK(payment_method in ('transferencia','mercadopago')),
	CONSTRAINT "orders_totals_chk" CHECK(subtotal_cents >= 0 and discount_cents >= 0 and total_cents >= 0)
);
--> statement-breakpoint
CREATE UNIQUE INDEX `orders_number_unique` ON `orders` (`number`);--> statement-breakpoint
CREATE INDEX `orders_user_idx` ON `orders` (`user_id`,`created_at`);--> statement-breakpoint
CREATE INDEX `orders_store_status_idx` ON `orders` (`store_id`,`status`);--> statement-breakpoint
CREATE INDEX `orders_gestor_idx` ON `orders` (`gestor_id`);--> statement-breakpoint
CREATE TABLE `password_reset_tokens` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`email` text NOT NULL,
	`token` text NOT NULL,
	`expires_at` integer NOT NULL,
	`used_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE UNIQUE INDEX `password_reset_tokens_token_unique` ON `password_reset_tokens` (`token`);--> statement-breakpoint
CREATE TABLE `point_logs` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` integer NOT NULL,
	`points` integer NOT NULL,
	`reason` text NOT NULL,
	`reference_type` text,
	`reference_id` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade,
	CONSTRAINT "point_logs_reason_chk" CHECK(reason in ('battle_win','challenge','login','order','admin_adjust'))
);
--> statement-breakpoint
CREATE INDEX `point_logs_user_idx` ON `point_logs` (`user_id`,`created_at`);--> statement-breakpoint
CREATE TABLE `products` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`name` text NOT NULL,
	`slug` text NOT NULL,
	`description` text DEFAULT '' NOT NULL,
	`price_cents` integer NOT NULL,
	`image_key` text,
	`stock` integer,
	`category` text DEFAULT 'general' NOT NULL,
	`active` integer DEFAULT true NOT NULL,
	`sort` integer DEFAULT 0 NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	CONSTRAINT "products_price_chk" CHECK(price_cents >= 0)
);
--> statement-breakpoint
CREATE UNIQUE INDEX `products_slug_unique` ON `products` (`slug`);--> statement-breakpoint
CREATE INDEX `products_active_idx` ON `products` (`active`,`sort`);--> statement-breakpoint
CREATE TABLE `sessions` (
	`id` text PRIMARY KEY NOT NULL,
	`user_id` integer NOT NULL,
	`ip` text,
	`user_agent` text,
	`last_seen_at` integer,
	`expires_at` integer NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `sessions_user_idx` ON `sessions` (`user_id`);--> statement-breakpoint
CREATE INDEX `sessions_expires_idx` ON `sessions` (`expires_at`);--> statement-breakpoint
CREATE TABLE `settings` (
	`key` text PRIMARY KEY NOT NULL,
	`value` text NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE TABLE `store_user` (
	`store_id` integer NOT NULL,
	`user_id` integer NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	PRIMARY KEY(`store_id`, `user_id`),
	FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `store_user_user_idx` ON `store_user` (`user_id`);--> statement-breakpoint
CREATE TABLE `stores` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`name` text NOT NULL,
	`zone` text,
	`active` integer DEFAULT true NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL
);
--> statement-breakpoint
CREATE UNIQUE INDEX `stores_name_unique` ON `stores` (`name`);--> statement-breakpoint
CREATE TABLE `user_activity` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` integer NOT NULL,
	`type` text NOT NULL,
	`day` text,
	`meta` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `user_activity_daily_uq` ON `user_activity` (`user_id`,`type`,`day`);--> statement-breakpoint
CREATE INDEX `user_activity_query_idx` ON `user_activity` (`user_id`,`type`,`created_at`);--> statement-breakpoint
CREATE TABLE `users` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`name` text NOT NULL,
	`email` text NOT NULL,
	`email_verified_at` integer,
	`password_hash` text NOT NULL,
	`role` text DEFAULT 'customer' NOT NULL,
	`avatar_id` integer,
	`points` integer DEFAULT 0 NOT NULL,
	`login_streak` integer DEFAULT 0 NOT NULL,
	`last_login_day` text,
	`active` integer DEFAULT true NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`avatar_id`) REFERENCES `avatars`(`id`) ON UPDATE no action ON DELETE set null,
	CONSTRAINT "users_role_chk" CHECK(role in ('admin','content_manager','store_manager','customer'))
);
--> statement-breakpoint
CREATE UNIQUE INDEX `users_email_unique` ON `users` (`email`);--> statement-breakpoint
CREATE INDEX `users_role_idx` ON `users` (`role`);--> statement-breakpoint
CREATE TABLE `weekly_challenges` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`title` text NOT NULL,
	`description` text DEFAULT '' NOT NULL,
	`goal_type` text NOT NULL,
	`goal_value` integer NOT NULL,
	`starts_at` integer NOT NULL,
	`ends_at` integer NOT NULL,
	`reward_points` integer DEFAULT 0 NOT NULL,
	`reward_avatar_id` integer,
	`active` integer DEFAULT true NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`reward_avatar_id`) REFERENCES `avatars`(`id`) ON UPDATE no action ON DELETE set null,
	CONSTRAINT "weekly_challenges_goal_chk" CHECK(goal_type in ('points','battles_won','login_days','sales_amount')),
	CONSTRAINT "weekly_challenges_ends_chk" CHECK(ends_at > starts_at)
);
--> statement-breakpoint
CREATE INDEX `weekly_challenges_window_idx` ON `weekly_challenges` (`active`,`ends_at`);