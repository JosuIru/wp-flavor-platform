# CHECKLIST COMPLETO PARA PRODUCCIÓN - FLAVOR PLATFORM
**Fecha:** 2026-03-23
**Estado actual:** Pre-producción
**Objetivo:** Lanzamiento 1.0 Production-Ready

---

## 📊 ESTADO ACTUAL DEL PROYECTO

### Módulos por Estado (63 módulos totales)
- ✅ **VERDE (34 módulos - 54%)**: Completamente funcionales
- ⚠️ **AMARILLO (15 módulos - 24%)**: Funcionales parciales
- 🔴 **ROJO (14 módulos - 22%)**: Bloqueantes o incompletos

### Dashboards Completados Recientemente
- ✅ Empresarial: 991 líneas
- ✅ Grupos Consumo: 1,057 líneas
- ✅ Socios: 1,022 líneas
- ✅ Marketplace: 1,472 líneas
- ✅ Eventos: 1,366 líneas
- ✅ Incidencias: 1,298 líneas
- ✅ Assets: 1,144 líneas

---

## 🚨 P0 - BLOQUEANTES CRÍTICOS (Debe completarse ANTES de producción)

### 1. Módulos en ROJO - Bloqueantes Reales

#### 1.1. Chat Grupos (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Módulo inutilizable
**Acciones:**
- [ ] Crear `/includes/modules/chat-grupos/views/dashboard.php` (mínimo 600 líneas)
- [ ] Crear `/includes/modules/chat-grupos/frontend/class-chat-grupos-frontend-controller.php`
- [ ] Implementar shortcodes de grupos de chat
- [ ] Smoke test: envío de mensajes en grupo

#### 1.2. Chat Interno (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Comunicación interna rota
**Acciones:**
- [ ] Crear `/includes/modules/chat-interno/views/dashboard.php`
- [ ] Crear `/includes/modules/chat-interno/frontend/class-chat-interno-frontend-controller.php`
- [ ] Implementar sistema de mensajería directa
- [ ] Smoke test: chat 1-a-1 funcional

#### 1.3. Facturas (ROJO - P0 CRÍTICO)
**Estado:** Pago online sin implementar
**Impacto:** Flujo de cobro ROTO
**Evidencia:** `shortcode_pagar_factura()` retorna "Sistema de pago online proximamente disponible"
**Acciones:**
- [ ] Crear `/includes/modules/facturas/views/dashboard.php`
- [ ] Crear frontend controller
- [ ] **CRÍTICO:** Implementar gateway de pago (Stripe/PayPal/Redsys)
- [ ] Implementar generación PDF de facturas
- [ ] Smoke test: pago completo end-to-end

#### 1.4. Advertising (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Monetización no disponible
**Acciones:**
- [ ] Decidir si mantener en v1.0 o marcar como EXPERIMENTAL
- [ ] Si se mantiene: crear dashboard + frontend
- [ ] Si se descarta: añadir feature flag OFF por defecto

#### 1.5. Assets (ROJO - RESUELTO)
**Estado:** ✅ Dashboard completado (1,144 líneas)
**Acción:** Excluir del semáforo de módulos (es pseudo-módulo técnico)

#### 1.6. Bares (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Directorio de bares no funciona
**Acciones:**
- [ ] Crear dashboard con listado de establecimientos
- [ ] Crear frontend con mapa y fichas
- [ ] Implementar sistema de valoraciones

#### 1.7. Empresarial (ROJO - RESUELTO)
**Estado:** ✅ Dashboard completado (991 líneas)
**Acción:** Crear frontend si es necesario o marcar como `admin_only`

#### 1.8. Encuestas (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Sistema de votación no disponible
**Acciones:**
- [ ] Crear dashboard con resultados
- [ ] Crear frontend para responder encuestas
- [ ] Implementar renderizado de resultados en gráficos

#### 1.9. Huella Ecológica (ROJO)
**Estado:** Sin dashboard ni frontend
**Impacto:** Calculadora de huella no funciona
**Acciones:**
- [ ] Crear dashboard con estadísticas
- [ ] Crear frontend con calculadora interactiva
- [ ] Implementar algoritmo de cálculo

#### 1.10. Sello Conciencia (ROJO - P0 CRÍTICO)
**Estado:** Envío de solicitud BLOQUEADO
**Evidencia:** Botón deshabilitado con "Envío pendiente de integración"
**Acciones:**
- [ ] Crear `/includes/modules/sello-conciencia/views/dashboard.php`
- [ ] **CRÍTICO:** Desbloquear envío de solicitudes
- [ ] Implementar workflow de aprobación
- [ ] Smoke test: solicitud completa

#### 1.11. Trading IA / DEX Solana / Themacle (ROJO - EXPERIMENTALES)
**Estado:** Módulos cripto sin desarrollo
**Decisión estratégica requerida:**
- [ ] **Opción A:** Marcar como EXPERIMENTAL con feature flag OFF
- [ ] **Opción B:** Eliminar de v1.0
- [ ] **Opción C:** Desarrollo completo (requiere +2 semanas)

### 2. Funcionalidades Rotas Críticas (P0)

#### 2.1. Transparencia (P0 CRÍTICO)
**Estado:** Templates con placeholders "sistema no disponible"
**Archivos afectados:**
- `includes/modules/transparencia/templates/actas.php`
- `includes/modules/transparencia/templates/presupuestos.php`
- `includes/modules/transparencia/templates/presupuesto-actual.php`
- `includes/modules/transparencia/templates/ultimos-gastos.php`
- `includes/modules/transparencia/templates/contratos.php`

**Acciones:**
- [ ] Implementar renderizado real de actas
- [ ] Implementar vista de presupuestos
- [ ] Implementar vista de gastos
- [ ] Implementar vista de contratos
- [ ] Smoke test: publicar acta y visualizarla

#### 2.2. Campañas (P0)
**Estado:** Vista retorna "Esta vista de campañas todavía no está disponible"
**Acciones:**
- [ ] Implementar vistas faltantes en `class-campanias-module.php`
- [ ] Smoke test: crear campaña y firmar

### 3. Colisiones de Shortcodes (P0 CRÍTICO)

**Problema:** 22 shortcodes duplicados detectados que se sobrescriben según orden de carga

**Shortcodes duplicados críticos:**
```
2x socios_mis_cuotas
2x socios_mi_perfil
2x reservas_recursos
2x reservas_mis_reservas
2x reservas_formulario
2x reservas_calendario
2x marketplace_formulario
2x incidencias_reportar
2x incidencias_mapa
2x incidencias_listado
2x incidencias_estadisticas
2x incidencias_detalle
2x gc_* (múltiples duplicados grupos consumo)
2x flavor_radio_* (múltiples duplicados)
2x flavor_saberes_catalogo
```

**Acciones:**
- [ ] Auditoría completa de shortcodes duplicados
- [ ] Implementar namespacing único por módulo
- [ ] Crear aliases legacy con deprecación controlada
- [ ] Actualizar documentación de shortcodes
- [ ] Smoke test: verificar que no hay sobrescrituras

### 4. Módulos con Tablas No Creadas (P0)

**Módulos afectados:**
- Clientes
- Reservas
- Socios
- Eventos

**Evidencia:** Avisos "tabla principal no está disponible" en dashboards

**Acciones:**
- [ ] Verificar script de instalación de BD
- [ ] Ejecutar creación de tablas faltantes
- [ ] Implementar migrations automáticas
- [ ] Smoke test: activar módulo y verificar tablas

### 5. Loading Infinito en Dashboards (P0 CRÍTICO)

**Problema:** Dashboards pueden quedar en spinner infinito si faltan assets/vistas

**Acciones:**
- [ ] Implementar fallback automático en `class-admin-menu-manager.php`
- [ ] Implementar timeout de carga en `class-dashboard.php`
- [ ] Añadir mensajes de error claros
- [ ] Smoke test: cargar todos los dashboards sin errores

---

## ⚠️ P1 - ALTA PRIORIDAD (Debe completarse para v1.0)

### 6. Módulos AMARILLO - Desarrollo Parcial

#### 6.1. Biodiversidad Local (AMARILLO)
- [ ] Crear dashboard con estadísticas de avistamientos
- [ ] Mejorar frontend existente

#### 6.2. Chat Estados (AMARILLO)
- [ ] Crear dashboard completo
- [ ] Mejorar sistema de estados/stories

#### 6.3. Círculos Cuidados (AMARILLO)
- [ ] Crear dashboard de gestión
- [ ] Completar frontend existente

#### 6.4. Clientes (AMARILLO)
- [ ] Crear frontend (actualmente solo tiene dashboard)
- [ ] Implementar portal del cliente

#### 6.5. Crowdfunding (AMARILLO)
- [ ] Crear frontend para campañas
- [ ] Integrar gateway de pagos

#### 6.6. Email Marketing (AMARILLO - P1 CRÍTICO)
**Estado:** Renders en fallback
**Evidencia:** `render_vista_fallback()` para 8 vistas
**Acciones:**
- [ ] Eliminar fallbacks
- [ ] Crear vistas canónicas para:
  - Dashboard
  - Campañas
  - Automatizaciones
  - Suscriptores
  - Listas
  - Plantillas
  - Estadísticas
  - Configuración

#### 6.7. Energía Comunitaria (AMARILLO)
- [ ] Crear frontend para gestión de energía

#### 6.8. Fichaje Empleados (AMARILLO)
- [ ] Crear dashboard completo

#### 6.9. Justicia Restaurativa (AMARILLO)
- [ ] Crear dashboard completo

#### 6.10. Kulturaka (AMARILLO)
- [ ] Crear frontend para eventos culturales

#### 6.11. Recetas (AMARILLO)
- [ ] Crear dashboard de gestión

#### 6.12. Red Social (AMARILLO - P1 CRÍTICO)
**Estado:** Panel admin en fallback
**Evidencia:** `render_admin_dashboard_fallback`, etc.
**Acciones:**
- [ ] Eliminar fallbacks admin
- [ ] Crear vistas canónicas:
  - Dashboard
  - Publicaciones
  - Moderación

#### 6.13. Saberes Ancestrales (AMARILLO)
- [ ] Crear dashboard completo

#### 6.14. Trabajo Digno (AMARILLO)
- [ ] Crear dashboard de ofertas

#### 6.15. WooCommerce (AMARILLO)
- [ ] Crear frontend si es necesario

### 7. Banco de Tiempo - Vistas No Disponibles (P1)

**Evidencia:**
- "La vista de reputación no está disponible"
- "El historial de intercambios aún no está disponible"

**Acciones:**
- [ ] Implementar vista de reputación
- [ ] Implementar historial de intercambios
- [ ] Smoke test: intercambio completo

### 8. Marketplace - Fallback de Formulario (P1)

**Evidencia:** `marketplace-formulario-fallback` en frontend controller

**Acciones:**
- [ ] Eliminar fallback
- [ ] Crear vista canónica de formulario
- [ ] Smoke test: publicar anuncio desde frontend

---

## 🔧 P2 - MEJORAS DE CALIDAD (Recomendado para v1.0)

### 9. Talleres - Materiales Placeholder (P2)

**Evidencia:** Sección "Materiales frecuentes" vacía en `views/materiales.php`

**Acciones:**
- [ ] Implementar listado de materiales frecuentes
- [ ] Añadir sistema de gestión de inventario

### 10. Homologación de Dashboards (P2)

**Módulos con dashboard-tab pero sin `views/dashboard.php`:**
- Bares
- Encuestas
- Huella Ecológica
- Sello Conciencia
- Trading IA
- DEX Solana
- Themacle
- Chat Grupos
- Chat Interno
- Fichaje Empleados

**Acción:**
- [ ] Crear `views/dashboard.php` canónico para cada uno

### 11. Metadata Admin-Only (P2)

**Módulos que deberían marcarse como `admin_only`:**
- Advertising
- Encuestas (posiblemente)
- Facturas (posiblemente)
- Empresarial

**Acción:**
- [ ] Actualizar metadata en `class-app-profiles.php`
- [ ] Actualizar `class-module-access-control.php`

### 12. Exportación CSV/PDF Real (P2)

**Estado:** Todos los dashboards tienen botones placeholder

**Acciones:**
- [ ] Implementar exportación CSV real
- [ ] Implementar exportación PDF real (TCPDF/FPDF)
- [ ] Aplicar a todos los 7 dashboards completados

### 13. Cache de Queries Pesadas (P2)

**Acciones:**
- [ ] Implementar sistema de cache con transients
- [ ] Cachear queries de dashboards (TTL: 1 hora)
- [ ] Añadir botón "Refrescar datos" en dashboards

---

## 📱 MOBILE / APK

### 14. Build Release Firmado (P0 CRÍTICO)

**Estado:** Solo existen builds debug
**Evidencia:**
- `mobile-apps/build/app/outputs/apk/client/debug/app-client-debug.apk`

**Acciones:**
- [ ] Definir entrypoint estable (`main_selector.dart` vs `main_admin.dart` vs `main_client.dart`)
- [ ] Generar keystore para firma
- [ ] Configurar `build.gradle` para release
- [ ] Ejecutar build release firmado
- [ ] Verificar `server_config.dart` por entorno (dev/staging/prod)
- [ ] Smoke test: instalar APK release en dispositivo real

### 15. Configuración Apps según Módulos Activos (P1)

**Acciones:**
- [ ] Verificar que `app/config` API funciona correctamente
- [ ] Sincronizar módulos activos en WP con módulos en app
- [ ] Verificar navegación bottom tabs
- [ ] Smoke test: app refleja configuración del backend

---

## 🔒 SEGURIDAD Y PERFORMANCE

### 16. Auditoría de Seguridad (P0)

**Acciones:**
- [ ] Verificar escaping en TODOS los módulos (esc_html, esc_url, esc_attr)
- [ ] Verificar SQL injection protection ($wpdb->prepare en todo)
- [ ] Verificar nonces en formularios
- [ ] Verificar permisos de usuario (current_user_can)
- [ ] Scan con plugin de seguridad (Wordfence/Sucuri)

### 17. Performance y Carga (P1)

**Acciones:**
- [ ] Minificar CSS/JS de módulos
- [ ] Implementar lazy loading de Chart.js
- [ ] Optimizar queries SQL (añadir índices)
- [ ] Test de carga con 1000 usuarios simulados
- [ ] Optimizar tiempo de carga < 3 segundos

### 18. Accesibilidad (P2)

**Acciones:**
- [ ] Verificar ARIA labels en dashboards
- [ ] Verificar navegación por teclado
- [ ] Verificar contraste de colores (WCAG AA)
- [ ] Test con lector de pantalla

---

## 📚 DOCUMENTACIÓN

### 19. Documentación de Usuario (P1)

**Acciones:**
- [ ] Guía de instalación
- [ ] Guía de configuración por módulo
- [ ] Guía de uso de shortcodes
- [ ] FAQs
- [ ] Videos tutoriales (opcional)

### 20. Documentación Técnica (P1)

**Acciones:**
- [ ] Documentar API REST endpoints
- [ ] Documentar hooks y filtros
- [ ] Documentar estructura de BD
- [ ] Changelog completo
- [ ] Guía de desarrollo de módulos

### 21. Documentación de Módulos Faltante (P2)

**Módulos sin documentación en `/docs/modulos/`:**
- Contabilidad
- Email Marketing
- Empresarial
- Woocommerce
- (verificar otros)

**Acción:**
- [ ] Crear documentación para cada módulo faltante

---

## 🧪 TESTING

### 22. Tests Automatizados (P1)

**Acciones:**
- [ ] Unit tests para funciones críticas
- [ ] Integration tests para módulos
- [ ] E2E tests para flujos principales:
  - Registro de usuario
  - Publicar anuncio marketplace
  - Crear evento
  - Reportar incidencia
  - Pagar factura
  - Unirse a grupo de consumo

### 23. Smoke Tests por Módulo (P0 CRÍTICO)

**Acciones:**
- [ ] Crear checklist de smoke test para cada módulo
- [ ] Ejecutar smoke tests en todos los módulos VERDE
- [ ] Ejecutar smoke tests tras resolver cada módulo ROJO/AMARILLO

### 24. Cross-Browser Testing (P1)

**Acciones:**
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

---

## 🚀 DEPLOYMENT

### 25. Preparación de Entorno de Producción (P0)

**Acciones:**
- [ ] Servidor configurado (PHP 7.4+, MySQL 5.7+, WordPress 6.0+)
- [ ] SSL/HTTPS configurado
- [ ] Backup automático configurado
- [ ] Monitoring configurado (Uptime, errors)
- [ ] CDN configurado (opcional)

### 26. Proceso de Despliegue (P0)

**Acciones:**
- [ ] Crear rama `release/1.0`
- [ ] Congelar features
- [ ] Ejecutar todos los tests
- [ ] Generar build final
- [ ] Deploy a staging
- [ ] Smoke test completo en staging
- [ ] Deploy a producción
- [ ] Smoke test completo en producción
- [ ] Rollback plan documentado

### 27. Monitoring Post-Deploy (P0)

**Acciones:**
- [ ] Configurar alertas de errores
- [ ] Configurar alertas de performance
- [ ] Monitorizar logs primeras 48h
- [ ] Hotfix plan ready

---

## 📊 MÉTRICAS DE ÉXITO

### KPIs de Lanzamiento
- [ ] 0 errores PHP en logs primeras 24h
- [ ] Tiempo de carga < 3 segundos
- [ ] Uptime > 99.9%
- [ ] 0 vulnerabilidades críticas
- [ ] Todos los módulos VERDE funcionales al 100%
- [ ] Todos los shortcodes sin colisiones
- [ ] APK instalable y funcional

---

## ⏱️ ESTIMACIÓN DE TIEMPO

### Por Prioridad

**P0 - Bloqueantes Críticos:**
- Módulos ROJO bloqueantes: **5-7 días**
- Funcionalidades rotas críticas: **3-4 días**
- Colisiones shortcodes: **1-2 días**
- Tablas BD: **0.5 día**
- Loading infinito: **1 día**
- Mobile release: **1-2 días**
- Smoke tests: **2-3 días**
**Total P0: ~14-20 días laborables**

**P1 - Alta Prioridad:**
- Módulos AMARILLO: **7-10 días**
- Vistas faltantes: **3-4 días**
- Documentación: **2-3 días**
**Total P1: ~12-17 días laborables**

**P2 - Mejoras:**
- Homologación dashboards: **3-5 días**
- Exportación real: **2-3 días**
- Cache: **1-2 días**
- Accesibilidad: **2-3 días**
**Total P2: ~8-13 días laborables**

**TOTAL GENERAL: 34-50 días laborables (7-10 semanas)**

---

## 🎯 PLAN DE ACCIÓN RECOMENDADO

### Sprint 1 (Semana 1-2): Desbloqueo Crítico
1. Resolver módulos ROJO bloqueantes (Facturas, Sello Conciencia, Chat Grupos/Interno)
2. Implementar Transparencia completo
3. Resolver colisiones de shortcodes
4. Verificar/crear tablas BD faltantes

### Sprint 2 (Semana 3-4): Estabilización
1. Resolver módulos AMARILLO críticos (Email Marketing, Red Social)
2. Eliminar fallbacks
3. Implementar vistas faltantes
4. Mobile release build

### Sprint 3 (Semana 5-6): Testing y Calidad
1. Módulos AMARILLO restantes
2. Tests automatizados
3. Smoke tests completos
4. Auditoría de seguridad

### Sprint 4 (Semana 7-8): Refinamiento
1. Homologación dashboards
2. Documentación completa
3. Performance optimization
4. Staging deployment

### Sprint 5 (Semana 9-10): Launch
1. Testing final en staging
2. Producción deployment
3. Monitoring activo
4. Hotfixes si necesario

---

## ✅ CRITERIOS DE ACEPTACIÓN PARA GO-LIVE

### Requisitos Mínimos (Showstoppers)
- [ ] 0 módulos en ROJO bloqueantes
- [ ] 0 funcionalidades rotas críticas (P0)
- [ ] 0 colisiones de shortcodes sin resolver
- [ ] Todas las tablas BD creadas
- [ ] 0 loading infinito en dashboards
- [ ] APK release firmado instalable
- [ ] Smoke tests PASS al 100%
- [ ] 0 vulnerabilidades de seguridad críticas
- [ ] Documentación de usuario básica completa

### Requisitos Recomendados
- [ ] Máximo 5 módulos AMARILLO
- [ ] Tests automatizados implementados
- [ ] Performance < 3 segundos
- [ ] Cross-browser testing completo
- [ ] Documentación técnica completa

---

## 📝 NOTAS FINALES

**Estado Actual:** El proyecto está en buen estado. Los 7 dashboards profesionales recientemente completados representan +5,082 líneas de código de alta calidad.

**Bloqueo Principal:** Los módulos ROJO y las funcionalidades rotas (Facturas, Transparencia, Sello Conciencia, Chats) son el cuello de botella crítico.

**Recomendación:** Priorizar P0 completo antes de cualquier otra tarea. El lanzamiento es viable en 7-10 semanas con equipo dedicado.

**Riesgo Mayor:** Colisiones de shortcodes pueden causar bugs silenciosos difíciles de detectar. Resolver ASAP.

---

**Generado:** 2026-03-23
**Autor:** Auditoría técnica Flavor Platform
**Versión:** 1.0
