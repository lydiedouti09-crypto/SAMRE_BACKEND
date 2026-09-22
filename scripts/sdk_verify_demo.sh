#!/usr/bin/env bash

# Exemple d’utilisation local :
# 1) export des variables
# 2) lancer le script
#
# Exemple :
# export BASE_URL="http://localhost:8000"
# export APP_ID="1"
# export API_KEY="sk_app_xxx"
# export SDK_TOKEN="sdk_xxx"
# export TESTER_ID="42"
# export MISSION_ID="18"
# export DEVICE_ID="device-123"
# export SECRET_KEY="sk_hmac_xxx"
# ./scripts/sdk_verify_demo.sh

set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000}"
APP_ID="${APP_ID:-1}"
API_KEY="${API_KEY:-}"
SDK_TOKEN="${SDK_TOKEN:-}"
TESTER_ID="${TESTER_ID:-42}"
MISSION_ID="${MISSION_ID:-18}"
DEVICE_ID="${DEVICE_ID:-device-123}"
SECRET_KEY="${SECRET_KEY:-}"

if [[ -z "$API_KEY" || -z "$SDK_TOKEN" || -z "$SECRET_KEY" ]]; then
  echo "Erreur : définis API_KEY, SDK_TOKEN et SECRET_KEY avant d’exécuter le script."
  exit 1
fi

CODE=$(php -r '
require __DIR__ . "/../vendor/autoload.php";
$secret = $argv[1];
$missionId = (int) $argv[2];
$appId = (int) $argv[3];
$testerId = (int) $argv[4];
$date = new DateTimeImmutable("today");
$service = new App\Service\DailyCodeService();
echo $service->generateToken($secret, $missionId, $appId, $testerId, $date);
' "$SECRET_KEY" "$MISSION_ID" "$APP_ID" "$TESTER_ID")

echo "Code généré : $CODE"

echo "Appel API SDK..."
curl -sS -X POST "$BASE_URL/api/v1/sdk/verify-code" \
  -H "Content-Type: application/json" \
  -H "X-App-Key: $API_KEY" \
  -H "X-SDK-Token: $SDK_TOKEN" \
  -H "X-Device-Id: $DEVICE_ID" \
  --data "{\"app_id\":$APP_ID,\"tester_id\":$TESTER_ID,\"deviceId\":\"$DEVICE_ID\",\"code\":\"$CODE\"}"

echo
