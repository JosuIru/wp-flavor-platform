# Plan de Mejoras: Subvistas y Badges en Flavor Shell

**Fecha**: 2026-03-05
**Estado**: IMPLEMENTADO
**Prioridad**: Alta (mejora UX significativa)

---

## Objetivo

Mejorar el Flavor Shell para:
1. **Mostrar subvistas** cuando el usuario está en un dashboard de módulo
2. **Añadir badges** con contadores de acciones pendientes/notificaciones

---

## Análisis del Estado Actual

### Estructura de Dashboards de Módulos

Cada módulo ya define un array `$quick_actions` con:

```php
$quick_actions = [
    [
        'label' => __('Próximos eventos', 'flavor-chat-ia'),
        'href' => admin_url('admin.php?page=eventos-proximos'),
        'icon' => 'dashicons-calendar',
        'tone' => 'warning',
        'description' => __('Abrir agenda operativa', 'flavor-chat-ia'),
        'badge' => $eventos_semana,  // <-- CONTADOR
    ],
    // ...
];
```

### Ejemplo: Módulo Eventos

| Subpágina | Slug | Badge |
|-----------|------|-------|
| Dashboard | `eventos-dashboard` | - |
| Próximos | `eventos-proximos` | `$eventos_semana` |
| Calendario | `eventos-calendario` | - |
| Asistentes | `eventos-asistentes` | `$inscripciones_pendientes` |
| Nuevo | `eventos-nuevo` | - |
| Config | `eventos-config` | - |

### Ejemplo: Módulo Trámites

| Subpágina | Slug | Badge |
|-----------|------|-------|
| Dashboard | `tramites-dashboard` | - |
| Pendientes | `tramites-pendientes` | `$solicitudes_pendientes` |
| Historial | `tramites-historial` | - |
| Tipos | `tramites-tipos` | - |
| Config | `tramites-config` | - |

### Ejemplo: Módulo Incidencias

| Subpágina | Slug | Badge |
|-----------|------|-------|
| Dashboard | `incidencias-dashboard` | - |
| Abiertas | `incidencias-abiertas` | `$incidencias_abiertas` |
| Todas | `incidencias-todas` | - |
| Mapa | `incidencias-mapa` | - |
| Config | `incidencias-config` | - |

---

## Diseño de la Solución

### 1. Nuevo Sistema de Registro de Subvistas

Crear un sistema centralizado donde cada módulo registre sus subvistas y badges:

```php
// Nuevo archivo: includes/class-shell-navigation-registry.php

class Flavor_Shell_Navigation_Registry {

    private static $instance = null;
    private $module_subpages = [];
    private $badge_callbacks = [];

    /**
     * Registrar subpáginas de un módulo
     */
    public function register_module_subpages($module_slug, $subpages) {
        $this->module_subpages[$module_slug] = $subpages;
    }

    /**
     * Registrar callback para obtener badge de un item
     */
    public function register_badge_callback($page_slug, $callback) {
        $this->badge_callbacks[$page_slug] = $callback;
    }

    /**
     * Obtener subpáginas de un módulo
     */
    public function get_module_subpages($module_slug) {
        return $this->module_subpages[$module_slug] ?? [];
    }

    /**
     * Obtener badge para una página
     */
    public function get_badge($page_slug) {
        if (isset($this->badge_callbacks[$page_slug])) {
            return call_user_func($this->badge_callbacks[$page_slug]);
        }
        return null;
    }

    /**
     * Obtener todos los badges (para el shell)
     */
    public function get_all_badges() {
        $badges = [];
        foreach ($this->badge_callbacks as $slug => $callback) {
            $count = call_user_func($callback);
            if ($count > 0) {
                $badges[$slug] = $count;
            }
        }
        return $badges;
    }
}
```

### 2. Registro desde cada Módulo

Cada módulo registra sus subpáginas en su inicialización:

```php
// En class-eventos-module.php

public function register_shell_navigation() {
    $registry = Flavor_Shell_Navigation_Registry::get_instance();

    // Registrar subpáginas
    $registry->register_module_subpages('eventos-dashboard', [
        [
            'slug' => 'eventos-proximos',
            'label' => __('Próximos', 'flavor-chat-ia'),
            'icon' => 'dashicons-calendar-alt',
        ],
        [
            'slug' => 'eventos-calendario',
            'label' => __('Calendario', 'flavor-chat-ia'),
            'icon' => 'dashicons-calendar',
        ],
        [
            'slug' => 'eventos-asistentes',
            'label' => __('Asistentes', 'flavor-chat-ia'),
            'icon' => 'dashicons-groups',
        ],
        [
            'slug' => 'eventos-config',
            'label' => __('Configuración', 'flavor-chat-ia'),
            'icon' => 'dashicons-admin-settings',
        ],
    ]);

    // Registrar badges
    $registry->register_badge_callback('eventos-dashboard', [$this, 'get_eventos_semana_count']);
    $registry->register_badge_callback('eventos-asistentes', [$this, 'get_inscripciones_pendientes']);
}

public function get_eventos_semana_count() {
    global $wpdb;
    return (int) $wpdb->get_var("...");
}

public function get_inscripciones_pendientes() {
    global $wpdb;
    return (int) $wpdb->get_var("...");
}
```

### 3. Modificaciones al Shell

#### 3.1. Obtener Subpáginas en `get_navigation_structure()`

```php
// En class-admin-shell.php

public function get_navigation_structure() {
    $estructura_completa = [...]; // estructura actual

    // Obtener página actual
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    // Determinar si estamos en un dashboard de módulo
    $parent_dashboard = $this->get_parent_dashboard($current_page);

    // Obtener registry
    $registry = Flavor_Shell_Navigation_Registry::get_instance();

    // Si estamos en un dashboard, obtener subpáginas
    if ($parent_dashboard) {
        $subpages = $registry->get_module_subpages($parent_dashboard);
        // Inyectar subpáginas en la estructura
        $estructura_completa = $this->inject_subpages($estructura_completa, $parent_dashboard, $subpages);
    }

    // Obtener badges
    $badges = $registry->get_all_badges();

    // Inyectar badges en la estructura
    $estructura_completa = $this->inject_badges($estructura_completa, $badges);

    return $estructura_completa;
}
```

#### 3.2. Nuevo Template para Subpáginas

```php
// En shell-sidebar.php

<?php foreach ($section['items'] as $item) : ?>
    <li class="fls-shell__menu-item">
        <a href="..." class="fls-shell__menu-link <?php echo $is_active ? 'fls-shell__menu-link--active' : ''; ?>">
            <span class="fls-shell__menu-icon">...</span>
            <span class="fls-shell__menu-text"><?php echo $item['label']; ?></span>

            <!-- Badge -->
            <?php if (!empty($item['badge']) && $item['badge'] > 0) : ?>
                <span class="fls-shell__menu-badge"><?php echo number_format_i18n($item['badge']); ?></span>
            <?php endif; ?>
        </a>

        <!-- Subpáginas (si existen y el item está activo) -->
        <?php if (!empty($item['subpages']) && $is_in_module) : ?>
            <ul class="fls-shell__submenu">
                <?php foreach ($item['subpages'] as $subpage) : ?>
                    <li class="fls-shell__submenu-item">
                        <a href="<?php echo admin_url('admin.php?page=' . $subpage['slug']); ?>"
                           class="fls-shell__submenu-link <?php echo $subpage_active ? 'fls-shell__submenu-link--active' : ''; ?>">
                            <span class="fls-shell__submenu-icon">
                                <span class="dashicons <?php echo $subpage['icon']; ?>"></span>
                            </span>
                            <span class="fls-shell__submenu-text"><?php echo $subpage['label']; ?></span>
                            <?php if (!empty($subpage['badge'])) : ?>
                                <span class="fls-shell__submenu-badge"><?php echo $subpage['badge']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
```

### 4. Estilos CSS para Badges y Submenús

```css
/* Badges en items principales */
.fls-shell__menu-badge {
    margin-left: auto;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: var(--fls-shell-accent, #3b82f6);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.fls-shell__menu-badge--warning {
    background: #f59e0b;
}

.fls-shell__menu-badge--danger {
    background: #ef4444;
}

/* Submenú */
.fls-shell__submenu {
    list-style: none;
    margin: 0;
    padding: 4px 0 8px 28px;
    border-left: 2px solid var(--fls-shell-border);
    margin-left: 16px;
}

.fls-shell__submenu-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    color: var(--fls-shell-text-muted);
    text-decoration: none;
    font-size: 13px;
    border-radius: 6px;
    transition: all 150ms ease;
}

.fls-shell__submenu-link:hover {
    background: var(--fls-shell-bg-hover);
    color: var(--fls-shell-text);
}

.fls-shell__submenu-link--active {
    background: var(--fls-shell-accent-light);
    color: var(--fls-shell-accent);
    font-weight: 500;
}

.fls-shell__submenu-icon {
    font-size: 14px;
    width: 14px;
    height: 14px;
    opacity: 0.7;
}

.fls-shell__submenu-badge {
    margin-left: auto;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: var(--fls-shell-accent);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Modo colapsado - ocultar submenús */
.fls-shell--collapsed .fls-shell__submenu {
    display: none;
}

/* Animación de expansión */
.fls-shell__submenu {
    animation: slideDown 200ms ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

---

## Implementación por Fases

### Fase 1: Sistema de Registro (Core)

1. Crear `class-shell-navigation-registry.php`
2. Añadir hook `flavor_shell_register_navigation`
3. Integrar con `class-admin-shell.php`

**Archivos a modificar/crear**:
- `includes/class-shell-navigation-registry.php` (nuevo)
- `admin/class-admin-shell.php` (modificar)

### Fase 2: Registro desde Módulos Principales

Implementar registro en los 10 módulos más usados:
1. Eventos
2. Trámites
3. Incidencias
4. Socios
5. Marketplace
6. Reservas
7. Foros
8. Participación
9. Huertos
10. Comunidades

**Archivos a modificar**:
- `includes/modules/eventos/class-eventos-module.php`
- `includes/modules/tramites/class-tramites-module.php`
- etc.

### Fase 3: UI del Shell

1. Modificar template `shell-sidebar.php`
2. Añadir estilos CSS para badges y submenús
3. Actualizar JavaScript si es necesario

**Archivos a modificar**:
- `admin/views/shell-sidebar.php`
- `admin/css/admin-shell.css`
- `admin/js/admin-shell.js` (si necesario)

### Fase 4: Badges Dinámicos (AJAX)

Para evitar cargar todos los badges en cada página:
1. Cargar badges críticos en PHP
2. Cargar badges secundarios via AJAX
3. Actualizar badges cada 5 minutos

---

## Mapa de Badges por Módulo

| Módulo | Item | Badge | Descripción |
|--------|------|-------|-------------|
| Eventos | eventos-dashboard | `$eventos_semana` | Eventos esta semana |
| Eventos | eventos-asistentes | `$inscripciones_pendientes` | Inscripciones pendientes |
| Trámites | tramites-dashboard | `$solicitudes_pendientes` | Solicitudes en curso |
| Trámites | tramites-pendientes | `$solicitudes_urgentes` | Urgentes sin cerrar |
| Incidencias | incidencias-dashboard | `$incidencias_abiertas` | Incidencias abiertas |
| Socios | socios-dashboard | `$solicitudes_alta` | Solicitudes de alta |
| Marketplace | marketplace-dashboard | `$anuncios_pendientes` | Anuncios por aprobar |
| Foros | foros-dashboard | `$temas_sin_respuesta` | Temas sin respuesta |
| Chat IA | flavor-chat-config | `$escalados_pendientes` | Escalados sin atender |

---

## Mockup Visual

```
┌────────────────────────────────┐
│ 🦸 Flavor                    ◀ │
├────────────────────────────────┤
│ 👤 Admin               ▼       │
├────────────────────────────────┤
│                                │
│ MI APP                         │
│  📊 Dashboard                  │
│  📐 Widgets                    │
│  ⬚ Módulos                     │
│                                │
│ ACTIVIDADES                    │
│  📅 Eventos              (3)   │  <-- Badge: 3 eventos esta semana
│     ├─ 📆 Próximos             │  <-- Subvistas expandidas
│     ├─ 🗓️ Calendario           │
│     ├─ 👥 Asistentes     (5)   │  <-- Badge: 5 inscripciones pendientes
│     └─ ⚙️ Configuración        │
│  📚 Cursos                     │
│  🔨 Talleres                   │
│                                │
│ SERVICIOS                      │
│  📋 Trámites             (12)  │  <-- Badge: 12 pendientes
│  ⚠️ Incidencias          (8)   │  <-- Badge: 8 abiertas
│                                │
└────────────────────────────────┘
```

---

## Consideraciones de Performance

1. **Cacheo de badges**: Almacenar en transients con expiración de 5 minutos
2. **Lazy loading**: Cargar badges secundarios via AJAX
3. **Badges críticos primero**: Priorizar módulos activos del usuario

---

## Compatibilidad

- WordPress 5.8+
- PHP 7.4+
- Alpine.js 3.x (ya incluido)
- Sin dependencias adicionales

---

## Estimación de Trabajo

| Fase | Componentes | Complejidad |
|------|-------------|-------------|
| Fase 1 | Registry Core | Media |
| Fase 2 | Registro módulos | Baja (repetitivo) |
| Fase 3 | UI Shell | Media |
| Fase 4 | AJAX badges | Media |

---

## Próximos Pasos

1. **Aprobar diseño** - Revisar mockups y flujo
2. **Fase 1** - Crear sistema de registro
3. **Fase 3** - UI básica (puede hacerse en paralelo)
4. **Fase 2** - Integrar módulos uno a uno
5. **Fase 4** - Optimización con AJAX

---

## Preguntas Abiertas

1. ~~¿Mostrar subvistas solo cuando estamos en el módulo, o siempre expandidas?~~ **DECIDIDO: Solo cuando estamos en el módulo**
2. ~~¿Umbral de badges? (ej: mostrar "99+" si hay más de 99)~~ **IMPLEMENTADO: Sí, "99+"**
3. ~~¿Colores diferentes para badges según severidad?~~ **IMPLEMENTADO: info (azul), warning (naranja), danger (rojo)**
4. ¿Sonido/animación cuando aparece un nuevo badge? - Pendiente

---

## IMPLEMENTACIÓN COMPLETADA

### Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `includes/class-shell-navigation-registry.php` | Sistema central de registro de subpáginas y badges |
| `includes/class-shell-module-registrations.php` | Registro de navegación de todos los módulos |

### Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `flavor-chat-ia.php` | Carga de los nuevos archivos del registry |
| `admin/class-admin-shell.php` | Integración del registry, detección de módulo activo |
| `admin/views/shell-sidebar.php` | UI para submenús y badges |
| `admin/css/admin-shell.css` | Estilos para badges y submenús |

### Módulos con Subpáginas Registradas

| Módulo | Subpáginas | Badges |
|--------|------------|--------|
| Eventos | Próximos, Calendario, Asistentes, Nuevo, Config | eventos_semana, inscripciones_pendientes |
| Trámites | Pendientes, Historial, Tipos, Config | solicitudes_pendientes, urgentes |
| Incidencias | Abiertas, Todas, Mapa, Config | incidencias_abiertas, sin_asignar |
| Socios | Listado, Solicitudes, Cuotas, Config | solicitudes_pendientes |
| Marketplace | Anuncios, Vendedores, Ventas, Config | anuncios_pendientes |
| Reservas | Calendario, Espacios, Pendientes, Config | reservas_pendientes |
| Foros | Temas, Categorías, Moderación, Config | temas_sin_respuesta |
| Participación | Propuestas, Votaciones, Config | propuestas_en_votacion |
| Huertos | Parcelas, Huertanos, Cosechas, Recursos, Config | solicitudes_parcela |
| Comunidades | Listado, Miembros, Config | - |
| Colectivos | Listado, Solicitudes, Config | solicitudes_union |
| Banco de Tiempo | Servicios, Intercambios, Miembros, Config | intercambios_pendientes |
| Biblioteca | Catálogo, Préstamos, Reservas, Config | prestamos_vencidos |
| Cursos | Listado, Inscripciones, Config | - |
| Talleres | Listado, Inscripciones, Materiales, Config | - |
| Radio | Programas, Locutores, Config | - |
| Podcast | Episodios, Series, Config | - |
| Campañas | Listado, Estadísticas, Config | - |
| Chat IA | Escalados, Analíticas | escalados_pendientes |

### Severidades de Badges

- **info** (azul): Información general, contadores normales
- **warning** (naranja): Requiere atención pronto
- **danger** (rojo): Urgente, acción requerida inmediatamente
