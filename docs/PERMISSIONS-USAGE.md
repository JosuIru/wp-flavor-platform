# Sistema de Permisos Granulares - Flavor Platform

## Descripcion General

Flavor Platform incluye un sistema de permisos granulares que permite controlar el acceso
a funcionalidades especificas por modulo. Este sistema extiende las capabilities de WordPress
con roles especificos por modulo.

## Componentes Principales

### 1. Flavor_Role_Manager
Gestiona la definicion de roles y capabilities.

```php
// Obtener instancia
$role_manager = Flavor_Role_Manager::get_instance();

// Obtener todas las capabilities
$todas = $role_manager->obtener_todas_las_capabilities();

// Obtener capabilities de un modulo
$caps_gc = $role_manager->obtener_capabilities_modulo('grupos_consumo');

// Asignar rol de modulo a usuario
$role_manager->asignar_rol_modulo($user_id, 'grupos_consumo', 'gc_coordinador');

// Revocar rol de modulo
$role_manager->revocar_rol_modulo($user_id, 'grupos_consumo');
```

### 2. Flavor_Permission_Helper
Clase estatica para verificar permisos de forma sencilla.

```php
// Verificar un permiso
if (Flavor_Permission_Helper::can('gc_gestionar_ciclos')) {
    // mostrar boton crear ciclo
}

// Verificar multiples permisos (AND)
if (Flavor_Permission_Helper::can_all(['gc_ver_productos', 'gc_crear_pedido'])) {
    // el usuario puede ver productos Y crear pedidos
}

// Verificar al menos uno (OR)
if (Flavor_Permission_Helper::can_any(['gc_gestionar_ciclos', 'eventos_gestionar'])) {
    // el usuario puede gestionar ciclos O eventos
}

// Obtener rol del usuario en un modulo
$rol = Flavor_Permission_Helper::get_module_role('grupos_consumo');

// Asignar rol de modulo
Flavor_Permission_Helper::assign_module_role($user_id, 'grupos_consumo', 'gc_coordinador');
```

### 3. Funciones Helper Globales

```php
// Verificar permiso (shortcut)
if (flavor_can('gc_gestionar_ciclos')) {
    // ...
}

// Verificar multiples (OR)
if (flavor_can_any(['gc_gestionar_ciclos', 'gc_cerrar_ciclos'])) {
    // ...
}

// Verificar multiples (AND)
if (flavor_can_all(['gc_ver_productos', 'gc_crear_pedido'])) {
    // ...
}

// Verificar si es admin de Flavor
if (flavor_is_admin()) {
    // ...
}

// Requerir permiso (envia error 403 si no tiene)
flavor_require_permission('gc_gestionar_ciclos');
```

## Capabilities por Modulo

### Grupos de Consumo (gc_*)
- `gc_ver_productos` - Ver productos del grupo
- `gc_gestionar_mis_productos` - Gestionar mis productos (productor)
- `gc_gestionar_productos` - Gestionar todos los productos
- `gc_crear_pedido` - Crear pedidos
- `gc_ver_pedidos_propios` - Ver pedidos propios
- `gc_gestionar_pedidos` - Gestionar todos los pedidos
- `gc_cancelar_pedido_propio` - Cancelar pedido propio
- `gc_ver_ciclos` - Ver ciclos de pedidos
- `gc_gestionar_ciclos` - Crear y gestionar ciclos
- `gc_cerrar_ciclos` - Cerrar ciclos de pedidos
- `gc_ver_productores` - Ver productores
- `gc_gestionar_productores` - Gestionar productores
- `gc_aprobar_productores` - Aprobar nuevos productores
- `gc_ver_grupos` - Ver grupos de consumo
- `gc_gestionar_grupos` - Gestionar grupos de consumo
- `gc_crear_grupos` - Crear nuevos grupos
- `gc_gestionar_miembros` - Gestionar miembros del grupo
- `gc_aprobar_solicitudes` - Aprobar solicitudes de union
- `gc_ver_repartos` - Ver calendario de repartos
- `gc_gestionar_repartos` - Gestionar repartos
- `gc_exportar_datos` - Exportar datos del grupo
- `gc_ver_estadisticas` - Ver estadisticas del grupo
- `gc_gestionar_suscripciones` - Gestionar suscripciones
- `gc_configurar_grupo` - Configurar ajustes del grupo

### Eventos (eventos_*)
- `eventos_ver` - Ver eventos
- `eventos_ver_detalles` - Ver detalles de eventos
- `eventos_inscribirse` - Inscribirse en eventos
- `eventos_crear` - Crear eventos
- `eventos_editar_propios` - Editar eventos propios
- `eventos_gestionar` - Gestionar todos los eventos
- `eventos_eliminar` - Eliminar eventos
- `eventos_gestionar_asistentes` - Gestionar asistentes
- `eventos_ver_estadisticas` - Ver estadisticas de eventos
- `eventos_exportar` - Exportar datos de eventos
- `eventos_configurar` - Configurar modulo de eventos

### Socios (socios_*)
- `socios_ver_propios` - Ver datos propios de socio
- `socios_editar_propios` - Editar datos propios
- `socios_ver_directorio` - Ver directorio de socios
- `socios_ver_todos` - Ver todos los socios
- `socios_gestionar` - Gestionar socios
- `socios_crear` - Crear nuevos socios
- `socios_eliminar` - Eliminar socios
- `socios_gestionar_cuotas` - Gestionar cuotas
- `socios_ver_cuotas` - Ver cuotas propias
- `socios_importar` - Importar socios
- `socios_exportar` - Exportar socios
- `socios_configurar` - Configurar modulo de socios

## Roles por Modulo

### Grupos de Consumo
| Rol | Descripcion | Capabilities |
|-----|-------------|--------------|
| gc_consumidor | Puede ver productos y realizar pedidos | gc_ver_productos, gc_crear_pedido, gc_ver_pedidos_propios... |
| gc_productor | Puede gestionar sus productos | gc_ver_productos, gc_gestionar_mis_productos... |
| gc_coordinador | Acceso completo | gc_* (todas) |

### Eventos
| Rol | Descripcion | Capabilities |
|-----|-------------|--------------|
| eventos_asistente | Ver e inscribirse | eventos_ver, eventos_inscribirse... |
| eventos_organizador | Crear y gestionar propios | eventos_crear, eventos_editar_propios... |
| eventos_gestor | Acceso completo | eventos_* (todas) |

### Socios
| Rol | Descripcion | Capabilities |
|-----|-------------|--------------|
| socios_basico | Ver y editar propios | socios_ver_propios, socios_editar_propios... |
| socios_tesorero | Gestionar cuotas | socios_gestionar_cuotas, socios_exportar... |
| socios_admin | Acceso completo | socios_* (todas) |

## Uso en Templates

```php
<!-- Mostrar boton solo si tiene permiso -->
<?php if (flavor_can('gc_gestionar_ciclos')): ?>
    <button class="btn-crear-ciclo">Crear Ciclo</button>
<?php endif; ?>

<!-- Usando el helper if_can -->
<?php echo Flavor_Permission_Helper::if_can(
    'gc_gestionar_ciclos',
    '<button class="btn-crear-ciclo">Crear Ciclo</button>',
    '<p>No tienes permisos para crear ciclos</p>'
); ?>
```

## Uso en AJAX

```php
public function ajax_crear_ciclo() {
    check_ajax_referer('gc_nonce', 'nonce');

    // Verificar permiso granular
    if (!Flavor_Permission_Helper::can('gc_gestionar_ciclos')) {
        wp_send_json_error([
            'mensaje' => __('No tienes permisos para esta accion.', 'flavor-chat-ia'),
            'code' => 'permission_denied',
        ], 403);
    }

    // ... logica de crear ciclo
}
```

## Uso en REST API

```php
public function register_routes() {
    register_rest_route('flavor/v1', '/ciclos', [
        'methods' => 'POST',
        'callback' => [$this, 'crear_ciclo'],
        'permission_callback' => function() {
            return Flavor_Permission_Helper::can('gc_gestionar_ciclos');
        },
    ]);
}
```

## Panel de Administracion

El panel de permisos esta disponible en:
**Flavor Platform > Permisos** (`/wp-admin/admin.php?page=flavor-permissions`)

Desde ahi puedes:
- Ver matriz de roles vs capabilities
- Crear roles personalizados
- Asignar roles de modulo a usuarios
- Editar capabilities de roles existentes

## Comandos WP-CLI

```bash
# Asignar rol de modulo
wp flavor permission grant 5 gc_coordinador

# Revocar rol de modulo
wp flavor permission revoke 5 grupos_consumo

# Listar permisos de usuario
wp flavor permission list --user=5

# Listar capabilities de modulo
wp flavor permission list --module=grupos_consumo

# Ver todos los roles disponibles
wp flavor permission roles

# Verificar si usuario tiene capability
wp flavor permission check 5 gc_gestionar_ciclos

# Crear rol personalizado
wp flavor permission create-role mi_rol "Mi Rol" --capabilities=gc_ver_productos,gc_crear_pedido

# Sincronizar roles del sistema
wp flavor permission sync

# Exportar permisos de usuario
wp flavor permission export 5 > permisos.json

# Importar permisos a usuario
wp flavor permission import 12 permisos.json
```

## Filtros para Extensibilidad

```php
// Modificar permisos dinamicamente
add_filter('flavor_user_can', function($can, $capability, $user_id) {
    // Logica personalizada
    return $can;
}, 10, 3);

// Agregar capabilities a un modulo
add_filter('flavor_module_capabilities', function($caps, $module_slug) {
    if ($module_slug === 'grupos_consumo') {
        $caps['gc_mi_capability_custom'] = __('Mi capability personalizada', 'mi-plugin');
    }
    return $caps;
}, 10, 2);

// Agregar roles a un modulo
add_filter('flavor_module_roles', function($roles) {
    $roles['grupos_consumo']['gc_mi_rol'] = [
        'label' => __('Mi Rol', 'mi-plugin'),
        'description' => __('Descripcion', 'mi-plugin'),
        'capabilities' => ['gc_ver_productos', 'gc_mi_capability_custom'],
    ];
    return $roles;
});
```

## Acciones (Hooks)

```php
// Cuando se asigna un rol de modulo
add_action('flavor_module_role_assigned', function($user_id, $module, $role) {
    // Enviar notificacion, log, etc.
}, 10, 3);

// Cuando se revoca un rol de modulo
add_action('flavor_module_role_revoked', function($user_id, $module, $role) {
    // ...
}, 10, 3);

// Cuando se crea un rol personalizado
add_action('flavor_custom_role_created', function($slug, $datos) {
    // ...
}, 10, 2);

// Cuando se actualizan capabilities de un rol
add_action('flavor_role_capabilities_updated', function($slug, $capabilities) {
    // ...
}, 10, 2);
```
