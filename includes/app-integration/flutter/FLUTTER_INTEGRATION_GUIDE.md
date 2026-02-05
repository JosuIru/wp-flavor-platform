# 📱 Guía de Integración Flutter - Directorio de Negocios

## 🎯 Objetivo

Integrar el sistema de directorio de negocios en las apps Flutter existentes para permitir que los usuarios:
- Descubran negocios/comunidades cercanas
- Cambien entre diferentes negocios sin cambiar de app
- Usen la misma app con múltiples WordPress

---

## 📁 Estructura de Archivos

Todos los archivos necesarios están en la carpeta `flutter/`:

```
flutter/
├── lib/
│   ├── core/
│   │   ├── config/
│   │   │   └── api_config.dart                 ← Configuración de API
│   │   ├── models/
│   │   │   ├── business.dart                   ← Modelo de negocio
│   │   │   ├── business_filter.dart            ← Filtros de búsqueda
│   │   │   ├── site_config.dart                ← Configuración del sitio
│   │   │   └── system_info.dart                ← Info del sistema WordPress
│   │   └── services/
│   │       ├── business_directory_service.dart  ← Servicio de directorio
│   │       ├── business_switcher_service.dart   ← Cambio entre negocios
│   │       └── plugin_detector_service.dart     ← Detección de plugins
│   └── screens/
│       └── switch_business_screen.dart          ← Pantalla de cambio
└── FLUTTER_INTEGRATION_GUIDE.md                 ← Este archivo
```

---

## 🚀 Paso 1: Añadir Dependencias

Actualiza tu `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter

  # HTTP requests
  http: ^1.1.0

  # Almacenamiento local
  shared_preferences: ^2.2.2

  # JSON (incluido en Dart)
  # No necesitas añadir nada para json
```

Ejecuta:
```bash
flutter pub get
```

---

## 🚀 Paso 2: Copiar los Archivos

Copia todos los archivos de la carpeta `flutter/lib/` a tu proyecto:

```bash
# Desde la raíz de tu proyecto Flutter
cp -r /path/to/flavor-chat-ia/includes/app-integration/flutter/lib/* lib/
```

O hazlo manualmente:
1. Crea las carpetas necesarias en tu proyecto
2. Copia cada archivo a su ubicación correspondiente

---

## 🚀 Paso 3: Configurar la API

Actualiza `lib/core/config/api_config.dart` con tu configuración:

```dart
class ApiConfig {
  // URL base de tu sitio WordPress
  static String baseUrl = 'https://tudominio.com';

  // Token de API generado desde el panel de WordPress
  static String apiToken = 'tu-token-aqui';

  // URL del servidor de directorio central (opcional)
  // Si no tienes servidor central, déjalo null
  static String? directoryServerUrl;

  // ... resto del código
}
```

### ¿Cómo obtener el token?

1. Ve a tu WordPress Admin
2. **Flavor Chat IA** → **Apps Móviles** → **Seguridad**
3. Genera un nuevo token
4. Copia el token generado
5. Úsalo en `ApiConfig.apiToken`

---

## 🚀 Paso 4: Inicializar el Servicio en tu App

En tu `main.dart`:

```dart
import 'package:flutter/material.dart';
import 'core/services/business_switcher_service.dart';
import 'core/config/api_config.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Inicializar el servicio de cambio de negocio
  final switcherService = BusinessSwitcherService();
  await switcherService.initialize();

  // Si hay un negocio guardado, configurar la API
  if (switcherService.currentBusiness != null) {
    ApiConfig.configure(
      newBaseUrl: switcherService.currentBusiness!.url,
      newApiToken: 'el-token-del-negocio',
    );
  }

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final switcherService = BusinessSwitcherService();
    final siteConfig = switcherService.currentSiteConfig;

    return MaterialApp(
      title: siteConfig?.siteName ?? 'Mi App',
      theme: ThemeData(
        // Usar los colores del sitio actual
        primaryColor: siteConfig != null
            ? _hexToColor(siteConfig.primaryColor)
            : Colors.blue,
        colorScheme: ColorScheme.fromSeed(
          seedColor: siteConfig != null
              ? _hexToColor(siteConfig.primaryColor)
              : Colors.blue,
        ),
      ),
      home: const HomeScreen(),
    );
  }

  // Helper para convertir hex a Color
  Color _hexToColor(String hex) {
    hex = hex.replaceAll('#', '');
    return Color(int.parse('FF$hex', radix: 16));
  }
}
```

---

## 🚀 Paso 5: Añadir el Botón de Cambio de Negocio

En tu pantalla principal o menú lateral:

```dart
import 'package:flutter/material.dart';
import 'screens/switch_business_screen.dart';
import 'core/services/business_switcher_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _switcherService = BusinessSwitcherService();

  @override
  Widget build(BuildContext context) {
    final currentBusiness = _switcherService.currentBusiness;

    return Scaffold(
      appBar: AppBar(
        title: Text(currentBusiness?.name ?? 'Mi App'),
        actions: [
          // Botón para cambiar de negocio
          IconButton(
            icon: const Icon(Icons.swap_horiz),
            tooltip: 'Cambiar de negocio',
            onPressed: () async {
              final changed = await Navigator.push<bool>(
                context,
                MaterialPageRoute(
                  builder: (context) => const SwitchBusinessScreen(),
                ),
              );

              // Si se cambió de negocio, recargar la app
              if (changed == true && mounted) {
                // Opción 1: Recargar la pantalla actual
                setState(() {});

                // Opción 2: Reiniciar toda la app
                // Phoenix.rebirth(context);
              }
            },
          ),
        ],
      ),
      drawer: _buildDrawer(),
      body: const Center(
        child: Text('Pantalla principal'),
      ),
    );
  }

  /// Drawer con información del negocio actual
  Widget _buildDrawer() {
    final currentBusiness = _switcherService.currentBusiness;
    final siteConfig = _switcherService.currentSiteConfig;

    return Drawer(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          UserAccountsDrawerHeader(
            decoration: BoxDecoration(
              color: siteConfig != null
                  ? _hexToColor(siteConfig.primaryColor)
                  : Colors.blue,
            ),
            accountName: Text(currentBusiness?.name ?? 'Sin negocio'),
            accountEmail: Text(currentBusiness?.regionName ?? ''),
            currentAccountPicture: currentBusiness?.logoUrl != null
                ? CircleAvatar(
                    backgroundImage: NetworkImage(currentBusiness!.logoUrl!),
                  )
                : const CircleAvatar(
                    child: Icon(Icons.store),
                  ),
          ),
          ListTile(
            leading: const Icon(Icons.swap_horiz),
            title: const Text('Cambiar de negocio'),
            onTap: () async {
              Navigator.pop(context); // Cerrar drawer

              final changed = await Navigator.push<bool>(
                context,
                MaterialPageRoute(
                  builder: (context) => const SwitchBusinessScreen(),
                ),
              );

              if (changed == true && mounted) {
                setState(() {});
              }
            },
          ),
          const Divider(),
          // ... otros items del menú
        ],
      ),
    );
  }

  Color _hexToColor(String hex) {
    hex = hex.replaceAll('#', '');
    return Color(int.parse('FF$hex', radix: 16));
  }
}
```

---

## 🚀 Paso 6: Probar el Sistema

### Escenario de prueba:

1. **Inicia la app** conectada a tu negocio principal
2. **Tap en "Cambiar de negocio"**
3. Verás una lista de negocios disponibles (si hay más de uno público)
4. **Filtra por región** o **busca por nombre**
5. **Tap en un negocio** diferente
6. La app se conecta automáticamente
7. **Verifica que**:
   - Los colores cambien
   - El logo cambie
   - El nombre del negocio cambie
   - Los módulos disponibles cambien

---

## 🔧 Personalización Avanzada

### Persistencia de múltiples tokens

Si cada negocio tiene su propio token:

```dart
// En business_switcher_service.dart
class BusinessSwitcherService {
  // Mapa de tokens por URL
  final Map<String, String> _businessTokens = {};

  Future<bool> switchToBusiness(Business business) async {
    // ... código existente ...

    // Obtener el token para este negocio
    final token = _businessTokens[business.url];
    if (token != null) {
      ApiConfig.configure(
        newBaseUrl: business.url,
        newApiToken: token,
      );
    }

    return true;
  }

  // Método para guardar token de un negocio
  Future<void> saveBusinessToken(String url, String token) async {
    _businessTokens[url] = token;
    // Persistir en SharedPreferences
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('business_token_$url', token);
  }
}
```

### Caché de negocios

Para mejorar el rendimiento:

```dart
// En business_directory_service.dart
class BusinessDirectoryService {
  List<Business>? _cachedBusinesses;
  DateTime? _lastCacheUpdate;

  static const cacheDuration = Duration(hours: 1);

  Future<List<Business>> getBusinesses({BusinessFilter? filter}) async {
    // Si hay caché válida y no hay filtros, usar caché
    if (_cachedBusinesses != null &&
        _lastCacheUpdate != null &&
        DateTime.now().difference(_lastCacheUpdate!) < cacheDuration &&
        filter == null) {
      return _cachedBusinesses!;
    }

    // Obtener de la API
    final businesses = await _fetchBusinessesFromApi(filter);

    // Cachear solo si no hay filtros
    if (filter == null) {
      _cachedBusinesses = businesses;
      _lastCacheUpdate = DateTime.now();
    }

    return businesses;
  }
}
```

### Reiniciar la app completamente

Para reiniciar toda la app al cambiar de negocio, usa el paquete `flutter_phoenix`:

```yaml
dependencies:
  flutter_phoenix: ^1.1.1
```

```dart
import 'package:flutter_phoenix/flutter_phoenix.dart';

void main() {
  runApp(
    Phoenix(
      child: const MyApp(),
    ),
  );
}

// Para reiniciar la app:
Phoenix.rebirth(context);
```

---

## 🧪 Testing

### Test unitario para BusinessDirectoryService:

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:your_app/core/services/business_directory_service.dart';

void main() {
  group('BusinessDirectoryService', () {
    late BusinessDirectoryService service;

    setUp(() {
      service = BusinessDirectoryService();
    });

    test('getBusinesses retorna lista de negocios', () async {
      final businesses = await service.getBusinesses();
      expect(businesses, isNotNull);
      expect(businesses, isA<List>());
    });

    test('getAvailableRegions retorna mapa de regiones', () async {
      final regions = await service.getAvailableRegions();
      expect(regions, isNotNull);
      expect(regions['euskal_herria'], 'Euskal Herria');
    });
  });
}
```

---

## 🎨 UI/UX Mejorado

### Animaciones al cambiar de negocio:

```dart
class _HomeScreenState extends State<HomeScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 500),
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(_controller);
    _controller.forward();
  }

  Future<void> _switchBusiness() async {
    final changed = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (context) => const SwitchBusinessScreen()),
    );

    if (changed == true && mounted) {
      // Fade out
      await _controller.reverse();

      // Recargar
      setState(() {});

      // Fade in
      await _controller.forward();
    }
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: Scaffold(
        // ... tu UI
      ),
    );
  }
}
```

---

## 📊 Métricas y Analytics

Para hacer seguimiento de cambios de negocio:

```dart
import 'package:firebase_analytics/firebase_analytics.dart';

class BusinessSwitcherService {
  final _analytics = FirebaseAnalytics.instance;

  Future<bool> switchToBusiness(Business business) async {
    // ... código existente ...

    if (success) {
      // Registrar evento
      await _analytics.logEvent(
        name: 'business_switched',
        parameters: {
          'business_url': business.url,
          'business_name': business.name,
          'business_region': business.region,
        },
      );
    }

    return success;
  }
}
```

---

## 🆘 Solución de Problemas

### Error: "No se puede conectar con el negocio"

**Causa**: El sitio no responde o no tiene el plugin instalado

**Solución**:
1. Verifica que el sitio esté online
2. Comprueba que tenga Flavor Chat IA instalado
3. Verifica que el endpoint `/wp-json/app-discovery/v1/info` responda

### Error: "Token inválido"

**Causa**: El token de API es incorrecto o ha sido revocado

**Solución**:
1. Genera un nuevo token desde WordPress Admin
2. Actualiza `ApiConfig.apiToken` en la app
3. Si es producción, sube una nueva versión de la app

### Los colores no cambian

**Causa**: El tema no se actualiza correctamente

**Solución**:
1. Asegúrate de llamar a `setState()` después de cambiar de negocio
2. O usa un sistema de state management (Provider, Riverpod, etc.)
3. O reinicia la app completamente con `Phoenix.rebirth()`

---

## 🎉 ¡Listo!

Tu app ahora puede:
- ✅ Descubrir negocios cercanos
- ✅ Cambiar entre negocios sin reinstalar
- ✅ Adaptar su apariencia automáticamente
- ✅ Cargar módulos dinámicamente

---

## 📚 Recursos Adicionales

- **Backend**: `/includes/app-integration/DIRECTORIO_NEGOCIOS.md`
- **Panel Admin**: `/includes/app-integration/GUIA_PANEL_APPS.md`
- **APIs**: `/includes/modules/*/API_MOBILE.md`

---

**¡Tu app multi-tenant está lista para funcionar!** 🚀
