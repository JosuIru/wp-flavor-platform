# Flavor Platform

**Plataforma integral para WordPress** orientada a comunidades, cooperativas, asociaciones, municipios y redes ciudadanas. Un único plugin que combina **74+ módulos comunitarios**, un **page builder visual propio**, **apps móviles Flutter** (cliente y admin), **automatización vía API para Claude Code / IA**, y un ecosistema de **addons** para publicidad ética, multiidioma, redes federadas, hostelería y más.

> Nombre comercial: `Flavor Platform` · Slug técnico (legacy, compatibilidad): `flavor-chat-ia` · Versión: 3.5.12 · Licencia: GPL-2.0+

---

## ¿Qué resuelve?

La mayoría de webs de comunidad/cooperativa acaban siendo un *Frankenstein* de 15 plugins mal integrados. Flavor Platform propone lo contrario: **un único núcleo** con módulos activables que comparten datos, permisos, UI y API. Todo lo que una comunidad o red social local necesita —socios, eventos, grupos de consumo, reservas, participación, transparencia, crowdfunding, foros, chat, biblioteca, banco de tiempo, huertos urbanos…— se activa/desactiva como piezas Lego.

Y cuando la web no basta: las mismas entidades se consumen desde las **apps móviles Flutter** (cliente y admin) que vienen incluidas.

---

## Arquitectura general

```
flavor-platform/
├── flavor-platform.php         # Plugin principal (WordPress)
├── includes/
│   ├── modules/                # 74+ módulos funcionales (ver abajo)
│   ├── visual-builder-pro/     # Integración del page builder
│   └── api/                    # REST APIs (flavor-vbp, site-builder, platform…)
├── addons/                     # Extensiones opcionales (cada una autocontenida)
│   ├── flavor-visual-builder-pro/
│   ├── flavor-admin-assistant/
│   ├── flavor-advertising-pro/
│   ├── flavor-multilingual/
│   ├── flavor-network-communities/
│   ├── flavor-restaurant-ordering/
│   └── flavor-demo-orchestrator/
├── mobile-apps/                # Apps Flutter (cliente + admin, misma base)
├── templates/                  # Plantillas de sitio (cooperativa, asociación…)
├── tools/                      # Scripts de inventario, validación, build
└── docs/                       # Documentación técnica
```

---

## Módulos incluidos (74+)

Se agrupan por área. Todos son **opcionales** y se activan por panel o vía API. Los módulos activos aparecen tanto en la web (con bloques VBP listos) como en las apps móviles.

**Comunidad y personas**
`socios` · `comunidades` · `colectivos` · `red-social` · `foros` · `chat-interno` · `chat-grupos` · `chat-estados` · `participacion` · `encuestas` · `circulos-cuidados` · `ayuda-vecinal` · `banco-tiempo` · `saberes-ancestrales`

**Economía social y cooperativa**
`grupos-consumo` · `marketplace` · `crowdfunding` · `economia-don` · `economia-suficiencia` · `presupuestos-participativos` · `transparencia` · `contabilidad` · `facturas` · `woocommerce` · `empresas` · `empresarial` · `clientes`

**Territorio, municipio y participación**
`avisos-municipales` · `tramites` · `mapa-actores` · `seguimiento-denuncias` · `justicia-restaurativa` · `documentacion-legal` · `sello-conciencia`

**Ecología y bienes comunes**
`huertos-urbanos` · `compostaje` · `reciclaje` · `biodiversidad-local` · `energia-comunitaria` · `huella-ecologica` · `bicicletas-compartidas` · `carpooling` · `espacios-comunes` · `parkings`

**Cultura, educación y ocio**
`eventos` · `cursos` · `talleres` · `biblioteca` · `multimedia` · `podcast` · `radio` · `kulturaka` · `themacle` · `recetas` · `bares`

**Gestión interna y operaciones**
`reservas` · `incidencias` · `bug-tracker` · `fichaje-empleados` · `trabajo-digno` · `campanias` · `email-marketing` · `agregador-contenido` · `advertising`

**Experimental / vertical**
`dex-solana` · `trading-ia`

> La lista viva se consulta con `bash tools/vbp-inventory.sh "http://tu-sitio"` o con `GET /wp-json/flavor-site-builder/v1/modules`.

---

## Addons

Cada addon vive en `addons/` y se puede activar de forma independiente. Aportan capacidades que no pertenecen al núcleo comunitario.

| Addon | Qué hace |
|---|---|
| **Visual Builder Pro (VBP)** | Editor visual fullscreen tipo Figma/Photoshop con bloques **dinámicos** que consumen los módulos (eventos, socios, biblioteca…). Filtros editables por el visitante, *Cargar más* con firma HMAC, caché CDN-friendly, templates con placeholders y presets de diseño (`modern`, `community`, `cooperative`, `eco`, `fundraising`, `nature`). |
| **Admin Assistant** | Asistente con IA dentro del panel de WordPress: comandos de voz, atajos de teclado y herramientas inteligentes con control de acceso por rol. |
| **Advertising Pro** | Sistema de **publicidad ética** con gestión de anuncios, anunciantes, tracking, pagos y red global. GDPR compliant. |
| **Multilingual** | Sistema multiidioma con traducción por IA. Soporta castellano, inglés, euskera, catalán, gallego y más. Endpoint propio `/wp-json/flavor-multilingual/v1/`. |
| **Network Communities** | Federación multi-sitio: conectar comunidades independientes para compartir contenido, eventos, colaboraciones y catálogo global. Redes de municipios, franquicias, federaciones. |
| **Restaurant Ordering** | Gestión completa de pedidos y reservas para hostelería, integrada con los módulos de `bares` y `eventos`. |
| **Demo Orchestrator** | Carga/limpieza de datos demo desde las apps móviles e historial de ejecuciones. Útil para onboarding y QA. |

---

## Apps móviles (Flutter)

`mobile-apps/` contiene **dos builds desde una sola base de código** (Flutter 3.19+):

- **Client APK** — experiencia ciudadana/socia: autenticación, feed, módulos activos, chat, eventos, reservas, grupos de consumo, pagos.
- **Admin APK** — gestión desde móvil: moderación, caja, altas de socios, aprobar pedidos, alertas.

Cada módulo WordPress tiene su contraparte Flutter. El script `tools/apk-inventory.sh` valida el soporte en los **3 niveles** (WordPress + Flutter + API) antes de compilar:

```bash
cd mobile-apps/
./build_app.sh client release
./build_app.sh admin  release
```

> **Nunca compilar APKs sin `build_app.sh`** — hay dos targets que comparten base y el script gestiona el entry-point y el `flavor`. Ver `CLAUDE-APK.md`.

---

## APIs REST

Todas autenticadas con **X-VBP-Key** (API key regenerable por admin).

| Base | Uso |
|---|---|
| `/wp-json/flavor-vbp/v1/claude/` | Automatización vía Claude Code / LLM: inventario, bloques disponibles, creación de páginas. |
| `/wp-json/flavor-site-builder/v1/` | Creación de sitios completos desde plantilla (módulos + páginas + menús). |
| `/wp-json/flavor-platform/v1/` | Diagnóstico, health checks, compatibilidad. |
| `/wp-json/flavor-multilingual/v1/` | Traducciones (addon Multilingual). |

Ejemplo mínimo:

```bash
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')

# Crear una cooperativa completa con 4 módulos, páginas y menús
curl -X POST "http://tu-sitio.local/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{
    "template": "cooperativa",
    "name": "Mi Cooperativa",
    "modules": ["socios","transparencia","presupuestos-participativos"],
    "create_pages": true,
    "create_menus": true,
    "theme": "light"
  }'
```

---

## Plantillas de sitio

Combinaciones preconfiguradas de módulos + páginas + menús:

| Template | Módulos típicos |
|---|---|
| `grupos_consumo` | grupos-consumo, socios, eventos, marketplace |
| `comunidad` | comunidades, participacion, eventos, foros |
| `asociacion` | socios, eventos, transparencia, foros |
| `cooperativa` | socios, transparencia, presupuestos-participativos |

---

## Quickstart

```bash
# 1. Activar el plugin
wp plugin activate flavor-chat-ia

# 2. Validar el entorno (plugin, tema, API key, health)
bash tools/full-inventory.sh "http://tu-sitio.local" "." "mobile-apps"

# 3. Crear un sitio desde plantilla
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -X POST "http://tu-sitio.local/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{"template":"cooperativa","name":"Mi Cooperativa"}'
```

---

## Requisitos

- WordPress **5.8+** (probado hasta 6.4)
- PHP **7.4+**
- Opcional: **WooCommerce 5.0+** para módulos de economía
- Para apps móviles: **Flutter 3.19+**

---

## Documentación

| Archivo | Contenido |
|---|---|
| `CLAUDE.md` | Instrucciones para Claude Code: reglas, APIs, flujos automatizados |
| `CLAUDE-APK.md` | Configuración y build de las apps móviles |
| `docs/api/ENDPOINTS-REFERENCE.md` | Referencia completa de endpoints |
| `docs/api/EJEMPLOS-USO.md` | Ejemplos de curl |
| `docs/PLANTILLAS.md` | Plantillas, presets de diseño y secciones |
| `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md` | Auditoría viva del estado del sistema |
| `CHANGELOG.md` | Historial de cambios |

> Ante cualquier contradicción entre documentación histórica (`archive/docs-historicos/`) y el código, **manda el código y la auditoría**.

---

## Licencia

GPL-2.0+ — © Gailu Labs · https://gailu.net
