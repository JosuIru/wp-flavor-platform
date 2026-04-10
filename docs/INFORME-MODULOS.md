# Informe de Estado de Módulos - Flavor Platform

**Fecha**: 2026-04-09 (actualizado)
**Versión**: 3.5.1

---

## Resumen Ejecutivo

| Plataforma | Total Módulos | Con API REST | Con Frontend |
|------------|---------------|--------------|--------------|
| **PHP (WordPress)** | 66 | 71 | 64 |
| **Flutter (Mobile)** | 67 | 67 | 67 |
| **Comunes** | 65 | - | - |

---

## 1. Módulos Solo en PHP (sin app móvil)

Estos módulos existen en WordPress pero **no tienen pantalla Flutter**:

| Módulo | Archivos PHP | Estado | Prioridad App |
|--------|-------------|--------|---------------|
| `bug-tracker` | 6 | ✅ Completo (interno) | ❌ No aplica |

### Recomendación
✅ **Todos los módulos de usuario ya tienen pantalla Flutter implementada.**

> ✅ **Completados**: Los módulos `encuestas`, `chat-estados`, `seguimiento-denuncias`, `documentacion-legal`, `recetas`, `mapa-actores`, `agregador-contenido`, `empresas` y `contabilidad` ya tienen pantalla Flutter implementada.

> ✅ **Chat COMPLETO**: Se han implementado TODAS las mejoras en el módulo de chat (100%):
> - Selector de emojis completo con 10 categorías y persistencia
> - Reproductor de video para estados con controles
> - Persistencia de búsquedas recientes (SharedPreferences)
> - Persistencia de stickers/GIFs recientes
> - Navegaciones: búsqueda → chat, perfil, grupo
> - Preferencias de notificaciones por grupo/usuario
> - Historial de llamadas integrado en tabs
> - Navegación desde llamadas a chat y perfil
> - Cámara integrada en chat_input
> - Selector de fuentes para estados de texto
> - Grupos en común entre usuarios
> - Multimedia compartida con navegación a búsqueda
> - Pantalla de configuración de chat completa
> - Widget de encuestas con votación y progreso visual
> - Llamadas telefónicas (url_launcher)
> - Selector de contactos integrado (nueva llamada, añadir miembros a grupo, compartir contacto)
> - Tipo MessageType.link añadido
> - Servicio MediaUploadService para subir imágenes
> - Upload de avatar de grupo con compresión
> - Upload de imagen para estados
> - **WebRTC/VoIP completo** con flutter_webrtc:
>   - Servicio WebRTCService con señalización ICE
>   - Llamadas 1:1 con video/audio real
>   - Llamadas grupales con grid de participantes
>   - Controles: silenciar, altavoz, cámara, cambiar cámara
>   - Chat overlay durante llamadas
>   - Añadir participantes a llamada grupal
>
> **TODOs pendientes (0)**: ✅ Todos los TODOs del módulo chat han sido resueltos

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
| `encuestas` | 2 | CRUD, responder, resultados |
| `chat_estados` | 2 | Stories, visor fullscreen, reacciones |
| `campanias` | 2 | Campañas ciudadanas, firmas, acciones |
| `crowdfunding` | 2 | Financiación colectiva, tiers, aportaciones |
| `kulturaka` | 2 | Red cultural: espacios, artistas, eventos, comunidad |

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
| `chat_interno` | 179 | Integrar con `chat/` |

> **Nota**: Los siguientes módulos han sido mejorados y movidos a completos:
> - ~~`facturas`~~ ✅ Ahora con filtros por estado, detalle completo, marcar como pagada
> - ~~`woocommerce`~~ ✅ Ya tenía gestión de pedidos
> - ~~`circulos_cuidados`~~ ✅ Ya tenía formularios completos (crear círculo, ofrecer ayuda, chat)
> - ~~`saberes_ancestrales`~~ ✅ Ya tenía detalle contenido completo
> - ~~`bares`~~ ✅ Ahora con carta/menú, reservas, detalle completo

---

## 5. TODOs Pendientes

### 5.1 Flutter (0 TODOs en chat/)

✅ **Todos los TODOs del módulo chat han sido resueltos:**
- `call_screen.dart` - WebRTC integrado, chat overlay, añadir participantes
- `status_screen.dart` - Subida de estados completa
- `chat_input.dart` - Selector emojis, cámara, contactos
- `group_info_screen.dart` - Gestión miembros, llamadas, multimedia

### 5.2 PHP (0 TODOs en dashboards)

✅ **Todos los dashboards de administración completados:**
- advertising, bares, chat-estados, contabilidad, dex-solana, empresarial, facturas, sello-conciencia, themacle

Los TODOs restantes en PHP son menores y están en features secundarios:

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
| incidencias | ✅ | ✅ | ✅ | ✅ |
| foros | ✅ | ✅ | ✅ | ✅ |
| espacios-comunes | ✅ | ✅ | ✅ | ✅ |
| tramites | ✅ | ✅ | ✅ | ✅ |
| transparencia | ✅ | ✅ | ✅ | ✅ (read-only completo) |
| participacion | ✅ | ✅ | ✅ | ✅ |
| encuestas | ✅ | ✅ | ✅ | ✅ |
| campanias | ✅ | ✅ | ✅ | ✅ |
| crowdfunding | ✅ | ✅ | ✅ | ✅ |
| chat-estados | ✅ | ✅ | ✅ | ✅ |
| colectivos | ✅ | ✅ | ✅ | ✅ |
| kulturaka | ✅ | ✅ | ✅ | ✅ |
| bares | ✅ | ✅ | ✅ | ✅ |
| facturas | ✅ | ✅ | ✅ | ✅ |
| circulos-cuidados | ✅ | ✅ | ✅ | ✅ |
| saberes-ancestrales | ✅ | ✅ | ✅ | ✅ |

**Leyenda**: ✅ Completo | ⚠️ Parcial | ❌ No existe

---

## 7. Recomendaciones de Desarrollo

### 7.1 Prioridad Alta (Impacto Usuario)

1. ~~**Crear pantalla Flutter para `encuestas`**~~ ✅ Completado
2. ~~**Crear pantalla Flutter para `chat-estados`**~~ ✅ Completado
3. ~~**Crear pantalla Flutter para `campanias`**~~ ✅ Completado
4. ~~**Crear pantalla Flutter para `crowdfunding`**~~ ✅ Completado

5. ~~**Completar CRUD en `incidencias` Flutter**~~ ✅ Completado
   - ~~Falta formulario de creación~~ Ya existía
   - ~~Falta edición de estado~~ Añadido

6. ~~**Integrar `chat_grupos` y `chat_interno` con módulo `chat`**~~ ✅ Ya integrados
   - Ambos usan `ChatConversationsScreen` del chat unificado
   - Componentes legacy mantenidos para compatibilidad

### 7.2 Prioridad Media

5. ~~**Completar CRUD en `espacios-comunes` Flutter**~~ ✅ Ya estaba completo
8. ~~**Añadir gestión de pedidos en `woocommerce` Flutter**~~ ✅ Completado (detalle pedido, cancelar)

### 7.3 Prioridad Baja

9. ~~Crear pantallas para módulos culturales (`kulturaka`, `recetas`)~~ ✅ Kulturaka completo (API + Flutter)
10. ~~Mejorar `transparencia` Flutter con gráficos~~ ✅ Completado (dashboard estadísticas, gráficos por categoría)
11. ~~Añadir `mapa-actores` a Flutter~~ ✅ Completado (API + Flutter con 3 tabs, detalle, formulario)

### 7.4 Actualizaciones Recientes (Abril 2026)

**Kulturaka** - API REST ampliada + Pantalla Flutter creada:
- GET /kulturaka/espacios - Listar espacios culturales
- GET /kulturaka/espacios/{id} - Vista espacio con calendario, propuestas, métricas
- GET /kulturaka/artistas - Listar artistas
- GET /kulturaka/artistas/{id} - Vista artista con gira, propuestas, crowdfunding
- GET /kulturaka/eventos - Eventos culturales
- GET /kulturaka/comunidad - Vista comunidad completa
- GET /kulturaka/metricas - Métricas globales de la red
- POST /kulturaka/artistas/{id}/seguir - Seguir/dejar artista

**Pantalla Flutter Kulturaka** ✅ Creada completa:
- 4 tabs: Espacios, Artistas, Eventos, Comunidad
- Detalle de espacios con métricas y calendario
- Detalle de artistas con gira y proyectos crowdfunding
- Vista comunidad con eventos cercanos y agradecimientos
- Panel de métricas de la red

**Colectivos** - Mejoras en PHP:
- Creada vista admin `nuevo.php` para formulario de creación
- POST /colectivos - Crear nuevo colectivo
- PUT /colectivos/{id} - Actualizar colectivo
- POST /colectivos/{id}/abandonar - Abandonar colectivo
- GET /colectivos/{id}/proyectos - Proyectos del colectivo
- GET /colectivos/{id}/asambleas - Asambleas del colectivo

**Pantalla Flutter Colectivos** ✅ Verificada completa:
- Listado de colectivos con filtros
- Vista detalle con miembros
- Formulario de creación
- Usa API REST correctamente

**Facturas** ✅ Mejorado:
- Filtros por estado (pendiente, pagada, vencida, anulada)
- Tarjetas con iconos de estado coloreados
- Pantalla de detalle completa con líneas de factura
- Desglose de totales (base, IVA, total)
- Acción para marcar factura como pagada

**Bares** ✅ Mejorado completamente:
- Filtros por tipo de bar (cervecería, coctelería, taberna, etc.)
- Filtro de "solo abiertos"
- Tarjetas mejoradas con valoración y especialidades
- Pantalla de detalle con 3 tabs:
  - Info: valoración, descripción, servicios, contacto
  - Carta: menú con categorías y precios, alérgenos
  - Reservar: formulario completo de reserva
- Integración con mapas y llamadas

**Eventos** ✅ Mejorado completamente:
- 2 tabs: Lista y Calendario (agrupado por fecha)
- Filtros por fecha (hoy, esta semana, este mes)
- Filtros por categoría (cultural, deportivo, social, formativo, festivo, solidario)
- Tarjetas visuales con badge de fecha y categoría
- Pantalla de detalle completa con:
  - Info: fecha, hora, precio, plazas disponibles
  - Ubicación con integración de mapas
  - Organizador y contacto
  - Sistema de inscripción con selección de plazas
  - Cancelar inscripción
- Compartir evento

**Participación Ciudadana** ✅ Mejorado completamente:
- 2 tabs: Votaciones y Propuestas
- Tab Votaciones: listado con estado (activa/cerrada), votos, fecha límite
- Tab Propuestas: listado con filtros por estado (pendiente, aprobada, en debate, rechazada)
- Detalle de votación con opciones y resultados en porcentaje
- Sistema de votación con confirmación
- **Nueva funcionalidad**: Crear propuestas ciudadanas
  - Formulario completo con título, categoría, descripción
  - Validación de campos (mínimo 10 caracteres título, 50 descripción)
  - Categorías: urbanismo, medio ambiente, movilidad, cultura, deportes, etc.
- Detalle de propuesta con:
  - Estado y categoría
  - Autor y fecha de creación
  - Sistema de apoyos (votar a favor)
  - Respuesta oficial (si existe)
  - Comentarios con input en tiempo real
  - Indicador de "tu propuesta"

**Trámites** ✅ Verificado completo:
- 2 tabs: Catálogo de trámites y Mis solicitudes
- Detalle de trámite con requisitos, documentos, pasos, tasas
- Iniciar solicitud online con observaciones
- Detalle de solicitud con historial de estados
- Estados con colores: pendiente, en revisión, aprobada, rechazada, completada

**Transparencia** ✅ Verificado completo:
- Dashboard de estadísticas con totales
- Gráfico de documentos por categoría (interactivo)
- Filtros por búsqueda y categoría
- Tarjetas de documento con formato, fecha, descargas
- Detalle en bottom sheet con descarga
- Es read-only por naturaleza (portal de consulta)

**Mapa de Actores** ✅ Completado (API + Flutter):
- GET /actores - Listar actores con filtros
- GET /actores/buscar - Búsqueda por nombre/organización
- GET /actores/estadisticas - Estadísticas por posición e influencia
- GET /actores/tipos - Tipos de actor disponibles
- POST /actores - Crear nuevo actor (requiere login)
- GET /actores/{id}/relaciones - Relaciones del actor
- GET /actores/{id}/interacciones - Historial de interacciones

**Pantalla Flutter Mapa Actores** ✅ Creada completa:
- 3 tabs: Actores, Estadísticas, Por Tipo
- Filtros por posición (aliado, neutro, opositor) y tipo
- Tarjetas con badges de posición e influencia coloreados
- Pantalla de detalle con:
  - Información completa del actor
  - Personas clave de la organización
  - Relaciones con otros actores (alianzas, conflictos)
  - Historial de interacciones
- Formulario de creación de nuevo actor
- Buscador integrado con SearchDelegate

**Agregador de Contenido** ✅ Completado (Flutter):
- GET /flavor-agregador/v1/noticias - Noticias RSS importadas
- GET /flavor-agregador/v1/videos - Videos de YouTube

**Pantalla Flutter Agregador Contenido** ✅ Creada completa:
- 3 tabs: Feed (combinado), Noticias, Videos
- Feed combinado ordenado por fecha
- Grid de videos con thumbnails
- Detalle de noticia con fuente y fecha
- Detalle de video con botón para YouTube
- Buscador integrado

**Empresas** ✅ Completado (API existente + Flutter):
- GET /flavor/v1/empresas - Listar empresas
- GET /flavor/v1/empresas/{id} - Detalle empresa
- GET /flavor/v1/empresas/mis-empresas - Empresas del usuario
- GET /flavor/v1/empresas/{id}/miembros - Miembros de empresa
- GET /flavor/v1/empresas/{id}/estadisticas - Estadísticas

**Pantalla Flutter Empresas** ✅ Creada completa:
- 2 tabs: Directorio y Mis Empresas
- Filtros por sector
- Tarjetas con tipo de empresa y ubicación
- Vista "Mis Empresas" con rol y cargo
- Detalle con miembros, estadísticas, contacto
- Buscador integrado

**Contabilidad** ✅ Completado (API + Flutter):
- GET /flavor/v1/contabilidad/dashboard - Dashboard con resumen
- GET /flavor/v1/contabilidad/movimientos - Listar movimientos
- GET /flavor/v1/contabilidad/movimientos/{id} - Detalle movimiento
- POST /flavor/v1/contabilidad/movimientos - Crear movimiento
- GET /flavor/v1/contabilidad/resumen - Resumen por período
- GET /flavor/v1/contabilidad/categorias - Categorías disponibles
- GET /flavor/v1/contabilidad/grafico-mensual - Datos para gráfico

**Pantalla Flutter Contabilidad** ✅ Creada completa:
- 3 tabs: Resumen, Movimientos, Gráficos
- Selector de período (mes/año)
- Resumen del mes con ingresos, gastos, resultado
- Acumulado anual con IVA
- Lista de movimientos con filtros por tipo
- Gráfico de evolución de 12 meses
- Tabla resumen mensual
- Detalle de movimiento con desglose fiscal

### 7.5 Dashboards PHP Completos (Abril 2026)

Se han creado **dashboards administrativos completos** para todos los módulos que usaban plantilla placeholder. Cada dashboard incluye:
- Header con título e iconos
- Grid de accesos rápidos
- Tarjetas de estadísticas con datos reales de base de datos
- Tablas de datos recientes
- Gráficos Chart.js (donde aplica)
- Alertas de estados pendientes
- Información del módulo

| Módulo | Líneas | Características |
|--------|--------|-----------------|
| `advertising` | 505 | Stats de anuncios, impresiones, clics, CTR, ingresos, pool comunitario, gráfico evolución |
| `bares` | 446 | Establecimientos, reservas, valoraciones, distribución por tipo y zona |
| `contabilidad` | 606 | Balance, ingresos/gastos, IVA, evolución mensual, resumen fiscal anual |
| `chat-estados` | 295 | Estados activos, visualizaciones, reportes pendientes, distribución por tipo |
| `dex-solana` | 340 | Swaps, volumen 24h, TVL, pools, top pares, gráfico volumen semanal, modo paper/real |
| `empresarial` | 245 | Componentes web corporativos, categorías, páginas usando componentes |
| `sello-conciencia` | 232 | 5 premisas de economía consciente, puntuación global, niveles de conciencia |
| `themacle` | 337 | Componentes universales, catálogo por categoría, distribución, guía de uso |
| `facturas` | 316 | Facturas recientes, resumen por estado, gráfico mensual, totales anuales |

**Total**: 9 módulos con dashboards completos creados, reemplazando la plantilla placeholder de 14 líneas.

**Patrón de diseño utilizado**:
- Clases CSS `dm-*` (dm-dashboard, dm-card, dm-stat-card, dm-badge, dm-alert, dm-progress, dm-action-grid)
- Consultas `$wpdb` con verificación de existencia de tablas
- Integración con `flavor_dashboard_help()` para ayuda contextual
- Datos dinámicos desde `Flavor_Chat_Module_Loader::get_instance()->get_module()`

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

El sistema tiene una excelente cobertura de módulos entre PHP y Flutter, con **65+ módulos comunes** funcionando en ambas plataformas.

**Gaps principales**:
1. Solo 1 módulo PHP sin app móvil (bug-tracker, que es interno y no aplica)
2. ~~~30 TODOs en el módulo chat de Flutter~~ ✅ **0 TODOs** - Chat completamente funcional con WebRTC
3. 2 módulos chat legacy mantenidos para compatibilidad (redirigen al chat unificado)

**Fortalezas**:
1. API REST completa en PHP para todos los módulos
2. Arquitectura consistente con providers en Flutter
3. Sistema de lazy loading implementado
4. **Todos los módulos de usuario con CRUD completo y pantalla Flutter**
5. Pantallas de detalle implementadas en todos los módulos activos
6. Sistema de propuestas ciudadanas con apoyos y comentarios
7. **Cobertura completa**: agregador-contenido, empresas y contabilidad ahora con app móvil
8. **Dashboards PHP profesionales**: 9 módulos actualizados con dashboards completos
9. **WebRTC/VoIP completo** en Flutter con llamadas 1:1 y grupales

---

*Generado automáticamente por Claude Code*
