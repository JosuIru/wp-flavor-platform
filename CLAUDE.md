# Flavor Platform - Instrucciones para Claude Code

## REGLAS CRITICAS

### PROHIBIDO
1. **NUNCA crear paginas con HTML/Gutenberg** - Usar Visual Builder Pro API
2. **NUNCA activar +20 modulos** sin justificacion
3. **NUNCA configurar tema no instalado**
4. **NUNCA crear menus sin asignar ubicaciones**
5. **NUNCA compilar APKs sin `build_app.sh`** (hay 2 APKs: client y admin)

### OBLIGATORIO
1. Ejecutar validacion primero
2. Usar VBP para paginas con diseno
3. Verificar tema existe antes de activar
4. Asignar menus a ubicaciones (primary, footer, mobile)
5. Configurar footer con widgets

---

## Validacion Pre-vuelo

```bash
cd /ruta/wordpress
wp plugin is-active flavor-platform || echo "ERROR: Plugin no activo"
[ -d "wp-content/themes/flavor-starter" ] || echo "ERROR: Tema no instalado"
[ "$(wp option get template)" = "flavor-starter" ] || echo "ERROR: Tema no activo"

API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/system/health" -H "X-VBP-Key: $API_KEY"
```

---

## Discovery (ANTES de componer paginas)

```bash
# Inventario completo
bash tools/vbp-inventory.sh "http://SITIO"

# O consultas especificas:
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/blocks" -H "X-VBP-Key: $API_KEY"
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/design-presets" -H "X-VBP-Key: $API_KEY"
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/modules" -H "X-VBP-Key: $API_KEY" | jq '.[] | select(.active==true)'
```

**REGLAS:**
- NUNCA usar bloques que no aparezcan en `/claude/blocks`
- NUNCA referenciar modulos no ACTIVOS
- Si algo no existe, PREGUNTAR

---

## API Key

```bash
# Obtener
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")

# Usar en requests
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/status" -H "X-VBP-Key: $API_KEY"

# Regenerar (produccion)
wp eval "echo flavor_regenerate_vbp_api_key();"
```

---

## Crear Paginas (SIEMPRE VBP)

```bash
# Pagina con preset
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{"title": "Mi Landing", "preset": "modern", "sections": ["hero", "features", "cta"], "status": "publish"}'

# Pagina funcional (shortcode)
wp post create --post_type=page --post_title="Portal" --post_content='[flavor_unified_dashboard]' --post_status=publish
```

---

## Crear Sitio Completo

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "name": "Mi Cooperativa",
    "modules": ["grupos_consumo", "socios", "eventos"],
    "create_pages": true,
    "create_menus": true,
    "theme": "light"
  }'
```

---

## Menus

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/menu" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "location": "primary",
    "items": [
      {"title": "Inicio", "url": "/"},
      {"title": "Productos", "url": "/productos"}
    ]
  }'
```

---

## Apps Moviles

Ver `CLAUDE-APK.md` para documentacion completa.

```bash
# Verificar soporte de modulos (3 niveles: WordPress + Flutter + API)
bash tools/apk-inventory.sh "http://SITIO" "mobile-apps"

# Compilar (OBLIGATORIO usar script)
cd mobile-apps/
./build_app.sh client release  # APK cliente
./build_app.sh admin release   # APK admin
```

---

## Checklist Final

```bash
[ "$(wp option get template)" = "flavor-starter" ] && echo "OK Tema"
[ $(wp option get flavor_active_modules --format=json | jq length) -lt 20 ] && echo "OK Modulos"
[ $(wp option get page_on_front) -gt 0 ] && echo "OK Homepage"
wp menu location list | grep -q primary && echo "OK Menus"
```

---

## Documentacion Detallada

| Archivo | Contenido |
|---------|-----------|
| `docs/api/ENDPOINTS-REFERENCE.md` | Referencia completa de endpoints |
| `docs/api/EJEMPLOS-USO.md` | Ejemplos de curl |
| `docs/PLANTILLAS.md` | Plantillas, presets y secciones |
| `CLAUDE-APK.md` | Configuracion de apps moviles |

---

## Plantillas Rapidas

| Template | Modulos |
|----------|---------|
| `grupos_consumo` | grupos-consumo, socios, eventos, marketplace |
| `comunidad` | comunidades, participacion, eventos, foros |
| `asociacion` | socios, eventos, transparencia, foros |
| `cooperativa` | socios, transparencia, presupuestos-participativos |

## Presets de Diseno

`modern` | `community` | `cooperative` | `eco` | `fundraising` | `nature`
