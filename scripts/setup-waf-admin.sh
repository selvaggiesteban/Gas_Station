#!/usr/bin/env bash
# Configurar WAF Rules para proteger /admin/*
# Requiere: CLOUDFLARE_API_TOKEN, CLOUDFLARE_ACCOUNT_ID, CLOUDFLARE_ZONE_ID

set -euo pipefail

ACCOUNT_ID="${CLOUDFLARE_ACCOUNT_ID:-}"
ZONE_ID="${CLOUDFLARE_ZONE_ID:-}"
TOKEN="${CLOUDFLARE_API_TOKEN:-}"

if [ -z "$ACCOUNT_ID" ] || [ -z "$ZONE_ID" ] || [ -z "$TOKEN" ]; then
  echo "Error: Export CLOUDFLARE_API_TOKEN, CLOUDFLARE_ACCOUNT_ID, CLOUDFLARE_ZONE_ID"
  echo "Zone ID se obtiene en: Dashboard > Domain > Overview > API > Zone ID"
  exit 1
fi

echo "=== Configurando WAF Rules para /admin/* ==="
echo "Zone: $ZONE_ID"

# 1. Regla: Bloquear acceso a /admin/* fuera de Argentina (geo-block)
echo "1. Creando regla geo-block para /admin/*..."
curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/rulesets/phases/http_request_firewall_managed/entries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "block",
    "action_parameters": {},
    "description": "Block /admin/* access outside Argentina",
    "enabled": true,
    "expression": "(http.request.uri.path matches \"^/admin/.*\" and ip.geoip.country ne \"AR\")",
    "logging": true
  }' | jq .

# 2. Regla: Rate limiting estricto en /admin/*
echo "2. Creando rate limit para /admin/* (10 req/min)..."
curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/rulesets/phases/http_request_firewall_managed/entries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "rate_limit",
    "action_parameters": {
      "rate_limit": {
        "requests": 10,
        "period": 60,
        "mitigation_timeout": 300
      }
    },
    "description": "Rate limit /admin/* to 10 req/min",
    "enabled": true,
    "expression": "http.request.uri.path matches \"^/admin/.*\"",
    "logging": true
  }' | jq .

# 3. Regla: Managed Ruleset OWASP (si no está activo)
echo "3. Verificando Managed Ruleset OWASP..."
RULESETS=$(curl -sS -X GET \
  "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/rulesets/phases/http_request_firewall_managed" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "$RULESETS" | jq .

echo ""
echo "✅ WAF rules configuradas. Verifica en Dashboard > Security > WAF > Custom Rules"
echo "Reglas creadas:"
echo "  - Bloqueo geo /admin/* fuera de AR"
echo "  - Rate limit 10 req/min en /admin/*"
echo "  - OWASP Managed Ruleset (verificar activado)"