# ARQUITECTURA DE MÓDULOS - FLAVOR CHAT IA

> Documento estructural de referencia tecnica.
> Sigue siendo util para entender patrones del sistema, pero no debe leerse como fuente primaria de estado real o madurez modulo a modulo.
> Para estado vigente, usa `ESTADO-REAL-PLUGIN.md` y `../reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`.

**Fecha:** 2026-02-26
**Estado:** DOCUMENTO OFICIAL

---

## RESUMEN

El plugin tiene **2 sistemas principales** que NO deben confundirse:

| Sistema | Propósito | Archivo clave |
|---------|-----------|---------------|
| **Archive Renderer** | Renderizar LISTADOS/archives | `class-archive-renderer.php` |
| **Módulos** | Lógica de negocio + acciones IA | `PLANTILLA_MODULO.php` |

---

## 1. ESTRUCTURA DE UN MÓDULO

Ubicación: `includes/modules/{nombre-modulo}/`

```
includes/modules/incidencias/
├── class-incidencias-module.php    # Clase principal
├── install.php                      # Creación de tablas (opcional)
├── assets/                          # CSS/JS del módulo
│   ├── css/
│   └── js/
├── frontend/                        # Controlador frontend
│   └── class-incidencias-frontend-controller.php
├── templates/                       # Plantillas específicas
│   └── ...
└── views/                           # Vistas admin
    └── dashboard.php
```

---

## 2. CLASE DE MÓDULO

Basada en `PLANTILLA_MODULO.php`:

```php
class Flavor_Chat_Incidencias_Module extends Flavor_Chat_Module_Base {

    // TRAITS DISPONIBLES (usar solo los necesarios)
    use Flavor_Module_Admin_Pages_Trait;     // Páginas admin
    use Flavor_Module_Notifications_Trait;   // Notificaciones
    use Flavor_Module_Integration_Consumer;  // Integraciones

    public function __construct() {
        $this->id = 'incidencias';
        $this->name = __('Incidencias', 'flavor-platform');
        $this->description = __('Reportar problemas', 'flavor-platform');
        parent::__construct();
    }

    // Métodos requeridos
    public function can_activate() { return true; }
    public function get_activation_error() { return ''; }
    public function init() { /* hooks y shortcodes */ }

    // Métodos para IA
    public function get_actions() { /* acciones disponibles */ }
    public function execute_action($action, $params) { /* ejecutar acción */ }
    public function get_tool_definitions() { /* herramientas Claude */ }
}
```

---

## 3. ARCHIVE RENDERER (PLANTILLAS DINÁMICAS)

**Para renderizar LISTADOS de cualquier módulo con UNA sola llamada.**

### Uso:

```php
// En templates/frontend/{modulo}/archive.php

$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'incidencias',
    'title'        => __('Incidencias', 'flavor-platform'),
    'subtitle'     => __('Reporta problemas', 'flavor-platform'),
    'icon'         => '⚠️',
    'color'        => 'red',
    'items'        => $incidencias,          // Array de datos
    'total'        => $total,
    'per_page'     => 10,
    'current_page' => 1,
    'stats'        => [
        ['value' => 5, 'label' => 'Pendientes', 'icon' => '🔴', 'color' => 'red'],
        ['value' => 3, 'label' => 'En curso', 'icon' => '🟡', 'color' => 'yellow'],
    ],
    'filters'      => [
        ['id' => 'todos', 'label' => 'Todas', 'active' => true],
        ['id' => 'pendiente', 'label' => 'Pendientes'],
    ],
    'cta_text'     => __('Nueva incidencia', 'flavor-platform'),
    'cta_action'   => 'flavorIncidencias.nueva()',
    'empty_state'  => [
        'icon'  => '✅',
        'title' => __('No hay incidencias', 'flavor-platform'),
    ],
]);
```

### Componentes disponibles en `/templates/components/shared/`:

| Componente | Descripción |
|------------|-------------|
| `archive-header.php` | Header con título, icono, CTA |
| `stats-grid.php` | Grid de estadísticas/KPIs |
| `filter-pills.php` | Botones de filtro |
| `items-grid.php` | Grid de cards |
| `item-card.php` | Card individual |
| `pagination.php` | Paginación |
| `empty-state.php` | Estado vacío |
| `form-builder.php` | Constructor de formularios |
| `_functions.php` | Helpers compartidos |

---

## 4. TRAITS DISPONIBLES

| Trait | Propósito | Cuándo usarlo |
|-------|-----------|---------------|
| `Flavor_Module_Admin_Pages_Trait` | Páginas en admin | Siempre |
| `Flavor_Module_Notifications_Trait` | Sistema de notificaciones | Si envía notificaciones |
| `Flavor_Module_Integration_Consumer` | Integraciones entre módulos | Si consume datos de otros módulos |
| `Flavor_Module_Dashboard_Tabs_Trait` | Tabs en dashboard | Si tiene dashboard |
| `Flavor_Module_Frontend_Actions` | Acciones frontend/formularios | Si tiene formularios |
| `Flavor_Encuestas_Features` | Encuestas integradas | Si permite encuestas |
| `Flavor_WhatsApp_Features` | Integración WhatsApp | Si usa WhatsApp |

---

## 5. MÓDULOS DE REFERENCIA (BIEN IMPLEMENTADOS)

Usar estos como ejemplo:

| Módulo | Archivo | Por qué es buen ejemplo |
|--------|---------|------------------------|
| `incidencias` | `class-incidencias-module.php` | Usa Archive Renderer, notificaciones, completo |
| `eventos` | `class-eventos-module.php` | Calendario, inscripciones, integraciones |
| `grupos-consumo` | `class-grupos-consumo-module.php` | Complejo, bien estructurado |

---

## 6. FLUJO DE DATOS

```
Usuario → Frontend Controller → Módulo → Base de datos
                ↓
         Archive Renderer
                ↓
         Componentes Shared
                ↓
              HTML
```

---

## 7. CHECKLIST PARA CREAR/MODIFICAR MÓDULOS

### Crear nuevo módulo:
- [ ] Copiar `PLANTILLA_MODULO.php`
- [ ] Registrar en `class-module-loader.php`
- [ ] Añadir a perfil en `class-app-profiles.php`
- [ ] Crear `install.php` si necesita tablas
- [ ] Crear `archive.php` usando Archive Renderer

### Modificar módulo existente:
- [ ] NO modificar la clase del módulo para cambiar UI
- [ ] Modificar solo `templates/frontend/{modulo}/archive.php`
- [ ] Usar componentes shared existentes
- [ ] NO crear código duplicado

---

## 8. LO QUE NO HAY QUE HACER

❌ Modificar 60 módulos individualmente para cambiar UI
❌ Duplicar código entre módulos
❌ Crear nuevos traits sin documentarlos
❌ Mezclar lógica de negocio con renderizado
❌ Ignorar los componentes shared existentes

---

## 9. ESTADO ACTUAL

### Módulos usando Archive Renderer (20):
- incidencias ✅
- carpooling ✅
- banco-tiempo ✅
- avisos-municipales ✅
- ayuda-vecinal ✅
- comunidades ✅
- colectivos ✅
- grupos-consumo ✅
- huertos-urbanos ✅
- bares ✅
- reservas ✅
- foros ✅
- parkings ✅
- talleres ✅
- tramites ✅
- participacion ✅
- espacios-comunes ✅
- presupuestos-participativos ✅
- radio ✅
- multimedia ✅

### Módulos pendientes de migrar:
(Consultar con `git status` o revisar `/templates/frontend/`)

---

## 10. PRÓXIMOS PASOS

1. Verificar qué módulos NO usan Archive Renderer
2. Migrar módulos restantes UNO POR UNO
3. NO hacer cambios masivos en 60 archivos
4. Probar cada módulo después de migrar
