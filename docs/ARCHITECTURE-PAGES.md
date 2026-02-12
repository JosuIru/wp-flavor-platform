# Arquitectura de Páginas - Auditoría y Propuesta de Mejora

## 🔍 Análisis del Sistema Actual

### Problemas Identificados

1. **Sin Centro de Navegación Claro**
   - No hay un dashboard central unificado
   - Cada módulo crea sus propias páginas independientes
   - Usuario no sabe dónde empezar

2. **Páginas Redundantes**
   - "Mi Cuenta" separado de "Mi Portal"
   - Múltiples dashboards (uno por módulo)
   - Sin jerarquía lógica

3. **Limitaciones de Container**
   - Tema aplica max-w-4xl a todo el contenido
   - Componentes no respetan configuración de diseño
   - Stats y grids no usan espacio disponible

4. **Control de Acceso Confuso**
   - No está claro qué páginas requieren login
   - Sin diferenciación público/privado
   - Navegación no adapta a permisos

---

## 🎯 Propuesta: Arquitectura Centrada en Dashboard

### Concepto Principal

**Dashboard como Hub Central** → Todo parte de ahí, todo regresa ahí.

```
┌─────────────────────────────────────────┐
│          HOME (Público)                 │
│  - Hero                                 │
│  - Servicios disponibles              │
│  - CTA: Acceder a Mi Portal           │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│       MI PORTAL (Dashboard)             │
│  ┌─────────────────────────────────┐   │
│  │  Stats Cards (Tarjetas)         │   │
│  │  - Un card por módulo activo    │   │
│  │  - Valor principal + secundarios│   │
│  │  │  - Link "Ver →" al módulo      │   │
│  └─────────────────────────────────┘   │
│  ┌─────────────────────────────────┐   │
│  │  Accesos Rápidos                │   │
│  │  - Crear Taller                 │   │
│  │  - Solicitar Ayuda              │   │
│  │  - Nuevo Evento                 │   │
│  └─────────────────────────────────┘   │
│  ┌─────────────────────────────────┐   │
│  │  Actividad Reciente             │   │
│  │  - Timeline de acciones         │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
       ↓          ↓          ↓
   [Módulos]  [Módulos]  [Módulos]
```

---

## 📁 Nueva Estructura de Páginas

### Nivel 1: Páginas Principales

#### 1.1 Home (Público)
- **URL**: `/`
- **Slug**: `home` (front page)
- **Contenido**: `[flavor_servicios]`
- **Acceso**: Público
- **Propósito**: Landing principal que presenta los servicios

#### 1.2 Mi Portal (Dashboard)
- **URL**: `/mi-portal/`
- **Slug**: `mi-portal`
- **Contenido**: `[flavor_mi_portal]` + `[flavor_dashboard_stats]`
- **Acceso**: Requiere login
- **Propósito**: Hub central del usuario
- **Características**:
  - Stats cards de todos los módulos
  - Accesos rápidos personalizados
  - Actividad reciente
  - Notificaciones

#### 1.3 Servicios (Explorar)
- **URL**: `/servicios/`
- **Slug**: `servicios`
- **Contenido**: `[flavor_servicios mostrar_stats="yes"]`
- **Acceso**: Público
- **Propósito**: Catálogo de todos los módulos disponibles

---

### Nivel 2: Páginas de Módulo

Cada módulo tiene su propia sección con estructura estandarizada:

```
/{modulo}/              → Landing/Grid del módulo
/{modulo}/mis-{modulo}/ → Dashboard personal
/{modulo}/crear/        → Formulario de creación
/{modulo}/buscar/       → Búsqueda/Filtros
```

#### Ejemplo: Eventos

```
/eventos/                → [flavor_eventos] (Grid de eventos)
/eventos/mis-eventos/    → Dashboard personal de eventos
/eventos/crear/          → [flavor_module_form module="eventos" action="crear_evento"]
/eventos/inscribirse/    → [flavor_module_form module="eventos" action="inscribirse_evento"]
```

#### Ejemplo: Talleres

```
/talleres/               → [flavor_talleres] (Grid de talleres)
/talleres/mis-talleres/  → Dashboard personal
/talleres/crear/         → [flavor_module_form module="talleres" action="proponer_taller"]
/talleres/inscribirse/   → [flavor_module_form module="talleres" action="inscribirse"]
```

---

## 🗂️ Jerarquía de Navegación

### Menú Principal (Header)

**Para usuarios NO logueados:**
```
- Home
- Servicios
- Sobre Nosotros
- Contacto
→ Acceder | Registrarse
```

**Para usuarios logueados:**
```
- Mi Portal (Dashboard)
- Servicios
- Notificaciones (con badge)
- Avatar → Dropdown:
  - Mi Perfil
  - Configuración
  - Cerrar Sesión
```

### Breadcrumbs

Todas las páginas internas muestran su ubicación:

```
Mi Portal > Eventos > Inscribirse
Mi Portal > Talleres > Mis Talleres
Mi Portal > Grupos de Consumo > Mi Grupo
```

---

## 🔐 Control de Acceso

### Niveles de Acceso

#### 1. Público (No login)
- Home
- Servicios (listado)
- Landings de módulos (vista previa)
- Páginas informativas

#### 2. Usuario Registrado
- Mi Portal (Dashboard)
- "Mis..." de cada módulo
- Crear/Editar contenido
- Ver detalles completos

#### 3. Rol Específico
- Algunas acciones requieren roles
- Ejemplo: "Crear Evento" puede requerir rol "Organizador"
- Se verifica con `Flavor_Module_Access_Control`

### Redirecciones

```php
// Si usuario no logueado intenta acceder a página privada
if (!is_user_logged_in() && is_page_private()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Después de login, ir a Mi Portal
add_filter('login_redirect', function($redirect, $request, $user) {
    return home_url('/mi-portal/');
}, 10, 3);
```

---

## 📋 Plan de Implementación

### Fase 1: Limpieza (Inmediato)
✓ CSS override para containers → **HECHO**
✓ Auto-asignar templates full-width → **HECHO**
- [ ] Eliminar páginas duplicadas
- [ ] Consolidar dashboards

### Fase 2: Dashboard Central (1-2 días)
- [ ] Mejorar `[flavor_mi_portal]` como hub central
- [ ] Añadir navegación consistente
- [ ] Implementar breadcrumbs
- [ ] Sistema de notificaciones en dashboard

### Fase 3: Estandarización Módulos (2-3 días)
- [ ] Template consistente para landings de módulo
- [ ] Páginas "Mis..." estandarizadas
- [ ] Formularios con diseño uniforme
- [ ] Navegación contextual por módulo

### Fase 4: Control de Acceso (1 día)
- [ ] Middleware para verificar login
- [ ] Redirecciones automáticas
- [ ] Mensajes de permiso
- [ ] Roles por módulo

### Fase 5: UX/UI (1-2 días)
- [ ] Menú adaptativo según login
- [ ] Avatar y dropdown de usuario
- [ ] Indicadores visuales de progreso
- [ ] Animaciones de transición

---

## 🎨 Componentes Reutilizables

### PageHeader Component
```php
[flavor_page_header 
    title="Mi Portal" 
    subtitle="Tu centro de control comunitario"
    breadcrumbs="yes"
    background="gradient"]
```

### ModuleNav Component
```php
[flavor_module_nav 
    module="eventos"
    current="crear"
    items="listado,mis-eventos,crear,buscar"]
```

### StatsOverview Component
```php
[flavor_stats_overview 
    modules="eventos,talleres,banco_tiempo"
    layout="cards"
    columns="4"]
```

---

## 🔧 Código para Implementar

### 1. Dashboard Mejorado

```php
// includes/class-portal-shortcodes.php
public function render_mi_portal($atts) {
    if (!is_user_logged_in()) {
        return $this->render_login_required();
    }
    
    ob_start();
    ?>
    <div class="flavor-portal-hub">
        <!-- Hero con saludo -->
        <div class="flavor-portal__hero">
            <h1>Bienvenido/a, <?php echo esc_html(wp_get_current_user()->display_name); ?></h1>
            <p>Tu centro de control comunitario</p>
        </div>
        
        <!-- Stats Dashboard -->
        <?php echo $this->render_dashboard_stats(['columnas' => '4']); ?>
        
        <!-- Quick Actions -->
        <div class="flavor-quick-actions">
            <h2>Accesos Rápidos</h2>
            <?php echo $this->render_quick_actions(); ?>
        </div>
        
        <!-- Activity Feed -->
        <div class="flavor-activity-feed">
            <h2>Actividad Reciente</h2>
            <?php echo $this->render_activity_feed(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

### 2. Breadcrumbs

```php
// includes/class-breadcrumbs.php
class Flavor_Breadcrumbs {
    public static function render() {
        if (is_front_page()) return '';
        
        $crumbs = [];
        $crumbs[] = ['url' => home_url('/mi-portal/'), 'title' => 'Mi Portal'];
        
        if (is_page()) {
            $ancestors = get_post_ancestors(get_the_ID());
            foreach (array_reverse($ancestors) as $ancestor) {
                $crumbs[] = [
                    'url' => get_permalink($ancestor),
                    'title' => get_the_title($ancestor)
                ];
            }
        }
        
        $crumbs[] = ['url' => '', 'title' => get_the_title()];
        
        return self::render_crumbs($crumbs);
    }
}
```

### 3. Access Control Middleware

```php
// includes/class-page-access-control.php
class Flavor_Page_Access_Control {
    public function __construct() {
        add_action('template_redirect', [$this, 'check_page_access']);
    }
    
    public function check_page_access() {
        if (!is_page()) return;
        
        $page_id = get_the_ID();
        $requires_login = get_post_meta($page_id, '_flavor_requires_login', true);
        
        if ($requires_login && !is_user_logged_in()) {
            auth_redirect();
        }
        
        // Check module-specific permissions
        $module_id = get_post_meta($page_id, '_flavor_page_module', true);
        if ($module_id && class_exists('Flavor_Module_Access_Control')) {
            $control = Flavor_Module_Access_Control::get_instance();
            if (!$control->user_can_access($module_id)) {
                wp_die(__('No tienes permisos para acceder a esta página', 'flavor-chat-ia'));
            }
        }
    }
}
```

---

## 📊 Métricas de Éxito

### Antes
- ❌ Usuario confundido sobre dónde ir
- ❌ Dashboard escondido o inexistente
- ❌ Páginas con ancho limitado (896px)
- ❌ Sin navegación clara entre módulos

### Después
- ✅ Dashboard como hub central obvio
- ✅ Navegación intuitiva desde dashboard
- ✅ Contenido usando espacio completo
- ✅ Breadcrumbs y navegación contextual
- ✅ Control de acceso transparente

---

## 🚀 Próximos Pasos

1. **Revisar este documento** y dar feedback
2. **Priorizar fases** según necesidades
3. **Implementar Fase 1** (limpieza)
4. **Testear con usuarios** reales
5. **Iterar** según feedback

¿Empezamos con la implementación?
