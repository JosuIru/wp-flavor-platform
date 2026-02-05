# 🔗 Integración APKs Existentes con Flavor Chat IA

## 📋 Situación Actual

### Sistema Existente (wp-calendario-experiencias)
**Ubicación**: `/wp-content/plugins/wp-calendario-experiencias/addons/chat-ia-addon/mobile-apps/`

**Características**:
- ✅ 2 APKs Flutter: Admin y Cliente
- ✅ Sistema multi-tenant (cualquier WordPress)
- ✅ API REST completa: `/wp-json/chat-ia-mobile/v1/`
- ✅ Scripts de personalización automática
- ✅ Chat IA integrado
- ✅ Reservas y tickets
- ✅ Sistema de verificación por email
- ✅ Deep links entre apps

### Sistema Nuevo (Flavor Chat IA)
**Ubicación**: `/wp-content/plugins/flavor-chat-ia/`

**Características**:
- ✅ Sistema modular independiente
- ✅ Perfiles de aplicación
- ✅ Módulos: Grupos Consumo, Banco Tiempo, Marketplace, WooCommerce
- ✅ API REST por módulo: `/wp-json/flavor-chat-ia/v1/`
- ✅ Chat IA multi-proveedor

---

## 🎯 Objetivo de Integración

**Hacer que las MISMAS APKs puedan funcionar con ambos sistemas**, permitiendo:

1. **Compatibilidad total**: Apps funcionan con wp-calendario-experiencias O Flavor Chat IA
2. **Detección automática**: App detecta qué plugin está activo
3. **API unificada**: Endpoints compatibles entre sistemas
4. **Módulos dinámicos**: App muestra módulos disponibles según plugin
5. **Sin recompilar**: Mismas APKs sirven para todos los casos

---

## 🏗️ Arquitectura de Integración

```
┌──────────────────────────────────────────────────────┐
│           APPS FLUTTER (YA EXISTENTES)               │
│                                                       │
│  ┌──────────────┐         ┌──────────────┐         │
│  │  APP ADMIN   │         │ APP CLIENTE  │         │
│  └──────┬───────┘         └──────┬───────┘         │
│         │                         │                  │
│         └──────────┬──────────────┘                  │
│                    │                                 │
│         ┌──────────▼──────────┐                     │
│         │   API CLIENT        │                     │
│         │  (con detección)    │                     │
│         └──────────┬──────────┘                     │
└────────────────────┼──────────────────────────────────┘
                     │
         ┌───────────▼──────────┐
         │  WORDPRESS SERVER    │
         │                      │
         │  Detecta plugins     │
         │  activos y responde  │
         │  con API unificada   │
         └──────────────────────┘
                     │
      ┌──────────────┴──────────────┐
      │                             │
      ▼                             ▼
┌─────────────┐            ┌──────────────┐
│ calendario  │            │ flavor-chat  │
│ experiencias│            │     -ia      │
│   addon     │            │              │
└─────────────┘            └──────────────┘
```

---

## 📡 Sistema de Detección de Plugins

### 1. Endpoint de Descubrimiento (WordPress)
**Nuevo endpoint**: `/wp-json/app-discovery/v1/info`

```php
// Responde con información de plugins activos
{
  "wordpress_url": "https://tu-sitio.com",
  "site_name": "Zoo de Mallorca",
  "active_systems": [
    {
      "id": "calendario-experiencias",
      "active": true,
      "version": "3.0",
      "api_namespace": "chat-ia-mobile/v1",
      "features": ["reservas", "tickets", "chat"]
    },
    {
      "id": "flavor-chat-ia",
      "active": true,
      "version": "1.5.0",
      "api_namespace": "flavor-chat-ia/v1",
      "modules": ["grupos_consumo", "woocommerce", "banco_tiempo", "marketplace"],
      "profile": "grupo_consumo"
    }
  ],
  "unified_api": true,
  "theme": {
    "primary_color": "#4CAF50",
    "secondary_color": "#8BC34A",
    "logo_url": "https://..."
  }
}
```

### 2. Cliente Flutter Adaptativo
```dart
// lib/core/services/plugin_detector.dart
class PluginDetector {
  Future<SystemInfo> detectSystem(String baseUrl) async {
    final response = await dio.get('$baseUrl/wp-json/app-discovery/v1/info');

    final info = SystemInfo.fromJson(response.data);

    // Configurar cliente según sistemas activos
    if (info.hasFlavorChatIA) {
      _configureFlavorChatIA(info);
    }
    if (info.hasCalendarioExperiencias) {
      _configureCalendarioExperiencias(info);
    }

    return info;
  }
}
```

---

## 🔌 API Unificada

### Estrategia: Adaptador de API

Crear una capa de adaptación en WordPress que traduce entre formatos:

```php
/**
 * Adaptador de API - Traduce entre sistemas
 */
class Flavor_API_Adapter {

    /**
     * Endpoint unificado para chat
     * Detecta qué sistema usar y traduce la respuesta
     */
    public function unified_chat_endpoint($request) {
        // Detectar sistemas activos
        $has_calendario = class_exists('Chat_IA_Addon');
        $has_flavor = class_exists('Flavor_Chat_IA');

        if ($has_flavor) {
            // Usar Flavor Chat IA
            return $this->use_flavor_chat($request);
        } elseif ($has_calendario) {
            // Usar Calendario addon
            return $this->use_calendario_chat($request);
        }

        return new WP_Error('no_system', 'No chat system available');
    }

    /**
     * Endpoint unificado para módulos dinámicos
     */
    public function unified_modules_endpoint() {
        $modules = [];

        // Añadir módulos de Flavor Chat IA
        if (class_exists('Flavor_Chat_IA')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $flavor_modules = $loader->get_active_modules();
            $modules = array_merge($modules, $this->format_flavor_modules($flavor_modules));
        }

        // Añadir funcionalidades de Calendario
        if (class_exists('Chat_IA_Addon')) {
            $modules[] = $this->format_calendario_module();
        }

        return $modules;
    }
}
```

---

## 📦 Nuevos Módulos para las APKs

### Estructura de Módulos Flutter

Añadir a las apps existentes:

```
lib/modules/
├── reservas/              # Ya existe (calendario)
│   ├── screens/
│   ├── widgets/
│   └── services/
├── grupos_consumo/        # ← NUEVO
│   ├── screens/
│   │   ├── pedidos_screen.dart
│   │   ├── mis_pedidos_screen.dart
│   │   └── detalle_pedido_screen.dart
│   ├── widgets/
│   │   ├── pedido_card.dart
│   │   └── progreso_bar.dart
│   ├── services/
│   │   └── grupos_consumo_service.dart
│   └── models/
│       └── pedido.dart
├── banco_tiempo/          # ← NUEVO
│   ├── screens/
│   │   ├── servicios_screen.dart
│   │   └── mis_intercambios_screen.dart
│   └── services/
│       └── banco_tiempo_service.dart
├── marketplace/           # ← NUEVO
│   ├── screens/
│   │   ├── anuncios_screen.dart
│   │   └── mis_anuncios_screen.dart
│   └── services/
│       └── marketplace_service.dart
└── woocommerce/           # ← NUEVO
    ├── screens/
    │   ├── productos_screen.dart
    │   └── carrito_screen.dart
    └── services/
        └── woocommerce_service.dart
```

### Sistema de Módulos Dinámicos

```dart
// lib/core/modules/module_manager.dart
class ModuleManager {
  final Map<String, AppModule> _modules = {};

  Future<void> loadModules(SystemInfo systemInfo) async {
    // Limpiar módulos anteriores
    _modules.clear();

    // Cargar módulos según sistema activo
    for (final moduleInfo in systemInfo.availableModules) {
      final module = _createModule(moduleInfo);
      if (module != null) {
        _modules[moduleInfo.id] = module;
      }
    }
  }

  AppModule? _createModule(ModuleInfo info) {
    switch (info.id) {
      case 'grupos_consumo':
        return GruposConsumoModule(config: info.config);
      case 'banco_tiempo':
        return BancoTiempoModule(config: info.config);
      case 'marketplace':
        return MarketplaceModule(config: info.config);
      case 'reservas':
        return ReservasModule(config: info.config);
      default:
        return null;
    }
  }

  List<NavigationItem> getNavigationItems() {
    return _modules.values
        .where((m) => m.showInNavigation)
        .map((m) => m.getNavigationItem())
        .toList();
  }
}
```

---

## 🎨 Configuración Dinámica de UI

### Home Screen Adaptativo

```dart
// lib/screens/home/home_screen.dart
class HomeScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final systemInfo = ref.watch(systemInfoProvider);
    final modules = ref.watch(moduleManagerProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(systemInfo.siteName),
      ),
      body: _buildBody(systemInfo, modules),
      bottomNavigationBar: _buildBottomNav(modules),
    );
  }

  Widget _buildBody(SystemInfo info, ModuleManager modules) {
    // Construir UI según módulos disponibles
    final sections = <Widget>[];

    // Sección de Chat (siempre presente)
    sections.add(ChatQuickAccess());

    // Secciones dinámicas por módulo
    for (final module in modules.activeModules) {
      sections.add(module.getHomeWidget());
    }

    return ListView(
      children: sections,
    );
  }
}
```

---

## 🔄 Plan de Migración

### Fase 1: Preparación (1 semana)
**WordPress - Flavor Chat IA**:
- [x] Crear endpoint `/app-discovery/v1/info`
- [ ] Implementar `Flavor_API_Adapter`
- [ ] Registrar rutas unificadas
- [ ] Documentar formato de respuestas

**Flutter - Apps Existentes**:
- [ ] Añadir `PluginDetector`
- [ ] Añadir `ModuleManager`
- [ ] Refactorizar `ApiClient` para multi-sistema

### Fase 2: Módulos Nuevos (2 semanas)
- [ ] Implementar `GruposConsumoModule` en Flutter
- [ ] Implementar `BancoTiempoModule` en Flutter
- [ ] Implementar `MarketplaceModule` en Flutter
- [ ] Implementar `WooCommerceModule` en Flutter

### Fase 3: Integración (1 semana)
- [ ] Pruebas con solo wp-calendario-experiencias
- [ ] Pruebas con solo Flavor Chat IA
- [ ] Pruebas con ambos activos
- [ ] Ajustes de UI dinámica

### Fase 4: Testing (1 semana)
- [ ] Tests unitarios de adaptadores
- [ ] Tests de integración
- [ ] Tests en dispositivos reales
- [ ] Documentación actualizada

### Fase 5: Deploy (1 semana)
- [ ] Build de APKs finales
- [ ] Actualización en Play Store
- [ ] Documentación de usuario
- [ ] Video tutoriales

---

## 📝 Archivos a Crear/Modificar

### WordPress (Flavor Chat IA)

#### 1. Plugin de Integración
**Ubicación**: `includes/app-integration/`

```
includes/app-integration/
├── class-app-integration.php          # Controlador principal
├── class-plugin-detector.php          # Detecta plugins activos
├── class-api-adapter.php              # Adaptador de APIs
├── class-discovery-endpoints.php      # Endpoints de descubrimiento
└── class-unified-api.php              # API unificada
```

#### 2. Endpoints Específicos por Módulo
- `includes/modules/grupos-consumo/class-grupos-consumo-mobile-api.php`
- `includes/modules/banco-tiempo/class-banco-tiempo-mobile-api.php`
- `includes/modules/marketplace/class-marketplace-mobile-api.php`

### Flutter (Apps Móviles)

#### 1. Core de Integración
```
lib/core/
├── services/
│   ├── plugin_detector.dart           # Detecta plugins
│   ├── api_adapter.dart               # Adapta APIs
│   └── unified_api_client.dart        # Cliente unificado
├── modules/
│   ├── module_manager.dart            # Gestión de módulos
│   └── app_module.dart                # Interfaz de módulo
└── models/
    ├── system_info.dart               # Info del sistema
    └── module_info.dart               # Info de módulo
```

#### 2. Módulos Nuevos
- `lib/modules/grupos_consumo/` (completo)
- `lib/modules/banco_tiempo/` (completo)
- `lib/modules/marketplace/` (completo)
- `lib/modules/woocommerce/` (completo)

---

## 🧪 Casos de Uso

### Caso 1: Solo wp-calendario-experiencias
```json
{
  "active_systems": [
    {
      "id": "calendario-experiencias",
      "active": true,
      "features": ["reservas", "tickets", "chat"]
    }
  ]
}
```
**Resultado**: App muestra solo módulo de Reservas (como ahora)

### Caso 2: Solo Flavor Chat IA
```json
{
  "active_systems": [
    {
      "id": "flavor-chat-ia",
      "active": true,
      "modules": ["grupos_consumo", "banco_tiempo"],
      "profile": "grupo_consumo"
    }
  ]
}
```
**Resultado**: App muestra Grupos de Consumo y Banco de Tiempo

### Caso 3: Ambos Activos
```json
{
  "active_systems": [
    {
      "id": "calendario-experiencias",
      "active": true,
      "features": ["reservas", "tickets"]
    },
    {
      "id": "flavor-chat-ia",
      "active": true,
      "modules": ["grupos_consumo", "marketplace"]
    }
  ]
}
```
**Resultado**: App muestra TODO (Reservas + Grupos Consumo + Marketplace)

---

## 🎯 KPIs de Éxito

- ✅ **Mismas APKs funcionan con ambos sistemas**
- ✅ **Detección automática sin configuración manual**
- ✅ **Sin necesidad de recompilar para diferentes sitios**
- ✅ **UI se adapta dinámicamente**
- ✅ **Rendimiento no se degrada**
- ✅ **Compatibilidad hacia atrás 100%**

---

## 🚀 Próximos Pasos Inmediatos

1. **Crear clase de integración en Flavor Chat IA**
2. **Implementar endpoint de descubrimiento**
3. **Adaptar API client en Flutter**
4. **Crear primer módulo nuevo (Grupos Consumo)**
5. **Probar integración end-to-end**

---

**Con esta integración, tendrás un sistema verdaderamente modular donde las MISMAS APKs se adaptan automáticamente a cualquier configuración de WordPress** 🚀
