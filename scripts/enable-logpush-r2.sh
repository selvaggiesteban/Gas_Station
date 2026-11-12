#!/usr/bin/env bash
# Habilitar Logpush a R2 para Workers Logs
# Requiere: CLOUDFLARE_API_TOKEN y CLOUDFLARE_ACCOUNT_ID

set -euo pipefail

ACCOUNT_ID="${CLOUDFLARE_ACCOUNT_ID:-}"
TOKEN="${CLOUDFLARE_API_TOKEN:-}"

if [ -z "$ACCOUNT_ID" ] || [ -z "$TOKEN" ]; then
  echo "Error: Export CLOUDFLARE_API_TOKEN y CLOUDFLARE_ACCOUNT_ID"
  exit 1
fi

echo "=== Creando Logpush job para Workers Logs ==="
echo "Account: $ACCOUNT_ID"
echo "Destino: R2 bucket 'logs-workers'"

# Verificar que el bucket existe
echo "Verificando bucket logs-workers..."
wrangler r2 bucket list | grep -q "logs-workers" || {
  echo "Bucket 'logs-workers' no existe. Creando..."
  wrangler r2 bucket create logs-workers
}

# Crear Logpush job
response=$(curl -sS -X POST \
  "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT_ID/logpush/jobs" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "workers-logs-to-r2",
    "logpull_options": "fields=EventTimestamp,EventType,RayID,ClientIP,ClientRequestHost,ClientRequestMethod,ClientRequestPath,ClientRequestQuery,ClientRequestUserAgent,EdgeResponseStatus,EdgeResponseContentType,EdgeStartTimestamp,EdgeEndTimestamp,OriginResponseStatus,OriginResponseContentType,OriginStartTimestamp,OriginEndTimestamp,WorkerCPUTime,WorkerWallTime,WorkerMemoryUsage,WorkerSubrequestCount,WorkerSubrequestCPUTime,WorkerSubrequestWallTime,WorkerStatus,WorkerErrorMessage,WorkerErrorType,Outcome,CacheCacheStatus,CacheResponseBytes,CacheResponseStatus,CacheTTL,CacheTieredFill,WorkerScriptName,WorkerDeploymentID,RequestHeaders,ResponseHeaders&timestamps=rfc3339",
    "destination_conf": "r2://logs-workers/workers-logs/{DATE}?account_id='$ACCOUNT_ID'",
    "dataset": "workers_logs",
    "enabled": true,
    "frequency": "high"
  }')

echo "$response" | jq .

success=$(echo "$response" | jq -r '.success')
if [ "$success" = "true" ]; then
  job_id=$(echo "$response" | jq -r '.result.id')
  echo "✅ Logpush job creado! Job ID: $job_id"
  echo "Los logs llegarán a: r2://logs-workers/workers-logs/YYYY-MM-DD/"
else
  echo "❌ Error creando Logpush job"
  exit 1
fi