# Plan de Desarrollo - Flavor Chat IA

## Estado Actual del Sistema (Actualizado 2026-02-28)

### Resumen de Auditoría

| Categoría | Total | Completos | Parciales | Pendientes |
|-----------|-------|-----------|-----------|------------|
| Módulos | 60 | 60 | 0 | 0 |
| Dashboard Tabs | 60 | 60 | 0 | 0 |
| Widgets Dashboard | 60 | 35 | 0 | 25 |
| Tablas BD | ~100 | ~100 | 0 | 0 |
| Shortcodes | 333 | 333 | 0 | 0 |

---

## ✅ Tareas Completadas

### Dashboard Tabs (Todos implementados)

Los siguientes módulos ahora tienen Dashboard Tab completo:

- ✅ advertising, chat-estados, chat-grupos, chat-interno
- ✅ clientes, dex-solana, empresarial, facturas
- ✅ fichaje-empleados, red-social, sello-conciencia
- ✅ trading-ia, woocommerce

### Dashboard Widgets (Todos implementados)

- ✅ banco-tiempo, eventos, marketplace, comunidades
- ✅ socios, foros, incidencias, reservas

### Correcciones Técnicas Completadas

1. ✅ **Dashboard Tabs Loader** - Sin duplicados, limpio
2. ✅ **should_load_assets()** - 44 módulos con verificación de carga de assets
   - Métodos implementados: `should_load_assets()`, `is_module_page()`, `debe_cargar_assets()`, `maybe_enqueue_frontend_assets()`
   - Optimiza la carga de CSS/JS solo cuando el shortcode está presente
   - Frontend Controllers también actualizados: eventos, espacios-comunes, foros, fichaje-empleados
3. ℹ️ **Nombres de clase no estándar** - Mantenidos por compatibilidad:
   - email-marketing: `Flavor_EM_Dashboard_Tab`
   - grupos-consumo: `Flavor_GC_Dashboard_Tab`

---

## Tareas por Módulo

### Template para Dashboard Tab

```php
<?php
/**
 * Dashboard Tab para {Módulo}
 */

class Flavor_{Modulo}_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['{modulo}'] = [
            'label' => __('{Nombre}', 'flavor-chat-ia'),
            'icon' => 'dashicons-{icon}',
            'callback' => [$this, 'render_tab'],
            'priority' => 50,
        ];
        return $tabs;
    }

    public function render_tab() {
        // Cargar datos reales de BD
        $datos = $this->obtener_datos_usuario();

        // Incluir vista
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/{modulo}/views/dashboard.php';
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();

        // Consultas reales a BD
        // ...

        return $datos;
    }
}

Flavor_{Modulo}_Dashboard_Tab::get_instance();
```

### Template para Widget

```php
<?php
/**
 * Widget Dashboard para {Módulo}
 */

class Flavor_{Modulo}_Widget extends Flavor_Dashboard_Widget_Base {

    public function get_widget_id() {
        return '{modulo}_widget';
    }

    public function get_widget_title() {
        return __('{Nombre}', 'flavor-chat-ia');
    }

    public function get_widget_data() {
        $user_id = get_current_user_id();

        // Datos reales de BD
        return [
            'stats' => [
                ['label' => 'Total', 'value' => $this->get_total($user_id)],
            ],
            'items' => $this->get_recent_items($user_id, 5),
            'cta' => [
                'label' => __('Ver todo', 'flavor-chat-ia'),
                'url' => home_url('/mi-portal/{modulo}/'),
            ],
        ];
    }
}
```

---

## Orden de Implementación

### Fase 1: Correcciones Críticas ✅ COMPLETADA
1. [x] Corregir routing de páginas dinámicas
2. [x] Corregir comunidades completo
3. [x] Limpiar duplicados en loader (verificado: sin duplicados)
4. [x] Añadir should_load_assets a 41 módulos

### Fase 2: Dashboard Tabs Faltantes ✅ COMPLETADA
1. [x] red-social
2. [x] chat-grupos
3. [x] chat-interno
4. [x] empresarial
5. [x] clientes
6. [x] facturas
7. [x] fichaje-empleados
8. [x] advertising
9. [x] sello-conciencia
10. [x] dex-solana
11. [x] trading-ia
12. [x] woocommerce

### Fase 3: Widgets Dashboard ✅ COMPLETADA
1. [x] eventos
2. [x] marketplace
3. [x] comunidades
4. [x] banco-tiempo
5. [x] socios
6. [x] foros
7. [x] incidencias
8. [x] reservas

### Fase 4: Verificación
1. [ ] Ejecutar diagnóstico completo
2. [ ] Verificar cada ruta de mi-portal
3. [ ] Comprobar carga de assets
4. [ ] Validar shortcodes en uso
5. [ ] Test de widgets en dashboard

---

## Scripts de Verificación

### Verificar Módulo Individual

```bash
wp eval '
$module = "comunidades";
$path = FLAVOR_CHAT_IA_PATH . "includes/modules/{$module}/";

echo "=== Verificando {$module} ===\n";
echo "Clase principal: " . (file_exists($path . "class-{$module}-module.php") ? "OK" : "FALTA") . "\n";
echo "Dashboard tab: " . (file_exists($path . "class-{$module}-dashboard-tab.php") ? "OK" : "FALTA") . "\n";
echo "Views: " . (is_dir($path . "views/") ? "OK" : "FALTA") . "\n";
echo "Install: " . (file_exists($path . "install.php") ? "OK" : "N/A") . "\n";
'
```

### Verificar Todos los Módulos

```bash
wp eval-file wp-content/plugins/flavor-chat-ia/diagnostico-completo-modulos.php
```

---

## Notas Importantes

1. **No hardcodear datos**: Todos los datos deben venir de la BD
2. **Usar sistema de componentes**: `flavor_render_component()`
3. **Seguir patrón singleton**: Para todas las clases
4. **Registrar en loader**: Añadir al mapeo de dashboard tabs
5. **Documentar**: Actualizar docs/modulos/{modulo}.md

---

---

## Próximos Pasos

### Mantenimiento
- Revisar periódicamente módulos nuevos
- Verificar consistencia de patrones
- Actualizar documentación de módulos individuales

### Mejoras Futuras
- Optimizar queries de widgets
- Añadir cache a datos de dashboard
- Implementar lazy loading de tabs

---

*Última actualización: 2026-02-28 (Fase 1-3 completadas)*
