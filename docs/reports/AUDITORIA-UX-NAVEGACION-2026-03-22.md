# AUDITORÍA UX DE NAVEGACIÓN - FLAVOR PLATFORM

**Fecha:** 2026-03-22
**Objetivo:** Identificar y corregir URLs dispersas que rompen el flujo canónico `/mi-portal/MODULO/ACCION/`
**Scope:** Módulos prioritarios (mi-portal, grupos-consumo, colectivos, tramites, socios, eventos)

---

## RESUMEN EJECUTIVO

### Problema Global

El sistema tiene **URLs hardcodeadas dispersas** que no siguen el patrón canónico `/mi-portal/{modulo}/{accion}/`. Esto genera:

1. **CTAs que sacan al usuario del flujo correcto** (`/mis-pedidos` vs `/mi-portal/grupos-consumo/pedidos/`)
2. **Navegación inconsistente** (mezcla `/tramites/` y `/mi-portal/tramites/`)
3. **Mantenibilidad baja** (cambiar una URL requiere buscar en múltiples archivos)
4. **Desorientación del usuario** (no sabe si está en "su portal" o en páginas genéricas)

### Helper Disponible (NO utilizado)

```php
Flavor_Chat_Helpers::get_action_url('modulo', 'accion')
// Genera: /mi-portal/modulo/accion/
```

**Uso actual:** 10-40% en mejores módulos, 0% en peores.

---

## MATRIZ DE AUDITORÍA POR MÓDULO

### Tabla Resumen

| Módulo | Helper Usage | Hardcoded URLs | URLs fuera de /mi-portal/ | Estado | Prioridad |
|--------|--------------|----------------|--------------------------|--------|-----------|
| **mi-portal** | 10% | 90% | ✅ **SÍ** (múltiples) | ❌ Crítico | **P0** |
| **tramites** | 0% | 100% | ✅ **SÍ** (algunos) | ❌ Crítico | **P0** |
| **socios** | 0% | 100% | ✅ **SÍ** (/validar-socio/) | ❌ Crítico | **P0** |
| **grupos-consumo** | 40% | 60% | ❌ NO | ⚠️ Parcial | **P1** |
| **colectivos** | 60% | 40% | ❌ NO | ⚠️ Parcial | **P2** |
| **eventos** | ? | ? | ? | ⚠️ Desconocido | **P3** |

---

## ANÁLISIS DETALLADO POR MÓDULO

### 1. MI-PORTAL ⚠️ ESTADO: CRÍTICO (P0)

**Archivos afectados:**
- `/includes/frontend/class-user-portal.php` (líneas 157-258)
- `/includes/frontend/class-portal-profiles.php` (líneas 67-300)

#### Problemas Encontrados

**A. Array `$personal_modules` con URLs genéricas** (`class-user-portal.php:157-258`)

```php
// ❌ ACTUAL (líneas 164-195)
[
    'perfil_socios' => [
        'acciones' => [
            'url' => '/mi-cuota',           // ❌ Sin prefijo /mi-portal/
            'url' => '/renovar-socio',      // ❌ Sin prefijo /mi-portal/
        ],
    ],
    'perfil_consumidor' => [
        'acciones' => [
            'url' => '/mis-eventos',        // ❌ Sin prefijo /mi-portal/
            'url' => '/eventos',            // ❌ Sin prefijo /mi-portal/
            'url' => '/mis-pedidos',        // ❌ Sin prefijo /mi-portal/
            'url' => '/nuevo-pedido',       // ❌ Sin prefijo /mi-portal/
        ],
    ],
]

// ✅ DEBERÍA SER
[
    'perfil_socios' => [
        'acciones' => [
            'url' => Flavor_Chat_Helpers::get_action_url('socios', 'mi-cuota'),
            'url' => Flavor_Chat_Helpers::get_action_url('socios', 'renovar'),
        ],
    ],
    'perfil_consumidor' => [
        'acciones' => [
            'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'mis-eventos'),
            'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado'),
            'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos'),
            'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'nuevo-pedido'),
        ],
    ],
]
```

**B. CTAs en Portal Profiles hardcodeadas** (`class-portal-profiles.php:67-73`)

```php
// ❌ ACTUAL
'acciones' => [
    ['label' => '🛒 Hacer Pedido', 'url' => '/nuevo-pedido'],      // ❌
    ['label' => '📅 Próximos Eventos', 'url' => '/eventos'],       // ❌
    ['label' => '💶 Mi Cuota', 'url' => '/mi-cuota'],              // ❌
]

// ✅ DEBERÍA SER
'acciones' => [
    ['label' => '🛒 Hacer Pedido', 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'nuevo-pedido')],
    ['label' => '📅 Próximos Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'proximos')],
    ['label' => '💶 Mi Cuota', 'url' => Flavor_Chat_Helpers::get_action_url('socios', 'mi-cuota')],
]
```

**C. Navegación de perfiles con URLs hardcodeadas** (`class-portal-profiles.php:93-97`)

```php
// ❌ ACTUAL
'items' => [
    ['label' => 'Inicio', 'url' => '/mi-portal'],
    ['label' => 'Pedidos', 'url' => '/mis-pedidos'],     // ❌
    ['label' => 'Eventos', 'url' => '/eventos'],         // ❌
]

// ✅ DEBERÍA SER
'items' => [
    ['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url()],
    ['label' => 'Pedidos', 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos')],
    ['label' => 'Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'mis-eventos')],
]
```

#### Impacto Usuario

- ⚠️ Al hacer clic en "Hacer Pedido" se le lleva a `/nuevo-pedido` (página que probablemente no existe o es genérica)
- ⚠️ Sale del contexto visual `/mi-portal/`
- ⚠️ Si la página no existe, ve un 404
- ⚠️ Confusión sobre dónde está en la navegación

#### Solución

**Paso 1:** Refactorizar `build_personal_modules()` en `class-user-portal.php:157-258`

**Paso 2:** Refactorizar todos los perfiles en `class-portal-profiles.php:90-350`

**Paso 3:** Crear método helper `get_profile_navigation()` que genere URLs dinámicamente

---

### 2. TRÁMITES ⚠️ ESTADO: CRÍTICO (P0)

**Archivos afectados:**
- `/includes/modules/tramites/frontend/class-tramites-frontend-controller.php` (líneas 1899-1923)
- `/includes/modules/tramites/templates/*.php` (TODOS)
- `/includes/modules/tramites/class-tramites-dashboard-tab.php` (líneas 313-360)
- `/includes/modules/tramites/class-tramites-dashboard-widget.php` (líneas 87-158)

#### Problemas Encontrados

**A. NINGÚN uso del helper** - 100% hardcoded

```php
// ❌ ACTUAL (frontend-controller.php:1899-1923)
public function get_tramite_url($tramite_id) {
    return add_query_arg('tramite_id', $tramite_id, home_url('/mi-portal/tramites/detalle/'));
}

public function get_iniciar_tramite_url($tramite_id) {
    return add_query_arg('tramite_id', $tramite_id, home_url('/mi-portal/tramites/iniciar/'));
}

public function get_seguimiento_url($solicitud_id) {
    return add_query_arg('solicitud_id', $solicitud_id, home_url('/mi-portal/tramites/seguimiento/'));
}

public function get_citas_url() {
    return home_url('/mi-portal/tramites/citas/');
}

public function get_tramites_base_url() {
    return home_url('/mi-portal/tramites/');
}

// ✅ DEBERÍA SER
public function get_tramite_url($tramite_id) {
    return add_query_arg('tramite_id', $tramite_id, Flavor_Chat_Helpers::get_action_url('tramites', 'detalle'));
}

public function get_iniciar_tramite_url($tramite_id) {
    return add_query_arg('tramite_id', $tramite_id, Flavor_Chat_Helpers::get_action_url('tramites', 'iniciar'));
}

public function get_seguimiento_url($solicitud_id) {
    return add_query_arg('solicitud_id', $solicitud_id, Flavor_Chat_Helpers::get_action_url('tramites', 'seguimiento'));
}

public function get_citas_url() {
    return Flavor_Chat_Helpers::get_action_url('tramites', 'citas');
}

public function get_tramites_base_url() {
    return Flavor_Chat_Helpers::get_action_url('tramites', '');
}
```

**B. Variable `$tramites_base_url` repetida en TODOS los templates**

```php
// ❌ ACTUAL (en templates/listado.php, templates/detalle.php, etc.)
<?php $tramites_base_url = home_url('/mi-portal/tramites/'); ?>
<a href="<?php echo $tramites_base_url; ?>iniciar/">Iniciar trámite</a>

// ✅ DEBERÍA SER
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('tramites', 'iniciar')); ?>">Iniciar trámite</a>
```

**C. Dashboard Tab con mezcla de URLs** (`class-tramites-dashboard-tab.php:313-360`)

```php
// ❌ ACTUAL
<a href="<?php echo esc_url(home_url('/mi-portal/?tab=tramites-pendientes')); ?>">
<a href="<?php echo esc_url(home_url('/tramites/')); ?>">                          // ⚠️ Fuera de /mi-portal/
<a href="<?php echo esc_url(home_url('/mi-portal/?tab=tramites-historial')); ?>">

// ✅ DEBERÍA SER
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('tramites', 'pendientes')); ?>">
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('tramites', 'listado')); ?>">
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('tramites', 'historial')); ?>">
```

#### Impacto Usuario

- ⚠️ Enlaces `/tramites/` (sin `/mi-portal/`) sacan al usuario del flujo
- ⚠️ Si cambia la estructura de URLs, hay que modificar 10+ archivos
- ⚠️ Inconsistencia total con otros módulos

#### Solución

**Paso 1:** Refactorizar métodos `get_*_url()` en `class-tramites-frontend-controller.php:1899-1923`

**Paso 2:** Eliminar `$tramites_base_url` de todos los templates y usar helper directamente

**Paso 3:** Refactorizar `class-tramites-dashboard-tab.php` y `class-tramites-dashboard-widget.php`

---

### 3. SOCIOS ⚠️ ESTADO: CRÍTICO (P0)

**Archivos afectados:**
- `/includes/modules/socios/frontend/class-socios-frontend-controller.php` (múltiples líneas)
- `/includes/modules/socios/class-socios-dashboard-tab.php` (líneas 85-242)
- `/includes/modules/socios/class-socios-dashboard-widget.php` (líneas 188-273)

#### Problemas Encontrados

**A. 0% uso del helper**

```php
// ❌ ACTUAL (frontend-controller.php)
home_url('/mi-portal/socios/')               (línea 166)
home_url('/mi-portal/socios/unirse/')       (línea 358, 825)
home_url('/mi-portal/socios/pagar-cuota/')  (línea 414, 625, 1011)
home_url('/mi-portal/socios/carnet/')       (línea 423, 1056)
home_url('/validar-socio/' . $socio->numero) (línea 516)  // ⚠️⚠️ FUERA DE PATRÓN

// ✅ DEBERÍA SER
Flavor_Chat_Helpers::get_action_url('socios', '')
Flavor_Chat_Helpers::get_action_url('socios', 'unirse')
Flavor_Chat_Helpers::get_action_url('socios', 'pagar-cuota')
Flavor_Chat_Helpers::get_action_url('socios', 'carnet')
Flavor_Chat_Helpers::get_action_url('socios', 'validar', ['numero' => $socio->numero])
```

**B. URL `/validar-socio/` totalmente fuera del patrón** (línea 516)

```php
// ❌ ACTUAL
$validar_url = home_url('/validar-socio/' . $socio->numero);

// ✅ DEBERÍA SER
$validar_url = Flavor_Chat_Helpers::get_action_url('socios', 'validar', ['numero' => $socio->numero]);
// Genera: /mi-portal/socios/validar/?numero=XXX
```

**C. Dashboard Tab hardcoded** (`class-socios-dashboard-tab.php:85-242`)

```php
// ❌ ACTUAL
home_url('/mi-portal/socios/unirse/')
home_url('/mi-portal/socios/mi-perfil/')
home_url('/mi-portal/socios/carnet/')
add_query_arg('cuota_id', $cuota->id, home_url('/mi-portal/socios/pagar-cuota/'))

// ✅ DEBERÍA SER
Flavor_Chat_Helpers::get_action_url('socios', 'unirse')
Flavor_Chat_Helpers::get_action_url('socios', 'mi-perfil')
Flavor_Chat_Helpers::get_action_url('socios', 'carnet')
add_query_arg('cuota_id', $cuota->id, Flavor_Chat_Helpers::get_action_url('socios', 'pagar-cuota'))
```

#### Impacto Usuario

- ⚠️⚠️ **CRÍTICO:** `/validar-socio/` rompe completamente el flujo del portal
- ⚠️ Usuario sale del contexto visual `/mi-portal/`
- ⚠️ Posible 404 si la página `/validar-socio/` no existe

#### Solución

**Paso 1:** Refactorizar todas las URLs en `class-socios-frontend-controller.php`

**Paso 2:** **CRÍTICO:** Mover `/validar-socio/` a `/mi-portal/socios/validar/`

**Paso 3:** Refactorizar dashboard tab y widget

---

### 4. GRUPOS-CONSUMO ⚠️ ESTADO: PARCIAL (P1)

**Archivos afectados:**
- `/includes/modules/grupos-consumo/frontend/class-gc-frontend-controller.php` (líneas 143, 521, 801, 956, 1012)

#### Problemas Encontrados

**Mixto:** 40% usa helper, 60% hardcoded

```php
// ✅ CORRECTO (línea 144)
'carritoUrl' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mi-pedido')

// ❌ INCORRECTO (líneas 143, 521, 801, etc.)
'loginUrl' => wp_login_url(home_url('/mi-portal/grupos-consumo/productos/'))
$productos_url = home_url('/mi-portal/grupos-consumo/productos/');
$ciclo_url = home_url('/mi-portal/grupos-consumo/ciclo/');
$suscripciones_url = home_url('/mi-portal/grupos-consumo/suscripciones/');

// ✅ DEBERÍA SER
'loginUrl' => wp_login_url(Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'productos'))
$productos_url = Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'productos');
$ciclo_url = Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'ciclo');
$suscripciones_url = Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'suscripciones');
```

#### Solución

**Paso 1:** Buscar y reemplazar todas las instancias de `home_url('/mi-portal/grupos-consumo/...)` con helper

**Paso 2:** Verificar templates `/templates/catalogo.php` línea 638

---

### 5. COLECTIVOS ⚠️ ESTADO: PARCIAL (P2)

**Archivos afectados:**
- `/includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` (líneas 251, 254)

#### Problemas Encontrados

**Mixto:** 60% usa helper, 40% hardcoded

```php
// ✅ CORRECTO (línea 224)
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'crear')); ?>">

// ❌ INCORRECTO (líneas 251-254 - en mensajes de empty state)
<a href="<?php echo esc_url(home_url('/mi-portal/colectivos/')); ?>">
<a href="<?php echo esc_url(home_url('/mi-portal/colectivos/crear/')); ?>">

// ✅ DEBERÍA SER
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', '')); ?>">
<a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('colectivos', 'crear')); ?>">
```

#### Solución

**Paso 1:** Refactorizar líneas 251-254 de `class-colectivos-frontend-controller.php`

---

### 6. EVENTOS ⚠️ ESTADO: DESCONOCIDO (P3)

**Pendiente de análisis profundo.**

Aparentemente usa shortcodes, lo cual es buena práctica. Requiere auditoría específica.

---

## PLAN DE REMEDIACIÓN

### FASE 0: Preparación (1 día)

**Tareas:**
1. ✅ Crear este documento de auditoría
2. ⬜ Crear tests unitarios para `Flavor_Chat_Helpers::get_action_url()`
3. ⬜ Crear script de validación de URLs en el código

### FASE 1: Correcciones P0 - Críticas (3-4 días)

#### 1.1 Mi-Portal

**Archivos:** `class-user-portal.php`, `class-portal-profiles.php`

**Tareas:**
- ⬜ Refactorizar `build_personal_modules()` (líneas 157-258)
- ⬜ Refactorizar perfiles (líneas 90-350 portal-profiles)
- ⬜ Crear método `get_profile_navigation($perfil)`
- ⬜ Testing manual de todos los perfiles

**Estimación:** 2 días

#### 1.2 Trámites

**Archivos:** Frontend controller, templates, dashboard tab, dashboard widget

**Tareas:**
- ⬜ Refactorizar métodos `get_*_url()` (líneas 1899-1923)
- ⬜ Eliminar `$tramites_base_url` de templates
- ⬜ Refactorizar dashboard tab (líneas 313-360)
- ⬜ Refactorizar dashboard widget
- ⬜ Testing completo del flujo trámites

**Estimación:** 1.5 días

#### 1.3 Socios

**Archivos:** Frontend controller, dashboard tab, dashboard widget

**Tareas:**
- ⬜ **CRÍTICO:** Migrar `/validar-socio/` a `/mi-portal/socios/validar/`
- ⬜ Refactorizar frontend controller (múltiples líneas)
- ⬜ Refactorizar dashboard tab (líneas 85-242)
- ⬜ Refactorizar dashboard widget
- ⬜ Testing completo del flujo socios

**Estimación:** 1 día

**Total Fase 1:** 4-5 días

---

### FASE 2: Correcciones P1 - Alta Prioridad (2-3 días)

#### 2.1 Grupos-Consumo

**Tareas:**
- ⬜ Refactorizar frontend controller (líneas 143, 521, 801, 956, 1012)
- ⬜ Verificar templates (catalogo.php:638)
- ⬜ Testing completo

**Estimación:** 1 día

#### 2.2 Portal Unificado

**Tareas:**
- ⬜ Verificar que `class-unified-portal.php` usa helpers correctamente
- ⬜ Migrar lógica de user-portal a unified-portal si es necesario

**Estimación:** 1 día

**Total Fase 2:** 2 días

---

### FASE 3: Correcciones P2-P3 + Mejoras (3-4 días)

#### 3.1 Colectivos

**Tareas:**
- ⬜ Refactorizar líneas 251-254
- ⬜ Testing

**Estimación:** 0.5 días

#### 3.2 Eventos (Auditoría + Fix)

**Tareas:**
- ⬜ Auditoría profunda
- ⬜ Correcciones si es necesario

**Estimación:** 1 día

#### 3.3 Mejoras Generales

**Tareas:**
- ⬜ Crear constantes para acciones comunes
- ⬜ Sistema de validación automática de URLs
- ⬜ Tests unitarios completos
- ⬜ Documentación de patrones

**Estimación:** 2 días

**Total Fase 3:** 3.5 días

---

### FASE 4: Aplicar Capa Visual (3-5 días)

**Solo después de completar Fases 1-3**

**Tareas:**
- ⬜ Aplicar componentes Empty State
- ⬜ Aplicar componentes Form Wizard
- ⬜ Aplicar componentes Filter Bar
- ⬜ Mejoras visuales generales

---

## TIEMPO TOTAL ESTIMADO

- **Fase 0:** 1 día
- **Fase 1 (P0):** 4-5 días
- **Fase 2 (P1):** 2 días
- **Fase 3 (P2-P3):** 3.5 días
- **Fase 4 (Visual):** 3-5 días

**TOTAL:** 13-16 días de trabajo

---

## MÉTRICAS DE ÉXITO

### Pre-Remediación (Actual)

| Métrica | Valor |
|---------|-------|
| Módulos con 0% helper usage | 2 (Trámites, Socios) |
| URLs fuera de `/mi-portal/` | 15+ detectadas |
| Archivos con URLs hardcodeadas | 20+ |

### Post-Remediación (Objetivo)

| Métrica | Objetivo |
|---------|----------|
| Módulos con 100% helper usage | 6/6 |
| URLs fuera de `/mi-portal/` | 0 |
| Archivos con URLs hardcodeadas | 0 |
| Tests unitarios | >20 tests |
| Cobertura de código | >80% en helpers |

---

## REFERENCIAS

- Helper centralizado: `/includes/class-helpers.php` líneas 393-450
- Portal Unificado (✅ bueno): `/includes/frontend/class-unified-portal.php`
- Reports previos:
  - `/reports/AUDITORIA-ASIDES-MODULOS-2026-03-02.md`
  - `/reports/INFORME-TECNICO-SESION-RUNTIME-2026-03-04.md`
  - `/reports/MEJORAS-UX-UI-DASHBOARDS-2026-03-21.md`

---

**Responsable:** Claude Code
**Aprobado por:** Pendiente revisión usuario
**Próximo paso:** Implementar Fase 1.1 (Mi-Portal)
