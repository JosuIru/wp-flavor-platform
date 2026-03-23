# Hooks y Filtros - Flavor Platform

Referencia completa de hooks (actions y filters) para desarrolladores que quieran extender el plugin.

## Índice

- [Filtros de Módulos](#filtros-de-módulos)
- [Filtros de Dashboard](#filtros-de-dashboard)
- [Filtros de API](#filtros-de-api)
- [Filtros de Diseño](#filtros-de-diseño)
- [Filtros de Permisos](#filtros-de-permisos)
- [Actions de Módulos](#actions-de-módulos)
- [Actions de Notificaciones](#actions-de-notificaciones)
- [Actions de Eventos](#actions-de-eventos)

---

## Filtros de Módulos

### `flavor_chat_ia_modules`
Modifica la lista de módulos disponibles.

```php
add_filter('flavor_chat_ia_modules', function($modules) {
    // Añadir módulo personalizado
    $modules['mi_modulo'] = [
        'name' => 'Mi Módulo',
        'description' => 'Descripción',
        'category' => 'custom'
    ];
    return $modules;
});
```

### `flavor_before_activate_module`
Se ejecuta antes de activar un módulo. Permite cancelar la activación.

```php
add_filter('flavor_before_activate_module', function($allow, $module_id) {
    if ($module_id === 'mi_modulo' && !check_dependencies()) {
        return false; // Cancelar activación
    }
    return $allow;
}, 10, 2);
```

### `flavor_module_capabilities`
Define las capacidades (permisos) de un módulo.

```php
add_filter('flavor_module_capabilities', function($capabilities, $module_id) {
    if ($module_id === 'eventos') {
        $capabilities['crear_evento'] = 'Crear eventos';
        $capabilities['editar_evento'] = 'Editar eventos';
    }
    return $capabilities;
}, 10, 2);
```

### `flavor_{$module_id}_dashboard_tabs`
Añade pestañas al dashboard de un módulo específico.

```php
add_filter('flavor_eventos_dashboard_tabs', function($tabs) {
    $tabs['estadisticas'] = [
        'label' => 'Estadísticas',
        'icon' => 'chart-bar',
        'callback' => 'render_estadisticas_tab'
    ];
    return $tabs;
});
```

### `flavor_module_config_schema`
Define el esquema de configuración de un módulo.

```php
add_filter('flavor_module_config_schema', function($schema, $module_id) {
    if ($module_id === 'mi_modulo') {
        $schema['fields']['mi_opcion'] = [
            'type' => 'text',
            'label' => 'Mi Opción',
            'default' => ''
        ];
    }
    return $schema;
}, 10, 2);
```

---

## Filtros de Dashboard

### `flavor_dashboard_widgets`
Registra widgets para el dashboard del cliente.

```php
add_filter('flavor_dashboard_widgets', function($widgets) {
    $widgets['mi_widget'] = [
        'title' => 'Mi Widget',
        'callback' => 'render_mi_widget',
        'size' => 'medium', // small, medium, large
        'priority' => 10
    ];
    return $widgets;
});
```

### `flavor_client_dashboard_data`
Modifica los datos del dashboard del cliente.

```php
add_filter('flavor_client_dashboard_data', function($data, $user_id) {
    $data['custom_stats'] = get_user_custom_stats($user_id);
    return $data;
}, 10, 2);
```

### `flavor_client_dashboard_widgets`
Filtra los widgets visibles en el dashboard.

```php
add_filter('flavor_client_dashboard_widgets', function($widgets, $user_id) {
    // Ocultar widget para ciertos usuarios
    if (!user_can($user_id, 'manage_options')) {
        unset($widgets['admin_stats']);
    }
    return $widgets;
}, 10, 2);
```

### `flavor_client_dashboard_estadisticas`
Modifica las estadísticas mostradas.

```php
add_filter('flavor_client_dashboard_estadisticas', function($stats, $user_id) {
    $stats['eventos_creados'] = count_user_events($user_id);
    return $stats;
}, 10, 2);
```

---

## Filtros de API

### `flavor_api_endpoints`
Registra endpoints de API adicionales.

```php
add_filter('flavor_api_endpoints', function($endpoints) {
    $endpoints['mi-endpoint'] = [
        'methods' => 'GET',
        'callback' => 'mi_endpoint_callback',
        'permission_callback' => '__return_true'
    ];
    return $endpoints;
});
```

### `flavor_mobile_modules_config`
Configura módulos para la app móvil.

```php
add_filter('flavor_mobile_modules_config', function($config) {
    $config['mi_modulo'] = [
        'enabled' => true,
        'screen' => 'MiModuloScreen',
        'icon' => 'star'
    ];
    return $config;
});
```

### `flavor_app_drawer_items`
Añade items al drawer de la app móvil.

```php
add_filter('flavor_app_drawer_items', function($items) {
    $items[] = [
        'id' => 'mi_seccion',
        'title' => 'Mi Sección',
        'icon' => 'folder',
        'route' => '/mi-seccion'
    ];
    return $items;
});
```

---

## Filtros de Diseño

### `flavor_themes`
Define temas visuales disponibles.

```php
add_filter('flavor_themes', function($themes) {
    $themes['mi_tema'] = [
        'name' => 'Mi Tema',
        'colors' => [
            'primary' => '#ff6600',
            'secondary' => '#333333'
        ]
    ];
    return $themes;
});
```

### `flavor_landing_templates`
Registra plantillas de landing pages.

```php
add_filter('flavor_landing_templates', function($templates) {
    $templates['mi_plantilla'] = [
        'name' => 'Mi Plantilla',
        'sections' => ['hero', 'features', 'cta'],
        'preview' => 'url/preview.jpg'
    ];
    return $templates;
});
```

### `flavor_vb_sections`
Registra secciones para Visual Builder.

```php
add_filter('flavor_vb_sections', function($sections) {
    $sections['mi_seccion'] = [
        'name' => 'Mi Sección',
        'icon' => 'layout',
        'fields' => [...],
        'render' => 'render_mi_seccion'
    ];
    return $sections;
});
```

### `flavor_animations`
Define animaciones disponibles.

```php
add_filter('flavor_animations', function($animations) {
    $animations['mi_animacion'] = [
        'name' => 'Mi Animación',
        'css_class' => 'animate-mi-animacion'
    ];
    return $animations;
});
```

---

## Filtros de Permisos

### `flavor_user_can`
Verifica permisos personalizados de usuario.

```php
add_filter('flavor_user_can', function($can, $capability, $user_id) {
    if ($capability === 'ver_dashboard_especial') {
        return user_has_special_access($user_id);
    }
    return $can;
}, 10, 3);
```

### `flavor_module_access`
Controla el acceso a módulos.

```php
add_filter('flavor_module_access', function($has_access, $module_id, $user_id) {
    if ($module_id === 'modulo_premium') {
        return user_has_premium($user_id);
    }
    return $has_access;
}, 10, 3);
```

### `flavor_member_roles`
Define roles de miembro personalizados.

```php
add_filter('flavor_member_roles', function($roles) {
    $roles['coordinador'] = [
        'name' => 'Coordinador',
        'capabilities' => ['gestionar_eventos', 'ver_estadisticas']
    ];
    return $roles;
});
```

---

## Actions de Módulos

### `flavor_module_activated`
Se ejecuta cuando se activa un módulo.

```php
add_action('flavor_module_activated', function($module_id) {
    // Crear tablas, configurar opciones, etc.
    if ($module_id === 'mi_modulo') {
        create_module_tables();
    }
});
```

### `flavor_module_deactivated`
Se ejecuta cuando se desactiva un módulo.

```php
add_action('flavor_module_deactivated', function($module_id) {
    // Limpiar caché, etc.
    wp_cache_delete('module_' . $module_id);
});
```

### `flavor_module_{$module_id}_activated`
Acción específica por módulo.

```php
add_action('flavor_module_eventos_activated', function() {
    // Configurar módulo de eventos
    update_option('eventos_configured', true);
});
```

### `flavor_module_config_saved`
Se ejecuta al guardar configuración de módulo.

```php
add_action('flavor_module_config_saved', function($module_id, $config) {
    // Procesar configuración guardada
    do_something_with_config($config);
}, 10, 2);
```

---

## Actions de Notificaciones

### `flavor_notification_sent`
Se ejecuta al enviar una notificación.

```php
add_action('flavor_notification_sent', function($notification_id, $user_id, $type) {
    // Log, analytics, etc.
    log_notification($notification_id, $user_id);
}, 10, 3);
```

### `flavor_send_push_notification`
Envía notificación push.

```php
do_action('flavor_send_push_notification', [
    'user_id' => 123,
    'title' => 'Nueva actividad',
    'body' => 'Tienes una nueva notificación',
    'data' => ['type' => 'evento']
]);
```

### `flavor_notificar_usuario`
Notifica a un usuario específico.

```php
do_action('flavor_notificar_usuario', $user_id, [
    'tipo' => 'info',
    'mensaje' => 'Tu solicitud ha sido aprobada',
    'enlace' => '/mi-cuenta/'
]);
```

---

## Actions de Eventos de Módulos

### Socios
```php
// Nuevo socio creado
add_action('socio_solicitud_created', function($solicitud_id) {...});

// Cuota pagada
add_action('flavor_socios_cuota_pagada', function($socio_id, $cuota_id) {...}, 10, 2);
```

### Eventos
```php
// Inscripción a evento
add_action('flavor_evento_inscripcion', function($evento_id, $user_id) {...}, 10, 2);
```

### Grupos de Consumo
```php
// Pedido creado
add_action('gc_pedido_creado', function($pedido_id) {...});

// Ciclo cerrado
add_action('gc_ciclo_cerrado', function($ciclo_id) {...});

// Checkout procesado
add_action('gc_checkout_processed', function($order_id) {...});
```

### Crowdfunding
```php
// Proyecto creado
add_action('flavor_crowdfunding_proyecto_creado', function($proyecto_id) {...});

// Aportación registrada
add_action('flavor_crowdfunding_aportacion_registrada', function($aportacion_id, $proyecto_id) {...}, 10, 2);

// Hito alcanzado
add_action('flavor_crowdfunding_hito_alcanzado', function($proyecto_id, $porcentaje) {...}, 10, 2);
```

### Reservas
```php
// Reserva creada
add_action('flavor_reserva_creada', function($reserva_id) {...});

// Reserva cancelada
add_action('flavor_reserva_cancelada', function($reserva_id) {...});
```

### Encuestas
```php
// Encuesta respondida
add_action('flavor_encuesta_respondida', function($encuesta_id, $user_id, $respuestas) {...}, 10, 3);
```

### Cursos
```php
// Curso completado
add_action('flavor_curso_completado', function($curso_id, $user_id) {...}, 10, 2);

// Lección completada
add_action('flavor_leccion_completada', function($leccion_id, $user_id) {...}, 10, 2);
```

---

## Actions de Visual Builder

### `vbp_loaded`
VBP está cargado y listo.

```php
add_action('vbp_loaded', function() {
    // Registrar bloques personalizados
});
```

### `vbp_register_blocks`
Registra bloques de VBP.

```php
add_action('vbp_register_blocks', function($registry) {
    $registry->register('mi_bloque', [
        'name' => 'Mi Bloque',
        'render' => 'render_mi_bloque'
    ]);
});
```

### `vbp_form_submitted`
Formulario VBP enviado.

```php
add_action('vbp_form_submitted', function($form_id, $data) {
    // Procesar envío de formulario
}, 10, 2);
```

---

## Actions de Sistema

### `flavor_log`
Registra evento en el log.

```php
do_action('flavor_log', 'mi_evento', [
    'user_id' => get_current_user_id(),
    'details' => 'Descripción del evento'
]);
```

### `flavor_theme_changed`
Tema visual cambiado.

```php
add_action('flavor_theme_changed', function($new_theme, $old_theme) {
    // Limpiar caché de estilos
    wp_cache_delete('theme_styles');
}, 10, 2);
```

### `flavor_register_dashboard_widgets`
Momento para registrar widgets.

```php
add_action('flavor_register_dashboard_widgets', function() {
    // Registrar widgets aquí
});
```

---

## Hooks de Integración

### `flavor_integration_providers`
Registra proveedores de integración.

```php
add_filter('flavor_integration_providers', function($providers) {
    $providers['mi_servicio'] = [
        'name' => 'Mi Servicio',
        'callback' => 'mi_servicio_callback'
    ];
    return $providers;
});
```

### `flavor_webhook_received_{$type}`
Webhook recibido.

```php
add_action('flavor_webhook_received_stripe', function($payload) {
    // Procesar webhook de Stripe
});
```

---

## Estadísticas

| Tipo | Cantidad |
|------|----------|
| Filtros | ~120 |
| Actions | ~230 |
| **Total** | **~350** |

---

## Mejores Prácticas

1. **Usa prefijos únicos** para evitar conflictos
2. **Documenta tus hooks** para otros desarrolladores
3. **Verifica el contexto** antes de modificar datos
4. **Usa prioridades** adecuadas (default: 10)
5. **Limpia hooks** cuando ya no sean necesarios

```php
// Buena práctica: verificar contexto
add_filter('flavor_dashboard_widgets', function($widgets) {
    if (!is_user_logged_in()) {
        return $widgets;
    }
    // Modificar widgets...
    return $widgets;
});
```

---

*Última actualización: Marzo 2026*
