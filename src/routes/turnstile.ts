import { Hono } from 'hono';
import type { Context } from 'hono';
import type { AppEnv } from '../env';

export const turnstileRoutes = new Hono<AppEnv>();

interface TurnstileVerifyResponse {
  success: boolean;
  challenge_ts?: string;
  hostname?: string;
  'error-codes'?: string[];
  action?: string;
  ts?: number;
}

/**
 * Verify a Turnstile token via the siteverify API.
 * This endpoint is called from the frontend to validate the CAPTCHA.
 */
turnstileRoutes.post('/turnstile/verify', async (c) => {
  const body = await c.req.json();
  const token = body?.token;
  const action = body?.action;

  if (!token || typeof token !== 'string') {
    return c.json({ success: false, error: 'Missing token' }, 400);
  }

  const secret = c.env.TURNSTILE_SECRET_KEY;
  if (!secret) {
    console.error('TURNSTILE_SECRET_KEY not configured');
    return c.json({ success: false, error: 'Server configuration error' }, 500);
  }

  try {
    const response = await fetch('https://challenges.cloudflare.com/turnstile/v0/siteverify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        secret,
        response: token,
      }),
    });

    const result = await response.json() as TurnstileVerifyResponse;

    // Add custom metadata for our tracking
    if (result.success) {
      result.action = action || 'unknown';
      result.ts = Date.now();
    }

    return c.json(result);
  } catch (err) {
    console.error('Turnstile verification failed:', err);
    return c.json({ success: false, error: 'Verification service unavailable' }, 502);
  }
});

export function getTurnstileSiteKey(c: Context<AppEnv>): string {
  return c.env.TURNSTILE_SITE_KEY || '';
}