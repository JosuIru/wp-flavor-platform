# PLAN MAESTRO: Arquitectura UX y Sistema de Navegación

**Fecha:** 2026-03-22
**Objetivo:** Plan consolidado que integra navegación, jerarquías, contextos y componentes visuales
**Alcance:** Todo el ecosistema Flavor Platform 3.3.0

---

## 📋 ÍNDICE

1. [Visión General](#visión-general)
2. [Arquitectura en 4 Capas](#arquitectura-en-4-capas)
3. [Plan de Implementación](#plan-de-implementación)
4. [Roadmap y Prioridades](#roadmap-y-prioridades)
5. [Métricas de Éxito](#métricas-de-éxito)
6. [Referencias](#referencias)

---

## 🎯 VISIÓN GENERAL

### El Problema

Flavor Platform tiene actualmente **tres problemas arquitectónicos simultáneos**:

#### 1. **Capa 0 - Navegación Rota** ❌
- URLs hardcodeadas dispersas (`/mis-pedidos`, `/eventos`, `/tramites/`)
- CTAs que sacan al usuario del flujo canónico `/mi-portal/`
- Módulos sin uso del helper `Flavor_Chat_Helpers::get_action_url()`
- Inconsistencia entre módulos (0-60% de compliance)

**Impacto usuario:**
- Confusión sobre "¿dónde estoy?"
- 404s por páginas inexistentes
- Pérdida del contexto visual del portal

#### 2. **Capa 1 - Arquitectura de Información Plana** ⚠️
- Los 67 módulos se presentan como lista plana
- No hay jerarquías claras (hubs → ecosistemas → módulos operativos)
- Relaciones entre módulos no visibles en UX
- Contextos mezclados (cooperativa, ayuntamiento, barrio)

**Impacto usuario:**
- Abrumado por opciones sin organización
- No entiende qué módulos van juntos
- Experiencia genérica, no adaptada a su contexto

#### 3. **Capa 2 - UX Visual Inconsistente** ⚠️
- Dashboards admin mejorados ✅ (hecho 21/03)
- **Pero:** Frontend de módulos sin componentes reutilizables
- Empty states pobres ("No hay X")
- Formularios largos sin wizards
- Sin filtros avanzados ni búsquedas

**Impacto usuario:**
- Interfaces que parecen de generaciones distintas
- Frustración con formularios largos
- Dificultad para encontrar contenido

---

### La Solución: Arquitectura en 4 Capas

```
┌─────────────────────────────────────────────────────────┐
│ CAPA 3: EXPERIENCIAS COMPLETAS POR CONTEXTO             │
│ Grupo Consumo | Ayuntamiento | Barrio | Cooperativa     │
└─────────────────────────────────────────────────────────┘
                           ▲
┌─────────────────────────────────────────────────────────┐
│ CAPA 2: COMPONENTES VISUALES REUTILIZABLES              │
│ ✅ Dashboards Admin (HECHO) | ⬜ Frontend Components    │
└─────────────────────────────────────────────────────────┘
                           ▲
┌─────────────────────────────────────────────────────────┐
│ CAPA 1: ARQUITECTURA DE INFORMACIÓN                     │
│ Jerarquías | Contextos | Relaciones Módulos             │
└─────────────────────────────────────────────────────────┘
                           ▲
┌─────────────────────────────────────────────────────────┐
│ CAPA 0: NAVEGACIÓN CANÓNICA (FUNDAMENTO)                │
│ URLs /mi-portal/MODULO/ACCION/ | Helpers centralizados  │
└─────────────────────────────────────────────────────────┘
```

**Principio:** No se puede construir la capa superior sin corregir la inferior.

---

## 🏗️ ARQUITECTURA EN 4 CAPAS

### CAPA 0: Navegación Canónica (FUNDAMENTO)

**Estado:** ⏳ 20% completado
**Prioridad:** P0 (Crítico - bloqueante)

#### Problema Actual

| Módulo | Helper Usage | URLs Fuera de /mi-portal/ | Estado |
|--------|--------------|---------------------------|--------|
| mi-portal | 10% | ✅ SÍ (múltiples) | ❌ Crítico |
| tramites | 0% | ✅ SÍ | ❌ Crítico |
| socios | 0% | ✅ SÍ (/validar-socio/) | ❌ Crítico |
| grupos-consumo | 40% | ❌ NO | ⚠️ Parcial |
| colectivos | 60% | ❌ NO | ⚠️ Parcial |

#### Patrón Objetivo

```php
// ❌ ANTES (hardcoded, fuera de contexto)
$url = '/mis-pedidos';
$url = home_url('/tramites/detalle/');
$url = '/validar-socio/' . $numero;

// ✅ DESPUÉS (canónico, dentro de /mi-portal/)
$url = Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos');
$url = Flavor_Chat_Helpers::get_action_url('tramites', 'detalle');
$url = Flavor_Chat_Helpers::get_action_url('socios', 'validar', ['numero' => $numero]);
// Genera: /mi-portal/socios/validar/?numero=XXX
```

#### Archivos a Refactorizar

**P0 - Crítico (3-4 días):**
- ✅ `/includes/frontend/class-user-portal.php` (50% hecho)
- ✅ `/includes/frontend/class-portal-profiles.php` (50% hecho)
- ⬜ `/includes/modules/tramites/frontend/class-tramites-frontend-controller.php`
- ⬜ `/includes/modules/tramites/templates/*.php` (eliminar `$tramites_base_url`)
- ⬜ `/includes/modules/socios/frontend/class-socios-frontend-controller.php`
- ⬜ `/includes/modules/socios/class-socios-dashboard-tab.php`

**P1 - Alto (2 días):**
- ⬜ `/includes/modules/grupos-consumo/frontend/class-gc-frontend-controller.php`
- ⬜ `/templates/catalogo.php`

**Referencia completa:** `reports/AUDITORIA-UX-NAVEGACION-2026-03-22.md`

---

### CAPA 1: Arquitectura de Información

**Estado:** ⬜ 0% implementado (solo documentado)
**Prioridad:** P1 (Alto - habilita contextos)

#### Jerarquías de Módulos

Según `MATRIZ-JERARQUIAS-INTERCONEXIONES-MODULOS-2026-03-22.md`:

```
Nivel 0: Portal
  └─ mi-portal

Nivel 1: Hubs y Contenedores
  ├─ comunidades
  ├─ colectivos
  ├─ socios
  └─ network-communities

Nivel 2: Ecosistemas Funcionales
  ├─ gobernanza (participacion, transparencia, presupuestos-participativos...)
  ├─ conocimiento (biblioteca, cursos, talleres, saberes-ancestrales...)
  ├─ economia (marketplace, grupos-consumo, banco-tiempo...)
  ├─ territorio (espacios-comunes, reservas, huertos-urbanos...)
  ├─ conversacion (foros, chat-grupos, red-social...)
  └─ servicios (incidencias, tramites, facturas...)

Nivel 3: Módulos Operativos
  └─ Los 67 módulos concretos
```

#### Contextos y Composiciones

Según `MATRIZ-COMPATIBILIDAD-CONTEXTOS-MODULOS-2026-03-22.md`:

**Modelo de Composición:**
```
Contexto Contenedor + Módulo Base + Módulos Transversales + Alcance
```

**Ejemplo: Grupo de Consumo**
```yaml
contexto: cooperativa_consumo
modulo_base: grupos-consumo
modulos_nucleares:
  - socios
  - eventos
  - chat-grupos
modulos_recomendados:
  - participacion
  - marketplace
  - facturas
  - documentacion-legal
modulos_opcionales:
  - red-social
  - foros
  - recetas
  - economia-suficiencia
```

**Ejemplo: Ayuntamiento**
```yaml
contexto: municipio
modulos_base:
  - tramites
  - incidencias
  - participacion
  - transparencia
modulos_nucleares:
  - avisos-municipales
  - documentacion-legal
  - eventos
modulos_recomendados:
  - encuestas
  - presupuestos-participativos
  - foros
  - mapa-actores
```

#### Implementación en UX

**1. Portal Adaptativo por Contexto**

Actualmente `class-portal-profiles.php` tiene 6 perfiles:
- ✅ consumidor
- ✅ comunidad
- ✅ barrio
- ✅ coworking
- ✅ cooperativa
- ✅ academia

**Mejora necesaria:**
```php
// En lugar de hardcodear todo en el PHP, usar configuración dinámica
$configuracion_contexto = Flavor_Context_Manager::get_active_context();
// Returns: ['base_modules' => [...], 'nuclear' => [...], 'recommended' => [...]]

// Construir navegación y CTAs dinámicamente según contexto
$nav_items = Flavor_Navigation_Builder::build_for_context($configuracion_contexto);
```

**2. Navegación con Jerarquías**

```
Mi Portal
├─ 🏛️ Mi Comunidad (hub nivel 1)
│   ├─ 👥 Socios
│   ├─ 📅 Eventos
│   ├─ 🗳️ Participación
│   └─ 📊 Transparencia
│
├─ 🛒 Economía Comunitaria (ecosistema nivel 2)
│   ├─ 🥬 Grupos de Consumo
│   ├─ 🛍️ Marketplace
│   ├─ ⏰ Banco de Tiempo
│   └─ 💶 Economía del Don
│
└─ 🌱 Territorio y Sostenibilidad (ecosistema nivel 2)
    ├─ 🏞️ Espacios Comunes
    ├─ 🚲 Bicicletas Compartidas
    ├─ 🌿 Huertos Urbanos
    └─ ♻️ Reciclaje y Compostaje
```

**3. Relaciones Visibles entre Módulos**

En cada módulo, mostrar **módulos relacionados**:

```
┌─────────────────────────────────────┐
│ GRUPOS DE CONSUMO                   │
│ Dashboard principal                 │
│                                     │
│ [Mi pedido actual]                  │
│ [Próximo ciclo]                     │
│                                     │
│ 🔗 RELACIONADO:                     │
│  • 👥 Mi Grupo (socios)             │
│  • 📅 Próxima asamblea (eventos)    │
│  • 🍳 Recetas con productos         │
│  • 💬 Foro del grupo                │
│  • 📊 Transparencia económica       │
└─────────────────────────────────────┘
```

#### Tareas de Implementación

**Fase 1 - Fundamentos (5 días):**
1. ⬜ Crear `class-context-manager.php`
   - Detectar contexto activo (comunidad, cooperativa, ayuntamiento...)
   - Cargar configuración de módulos por contexto
   - API: `get_active_context()`, `get_modules_for_context()`

2. ⬜ Crear `class-navigation-builder.php`
   - Construir navegación jerárquica dinámica
   - Agrupar módulos por ecosistema
   - API: `build_for_context()`, `get_breadcrumbs()`

3. ⬜ Crear `class-module-relations.php` (ya existe parcialmente)
   - Obtener módulos relacionados para uno dado
   - Renderizar widget "Relacionado"
   - API: `get_related_modules($module_id)`

**Fase 2 - UX (3 días):**
4. ⬜ Refactorizar `class-portal-profiles.php`
   - Usar Context Manager en lugar de arrays hardcodeados
   - Navegación dinámica basada en jerarquías

5. ⬜ Crear componente `module-relations-widget.php`
   - Widget reutilizable "Módulos relacionados"
   - Aplicar a todos los dashboards de módulos

---

### CAPA 2: Componentes Visuales Reutilizables

**Estado:** ⚠️ 50% completado
**Prioridad:** P2 (Medio - mejora experiencia)

#### 2A. Dashboards Admin (✅ COMPLETADO 21/03)

**Referencia:** `reports/MEJORAS-UX-UI-DASHBOARDS-2026-03-21.md`

**Componentes disponibles:**
- ✅ `stat_card()` - Tarjetas de estadísticas
- ✅ `stats_grid()` - Grid responsivo
- ✅ `data_table()` - Tablas mejoradas
- ✅ `badge()` - Badges de estado
- ✅ `alert()` - Alertas descartables
- ✅ `progress_bar()` - Barras de progreso
- ✅ `section()` - Secciones colapsables
- ✅ `empty_state()` - Estados vacíos
- ✅ `mini_chart()` - Mini gráficos CSS

**Archivos:**
- ✅ `includes/dashboard/class-dashboard-components.php`
- ✅ `assets/css/dashboard-components-enhanced.css`
- ✅ `assets/js/dashboard-components.js`

**Estado:** Ya implementado y funcionando en dashboards admin.

#### 2B. Componentes Frontend (⬜ 40% hecho)

**Referencia:** `docs/PLAN-MEJORAS-FRONTEND-UX.md`

**Componentes creados:**
- ✅ Empty State Component
  - PHP: `templates/components/shared/empty-state.php`
  - CSS: `assets/css/components/empty-state.css`
  - Función: `flavor_render_empty_state()`

- ✅ Form Wizard Component
  - JS: `assets/js/components/form-wizard.js`
  - CSS: `assets/css/components/form-wizard.css`
  - Clase: `FlavorFormWizard`

**Componentes pendientes:**
- ⬜ Advanced Search Component
- ⬜ Responsive Grid Component (CSS)
- ⬜ Filter Bar Component

#### Aplicación a Módulos

**Prioridad de aplicación (según análisis):**

| Módulo | Empty States | Form Wizard | Search | Priority |
|--------|--------------|-------------|--------|----------|
| Eventos | ⬜ | ⬜ | ⬜ | P1 |
| Grupos-Consumo | ⬜ | ⬜ | ⬜ | P1 |
| Incidencias | ⬜ | ⬜ | ⬜ | P1 |
| Biblioteca | ⬜ | ⬜ | ⬜ | P1 |
| Comunidades | ⬜ | ⬜ | ⬜ | P2 |
| Marketplace | ⬜ | ⬜ | ⬜ | P2 |
| Talleres | ⬜ | ⬜ | ⬜ | P2 |

**Tareas:**

**Fase 1 - Completar Componentes (2 días):**
1. ⬜ Crear Advanced Search Component
2. ⬜ Crear Responsive Grid CSS
3. ⬜ Crear Filter Bar Component
4. ⬜ Documentar uso en guía única

**Fase 2 - Aplicar a P1 (3 días):**
5. ⬜ Aplicar Empty State a Eventos, Grupos-Consumo, Incidencias, Biblioteca
6. ⬜ Aplicar Form Wizard a formularios largos (crear incidencia, crear evento, etc.)
7. ⬜ Testing de usabilidad

**Fase 3 - Aplicar a P2 (2 días):**
8. ⬜ Resto de módulos

---

### CAPA 3: Experiencias Completas por Contexto

**Estado:** ⬜ 0% implementado
**Prioridad:** P3 (Bajo - integración final)

Una vez completadas las capas 0-2, crear **experiencias completas** para cada contexto:

#### Contexto 1: Grupo de Consumo Ecológico

**Perfil objetivo:** Cooperativa de consumo responsable

**Módulos activos:**
- Base: grupos-consumo
- Nucleares: socios, eventos, chat-grupos
- Recomendados: participacion, marketplace, facturas, recetas
- Opcionales: red-social, foros, economia-suficiencia

**Navegación adaptada:**
```
Mi Cooperativa
├─ 🥬 Mis Pedidos
│   ├─ Ciclo actual
│   ├─ Histórico
│   └─ Catálogo
├─ 👥 Mi Grupo
│   ├─ Miembros
│   ├─ Asambleas (eventos)
│   └─ Transparencia
├─ 🍳 Recetas
├─ 💬 Comunidad
│   ├─ Chat del grupo
│   └─ Foros
└─ 📊 Gestión
    ├─ Mi cuota (socios)
    └─ Documentos
```

**Portada personalizada:**
- Widget "Tu próximo pedido"
- Widget "Próxima asamblea"
- Stats del grupo
- Recetas destacadas
- Actividad reciente

#### Contexto 2: Ayuntamiento Digital

**Perfil objetivo:** Ciudadano interactuando con administración

**Módulos activos:**
- Base: tramites, incidencias, participacion, transparencia
- Nucleares: avisos-municipales, documentacion-legal, eventos
- Recomendados: encuestas, presupuestos-participativos, foros

**Navegación adaptada:**
```
Mi Ayuntamiento
├─ 📋 Trámites
│   ├─ Mis trámites
│   ├─ Iniciar trámite
│   └─ Citas previas
├─ ⚠️ Incidencias
│   ├─ Mis reportes
│   └─ Reportar nueva
├─ 🗳️ Participar
│   ├─ Votaciones activas
│   ├─ Propuestas
│   └─ Presupuestos participativos
├─ 📢 Avisos
└─ 📊 Transparencia
    ├─ Presupuestos
    └─ Documentos públicos
```

**Portada personalizada:**
- Widget "Trámites pendientes"
- Widget "Votaciones activas"
- Avisos urgentes
- Calendario de eventos municipales

#### Contexto 3: Barrio Solidario

**Perfil objetivo:** Vecino/a participando en comunidad local

**Módulos activos:**
- Base: ayuda-vecinal, comunidades
- Nucleares: banco-tiempo, chat-grupos, eventos
- Recomendados: bicicletas-compartidas, huertos-urbanos, incidencias
- Opcionales: red-social, foros, compostaje

**Navegación adaptada:**
```
Mi Barrio
├─ 🤝 Ayuda Vecinal
│   ├─ Pedir ayuda
│   ├─ Ofrecer ayuda
│   └─ Mi círculo
├─ ⏰ Banco de Tiempo
│   ├─ Mi saldo
│   ├─ Servicios
│   └─ Intercambios
├─ 🌱 Ecología Local
│   ├─ Huertos
│   ├─ Compostaje
│   └─ Reciclaje
├─ 🚲 Movilidad
│   ├─ Bicis compartidas
│   └─ Carpooling
└─ 💬 Comunidad
    ├─ Chat del barrio
    └─ Eventos vecinales
```

#### Tareas de Implementación

**Fase 1 - Plantillas de Contexto (5 días):**
1. ⬜ Crear plantillas para 5 contextos principales:
   - Grupo de consumo
   - Ayuntamiento
   - Barrio
   - Coworking
   - Cooperativa

2. ⬜ Portadas personalizadas con widgets adaptativos

**Fase 2 - Testing (2 días):**
3. ⬜ Pruebas con usuarios reales de cada contexto
4. ⬜ Ajustes según feedback

---

## 📅 PLAN DE IMPLEMENTACIÓN

### Resumen de Fases

| Fase | Componente | Tiempo | Prioridad | Dependencias |
|------|-----------|--------|-----------|--------------|
| **0.1** | Capa 0: Mi-Portal (completar) | 1 día | P0 | Ninguna |
| **0.2** | Capa 0: Trámites | 1.5 días | P0 | 0.1 |
| **0.3** | Capa 0: Socios | 1 día | P0 | 0.1 |
| **0.4** | Capa 0: Grupos-Consumo | 1 día | P1 | 0.1-0.3 |
| **1.1** | Capa 1: Context Manager | 2 días | P1 | 0.1-0.3 |
| **1.2** | Capa 1: Navigation Builder | 2 días | P1 | 1.1 |
| **1.3** | Capa 1: Module Relations | 1 día | P1 | 1.1 |
| **1.4** | Capa 1: Aplicar a UX | 3 días | P1 | 1.1-1.3 |
| **2.1** | Capa 2: Completar componentes frontend | 2 días | P2 | 0.1-0.4 |
| **2.2** | Capa 2: Aplicar a módulos P1 | 3 días | P2 | 2.1 |
| **2.3** | Capa 2: Aplicar a módulos P2 | 2 días | P2 | 2.2 |
| **3.1** | Capa 3: Plantillas contexto | 5 días | P3 | 0-2 completos |
| **3.2** | Capa 3: Testing con usuarios | 2 días | P3 | 3.1 |

**TOTAL ESTIMADO: 26-28 días de trabajo**

---

### Secuencia Recomendada

```
SPRINT 1 (5 días) - FUNDAMENTO
├─ Fase 0.1: Completar Mi-Portal (1d)
├─ Fase 0.2: Refactor Trámites (1.5d)
├─ Fase 0.3: Refactor Socios (1d)
└─ Fase 0.4: Refactor Grupos-Consumo (1d)
    ✅ Objetivo: Navegación 100% canónica

SPRINT 2 (8 días) - ARQUITECTURA INFORMACIÓN
├─ Fase 1.1: Context Manager (2d)
├─ Fase 1.2: Navigation Builder (2d)
├─ Fase 1.3: Module Relations (1d)
└─ Fase 1.4: Aplicar a UX (3d)
    ✅ Objetivo: Jerarquías y contextos funcionando

SPRINT 3 (7 días) - COMPONENTES VISUALES
├─ Fase 2.1: Completar componentes (2d)
├─ Fase 2.2: Aplicar a módulos P1 (3d)
└─ Fase 2.3: Aplicar a módulos P2 (2d)
    ✅ Objetivo: UX consistente en todo el sistema

SPRINT 4 (7 días) - INTEGRACIÓN FINAL
├─ Fase 3.1: Plantillas por contexto (5d)
└─ Fase 3.2: Testing con usuarios (2d)
    ✅ Objetivo: Experiencias completas listas
```

---

## 🎯 ROADMAP Y PRIORIDADES

### Prioridad P0 - CRÍTICO (Sprint 1)

**Bloqueante:** Sin esto, las capas superiores no tienen sentido.

- ⬜ Completar refactor de navegación canónica
- ⬜ 100% de URLs usando helpers
- ⬜ Eliminar URLs fuera de `/mi-portal/`

**Entregable:** Navegación consistente en toda la plataforma.

**Métrica de éxito:** 0 URLs hardcodeadas, 100% helper usage.

---

### Prioridad P1 - ALTO (Sprint 2)

**Habilitador:** Permite experiencias adaptativas por contexto.

- ⬜ Context Manager funcional
- ⬜ Navigation Builder con jerarquías
- ⬜ Relaciones entre módulos visibles

**Entregable:** Portal que se adapta al contexto del usuario.

**Métrica de éxito:** Usuario identifica claramente su contexto y módulos relacionados.

---

### Prioridad P2 - MEDIO (Sprint 3)

**Pulido:** Mejora la experiencia visual.

- ⬜ Componentes frontend completos
- ⬜ Empty states en todos los módulos
- ⬜ Form wizards en formularios largos

**Entregable:** UX consistente y profesional.

**Métrica de éxito:** Reducción 50% en abandono de formularios, feedback positivo.

---

### Prioridad P3 - BAJO (Sprint 4)

**Integración:** Experiencias completas listas para producción.

- ⬜ Plantillas por contexto
- ⬜ Testing con usuarios reales

**Entregable:** 5 experiencias contextuales listas para deploy.

**Métrica de éxito:** Satisfacción usuario >8/10 en cada contexto.

---

## 📊 MÉTRICAS DE ÉXITO

### Capa 0 - Navegación

| Métrica | Antes | Objetivo |
|---------|-------|----------|
| Módulos con 100% helper usage | 0/67 | 67/67 |
| URLs fuera de /mi-portal/ | 15+ | 0 |
| Archivos con URLs hardcodeadas | 20+ | 0 |
| Tests unitarios de navegación | 0 | 20+ |

### Capa 1 - Arquitectura Información

| Métrica | Antes | Objetivo |
|---------|-------|----------|
| Contextos configurables | 0 | 5+ |
| Navegación jerárquica | No | Sí |
| Relaciones módulos visibles | No | Sí |
| Usuarios que entienden su contexto | <30% | >80% |

### Capa 2 - Componentes Visuales

| Métrica | Antes | Objetivo |
|---------|-------|----------|
| Módulos con empty states mejorados | 0/67 | 67/67 |
| Formularios con wizard | 0 | 15+ |
| Consistencia visual (encuesta) | 3.2/5 | >4.5/5 |
| Abandono de formularios | ~40% | <20% |

### Capa 3 - Experiencias Contextuales

| Métrica | Antes | Objetivo |
|---------|-------|----------|
| Contextos con portada personalizada | 0 | 5 |
| Satisfacción usuario (NPS) | ? | >8/10 |
| Módulos "relacionados" mostrados | 0% | 100% |

---

## 📚 REFERENCIAS

### Documentación Base

1. **Navegación (Capa 0):**
   - `reports/AUDITORIA-UX-NAVEGACION-2026-03-22.md` - Análisis completo de problemas
   - `reports/PROGRESO-REFACTOR-NAVEGACION-2026-03-22.md` - Tracking en tiempo real
   - `includes/class-helpers.php` (líneas 393-450) - Helper central

2. **Arquitectura Información (Capa 1):**
   - `reports/MATRIZ-JERARQUIAS-INTERCONEXIONES-MODULOS-2026-03-22.md` - Jerarquías y relaciones
   - `reports/MATRIZ-COMPATIBILIDAD-CONTEXTOS-MODULOS-2026-03-22.md` - Composiciones por contexto
   - `reports/SISTEMA-INTEGRACION-MODULOS-2026-03-22.md` - Sistema de integración

3. **Componentes Visuales (Capa 2):**
   - `reports/MEJORAS-UX-UI-DASHBOARDS-2026-03-21.md` - Dashboards admin (HECHO)
   - `docs/PLAN-MEJORAS-FRONTEND-UX.md` - Componentes frontend
   - `includes/dashboard/class-dashboard-components.php` - Componentes admin

4. **Bundles Modulares:**
   - `reports/CATALOGO-BUNDLES-MODULARES-UX-2026-03-22.md` - Catálogo de bundles por tipo de cliente

5. **Otros:**
   - `admin/README-RELACIONES-MODULOS.md` - Guía admin de relaciones
   - `docs/SISTEMA-RELACIONES-MODULOS.md` - Documentación técnica

### Bundles Modulares

**Referencia:** `reports/CATALOGO-BUNDLES-MODULARES-UX-2026-03-22.md`

El catálogo define **bundles preconfigured** para diferentes tipos de clientes:

| Bundle | Módulos Base | Cliente Ideal | Implementación |
|--------|--------------|---------------|----------------|
| **Comunidad Base** | comunidades | Asociaciones, colectivos pequeños | ⬜ Crear preset |
| **Gobernanza Cooperativa** | participacion, transparencia | Cooperativas con asambleas | ⬜ Crear preset |
| **Economía Comunitaria** | grupos-consumo, marketplace | Grupos de consumo, mercados | ⬜ Crear preset |
| **Territorio Vecinal** | ayuda-vecinal, incidencias | Barrios, vecindarios | ⬜ Crear preset |
| **Conocimiento y Cultura** | cursos, biblioteca, talleres | Academias, centros culturales | ⬜ Crear preset |
| **Gestión Municipal** | tramites, avisos-municipales | Ayuntamientos | ⬜ Crear preset |
| **Trabajo y Empresa** | clientes, facturas, contabilidad | Empresas, freelance | ⬜ Crear preset |
| **Sostenibilidad** | huertos-urbanos, compostaje | Proyectos ecológicos | ⬜ Crear preset |

**Implementación técnica:**

```php
// Nuevo archivo: includes/bundles/class-bundle-manager.php

class Flavor_Bundle_Manager {

    /**
     * Obtiene configuración de un bundle
     */
    public static function get_bundle_config($bundle_id) {
        $bundles = [
            'comunidad-base' => [
                'nombre' => 'Comunidad Base',
                'modulos_base' => ['comunidades'],
                'modulos_nucleares' => ['socios', 'eventos', 'red-social', 'chat-grupos', 'foros'],
                'modulos_recomendados' => ['colectivos', 'biblioteca', 'cursos'],
                'modulos_transversales' => ['multimedia', 'email-marketing'],
            ],
            'economia-comunitaria' => [
                'nombre' => 'Economía Comunitaria',
                'modulos_base' => ['grupos-consumo', 'marketplace'],
                'modulos_nucleares' => ['banco-tiempo', 'socios', 'chat-grupos', 'facturas'],
                'modulos_recomendados' => ['economia-don', 'economia-suficiencia', 'trabajo-digno'],
                'modulos_transversales' => ['red-social', 'documentacion-legal'],
            ],
            // ... resto de bundles
        ];

        return $bundles[$bundle_id] ?? null;
    }

    /**
     * Activa un bundle completo
     */
    public static function activate_bundle($bundle_id, $include_recommended = true) {
        $config = self::get_bundle_config($bundle_id);
        if (!$config) return false;

        $modulos_activar = array_merge(
            $config['modulos_base'],
            $config['modulos_nucleares']
        );

        if ($include_recommended) {
            $modulos_activar = array_merge($modulos_activar, $config['modulos_recomendados']);
        }

        // Activar módulos
        foreach ($modulos_activar as $modulo) {
            Flavor_Chat_Module_Loader::activate_module($modulo);
        }

        // Configurar contexto
        Flavor_Context_Manager::set_context($bundle_id);

        return true;
    }

    /**
     * Obtiene bundles sugeridos según módulos activos
     */
    public static function suggest_bundle() {
        $active_modules = get_option('flavor_active_modules', []);

        // Analizar qué bundle se ajusta mejor
        $scores = [];
        foreach (self::get_all_bundles() as $bundle_id => $config) {
            $match = array_intersect($active_modules, array_merge(
                $config['modulos_base'],
                $config['modulos_nucleares']
            ));
            $scores[$bundle_id] = count($match);
        }

        arsort($scores);
        return array_key_first($scores);
    }
}
```

**Uso en Setup Wizard:**

```php
// Durante onboarding del sitio
$bundle_seleccionado = $_POST['bundle']; // 'comunidad-base', 'economia-comunitaria', etc.
Flavor_Bundle_Manager::activate_bundle($bundle_seleccionado, $include_recommended = true);

// Crea automáticamente:
// - Activa módulos del bundle
// - Configura contexto
// - Crea páginas necesarias
// - Configura navegación
```

---

### Repositorio de Código

```
/includes/
├── bundles/
│   └── [NUEVO] class-bundle-manager.php
├── frontend/
│   ├── class-user-portal.php            (navegación portal)
│   ├── class-portal-profiles.php        (perfiles contextuales)
│   ├── class-unified-portal.php         (portal unificado)
│   └── [NUEVO] class-context-manager.php
│   └── [NUEVO] class-navigation-builder.php
├── modules/
│   └── class-module-relations-helper.php (relaciones módulos)
├── dashboard/
│   └── class-dashboard-components.php   (componentes admin ✅)
└── class-helpers.php                    (helpers centrales)

/templates/components/shared/
├── empty-state.php                      (✅ creado)
└── [NUEVO] module-relations-widget.php

/assets/
├── css/components/
│   ├── empty-state.css                  (✅ creado)
│   ├── form-wizard.css                  (✅ creado)
│   └── [NUEVO] filter-bar.css
├── js/components/
│   ├── form-wizard.js                   (✅ creado)
│   └── [NUEVO] advanced-search.js
```

---

## ✅ CHECKLIST DE CALIDAD FINAL

Antes de considerar completado el plan:

### Capa 0 - Navegación
- [ ] Todas las URLs usan helpers centralizados
- [ ] 0 URLs fuera de `/mi-portal/` (excepto landing público)
- [ ] Tests unitarios >80% cobertura en helpers
- [ ] Sin errores 404 en navegación interna

### Capa 1 - Arquitectura
- [ ] Context Manager funcional con 5+ contextos
- [ ] Navegación se adapta al contexto activo
- [ ] Relaciones módulos visibles en UI
- [ ] Breadcrumbs jerárquicos en todos los módulos

### Capa 2 - Componentes
- [ ] Empty states en 100% de módulos
- [ ] Form wizards en formularios >10 campos
- [ ] Componentes documentados con ejemplos
- [ ] CSS responsive en todos los componentes

### Capa 3 - Experiencias
- [ ] 5 plantillas de contexto completas
- [ ] Portadas personalizadas funcionales
- [ ] Testing con usuarios reales completado
- [ ] Feedback positivo (NPS >8)

---

**Última actualización:** 2026-03-22
**Responsable:** Claude Code
**Estado:** 📋 Plan maestro consolidado, listo para ejecutar Sprint 1

**Próximo paso:** Completar Fase 0.1 (Mi-Portal al 100%)
