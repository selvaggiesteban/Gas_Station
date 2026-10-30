import { describe, expect, it } from 'vitest';
import { SELF, env } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

function encode(data: Record<string, string | string[]>): string {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(data)) {
    if (Array.isArray(value)) {
      for (const item of value) params.append(key, item);
    } else {
      params.append(key, value);
    }
  }
  return params.toString();
}

async function post(
  path: string,
  data: Record<string, string | string[]>,
  cookie?: string,
) {
  return SELF.fetch(BASE + path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      ...(cookie ? { cookie } : {}),
    },
    body: encode(data),
    redirect: 'manual',
  });
}

async function get(path: string, cookie?: string) {
  return SELF.fetch(BASE + path, {
    headers: cookie ? { cookie } : {},
    redirect: 'manual',
  });
}

function sessionCookie(res: Response): string {
  const found = res.headers
    .getSetCookie()
    .map((c) => c.split(';')[0] ?? '')
    .find((c) => c.startsWith('gs_session='));
  if (!found) throw new Error('no se recibió cookie de sesión');
  return found;
}

let seq = 0;
function uniqueEmail(prefix: string): string {
  seq += 1;
  return `${prefix}-${Date.now()}-${seq}@test.com`;
}

async function register(nombre: string, email: string): Promise<string> {
  const res = await post('/registro', {
    nombre,
    email,
    password: 'supersecret1',
    password2: 'supersecret1',
  });
  expect(res.status).toBe(302);
  return sessionCookie(res);
}

async function registerAs(
  nombre: string,
  email: string,
  role: string,
): Promise<string> {
  const cookie = await register(nombre, email);
  await env.DB.prepare('update users set role = ? where email = ?')
    .bind(role, email)
    .run();
  return cookie;
}

async function count(table: string): Promise<number> {
  const row = await env.DB.prepare(
    `select count(*) as n from ${table}`,
  ).first<{ n: number }>();
  return row?.n ?? 0;
}

describe('operativa', () => {
  it('/operativa responde 200 logueado y redirige a login sin sesión', async () => {
    const sinSesion = await get('/operativa');
    expect(sinSesion.status).toBe(302);
    expect(sinSesion.headers.get('location')).toBe('/login');

    const cookie = await registerAs(
      'Admin Operativa',
      uniqueEmail('admin-op'),
      'admin',
    );
    const conSesion = await get('/operativa', cookie);
    expect(conSesion.status).toBe(200);
    const html = await conSesion.text();
    expect(html).toContain('Caja diaria');
    expect(html).toContain('Resumen de ventas');
    expect(html).toContain('Resumen diario de actividades');
    expect(html).toContain('Stock de lubricantes');
    expect(html).toContain('Rendición de turno');
    expect(html).toContain('Cupón de sorteo');
    expect(html).toContain('/operativa/caja');
    expect(html).toContain('/operativa/sorteo');
  });

  it('caja: crear, listar y cerrar con diferencia calculada en el servidor', async () => {
    const cookie = await registerAs(
      'Caja Admin',
      uniqueEmail('caja-admin'),
      'admin',
    );
    const antes = await count('cash_days');

    const creada = await post(
      '/operativa/caja',
      { fecha: '2026-11-02', apertura: '1500.50', notas: 'arranque del día' },
      cookie,
    );
    expect(creada.status).toBe(302);
    expect(creada.headers.get('location')).toBe('/operativa/caja?aviso=1');
    expect(await count('cash_days')).toBe(antes + 1);

    const row = await env.DB.prepare(
      'select * from cash_days where report_date = ? order by id desc limit 1',
    )
      .bind(Date.UTC(2026, 10, 2))
      .first<{
        id: number;
        status: string;
        opening_cents: number;
        store_id: number;
        difference_cents: number | null;
        closed_by: number | null;
      }>();
    expect(row).toBeTruthy();
    expect(row!.status).toBe('open');
    expect(row!.opening_cents).toBe(150050);
    expect(row!.difference_cents).toBeNull();

    const listado = await get('/operativa/caja?fecha=2026-11-02', cookie);
    expect(listado.status).toBe(200);
    const listHtml = await listado.text();
    expect(listHtml).toContain('Abierta');
    expect(listHtml).toContain('1.500,50');

    const cerrada = await post(
      `/operativa/caja/${row!.id}/cerrar`,
      { declarado: '1600', contado: '1580.75', notas: 'cierre de noche' },
      cookie,
    );
    expect(cerrada.status).toBe(302);

    const after = await env.DB.prepare(
      'select * from cash_days where id = ?',
    )
      .bind(row!.id)
      .first<{
        status: string;
        closing_declared_cents: number;
        closing_counted_cents: number;
        difference_cents: number;
        closed_by: number | null;
        closed_at: number | null;
      }>();
    expect(after!.status).toBe('closed');
    expect(after!.closing_declared_cents).toBe(160000);
    expect(after!.closing_counted_cents).toBe(158075);
    expect(after!.difference_cents).toBe(158075 - 160000);
    expect(after!.closed_by).toBeTruthy();
    expect(after!.closed_at).toBeTruthy();

    const doble = await post(
      `/operativa/caja/${row!.id}/cerrar`,
      { declarado: '1', contado: '1' },
      cookie,
    );
    expect(doble.status).toBe(422);

    const duplicate = await post(
      '/operativa/caja',
      { fecha: '2026-11-02', apertura: '100' },
      cookie,
    );
    expect(duplicate.status).toBe(422);
    expect(await count('cash_days')).toBe(antes + 1);
  });

  it('caja: resumen en papel con líneas de entradas, salidas y totales', async () => {
    const cookie = await registerAs(
      'Caja Papel',
      uniqueEmail('caja-papel'),
      'admin',
    );
    const fecha = '2026-11-09';
    const dia = Date.UTC(2026, 10, 9);

    // 1. Abrir caja con apertura = 200
    const abierta = await post('/operativa/caja', { fecha, apertura: '200' }, cookie);
    expect(abierta.status).toBe(302);

    // 2. Cargar turnos de ventas (morning/evening/night) que alimentan el resumen
    await env.DB.prepare(`
      insert into sales_summaries (store_id, report_date, shift, cash_cents, card_cents, transfer_cents, credit_cents, total_cents, notes)
      values (1, ?, 'morning', 0, 0, 0, 0, 100000, 'test'),
             (1, ?, 'evening', 0, 0, 0, 0, 200000, 'test'),
             (1, ?, 'night', 0, 0, 0, 0, 50000, 'test')
    `).bind(dia, dia, dia).run();

    // 3. Guardar movimientos manuales vía nueva pantalla
    const guardado = await post(
      '/operativa/caja/movimientos',
      {
        fecha,
        entrada_d: ['Banco', 'Monedas'],
        entrada_i: ['1500', '50'],
        entrada_t: ['cuenta', 'cuenta'],
        entrada_s: ['0', '1'],
        salida_d: ['ARCA', 'Uniformes'],
        salida_i: ['1000', '200'],
        salida_t: ['referencia', 'movimiento'],
        salida_s: ['0', '1'],
      },
      cookie,
    );
    expect(guardado.status).toBe(302);
    expect(guardado.headers.get('location')).toBe(
      '/operativa/caja/movimientos?fecha=2026-11-09&aviso=1',
    );

    // Verificar que se guardaron 4 líneas (2 entradas + 2 salidas)
    const filas = await env.DB.prepare(
      'select count(*) as n from cash_lines where report_date = ?',
    )
      .bind(dia)
      .first<{ n: number }>();
    expect(filas?.n).toBe(4);

    // 4. Ver el resumen (solo lectura)
    const vista = await get(`/operativa/caja?fecha=${fecha}`, cookie);
    expect(vista.status).toBe(200);
    const html = await vista.text();

    // Verificar estructura del resumen
    expect(html).toContain('>Entradas<');
    expect(html).toContain('>Salidas<');
    expect(html).toContain('Total Entradas');
    expect(html).toContain('Total Salidas');
    expect(html).toContain('Total Neto');
    expect(html).toContain('Total del día');
    expect(html).toContain('SALDO DÍA ANTERIOR');
    expect(html).toContain('TURNO MAÑANA');
    expect(html).toContain('TURNO TARDE');
    expect(html).toContain('TURNO NOCHE');
    expect(html).toContain('TOTAL TURNOS');
    expect(html).toContain('BANCO (cuenta)');
    expect(html).toContain('MONEDAS (cuenta)');
    expect(html).toContain('ARCA (ref.)');
    expect(html).toContain('UNIFORMES');

    // Fórmulas:
    // prevOpening = 200
    // totalTurnos = 1000 + 2000 + 500 = 3500
    // entradasManuales = 0 (Banco y Monedas son 'cuenta', no suman en Entradas)
    // totalEntradas = 200 + 3500 + 0 = 3700
    // salidasManuales = 200 (solo Uniformes, 'movimiento')
    // totalSalidas = 200 (ARCA es 'referencia', no suma)
    // totalDia = 3700 - 200 = 3500
    // totalCuentas = 1500 + 50 = 1550
    // totalNeto = 3500 - 1550 = 1950
    expect(html).toContain('3.700,00'); // Total Entradas
    expect(html).toContain('200,00');   // Total Salidas
    expect(html).toContain('3.500,00'); // Total del día
    expect(html).toContain('1.950,00'); // Total Neto

    // 5. Reemplazo total (borra y vuelve a insertar)
    const reemplazo = await post(
      '/operativa/caja/movimientos',
      {
        fecha,
        entrada_d: ['Nuevo Banco'],
        entrada_i: ['500'],
        entrada_t: ['cuenta'],
        entrada_s: ['0'],
        salida_d: [''],
        salida_i: [''],
        salida_t: [''],
        salida_s: [''],
      },
      cookie,
    );
    expect(reemplazo.status).toBe(302);
    const despues = await env.DB.prepare(
      'select count(*) as n from cash_lines where report_date = ?',
    )
      .bind(dia)
      .first<{ n: number }>();
    expect(despues?.n).toBe(1);

    // 6. Validaciones
    const sinDetalle = await post(
      '/operativa/caja/movimientos',
      { fecha, entrada_d: [''], entrada_i: ['10'] },
      cookie,
    );
    expect(sinDetalle.status).toBe(422);
    const sigueUno = await env.DB.prepare(
      'select count(*) as n from cash_lines where report_date = ?',
    )
      .bind(dia)
      .first<{ n: number }>();
    expect(sigueUno?.n).toBe(1);

    const negativo = await post(
      '/operativa/caja/movimientos',
      { fecha, entrada_d: ['x'], entrada_i: ['-1'] },
      cookie,
    );
    expect(negativo.status).toBe(422);

    const fechaMala = await post(
      '/operativa/caja/movimientos',
      { fecha: 'no-es-fecha', entrada_d: ['x'], entrada_i: ['1'] },
      cookie,
    );
    expect(fechaMala.status).toBe(422);

    // 7. 403 para cliente en la pantalla de movimientos
    const cookieCliente = await registerAs(
      'Cliente Caja',
      uniqueEmail('cliente-caja'),
      'customer',
    );
    const movAntes = await count('cash_lines');
    const mov403 = await post(
      '/operativa/caja/movimientos',
      { fecha: '2026-11-10', entrada_d: ['x'], entrada_i: ['10'] },
      cookieCliente,
    );
    expect(mov403.status).toBe(403);
    expect(await count('cash_lines')).toBe(movAntes);
  });

  it('ventas: crear, listar y editar recalculando el total', async () => {
    const cookie = await registerAs(
      'Ventas Admin',
      uniqueEmail('ventas-admin'),
      'admin',
    );
    const antes = await count('sales_summaries');

    const creada = await post(
      '/operativa/ventas',
      {
        fecha: '2026-11-03',
        turno: 'morning',
        efectivo: '1000',
        tarjeta: '500',
        transferencia: '250',
        fiado: '100',
        notas: 'ensobrado mañana',
      },
      cookie,
    );
    expect(creada.status).toBe(302);
    expect(await count('sales_summaries')).toBe(antes + 1);

    const row = await env.DB.prepare(
      'select * from sales_summaries where report_date = ? limit 1',
    )
      .bind(Date.UTC(2026, 10, 3))
      .first<{
        id: number;
        cash_cents: number;
        card_cents: number;
        transfer_cents: number;
        credit_cents: number;
        total_cents: number;
        shift: string;
      }>();
    expect(row).toBeTruthy();
    expect(row!.cash_cents).toBe(100000);
    expect(row!.card_cents).toBe(50000);
    expect(row!.transfer_cents).toBe(25000);
    expect(row!.credit_cents).toBe(10000);
    expect(row!.total_cents).toBe(185000);
    expect(row!.shift).toBe('morning');

    const listado = await get('/operativa/ventas?fecha=2026-11-03', cookie);
    expect(listado.status).toBe(200);
    expect(await listado.text()).toContain('1.850,00');

    const editada = await post(
      `/operativa/ventas/${row!.id}/editar`,
      {
        efectivo: '900',
        tarjeta: '400',
        transferencia: '200',
        fiado: '50',
        notas: 'recuento',
      },
      cookie,
    );
    expect(editada.status).toBe(302);

    const after = await env.DB.prepare(
      'select * from sales_summaries where id = ?',
    )
      .bind(row!.id)
      .first<{ cash_cents: number; total_cents: number; notes: string }>();
    expect(after!.cash_cents).toBe(90000);
    expect(after!.total_cents).toBe(90000 + 40000 + 20000 + 5000);
    expect(after!.notes).toBe('recuento');
  });

  it('rendición: crea el turno con lecturas y suma totales calculados', async () => {
    const cookie = await registerAs(
      'Rendicion Admin',
      uniqueEmail('rendicion-admin'),
      'admin',
    );
    const antes = await count('shift_reports');

    const creada = await post(
      '/operativa/rendicion',
      {
        fecha: '2026-11-04',
        turno: 'evening',
        inicio: '2026-11-04T14:00',
        fin: '2026-11-04T22:00',
        apertura: '2000',
        cierre: '2100',
        notas: 'turno tarde',
        r0_surtidor: '1',
        r0_nozzle: '2',
        r0_producto: 'nafta',
        r0_apertura: '1000.5',
        r0_cierre: '1250.5',
        r0_precio: '1200',
        r1_surtidor: '3',
        r1_nozzle: '1',
        r1_producto: 'gasoil',
        r1_apertura: '500',
        r1_cierre: '625.5',
        r1_precio: '1300',
      },
      cookie,
    );
    expect(creada.status).toBe(302);
    expect(creada.headers.get('location')).toMatch(
      /^\/operativa\/rendicion\/\d+\?aviso=1$/,
    );
    expect(await count('shift_reports')).toBe(antes + 1);

    const report = await env.DB.prepare(
      'select * from shift_reports where report_date = ? limit 1',
    )
      .bind(Date.UTC(2026, 10, 4))
      .first<{
        id: number;
        shift: string;
        opening_cents: number;
        closing_cents: number;
        cashier_id: number | null;
      }>();
    expect(report).toBeTruthy();
    expect(report!.shift).toBe('evening');
    expect(report!.opening_cents).toBe(200000);
    expect(report!.closing_cents).toBe(210000);
    expect(report!.cashier_id).toBeTruthy();

    const readings = await env.DB.prepare(
      'select * from shift_report_readings where report_id = ? order by surtidor',
    )
      .bind(report!.id)
      .all<{
        surtidor: number;
        nozzle: number;
        product: string;
        opening_reading: number;
        closing_reading: number;
        volume: number;
        price_cents: number;
        total_cents: number;
      }>();
    expect(readings.results.length).toBe(2);
    const nafta = readings.results[0]!;
    expect(nafta.product).toBe('nafta');
    expect(nafta.volume).toBe(250);
    expect(nafta.price_cents).toBe(120000);
    expect(nafta.total_cents).toBe(250 * 120000);
    const gasoil = readings.results[1]!;
    expect(gasoil.volume).toBeCloseTo(125.5, 5);
    expect(gasoil.total_cents).toBe(Math.round(125.5 * 130000));

    const detalle = await get(
      `/operativa/rendicion/${report!.id}`,
      cookie,
    );
    expect(detalle.status).toBe(200);
    const html = await detalle.text();
    const sumaEsperada = 250 * 120000 + Math.round(125.5 * 130000);
    expect(html).toContain(
      new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
      }).format(sumaEsperada / 100),
    );
  });

  it('sorteo: genera códigos únicos y permite marcar el ganador', async () => {
    const cookie = await registerAs(
      'Sorteo Admin',
      uniqueEmail('sorteo-admin'),
      'admin',
    );
    const antes = await count('raffle_coupons');

    const emitidos = await post(
      '/operativa/sorteo',
      {
        cantidad: '3',
        nombre: 'María Díaz',
        email: 'maria@example.com',
        telefono: '1155554444',
      },
      cookie,
    );
    expect(emitidos.status).toBe(302);
    expect(await count('raffle_coupons')).toBe(antes + 3);

    const rows = await env.DB.prepare(
      'select * from raffle_coupons where holder_name = ? order by id',
    )
      .bind('María Díaz')
      .all<{
        id: number;
        code: string;
        winning: number;
        drawn_at: number | null;
        holder_email: string | null;
      }>();
    expect(rows.results.length).toBe(3);
    const codes = rows.results.map((r) => r.code);
    expect(new Set(codes).size).toBe(3);
    for (const code of codes) {
      expect(code).toMatch(/^[A-Z2-9]{8}$/);
    }
    expect(rows.results[0]!.holder_email).toBe('maria@example.com');

    const listado = await get('/operativa/sorteo', cookie);
    expect(listado.status).toBe(200);
    expect(await listado.text()).toContain(codes[0]!);

    const ganador = await post(
      `/operativa/sorteo/${rows.results[0]!.id}/ganador`,
      {},
      cookie,
    );
    expect(ganador.status).toBe(302);

    const after = await env.DB.prepare(
      'select * from raffle_coupons where id = ?',
    )
      .bind(rows.results[0]!.id)
      .first<{ winning: number; drawn_at: number | null }>();
    expect(after!.winning).toBe(1);
    expect(after!.drawn_at).toBeTruthy();

    const repetido = await post(
      `/operativa/sorteo/${rows.results[0]!.id}/ganador`,
      {},
      cookie,
    );
    expect(repetido.status).toBe(422);
  });

  it('validación: datos inválidos devuelven 422 y no crean registros', async () => {
    const cookie = await registerAs(
      'Validacion Admin',
      uniqueEmail('valid-admin'),
      'admin',
    );

    const cajas = await count('cash_days');
    const cajaMala = await post(
      '/operativa/caja',
      { fecha: 'no-es-fecha', apertura: '100' },
      cookie,
    );
    expect(cajaMala.status).toBe(422);
    expect(await count('cash_days')).toBe(cajas);

    const cajaMontoMalo = await post(
      '/operativa/caja',
      { fecha: '2026-11-06', apertura: 'abc' },
      cookie,
    );
    expect(cajaMontoMalo.status).toBe(422);
    expect(await count('cash_days')).toBe(cajas);

    const ventas = await count('sales_summaries');
    const ventaMala = await post(
      '/operativa/ventas',
      {
        fecha: '2026-11-06',
        turno: 'mediodia',
        efectivo: '10',
        tarjeta: '0',
        transferencia: '0',
        fiado: '0',
      },
      cookie,
    );
    expect(ventaMala.status).toBe(422);
    expect(await count('sales_summaries')).toBe(ventas);

    const ventaMontoMalo = await post(
      '/operativa/ventas',
      {
        fecha: '2026-11-06',
        turno: 'night',
        efectivo: '-5',
        tarjeta: '0',
        transferencia: '0',
        fiado: '0',
      },
      cookie,
    );
    expect(ventaMontoMalo.status).toBe(422);
    expect(await count('sales_summaries')).toBe(ventas);
  });

  it('permiso: un customer recibe 403 en el alta y no crea registros', async () => {
    const cookie = await register('Cliente Común', uniqueEmail('customer-op'));
    const cajas = await count('cash_days');
    const ventas = await count('sales_summaries');

    const indice = await get('/operativa', cookie);
    expect(indice.status).toBe(403);

    const alta = await post(
      '/operativa/caja',
      { fecha: '2026-11-07', apertura: '500' },
      cookie,
    );
    expect(alta.status).toBe(403);
    expect(await count('cash_days')).toBe(cajas);

    const lineasAntes = await count('cash_lines');
    const lineas = await post(
      '/operativa/caja/lineas',
      { fecha: '2026-11-07', entrada_d: ['x'], entrada_i: ['10'] },
      cookie,
    );
    expect(lineas.status).toBe(403);
    expect(await count('cash_lines')).toBe(lineasAntes);

    const altaVenta = await post(
      '/operativa/ventas',
      {
        fecha: '2026-11-07',
        turno: 'morning',
        efectivo: '1',
        tarjeta: '0',
        transferencia: '0',
        fiado: '0',
      },
      cookie,
    );
    expect(altaVenta.status).toBe(403);
    expect(await count('sales_summaries')).toBe(ventas);
  });
});
