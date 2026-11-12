#!/usr/bin/env bash
# Configurar Cloudflare Images para /tienda (product images)
# Requiere: CLOUDFLARE_API_TOKEN, CLOUDFLARE_ACCOUNT_ID

set -euo pipefail

ACCOUNT_ID="${CLOUDFLARE_ACCOUNT_ID:-}"
TOKEN="${CLOUDFLARE_API_TOKEN:-}"

if [ -z "$ACCOUNT_ID" ] || [ -z "$TOKEN" ]; then
  echo "Error: Export CLOUDFLARE_API_TOKEN y CLOUDFLARE_ACCOUNT_ID"
  exit 1
fi

echo "=== Configurando Cloudflare Images ==="

# 1. Verificar si Images está habilitado en la cuenta
echo "Verificando plan Images..."
IMAGES_INFO=$(curl -sS -X GET \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/images/v1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "$IMAGES_INFO" | jq .

# 2. Crear variante para thumbnails (tienda listing)
echo "Creando variante 'thumbnail' (300x300 fit)..."
curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/images/v1/variants" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "thumbnail",
    "options": {
      "width": 300,
      "height": 300,
      "fit": "scale-down",
      "metadata": "none"
    },
    "never_require_signed_urls": true
  }' | jq .

# 3. Crear variante 'detail' (800x800 para vista detalle)
echo "Creando variante 'detail' (800x800 fit)..."
curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/images/v1/variants" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "detail",
    "options": {
      "width": 800,
      "height": 800,
      "fit": "scale-down",
      "metadata": "none"
    },
    "never_require_signed_urls": true
  }' | jq .

# 4. Crear variante 'original' (sin transformar, para descarga)
echo "Creando variante 'original' (sin redimensionar)..."
curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/images/v1/variants" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "original",
    "options": {
      "fit": "scale-down",
      "metadata": "all"
    },
    "never_require_signed_urls": true
  }' | jq .

# 5. Configurar Cache Rules para /tienda/* (HTML cacheable para no-logueados)
echo "Configurando Cache Rules para /tienda/..."
# Esto se hace mejor en Dashboard > Rules > Cache Rules
# Pero se puede intentar via API si está disponible

echo ""
echo "✅ Cloudflare Images configurado. Variantes creadas:"
echo "  - thumbnail: 300x300 (listados tienda)"
echo "  - detail: 800x800 (vista detalle producto)"
echo "  - original: sin cambios (descarga)"
echo ""
echo "📝 Próximos pasos manuales en Dashboard:"
echo "  1. Rules > Cache Rules > Create Rule:"
echo "     Expression: (http.host eq \"app.lescognc.workers.dev\" and http.request.uri.path matches \"^/tienda/\" and not http.cookie contains \"session=\")"
echo "     Cache Level: Cache Everything"
echo "     Edge TTL: 1 hour"
echo "     Browser TTL: 30 minutes"
echo "  2. Rules > Cache Rules > Create Rule para imágenes:"
echo "     Expression: (http.request.uri.path matches \"^/tienda/.*\\.(jpg|jpeg|png|webp|avif)$\")"
echo "     Cache Level: Cache Everything"
echo "     Edge TTL: 1 year"
echo "     Browser TTL: 1 year"
echo ""
echo "🔧 Migración de imágenes existentes:"
echo "  - Las imágenes en R2 bucket 'gas-station-assets' deben subirse a Images"
echo "  - Usar: wrangler r2 object list gas-station-assets | ... | curl -X POST .../images/v1"