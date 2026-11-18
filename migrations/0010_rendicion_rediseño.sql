-- Migración 0010: Rediseño rendición de turno
-- 1. shift_reports: agregar price_cents (precio único por turno)
-- 2. shift_report_readings: 6 mangueras fijas, quitar price_cents
-- 3. Eliminar turno adicional (shift=4)
-- 4. Nueva tabla user_activity_log para log llegada tarde

-- 1. Agregar price_cents a shift_reports
ALTER TABLE shift_reports ADD COLUMN price_cents INTEGER DEFAULT 0;

-- Migrar precio promedio de readings existentes a shift_reports
UPDATE shift_reports SET price_cents = (
  SELECT CAST(AVG(price_cents) AS INTEGER)
  FROM shift_report_readings
  WHERE report_id = shift_reports.id
);

-- 2. shift_report_readings: 6 mangueras fijas, quitar price_cents
-- SQLite no soporta DROP COLUMN directo, crear tabla nueva
CREATE TABLE shift_report_readings_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL REFERENCES shift_reports(id) ON DELETE CASCADE,
    manguera INTEGER NOT NULL CHECK (manguera >= 1 AND manguera <= 6),
    product TEXT NOT NULL,
    opening_reading REAL NOT NULL DEFAULT 0,
    closing_reading REAL NOT NULL DEFAULT 0,
    volume REAL NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL DEFAULT 0,
    created_at INTEGER NOT NULL DEFAULT (unixepoch() * 1000)
);

INSERT INTO shift_report_readings_new (id, report_id, manguera, product, opening_reading, closing_reading, volume, total_cents)
SELECT id, report_id, surtidor as manguera, product, opening_reading, closing_reading, volume, total_cents
FROM shift_report_readings;

DROP TABLE shift_report_readings;
ALTER TABLE shift_report_readings_new RENAME TO shift_report_readings;

CREATE UNIQUE INDEX IF NOT EXISTS shift_report_readings_unique
ON shift_report_readings(report_id, manguera);
CREATE INDEX IF NOT EXISTS shift_report_readings_report_idx
ON shift_report_readings(report_id);

-- 3. Eliminar turno adicional (shift=4)
DELETE FROM shift_report_readings WHERE report_id IN (SELECT id FROM shift_reports WHERE shift = 4);
DELETE FROM shift_reports WHERE shift = 4;

-- 4. Nueva tabla user_activity_log
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type TEXT NOT NULL, -- 'late_arrival', 'login', 'logout'
    turno INTEGER, -- 0=mañana, 1=tarde, 2=noche
    expected_time TEXT, -- '06:00'
    actual_time TEXT, -- '06:15'
    created_at INTEGER NOT NULL DEFAULT (unixepoch() * 1000)
);

CREATE INDEX IF NOT EXISTS user_activity_log_user_idx ON user_activity_log(user_id);
CREATE INDEX IF NOT EXISTS user_activity_log_type_idx ON user_activity_log(type);
CREATE INDEX IF NOT EXISTS user_activity_log_created_idx ON user_activity_log(created_at);