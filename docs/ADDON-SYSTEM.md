# Sistema de Addons de Flavor Platform v3.0

## Introducción

El Sistema de Addons es una arquitectura modular introducida en Flavor Platform 3.0 que permite extender las funcionalidades del plugin base mediante componentes independientes.

## Objetivos del Sistema

1. **Modularidad**: Separar funcionalidades en componentes independientes
2. **Rendimiento**: Cargar solo lo necesario, reducir peso del core
3. **Mantenibilidad**: Código más fácil de mantener y actualizar
4. **Escalabilidad**: Facilitar el crecimiento del ecosistema
5. **Comercialización**: Permitir addons premium independientes

## Arquitectura

### Componentes Core del Sistema

| Componente | Archivo | Responsabilidad |
|------------|---------|-----------------|
| **Autoloader** | `includes/class-autoloader.php` | Carga automática de clases bajo demanda (PSR-4) |
| **Dependency Checker** | `includes/class-dependency-checker.php` | Verifica requisitos antes de activar |
| **Addon Manager** | `includes/class-addon-manager.php` | Registro, activación y gestión de addons |
| **Addon Admin** | `admin/class-addon-admin.php` | Interfaz de administración |

### Flujo de Funcionamiento

```
1. WordPress se carga
   ↓
2. Flavor Platform se inicializa
   ↓
3. Se carga Autoloader, Dependency Checker y Addon Manager
   ↓
4. Se dispara hook 'flavor_register_addons'
   ↓
5. Los addons se registran a sí mismos
   ↓
6. Addon Manager verifica dependencias de addons activos
   ↓
7. Se cargan los addons activos que cumplen requisitos
   ↓
8. Cada addon ejecuta su init_callback
```

## Clase: Flavor_Autoloader

### Propósito

Implementa carga automática de clases siguiendo PSR-4, reduciendo la necesidad de `require_once` y mejorando el rendimiento.

### Convenciones de Nombres

| Clase | Archivo |
|-------|---------|
| `Flavor_Chat_Core` | `includes/core/class-chat-core.php` |
| `Flavor_Module_WooCommerce` | `includes/modules/woocommerce/class-woocommerce.php` |
| `Flavor_Engine_Claude` | `includes/engines/class-engine-claude.php` |
| `Flavor_Addon_Web_Builder` | `includes/addons/web-builder/class-web-builder.php` |

### Uso

```php
// Registrar el autoloader (se hace automáticamente en el core)
Flavor_Autoloader::register();

// Ahora las clases se cargan automáticamente
$core = new Flavor_Chat_Core();  // Auto-carga includes/core/class-chat-core.php
```

### Características

- ✅ Carga bajo demanda (lazy loading)
- ✅ Caché de clases cargadas
- ✅ Soporte para múltiples namespaces
- ✅ Logging en modo debug
- ✅ Mapeo automático de carpetas

## Clase: Flavor_Dependency_Checker

### Propósito

Verifica que todos los requisitos estén satisfechos antes de activar un addon o módulo.

### Tipos de Dependencias Soportadas

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| **Plugin** | `plugin:slug` | `plugin:woocommerce` |
| **Addon** | `addon:slug` | `addon:web-builder-pro` |
| **Módulo** | `module:id` | `module:marketplace` |
| **PHP** | `php` | `'php' => '7.4'` |
| **WordPress** | `wordpress` | `'wordpress' => '5.8'` |
| **Extensión PHP** | `php_extension:nombre` | `php_extension:curl` |
| **Función** | `function:nombre` | `function:wp_json_encode` |

### Uso

```php
$dependencias = [
    'required' => [
        'plugin:woocommerce' => [
            'name' => 'WooCommerce',
            'version' => '5.0'
        ],
        'php' => '7.4',
        'wordpress' => '5.8',
        'php_extension:curl' => true
    ],
    'optional' => [
        'plugin:wpml' => [
            'name' => 'WPML',
            'feature' => 'Soporte multiidioma'
        ]
    ]
];

$resultado = Flavor_Dependency_Checker::check($dependencias, 'Mi Addon');

if (is_wp_error($resultado)) {
    // Hay dependencias no satisfechas
    echo $resultado->get_error_message();
} else {
    // Todas las dependencias OK
}
```

### Características

- ✅ Verifica dependencias requeridas y opcionales
- ✅ Mensajes de error descriptivos
- ✅ Verificación de versiones
- ✅ Warnings para dependencias opcionales
- ✅ Verificación de plugins, addons, módulos, PHP, WordPress

## Clase: Flavor_Addon_Manager

### Propósito

Gestor centralizado para registrar, activar, desactivar y cargar addons.

### API Pública

#### Registrar un Addon

```php
Flavor_Addon_Manager::register_addon('mi-addon', [
    // REQUERIDO
    'name' => 'Mi Addon',
    'version' => '1.0.0',

    // OPCIONAL
    'description' => 'Descripción del addon',
    'author' => 'Tu Nombre',
    'author_uri' => 'https://tunombre.com',
    'requires_core' => '3.0.0',
    'requires' => [...],  // Array de dependencias
    'init_callback' => 'mi_addon_init',
    'settings_page' => 'admin.php?page=mi-addon',
    'icon' => 'dashicons-admin-plugins',
    'file' => __FILE__,
    'is_premium' => false,
    'documentation_url' => 'https://...'
]);
```

#### Verificar si un Addon está Activo

```php
if (Flavor_Addon_Manager::is_addon_active('web-builder-pro')) {
    // El addon está activo
}
```

#### Obtener Información de un Addon

```php
$info = Flavor_Addon_Manager::get_addon_info('web-builder-pro');
echo $info['version'];  // "1.0.0"
echo $info['name'];     // "Web Builder Pro"
```

#### Listar Todos los Addons

```php
// Todos los addons registrados
$registrados = Flavor_Addon_Manager::get_registered_addons();

// Solo los addons activos
$activos = Flavor_Addon_Manager::get_active_addons();

// Addons cargados en esta ejecución
$cargados = Flavor_Addon_Manager::get_loaded_addons();
```

#### Activar/Desactivar Programáticamente

```php
$manager = Flavor_Addon_Manager::get_instance();

// Activar
$resultado = $manager->activate_addon('mi-addon');
if (is_wp_error($resultado)) {
    echo $resultado->get_error_message();
}

// Desactivar
$manager->deactivate_addon('mi-addon');
```

### Hooks Disponibles

| Hook | Parámetros | Descripción |
|------|------------|-------------|
| `flavor_register_addons` | - | Se dispara para que los addons se registren |
| `flavor_addon_registered` | `$slug, $config` | Después de registrar un addon |
| `flavor_addon_activated` | `$slug` | Después de activar un addon |
| `flavor_addon_loaded` | `$slug, $config` | Después de cargar un addon |
| `flavor_addon_deactivated` | `$slug` | Después de desactivar un addon |

### Características

- ✅ Singleton pattern
- ✅ Registro automático de addons
- ✅ Verificación de dependencias antes de activar
- ✅ Prevención de carga duplicada
- ✅ Persistencia en base de datos
- ✅ Callbacks de inicialización
- ✅ Mensajes de error descriptivos

## Clase: Flavor_Addon_Admin

### Propósito

Panel de administración visual para gestionar addons instalados.

### Características

- ✅ Grid visual de addons
- ✅ Activar/desactivar con un clic
- ✅ Estadísticas de addons
- ✅ Enlaces a configuración
- ✅ Badges de estado (Activo, Premium, etc.)
- ✅ Mensajes de error descriptivos

### Ubicación

`Flavor Platform > Addons` en el menú de WordPress

## Migración a Addons

### Componentes que Pueden Migrar

| Componente Actual | Potencial Addon | Prioridad |
|-------------------|----------------|-----------|
| Web Builder | `flavor-web-builder-pro` | ⭐⭐⭐⭐⭐ |
| Red de Comunidades | `flavor-network-communities` | ⭐⭐⭐⭐⭐ |
| Sistema de Publicidad | `flavor-advertising-pro` | ⭐⭐⭐⭐⭐ |
| Admin Assistant | `flavor-admin-assistant` | ⭐⭐⭐⭐ |
| Deep Linking | `flavor-mobile-integration` | ⭐⭐⭐⭐ |
| Notificaciones | `flavor-notifications-pro` | ⭐⭐⭐ |

### Proceso de Migración

1. **Crear el plugin addon**
   - Estructura de carpetas
   - Archivo principal con headers de WordPress
   - Registro con Addon Manager

2. **Mover código**
   - Copiar clases a la carpeta del addon
   - Ajustar rutas y constantes
   - Actualizar namespaces si es necesario

3. **Definir dependencias**
   - Especificar `requires_core`
   - Listar dependencias de plugins
   - Verificar módulos requeridos

4. **Crear panel admin**
   - Submenu bajo Flavor Platform
   - Configuraciones específicas
   - Documentación de uso

5. **Testing**
   - Activación/desactivación
   - Verificación de dependencias
   - Compatibilidad con core

6. **Desacoplar del core**
   - Remover del archivo principal
   - Actualizar documentación
   - Comunicar a usuarios

## Ventajas del Sistema

### Para Desarrolladores

- ✅ Código más organizado y mantenible
- ✅ Desarrollo independiente de addons
- ✅ Testing más fácil
- ✅ Despliegue independiente
- ✅ Versionado individual

### Para Usuarios

- ✅ Instalar solo lo necesario
- ✅ Mejor rendimiento (menos código cargado)
- ✅ Actualizaciones granulares
- ✅ Fácil activar/desactivar funcionalidades
- ✅ Menor complejidad administrativa

### Para el Negocio

- ✅ Posibilidad de addons premium
- ✅ Marketplace de addons
- ✅ Licenciamiento individual
- ✅ Mejor escalabilidad del producto
- ✅ Ecosistema de terceros

## Limitaciones Actuales

- ❌ No hay sistema de actualización automática de addons
- ❌ No hay marketplace integrado
- ❌ No hay sistema de licenciamiento
- ❌ No hay verificación de firma digital
- ❌ No hay sandbox de seguridad

## Roadmap Futuro

### Versión 3.1
- [ ] Sistema de actualización automática de addons
- [ ] Verificación de compatibilidad entre versiones
- [ ] API REST para listar addons disponibles

### Versión 3.2
- [ ] Marketplace integrado
- [ ] Sistema de reviews y ratings
- [ ] Instalación de addons desde el admin

### Versión 3.3
- [ ] Sistema de licenciamiento
- [ ] Verificación de firma digital
- [ ] Sandbox de seguridad para addons

## Preguntas Frecuentes

### ¿Los addons se actualizan automáticamente?

No en la versión actual. Los addons se deben actualizar manualmente como cualquier plugin de WordPress.

### ¿Puedo crear addons privados?

Sí, puedes crear addons para uso interno sin publicarlos.

### ¿Los addons pueden depender de otros addons?

Sí, usando el sistema de dependencias:

```php
'requires' => [
    'required' => [
        'addon:otro-addon' => ['version' => '1.0.0']
    ]
]
```

### ¿Qué pasa si desactivo un addon que otro requiere?

El addon dependiente se desactivará automáticamente o mostrará un error.

### ¿Los addons pueden agregar módulos?

Sí, los addons pueden registrar nuevos módulos con el Module Loader.

### ¿Puedo monetizar addons?

Sí, puedes crear addons premium y venderlos independientemente.

## Recursos

- [Ejemplo de Addon](ADDON-EXAMPLE.md)
- [Documentación de API](API-REFERENCE.md)
- [Guía de Desarrollo](DEVELOPMENT-GUIDE.md)
- [Addons Oficiales](https://gailu.net/addons)

## Soporte

Para soporte sobre el sistema de addons:

- GitHub Issues: https://github.com/gailu-labs/flavor-platform/issues
- Documentación: https://gailu.net/docs
- Comunidad: https://community.gailu.net

---

**Versión del documento:** 1.0.0
**Última actualización:** 2025-02-04
**Compatible con:** Flavor Platform 3.0.0+
