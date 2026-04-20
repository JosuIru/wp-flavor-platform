#!/usr/bin/env bash
# Smoke test del endpoint público /flavor-vbp/v1/collections/load-more
# contra payloads hostiles. Verifica que cada caso devuelve el código y
# error code esperados. Exit code 0 si todo OK, 1 si algún caso falla.
#
# Uso:
#   ./tests/smoke/vbp-load-more-hostile.sh [URL_BASE] [WP_PUBLIC_PATH]
#
# Defaults:
#   URL_BASE       = http://localhost:10028
#   WP_PUBLIC_PATH = /home/josu/Local Sites/sitio-prueba/app/public
#
# Requiere wp-cli en PATH (para generar payloads firmados) y python3.

set -u

URL_BASE="${1:-http://localhost:10028}"
WP_PATH="${2:-/home/josu/Local Sites/sitio-prueba/app/public}"
ENDPOINT="${URL_BASE}/wp-json/flavor-vbp/v1/collections/load-more"
TMP_DIR="${TMPDIR:-/tmp}/vbp-hostile-$$"
mkdir -p "$TMP_DIR"
trap 'rm -rf "$TMP_DIR"' EXIT

echo "[smoke] Generando payloads contra $URL_BASE"

# wp eval genera los payloads firmados válidos y sus variantes hostiles.
# Usa la colección eventos como caso representativo.
(cd "$WP_PATH" && wp eval "
\$registry = Flavor_VBP_Collection_Registry::get_instance();
\$cleaned  = \$registry->sanitize_query_args(\$registry->get('eventos'), ['estado' => 'publicado', 'limit' => 5]);
unset(\$cleaned['page']);
\$sig = Flavor_VBP_Query_Signature::sign('eventos', \$cleaned);

\$base = ['source' => 'eventos', 'args' => \$cleaned, 'signature' => \$sig, 'page' => 2];

file_put_contents('$TMP_DIR/valid.json',        json_encode(\$base));
file_put_contents('$TMP_DIR/no_source.json',    json_encode(array_merge(\$base, ['source' => ''])));
file_put_contents('$TMP_DIR/unknown.json',      json_encode(array_merge(\$base, ['source' => 'no_existe_esta_coleccion'])));
file_put_contents('$TMP_DIR/bad_sig.json',      json_encode(array_merge(\$base, ['signature' => 'deadbeef1234'])));
file_put_contents('$TMP_DIR/empty_sig.json',    json_encode(array_merge(\$base, ['signature' => ''])));
file_put_contents('$TMP_DIR/tampered.json',     json_encode(array_merge(\$base, ['args' => ['estado' => 'borrador']])));
file_put_contents('$TMP_DIR/garbage.txt',       'esto-no-es-json');
file_put_contents('$TMP_DIR/empty.json',        '{}');
" 2>/dev/null) || { echo "[smoke] ERROR al generar payloads con wp-cli"; exit 1; }

fallos=0
total=0

test_case() {
    local nombre="$1" archivo_payload="$2" codigo_http_esperado="$3" codigo_error_esperado="$4"
    total=$((total + 1))

    local codigo_real
    codigo_real=$(curl -s -X POST "$ENDPOINT" \
        -H "Content-Type: application/json" \
        --data-binary @"$archivo_payload" \
        -o "$TMP_DIR/body.json" \
        -w "%{http_code}")

    local error_real
    error_real=$(python3 -c "import json,sys; d=json.load(open('$TMP_DIR/body.json')); print(d.get('code') or 'OK')" 2>/dev/null || echo "parse_err")

    if [ "$codigo_real" = "$codigo_http_esperado" ] && [ "$error_real" = "$codigo_error_esperado" ]; then
        printf "  ✓ %-22s HTTP=%s code=%s\n" "$nombre" "$codigo_real" "$error_real"
    else
        printf "  ✗ %-22s HTTP=%s code=%s (esperado HTTP=%s code=%s)\n" \
            "$nombre" "$codigo_real" "$error_real" "$codigo_http_esperado" "$codigo_error_esperado"
        fallos=$((fallos + 1))
    fi
}

echo "[smoke] Verificando casos hostiles:"
test_case "valid"          "$TMP_DIR/valid.json"        200 "OK"
test_case "empty_source"   "$TMP_DIR/no_source.json"    400 "invalid_request"
test_case "unknown_source" "$TMP_DIR/unknown.json"      404 "collection_not_found"
test_case "bad_signature"  "$TMP_DIR/bad_sig.json"      403 "invalid_signature"
test_case "empty_signature" "$TMP_DIR/empty_sig.json"   400 "invalid_request"
test_case "tampered_args"  "$TMP_DIR/tampered.json"     403 "invalid_signature"
test_case "body_empty"     "$TMP_DIR/empty.json"        400 "invalid_request"
test_case "body_garbage"   "$TMP_DIR/garbage.txt"       400 "rest_invalid_json"

echo ""
if [ "$fallos" -eq 0 ]; then
    echo "[smoke] ✓ OK — $total / $total casos pasan"
    exit 0
fi

echo "[smoke] ✗ FALLO — $fallos de $total casos fallaron"
exit 1
