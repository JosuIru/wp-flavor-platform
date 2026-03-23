# PLAN DE PARALELIZACIÓN OPTIMIZADO

**Fecha:** 2026-03-22
**Objetivo:** Acortar tiempo de desarrollo sin perder calidad
**Estrategia:** Aprovechar lo que YA EXISTE y trabajar en paralelo donde sea posible

---

## 📊 HALLAZGOS CRÍTICOS DE LA AUDITORÍA

### ✅ LO QUE YA EXISTE Y FUNCIONA

1. **Sistema de Contextos (FRAGMENTADO pero funcional):**
   - ✅ App Profiles (`class-app-profiles.php`) - 25 perfiles, gestión de módulos
   - ✅ Portal Profiles (`class-portal-profiles.php`) - 8 layouts adaptativos
   - ✅ Template Orchestrator - Activación completa de plantillas
   - ❌ **PROBLEMA:** Los 3 sistemas NO se comunican entre sí

2. **Sistema de Navegación (FRAGMENTADO pero robusto):**
   - ✅ Shell Navigation Registry - Sistema central de subpáginas + badges
   - ✅ Dashboard Tabs Trait - API completa para tabs de módulos
   - ✅ Breadcrumbs (3 clases separadas: frontend, CPT, admin)
   - ✅ Module Navigation (shortcodes hardcoded)
   - ❌ **PROBLEMA:** 4 sistemas que hacen cosas similares sin cohesión

3. **Componentes Frontend (COMPLETO):**
   - ✅ **79 componentes shared** en `/templates/components/shared/`
   - ✅ **30+ funciones helper** de renderizado (`flavor_render_*()`)
   - ✅ **Sistema de layouts** completo (6 tipos menú + 5 footers)
   - ✅ **Form Wizard** multi-paso ya implementado
   - ✅ **Empty States** modernos (creado hoy)
   - ✅ **Sistema visual robusto** con Tailwind + variables CSS
   - ⚠️ **PROBLEMA:** Módulos no los usan, tienen HTML hardcoded

4. **URLs y Helpers (CRÍTICO - 0% compliance):**
   - ❌ **Eventos:** 0% helper usage (8 URLs hardcodeadas)
   - ❌ **Socios:** 0% helper usage (7 URLs hardcodeadas)
   - ❌ **Comunidades:** 0% helper usage (12 URLs hardcodeadas)
   - ⚠️ **Grupos-Consumo:** 22% helper usage (6 URLs hardcodeadas)
   - ❌ **Tramites, Incidencias, Biblioteca, Cursos:** Sin auditar pero estimado 0-20%

---

## 🎯 NUEVA ESTRATEGIA: NO DUPLICAR, INTEGRAR

### ANTES (Plan original):
```
Sprint 1 (5d) → Sprint 2 (8d) → Sprint 3 (7d) → Sprint 4 (7d)
TOTAL: 27 días SECUENCIALES
```

### AHORA (Plan optimizado):
```
Track 1: Navegación     ████████████░░░░ (14 días)
Track 2: Integración    ░░░░████████████ (10 días, empieza día 5)
Track 3: Componentes    ░░░░░░░░████████ (8 días, empieza día 9)

TOTAL: 17 días EN PARALELO (ahorro de 10 días)
```

---

## 🚀 TRACKS EN PARALELO

### TRACK 1: NAVEGACIÓN CANÓNICA (P0 - BLOQUEANTE)

**Duración:** 14 días
**Puede empezar:** Inmediatamente
**Responsable:** Claude (principal)

#### Semana 1: Refactor URLs (5 días)

**Día 1-2: Módulos críticos (0% compliance)**
- ⬜ Eventos (8 URLs)
- ⬜ Socios (7 URLs)
- ⬜ Comunidades (12 URLs)

**Día 3-4: Módulos parciales**
- ⬜ Grupos-Consumo (completar 6 URLs restantes)
- ⬜ Trámites (estimado 15 URLs)
- ⬜ Incidencias (estimado 10 URLs)

**Día 5: Testing y ajustes**
- ⬜ Testing manual de navegación
- ⬜ Fix de URLs rotas
- ⬜ Verificación completa

#### Semana 2: Resto de módulos (5 días)

**Día 6-7:**
- ⬜ Biblioteca, Cursos, Talleres

**Día 8-9:**
- ⬜ Marketplace, Reservas, Foros

**Día 10:**
- ⬜ Testing final
- ⬜ Métrica: 100% helper usage en todos

#### Semana 3: Portal Profiles completo (4 días)

**Día 11-12: Completar perfiles restantes**
- ✅ Ya refactorizados: consumidor, comunidad, barrio, coworking, cooperativa, academia
- ⬜ Pendientes: marketplace, ayuntamiento, default

**Día 13: Testing de perfiles**
- ⬜ Verificar todas las URLs de CTAs
- ⬜ Verificar navegación adaptativa

**Día 14: Documentación**
- ⬜ Actualizar guías de uso
- ⬜ Crear tests unitarios de navegación

**Entregable Track 1:**
- ✅ 100% URLs usando helpers
- ✅ 0 URLs fuera de `/mi-portal/`
- ✅ Navegación 100% canónica

---

### TRACK 2: INTEGRACIÓN DE SISTEMAS (P1 - ARQUITECTURA)

**Duración:** 10 días
**Puede empezar:** Día 5 (cuando Track 1 avance)
**Responsable:** Claude o desarrollador en paralelo

**OBJETIVO:** Unificar los 3 sistemas existentes en lugar de crear desde cero.

#### Fase 1: Context Manager Integrador (3 días)

**NO crear desde cero**, sino crear **facade** que unifique:

```php
// NUEVO: includes/class-context-manager.php (300 líneas aprox)
class Flavor_Context_Manager {

    /**
     * Fuente de verdad única
     */
    private static function get_context_option() {
        return get_option('flavor_platform_context', [
            'primary_profile' => 'comunidad',
            'additional_profiles' => [],
            'active_modules' => [],
            'portal_layout' => 'balanced',
        ]);
    }

    /**
     * Sincroniza con los 3 sistemas existentes
     */
    public static function set_context($profile_id) {
        // 1. Actualizar App Profiles
        $app_profiles = Flavor_App_Profiles::get_instance();
        $app_profiles->establecer_perfil($profile_id);

        // 2. Sincronizar con Portal Profiles
        update_option('flavor_active_app_profile', $profile_id);

        // 3. Activar plantilla si es primera vez
        $orchestrator = Flavor_Template_Orchestrator::get_instance();
        if (!get_option('flavor_template_activated_' . $profile_id)) {
            $orchestrator->activate_template($profile_id);
        }

        // 4. Guardar en opción unificada
        self::update_context_option(['primary_profile' => $profile_id]);
    }

    /**
     * Obtiene contexto actual (lee de los 3 sistemas)
     */
    public static function get_active_context() {
        $option = self::get_context_option();

        // Verificar consistencia con sistemas existentes
        $app_profile = Flavor_App_Profiles::get_instance()->obtener_perfil_activo();
        $portal_profile = Flavor_Portal_Profiles::get_instance()->get_active_profile();

        // Si hay inconsistencia, sincronizar
        if ($app_profile !== $option['primary_profile']) {
            self::sync_from_app_profiles();
        }

        return $option;
    }

    /**
     * Obtiene módulos del contexto
     */
    public static function get_context_modules($include_optional = false) {
        $context = self::get_active_context();

        // Delega a App Profiles que ya tiene esta lógica
        $app_profiles = Flavor_App_Profiles::get_instance();
        return $app_profiles->get_profile_modules($context['primary_profile'], $include_optional);
    }
}
```

**Tareas:**
- Día 5: Crear clase Context Manager (facade)
- Día 6: Integrar con App Profiles
- Día 7: Integrar con Portal Profiles y Template Orchestrator
- Día 8: Testing de sincronización

#### Fase 2: Navigation Builder Unificado (4 días)

**NO crear desde cero**, extender Shell Navigation Registry al frontend:

```php
// MODIFICAR: includes/class-shell-navigation-registry.php
class Flavor_Shell_Navigation_Registry {

    // AÑADIR métodos frontend:

    /**
     * Registrar navegación para frontend
     */
    public function register_frontend_nav($module_id, $items) {
        $this->frontend_nav[$module_id] = $items;
    }

    /**
     * Renderizar navegación frontend
     */
    public function render_module_nav($module_id, $current_slug = '') {
        $items = $this->get_module_subpages($module_id);

        // Usar componente shared existente
        flavor_render_component('tabs', [
            'tabs' => $items,
            'active' => $current_slug,
        ]);
    }

    /**
     * Renderizar aside (NUEVO)
     */
    public function render_module_aside($module_id, $current_slug = '') {
        $items = $this->get_module_subpages($module_id);

        // Genera HTML de aside vertical
        include FLAVOR_PLUGIN_DIR . 'templates/navigation/module-aside.php';
    }
}
```

**CREAR:** `templates/navigation/module-aside.php` (100 líneas) - Template de aside

**Tareas:**
- Día 9: Extender Shell Registry al frontend
- Día 10: Crear template de aside
- Día 11: Migrar Module Navigation hardcoded a usar Registry
- Día 12: Testing en 5 módulos principales

#### Fase 3: Forzar uso de Dashboard Tabs Trait (3 días)

**El trait ya existe**, solo hay que hacer que los módulos lo usen:

**Tareas:**
- Día 13: Modificar 10 módulos principales para usar el trait
- Día 14: Verificar que navegación se genera automáticamente
- Día 15: Deprecar navegación hardcoded

**Entregable Track 2:**
- ✅ Context Manager funcionando (unifica 3 sistemas)
- ✅ Navigation Builder funcional (frontend + admin)
- ✅ 10+ módulos usando Dashboard Tabs Trait

---

### TRACK 3: APLICAR COMPONENTES EXISTENTES (P2 - UX)

**Duración:** 8 días
**Puede empezar:** Día 9 (cuando Track 1 esté >50% y Track 2 iniciado)
**Responsable:** Desarrollador frontend en paralelo

**OBJETIVO:** Aplicar los 79 componentes shared que ya existen.

#### Fase 1: Empty States (2 días)

Usar `flavor_render_empty_state()` que ya existe:

**Tareas:**
- Día 9: Aplicar a Eventos, Grupos-Consumo, Incidencias, Biblioteca
- Día 10: Aplicar a resto de módulos (buscar "No hay" en templates)

#### Fase 2: Form Wizards (3 días)

Usar `flavor_render_wizard()` que ya existe:

**Identificar formularios largos (>10 campos):**
- Crear incidencia
- Crear evento
- Alta de socio
- Crear grupo de consumo
- Publicar en marketplace

**Tareas:**
- Día 11: Convertir 2 formularios prioritarios en wizards
- Día 12: Convertir 3 formularios más
- Día 13: Testing y ajustes

#### Fase 3: Componentes de Data Display (3 días)

Usar componentes shared existentes:
- `flavor_render_table()` para listados
- `flavor_render_kpis()` para métricas
- `flavor_render_activity_feed()` para actividad

**Tareas:**
- Día 14: Aplicar a dashboards de 3 módulos
- Día 15: Aplicar a listados de 5 módulos
- Día 16: Testing y refinamiento

**Entregable Track 3:**
- ✅ Empty states en 100% de módulos
- ✅ Form wizards en 5+ formularios largos
- ✅ Componentes shared en 10+ módulos

---

## 📅 CRONOGRAMA VISUAL

```
Día  | Track 1: Navegación      | Track 2: Integración  | Track 3: Componentes
-----|--------------------------|----------------------|---------------------
1-2  | Eventos, Socios, Comu    | -                    | -
3-4  | GC, Tramites, Incid      | -                    | -
5    | Testing URLs             | Context Manager (1)  | -
6    | Biblioteca, Cursos       | Context Manager (2)  | -
7    | Talleres, Marketplace    | Context Manager (3)  | -
8    | Reservas, Foros          | Sync testing         | -
9    | Testing navegación       | Nav Builder (1)      | Empty States (1)
10   | Portal Profiles (1)      | Nav Builder (2)      | Empty States (2)
11   | Portal Profiles (2)      | Migrar hardcoded     | Form Wizards (1)
12   | Testing perfiles         | Testing nav builder  | Form Wizards (2)
13   | Documentación (1)        | Dashboard Trait (1)  | Form Wizards (3)
14   | Documentación (2)        | Dashboard Trait (2)  | Data Display (1)
15   | -                        | Deprecar legacy      | Data Display (2)
16   | -                        | -                    | Testing final
17   | DONE                     | DONE                 | DONE
```

**TOTAL:** 17 días trabajando en paralelo
**vs** 27 días del plan original
**AHORRO:** 10 días (37% más rápido)

---

## 🔄 PLAN DE TRABAJO DIARIO

### CONFIGURACIÓN DE TRABAJO EN PARALELO

**Opción A: Claude solo (secuencial pero optimizado)**
- Día 1-5: Track 1 completo
- Día 6-15: Track 2 completo
- Día 16-23: Track 3 completo
- **TOTAL: 23 días** (vs 27 original, ahorro 4 días)

**Opción B: Claude + 1 Desarrollador (paralelo real)**
- Claude: Track 1 + Track 2 (15 días)
- Dev: Track 3 (8 días, empieza día 9)
- **TOTAL: 17 días** (vs 27 original, ahorro 10 días)

**Opción C: Claude + 2 Desarrolladores (paralelo máximo)**
- Claude: Track 1 (14 días)
- Dev 1: Track 2 (10 días, empieza día 5)
- Dev 2: Track 3 (8 días, empieza día 9)
- **TOTAL: 14 días** (vs 27 original, ahorro 13 días)

---

## ✅ PRIORIDADES SI HAY QUE ELEGIR

Si solo puedes hacer UNA cosa a la vez, este es el orden:

### CRÍTICO (Hacer primero, sí o sí):
1. **Track 1: Navegación** (días 1-14)
   - Sin esto, las URLs están rotas
   - Bloquea todo lo demás

### IMPORTANTE (Hacer segundo):
2. **Track 2: Context Manager** (días 5-8)
   - Unifica sistemas existentes
   - Habilita experiencias contextuales

3. **Track 2: Navigation Builder** (días 9-12)
   - Mejora navegación
   - No bloqueante pero muy visible

### MEJORA (Hacer tercero):
4. **Track 3: Empty States** (días 9-10)
   - Rápido, alto impacto visual
   - Usa componentes que ya existen

5. **Track 3: Form Wizards** (días 11-13)
   - Mejora conversión
   - Reduce abandono

6. **Track 3: Data Display** (días 14-16)
   - Pulido final
   - Consistencia visual

---

## 🎯 MÉTRICAS DE ÉXITO

### Track 1: Navegación
- [ ] 100% URLs usando helpers (67/67 módulos)
- [ ] 0 URLs fuera de `/mi-portal/`
- [ ] 20+ tests unitarios pasando
- [ ] 0 errores 404 en navegación

### Track 2: Integración
- [ ] 1 opción WordPress unificada (vs 4 actuales)
- [ ] Context Manager funcionando
- [ ] Navigation Builder en 10+ módulos
- [ ] Dashboard Tabs Trait en 10+ módulos

### Track 3: Componentes
- [ ] Empty states en 67/67 módulos
- [ ] Form wizards en 5+ formularios
- [ ] Componentes shared en 10+ módulos
- [ ] Reducción 50% abandono formularios

---

## 📝 CÓMO EMPEZAR MAÑANA

### Día 1 - Plan de Acción Inmediato

**Mañana (8h de trabajo):**

**Hora 1-2: Completar Eventos (8 URLs)**
```bash
# Archivos a modificar:
- includes/modules/eventos/frontend/class-eventos-frontend-controller.php
```

**Hora 3-4: Completar Socios (7 URLs)**
```bash
# Archivos a modificar:
- includes/modules/socios/frontend/class-socios-frontend-controller.php
- includes/modules/socios/class-socios-dashboard-tab.php
```

**Hora 5-6: Completar Comunidades (12 URLs)**
```bash
# Archivos a modificar:
- includes/modules/comunidades/frontend/class-comunidades-frontend-controller.php
```

**Hora 7-8: Testing y ajustes**
- Verificar que las URLs funcionan
- Fix de errores encontrados
- Commit del día

**Entregable día 1:**
- ✅ 3 módulos críticos al 100% helper usage
- ✅ 27 URLs refactorizadas

---

## 🚨 RIESGOS Y MITIGACIONES

### Riesgo 1: URLs rotas después del refactor
**Mitigación:** Testing exhaustivo, crear script de verificación

### Riesgo 2: Context Manager rompe funcionalidad existente
**Mitigación:** Es un facade, NO reemplaza, solo coordina

### Riesgo 3: Componentes shared no se adaptan a algunos módulos
**Mitigación:** Son 79 componentes, hay variedad; crear variantes si es necesario

### Riesgo 4: Trabajo en paralelo genera conflictos
**Mitigación:** Tracks trabajan en archivos diferentes, bajo riesgo de conflicto

---

## ✅ CHECKLIST DE INICIO

Antes de empezar mañana:

- [ ] Hacer backup del plugin completo
- [ ] Crear rama git `refactor-navegacion-urls`
- [ ] Leer este documento completo
- [ ] Priorizar: ¿Solo Claude o tienes devs disponibles?
- [ ] Decidir: ¿Opción A, B o C?
- [ ] Establecer comunicación diaria si hay equipo
- [ ] Configurar tests automáticos de URLs

---

**Última actualización:** 2026-03-22
**Próximo paso:** Empezar Track 1, Día 1 mañana
**Responsable:** Claude Code + equipo (si disponible)
