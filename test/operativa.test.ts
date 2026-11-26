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

function formatArs(cents: number): string {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(cents / 100);
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
    expect(html).toContain('Resumen diario');
    expect(html).toContain('/operativa/caja');
    expect(html).not.toContain('Stock de lubricantes');
    expect(html).not.toContain('Resumen de ventas');
    expect(html).not.toContain('Rendición de turno');
    expect(html).not.toContain('Sorteo');
    expect(html).not.toContain('/operativa/sorteo');
  });

  it('home del admin no muestra links a sorteo ni a cargar ventas', async () => {
    const cookie = await registerAs(
      'Admin Home Limpio',
      uniqueEmail('admin-home-limpio'),
      'admin',
    );
    const res = await get('/operativa', cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).not.toContain('/operativa/sorteo');
    expect(html).not.toContain('/operativa/ventas/nuevo');
    expect(html).not.toContain('/operativa/rendicion/nuevo');
    // El playero sigue viendo su única carga: la rendición del turno.
    const playero = await registerAs(
      'Playero Home',
      uniqueEmail('playero-home'),
      'store_manager',
    );
    const homePlayero = await get('/operativa', playero);
    expect(homePlayero.status).toBe(200);
    const htmlPlayero = await homePlayero.text();
    expect(htmlPlayero).toContain('Rendición de turno');
    expect(htmlPlayero).toContain('/operativa/rendicion');
    expect(htmlPlayero).not.toContain('/operativa/sorteo');
    expect(htmlPlayero).not.toContain('/operativa/caja');
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
    expect(html).toContain('BANCO (Mercado Pago)');
    // MONEDAS ya no existe como categoría separada
    expect(html).toContain('ARCA (ref.)');
    expect(html).toContain('UNIFORMES');

    // Fórmulas NUEVAS:
    // prevOpening = 200
    // totalTurnos = 1000 + 2000 + 500 = 3500
    // totalBanco (MP auto) = 0 (no hay órdenes MP en este test)
    // entradasManuales = 0
    // totalEntradas = 200 + 3500 + 0 = 3700 (Banco NO suma a entradas)
    // salidasManuales = 200 (solo Uniformes, 'movimiento')
    // totalSalidas = 200 (ARCA es 'referencia', no suma)
    // totalDia = 3700 - 200 = 3500
    // totalNeto = totalDia - totalBanco = 3500 - 0 = 3500
    expect(html).toContain('3.700,00'); // Total Entradas
    expect(html).toContain('200,00');   // Total Salidas
    expect(html).toContain('3.500,00'); // Total del día
    expect(html).toContain('3.500,00'); // Total Neto (efectivo)

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

    const response = await post(
      '/operativa/rendicion',
      {
        fecha: '2026-11-04',
        turno: 'evening',
        puente_entrante: '1000.25',
        puente_saliente: '900.75',
        notas: 'turno tarde',
        lubricantes_sales: '0',
        varios_qty: '0',
        varios_price: '0',
        // 6 filas fijas: mangueras 1-6, todas GNC
        // Solo enviamos lecturas para mangueras 1 y 2 (índices 0 y 1)
        r0_apertura: '1000.5',
        r0_cierre: '1250.5',
        r1_apertura: '500',
        r1_cierre: '625.5',
      },
      cookie,
    );
    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toMatch(
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
        puente_entrante: number;
        puente_saliente: number;
        cashier_id: number | null;
        gnc_qty: number;
        price_cents: number;
      }>();
    expect(report).toBeTruthy();
    expect(report!.shift).toBe('evening');
    expect(report!.puente_entrante).toBeCloseTo(1000.25, 5);
    expect(report!.puente_saliente).toBeCloseTo(900.75, 5);
    expect(report!.cashier_id).toBeTruthy();
    // gnc_qty calculado automáticamente = suma (cierre - apertura) = 250 + 125.5 = 375.5
    expect(report!.gnc_qty).toBeCloseTo(375.5, 5);
    // price_cents: 0 si no hay producto GNC en la BD de test (se usa body.gnc_price que no se envía)
    expect(report!.price_cents).toBeGreaterThanOrEqual(0);

    const readings = await env.DB.prepare(
      'select * from shift_report_readings where report_id = ? order by manguera',
    )
      .bind(report!.id)
      .all<{
        manguera: number;
        product: string;
        opening_reading: number;
        closing_reading: number;
        volume: number;
        total_cents: number;
      }>();
    expect(readings.results.length).toBe(2);
    const m1 = readings.results[0]!;
    expect(m1.manguera).toBe(1);
    expect(m1.product).toBe('gnc');
    expect(m1.volume).toBe(250);
    expect(m1.total_cents).toBe(0); // precio está en shift_reports.price_cents
    const m2 = readings.results[1]!;
    expect(m2.manguera).toBe(2);
    expect(m2.product).toBe('gnc');
    expect(m2.volume).toBeCloseTo(125.5, 5);
    expect(m2.total_cents).toBe(0); // precio está en shift_reports.price_cents

    const detalle = await get(
      `/operativa/rendicion/${report!.id}`,
      cookie,
    );
    expect(detalle.status).toBe(200);
    const html = await detalle.text();
    // Sin producto GNC en BD de test, price_cents = 0 => ventas GNC = $0,00
    expect(html).toContain('0,00');
  });

  it('sorteo: registro crea participación automática', async () => {
    const antes = await count('raffle_coupons');
    const email = uniqueEmail('sorteo-reg');
    const cookie = await registerAs('Sorteo Registro', email, 'customer');
    expect(await count('raffle_coupons')).toBe(antes + 1);
    const rows = await env.DB.prepare(
      'select * from raffle_coupons where holder_email = ?',
    ).bind(email).all();
    expect(rows.results.length).toBe(1);
    const row0 = rows.results[0];
    expect(row0).toBeTruthy();
    expect(row0!.code).toMatch(/^[A-Z2-9]{8}$/);
    expect(row0!.holder_name).toBe('Sorteo Registro');
    expect(row0!.winning).toBe(0);
  });

  it('sorteo: alta en checkout crea participación automática', async () => {
    const antes = await count('raffle_coupons');
    const email = uniqueEmail('checkout-reg');
    const cookie = await registerAs('Checkout Usuario', email, 'customer');
    // Crear producto activo si no existe
    const prod = await env.DB.prepare(
      'select id from products where active = 1 limit 1'
    ).first();
    let pid;
    if (prod) {
      pid = prod.id;
    } else {
      const slug = 'producto-test-' + Date.now();
      const ins = await env.DB.prepare(
        'insert into products (name, slug, price_cents, active, category) values (?, ?, ?, 1, ?)'
      ).bind('Producto Test', slug, 1000, 'general').run();
      pid = ins.meta.last_row_id;
    }
    const postBody = { ['qty_' + pid]: '1', cupon: '', comprobante: '', metodo: 'transferencia' };
    const checkout = await post('/checkout', postBody, cookie);
    expect(checkout.status).toBe(302);
    expect(await count('raffle_coupons')).toBe(antes + 1);
  });

  it('sorteo: listado muestra inscriptos y permite marcar ganador', async () => {
    const cookie = await registerAs(
      'Sorteo Admin List',
      uniqueEmail('sorteo-admin-list'),
      'admin',
    );
    await registerAs('Sorteo User 1', uniqueEmail('sorteo-user1'), 'customer');
    await registerAs('Sorteo User 2', uniqueEmail('sorteo-user2'), 'customer');
    const listado = await get('/operativa/sorteo', cookie);
    expect(listado.status).toBe(200);
    const html = await listado.text();
    expect(html).toContain('Inscripto');
    expect(html).toContain('Marcar ganador');
    const rows = await env.DB.prepare(
      'select * from raffle_coupons order by id desc limit 2'
    ).all();
    expect(rows.results.length).toBe(2);
    const r0 = rows.results[0];
    expect(r0).toBeTruthy();
    const ganador = await post(
      '/operativa/sorteo/' + r0!.id + '/ganador',
      {},
      cookie,
    );
    expect(ganador.status).toBe(302);
    const after = await env.DB.prepare(
      'select * from raffle_coupons where id = ?',
    ).bind(r0!.id).first();
    expect(after).toBeTruthy();
    expect(after!.winning).toBe(1);
    expect(after!.drawn_at).toBeTruthy();
  });

  it('sorteo: marcar ganador dos veces devuelve 422', async () => {
    const cookie = await registerAs(
      'Sorteo Admin 2',
      uniqueEmail('sorteo-admin-2'),
      'admin',
    );
    await registerAs('Sorteo User 3', uniqueEmail('sorteo-user3'), 'customer');
    const rows = await env.DB.prepare(
      'select * from raffle_coupons order by id desc limit 1'
    ).all();
    const r0 = rows.results[0];
    expect(r0).toBeTruthy();
    await post('/operativa/sorteo/' + r0!.id + '/ganador', {}, cookie);
    const repetido = await post('/operativa/sorteo/' + r0!.id + '/ganador', {}, cookie);
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

  it('rendición ampliada: ventas por rubro, ensobrada, puente y observaciones', async () => {
    const cookie = await registerAs(
      'Rendicion Ampliada',
      uniqueEmail('rendicion-amp'),
      'admin',
    );
    const creada = await post(
      '/operativa/rendicion',
      {
        puente_entrante: '1000.5',
        puente_saliente: '900.25',
        lubricantes_sales: '10000',
        varios_qty: '2',
        varios_price: '800',
        sobre1_importe: '5000',
        sobre2_importe: '3500',
        notas: 'observación del turno',
        // 6 mangueras fijas GNC
        r0_apertura: '100',
        r0_cierre: '200',
        r1_apertura: '200',
        r1_cierre: '300',
        r2_apertura: '300',
        r2_cierre: '400',
        r3_apertura: '400',
        r3_cierre: '500',
        r4_apertura: '500',
        r4_cierre: '600',
        r5_apertura: '600',
        r5_cierre: '700',
      },
      cookie,
    );
    expect(creada.status).toBe(302);
    const loc = creada.headers.get('location') ?? '';
    expect(loc).toMatch(/^\/operativa\/rendicion\/\d+\?aviso=1$/);
    const id = Number(/rendicion\/(\d+)/.exec(loc)?.[1]);

    const row = await env.DB.prepare(
      'select * from shift_reports where id = ?',
    )
      .bind(id)
      .first<{
        shift: string;
        gnc_qty: number;
        price_cents: number;
        gnc_sales_cents: number;
        lubricantes_sales_cents: number;
        varios_sales_cents: number;
        total_sales_cents: number;
        sobre_1_hora: string | null;
        sobre_1_importe_cents: number;
        sobre_2_hora: string | null;
        sobre_2_importe_cents: number;
        total_ensobrado_cents: number;
        puente_entrante: number;
        puente_saliente: number;
        fondo_cambio_saliente_cents: number | null;
        vales_empleados_cents: number | null;
        opening_cents: number | null;
        observaciones: string | null;
      }>();
    expect(row).toBeTruthy();
    expect(row!.shift).toBe('morning');
    // gnc_qty = suma volúmenes 6 mangueras = 100*6 = 600
    expect(row!.gnc_qty).toBe(600);
    // price_cents: 0 si no hay producto GNC en la BD de test
    expect(row!.price_cents).toBeGreaterThanOrEqual(0);
    expect(row!.gnc_sales_cents).toBe(row!.gnc_qty * row!.price_cents);
    expect(row!.lubricantes_sales_cents).toBe(1000000);
    expect(row!.varios_sales_cents).toBe(2 * 80000);
    expect(row!.total_sales_cents).toBe(
      row!.gnc_qty * row!.price_cents + 1000000 + 2 * 80000,
    );
    // sobres sin hora
    expect(row!.sobre_1_hora).toBeNull();
    expect(row!.sobre_1_importe_cents).toBe(500000);
    expect(row!.sobre_2_hora).toBeNull();
    expect(row!.sobre_2_importe_cents).toBe(350000);
    expect(row!.total_ensobrado_cents).toBe(850000);
    expect(row!.puente_entrante).toBeCloseTo(1000.5, 5);
    expect(row!.puente_saliente).toBeCloseTo(900.25, 5);
    expect(row!.fondo_cambio_saliente_cents).toBeUndefined();
    expect(row!.vales_empleados_cents).toBeUndefined();
    expect(row!.opening_cents).toBeUndefined();
    expect(row!.observaciones).toBe('observación del turno');

    const detalle = await get(`/operativa/rendicion/${id}`, cookie);
    expect(detalle.status).toBe(200);
    const html = await detalle.text();
    expect(html).toContain('Ventas del turno');
    expect(html).toContain('Recaudación ensobrada');
    expect(html).toContain('Puente de medición');
    expect(html).toContain('1000.500 m³');
    expect(html).not.toContain('Fondo de cambio transferido');
    expect(html).not.toContain('Vales de empleados');
    expect(html).toContain('observación del turno');
    // Sin producto GNC: gnc_sales = 0, total = lubricantes 1000000 + varios 160000 = 1160000 cents = 11600.00 ARS
    // formatArs divide por 100, así que pasamos 11600
    expect(html).toContain(
      new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
      }).format(11600),
    );

    const listado = await get('/operativa/rendicion', cookie);
    expect(listado.status).toBe(200);
    const htmlListado = await listado.text();
    expect(htmlListado).toContain('Ventas');
    expect(htmlListado).toContain('Ensobrado');
    expect(htmlListado).toContain('Puente entr.');
    expect(htmlListado).toContain(formatArs(850000));
  });

  it('resumen diario: solo admin lo ve y puede guardar observaciones', async () => {
    const cliente = await register(
      'Cliente Resumen',
      uniqueEmail('resumen-cliente'),
    );
    expect((await get('/operativa/resumen-diario', cliente)).status).toBe(403);

    const gestor = await registerAs(
      'Gestor Resumen',
      uniqueEmail('resumen-gestor'),
      'store_manager',
    );
    expect((await get('/operativa/resumen-diario', gestor)).status).toBe(403);

    const admin = await registerAs(
      'Admin Resumen',
      uniqueEmail('resumen-admin'),
      'admin',
    );
    const pagina = await get('/operativa/resumen-diario', admin);
    expect(pagina.status).toBe(200);
    const html = await pagina.text();
    expect(html).toContain('Resumen diario de actividades');
    expect(html).toContain('TOTAL RECAUDACIÓN');
    expect(html).toContain('Mañana');
    expect(html).toContain('Tarde');
    expect(html).toContain('Noche');

    const guardado = await post(
      '/operativa/resumen-diario/observaciones',
      { fecha: '2026-11-05', observaciones: 'todo en orden' },
      admin,
    );
    expect(guardado.status).toBe(302);

    const tras = await get('/operativa/resumen-diario?fecha=2026-11-05', admin);
    expect(tras.status).toBe(200);
    expect(await tras.text()).toContain('todo en orden');
  });

  it('resumen diario: lub/varios desde la rendición, facturado playeros y facturación manual', async () => {
    const admin = await registerAs(
      'Admin Resumen Calculo',
      uniqueEmail('resumen-calc'),
      'admin',
    );
    const fecha = '2026-11-12';
    const dia = Date.UTC(2026, 10, 12);

    // Actividad turno mañana: ingreso 1000 m³, cierre 1200 m³, ventas 1180 m³, GNC $17.700
    await env.DB.prepare(
      `
      insert into daily_activities (store_id, report_date, shift, gnc_opening, gnc_closing, gnc_volume, gnc_sales_cents)
      values (1, ?, 'morning', 1000, 1200, 1180, 1770000)
    `,
    )
      .bind(dia)
      .run();

    // Rendición turno mañana: lubricantes $3.000 + varios $2.000
    await env.DB.prepare(
      `
      insert into shift_reports (store_id, report_date, shift, shift_start, shift_end, lubricantes_sales_cents, varios_sales_cents)
      values (1, ?, 'morning', ?, ?, 300000, 200000)
    `,
    )
      .bind(dia, dia, dia + 8 * 3600_000)
      .run();

    const pagina = await get(`/operativa/resumen-diario?fecha=${fecha}`, admin);
    expect(pagina.status).toBe(200);
    const html = await pagina.text();

    // Columna de totales del día
    expect(html).toContain('TOTALES');
    // Imp. lubricantes y varios = $3.000 + $2.000 (NO el total por medios de pago)
    expect(html).toContain(formatArs(500000));
    // Facturado playeros = GNC $17.700 + lub/varios $5.000 = $22.700
    expect(html).toContain(formatArs(2270000));

    // Verificar que ya no hay campos de facturación servicios/mini mercado
    const tras = await get(`/operativa/resumen-diario?fecha=${fecha}`, admin);
    expect(tras.status).toBe(200);
    const html2 = await tras.text();
    expect(html2).not.toContain('name="servicios_morning"');
    expect(html2).not.toContain('name="mini_morning"');
    expect(html2).not.toContain('Facturación servicios/materiales');
    expect(html2).not.toContain('Facturación mini mercado');
    // Total recaudación = facturado playeros (sin servicios ni mini mercado)
    expect(html2).toContain('TOTAL RECAUDACIÓN');
  });

  it('ventas: la pantalla queda de solo lectura (sin carga ni edición inline)', async () => {
    const cookie = await registerAs(
      'Ventas Solo Lectura',
      uniqueEmail('ventas-solo-lectura'),
      'admin',
    );
    const res = await get('/operativa/ventas', cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Resumen de ventas');
    expect(html).toContain('Total');
    expect(html).not.toContain('/operativa/ventas/nuevo');
    expect(html).not.toContain('Cargá un turno');
    expect(html).not.toContain('<details');
    // Las rutas siguen vivas: el alta directa por POST sigue funcionando.
    const alta = await post(
      '/operativa/ventas',
      {
        fecha: '2026-11-21',
        turno: 'night',
        efectivo: '10',
        tarjeta: '0',
        transferencia: '0',
        fiado: '0',
      },
      cookie,
    );
    expect(alta.status).toBe(302);
    const nuevo = await get('/operativa/ventas/nuevo', cookie);
    expect(nuevo.status).toBe(200);
  });

  it('actividades: sin botón de carga y con botón Imprimir', async () => {
    const cookie = await registerAs(
      'Actividades Sin Carga',
      uniqueEmail('actividades-sin-carga'),
      'admin',
    );
    const res = await get('/operativa/actividades', cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Resumen diario de actividades');
    expect(html).not.toContain('/operativa/actividades/nuevo');
    expect(html).not.toContain('Cargá un turno');
    expect(html).toContain('/operativa/actividades/print');
    // El formulario de alta sigue disponible por URL.
    const nuevo = await get('/operativa/actividades/nuevo', cookie);
    expect(nuevo.status).toBe(200);
  });

  it('rendición: el alta la ve solo el playero, no el admin', async () => {
    const admin = await registerAs(
      'Rendicion Admin Oculta',
      uniqueEmail('rendicion-admin-oculta'),
      'admin',
    );
    const listadoAdmin = await get('/operativa/rendicion', admin);
    expect(listadoAdmin.status).toBe(200);
    expect(await listadoAdmin.text()).not.toContain('Cargá una rendición');

    const playero = await registerAs(
      'Playero Rendicion',
      uniqueEmail('playero-rendicion'),
      'store_manager',
    );
    const listadoPlayero = await get('/operativa/rendicion', playero);
    expect(listadoPlayero.status).toBe(200);
    expect(await listadoPlayero.text()).toContain('Cargá una rendición');
  });

  it('rendición: al guardar se actualiza la planilla del resumen diario', async () => {
    const cookie = await registerAs(
      'Rendicion Planilla',
      uniqueEmail('rendicion-planilla'),
      'admin',
    );
    const creada = await post(
      '/operativa/rendicion',
      {
        fecha: '2026-11-23',
        turno: 'evening',
        inicio: '2026-11-23T14:00',
        fin: '2026-11-23T22:00',
        puente_entrante: '500',
        puente_saliente: '480',
        gnc_qty: '120',
        gnc_price: '1400',
        r0_surtidor: '1',
        r0_producto: 'gnc',
        r0_apertura: '1000',
        r0_cierre: '1070',
        r1_surtidor: '2',
        r1_producto: 'gnc',
        r1_apertura: '2000',
        r1_cierre: '2050',
      },
      cookie,
    );
    expect(creada.status).toBe(302);

    const fila = await env.DB
      .prepare(
        `select gnc_opening, gnc_closing, gnc_volume, gnc_sales_cents, pump_check, notes
         from daily_activities
         where report_date = ? and shift = 'evening'`,
      )
      .bind(Date.UTC(2026, 10, 23))
      .first<{
        gnc_opening: number;
        gnc_closing: number;
        gnc_volume: number;
        gnc_sales_cents: number;
        pump_check: number;
        notes: string | null;
      }>();
    expect(fila).toBeTruthy();
    expect(fila!.gnc_opening).toBe(3000);
    expect(fila!.gnc_closing).toBe(3120);
    expect(fila!.gnc_volume).toBe(120);
    expect(fila!.gnc_sales_cents).toBe(120 * 140000);
    // No se pisan los datos que se cargan a mano.
    expect(fila!.pump_check).toBe(0);
    expect(fila!.notes).toBeNull();
  });

  it('rendición: el precio GNC sale del producto de la tienda y si no hay, del formulario', async () => {
    const cookie = await registerAs(
      'Rendicion Precio GNC',
      uniqueEmail('rendicion-precio-gnc'),
      'admin',
    );

    // Sin producto GNC: se muestra el input editable y manda body.gnc_price.
    const sinProducto = await get('/operativa/rendicion/nuevo', cookie);
    expect(sinProducto.status).toBe(200);
    const htmlSin = await sinProducto.text();
    expect(htmlSin).toContain('name="gnc_price"');
    expect(htmlSin).toContain('type="number"');

    // Con producto GNC: se muestra el precio de la tienda y el input queda oculto.
    await env.DB.prepare(
      `insert into products (name, slug, price_cents, active, category)
       values ('GNC Común', 'gnc-comun-test', 130000, 1, 'gnc')`,
    ).run();
    try {
      const conProducto = await get('/operativa/rendicion/nuevo', cookie);
      expect(conProducto.status).toBe(200);
      const htmlCon = await conProducto.text();
      expect(htmlCon).toContain('Precio GNC (desde Tienda)');
      expect(htmlCon).toContain('name="gnc_price" type="hidden"');
      expect(htmlCon).toContain('value="1300.00"');

      // El POST prioriza el precio del producto de la tienda (130000 cents).
      const creada = await post(
        '/operativa/rendicion',
        {
          fecha: '2026-11-22',
          turno: 'morning',
          inicio: '2026-11-22T06:00',
          fin: '2026-11-22T14:00',
          puente_entrante: '1000',
          puente_saliente: '990',
          gnc_qty: '10',
          gnc_price: '999',
          r0_surtidor: '1',
          r0_producto: 'gnc',
          r0_apertura: '0',
          r0_cierre: '10',
        },
        cookie,
      );
      expect(creada.status).toBe(302);
      const id = Number(/rendicion\/(\d+)/.exec(creada.headers.get('location') ?? '')?.[1]);
      const row = await env.DB
        .prepare('select price_cents, gnc_sales_cents from shift_reports where id = ?')
        .bind(id)
        .first<{ price_cents: number; gnc_sales_cents: number }>();
      expect(row?.price_cents).toBe(130000);
      expect(row?.gnc_sales_cents).toBe(10 * 130000);
    } finally {
      // No dejar el producto GNC puesto: rompería los otros tests de rendición.
      await env.DB.prepare(`delete from products where slug = 'gnc-comun-test'`).run();
    }
  });
});

describe('impresión PDF (endpoints /print)', () => {
  async function expectPdf(res: Response, filename: string) {
    expect(res.status).toBe(200);
    expect(res.headers.get('content-type')).toBe('application/pdf');
    expect(res.headers.get('content-disposition')).toContain(filename);
    const buf = new Uint8Array(await res.arrayBuffer());
    const magic = String.fromCharCode(buf[0] ?? 0, buf[1] ?? 0, buf[2] ?? 0, buf[3] ?? 0);
    expect(magic).toBe('%PDF');
    expect(buf.byteLength).toBeGreaterThan(500);
  }

  it('GET /operativa/actividades/print devuelve PDF (landscape) para admin', async () => {
    const cookie = await registerAs('Admin Print Act', uniqueEmail('admin-print-act'), 'admin');
    const res = await get('/operativa/actividades/print?fecha=2026-11-18', cookie);
    await expectPdf(res, 'resumen-diario-2026-11-18.pdf');
  });

  it('GET /operativa/caja/print devuelve PDF (portrait) para admin', async () => {
    const cookie = await registerAs('Admin Print Caja', uniqueEmail('admin-print-caja'), 'admin');
    const res = await get('/operativa/caja/print?fecha=2026-11-18', cookie);
    await expectPdf(res, 'caja-diaria-2026-11-18.pdf');
  });

  it('GET /operativa/actividades/print sin sesión redirige a login', async () => {
    const res = await get('/operativa/actividades/print?fecha=2026-11-18');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('GET /operativa/caja/print sin sesión redirige a login', async () => {
    const res = await get('/operativa/caja/print?fecha=2026-11-18');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('GET /operativa/actividades/print con rol playero devuelve 403', async () => {
    const cookie = await registerAs('Playero Print', uniqueEmail('playero-print'), 'store_manager');
    const res = await get('/operativa/actividades/print?fecha=2026-11-18', cookie);
    expect(res.status).toBe(403);
    const html = await res.text();
    expect(html).toContain('No tenés permiso');
  });

  it('GET /operativa/caja/print con rol cliente devuelve 403', async () => {
    const cookie = await registerAs('Cliente Print', uniqueEmail('cliente-print'), 'customer');
    const res = await get('/operativa/caja/print?fecha=2026-11-18', cookie);
    expect(res.status).toBe(403);
  });

  async function crearRendicion(cookie: string, fecha: string): Promise<number> {
    const res = await post(
      '/operativa/rendicion',
      {
        fecha,
        puente_entrante: '1000.5',
        puente_saliente: '999.25',
        lubricantes_sales: '2000',
        varios_qty: '1',
        varios_price: '800',
        sobre1_importe: '3000',
        notas: 'turno de prueba para imprimir',
        r0_apertura: '100',
        r0_cierre: '150',
        r1_apertura: '200',
        r1_cierre: '240',
      },
      cookie,
    );
    expect(res.status).toBe(302);
    const id = Number(/rendicion\/(\d+)/.exec(res.headers.get('location') ?? '')?.[1]);
    expect(id).toBeGreaterThan(0);
    return id;
  }

  it('GET /operativa/rendicion/:id/print devuelve PDF para admin y para playero', async () => {
    const admin = await registerAs('Admin Print Rend', uniqueEmail('admin-print-rend'), 'admin');
    const id = await crearRendicion(admin, '2026-11-19');

    const paraAdmin = await get(`/operativa/rendicion/${id}/print`, admin);
    await expectPdf(paraAdmin, 'rendicion-2026-11-19.pdf');

    const playero = await registerAs('Playero Print Rend', uniqueEmail('playero-print-rend'), 'store_manager');
    const paraPlayero = await get(`/operativa/rendicion/${id}/print`, playero);
    await expectPdf(paraPlayero, 'rendicion-2026-11-19.pdf');
  });

  it('GET /operativa/rendicion/:id/print sin sesión redirige a login', async () => {
    const res = await get('/operativa/rendicion/1/print');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('GET /operativa/rendicion/:id/print con rol cliente devuelve 403', async () => {
    const admin = await registerAs('Admin Print Rend 2', uniqueEmail('admin-print-rend-2'), 'admin');
    const id = await crearRendicion(admin, '2026-11-20');
    const cliente = await registerAs('Cliente Print Rend', uniqueEmail('cliente-print-rend'), 'customer');
    const res = await get(`/operativa/rendicion/${id}/print`, cliente);
    expect(res.status).toBe(403);
    const html = await res.text();
    expect(html).toContain('No tienes permiso');
  });

  it('las pantallas de caja y resumen muestran el botón Imprimir', async () => {
    const admin = await registerAs('Admin Boton Imprimir', uniqueEmail('admin-boton-imprimir'), 'admin');

    const caja = await get('/operativa/caja?fecha=2026-11-18', admin);
    expect(caja.status).toBe(200);
    const htmlCaja = await caja.text();
    expect(htmlCaja).toContain('/operativa/caja/print?fecha=2026-11-18');
    expect(htmlCaja).toContain('>Imprimir<');

    const resumen = await get('/operativa/resumen-diario?fecha=2026-11-18', admin);
    expect(resumen.status).toBe(200);
    expect(await resumen.text()).toContain('/operativa/actividades/print?fecha=2026-11-18');

    const detalle = await get('/operativa/rendicion', admin);
    expect(detalle.status).toBe(200);
  });

  it('el detalle de la rendición enlaza al PDF con el botón Imprimir', async () => {
    const admin = await registerAs('Admin Detalle Print', uniqueEmail('admin-detalle-print'), 'admin');
    const id = await crearRendicion(admin, '2026-11-24');
    const detalle = await get(`/operativa/rendicion/${id}`, admin);
    expect(detalle.status).toBe(200);
    const html = await detalle.text();
    expect(html).toContain(`/operativa/rendicion/${id}/print`);
    expect(html).toContain('>Imprimir<');
  });
});
