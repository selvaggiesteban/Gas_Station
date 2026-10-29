import { sql } from 'drizzle-orm';
import {
  check,
  index,
  integer,
  primaryKey,
  real,
  sqliteTable,
  text,
  uniqueIndex,
} from 'drizzle-orm/sqlite-core';

const createdAt = () =>
  integer('created_at', { mode: 'timestamp_ms' })
    .notNull()
    .default(sql`(unixepoch() * 1000)`);

const updatedAt = () =>
  integer('updated_at', { mode: 'timestamp_ms' })
    .notNull()
    .default(sql`(unixepoch() * 1000)`)
    .$onUpdateFn(() => new Date());

export const ROLE_VALUES = [
  'admin',
  'store_manager',
  'customer',
] as const;

export const ORDER_STATUS_VALUES = [
  'pending',
  'paid',
  'cancelled',
  'refunded',
] as const;

export const PAYMENT_METHOD_VALUES = [
  'transferencia',
  'mercadopago',
] as const;

export const GOAL_TYPE_VALUES = [
  'points',
  'login_days',
  'sales_amount',
] as const;

export const COUPON_TYPE_VALUES = ['percent', 'fixed'] as const;

export const ANNOUNCEMENT_AUDIENCE_VALUES = [
  'all',
  'customers',
  'store_managers',
] as const;

export const POINT_REASON_VALUES = [
  'challenge',
  'login',
  'order',
  'admin_adjust',
] as const;

export const avatars = sqliteTable('avatars', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(),
  imageKey: text('image_key'),
  minLevel: integer('min_level').notNull().default(0),
  pricePoints: integer('price_points').notNull().default(0),
  active: integer('active', { mode: 'boolean' }).notNull().default(true),
  createdAt: createdAt(),
});

export const users = sqliteTable(
  'users',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    name: text('name').notNull(),
    email: text('email').notNull().unique(),
    emailVerifiedAt: integer('email_verified_at', { mode: 'timestamp_ms' }),
    passwordHash: text('password_hash').notNull(),
    role: text('role', { enum: ROLE_VALUES }).notNull().default('customer'),
    avatarId: integer('avatar_id').references(() => avatars.id, {
      onDelete: 'set null',
    }),
    points: integer('points').notNull().default(0),
    loginStreak: integer('login_streak').notNull().default(0),
    lastLoginDay: text('last_login_day'),
    active: integer('active', { mode: 'boolean' }).notNull().default(true),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    roleIdx: index('users_role_idx').on(t.role),
    roleChk: check(
      'users_role_chk',
      sql`role in ('admin','store_manager','customer')`,
    ),
  }),
);

export const sessions = sqliteTable(
  'sessions',
  {
    id: text('id').primaryKey(),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    ip: text('ip'),
    userAgent: text('user_agent'),
    lastSeenAt: integer('last_seen_at', { mode: 'timestamp_ms' }),
    expiresAt: integer('expires_at', { mode: 'timestamp_ms' }).notNull(),
    createdAt: createdAt(),
  },
  (t) => ({
    userIdx: index('sessions_user_idx').on(t.userId),
    expiresIdx: index('sessions_expires_idx').on(t.expiresAt),
  }),
);

export const passwordResetTokens = sqliteTable('password_reset_tokens', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  email: text('email').notNull(),
  token: text('token').notNull().unique(),
  expiresAt: integer('expires_at', { mode: 'timestamp_ms' }).notNull(),
  usedAt: integer('used_at', { mode: 'timestamp_ms' }),
  createdAt: createdAt(),
});

export const jobs = sqliteTable(
  'jobs',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    type: text('type').notNull(),
    payload: text('payload').notNull().default('{}'),
    runAt: integer('run_at', { mode: 'timestamp_ms' }).notNull(),
    attempts: integer('attempts').notNull().default(0),
    status: text('status', { enum: ['pending', 'done', 'failed'] as const })
      .notNull()
      .default('pending'),
    lastError: text('last_error'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    readyIdx: index('jobs_ready_idx').on(t.status, t.runAt),
  }),
);

export const settings = sqliteTable('settings', {
  key: text('key').primaryKey(),
  value: text('value').notNull(),
  updatedAt: updatedAt(),
});

export const stores = sqliteTable('stores', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull().unique(),
  zone: text('zone'),
  active: integer('active', { mode: 'boolean' }).notNull().default(true),
  createdAt: createdAt(),
  updatedAt: updatedAt(),
});

export const storeUser = sqliteTable(
  'store_user',
  {
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    createdAt: createdAt(),
  },
  (t) => ({
    pk: primaryKey({ columns: [t.storeId, t.userId] }),
    userIdx: index('store_user_user_idx').on(t.userId),
  }),
);

export const products = sqliteTable(
  'products',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    name: text('name').notNull(),
    slug: text('slug').notNull().unique(),
    description: text('description').notNull().default(''),
    priceCents: integer('price_cents').notNull(),
    imageKey: text('image_key'),
    stock: integer('stock'),
    category: text('category').notNull().default('general'),
    active: integer('active', { mode: 'boolean' }).notNull().default(true),
    sort: integer('sort').notNull().default(0),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    activeIdx: index('products_active_idx').on(t.active, t.sort),
    priceChk: check('products_price_chk', sql`price_cents >= 0`),
  }),
);

export const coupons = sqliteTable(
  'coupons',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    code: text('code').notNull().unique(),
    type: text('type', { enum: COUPON_TYPE_VALUES }).notNull(),
    value: integer('value').notNull(),
    minOrderCents: integer('min_order_cents').notNull().default(0),
    maxUses: integer('max_uses'),
    usedCount: integer('used_count').notNull().default(0),
    active: integer('active', { mode: 'boolean' }).notNull().default(true),
    expiresAt: integer('expires_at', { mode: 'timestamp_ms' }),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    typeChk: check('coupons_type_chk', sql`type in ('percent','fixed')`),
    valueChk: check('coupons_value_chk', sql`value > 0`),
  }),
);

export const orders = sqliteTable(
  'orders',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    number: text('number').notNull().unique(),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id),
    gestorId: integer('gestor_id').references(() => users.id, {
      onDelete: 'set null',
    }),
    status: text('status', { enum: ORDER_STATUS_VALUES })
      .notNull()
      .default('pending'),
    paymentMethod: text('payment_method', { enum: PAYMENT_METHOD_VALUES })
      .notNull(),
    subtotalCents: integer('subtotal_cents').notNull(),
    discountCents: integer('discount_cents').notNull().default(0),
    totalCents: integer('total_cents').notNull(),
    couponId: integer('coupon_id').references(() => coupons.id, {
      onDelete: 'set null',
    }),
    proofKey: text('proof_key'),
    notes: text('notes'),
    paidAt: integer('paid_at', { mode: 'timestamp_ms' }),
    approvedAt: integer('approved_at', { mode: 'timestamp_ms' }),
    approvedBy: integer('approved_by').references(() => users.id, {
      onDelete: 'set null',
    }),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    userIdx: index('orders_user_idx').on(t.userId, t.createdAt),
    storeStatusIdx: index('orders_store_status_idx').on(t.storeId, t.status),
    gestorIdx: index('orders_gestor_idx').on(t.gestorId),
    statusChk: check(
      'orders_status_chk',
      sql`status in ('pending','paid','cancelled','refunded')`,
    ),
    paymentChk: check(
      'orders_payment_chk',
      sql`payment_method in ('transferencia','mercadopago')`,
    ),
    totalsChk: check(
      'orders_totals_chk',
      sql`subtotal_cents >= 0 and discount_cents >= 0 and total_cents >= 0`,
    ),
  }),
);

export const orderItems = sqliteTable(
  'order_items',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    orderId: integer('order_id')
      .notNull()
      .references(() => orders.id, { onDelete: 'cascade' }),
    productId: integer('product_id').references(() => products.id, {
      onDelete: 'set null',
    }),
    qty: integer('qty').notNull(),
    unitPriceCents: integer('unit_price_cents').notNull(),
    nameSnapshot: text('name_snapshot').notNull(),
  },
  (t) => ({
    orderIdx: index('order_items_order_idx').on(t.orderId),
    qtyChk: check('order_items_qty_chk', sql`qty > 0`),
  }),
);

export const weeklyChallenges = sqliteTable(
  'weekly_challenges',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    title: text('title').notNull(),
    description: text('description').notNull().default(''),
    goalType: text('goal_type', { enum: GOAL_TYPE_VALUES }).notNull(),
    goalValue: integer('goal_value').notNull(),
    startsAt: integer('starts_at', { mode: 'timestamp_ms' }).notNull(),
    endsAt: integer('ends_at', { mode: 'timestamp_ms' }).notNull(),
    rewardPoints: integer('reward_points').notNull().default(0),
    rewardAvatarId: integer('reward_avatar_id').references(() => avatars.id, {
      onDelete: 'set null',
    }),
    active: integer('active', { mode: 'boolean' }).notNull().default(true),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    windowIdx: index('weekly_challenges_window_idx').on(t.active, t.endsAt),
    goalChk: check(
      'weekly_challenges_goal_chk',
      sql`goal_type in ('points','battles_won','login_days','sales_amount')`,
    ),
    windowCheck: check('weekly_challenges_ends_chk', sql`ends_at > starts_at`),
  }),
);

export const challengeParticipations = sqliteTable(
  'challenge_participations',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    challengeId: integer('challenge_id')
      .notNull()
      .references(() => weeklyChallenges.id, { onDelete: 'cascade' }),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    progress: integer('progress').notNull().default(0),
    completedAt: integer('completed_at', { mode: 'timestamp_ms' }),
    rewardedAt: integer('rewarded_at', { mode: 'timestamp_ms' }),
    createdAt: createdAt(),
  },
  (t) => ({
    uq: uniqueIndex('challenge_participations_uq').on(
      t.challengeId,
      t.userId,
    ),
    userIdx: index('challenge_participations_user_idx').on(t.userId),
  }),
);

export const pointLogs = sqliteTable(
  'point_logs',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    points: integer('points').notNull(),
    reason: text('reason', { enum: POINT_REASON_VALUES }).notNull(),
    referenceType: text('reference_type'),
    referenceId: integer('reference_id'),
    createdAt: createdAt(),
  },
  (t) => ({
    userIdx: index('point_logs_user_idx').on(t.userId, t.createdAt),
    reasonChk: check(
      'point_logs_reason_chk',
      sql`reason in ('battle_win','challenge','login','order','admin_adjust')`,
    ),
  }),
);

export const announcements = sqliteTable(
  'announcements',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    title: text('title').notNull(),
    body: text('body').notNull(),
    authorId: integer('author_id')
      .notNull()
      .references(() => users.id),
    audience: text('audience', { enum: ANNOUNCEMENT_AUDIENCE_VALUES })
      .notNull()
      .default('all'),
    publishedAt: integer('published_at', { mode: 'timestamp_ms' }),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    publishedIdx: index('announcements_published_idx').on(t.publishedAt),
    audienceChk: check(
      'announcements_audience_chk',
      sql`audience in ('all','customers','store_managers')`,
    ),
  }),
);

export const notifications = sqliteTable(
  'notifications',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    type: text('type').notNull(),
    title: text('title').notNull(),
    body: text('body').notNull().default(''),
    data: text('data').default('{}'),
    readAt: integer('read_at', { mode: 'timestamp_ms' }),
    createdAt: createdAt(),
  },
  (t) => ({
    userIdx: index('notifications_user_idx').on(t.userId, t.createdAt),
  }),
);

export const motivationalQuotes = sqliteTable('motivational_quotes', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  text: text('text').notNull(),
  author: text('author'),
  active: integer('active', { mode: 'boolean' }).notNull().default(true),
  createdAt: createdAt(),
});

export const userActivity = sqliteTable(
  'user_activity',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    userId: integer('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    type: text('type').notNull(),
    day: text('day'),
    meta: text('meta'),
    createdAt: createdAt(),
  },
  (t) => ({
    dailyUq: uniqueIndex('user_activity_daily_uq').on(
      t.userId,
      t.type,
      t.day,
    ),
    queryIdx: index('user_activity_query_idx').on(
      t.userId,
      t.type,
      t.createdAt,
    ),
  }),
);

export const SHIFT_VALUES = ['morning', 'evening', 'night'] as const;

export const CASH_STATUS_VALUES = ['open', 'closed'] as const;

export const CASH_SIDE_VALUES = ['entrada', 'salida'] as const;

export const FUEL_PRODUCT_VALUES = ['nafta', 'gasoil', 'diesel', 'gnc'] as const;

export const cashDays = sqliteTable(
  'cash_days',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    openingCents: integer('opening_cents').notNull().default(0),
    closingDeclaredCents: integer('closing_declared_cents'),
    closingCountedCents: integer('closing_counted_cents'),
    differenceCents: integer('difference_cents'),
    notes: text('notes'),
    status: text('status', { enum: CASH_STATUS_VALUES })
      .notNull()
      .default('open'),
    openedBy: integer('opened_by').references(() => users.id, {
      onDelete: 'set null',
    }),
    closedBy: integer('closed_by').references(() => users.id, {
      onDelete: 'set null',
    }),
    closedAt: integer('closed_at', { mode: 'timestamp_ms' }),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeDateUq: uniqueIndex('cash_days_store_date_uq').on(
      t.storeId,
      t.reportDate,
    ),
    storeIdx: index('cash_days_store_idx').on(t.storeId, t.reportDate),
    statusChk: check(
      'cash_days_status_chk',
      sql`status in ('open','closed')`,
    ),
    openingChk: check('cash_days_opening_chk', sql`opening_cents >= 0`),
  }),
);

export const cashLines = sqliteTable(
  'cash_lines',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    side: text('side', { enum: CASH_SIDE_VALUES }).notNull(),
    sort: integer('sort').notNull().default(0),
    detail: text('detail').notNull(),
    amountCents: integer('amount_cents').notNull().default(0),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeIdx: index('cash_lines_store_idx').on(
      t.storeId,
      t.reportDate,
      t.side,
      t.sort,
    ),
    sideChk: check('cash_lines_side_chk', sql`side in ('entrada','salida')`),
    amountChk: check('cash_lines_amount_chk', sql`amount_cents >= 0`),
    detailChk: check('cash_lines_detail_chk', sql`length(trim(detail)) > 0`),
  }),
);

export const salesSummaries = sqliteTable(
  'sales_summaries',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    shift: text('shift', { enum: SHIFT_VALUES }).notNull(),
    cashCents: integer('cash_cents').notNull().default(0),
    cardCents: integer('card_cents').notNull().default(0),
    transferCents: integer('transfer_cents').notNull().default(0),
    creditCents: integer('credit_cents').notNull().default(0),
    totalCents: integer('total_cents').notNull().default(0),
    notes: text('notes'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeDateShiftUq: uniqueIndex('sales_summaries_store_date_shift_uq').on(
      t.storeId,
      t.reportDate,
      t.shift,
    ),
    storeIdx: index('sales_summaries_store_idx').on(t.storeId, t.reportDate),
    shiftChk: check(
      'sales_summaries_shift_chk',
      sql`shift in ('morning','evening','night')`,
    ),
    amountsChk: check(
      'sales_summaries_amounts_chk',
      sql`cash_cents >= 0 and card_cents >= 0 and transfer_cents >= 0 and credit_cents >= 0 and total_cents >= 0`,
    ),
  }),
);

export const dailyActivities = sqliteTable(
  'daily_activities',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    shift: text('shift', { enum: SHIFT_VALUES }).notNull(),
    gncOpening: integer('gnc_opening').notNull().default(0),
    gncClosing: integer('gnc_closing').notNull().default(0),
    gncVolume: integer('gnc_volume').notNull().default(0),
    gncSalesCents: integer('gnc_sales_cents').notNull().default(0),
    pumpCheck: integer('pump_check', { mode: 'boolean' }).notNull().default(false),
    notes: text('notes'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeDateShiftUq: uniqueIndex('daily_activities_store_date_shift_uq').on(
      t.storeId,
      t.reportDate,
      t.shift,
    ),
    storeIdx: index('daily_activities_store_idx').on(t.storeId, t.reportDate),
    shiftChk: check(
      'daily_activities_shift_chk',
      sql`shift in ('morning','evening','night')`,
    ),
    gncChk: check(
      'daily_activities_gnc_chk',
      sql`gnc_opening >= 0 and gnc_closing >= 0 and gnc_volume >= 0 and gnc_sales_cents >= 0`,
    ),
  }),
);

export const lubricantStocks = sqliteTable(
  'lubricant_stocks',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    productId: integer('product_id').references(() => products.id, {
      onDelete: 'set null',
    }),
    brand: text('brand').notNull(),
    grade: text('grade').notNull(),
    containerL: real('container_l').notNull(),
    openingQty: integer('opening_qty').notNull().default(0),
    entriesQty: integer('entries_qty').notNull().default(0),
    exitsQty: integer('exits_qty').notNull().default(0),
    closingQty: integer('closing_qty').notNull().default(0),
    unitPriceCents: integer('unit_price_cents').notNull().default(0),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeIdx: index('lubricant_stocks_store_idx').on(t.storeId, t.reportDate),
    containerChk: check('lubricant_stocks_container_chk', sql`container_l > 0`),
    qtyChk: check(
      'lubricant_stocks_qty_chk',
      sql`opening_qty >= 0 and entries_qty >= 0 and exits_qty >= 0 and closing_qty >= 0`,
    ),
    priceChk: check(
      'lubricant_stocks_price_chk',
      sql`unit_price_cents >= 0`,
    ),
  }),
);

export const shiftReports = sqliteTable(
  'shift_reports',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    reportDate: integer('report_date', { mode: 'timestamp_ms' }).notNull(),
    shift: text('shift', { enum: SHIFT_VALUES }).notNull(),
    shiftStart: integer('shift_start', { mode: 'timestamp_ms' }).notNull(),
    shiftEnd: integer('shift_end', { mode: 'timestamp_ms' }).notNull(),
    cashierId: integer('cashier_id').references(() => users.id, {
      onDelete: 'set null',
    }),
    openingCents: integer('opening_cents').notNull().default(0),
    closingCents: integer('closing_cents').notNull().default(0),
    notes: text('notes'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeDateShiftUq: uniqueIndex('shift_reports_store_date_shift_uq').on(
      t.storeId,
      t.reportDate,
      t.shift,
    ),
    storeIdx: index('shift_reports_store_idx').on(t.storeId, t.reportDate),
    cashierIdx: index('shift_reports_cashier_idx').on(t.cashierId),
    shiftChk: check(
      'shift_reports_shift_chk',
      sql`shift in ('morning','evening','night')`,
    ),
    windowChk: check('shift_reports_window_chk', sql`shift_end > shift_start`),
    cashChk: check(
      'shift_reports_cash_chk',
      sql`opening_cents >= 0 and closing_cents >= 0`,
    ),
  }),
);

export const shiftReportReadings = sqliteTable(
  'shift_report_readings',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    reportId: integer('report_id')
      .notNull()
      .references(() => shiftReports.id, { onDelete: 'cascade' }),
    surtidor: integer('surtidor').notNull(),
    nozzle: integer('nozzle').notNull(),
    product: text('product', { enum: FUEL_PRODUCT_VALUES }).notNull(),
    openingReading: real('opening_reading').notNull().default(0),
    closingReading: real('closing_reading').notNull().default(0),
    volume: real('volume').notNull().default(0),
    priceCents: integer('price_cents').notNull().default(0),
    totalCents: integer('total_cents').notNull().default(0),
  },
  (t) => ({
    reportIdx: index('shift_report_readings_report_idx').on(t.reportId),
    nozzleUq: uniqueIndex('shift_report_readings_nozzle_uq').on(
      t.reportId,
      t.surtidor,
      t.nozzle,
    ),
    surtidorChk: check(
      'shift_report_readings_surtidor_chk',
      sql`surtidor between 1 and 6`,
    ),
    nozzleChk: check(
      'shift_report_readings_nozzle_chk',
      sql`nozzle between 1 and 6`,
    ),
    productChk: check(
      'shift_report_readings_product_chk',
      sql`product in ('nafta','gasoil','diesel','gnc')`,
    ),
    readingChk: check(
      'shift_report_readings_reading_chk',
      sql`opening_reading >= 0 and closing_reading >= 0 and volume >= 0`,
    ),
    totalChk: check(
      'shift_report_readings_total_chk',
      sql`price_cents >= 0 and total_cents >= 0`,
    ),
  }),
);

export const raffleCoupons = sqliteTable(
  'raffle_coupons',
  {
    id: integer('id').primaryKey({ autoIncrement: true }),
    code: text('code').notNull().unique(),
    storeId: integer('store_id')
      .notNull()
      .references(() => stores.id, { onDelete: 'cascade' }),
    holderName: text('holder_name').notNull(),
    holderEmail: text('holder_email'),
    holderPhone: text('holder_phone'),
    issuedAt: integer('issued_at', { mode: 'timestamp_ms' })
      .notNull()
      .default(sql`(unixepoch() * 1000)`),
    drawnAt: integer('drawn_at', { mode: 'timestamp_ms' }),
    winning: integer('winning', { mode: 'boolean' }).notNull().default(false),
    notes: text('notes'),
    createdAt: createdAt(),
    updatedAt: updatedAt(),
  },
  (t) => ({
    storeIdx: index('raffle_coupons_store_idx').on(t.storeId, t.issuedAt),
  }),
);
