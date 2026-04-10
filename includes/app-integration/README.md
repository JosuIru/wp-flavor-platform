# 🔗 Sistema de Integración con APKs Existentes

## Descripción

Este sistema permite que las aplicaciones móviles (APKs) existentes de **wp-calendario-experiencias** funcionen con **Flavor Chat IA** sin necesidad de recompilar.

## Arquitectura

```
┌─────────────────────────────────────┐
│   APP FLUTTER (Ya existente)        │
│                                     │
│   1. Al iniciar, llama a:          │
│      /wp-json/app-discovery/v1/info│
│                                     │
│   2. Detecta plugins activos        │
│   3. Carga módulos dinámicamente    │
│   4. Adapta UI según disponibilidad │
└─────────────────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────┐
│        WORDPRESS SERVER             │
│                                     │
│  ┌──────────────────────────────┐  │
│  │  PLUGIN DETECTOR             │  │
│  │  - Detecta wp-calendario     │  │
│  │  - Detecta flavor-chat-ia    │  │
│  │  - Devuelve capacidades      │  │
│  └──────────────────────────────┘  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │  API ADAPTER                 │  │
│  │  - Traduce formatos API      │  │
│  │  - Unifica endpoints         │  │
│  │  - Adapta respuestas         │  │
│  └──────────────────────────────┘  │
└─────────────────────────────────────┘
```

## Componentes

### 1. Plugin Detector (`class-plugin-detector.php`)
Detecta qué plugins están activos y sus capacidades:
- **wp-calendario-experiencias**: Reservas, tickets, chat
- **flavor-chat-ia**: Módulos activos, perfil, features

### 2. API Adapter (`class-api-adapter.php`)
Traduce entre formatos de API diferentes:
- Convierte fechas (MySQL ↔ ISO 8601)
- Adapta respuestas al formato esperado por las apps
- Asegura campos obligatorios

### 3. App Integration (`class-app-integration.php`)
Controlador principal que:
- Registra endpoints de descubrimiento
- Proporciona información del sistema
- Lista módulos disponibles
- Devuelve configuración de tema

## Endpoints Disponibles

### 1. Información del Sistema
```http
GET /wp-json/app-discovery/v1/info
```

**Respuesta**:
```json
{
  "wordpress_url": "https://tu-sitio.com",
  "site_name": "Mi Sitio",
  "site_description": "Descripción del sitio",
  "active_systems": [
    {
      "id": "flavor-chat-ia",
      "name": "Flavor Chat IA",
      "active": true,
      "version": "1.5.0",
      "api_namespace": "flavor-platform/v1",
      "profile": "grupo_consumo",
      "modules": ["grupos_consumo", "woocommerce"],
      "features": ["chat", "pedidos_colectivos", "productos"],
      "endpoints": {
        "discovery": "/wp-json/app-discovery/v1/info",
        "modules": "/wp-json/app-discovery/v1/modules",
        "theme": "/wp-json/app-discovery/v1/theme",
        "grupos_consumo": {
          "pedidos": "/wp-json/flavor-platform/v1/pedidos",
          "mis_pedidos": "/wp-json/flavor-platform/v1/mis-pedidos"
        }
      }
    }
  ],
  "unified_api": true,
  "api_version": "1.0",
  "theme": {
    "primary_color": "#4CAF50",
    "secondary_color": "#8BC34A",
    "accent_color": "#FF9800",
    "logo_url": "https://...",
    "favicon_url": "https://...",
    "background_color": "#FFFFFF",
    "text_color": "#000000"
  },
  "timezone": "Europe/Madrid",
  "language": "es_ES"
}
```

### 2. Módulos Disponibles
```http
GET /wp-json/app-discovery/v1/modules
```

**Respuesta**:
```json
{
  "success": true,
  "modules": [
    {
      "id": "grupos_consumo",
      "name": "Grupos de Consumo",
      "description": "Sistema de pedidos colectivos",
      "system": "flavor-chat-ia",
      "api_namespace": "flavor-platform/v1",
      "icon": "shopping_basket",
      "color": "#46b450",
      "show_in_navigation": true,
      "config": {
        "allow_orders_from_app": true,
        "show_progress_bar": true,
        "enable_notifications": true,
        "cache_duration": 3600
      }
    },
    {
      "id": "reservas",
      "name": "Reservas",
      "description": "Sistema de reservas y tickets",
      "system": "calendario-experiencias",
      "api_namespace": "chat-ia-mobile/v1",
      "icon": "calendar",
      "color": "#2196F3",
      "show_in_navigation": true,
      "config": {}
    }
  ],
  "total": 2
}
```

### 3. Configuración de Tema
```http
GET /wp-json/app-discovery/v1/theme
```

**Respuesta**:
```json
{
  "primary_color": "#4CAF50",
  "secondary_color": "#8BC34A",
  "accent_color": "#FF9800",
  "logo_url": "https://tu-sitio.com/wp-content/uploads/logo.png",
  "favicon_url": "https://tu-sitio.com/wp-content/uploads/favicon.ico",
  "background_color": "#FFFFFF",
  "text_color": "#000000"
}
```

### 4. Chat Unificado (Bridging)
```http
POST /wp-json/unified-api/v1/chat
Content-Type: application/json

{
  "message": "Hola, ¿qué pedidos hay abiertos?",
  "session_id": "abc123",
  "user_id": 42
}
```

**Respuesta**:
```json
{
  "success": true,
  "response": "Actualmente hay 3 pedidos abiertos...",
  "session_id": "abc123",
  "suggestions": [
    "Ver pedidos abiertos",
    "Mis pedidos"
  ],
  "actions": [
    {
      "type": "navigate",
      "route": "/pedidos"
    }
  ],
  "system": "flavor-chat-ia"
}
```

### 5. Información del Sitio Unificada
```http
GET /wp-json/unified-api/v1/site-info
```

**Respuesta**:
```json
{
  "site_name": "Mi Sitio",
  "site_url": "https://tu-sitio.com",
  "description": "Descripción del sitio",
  "logo_url": "https://...",
  "systems_active": ["flavor-chat-ia"],
  "flavor": {
    "profile": "grupo_consumo",
    "active_modules": ["grupos_consumo", "woocommerce"],
    "api_version": "1.0"
  }
}
```

## Casos de Uso

### Caso 1: Solo wp-calendario-experiencias
```json
{
  "active_systems": [{
    "id": "calendario-experiencias",
    "features": ["reservas", "tickets", "chat"]
  }]
}
```
**Resultado**: App muestra solo módulo de Reservas (como siempre)

### Caso 2: Solo Flavor Chat IA
```json
{
  "active_systems": [{
    "id": "flavor-chat-ia",
    "modules": ["grupos_consumo", "banco_tiempo"]
  }]
}
```
**Resultado**: App muestra Grupos de Consumo y Banco de Tiempo

### Caso 3: Ambos Activos
```json
{
  "active_systems": [
    {
      "id": "calendario-experiencias",
      "features": ["reservas", "tickets"]
    },
    {
      "id": "flavor-chat-ia",
      "modules": ["grupos_consumo", "marketplace"]
    }
  ]
}
```
**Resultado**: App muestra todo (Reservas + Grupos Consumo + Marketplace)

## Testing

### Probar desde Terminal

```bash
# 1. Info del sistema
curl -X GET "https://tu-sitio.com/wp-json/app-discovery/v1/info"

# 2. Módulos disponibles
curl -X GET "https://tu-sitio.com/wp-json/app-discovery/v1/modules"

# 3. Tema
curl -X GET "https://tu-sitio.com/wp-json/app-discovery/v1/theme"

# 4. Chat unificado
curl -X POST "https://tu-sitio.com/wp-json/unified-api/v1/chat" \
  -H "Content-Type: application/json" \
  -d '{"message":"Hola","session_id":"test123"}'

# 5. Info del sitio
curl -X GET "https://tu-sitio.com/wp-json/unified-api/v1/site-info"
```

### Probar desde Navegador

Abre en tu navegador:
```
https://tu-sitio.com/wp-json/app-discovery/v1/info
https://tu-sitio.com/wp-json/app-discovery/v1/modules
https://tu-sitio.com/wp-json/app-discovery/v1/theme
```

### Probar desde Flutter App

```dart
// En el inicio de la app
final systemInfo = await PluginDetector.detectSystem('https://tu-sitio.com');

if (systemInfo.hasFlavorChatIA) {
  print('Flavor Chat IA activo');
  print('Módulos: ${systemInfo.flavorModules}');
}

if (systemInfo.hasCalendarioExperiencias) {
  print('Calendario Experiencias activo');
  print('Features: ${systemInfo.calendarioFeatures}');
}

// Cargar módulos dinámicamente
await ModuleManager.loadModules(systemInfo);

// La app ahora tiene los módulos correctos cargados
```

## Flujo de Integración

### 1. App Inicia
```dart
void main() async {
  // 1. Detectar sistema
  final info = await ApiService.get('/app-discovery/v1/info');

  // 2. Configurar según sistemas activos
  AppConfig.configure(info);

  // 3. Cargar módulos dinámicamente
  await ModuleLoader.load(info.modules);

  // 4. Iniciar app con módulos correctos
  runApp(MyApp());
}
```

### 2. Durante Uso
```dart
// La app usa los endpoints según el sistema activo
if (AppConfig.hasModule('grupos_consumo')) {
  final pedidos = await ApiService.get(
    '${AppConfig.apiNamespace}/pedidos'
  );
}

if (AppConfig.hasModule('reservas')) {
  final reservas = await ApiService.get(
    'chat-ia-mobile/v1/reservations'
  );
}
```

### 3. UI Dinámica
```dart
Widget buildNavigationBar() {
  final items = <NavigationItem>[];

  // Añadir items según módulos disponibles
  for (final module in AppConfig.activeModules) {
    items.add(NavigationItem(
      icon: module.icon,
      label: module.name,
      color: Color(module.color),
      route: module.route,
    ));
  }

  return BottomNavigationBar(items: items);
}
```

## Ventajas

✅ **Mismas APKs** para cualquier configuración
✅ **Sin recompilar** al cambiar plugins
✅ **Detección automática** de capacidades
✅ **UI dinámica** según módulos activos
✅ **Compatibilidad hacia atrás** 100%
✅ **Escalable** a futuros módulos
✅ **Multi-tenant** por diseño

## Próximos Pasos

1. ✅ Sistema de integración creado
2. ⏳ Modificar Flutter app para detección dinámica
3. ⏳ Crear módulos Flutter para Flavor Chat IA
4. ⏳ Testing end-to-end
5. ⏳ Documentación para desarrolladores Flutter

## Notas Técnicas

- Los endpoints son **públicos** (no requieren autenticación)
- La detección de plugins usa `class_exists()` de PHP
- El sistema es **lazy**: solo carga lo necesario
- Compatible con **multi-sitio** de WordPress
- Cache de detección para rendimiento
- Todas las fechas en formato **ISO 8601**
- Colores en formato **hexadecimal**
- Íconos usan nombres de **Material Icons**

---

**¡El sistema está listo para que las apps lo usen!** 🚀
