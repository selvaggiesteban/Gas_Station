# Turnstile Setup Instructions for LESCO GNC

## Prerequisites

Create an **API Token** with these scopes:
- `Account > Turnstile > Edit`
- `Account > Workers Scripts > Edit`

At: https://dash.cloudflare.com/profile/api-tokens → Custom Token

Account Resources: **Include > Specific account > `Lescogncfullshopcafe@gmail.com's Account` (ID: `69f95d8457f001712efcf4d21c0e63a0`)**

## Quick Setup (Run these commands)

```bash
# 1. Export your API token
export CLOUDFLARE_API_TOKEN="your_token_here"
export CLOUDFLARE_ACCOUNT_ID="69f95d8457f001712efcf4d21c0e63a0"

# 2. Create Turnstile widget
curl -sS -X POST "https://api.cloudflare.com/client/v4/accounts/$CLOUDFLARE_ACCOUNT_ID/challenges/widgets" \
  -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "LESCO GNC Production",
    "mode": "managed",
    "domains": ["app.lescognc.workers.dev", "localhost", "127.0.0.1"]
  }' | jq .

# 3. Save the sitekey and secret from the response
# Example response:
# {
#   "result": {
#     "sitekey": "0x4AAAAAAA...",
#     "secret": "0x4AAAAAAA..."
#   }
# }

# 4. Set secrets on the Worker (replace with your values)
npx wrangler secret put TURNSTILE_SITE_KEY --name app
# Enter: 0x4AAAAAAA... (sitekey)

npx wrangler secret put TURNSTILE_SECRET_KEY --name app
# Enter: 0x4AAAAAAA... (secret)

# 5. Deploy
npx wrangler deploy
```

## What's Already Implemented

✅ **Frontend widget** on:
- `/login` (data-action="login")
- `/registro` (data-action="register")
- `/checkout` (data-action="checkout")

✅ **Backend verification endpoint**: `POST /turnstile/verify`
- Called internally from login/registro/checkout handlers
- Verifies token with Cloudflare's siteverify API
- Adds action + timestamp metadata

✅ **Turnstile script** loaded globally in HTML head:
```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
```

✅ **Conditional rendering**: Widget only appears when `TURNSTILE_SITE_KEY` is configured

## Testing

After setting secrets and deploying:

1. Visit https://app.lescognc.workers.dev/login
2. Turnstile widget should appear above "Accedé" button
3. Complete CAPTCHA → form submits normally
4. Check Worker logs: `wrangler tail --format json`

## Files Modified

| File | Changes |
|------|---------|
| `src/routes/turnstile.ts` | New: Verification endpoint |
| `src/routes/auth.ts` | Login/Registro forms + verification |
| `src/routes/shop.ts` | Checkout form + verification |
| `src/lib/html.ts` | Global Turnstile script |
| `src/env.ts` | Turnstile env bindings |
| `src/index.ts` | Route registration |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Widget not showing | Check `TURNSTILE_SITE_KEY` secret is set |
| "Verification failed" | Check `TURNSTILE_SECRET_KEY` secret is set |
| 500 error on verify | Check Worker logs: `wrangler tail` |
| localhost not working | Add `localhost`, `127.0.0.1` to widget domains |

## Alternative: Use Setup Script

```bash
# Make executable
chmod +x scripts/setup-turnstile.sh

# Run (requires CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID env vars)
./scripts/setup-turnstile.sh
```