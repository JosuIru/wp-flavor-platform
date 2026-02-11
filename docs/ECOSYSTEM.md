# Flavor Platform - Documentación del Ecosistema

## Visión General

Flavor Platform es un ecosistema modular para WordPress que permite crear aplicaciones web y móviles para comunidades, empresas y colectivos. El sistema se basa en:

1. **Perfiles de Aplicación** - Configuraciones preestablecidas para distintos tipos de organizaciones
2. **Módulos** - Funcionalidades específicas que se pueden activar/desactivar
3. **Red de Nodos** - Federación de sitios para compartir recursos
4. **Apps Móviles** - Aplicaciones Flutter sincronizadas con el sitio

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                      FLAVOR PLATFORM                             │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   PERFILES  │  │   MÓDULOS   │  │      RED DE NODOS       │  │
│  │  18 tipos   │──│   50+       │──│  API REST federada      │  │
│  │  predefinidos│  │  dinámicos  │  │  Geolocalización        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
│         │                │                      │                │
│         ▼                ▼                      ▼                │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │                    PAGE CREATOR                              ││
│  │  - Landing Generator    - Shortcodes Dinámicos              ││
│  │  - Template Orchestrator - Dashboard Usuario                 ││
│  └─────────────────────────────────────────────────────────────┘│
│         │                │                      │                │
│         ▼                ▼                      ▼                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   FRONTEND  │  │    ADMIN    │  │       APPS MÓVILES      │  │
│  │  Templates  │  │   Panel WP  │  │  Flutter (client/admin) │  │
│  │  CSS/JS     │  │   Settings  │  │  Deep Links             │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Perfiles de Aplicación

Los perfiles preconfiguran qué módulos se activan y qué páginas se crean.

### Perfiles Disponibles

| Perfil | Módulos Requeridos | Módulos Opcionales | Color |
|--------|-------------------|-------------------|-------|
| `tienda` | woocommerce | marketplace, facturas, advertising | #00a0d2 |
| `grupo_consumo` | grupos_consumo | eventos, socios, marketplace | #46b450 |
| `restaurante` | woocommerce, reservas | eventos, facturas, fichaje | #f56e28 |
| `banco_tiempo` | banco_tiempo, socios | eventos, talleres, ayuda_vecinal | #9b59b6 |
| `comunidad` | socios, eventos | talleres, participacion, red_social | #e91e63 |
| `coworking` | espacios_comunes, socios, fichaje, reservas | eventos, facturas | #00bcd4 |
| `marketplace` | marketplace | advertising, chat_interno | #ff9800 |
| `ayuntamiento` | avisos_municipales, incidencias | participacion, transparencia, tramites | #1d4ed8 |
| `barrio` | ayuda_vecinal | huertos, bicicletas, banco_tiempo, carpooling | #22c55e |
| `academia` | cursos, talleres | eventos, multimedia, biblioteca | #7c3aed |
| `radio_comunitaria` | radio, podcast | eventos, multimedia, socios | #dc2626 |
| `cooperativa` | socios, transparencia, participacion | presupuestos_participativos | #059669 |
| `hosteleria` | woocommerce, reservas | eventos, facturas | #f97316 |
| `productora` | multimedia, eventos | talleres, advertising | #8b5cf6 |
| `centro_salud` | reservas | socios, tramites | #0891b2 |
| `inmobiliaria` | marketplace | advertising | #64748b |
| `ong` | socios, eventos, transparencia | participacion, talleres | #ec4899 |
| `trading` | trading_ia | facturas, advertising | #eab308 |

### Cómo Activar un Perfil

```php
// En código
Flavor_App_Profiles::get_instance()->activar_perfil('grupo_consumo');

// O desde el admin: Flavor Platform → Perfiles
```

---

## Sistema de Módulos

Los módulos son unidades funcionales independientes ubicadas en `/includes/modules/`.

### Estructura de un Módulo

```
/includes/modules/[nombre-modulo]/
├── class-[nombre]-module.php      # Clase principal (implements Flavor_Chat_Module)
├── class-[nombre]-api.php         # Endpoints REST
├── frontend/
│   ├── class-[nombre]-frontend-controller.php
│   └── class-[nombre]-shortcodes.php
├── views/                         # Vistas admin
├── assets/                        # CSS/JS específicos
└── install.php                    # Creación de tablas
```

### Módulos Disponibles (50+)

**Gestión Comunitaria:**
- socios, eventos, talleres, participacion, presupuestos_participativos

**Economía Colaborativa:**
- banco_tiempo, grupos_consumo, marketplace, carpooling

**Sostenibilidad:**
- huertos_urbanos, reciclaje, compostaje, bicicletas_compartidas

**Administración:**
- tramites, facturas, fichaje_empleados, transparencia, reservas

**Comunicación:**
- avisos_municipales, incidencias, newsletter, podcast, radio

**Comercio:**
- woocommerce, advertising, trading_ia

**Social:**
- red_social, ayuda_vecinal, chat_interno, chat_grupos

---

## Red de Nodos (Federación)

Sistema para conectar múltiples sitios Flavor Platform y compartir recursos.

### Activar un Nodo

```php
// Registrar el sitio como nodo
$hub = Flavor_Network_Hub::get_instance();
$hub->registrar_nodo([
    'nombre' => 'Mi Grupo de Consumo',
    'url' => 'https://migrupo.com',
    'lat' => 43.2627,
    'lng' => -2.9253,
    'perfil' => 'grupo_consumo',
    'recursos_compartidos' => ['productores', 'productos']
]);
```

### Recursos Compartibles por Perfil

| Perfil | Recursos que Comparte | Recursos que Consume |
|--------|----------------------|---------------------|
| grupo_consumo | productos, productores | productores de otros nodos |
| banco_tiempo | servicios, habilidades | servicios de otros nodos |
| marketplace | anuncios | anuncios de otros nodos |
| barrio | recursos comunitarios | servicios cercanos |

### API de Federación

```
GET  /wp-json/flavor/v1/network/nodes          # Listar nodos
GET  /wp-json/flavor/v1/network/nodes/{id}     # Detalle nodo
POST /wp-json/flavor/v1/network/register       # Registrar nodo
GET  /wp-json/flavor/v1/network/resources      # Recursos compartidos
GET  /wp-json/flavor/v1/network/nearby?lat=X&lng=Y&radius=50  # Nodos cercanos
```

---

## Sistema de Páginas

### Page Creator

Crea automáticamente todas las páginas necesarias al activar módulos.

**Archivo:** `/includes/class-page-creator.php`

```php
// Páginas creadas automáticamente para Grupos de Consumo:
/grupos-consumo/              # Listado
/grupos-consumo/productos/    # Catálogo
/grupos-consumo/unirme/       # Formulario unión
/grupos-consumo/mi-pedido/    # Panel usuario
```

### Template Orchestrator

Genera contenido dinámico basado en el perfil activo.

**Archivos:**
- `/includes/orchestrator/class-template-orchestrator.php`
- `/includes/orchestrator/class-template-definitions.php`

### Landing Generator

Crea landing pages con secciones predefinidas por perfil.

**Archivo:** `/includes/class-landing-shortcodes.php`

```php
// Shortcode
[flavor_landing tipo="grupos-consumo"]

// Genera secciones:
// - Hero con CTA
// - Cómo Funciona (pasos)
// - Grupos Activos (dinámico)
// - Productos Destacados (dinámico)
// - Ciclo Actual (dinámico)
// - CTA Final
```

---

## Dashboard de Usuario

Panel unificado en `/mi-cuenta/` con tabs dinámicos según módulos activos.

### Shortcode

```php
[flavor_mi_cuenta]
```

### Tabs Disponibles

Cada módulo puede registrar sus propios tabs:

```php
add_filter('flavor_user_dashboard_tabs', function($tabs) {
    $tabs['gc-mi-pedido'] = [
        'label' => 'Mi Pedido',
        'icon' => 'cart',
        'callback' => [$this, 'render_tab'],
        'orden' => 25,
        'requiere_login' => true,
    ];
    return $tabs;
});
```

---

## Diseño y Apariencia

### Temas Predefinidos

26 temas disponibles en **Flavor Platform → Diseño**:

- `default` - Azul moderno (#3b82f6)
- `modern-purple` - Púrpura (#8b5cf6)
- `grupos-consumo` - Verde orgánico (#4a7c59)
- `comunidad-viva` - Índigo (#4f46e5)
- `dark-mode` - Tema oscuro
- `minimal` - Minimalista
- Y muchos más...

### Personalización Manual

**Colores:**
```
primary_color      - Color principal de la marca
secondary_color    - Color secundario
accent_color       - Color de acento (CTAs, highlights)
success_color      - Estados de éxito
warning_color      - Advertencias
error_color        - Errores
background_color   - Fondo general
text_color         - Texto principal
text_muted_color   - Texto secundario
```

**Tipografía:**
```
font_family_headings  - Fuente para títulos
font_family_body      - Fuente para cuerpo
font_size_base        - Tamaño base (16px)
font_size_h1/h2/h3    - Tamaños de encabezados
line_height_base      - Interlineado
```

**Layout:**
```
container_max_width   - Ancho máximo (1280px)
section_padding_y     - Padding vertical secciones
grid_gap              - Espaciado entre elementos
card_padding          - Padding interno tarjetas
```

### Templates Personalizables

```
/templates/components/unified/     # Componentes genéricos
  ├── hero.php
  ├── features.php
  ├── cta.php
  ├── grid.php
  ├── testimonios.php
  └── _partials/                   # Variantes

/templates/frontend/landing/       # Secciones por módulo
  ├── _gc-ciclo-actual.php
  ├── _gc-productos-destacados.php
  └── _gc-grupos-activos.php

/templates/components/landings/    # Templates genéricos
  ├── _generic-hero.php
  ├── _generic-features.php
  └── _generic-cta.php
```

---

## Apps Móviles

### Deep Links

**Archivo:** `/includes/app-integration/class-deep-link-manager.php`

```
flavorapp://grupo-consumo/productos     → Catálogo
flavorapp://grupo-consumo/mi-pedido     → Mi pedido
flavorapp://eventos/123                 → Detalle evento
flavorapp://mi-cuenta                   → Dashboard
```

### API para Apps

**Archivo:** `/includes/app-integration/class-app-layouts-api.php`

```
GET /wp-json/flavor/v1/app/layouts      # Layouts disponibles
GET /wp-json/flavor/v1/app/config       # Configuración app
GET /wp-json/flavor/v1/app/sync         # Sincronización datos
POST /wp-json/flavor/v1/app/pair        # Pairing QR
```

### Sincronización QR

1. Admin genera QR con token temporal
2. App escanea QR
3. API valida token y crea sesión permanente
4. App recibe configuración y módulos activos

---

## API REST

### Endpoints Generales

```
GET  /wp-json/flavor/v1/modules          # Módulos activos
GET  /wp-json/flavor/v1/search           # Búsqueda global
GET  /wp-json/flavor/v1/notifications    # Notificaciones usuario
POST /wp-json/flavor/v1/activity         # Log actividad
```

### Endpoints por Módulo

Cada módulo registra sus propios endpoints en `class-[nombre]-api.php`:

```
# Grupos de Consumo
GET  /wp-json/flavor/v1/gc/ciclo-actual
GET  /wp-json/flavor/v1/gc/productos
POST /wp-json/flavor/v1/gc/pedido

# Socios
GET  /wp-json/flavor/v1/socios/mi-perfil
POST /wp-json/flavor/v1/socios/alta
POST /wp-json/flavor/v1/socios/pagar-cuota

# Eventos
GET  /wp-json/flavor/v1/eventos
POST /wp-json/flavor/v1/eventos/inscribirse
```

---

## Notificaciones

### Canales Disponibles

- **Email** - Vía wp_mail()
- **Push** - Firebase Cloud Messaging
- **WhatsApp** - Business API (requiere config)
- **Telegram** - Bot API (requiere config)

### Eventos Configurables

```php
// En cada módulo
$this->notification_manager->registrar_evento([
    'slug' => 'gc_nuevo_ciclo',
    'nombre' => 'Nuevo ciclo de pedidos',
    'canales' => ['email', 'push', 'whatsapp'],
    'plantilla' => 'Se ha abierto un nuevo ciclo de pedidos: {ciclo_nombre}',
]);
```

---

## Instalación y Activación

### Requisitos

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ o MariaDB 10.3+

### Pasos

1. Subir plugin a `/wp-content/plugins/`
2. Activar desde el panel de WordPress
3. Ir a **Flavor Platform → Asistente** de configuración
4. Seleccionar perfil de aplicación
5. Configurar módulos adicionales
6. Personalizar diseño

### Creación de Tablas

Las tablas se crean automáticamente al activar módulos. Archivo de instalación por módulo:

```php
// /includes/modules/[nombre]/install.php
function flavor_[nombre]_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_[tabla] (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ...
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
```

---

## Desarrollo de Módulos

### Crear un Nuevo Módulo

1. Crear directorio en `/includes/modules/mi-modulo/`

2. Implementar interface:

```php
class Flavor_Mi_Modulo_Module implements Flavor_Chat_Module {

    public function get_slug() {
        return 'mi_modulo';
    }

    public function get_name() {
        return __('Mi Módulo', 'flavor-chat-ia');
    }

    public function get_description() {
        return __('Descripción del módulo', 'flavor-chat-ia');
    }

    public function get_icon() {
        return 'dashicons-star-filled';
    }

    public function init() {
        // Inicializar hooks, shortcodes, etc.
    }

    public function get_required_tables() {
        return ['mi_modulo_datos'];
    }
}
```

3. Registrar en el Module Loader (automático vía autoloader)

---

## Troubleshooting

### Tablas no creadas

```php
// Forzar reinstalación
delete_option('flavor_[modulo]_db_version');
// Reactivar el módulo
```

### AJAX retorna error

1. Verificar nonce: `check_ajax_referer('nombre_accion', 'nonce_field')`
2. Verificar permisos: `current_user_can('capability')`
3. Verificar tabla existe: `$wpdb->get_var("SHOW TABLES LIKE '$tabla'")`

### Landing no muestra datos

1. Verificar que existen posts del CPT correspondiente
2. Verificar estado `publish`
3. Verificar meta queries en los templates

---

## Roadmap / Pendientes

### Prioridad Alta
- [ ] Completar PWA con offline sync
- [ ] Flujo completo de pairing QR para apps
- [ ] Campo `radio_entrega` para productores en red

### Prioridad Media
- [ ] Flag `visibilidad` (público/privado) para módulos
- [ ] Documentación usuario final (no técnica)
- [ ] Wizard de configuración mejorado

### Prioridad Baja
- [ ] Themes adicionales
- [ ] Integración con más pasarelas de pago
- [ ] Sistema de plugins/addons de terceros

---

## Licencia

GPL v2 or later

## Soporte

- Documentación: `/wp-admin/admin.php?page=flavor-documentacion`
- Issues: https://github.com/gailu-labs/flavor-platform/issues
