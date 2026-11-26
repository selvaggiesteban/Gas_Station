-- Migración 0013: Fix turno noche y sobres
-- 1. Quitar CHECK shift_end > shift_start (permite turno noche 22:00 -> 06:00 día siguiente)
-- 2. Dropear columnas sobre_{i}_hora (no se usan, solo importe)

-- SQLite no soporta DROP CONSTRAINT directo, hay que recrear la tabla
-- Primero: dropear CHECK recreando shift_reports sin el CHECK

-- Backup de datos
CREATE TABLE shift_reports_new AS
SELECT 
  id,
  store_id,
  report_date,
  shift,
  shift_start,
  shift_end,
  cashier_id,
  gnc_qty,
  price_cents,
  gnc_sales_cents,
  lubricantes_sales_cents,
  varios_qty,
  varios_price_cents,
  varios_sales_cents,
  total_sales_cents,
  sobre_1_importe_cents,
  sobre_2_importe_cents,
  sobre_3_importe_cents,
  sobre_4_importe_cents,
  sobre_5_importe_cents,
  sobre_6_importe_cents,
  total_ensobrado_cents,
  puente_entrante,
  puente_saliente,
  observaciones,
  notes,
  created_at,
  updated_at
FROM shift_reports;

-- Dropear tabla original
DROP TABLE shift_reports;

-- Recrear tabla sin CHECK shift_end > shift_start y sin columnas sobre_{i}_hora
CREATE TABLE shift_reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL REFERENCES stores(id) ON DELETE CASCADE,
  report_date INTEGER NOT NULL,
  shift TEXT NOT NULL CHECK (shift IN ('morning','evening','night')),
  shift_start INTEGER NOT NULL,
  shift_end INTEGER NOT NULL,
  cashier_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  gnc_qty REAL NOT NULL DEFAULT 0,
  price_cents INTEGER NOT NULL DEFAULT 0,
  gnc_sales_cents INTEGER NOT NULL DEFAULT 0,
  lubricantes_sales_cents INTEGER NOT NULL DEFAULT 0,
  varios_qty INTEGER NOT NULL DEFAULT 0,
  varios_price_cents INTEGER NOT NULL DEFAULT 0,
  varios_sales_cents INTEGER NOT NULL DEFAULT 0,
  total_sales_cents INTEGER NOT NULL DEFAULT 0,
  sobre_1_importe_cents INTEGER NOT NULL DEFAULT 0,
  sobre_2_importe_cents INTEGER NOT NULL DEFAULT 0,
  sobre_3_importe_cents INTEGER NOT NULL DEFAULT 0,
  sobre_4_importe_cents INTEGER NOT NULL DEFAULT 0,
  sobre_5_importe_cents INTEGER NOT NULL DEFAULT 0,
  sobre_6_importe_cents INTEGER NOT NULL DEFAULT 0,
  total_ensobrado_cents INTEGER NOT NULL DEFAULT 0,
  puente_entrante REAL NOT NULL DEFAULT 0,
  puente_saliente REAL NOT NULL DEFAULT 0,
  observaciones TEXT,
  notes TEXT,
  created_at INTEGER NOT NULL DEFAULT (strftime('%s','now') * 1000),
  updated_at INTEGER NOT NULL DEFAULT (strftime('%s','now') * 1000)
);

-- Índices
CREATE UNIQUE INDEX shift_reports_store_date_shift_uq ON shift_reports(store_id, report_date, shift);
CREATE INDEX shift_reports_store_idx ON shift_reports(store_id, report_date);
CREATE INDEX shift_reports_cashier_idx ON shift_reports(cashier_id);

-- Restaurar datos
INSERT INTO shift_reports (
  id, store_id, report_date, shift, shift_start, shift_end, cashier_id,
  gnc_qty, price_cents, gnc_sales_cents, lubricantes_sales_cents,
  varios_qty, varios_price_cents, varios_sales_cents, total_sales_cents,
  sobre_1_importe_cents, sobre_2_importe_cents, sobre_3_importe_cents,
  sobre_4_importe_cents, sobre_5_importe_cents, sobre_6_importe_cents,
  total_ensobrado_cents, puente_entrante, puente_saliente,
  observaciones, notes, created_at, updated_at
)
SELECT 
  id, store_id, report_date, shift, shift_start, shift_end, cashier_id,
  gnc_qty, price_cents, gnc_sales_cents, lubricantes_sales_cents,
  varios_qty, varios_price_cents, varios_sales_cents, total_sales_cents,
  sobre_1_importe_cents, sobre_2_importe_cents, sobre_3_importe_cents,
  sobre_4_importe_cents, sobre_5_importe_cents, sobre_6_importe_cents,
  total_ensobrado_cents, puente_entrante, puente_saliente,
  observaciones, notes, created_at, updated_at
FROM shift_reports_new;

-- Limpiar
DROP TABLE shift_reports_new;

-- Nota: Los valores de shift_start/shift_end para turnos noche existentes 
-- pueden tener shift_end < shift_start (ej: 22:00 día X -> 06:00 día X+1)
-- La validación ahora se hace en la aplicación (operativa.ts POST /rendicion)