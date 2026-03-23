# PROGRESO: Refactorización de Navegación Canónica

**Fecha:** 2026-03-22
**Objetivo:** Eliminar URLs hardcodeadas y unificar bajo patrón `/mi-portal/MODULO/ACCION/`
**Basado en:** `AUDITORIA-UX-NAVEGACION-2026-03-22.md`

---

## ESTADO ACTUAL

### ✅ COMPLETADO

#### 1. Mi-Portal: User Portal (Fase 1.1 - Parcial)

**Archivo:** `/includes/frontend/class-user-portal.php`

**Cambios realizados:**
- ✅ Refactorizado método `register_personal_modules()` (líneas 157-258)
- ✅ Todas las URLs de acciones ahora usan `Flavor_Chat_Helpers::get_action_url()`
- ✅ 10 módulos refactorizados:
  - socios
  - eventos
  - grupos-consumo
  - marketplace
  - reservas
  - cursos
  - biblioteca
  - banco-tiempo
  - incidencias
  - foros

**Antes:**
```php
'actions' => [
    ['label' => __('Mi Cuota', 'flavor-chat-ia'), 'url' => '/mi-cuota'],  // ❌
]
```

**Después:**
```php
'actions' => [
    ['label' => __('Mi Cuota', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('socios', 'mi-cuota')],  // ✅
]
```

**Impacto:**
- 20+ URLs corregidas
- Patrón canónico aplicado a todos los módulos personales
- CTAs ahora mantienen al usuario dentro del flujo `/mi-portal/`

---

#### 2. Mi-Portal: Portal Profiles (Fase 1.1 - Parcial)

**Archivo:** `/includes/frontend/class-portal-profiles.php`

**Perfiles refactorizados:**
- ✅ **consumidor** (líneas 67-98)
  - Acciones rápidas: 3 URLs corregidas
  - Navegación: 4 URLs corregidas
- ✅ **comunidad** (líneas 116-148)
  - Acciones rápidas: 4 URLs corregidas
  - Navegación: 5 URLs corregidas
- ✅ **barrio** (líneas 167-200)
  - Acciones rápidas: 4 URLs corregidas
  - Navegación: 4 URLs corregidas
- ✅ **coworking** (líneas 217-246)
  - Acciones rápidas: 3 URLs corregidas
  - Navegación: 4 URLs corregidas
- ✅ **cooperativa** (líneas 265-296)
  - Acciones rápidas: 4 URLs corregidas
  - Navegación: 4 URLs corregidas
- ✅ **academia** (líneas 315-...)
  - Acciones rápidas: 3 URLs corregidas

**Ejemplos de cambios:**

**Antes:**
```php
['label' => '🛒 Hacer Pedido', 'url' => '/nuevo-pedido', 'color' => 'primary'],  // ❌
['label' => 'Inicio', 'url' => '/mi-portal'],  // ❌ hardcoded
```

**Después:**
```php
['label' => '🛒 Hacer Pedido', 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'nuevo-pedido'), 'color' => 'primary'],  // ✅
['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url()],  // ✅
```

**Impacto:**
- 35+ URLs corregidas en acciones rápidas y navegación
- 6 perfiles principales refactorizados
- Consistencia total en CTAs de perfiles adaptativos

---

### 📊 MÉTRICAS DE PROGRESO

| Archivo | URLs Antes | URLs Después | Mejora |
|---------|-----------|--------------|--------|
| `class-user-portal.php` | 0% helper | **100% helper** | ✅ +100% |
| `class-portal-profiles.php` (6 perfiles) | 0% helper | **100% helper** | ✅ +100% |

**Total URLs corregidas:** ~55+

---

## ⏳ EN PROGRESO

Ninguno actualmente.

---

## 📋 PENDIENTE (según plan de remediación)

### Fase 1 - P0 (Crítico)

#### 1.1 Mi-Portal ⏱️ 50% COMPLETADO

- ✅ Refactorizar `class-user-portal.php`
- ✅ Refactorizar `class-portal-profiles.php` (6 perfiles principales)
- ⬜ **Verificar si quedan más perfiles en `class-portal-profiles.php`**
- ⬜ Testing manual de todos los perfiles

**Estimación restante:** 1 día

#### 1.2 Trámites ⏱️ 0% COMPLETADO

**Archivos:**
- ⬜ `/includes/modules/tramites/frontend/class-tramites-frontend-controller.php` (líneas 1899-1923)
- ⬜ `/includes/modules/tramites/templates/*.php` (eliminar `$tramites_base_url`)
- ⬜ `/includes/modules/tramites/class-tramites-dashboard-tab.php` (líneas 313-360)
- ⬜ `/includes/modules/tramites/class-tramites-dashboard-widget.php` (líneas 87-158)

**Tareas:**
- ⬜ Refactorizar métodos `get_*_url()` en frontend controller
- ⬜ Eliminar variable `$tramites_base_url` de todos los templates
- ⬜ Refactorizar dashboard tab
- ⬜ Refactorizar dashboard widget
- ⬜ **CRÍTICO:** Eliminar URLs `/tramites/` (sin `/mi-portal/`)
- ⬜ Testing completo del flujo

**Estimación:** 1.5 días

#### 1.3 Socios ⏱️ 0% COMPLETADO

**Archivos:**
- ⬜ `/includes/modules/socios/frontend/class-socios-frontend-controller.php` (múltiples líneas)
- ⬜ `/includes/modules/socios/class-socios-dashboard-tab.php` (líneas 85-242)
- ⬜ `/includes/modules/socios/class-socios-dashboard-widget.php` (líneas 188-273)

**Tareas:**
- ⬜ **CRÍTICO:** Migrar `/validar-socio/` a `/mi-portal/socios/validar/`
- ⬜ Refactorizar frontend controller (0% helper usage)
- ⬜ Refactorizar dashboard tab
- ⬜ Refactorizar dashboard widget
- ⬜ Testing completo del flujo

**Estimación:** 1 día

---

### Fase 2 - P1 (Alta Prioridad)

#### 2.1 Grupos-Consumo ⏱️ 0% COMPLETADO

**Archivos:**
- ⬜ `/includes/modules/grupos-consumo/frontend/class-gc-frontend-controller.php` (líneas 143, 521, 801, 956, 1012)
- ⬜ `/templates/catalogo.php` (línea 638)

**Tareas:**
- ⬜ Refactorizar URLs hardcodeadas en frontend controller
- ⬜ Verificar templates
- ⬜ Testing completo

**Estimación:** 1 día

#### 2.2 Portal Unificado ⏱️ 0% COMPLETADO

**Archivos:**
- ⬜ `/includes/frontend/class-unified-portal.php`

**Tareas:**
- ⬜ Verificar que usa helpers correctamente (parece que sí)
- ⬜ Migrar lógica de user-portal a unified-portal si es necesario

**Estimación:** 1 día

---

### Fase 3 - P2/P3 (Media/Baja Prioridad)

#### 3.1 Colectivos ⏱️ 0% COMPLETADO

**Archivos:**
- ⬜ `/includes/modules/colectivos/frontend/class-colectivos-frontend-controller.php` (líneas 251-254)

**Tareas:**
- ⬜ Refactorizar líneas 251-254 (URLs en empty states)
- ⬜ Testing

**Estimación:** 0.5 días

#### 3.2 Eventos ⏱️ 0% COMPLETADO

**Tareas:**
- ⬜ Auditoría profunda del módulo
- ⬜ Correcciones si es necesario

**Estimación:** 1 día

---

## 📈 PROGRESO GENERAL

### Fases Completadas

```
Fase 0 (Preparación):     [████████████████████] 100% ✅
Fase 1.1 (Mi-Portal):     [██████████----------]  50% ⏳
Fase 1.2 (Trámites):      [--------------------]   0% ⬜
Fase 1.3 (Socios):        [--------------------]   0% ⬜
Fase 2 (P1):              [--------------------]   0% ⬜
Fase 3 (P2-P3):           [--------------------]   0% ⬜
```

### Tiempo Invertido vs Estimado

| Fase | Estimado | Invertido | Restante |
|------|----------|-----------|----------|
| Fase 0 | 1 día | 0.5 días | - |
| Fase 1.1 | 2 días | **1 día** | 1 día |
| Fase 1.2 | 1.5 días | - | 1.5 días |
| Fase 1.3 | 1 día | - | 1 día |
| **TOTAL P0** | **4.5 días** | **1 día** | **3.5 días** |

---

## 🎯 PRÓXIMOS PASOS INMEDIATOS

### Prioridad 1 (Hoy)

1. ⬜ **Completar Fase 1.1: Mi-Portal**
   - Verificar si quedan más perfiles en `class-portal-profiles.php`
   - Testing manual de perfiles refactorizados
   - Documentar cualquier issue encontrado

### Prioridad 2 (Mañana)

2. ⬜ **Empezar Fase 1.2: Trámites**
   - Refactorizar `class-tramites-frontend-controller.php`
   - Eliminar `$tramites_base_url` de templates
   - Especial atención a URLs `/tramites/` (sin prefijo)

### Prioridad 3 (Siguiente)

3. ⬜ **Continuar Fase 1.3: Socios**
   - **CRÍTICO:** Manejar `/validar-socio/` → `/mi-portal/socios/validar/`
   - Refactorizar frontend controller

---

## 🐛 ISSUES ENCONTRADOS

Ninguno hasta ahora.

---

## 📝 NOTAS TÉCNICAS

### Helper Utilizado

```php
// Para URLs base del portal
Flavor_Chat_Helpers::get_portal_url()
// Retorna: /mi-portal/

// Para acciones de módulos
Flavor_Chat_Helpers::get_action_url('modulo', 'accion')
// Retorna: /mi-portal/modulo/accion/

// Con parámetros adicionales
Flavor_Chat_Helpers::get_action_url('modulo', 'accion', ['param' => 'value'])
// Retorna: /mi-portal/modulo/accion/?param=value
```

### Convenciones de Nombres de Acciones

| Acción Usuario | Slug Acción | Ejemplo URL |
|----------------|-------------|-------------|
| Listado general | `listado` o `''` | `/mi-portal/eventos/` o `/mi-portal/eventos/listado/` |
| Mis items | `mis-{items}` | `/mi-portal/eventos/mis-inscripciones/` |
| Crear nuevo | `nueva` o `nuevo-{item}` | `/mi-portal/reservas/nueva/` |
| Ver detalle | `detalle` | `/mi-portal/tramites/detalle/?tramite_id=123` |
| Acciones específicas | `{accion}` | `/mi-portal/socios/renovar/` |

---

## ✅ CHECKLIST DE CALIDAD

Para cada archivo refactorizado:

- [x] Todas las URLs usan `Flavor_Chat_Helpers::get_action_url()` o `get_portal_url()`
- [x] No quedan URLs hardcodeadas tipo `/modulo/` o `/accion/`
- [x] Las URLs siguen el patrón `/mi-portal/MODULO/ACCION/`
- [ ] Testing manual realizado
- [ ] No hay errores en logs de PHP
- [ ] No hay warnings de console en JavaScript

---

**Última actualización:** 2026-03-22 (en progreso)
**Responsable:** Claude Code
**Estado general:** ⏳ En progreso (Fase 1.1 al 50%)
