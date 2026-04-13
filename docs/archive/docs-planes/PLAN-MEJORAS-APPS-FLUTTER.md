# Plan de Mejoras - Sistema de Apps Flutter

## Resumen Ejecutivo

**Objetivo:** Completar el sistema de apps móviles Flutter con todas las funcionalidades pendientes.

**Estimación total:** 15 fases de desarrollo
**Prioridades:** P0 (Críticas) → P1 (Altas) → P2 (Medias) → P3 (UX)

---

## FASE 1: QR Code para Setup Rápido
**Prioridad:** P1 | **Complejidad:** Media

### Objetivo
Permitir que los usuarios configuren la app escaneando un QR en el panel de admin.

### Archivos a crear/modificar

#### WordPress (Backend)
```
includes/api/class-app-qr-setup.php          [NUEVO]
admin/views/app-qr-panel.php                  [NUEVO]
admin/js/app-qr-generator.js                  [NUEVO]
admin/css/app-qr.css                          [NUEVO]
```

#### Flutter (Frontend)
```
lib/features/setup/qr_setup_screen.dart       [NUEVO]
lib/core/services/qr_setup_service.dart       [NUEVO]
```

### Endpoints API
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/flavor-app/v2/setup/qr-token` | Genera token temporal para QR |
| POST | `/flavor-app/v2/setup/qr-validate` | Valida token y devuelve config |
| GET | `/flavor-app/v2/setup/qr-image` | Genera imagen QR (PNG/SVG) |

### Flujo
```
1. Admin genera QR en panel WordPress
2. QR contiene: {site_url, token, expires}
3. Usuario escanea QR en app
4. App valida token y obtiene manifest
5. App se configura automáticamente
```

### Tareas
- [ ] Crear clase `Flavor_App_QR_Setup` con generación de tokens
- [ ] Crear endpoint para generar QR image
- [ ] Añadir panel en admin con QR visible
- [ ] Crear pantalla de escaneo en Flutter
- [ ] Integrar con `AppManifestService`
- [ ] Añadir expiración de tokens (15 min)
- [ ] Tests de endpoints

---

## FASE 2: Webhooks de Cambios
**Prioridad:** P1 | **Complejidad:** Media

### Objetivo
Notificar a sistemas externos cuando cambia la configuración de la app.

### Archivos a crear/modificar

#### WordPress
```
includes/api/class-app-webhooks.php           [NUEVO]
includes/core/class-webhook-dispatcher.php    [NUEVO]
admin/views/webhooks-panel.php                [NUEVO]
```

### Base de datos
```sql
CREATE TABLE {prefix}flavor_app_webhooks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(64) NOT NULL,
    events JSON NOT NULL,
    active TINYINT(1) DEFAULT 1,
    last_triggered DATETIME,
    fail_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE {prefix}flavor_app_webhook_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    webhook_id BIGINT,
    event VARCHAR(50),
    payload JSON,
    response_code INT,
    response_body TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Eventos soportados
| Evento | Trigger |
|--------|---------|
| `config.updated` | Cambio en configuración general |
| `modules.changed` | Módulos activados/desactivados |
| `theme.changed` | Cambio de tema/colores |
| `branding.changed` | Cambio de branding |
| `device.synced` | Dispositivo confirmó sync |
| `build.requested` | Se solicitó nuevo build |

### Endpoints API
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/flavor-app/v2/webhooks` | Listar webhooks |
| POST | `/flavor-app/v2/webhooks` | Crear webhook |
| DELETE | `/flavor-app/v2/webhooks/{id}` | Eliminar webhook |
| POST | `/flavor-app/v2/webhooks/{id}/test` | Enviar test |

### Payload de webhook
```json
{
  "event": "config.updated",
  "timestamp": "2026-03-21T10:00:00Z",
  "site_url": "https://mi-sitio.com",
  "config_version": "v2.0-20260321-abc123",
  "data": { ... },
  "signature": "sha256=..."
}
```

### Tareas
- [ ] Crear tabla de webhooks
- [ ] Implementar dispatcher con retry logic
- [ ] Crear CRUD de webhooks
- [ ] Añadir firma HMAC para seguridad
- [ ] Implementar panel de admin
- [ ] Hooks en cambios de configuración
- [ ] Sistema de logs
- [ ] Tests

---

## FASE 3: Keystore y Firma de APKs
**Prioridad:** P0 | **Complejidad:** Baja

### Objetivo
Configurar firma de APKs para publicación en Play Store.

### Archivos a crear/modificar
```
mobile-apps/android/key.properties.example    [NUEVO]
mobile-apps/android/README-SIGNING.md         [NUEVO]
mobile-apps/scripts/generate-keystore.sh      [NUEVO]
mobile-apps/scripts/setup-signing.sh          [NUEVO]
```

### Estructura de key.properties
```properties
storePassword=YOUR_STORE_PASSWORD
keyPassword=YOUR_KEY_PASSWORD
keyAlias=flavor-app-key
storeFile=../keystores/flavor-release.jks
```

### Script generate-keystore.sh
```bash
#!/bin/bash
# Genera keystore para firma de APKs

KEYSTORE_DIR="./keystores"
KEYSTORE_FILE="$KEYSTORE_DIR/flavor-release.jks"
KEY_ALIAS="flavor-app-key"

mkdir -p "$KEYSTORE_DIR"

keytool -genkey -v \
  -keystore "$KEYSTORE_FILE" \
  -alias "$KEY_ALIAS" \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000 \
  -storepass "$STORE_PASSWORD" \
  -keypass "$KEY_PASSWORD" \
  -dname "CN=Flavor App, OU=Mobile, O=Flavor Platform, L=City, S=State, C=ES"
```

### Tareas
- [ ] Crear script de generación de keystore
- [ ] Crear template de key.properties
- [ ] Documentar proceso completo
- [ ] Actualizar build.gradle para usar keystore
- [ ] Actualizar build_app_v2.sh para release signing
- [ ] Añadir .gitignore para keystores
- [ ] Crear script de backup de keystore

---

## FASE 4: Push Notifications (Firebase)
**Prioridad:** P0 | **Complejidad:** Alta

### Objetivo
Implementar notificaciones push con Firebase Cloud Messaging.

### Archivos a crear/modificar

#### Flutter
```
lib/core/services/push_notification_service.dart    [NUEVO]
lib/core/services/notification_handler.dart         [NUEVO]
lib/core/models/push_notification.dart              [NUEVO]
android/app/src/main/AndroidManifest.xml            [MODIFICAR]
android/app/google-services.json                    [NUEVO - manual]
ios/Runner/GoogleService-Info.plist                 [NUEVO - manual]
```

#### WordPress
```
includes/api/class-push-notifications-api.php       [NUEVO]
includes/core/class-push-sender.php                 [NUEVO]
```

### Dependencias Flutter (descomentar en pubspec.yaml)
```yaml
firebase_core: ^2.24.2
firebase_messaging: ^14.7.10
flutter_local_notifications: ^16.3.0
```

### Endpoints API
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/flavor-app/v2/push/register` | Registrar token FCM |
| DELETE | `/flavor-app/v2/push/unregister` | Eliminar token |
| POST | `/flavor-app/v2/push/send` | Enviar notificación |
| POST | `/flavor-app/v2/push/broadcast` | Enviar a todos |
| GET | `/flavor-app/v2/push/stats` | Estadísticas |

### Base de datos
```sql
CREATE TABLE {prefix}flavor_push_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    device_id VARCHAR(100),
    token VARCHAR(500) NOT NULL,
    platform ENUM('android', 'ios', 'web'),
    app_version VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    INDEX idx_user (user_id),
    INDEX idx_token (token(100))
);

CREATE TABLE {prefix}flavor_push_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    notification_id VARCHAR(50),
    tokens_sent INT,
    tokens_failed INT,
    title VARCHAR(200),
    body TEXT,
    data JSON,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tipos de notificación
| Tipo | Trigger | Datos |
|------|---------|-------|
| `new_event` | Nuevo evento creado | event_id, title, date |
| `order_status` | Cambio estado pedido | order_id, status |
| `new_message` | Mensaje en chat | chat_id, sender, preview |
| `module_update` | Actualización módulo | module_id, changes |
| `custom` | Desde admin | title, body, action |

### Tareas
- [ ] Descomentar dependencias Firebase
- [ ] Crear PushNotificationService en Flutter
- [ ] Implementar handlers foreground/background
- [ ] Crear API de registro de tokens
- [ ] Implementar sender con FCM HTTP v1
- [ ] Panel admin para enviar notificaciones
- [ ] Triggers automáticos por eventos
- [ ] Manejo de deep links en notificaciones
- [ ] Tests

---

## FASE 5: Offline Mode Completo
**Prioridad:** P1 | **Complejidad:** Alta

### Objetivo
Permitir uso de la app sin conexión con sincronización posterior.

### Archivos a crear/modificar

#### Flutter
```
lib/core/services/offline_sync_service.dart         [NUEVO]
lib/core/services/action_queue_service.dart         [NUEVO]
lib/core/database/local_database.dart               [NUEVO]
lib/core/database/sync_status.dart                  [NUEVO]
lib/core/models/pending_action.dart                 [NUEVO]
lib/core/widgets/offline_banner.dart                [NUEVO]
lib/core/widgets/sync_indicator.dart                [NUEVO]
```

### Dependencias adicionales
```yaml
sqflite: ^2.3.0
connectivity_plus: ^5.0.2
workmanager: ^0.5.2
```

### Base de datos local (SQLite)
```sql
-- Caché de datos
CREATE TABLE cached_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    data TEXT NOT NULL,
    cached_at INTEGER NOT NULL,
    expires_at INTEGER,
    etag TEXT
);

-- Cola de acciones pendientes
CREATE TABLE pending_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action_type TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL,
    payload TEXT,
    created_at INTEGER NOT NULL,
    retry_count INTEGER DEFAULT 0,
    last_error TEXT,
    status TEXT DEFAULT 'pending'
);

-- Estado de sincronización
CREATE TABLE sync_status (
    module TEXT PRIMARY KEY,
    last_sync INTEGER,
    sync_token TEXT,
    pending_changes INTEGER DEFAULT 0
);
```

### Estrategia de sincronización
```
1. Al iniciar app:
   - Verificar conexión
   - Si offline: cargar de caché local
   - Si online: sync incremental

2. Al perder conexión:
   - Mostrar banner offline
   - Guardar acciones en cola
   - Seguir mostrando datos cacheados

3. Al recuperar conexión:
   - Procesar cola de acciones (FIFO)
   - Manejar conflictos
   - Actualizar caché
   - Notificar al usuario
```

### Tareas
- [ ] Implementar base de datos SQLite local
- [ ] Crear OfflineSyncService
- [ ] Implementar ActionQueueService
- [ ] Crear worker de background sync
- [ ] UI de estado offline/syncing
- [ ] Resolver conflictos de datos
- [ ] Caché selectivo por módulo
- [ ] Tests de sincronización

---

## FASE 6: Analytics Service
**Prioridad:** P1 | **Complejidad:** Media

### Objetivo
Conectar Flutter con el backend de analytics existente.

### Archivos a crear/modificar

#### Flutter
```
lib/core/services/analytics_service.dart            [NUEVO]
lib/core/analytics/event_tracker.dart               [NUEVO]
lib/core/analytics/screen_tracker.dart              [NUEVO]
lib/core/analytics/user_properties.dart             [NUEVO]
```

### Eventos a trackear automáticamente
| Evento | Trigger |
|--------|---------|
| `app_open` | App inicia |
| `app_background` | App va a background |
| `screen_view` | Cambio de pantalla |
| `button_click` | Click en botón principal |
| `module_access` | Acceso a módulo |
| `search` | Búsqueda realizada |
| `error` | Error capturado |
| `sync_complete` | Sincronización completada |

### Implementación
```dart
class AnalyticsService {
  static final AnalyticsService _instance = AnalyticsService._();
  factory AnalyticsService() => _instance;

  final Queue<AnalyticsEvent> _eventQueue = Queue();
  Timer? _flushTimer;

  void trackEvent(String name, [Map<String, dynamic>? params]) {
    _eventQueue.add(AnalyticsEvent(
      name: name,
      params: params,
      timestamp: DateTime.now(),
    ));
    _scheduleFlush();
  }

  void trackScreenView(String screenName) {
    trackEvent('screen_view', {'screen_name': screenName});
  }

  Future<void> _flush() async {
    if (_eventQueue.isEmpty) return;

    final events = _eventQueue.toList();
    _eventQueue.clear();

    await _sendToBackend(events);
  }
}
```

### Tareas
- [ ] Crear AnalyticsService singleton
- [ ] Implementar queue con batch sending
- [ ] Integrar con NavigatorObserver para screen_view
- [ ] Añadir tracking automático en widgets base
- [ ] Conectar con API existente
- [ ] Fallback a almacenamiento local si offline
- [ ] Dashboard de métricas en admin
- [ ] Tests

---

## FASE 7: Crash Reporting
**Prioridad:** P2 | **Complejidad:** Media

### Objetivo
Capturar y reportar errores/crashes de la app.

### Archivos a crear/modificar

#### Flutter
```
lib/core/services/crash_reporting_service.dart      [NUEVO]
lib/core/error/error_handler.dart                   [NUEVO]
lib/core/error/error_boundary.dart                  [NUEVO]
```

#### WordPress
```
includes/api/class-crash-reports-api.php            [NUEVO]
admin/views/crash-reports.php                       [NUEVO]
```

### Dependencias opcionales
```yaml
# Opción 1: Sentry (recomendado)
sentry_flutter: ^7.14.0

# Opción 2: Firebase Crashlytics
firebase_crashlytics: ^3.4.9
```

### Base de datos
```sql
CREATE TABLE {prefix}flavor_crash_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    device_id VARCHAR(100),
    app_version VARCHAR(20),
    platform VARCHAR(20),
    os_version VARCHAR(50),
    error_type VARCHAR(100),
    error_message TEXT,
    stack_trace TEXT,
    breadcrumbs JSON,
    user_id BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_error_type (error_type)
);
```

### Endpoints API
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/flavor-app/v2/crashes/report` | Reportar crash |
| GET | `/flavor-app/v2/crashes` | Listar crashes |
| GET | `/flavor-app/v2/crashes/stats` | Estadísticas |

### Tareas
- [ ] Implementar CrashReportingService
- [ ] Wrapper de FlutterError.onError
- [ ] Captura de errores async
- [ ] Breadcrumbs para contexto
- [ ] API de reporte
- [ ] Panel admin de crashes
- [ ] Agrupación de crashes similares
- [ ] Alertas por email
- [ ] Tests

---

## FASE 8: Tests Automatizados
**Prioridad:** P0 | **Complejidad:** Alta

### Objetivo
Cobertura de tests para código crítico.

### Estructura de tests
```
mobile-apps/test/
├── unit/
│   ├── services/
│   │   ├── api_client_test.dart
│   │   ├── manifest_service_test.dart
│   │   ├── analytics_service_test.dart
│   │   └── offline_sync_test.dart
│   ├── models/
│   │   └── ...
│   └── utils/
│       └── ...
├── widget/
│   ├── screens/
│   │   ├── setup_screen_test.dart
│   │   ├── dashboard_screen_test.dart
│   │   └── ...
│   └── components/
│       └── ...
├── integration/
│   ├── auth_flow_test.dart
│   ├── sync_flow_test.dart
│   └── module_navigation_test.dart
└── mocks/
    ├── mock_api_client.dart
    ├── mock_storage.dart
    └── ...
```

### Dependencias de test
```yaml
dev_dependencies:
  flutter_test:
    sdk: flutter
  mockito: ^5.4.4
  build_runner: ^2.4.8
  integration_test:
    sdk: flutter
  network_image_mock: ^2.1.1
```

### Tareas
- [ ] Setup de test environment
- [ ] Crear mocks de servicios
- [ ] Unit tests para ApiClient
- [ ] Unit tests para ManifestService
- [ ] Unit tests para OfflineSyncService
- [ ] Widget tests para pantallas principales
- [ ] Integration tests para flujos críticos
- [ ] CI/CD para ejecutar tests
- [ ] Reporte de cobertura

---

## FASE 9: Rate Limiting Mejorado
**Prioridad:** P2 | **Complejidad:** Media

### Objetivo
Proteger APIs de abuso con rate limiting inteligente.

### Archivos a crear/modificar
```
includes/core/class-rate-limiter-v2.php             [NUEVO]
includes/api/trait-rate-limited.php                 [NUEVO]
```

### Estrategias de limiting
| Tipo | Límite | Ventana |
|------|--------|---------|
| Por IP | 100 req | 1 min |
| Por API Key | 1000 req | 1 min |
| Por Usuario | 500 req | 1 min |
| Por Endpoint | Variable | Variable |

### Headers de respuesta
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1616161616
Retry-After: 30
```

### Almacenamiento
- Redis (preferido)
- Transients de WordPress (fallback)
- Base de datos (último recurso)

### Tareas
- [ ] Implementar rate limiter con múltiples backends
- [ ] Configuración por endpoint
- [ ] Headers informativos
- [ ] Panel de configuración
- [ ] Whitelist de IPs
- [ ] Blacklist automática
- [ ] Logs de rate limiting
- [ ] Tests

---

## FASE 10: Criptografía E2E Completa
**Prioridad:** P2 | **Complejidad:** Alta

### Objetivo
Completar implementación del protocolo Signal para chat seguro.

### Archivos a modificar
```
lib/core/crypto/signal_protocol_service.dart        [MODIFICAR]
lib/core/crypto/e2e_key_store.dart                  [MODIFICAR]
lib/core/crypto/key_exchange_service.dart           [NUEVO]
lib/core/crypto/session_manager.dart                [NUEVO]
```

### TODOs a completar
1. Verificación de firma digital
2. Búsqueda en prekeys antiguas
3. Manejo de one-time prekey
4. Exportación cifrada con password
5. Importación desde backup
6. Rotación de claves
7. Perfect forward secrecy

### Tareas
- [ ] Implementar verificación de firmas
- [ ] Sistema de prekeys con fallback
- [ ] Export/import de claves
- [ ] Rotación automática de claves
- [ ] UI de verificación de seguridad
- [ ] Indicadores de encriptación
- [ ] Tests de seguridad

---

## FASE 11: Lazy Loading desde API
**Prioridad:** P2 | **Complejidad:** Baja

### Objetivo
Completar el TODO de cargar módulos desde API cuando no están en caché.

### Archivos a modificar
```
lib/core/modules/lazy/module_lazy_loader.dart       [MODIFICAR]
lib/core/modules/module_cache_manager.dart          [MODIFICAR]
```

### Implementación
```dart
Future<ModuleConfig?> loadModule(String moduleId) async {
  // 1. Intentar caché local
  final cached = await _cacheManager.get(moduleId);
  if (cached != null && !cached.isExpired) {
    return cached;
  }

  // 2. Cargar desde API
  try {
    final response = await _apiClient.get('/modules/$moduleId/config');
    final config = ModuleConfig.fromJson(response.data);

    // 3. Guardar en caché
    await _cacheManager.set(moduleId, config);

    return config;
  } catch (e) {
    // 4. Fallback a caché expirado
    if (cached != null) {
      return cached;
    }
    rethrow;
  }
}
```

### Tareas
- [ ] Implementar carga desde API
- [ ] Fallback a caché expirado
- [ ] Indicador de carga
- [ ] Retry logic
- [ ] Tests

---

## FASE 12: Mejoras UX - Onboarding
**Prioridad:** P3 | **Complejidad:** Media

### Objetivo
Crear flujo de onboarding interactivo para nuevos usuarios.

### Archivos a crear
```
lib/features/onboarding/onboarding_screen.dart      [NUEVO]
lib/features/onboarding/onboarding_steps.dart       [NUEVO]
lib/features/onboarding/widgets/step_indicator.dart [NUEVO]
assets/images/onboarding/                           [NUEVO]
```

### Pasos del onboarding
1. Bienvenida + branding del sitio
2. Selección de idioma
3. Escaneo QR o URL manual
4. Confirmación de conexión
5. Tour de funcionalidades
6. Configuración de notificaciones
7. Listo para usar

### Tareas
- [ ] Diseñar flujo de onboarding
- [ ] Crear pantallas con animaciones
- [ ] Integrar con QR setup
- [ ] Persistir estado de onboarding
- [ ] Skip para usuarios expertos
- [ ] Tests

---

## FASE 13: Mejoras UX - Skeleton Loaders
**Prioridad:** P3 | **Complejidad:** Baja

### Objetivo
Implementar skeleton loaders consistentes en toda la app.

### Archivos a crear
```
lib/core/widgets/skeletons/skeleton_card.dart       [NUEVO]
lib/core/widgets/skeletons/skeleton_list.dart       [NUEVO]
lib/core/widgets/skeletons/skeleton_grid.dart       [NUEVO]
lib/core/widgets/skeletons/skeleton_detail.dart     [NUEVO]
lib/core/widgets/skeletons/shimmer_effect.dart      [NUEVO]
```

### Dependencias
```yaml
shimmer: ^3.0.0
```

### Tareas
- [ ] Crear componentes skeleton base
- [ ] Shimmer effect animado
- [ ] Integrar en todas las pantallas de lista
- [ ] Integrar en pantallas de detalle
- [ ] Tests visuales

---

## FASE 14: Mejoras UX - Pull to Refresh
**Prioridad:** P3 | **Complejidad:** Baja

### Objetivo
Implementar pull-to-refresh consistente y con feedback visual.

### Archivos a crear
```
lib/core/widgets/refreshable_list.dart              [NUEVO]
lib/core/widgets/custom_refresh_indicator.dart      [NUEVO]
```

### Implementación
```dart
class RefreshableList extends StatelessWidget {
  final Future<void> Function() onRefresh;
  final Widget child;
  final bool showLastUpdated;

  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      color: Theme.of(context).primaryColor,
      child: Column(
        children: [
          if (showLastUpdated) LastUpdatedBanner(),
          Expanded(child: child),
        ],
      ),
    );
  }
}
```

### Tareas
- [ ] Crear componente RefreshableList
- [ ] Indicador de última actualización
- [ ] Feedback háptico
- [ ] Integrar en todas las listas
- [ ] Tests

---

## FASE 15: Mejoras UX - Animaciones
**Prioridad:** P3 | **Complejidad:** Media

### Objetivo
Añadir animaciones fluidas y consistentes.

### Archivos a crear
```
lib/core/animations/page_transitions.dart           [NUEVO]
lib/core/animations/list_animations.dart            [NUEVO]
lib/core/animations/hero_animations.dart            [NUEVO]
lib/core/animations/loading_animations.dart         [NUEVO]
```

### Tipos de animaciones
| Tipo | Uso |
|------|-----|
| Fade + Slide | Transición entre pantallas |
| Staggered | Aparición de listas |
| Hero | Detalle de items |
| Pulse | Indicadores de carga |
| Scale | Botones y acciones |

### Tareas
- [ ] Definir sistema de animaciones
- [ ] Transiciones de página personalizadas
- [ ] Animaciones de lista staggered
- [ ] Hero transitions para imágenes
- [ ] Micro-interacciones en botones
- [ ] Tests de rendimiento

---

## CRONOGRAMA SUGERIDO

```
Semana 1-2:   Fase 1 (QR Setup) + Fase 3 (Keystore)
Semana 3-4:   Fase 4 (Push Notifications)
Semana 5-6:   Fase 5 (Offline Mode)
Semana 7:     Fase 2 (Webhooks) + Fase 6 (Analytics)
Semana 8:     Fase 7 (Crash Reporting)
Semana 9-10:  Fase 8 (Tests)
Semana 11:    Fase 9 (Rate Limiting) + Fase 11 (Lazy Loading)
Semana 12:    Fase 10 (Criptografía E2E)
Semana 13-14: Fases 12-15 (UX)
```

---

## MÉTRICAS DE ÉXITO

| Métrica | Objetivo |
|---------|----------|
| Cobertura de tests | > 70% |
| Crash-free rate | > 99% |
| Tiempo de setup | < 30 segundos |
| Sync offline → online | < 5 segundos |
| Tiempo de carga inicial | < 2 segundos |
| Push delivery rate | > 95% |

---

## DEPENDENCIAS ENTRE FASES

```
Fase 3 (Keystore) ──────────────────────────────────────┐
                                                         ↓
Fase 1 (QR) ───────────────────────────────────────→ Publicación
                                                         ↑
Fase 4 (Push) ──────────────────────────────────────────┘
       ↓
Fase 6 (Analytics) ──→ Fase 7 (Crash)
       ↓
Fase 5 (Offline) ────→ Fase 11 (Lazy Loading)
       ↓
Fase 8 (Tests) ──────→ CI/CD
```

---

## NOTAS FINALES

1. **Priorizar P0 y P1** antes de cualquier mejora P2/P3
2. **Tests desde el inicio** de cada fase
3. **Documentar** cada nueva funcionalidad
4. **Mantener compatibilidad** con versiones anteriores
5. **Feature flags** para rollout gradual
