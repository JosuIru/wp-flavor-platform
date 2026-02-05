# Cómo Construir tu Aplicación con Flavor Chat IA

Esta guía te explica cómo combinar módulos para crear diferentes tipos de aplicaciones.

## Índice

1. [Plantillas de Aplicación](#plantillas-de-aplicación)
2. [Configuración por Tipo de Proyecto](#configuración-por-tipo-de-proyecto)
3. [Combinaciones Recomendadas](#combinaciones-recomendadas)
4. [Integración entre Módulos](#integración-entre-módulos)
5. [Ejemplos Prácticos](#ejemplos-prácticos)

---

## Plantillas de Aplicación

### Portal Municipal / Ayuntamiento

**Módulos recomendados:**
```
✅ avisos_municipales    - Comunicados oficiales
✅ eventos               - Agenda cultural
✅ tramites              - Gestión de trámites
✅ incidencias           - Reportes ciudadanos
✅ transparencia         - Portal de transparencia
✅ participacion         - Procesos participativos
✅ presupuestos_participativos - Votación presupuestos
```

**Configuración:**
```php
// En functions.php o plugin personalizado
add_filter('flavor_active_modules', function($modules) {
    return [
        'avisos_municipales',
        'eventos',
        'tramites',
        'incidencias',
        'transparencia',
        'participacion',
        'presupuestos_participativos',
    ];
});
```

---

### Asociación / Club

**Módulos recomendados:**
```
✅ socios                - Gestión de membresías
✅ eventos               - Actividades
✅ chat_grupos           - Comunicación interna
✅ foros                 - Debates
✅ biblioteca            - Recursos compartidos
✅ espacios_comunes      - Reserva de salas
```

**Shortcodes disponibles:**
```html
<!-- Área de socios -->
[flavor_socios_area]

<!-- Calendario de eventos -->
[flavor_eventos_calendario]

<!-- Lista de foros -->
[flavor_foros]
```

---

### Cooperativa de Consumo

**Módulos recomendados:**
```
✅ grupos_consumo        - Pedidos grupales
✅ marketplace           - Catálogo productos
✅ socios                - Gestión de socios
✅ facturas              - Facturación
✅ banco_tiempo          - Intercambio servicios
```

**Flujo típico:**
```
1. Usuario se registra como socio
2. Se une a un grupo de consumo
3. Realiza pedidos del catálogo
4. Recibe factura automática
5. Puede intercambiar servicios (banco de tiempo)
```

---

### Coworking / Espacio Compartido

**Módulos recomendados:**
```
✅ espacios_comunes      - Reserva de salas
✅ reservas              - Sistema de reservas
✅ eventos               - Networking events
✅ clientes              - Gestión de clientes
✅ facturas              - Facturación mensual
✅ chat_interno          - Comunicación
```

---

### Tienda Online con IA

**Módulos recomendados:**
```
✅ woocommerce           - Integración tienda
✅ clientes              - CRM básico
✅ facturas              - Facturación
✅ advertising           - Promociones
```

**Ejemplo de integración:**
```php
// El chat IA puede:
// - Buscar productos
// - Añadir al carrito
// - Consultar pedidos
// - Gestionar incidencias

// Ejemplo de tool definition para Claude
$tools = [
    [
        'name' => 'buscar_productos',
        'description' => 'Busca productos en la tienda',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string'],
                'categoria' => ['type' => 'string'],
                'precio_max' => ['type' => 'number'],
            ],
        ],
    ],
];
```

---

### Restaurante / Bar

**Módulos recomendados:**
```
✅ bares                 - Gestión de local
✅ reservas              - Reserva de mesas
✅ eventos               - Eventos especiales
✅ multimedia            - Galería/menú visual
```

**Addon recomendado:**
- `flavor-restaurant-ordering` - Sistema completo de pedidos

---

### Red de Comunidades

**Módulos recomendados:**
```
✅ comunidades           - Gestión de comunidades
✅ colectivos            - Grupos/asociaciones
✅ red_social            - Funciones sociales
✅ chat_grupos           - Comunicación
✅ eventos               - Actividades compartidas
```

**Addon recomendado:**
- `flavor-network-communities` - Conectar múltiples sitios

---

### Plataforma Educativa

**Módulos recomendados:**
```
✅ cursos                - Gestión de cursos
✅ talleres              - Talleres prácticos
✅ biblioteca            - Material didáctico
✅ foros                 - Debate y dudas
✅ eventos               - Clases en vivo
```

---

### Movilidad Sostenible

**Módulos recomendados:**
```
✅ carpooling            - Compartir coche
✅ bicicletas_compartidas - Préstamo bicis
✅ parkings              - Gestión parking
```

---

## Configuración por Tipo de Proyecto

### Paso 1: Definir el Perfil

```php
// En tu theme functions.php o plugin
define('FLAVOR_APP_TYPE', 'municipal'); // municipal, asociacion, cooperativa, etc.

add_action('after_setup_theme', function() {
    $perfiles = [
        'municipal' => [
            'modules' => ['avisos_municipales', 'eventos', 'tramites', 'incidencias'],
            'theme' => 'flavor-municipal',
            'features' => ['transparencia', 'participacion'],
        ],
        'asociacion' => [
            'modules' => ['socios', 'eventos', 'chat_grupos', 'foros'],
            'theme' => 'flavor-social',
            'features' => ['membership', 'community'],
        ],
        'cooperativa' => [
            'modules' => ['grupos_consumo', 'marketplace', 'socios', 'facturas'],
            'theme' => 'flavor-commerce',
            'features' => ['shop', 'invoicing'],
        ],
    ];

    $tipo = defined('FLAVOR_APP_TYPE') ? FLAVOR_APP_TYPE : 'asociacion';
    $perfil = $perfiles[$tipo] ?? $perfiles['asociacion'];

    // Aplicar configuración
    update_option('flavor_app_profile', $perfil);
});
```

### Paso 2: Activar Módulos Automáticamente

```php
add_filter('flavor_active_modules', function($modules) {
    $perfil = get_option('flavor_app_profile', []);
    return $perfil['modules'] ?? $modules;
});
```

---

## Combinaciones Recomendadas

### Módulos que Trabajan Bien Juntos

| Combinación | Módulos | Beneficio |
|-------------|---------|-----------|
| **Gestión Social** | socios + eventos + chat_grupos | Comunidad activa |
| **Comercio Local** | marketplace + facturas + clientes | Tienda completa |
| **Participación** | participacion + presupuestos + foros | Democracia participativa |
| **Movilidad** | carpooling + bicicletas + parkings | Transporte sostenible |
| **Formación** | cursos + talleres + biblioteca | Plataforma educativa |

### Dependencias Automáticas

Algunos módulos activan automáticamente otros:

```
grupos_consumo → marketplace (catálogo)
facturas → socios (datos facturación)
presupuestos_participativos → participacion (votaciones)
chat_grupos → chat_interno (base de mensajería)
```

---

## Integración entre Módulos

### Ejemplo: Eventos + Socios

```php
// Los socios obtienen descuento en eventos
add_filter('flavor_evento_precio', function($precio, $evento_id, $user_id) {
    if (class_exists('Flavor_Chat_Socios_Module')) {
        $socios_module = Flavor_Chat_Module_Loader::get_instance()
            ->get_module('socios');

        if ($socios_module && $socios_module->es_socio_activo($user_id)) {
            $descuento = $socios_module->get_setting('descuento_eventos', 20);
            $precio = $precio * (1 - $descuento / 100);
        }
    }
    return $precio;
}, 10, 3);
```

### Ejemplo: Marketplace + Facturas

```php
// Generar factura automática en compras
add_action('flavor_marketplace_compra_completada', function($compra_id) {
    if (class_exists('Flavor_Chat_Facturas_Module')) {
        $facturas = Flavor_Chat_Module_Loader::get_instance()
            ->get_module('facturas');

        if ($facturas) {
            $compra = get_post($compra_id);
            $facturas->generar_factura([
                'cliente_id' => $compra->post_author,
                'items' => get_post_meta($compra_id, '_items', true),
                'total' => get_post_meta($compra_id, '_total', true),
            ]);
        }
    }
});
```

### Ejemplo: Chat IA Contextual

```php
// El chat IA conoce el contexto de todos los módulos activos
add_filter('flavor_chat_system_prompt', function($prompt) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $contexto = [];

    foreach ($loader->get_loaded_modules() as $module) {
        $knowledge = $module->get_knowledge_base();
        if (!empty($knowledge)) {
            $contexto[] = $knowledge;
        }
    }

    $prompt .= "\n\nMódulos disponibles:\n" . json_encode($contexto, JSON_PRETTY_PRINT);
    return $prompt;
});
```

---

## Ejemplos Prácticos

### Ejemplo 1: Portal Vecinal Completo

**Objetivo:** Crear un portal para una comunidad de vecinos.

**Módulos:**
```php
$modulos_activos = [
    'comunidades',      // Gestión de la comunidad
    'avisos_municipales', // Comunicados del presidente
    'incidencias',      // Reportar problemas
    'espacios_comunes', // Reservar salón de actos
    'chat_grupos',      // Grupos de vecinos
    'participacion',    // Votaciones en juntas
];
```

**Páginas a crear:**
```php
$paginas = [
    'Mi Comunidad' => '[flavor_comunidad_dashboard]',
    'Avisos' => '[flavor_avisos]',
    'Incidencias' => '[flavor_incidencias_form]',
    'Reservar Espacios' => '[flavor_espacios_calendario]',
    'Votaciones' => '[flavor_participacion_activa]',
];

foreach ($paginas as $titulo => $contenido) {
    wp_insert_post([
        'post_title' => $titulo,
        'post_content' => $contenido,
        'post_status' => 'publish',
        'post_type' => 'page',
    ]);
}
```

---

### Ejemplo 2: Cooperativa de Consumo

**Objetivo:** Plataforma para grupo de consumo ecológico.

**Configuración completa:**

```php
<?php
/**
 * Plugin Name: Mi Cooperativa Setup
 * Description: Configuración personalizada para cooperativa de consumo
 */

// Activar módulos necesarios
add_filter('flavor_active_modules', function() {
    return [
        'grupos_consumo',
        'marketplace',
        'socios',
        'facturas',
        'banco_tiempo',
        'eventos',
    ];
});

// Configuración de socios
add_filter('flavor_module_settings_socios', function($settings) {
    return array_merge($settings, [
        'cuota_mensual' => 10,
        'periodo_prueba' => 30,
        'requiere_aprobacion' => true,
    ]);
});

// Configuración de grupos de consumo
add_filter('flavor_module_settings_grupos_consumo', function($settings) {
    return array_merge($settings, [
        'pedido_minimo' => 50,
        'dia_cierre_pedidos' => 'miercoles',
        'dia_recogida' => 'sabado',
        'comision_gestion' => 5, // 5%
    ]);
});

// Roles personalizados
add_action('init', function() {
    add_role('socio_cooperativa', 'Socio Cooperativa', [
        'read' => true,
        'flavor_hacer_pedidos' => true,
        'flavor_ver_catalogo' => true,
        'flavor_banco_tiempo' => true,
    ]);

    add_role('gestor_cooperativa', 'Gestor Cooperativa', [
        'read' => true,
        'flavor_hacer_pedidos' => true,
        'flavor_ver_catalogo' => true,
        'flavor_gestionar_pedidos' => true,
        'flavor_gestionar_socios' => true,
        'flavor_ver_facturas' => true,
    ]);
});
```

---

### Ejemplo 3: Centro Cultural

**Módulos:**
```php
$modulos = [
    'eventos',
    'cursos',
    'talleres',
    'biblioteca',
    'espacios_comunes',
    'multimedia',
];
```

**Template personalizado:**
```php
// theme/page-centro-cultural.php
<?php
get_header();

// Próximos eventos
$eventos = do_shortcode('[flavor_eventos_proximos limit="3"]');

// Cursos activos
$cursos = do_shortcode('[flavor_cursos_activos]');

// Galería multimedia
$galeria = do_shortcode('[flavor_multimedia_galeria categoria="exposiciones"]');
?>

<div class="centro-cultural">
    <section class="proximos-eventos">
        <h2>Próximos Eventos</h2>
        <?php echo $eventos; ?>
    </section>

    <section class="cursos">
        <h2>Cursos y Talleres</h2>
        <?php echo $cursos; ?>
    </section>

    <section class="galeria">
        <h2>Exposiciones</h2>
        <?php echo $galeria; ?>
    </section>
</div>

<?php get_footer(); ?>
```

---

## Checklist de Implementación

### Antes de Empezar

- [ ] Definir tipo de aplicación
- [ ] Listar funcionalidades necesarias
- [ ] Identificar módulos requeridos
- [ ] Verificar dependencias

### Configuración Inicial

- [ ] Activar módulos seleccionados
- [ ] Configurar cada módulo
- [ ] Crear páginas necesarias
- [ ] Configurar menús

### Personalización

- [ ] Ajustar estilos CSS
- [ ] Personalizar templates si es necesario
- [ ] Configurar el Chat IA
- [ ] Probar flujos de usuario

### Lanzamiento

- [ ] Pruebas de funcionalidad
- [ ] Pruebas de rendimiento
- [ ] Documentar para usuarios finales
- [ ] Capacitar administradores

---

## Recursos Adicionales

- **[GUIA_MODULOS.md](./GUIA_MODULOS.md)** - Referencia completa de módulos
- **[API_REFERENCE.md](./API_REFERENCE.md)** - Documentación de la API
- **Soporte**: support@gailu.net

---

*Documentación actualizada: Febrero 2026 - Flavor Chat IA v3.1.0*
