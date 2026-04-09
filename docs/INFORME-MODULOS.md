# Informe de Estado de Módulos - Flavor Platform

**Fecha**: 2026-04-09
**Versión**: 3.5.0

---

## Resumen Ejecutivo

| Plataforma | Total Módulos | Con API REST | Con Frontend |
|------------|---------------|--------------|--------------|
| **PHP (WordPress)** | 66 | 67 | 64 |
| **Flutter (Mobile)** | 55 | 55 | 55 |
| **Comunes** | 53 | - | - |

---

## 1. Módulos Solo en PHP (sin app móvil)

Estos módulos existen en WordPress pero **no tienen pantalla Flutter**:

| Módulo | Archivos PHP | Estado | Prioridad App |
|--------|-------------|--------|---------------|
| `encuestas` | 5 | ✅ Completo (API + Frontend) | 🔴 Alta |
| `campanias` | 2 | ✅ Completo | 🟡 Media |
| `crowdfunding` | 2 | ✅ Completo | 🟡 Media |
| `recetas` | 1 | ⚠️ Básico | 🟢 Baja |
| `seguimiento-denuncias` | 1 | ⚠️ Básico | 🟡 Media |
| `documentacion-legal` | 1 | ⚠️ Básico | 🟢 Baja |
| `kulturaka` | 2 | ✅ Completo | 🟢 Baja |
| `mapa-actores` | 1 | ⚠️ Básico | 🟢 Baja |
| `agregador-contenido` | 1 | ⚠️ Básico | 🟢 Baja |
| `bug-tracker` | 6 | ✅ Completo (interno) | ❌ No aplica |
| `chat-estados` | 2 | ✅ Completo | 🔴 Alta |
| `contabilidad` | 1 | ⚠️ Básico | 🟢 Baja |
| `empresas` | 1 | ⚠️ Básico | 🟢 Baja |

### Recomendación
Crear pantallas Flutter para: **encuestas**, **chat-estados**, **campanias**, **crowdfunding**

---

## 2. Módulos Solo en Flutter (sin backend PHP dedicado)

| Módulo | Líneas Dart | Usa API de |
|--------|-------------|-----------|
| `chat` | 878 | API genérica de chat |
| `recursos-compartidos` | 541 | API compartida |

---

## 3. Top 10 Módulos PHP Más Completos

| # | Módulo | Archivos | Características |
|---|--------|----------|-----------------|
| 1 | `grupos-consumo` | 56 | API, pagos, suscripciones, Telegram, WhatsApp |
| 2 | `email-marketing` | 24 | Campañas, automatización, tracking, listas |
| 3 | `incidencias` | 24 | API, dashboard, notificaciones |
| 4 | `espacios-comunes` | 24 | Reservas, calendario, gestión |
| 5 | `banco-tiempo` | 23 | API móvil, intercambios, balance |
| 6 | `socios` | 21 | API, pagos, suscripciones, perfiles |
| 7 | `eventos` | 21 | API, inscripciones, calendario |
| 8 | `talleres` | 18 | API, inscripciones, materiales |
| 9 | `dex-solana` | 14 | Jupiter API, swaps, portfolio |
| 10 | `trading-ia` | 13 | Paper trading, indicadores, riesgo |

---

## 4. Estado de Módulos Flutter

### 4.1 Módulos Completos (CRUD + Detalle + Formularios)

| Módulo | Archivos | Funcionalidades |
|--------|----------|-----------------|
| `chat` | 7+ (subdirs) | Conversaciones, llamadas, estados, grupos |
| `foros` | 5 + widgets | Listado, detalle, búsqueda, crear |
| `red_social` | 4 + widgets | Feed, publicaciones, comentarios |
| `eventos` | 4 | CRUD, detalle, inscripciones |
| `marketplace` | 3 | CRUD, formulario producto |
| `banco_tiempo` | 4 | CRUD, formulario intercambio |
| `talleres` | 3 | CRUD, inscripciones |
| `cursos` | 3 | CRUD, contenido |
| `reservas` | 3 | CRUD, calendario |
| `socios` | 2 | CRUD, perfil |

### 4.2 Módulos con UI Completa (sin CRUD)

| Módulo | Líneas | Tipo |
|--------|--------|------|
| `huertos_urbanos` | 751 | Dashboard + mapa |
| `sello_conciencia` | 724 | Certificaciones |
| `energia_comunitaria` | 688 | Dashboard métricas |
| `advertising` | 674 | Gestión anuncios |
| `email_marketing` | 664 | Campañas |
| `dex_solana` | 645 | Trading dashboard |
| `podcast` | 615 | Reproductor |
| `trading_ia` | 598 | Dashboard trading |

### 4.3 Módulos Básicos (Solo Listado)

| Módulo | Líneas | Pendiente |
|--------|--------|-----------|
| `chat_grupos` | 162 | Integrar con `chat/` |
| `facturas` | 177 | Detalle, filtros |
| `chat_interno` | 179 | Integrar con `chat/` |
| `woocommerce` | 187 | Gestión pedidos |
| `circulos_cuidados` | 209 | Formularios |
| `saberes_ancestrales` | 217 | Detalle contenido |
| `bares` | 232 | Reservas, menú |

---

## 5. TODOs Pendientes

### 5.1 Flutter (54 TODOs en chat/)

```
chat/screens/call_screen.dart - Llamadas VoIP
chat/screens/status_screen.dart - Subida de estados
chat/widgets/chat_input.dart - Selector emojis/contactos
chat/screens/group_info_screen.dart - Gestión miembros
```

### 5.2 PHP (31 TODOs distribuidos)

| Módulo | TODOs | Área |
|--------|-------|------|
| `encuestas` | 6 | Features trait |
| `radio` | 4 | Streaming |
| `tramites` | 3 | Workflow |
| `compostaje` | 3 | Métricas |
| `avisos-municipales` | 3 | Notificaciones |

---

## 6. Matriz de Funcionalidad

| Módulo | PHP API | PHP Frontend | Flutter Screen | Flutter CRUD |
|--------|---------|--------------|----------------|--------------|
| grupos-consumo | ✅ | ✅ | ✅ | ✅ |
| eventos | ✅ | ✅ | ✅ | ✅ |
| marketplace | ✅ | ✅ | ✅ | ✅ |
| socios | ✅ | ✅ | ✅ | ✅ |
| banco-tiempo | ✅ | ✅ | ✅ | ✅ |
| talleres | ✅ | ✅ | ✅ | ✅ |
| cursos | ✅ | ✅ | ✅ | ✅ |
| reservas | ✅ | ✅ | ✅ | ✅ |
| incidencias | ✅ | ✅ | ✅ | ⚠️ |
| foros | ✅ | ✅ | ✅ | ✅ |
| espacios-comunes | ✅ | ✅ | ✅ | ⚠️ |
| tramites | ✅ | ✅ | ✅ | ⚠️ |
| transparencia | ✅ | ✅ | ✅ | ❌ |
| participacion | ✅ | ✅ | ✅ | ⚠️ |
| encuestas | ✅ | ✅ | ❌ | ❌ |
| campanias | ✅ | ✅ | ❌ | ❌ |
| crowdfunding | ✅ | ✅ | ❌ | ❌ |
| chat-estados | ✅ | ✅ | ❌ | ❌ |

**Leyenda**: ✅ Completo | ⚠️ Parcial | ❌ No existe

---

## 7. Recomendaciones de Desarrollo

### 7.1 Prioridad Alta (Impacto Usuario)

1. **Crear pantalla Flutter para `encuestas`**
   - API PHP existe y es completa
   - Funcionalidad muy usada en comunidades

2. **Crear pantalla Flutter para `chat-estados`**
   - Similar a estados de WhatsApp
   - API PHP existe

3. **Completar CRUD en `incidencias` Flutter**
   - Falta formulario de creación
   - Falta edición de estado

4. **Integrar `chat_grupos` y `chat_interno` con módulo `chat`**
   - Evitar duplicación de código
   - Reutilizar componentes

### 7.2 Prioridad Media

5. **Crear pantalla Flutter para `crowdfunding`**
6. **Crear pantalla Flutter para `campanias`**
7. **Completar CRUD en `espacios-comunes` Flutter**
8. **Añadir gestión de pedidos en `woocommerce` Flutter**

### 7.3 Prioridad Baja

9. Crear pantallas para módulos culturales (`kulturaka`, `recetas`)
10. Mejorar `transparencia` Flutter con gráficos
11. Añadir `mapa-actores` a Flutter

---

## 8. Estadísticas de Código

### PHP
- **Total archivos PHP en módulos**: ~450
- **Líneas de código estimadas**: ~85,000
- **Módulos con tests**: 5

### Flutter
- **Total archivos Dart en módulos**: ~180
- **Líneas de código estimadas**: ~45,000
- **Módulos con >500 líneas**: 18

---

## 9. Conclusión

El sistema tiene una buena cobertura de módulos entre PHP y Flutter, con **53 módulos comunes** funcionando en ambas plataformas.

**Gaps principales**:
1. 13 módulos PHP sin app móvil (4 prioritarios)
2. 54 TODOs en el módulo chat de Flutter
3. Varios módulos Flutter sin CRUD completo

**Fortalezas**:
1. API REST completa en PHP para todos los módulos
2. Arquitectura consistente con providers en Flutter
3. Sistema de lazy loading implementado

---

*Generado automáticamente por Claude Code*
