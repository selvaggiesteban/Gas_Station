export default {
  async fetch(request, env) {
    if (request.method !== 'POST') {
      return new Response('Method not allowed', { status: 405 });
    }
    const { token, action } = await request.json();
    if (!token) {
      return new Response(JSON.stringify({ success: false, error: 'Missing token' }), { status: 400 });
    }
    const response = await fetch('https://challenges.cloudflare.com/turnstile/v0/siteverify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ secret: env.TURNSTILE_SECRET_KEY, response: token }),
    });
    const result = await response.json();
    if (result.success) {
      result.action = action || 'unknown';
      result.ts = Date.now();
    }
    return new Response(JSON.stringify(result), { headers: { 'Content-Type': 'application/json' } });
  }
}