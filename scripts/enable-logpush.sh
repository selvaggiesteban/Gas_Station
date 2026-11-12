#!/usr/bin/env bash
# Enable Logpush to R2 for Workers logs
# Requires: CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID environment variables

set -euo pipefail

ACCOUNT_ID="${CLOUDFLARE_ACCOUNT_ID:-}"
TOKEN="${CLOUDFLARE_API_TOKEN:-}"

if [ -z "$ACCOUNT_ID" ] || [ -z "$TOKEN" ]; then
  echo "Error: CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID must be set"
  exit 1
fi

WORKER_NAME="app"
DESTINATION="r2"
BUCKET="logs-workers"
DATASET="workers_traces"  # or "workers_logs" for standard logs

echo "Creating Logpush job for worker '$WORKER_NAME' to R2 bucket '$BUCKET'..."

# Create Logpush job
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
  echo "Logpush job created successfully! Job ID: $job_id"
else
  echo "Failed to create Logpush job"
  exit 1
fi