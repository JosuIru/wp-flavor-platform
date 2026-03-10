# Arquitectura Flavor Platform v3.3

## Visión General

Flavor Platform es un plugin WordPress modular diseñado para comunidades, cooperativas y organizaciones sociales. La versión 3.3 introduce mejoras significativas en organización de código, sistema de base de datos y federación entre nodos.

## Estructura de Directorios

```
flavor-chat-ia/
├── addons/                    # Extensiones opcionales
│   ├── flavor-admin-assistant/
│   ├── flavor-network-communities/
│   └── flavor-web-builder-pro/
├── admin/                     # Panel de administración
│   ├── class-admin-shell.php  # Shell principal del admin
│   ├── class-dashboard.php    # Dashboard admin
│   └── views/                 # Vistas del admin
├── assets/
│   ├── css/                   # Estilos organizados (v3.3)
│   │   ├── core/              # Design tokens, reset, tipografía
│   │   ├── components/        # Elementos UI reutilizables
│   │   ├── layouts/           # Dashboard, portal
│   │   ├── modules/           # Estilos de módulos
│   │   ├── admin/             # Estilos de administración
│   │   ├── dist/              # Bundles compilados
│   │   └── flavor-core.css    # Punto de entrada
│   ├── js/                    # JavaScript
│   └── vbp/                   # Visual Builder Pro assets
├── docs/                      # Documentación
├── includes/
│   ├── bootstrap/             # Sistema de arranque (v3.2+)
│   │   ├── class-bootstrap-dependencies.php
│   │   ├── class-starter-theme-manager.php
│   │   ├── class-database-setup.php
│   │   ├── class-cron-manager.php
│   │   └── class-system-initializer.php
│   ├── database/              # Sistema de migrations (v3.3)
│   │   ├── class-migration-runner.php
│   │   ├── class-migration-base.php
│   │   └── migrations/        # Archivos de migration
│   ├── cli/                   # Comandos WP-CLI
│   ├── api/                   # REST API endpoints
│   ├── network/               # Sistema de federación
│   │   ├── class-network-manager.php
│   │   ├── class-network-federation-admin.php
│   │   ├── class-network-federation-shortcodes.php
│   │   └── class-network-webhooks.php
│   ├── modules/               # 60+ módulos funcionales
│   ├── dashboard/             # Sistema de dashboard unificado
│   ├── frontend/              # Controladores frontend
│   └── layouts/               # Sistema de layouts
├── mobile-apps/               # App Flutter
├── reports/                   # Auditorías e informes
├── templates/                 # Plantillas PHP
└── flavor-chat-ia.php         # Archivo principal
```

## Sistema de Bootstrap (v3.2+)

El archivo principal delega la inicialización a clases especializadas:

```php
// flavor-chat-ia.php
Flavor_Bootstrap_Dependencies::get_instance()->load_all();
Flavor_System_Initializer::get_instance();
```

### Clases de Bootstrap

| Clase | Responsabilidad |
|-------|-----------------|
| `Bootstrap_Dependencies` | Carga de archivos y dependencias |
| `Starter_Theme_Manager` | Configuración del tema starter |
| `Database_Setup` | Verificación de tablas |
| `Cron_Manager` | Tareas programadas |
| `System_Initializer` | Hooks y filtros iniciales |

## Sistema de Migrations (v3.3)

Reemplaza el instalador monolítico con migrations versionadas:

```bash
# Ver estado de migrations
wp flavor migrate:status

# Ejecutar migrations pendientes
wp flavor migrate

# Revertir última migration
wp flavor migrate:rollback
```

### Migrations Disponibles

| Migration | Tablas |
|-----------|--------|
| `000001_create_chat_tables` | Conversaciones, mensajes, escalaciones |
| `000002_create_eventos_tables` | Eventos, inscripciones |
| `000003_create_reservas_tables` | Recursos, reservas |
| `000004_create_social_tables` | Comunidades, colectivos, foros, publicaciones |
| `000005_create_economic_tables` | Marketplace, banco tiempo, socios |
| `000006_create_participation_tables` | Propuestas, presupuestos participativos |
| `000007_create_ecological_tables` | Huertos, compostaje, reciclaje, energía |
| `000008_create_media_admin_tables` | Radio, podcast, trámites, incidencias |
| `000009_create_learning_spaces_tables` | Cursos, talleres, espacios, biblioteca |

## Sistema de Federación (v3.3)

Permite sincronizar contenido entre múltiples instalaciones WordPress.

### Componentes

1. **API Federation** (`class-federation-api.php`)
   - Endpoints REST para 8 tipos de contenido
   - Filtrado por distancia (fórmula Haversine)

2. **Network Manager** (`class-network-manager.php`)
   - Sincronización con peers vía cron
   - Gestión de nodos conectados

3. **Webhooks** (`class-network-webhooks.php`)
   - Notificaciones en tiempo real
   - Firma HMAC-SHA256
   - Cola de reintentos

4. **Shortcodes** (`class-network-federation-shortcodes.php`)
   ```
   [red_eventos limite="6" distancia="50"]
   [red_cursos]
   [red_marketplace categoria="artesania"]
   [red_contenido tipo="events"]
   ```

### Módulos Federados

- Productores
- Eventos
- Carpooling
- Talleres
- Espacios Comunes
- Marketplace
- Banco de Tiempo
- Cursos

## Sistema CSS (v3.3)

Archivos organizados en subdirectorios con bundle automático:

```bash
# Compilar CSS
npm run css:build

# Desarrollo con watch
npm run css:watch
```

### Uso en PHP

```php
// Bundle completo (producción)
wp_enqueue_style(
    'flavor-core',
    FLAVOR_CHAT_IA_URL . 'assets/css/dist/flavor-core.min.css'
);
```

## Módulos

El plugin incluye 60+ módulos organizados por categoría:

### Categorías

- **Social**: Comunidades, colectivos, foros, red social
- **Económico**: Marketplace, banco de tiempo, economía del don, socios
- **Participación**: Eventos, reservas, propuestas, presupuestos
- **Ecológico**: Huertos, compostaje, reciclaje, energía, carpooling
- **Comunicación**: Radio, podcast, multimedia, avisos
- **Administración**: Trámites, incidencias, transparencia

### Estructura de Módulo

```
includes/modules/nombre-modulo/
├── class-nombre-modulo-module.php    # Clase principal
├── class-nombre-modulo-api.php       # API REST (opcional)
├── frontend/                         # Controlador frontend
├── views/                            # Vistas admin
├── templates/                        # Plantillas frontend
└── assets/                           # CSS/JS específicos
```

## API REST

Endpoints bajo el namespace `flavor/v1`:

- `/chat/*` - Chat IA
- `/modules/*` - Módulos genéricos
- `/federation/*` - Federación de contenido
- `/dashboard/*` - Dashboard del usuario

## Comandos WP-CLI

```bash
wp flavor migrate              # Ejecutar migrations
wp flavor migrate:status       # Ver estado
wp flavor migrate:rollback     # Revertir
wp flavor export              # Exportar datos
wp flavor import              # Importar datos
```

## Hooks Principales

### Actions

```php
do_action('flavor_after_init');
do_action('flavor_module_loaded', $module_slug);
do_action('flavor_federation_content_synced', $type, $count);
```

### Filters

```php
apply_filters('flavor_enabled_modules', $modules);
apply_filters('flavor_dashboard_widgets', $widgets);
apply_filters('flavor_federation_content', $content, $type);
```

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

## Versionado

- **3.3.0** - Sistema de migrations, federación completa, reorganización CSS
- **3.2.0** - Refactorización bootstrap, sistema de layouts
- **3.1.0** - Dashboard unificado, Visual Builder Pro
- **3.0.0** - Arquitectura modular, red de comunidades
