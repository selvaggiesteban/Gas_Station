#!/usr/bin/env bash
# Turnstile Setup Script for LESCO GNC
# Run this after creating an API Token with Account.Turnstile:Edit and Account.Workers Scripts:Edit scopes

set -euo pipefail

ACCOUNT_ID="${CLOUDFLARE_ACCOUNT_ID:-}"
TOKEN="${CLOUDFLARE_API_TOKEN:-}"

if [ -z "$ACCOUNT_ID" ] || [ -z "$TOKEN" ]; then
  echo "Error: Set CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID environment variables"
  echo ""
  echo "Create an API Token at: https://dash.cloudflare.com/profile/api-tokens"
  echo "  Permissions:"
  echo "    - Account > Turnstile > Edit"
  echo "    - Account > Workers Scripts > Edit"
  echo "  Account Resources: Include > Specific account > Your account"
  exit 1
fi

echo "=== Turnstile Setup for LESCO GNC ==="
echo "Account ID: $ACCOUNT_ID"
echo ""

# 1. Create Turnstile Widget
echo "1. Creating Turnstile widget..."
WIDGET_RESPONSE=$(curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/challenges/widgets" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "LESCO GNC Production",
    "mode": "managed",
    "domains": ["app.lescognc.workers.dev", "localhost", "127.0.0.1"]
  }')

echo "$WIDGET_RESPONSE" | jq .

SITEKEY=$(echo "$WIDGET_RESPONSE" | jq -r '.result.sitekey')
SECRET=$(echo "$WIDGET_RESPONSE" | jq -r '.result.secret')

if [ "$SITEKEY" = "null" ] || [ -z "$SITEKEY" ]; then
  echo "Error: Failed to create widget"
  exit 1
fi

echo ""
echo "Widget created successfully!"
echo "Site Key: $SITEKEY"
echo "Secret: $SECRET"
echo ""

# 2. Create siteverify Worker
echo "2. Creating siteverify Worker..."
WORKER_NAME="turnstile-siteverify-lesco-gnc"
WORKER_RESPONSE=$(curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/workers/scripts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/javascript" \
  --data-binary @- << 'WORKER_CODE'
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
WORKER_CODE
)

echo "$WORKER_RESPONSE" | jq .

# 3. Set secret on Worker
echo "3. Setting TURNSTILE_SECRET_KEY on Worker..."
SECRET_RESPONSE=$(echo -n "$SECRET" | npx wrangler secret put TURNSTILE_SECRET_KEY --name "$WORKER_NAME" 2>&1)
echo "$SECRET_RESPONSE"

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Add these to your wrangler.jsonc or set as secrets:"
echo "  TURNSTILE_SITE_KEY=$SITEKEY"
echo "  TURNSTILE_SECRET_KEY=$SECRET"
echo ""
echo "Then run: npx wrangler deploy"
echo ""
echo "Test the widget at: https://app.lescognc.workers.dev/login"