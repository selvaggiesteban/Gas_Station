export type PageOptions = {
  title: string;
  brand?: string;
  user?: { name: string; points: number; role: string } | null;
  error?: string | null;
  notice?: string | null;
};

const BASE_STYLE = `
:root { color-scheme: light; }
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
  background: #f5f4f2; color: #1c1917; line-height: 1.5; }
.wrap { max-width: 42rem; margin: 0 auto; padding: 1.5rem; }
header.top { background: #1c1917; color: #fff; }
header.top .wrap { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.5rem; }
header.top a { color: #fff; text-decoration: none; font-weight: 600; }
.brand { display: flex; align-items: center; gap: 0.5rem; }
.brand .dot { width: 0.9rem; height: 0.9rem; border-radius: 999px; background: #eb7716; display: inline-block; }
.card { background: #fff; border: 1px solid #e7e5e4; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem; }
h1 { font-size: 1.35rem; margin: 0 0 0.75rem; }
h2 { font-size: 1.05rem; margin: 0 0 0.5rem; }
label { display: block; font-size: 0.875rem; font-weight: 600; margin: 0.75rem 0 0.25rem; }
input, select, textarea { width: 100%; padding: 0.55rem 0.7rem; border: 1px solid #d6d3d1;
  border-radius: 0.5rem; font: inherit; background: #fff; }
button, .btn { display: inline-block; margin-top: 1rem; padding: 0.6rem 1.1rem; border: 0;
  border-radius: 0.5rem; background: #eb7716; color: #fff; font-weight: 600; font: inherit;
  cursor: pointer; text-decoration: none; }
button.secondary, .btn.secondary { background: #57534e; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
  padding: 0.6rem 0.8rem; border-radius: 0.5rem; font-size: 0.9rem; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;
  padding: 0.6rem 0.8rem; border-radius: 0.5rem; font-size: 0.9rem; }
.muted { color: #78716c; font-size: 0.875rem; }
.row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
.pill { background: #eb7716; color: #fff; border-radius: 999px; padding: 0.1rem 0.6rem;
  font-size: 0.8rem; font-weight: 700; }
nav.bottom a { margin-right: 1rem; color: #44403c; text-decoration: none; font-size: 0.9rem; }
`;

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

export function page(opts: PageOptions, body: string): string {
  const brand = opts.brand ?? 'Gas Station';
  const nav = opts.user
    ? `<div class="row">
        <a href="/app">Inicio</a>
        <a href="/tienda">Tienda</a>
        <span class="pill">${esc(opts.user.name)} · ${opts.user.points} pts</span>
        <form method="post" action="/logout" style="display:inline">
          <button class="secondary" style="margin-top:0;padding:0.35rem 0.7rem">Cerrar sesión</button>
        </form>
      </div>`
    : `<div class="row"><a href="/login">Accedé</a><a href="/registro">Creá tu cuenta</a></div>`;

  const alerts = [
    opts.error ? `<div class="alert-error">${esc(opts.error)}</div>` : '',
    opts.notice ? `<div class="alert-ok">${esc(opts.notice)}</div>` : '',
  ].join('');

  return `<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(opts.title)} · ${esc(brand)}</title>
<style>${BASE_STYLE}</style>
</head>
<body>
<header class="top"><div class="wrap">
  <a class="brand" href="/"><span class="dot"></span>${esc(brand)}</a>
  ${nav}
</div></header>
<main class="wrap">
${alerts}
${body}
</main>
</body>
</html>`;
}
