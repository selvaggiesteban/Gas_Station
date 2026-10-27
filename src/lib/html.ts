export type PageOptions = {
  title: string;
  brand?: string;
  user?: { name: string; points: number; role: string } | null;
  error?: string | null;
  notice?: string | null;
  landing?: boolean;
};

const LOGO_SVG = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:block;">
  <path d="M12 22c-4.97 0-9-4.03-9-9s4.03-9 9-9 9 4.03 9 9-4.03 9-9 9zM12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
</svg>`;

const BASE_STYLE = `
:root { color-scheme: light;
  --bg: #fafafa; --fg: #0a0a0a; --surface: #ffffff; --border: #e4e4e7;
  --muted: #71717a; --brand: #0a0a0a; --on-brand: #ffffff; --brand-contrast: #ffffff;
  --danger-bg: #18181b; --danger-fg: #ffffff; --danger-border: #18181b;
  --ok-bg: #f4f4f5; --ok-fg: #18181b; --ok-border: #a1a1aa; }
body.public { --brand: #0a0a0a; --on-brand: #ffffff; --brand-contrast: #ffffff; }
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
  background: var(--bg); color: var(--fg); line-height: 1.5; }
.wrap { max-width: 42rem; margin: 0 auto; padding: 1.5rem; }
header.top .wrap { max-width: 1366px; }
.foot .wrap { max-width: 1366px; }
.card { background: var(--surface); border: 1px solid var(--border);
  border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem; }
h1 { font-size: 1.35rem; margin: 0 0 0.75rem; }
h2 { font-size: 1.05rem; margin: 0 0 0.5rem; }
h3 { font-size: 1rem; margin: 0 0 0.35rem; }
label { display: block; font-size: 0.875rem; font-weight: 600; margin: 0.75rem 0 0.25rem; }
input, select, textarea { width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border);
  border-radius: 0.5rem; font: inherit; background: var(--surface); color: var(--fg); }
button, .btn { display: inline-block; margin-top: 1rem; padding: 0.6rem 1.1rem; border: 0;
  border-radius: 0.5rem; background: var(--brand); color: var(--on-brand); font: inherit;
  font-weight: 600; cursor: pointer; text-decoration: none; }
button:disabled { opacity: 0.6; cursor: not-allowed; }
button.secondary, .btn.secondary { background: #3f3f46; color: #ffffff; }
.alert-error { background: var(--danger-bg); border: 1px solid var(--danger-border);
  color: var(--danger-fg); padding: 0.6rem 0.8rem; border-radius: 0.5rem;
  font-size: 0.9rem; font-weight: 600; }
.alert-ok { background: var(--ok-bg); border: 1px solid var(--ok-border); color: var(--ok-fg);
  padding: 0.6rem 0.8rem; border-radius: 0.5rem; font-size: 0.9rem; }
.muted { color: var(--muted); font-size: 0.875rem; }
.row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
.pill { background: var(--brand); color: var(--on-brand); border-radius: 999px;
  padding: 0.1rem 0.6rem; font-size: 0.8rem; font-weight: 700; }
a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }
nav.bottom a { margin-right: 1rem; color: var(--muted); text-decoration: none; font-size: 0.9rem; }
header.top { background: var(--fg); color: #ffffff; position: sticky; top: 0; z-index: 40; }
header.top .wrap { display: flex; align-items: center; justify-content: space-between;
  gap: 0.75rem; padding: 0.75rem 1.5rem; flex-wrap: wrap; }
header.top a { color: #ffffff; text-decoration: none; font-weight: 600; }
header.top .btn, header.top button { background: #ffffff; color: var(--fg); margin-top: 0;
  padding: 0.45rem 0.9rem; }
.brand { display: flex; align-items: center; gap: 0.5rem; }
.brand svg { width: 28px; height: 28px; flex-shrink: 0; }
main.landing { max-width: 72rem; }
.hero { padding: 3.5rem 0 1rem; }
.hero h1 { font-size: clamp(2rem, 6vw, 3rem); line-height: 1.1; margin: 0 0 1rem;
  max-width: 22ch; }
.hero .lead { font-size: 1.15rem; color: var(--muted); max-width: 44rem; margin: 0 0 1.5rem; }
.cta-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.cta-row .btn { margin-top: 0; }
.btn.lg { padding: 0.8rem 1.5rem; font-size: 1.05rem; }
.section { margin-top: 3.5rem; scroll-margin-top: 5.5rem; }
.section > h2 { font-size: 1.75rem; margin: 0 0 0.35rem; }
.section > .lead { color: var(--muted); margin: 0 0 1.25rem; max-width: 46rem; }
.grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); }
.grid .card { margin-bottom: 0; }
.grid.plans { grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
.feature p { margin: 0; color: var(--muted); font-size: 0.95rem; }
.plan { display: flex; flex-direction: column; }
.plan .pill { display: inline-block; margin-bottom: 0.5rem; align-self: flex-start; }
.plan.featured { border: 2px solid var(--fg); }
.plan .price { font-size: 2rem; font-weight: 700; margin: 0.5rem 0 0.75rem; }
.plan .price span { font-size: 0.9rem; font-weight: 500; color: var(--muted); }
.plan .features { margin-bottom: 1.25rem; }
.plan .btn { margin-top: auto; }
.features { list-style: none; padding: 0; margin: 0; }
.features li { padding: 0.5rem 0; border-top: 1px solid var(--border); font-size: 0.95rem; }
.features li:first-child { border-top: 0; }
.contact { max-width: 32rem; }
.contact textarea { resize: vertical; }
.foot { background: var(--fg); color: #ffffff; margin-top: 4rem; }
.foot .wrap { max-width: 72rem; display: flex; gap: 0.75rem; align-items: center;
  justify-content: space-between; flex-wrap: wrap; padding-top: 1.25rem; padding-bottom: 1.25rem; }
.foot .muted { color: #a1a1aa; }
@media (max-width: 48rem) {
  .wrap { padding: 1rem; }
  header.top .wrap { padding: 0.65rem 1rem; }
  .hero { padding-top: 2.25rem; }
  .hero h1 { font-size: 2rem; }
  .section { margin-top: 2.5rem; }
  .section > h2 { font-size: 1.4rem; }
  .foot .wrap { justify-content: flex-start; }
}
`;

const AUTH_STYLE = `body { --brand: #eb7716; --on-brand: #ffffff; --brand-contrast: #eb7716; }`;

function esc(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export function escapeHtml(value: string): string {
  return esc(value);
}

function headerNav(opts: PageOptions): string {
  if (opts.user) {
    return `<div class="row">
        <a href="/app">Inicio</a>
        <a href="/tienda">Tienda</a>
        <span class="pill">${esc(opts.user.name)} · ${opts.user.points} pts</span>
        <form method="post" action="/logout" style="display:inline">
          <button class="secondary" style="margin-top:0;padding:0.35rem 0.7rem">Cerrar sesión</button>
        </form>
      </div>`;
  }
  if (opts.landing) {
    return `<nav class="row">
        <a href="#incluye">Qué incluye</a>
        <a href="#precios">Precios</a>
        <a href="#contacto">Contacto</a>
        <a href="/login">Accedé</a>
        <a class="btn" href="/registro">Creá tu cuenta</a>
      </nav>`;
  }
  return `<div class="row"><a href="/login">Accedé</a><a href="/registro">Creá tu cuenta</a></div>`;
}

export function page(opts: PageOptions, body: string): string {
  const brand = opts.brand ?? 'Gas Station';
  const nav = headerNav(opts);

  const alerts = [
    opts.error ? `<div class="alert-error">${esc(opts.error)}</div>` : '',
    opts.notice ? `<div class="alert-ok">${esc(opts.notice)}</div>` : '',
  ].join('');

  const footer = opts.landing
    ? `<footer class="foot"><div class="wrap">
  <span class="brand">${LOGO_SVG}<span>${esc(brand)}</span></span>
  <span class="muted">© ${new Date().getFullYear()} ${esc(brand)}. Todos los derechos reservados.</span>
</div></footer>`
    : '';

  return `<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(opts.title)} · ${esc(brand)}</title>
<style>${BASE_STYLE}${opts.user ? AUTH_STYLE : ''}</style>
</head>
<body${opts.user ? '' : ' class="public"'}>
<header class="top"><div class="wrap">
  <a class="brand" href="/">${LOGO_SVG}<span>${esc(brand)}</span></a>
  ${nav}
</div></header>
<main class="wrap${opts.landing ? ' landing' : ''}">
${alerts}
${body}
</main>
${footer}
</body>
</html>`;
}
