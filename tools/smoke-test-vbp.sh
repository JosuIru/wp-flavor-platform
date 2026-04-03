#!/bin/bash

set -euo pipefail

SITE_URL="${1:-http://sitio-prueba.local}"
WP_PATH="${2:-$(pwd)}"
API_KEY="${3:-}"

if ! command -v curl >/dev/null 2>&1; then
    echo "ERROR: curl no está disponible."
    exit 1
fi

if [ -z "$API_KEY" ] && command -v wp >/dev/null 2>&1 && [ -f "$WP_PATH/wp-config.php" ]; then
    API_KEY=$(cd "$WP_PATH" && wp eval "echo flavor_get_vbp_api_key();" 2>/dev/null || true)
fi

if [ -z "$API_KEY" ]; then
    echo "ERROR: No se pudo obtener la API key automáticamente."
    echo "Uso: bash tools/smoke-test-vbp.sh URL WP_PATH API_KEY"
    exit 1
fi

SITE_URL="${SITE_URL%/}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

have_jq=0
if command -v jq >/dev/null 2>&1; then
    have_jq=1
fi

request_json() {
    local method="$1"
    local url="$2"
    local body="${3:-}"
    local out_file="$4"
    local status

    if [ -n "$body" ]; then
        status=$(curl -sS -o "$out_file" -w "%{http_code}" \
            -X "$method" \
            -H "X-VBP-Key: $API_KEY" \
            -H "Content-Type: application/json" \
            --data "$body" \
            "$url")
    else
        status=$(curl -sS -o "$out_file" -w "%{http_code}" \
            -X "$method" \
            -H "X-VBP-Key: $API_KEY" \
            "$url")
    fi

    echo "$status"
}

assert_http_ok() {
    local status="$1"
    local label="$2"
    local file="$3"

    case "$status" in
        200|201)
            echo "OK: $label ($status)"
            ;;
        *)
            echo "ERROR: $label devolvió HTTP $status"
            cat "$file"
            exit 1
            ;;
    esac
}

extract_json_field() {
    local file="$1"
    local expr="$2"

    if [ "$have_jq" -eq 1 ]; then
        jq -r "$expr" "$file"
    else
        sed -n "s/.*\"$expr\"[[:space:]]*:[[:space:]]*\"\\([^\"]*\\)\".*/\\1/p" "$file" | head -n 1
    fi
}

echo "== Smoke Test VBP =="
echo "SITE_URL: $SITE_URL"
echo "WP_PATH: $WP_PATH"
echo

PING_FILE="$TMP_DIR/ping.json"
status=$(request_json "GET" "$SITE_URL/wp-json/flavor-vbp/v1/ping" "" "$PING_FILE")
assert_http_ok "$status" "Ping público VBP" "$PING_FILE"

CREATE_FILE="$TMP_DIR/create.json"
CREATE_BODY=$(cat <<'JSON'
{
  "pages": [
    {
      "title": "Smoke Test VBP",
      "slug": "smoke-test-vbp",
      "status": "publish",
      "blocks": {
        "version": "smoke-test",
        "settings": {
          "pageWidth": 1200,
          "backgroundColor": "#ffffff",
          "customCss": ""
        },
        "elements": [
          {
            "id": "hero-smoke",
            "type": "hero",
            "component_id": "hero-basic",
            "data": {
              "title": "Smoke Test VBP",
              "subtitle": "Creado por smoke-test-vbp.sh",
              "cta_text": "Probar",
              "cta_url": "/contacto"
            },
            "settings": {}
          }
        ]
      }
    }
  ]
}
JSON
)
status=$(request_json "POST" "$SITE_URL/wp-json/flavor-vbp/v1/claude/batch/pages" "$CREATE_BODY" "$CREATE_FILE")
assert_http_ok "$status" "Creación batch de landing" "$CREATE_FILE"

if [ "$have_jq" -ne 1 ]; then
    echo "ERROR: jq es obligatorio para este smoke test."
    exit 1
fi

PAGE_ID=$(jq -r '.pages[0].page_id // empty' "$CREATE_FILE")
if [ -z "$PAGE_ID" ] || [ "$PAGE_ID" = "null" ]; then
    echo "ERROR: No se pudo extraer page_id de la creación batch."
    cat "$CREATE_FILE"
    exit 1
fi

echo "OK: Landing creada con ID $PAGE_ID"

UPDATE_FILE="$TMP_DIR/update.json"
UPDATE_BODY=$(cat <<JSON
{
  "operations": [
    {
      "id": "add-text",
      "type": "add_element",
      "data": {
        "page_id": $PAGE_ID,
        "element": {
          "id": "text-smoke",
          "type": "text",
          "component_id": "text-basic",
          "data": {
            "content": "Elemento añadido por smoke test"
          },
          "settings": {}
        }
      }
    },
    {
      "id": "publish",
      "type": "publish_page",
      "data": {
        "page_id": $PAGE_ID
      }
    }
  ]
}
JSON
)
status=$(request_json "POST" "$SITE_URL/wp-json/flavor-vbp/v1/claude/batch" "$UPDATE_BODY" "$UPDATE_FILE")
assert_http_ok "$status" "Operaciones batch sobre landing" "$UPDATE_FILE"

APP_LAYOUT_FILE="$TMP_DIR/layout.json"
status=$(curl -sS -o "$APP_LAYOUT_FILE" -w "%{http_code}" \
    "$SITE_URL/wp-json/flavor-app/v1/layouts/landing/$PAGE_ID")
assert_http_ok "$status" "Lectura pública de layout nativo" "$APP_LAYOUT_FILE"

LAYOUT_COUNT=$(jq -r '.landing.layout | length' "$APP_LAYOUT_FILE")
if [ "$LAYOUT_COUNT" -lt 1 ]; then
    echo "ERROR: El layout publicado no contiene componentes."
    cat "$APP_LAYOUT_FILE"
    exit 1
fi

echo "OK: flavor-app/v1/layouts/landing/$PAGE_ID devuelve $LAYOUT_COUNT componentes"

if jq -e '.landing.layout[] | select(.component_id == "hero-basic" or .component_id == "text-basic")' "$APP_LAYOUT_FILE" >/dev/null 2>&1; then
    echo "OK: El layout público refleja los elementos creados por batch"
else
    echo "AVISO: El layout público no expone literalmente los component_id esperados"
    echo "      Revisa manualmente la conversión nativa:"
    cat "$APP_LAYOUT_FILE"
fi

echo
echo "Smoke test completado."
echo "Notas:"
echo "- Este script valida ping, creación batch, actualización batch y lectura pública del layout nativo."
echo "- No valida versionado REST porque esas rutas exigen usuario autenticado de WordPress, no solo X-VBP-Key."
