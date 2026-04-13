# Sistema de Deep Linking para Apps Móviles

## 📱 Resumen

Sistema completo de deep linking similar a Firebase Dynamic Links que permite configurar las apps móviles (Android/iOS) con diferentes empresas u organizaciones usando enlaces dinámicos.

**Ventajas:**
- ✅ Una sola APK para múltiples empresas
- ✅ Configuración dinámica (colores, logo, API, módulos)
- ✅ Enlaces inteligentes que detectan el dispositivo
- ✅ QR codes automáticos
- ✅ Smart App Banners para iOS
- ✅ Redirección automática a tiendas
- ✅ Deep links nativos para Android/iOS

## 🏗️ Arquitectura

### Flujo de Funcionamiento

```
1. Usuario recibe enlace → https://midominio.com/app/basabere

2. Sistema detecta dispositivo:
   - Mobile: Intenta abrir app (flavorapp://company/basabere)
   - Si no está instalada: Redirige a tienda con parámetros
   - Desktop: Muestra página informativa con QR

3. App al iniciar:
   - Lee el deep link: flavorapp://company/basabere
   - Extrae el slug: "basabere"
   - Llama a API: GET /wp-json/flavor-app/v1/config/basabere
   - Recibe configuración completa
   - Se configura como "App de Basabere"
```

### Endpoints REST API

#### GET `/wp-json/flavor-app/v1/config/{slug}`
Obtiene la configuración de una empresa.

**Respuesta:**
```json
{
  "slug": "basabere",
  "nombre": "Basabere",
  "descripcion": "Comunidad de Basauri",
  "logo": "https://cdn.miapp.com/logos/basabere.png",
  "api_base": "https://basabere.eus/wp-json",
  "colores": {
    "primario": "#3B82F6",
    "secundario": "#8B5CF6",
    "acento": "#10B981",
    "fondo": "#FFFFFF",
    "texto": "#1F2937",
    "error": "#EF4444",
    "exito": "#10B981",
    "advertencia": "#F59E0B"
  },
  "tema": "light",
  "idioma": "es",
  "modulos_activos": [
    "chat",
    "reservas",
    "marketplace",
    "carpooling"
  ],
  "config_adicional": {}
}
```

#### GET `/wp-json/flavor-app/v1/companies`
Lista todas las empresas activas (público).

**Respuesta:**
```json
[
  {
    "slug": "basabere",
    "nombre": "Basabere",
    "descripcion": "Comunidad de Basauri",
    "logo": "https://..."
  },
  {
    "slug": "acme",
    "nombre": "ACME Corp",
    "descripcion": "Empresa de tecnología",
    "logo": "https://..."
  }
]
```

#### POST `/wp-json/flavor-app/v1/config` (Admin)
Crea o actualiza una configuración de empresa.

**Request:**
```json
{
  "slug": "basabere",
  "nombre": "Basabere",
  "descripcion": "Comunidad de Basauri",
  "logo_url": "https://...",
  "api_base": "https://basabere.eus/wp-json",
  "colores": {
    "primario": "#3B82F6",
    ...
  },
  "tema": "light",
  "idioma": "es",
  "modulos_activos": ["chat", "reservas"],
  "activo": true
}
```

#### DELETE `/wp-json/flavor-app/v1/config/{slug}` (Admin)
Elimina una configuración de empresa.

#### GET `/wp-json/flavor-app/v1/generate-link/{slug}` (Admin)
Genera enlaces dinámicos para una empresa.

**Respuesta:**
```json
{
  "success": true,
  "slug": "basabere",
  "links": {
    "api_config_url": "https://midominio.com/wp-json/flavor-app/v1/config/basabere",
    "short_link": "https://midominio.com/app/basabere",
    "android_deep_link": "flavorapp://company/basabere",
    "ios_universal_link": "https://midominio.com/app/basabere",
    "google_play_link": "https://play.google.com/store/apps/details?id=com.flavor.community&referrer=company%3Dbasabere",
    "app_store_link": "https://apps.apple.com/app/id123456789?pt=company&ct=basabere",
    "qr_data": "https://midominio.com/app/basabere"
  }
}
```

## 🚀 Implementación en Flutter

### 1. Dependencias en `pubspec.yaml`

```yaml
dependencies:
  uni_links: ^0.5.1  # Para deep links
  app_links: ^3.4.0  # Para Universal Links (iOS)
  shared_preferences: ^2.2.0  # Para guardar configuración
  http: ^1.1.0
```

### 2. Configuración Android

**AndroidManifest.xml:**
```xml
<manifest>
  <application>
    <activity android:name=".MainActivity">
      <!-- Deep Link Scheme -->
      <intent-filter>
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data
          android:scheme="flavorapp"
          android:host="company" />
      </intent-filter>

      <!-- Universal Links / App Links -->
      <intent-filter android:autoVerify="true">
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data
          android:scheme="https"
          android:host="midominio.com"
          android:pathPrefix="/app" />
      </intent-filter>
    </activity>
  </application>
</manifest>
```

### 3. Configuración iOS

**Info.plist:**
```xml
<key>CFBundleURLTypes</key>
<array>
  <dict>
    <key>CFBundleURLSchemes</key>
    <array>
      <string>flavorapp</string>
    </array>
  </dict>
</array>

<!-- Associated Domains para Universal Links -->
<key>com.apple.developer.associated-domains</key>
<array>
  <string>applinks:midominio.com</string>
</array>
```

**apple-app-site-association (en tu servidor):**

Ubicación: `https://midominio.com/.well-known/apple-app-site-association`

```json
{
  "applinks": {
    "apps": [],
    "details": [
      {
        "appID": "TEAM_ID.com.flavor.community",
        "paths": ["/app/*"]
      }
    ]
  }
}
```

### 4. Modelo de Configuración

```dart
// lib/models/company_config.dart

class CompanyConfig {
  final String slug;
  final String nombre;
  final String? descripcion;
  final String? logo;
  final String apiBase;
  final Map<String, String> colores;
  final String tema;
  final String idioma;
  final List<String> modulosActivos;

  CompanyConfig({
    required this.slug,
    required this.nombre,
    this.descripcion,
    this.logo,
    required this.apiBase,
    required this.colores,
    this.tema = 'light',
    this.idioma = 'es',
    this.modulosActivos = const [],
  });

  factory CompanyConfig.fromJson(Map<String, dynamic> json) {
    return CompanyConfig(
      slug: json['slug'],
      nombre: json['nombre'],
      descripcion: json['descripcion'],
      logo: json['logo'],
      apiBase: json['api_base'],
      colores: Map<String, String>.from(json['colores'] ?? {}),
      tema: json['tema'] ?? 'light',
      idioma: json['idioma'] ?? 'es',
      modulosActivos: List<String>.from(json['modulos_activos'] ?? []),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'slug': slug,
      'nombre': nombre,
      'descripcion': descripcion,
      'logo': logo,
      'api_base': apiBase,
      'colores': colores,
      'tema': tema,
      'idioma': idioma,
      'modulos_activos': modulosActivos,
    };
  }
}
```

### 5. Servicio de Configuración

```dart
// lib/services/config_service.dart

import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ConfigService {
  static const String CONFIG_KEY = 'company_config';
  static const String API_BASE_URL = 'https://midominio.com/wp-json/flavor-app/v1';

  // Obtener configuración guardada
  Future<CompanyConfig?> getSavedConfig() async {
    final prefs = await SharedPreferences.getInstance();
    final configJson = prefs.getString(CONFIG_KEY);

    if (configJson != null) {
      return CompanyConfig.fromJson(json.decode(configJson));
    }

    return null;
  }

  // Guardar configuración
  Future<void> saveConfig(CompanyConfig config) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(CONFIG_KEY, json.encode(config.toJson()));
  }

  // Obtener configuración desde API
  Future<CompanyConfig> fetchConfig(String slug) async {
    final url = Uri.parse('$API_BASE_URL/config/$slug');
    final response = await http.get(url);

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      final config = CompanyConfig.fromJson(data);

      // Guardar para uso futuro
      await saveConfig(config);

      return config;
    } else {
      throw Exception('Error al obtener configuración: ${response.statusCode}');
    }
  }

  // Limpiar configuración (logout)
  Future<void> clearConfig() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(CONFIG_KEY);
  }

  // Verificar si hay configuración
  Future<bool> hasConfig() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.containsKey(CONFIG_KEY);
  }
}
```

### 6. Servicio de Deep Links

```dart
// lib/services/deep_link_service.dart

import 'dart:async';
import 'package:uni_links/uni_links.dart';
import 'package:flutter/services.dart';

class DeepLinkService {
  StreamSubscription? _sub;

  // Inicializar listener de deep links
  Future<void> initDeepLinks(Function(String slug) onCompanyLink) async {
    try {
      // Verificar deep link inicial (app cerrada)
      final initialLink = await getInitialLink();
      if (initialLink != null) {
        _handleDeepLink(initialLink, onCompanyLink);
      }

      // Listener para deep links mientras la app está abierta
      _sub = linkStream.listen((String? link) {
        if (link != null) {
          _handleDeepLink(link, onCompanyLink);
        }
      }, onError: (err) {
        print('Error en deep link: $err');
      });
    } on PlatformException {
      print('Error al inicializar deep links');
    }
  }

  void _handleDeepLink(String link, Function(String slug) onCompanyLink) {
    print('Deep link recibido: $link');

    // flavorapp://company/basabere
    final uri = Uri.parse(link);

    if (uri.scheme == 'flavorapp' && uri.host == 'company') {
      final slug = uri.pathSegments.isNotEmpty ? uri.pathSegments[0] : null;

      if (slug != null) {
        onCompanyLink(slug);
      }
    }

    // https://midominio.com/app/basabere
    if (uri.scheme == 'https' && uri.pathSegments.isNotEmpty) {
      if (uri.pathSegments[0] == 'app' && uri.pathSegments.length > 1) {
        final slug = uri.pathSegments[1];
        onCompanyLink(slug);
      }
    }
  }

  void dispose() {
    _sub?.cancel();
  }
}
```

### 7. Implementación en main.dart

```dart
// lib/main.dart

import 'package:flutter/material.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatefulWidget {
  @override
  _MyAppState createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  final ConfigService _configService = ConfigService();
  final DeepLinkService _deepLinkService = DeepLinkService();

  CompanyConfig? _config;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    // 1. Intentar cargar configuración guardada
    final savedConfig = await _configService.getSavedConfig();

    if (savedConfig != null) {
      setState(() {
        _config = savedConfig;
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }

    // 2. Inicializar deep links
    _deepLinkService.initDeepLinks((slug) async {
      print('Configurando app para: $slug');
      await _loadConfigForCompany(slug);
    });
  }

  Future<void> _loadConfigForCompany(String slug) async {
    try {
      setState(() => _isLoading = true);

      final config = await _configService.fetchConfig(slug);

      setState(() {
        _config = config;
        _isLoading = false;
      });

      // Navegar a home screen
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => HomeScreen(config: config)),
      );
    } catch (e) {
      print('Error al cargar configuración: $e');
      setState(() => _isLoading = false);

      // Mostrar error
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error al cargar configuración: $e')),
      );
    }
  }

  @override
  void dispose() {
    _deepLinkService.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return MaterialApp(
        home: Scaffold(
          body: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    if (_config == null) {
      // No hay configuración - mostrar pantalla de selección
      return MaterialApp(
        home: CompanySelectionScreen(
          onCompanySelected: _loadConfigForCompany,
        ),
      );
    }

    // Aplicar tema según configuración
    final primaryColor = _parseColor(_config!.colores['primario'] ?? '#3B82F6');
    final secondaryColor = _parseColor(_config!.colores['secundario'] ?? '#8B5CF6');

    return MaterialApp(
      title: _config!.nombre,
      theme: ThemeData(
        primaryColor: primaryColor,
        colorScheme: ColorScheme.fromSeed(
          seedColor: primaryColor,
          secondary: secondaryColor,
        ),
        brightness: _config!.tema == 'dark' ? Brightness.dark : Brightness.light,
      ),
      home: HomeScreen(config: _config!),
    );
  }

  Color _parseColor(String hex) {
    return Color(int.parse(hex.substring(1), radix: 16) + 0xFF000000);
  }
}
```

### 8. Pantalla de Selección de Empresa

```dart
// lib/screens/company_selection_screen.dart

class CompanySelectionScreen extends StatefulWidget {
  final Function(String slug) onCompanySelected;

  const CompanySelectionScreen({required this.onCompanySelected});

  @override
  _CompanySelectionScreenState createState() => _CompanySelectionScreenState();
}

class _CompanySelectionScreenState extends State<CompanySelectionScreen> {
  List<Map<String, dynamic>> _companies = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadCompanies();
  }

  Future<void> _loadCompanies() async {
    try {
      final response = await http.get(
        Uri.parse('https://midominio.com/wp-json/flavor-app/v1/companies'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body) as List;
        setState(() {
          _companies = List<Map<String, dynamic>>.from(data);
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error al cargar empresas: $e');
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Selecciona tu Organización')),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : ListView.builder(
              padding: EdgeInsets.all(16),
              itemCount: _companies.length,
              itemBuilder: (context, index) {
                final company = _companies[index];
                return Card(
                  margin: EdgeInsets.only(bottom: 16),
                  child: ListTile(
                    leading: company['logo'] != null
                        ? CircleAvatar(backgroundImage: NetworkImage(company['logo']))
                        : CircleAvatar(child: Icon(Icons.business)),
                    title: Text(company['nombre']),
                    subtitle: Text(company['descripcion'] ?? ''),
                    trailing: Icon(Icons.arrow_forward_ios),
                    onTap: () => widget.onCompanySelected(company['slug']),
                  ),
                );
              },
            ),
    );
  }
}
```

## 🎨 Panel de Administración

Accede desde WordPress Admin:
**Flavor Chat IA > Deep Links App**

### Funcionalidades:

1. **Crear Nueva Empresa**
   - Slug único (ej: `basabere`)
   - Nombre y descripción
   - Logo (desde media library)
   - URL base de API
   - Paleta de colores personalizada
   - Tema (claro/oscuro/automático)
   - Idioma
   - Módulos activos

2. **Generar Enlaces**
   - Enlaces cortos (`/app/basabere`)
   - Deep links nativos (`flavorapp://company/basabere`)
   - Enlaces a tiendas con parámetros
   - QR codes automáticos
   - Universal links iOS

3. **Gestionar Empresas**
   - Editar configuraciones
   - Activar/desactivar
   - Ver estadísticas de uso

## 📊 Base de Datos

### Tabla: `wp_flavor_company_configs`

```sql
CREATE TABLE `wp_flavor_company_configs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `logo_url` varchar(500),
  `api_base` varchar(500) NOT NULL,
  `configuracion` longtext,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `activo` (`activo`)
);
```

## 🔗 URLs y Rewrite Rules

El sistema añade automáticamente la regla:
```
/app/([a-z0-9-]+) → flavorapp://company/$1
```

**Ejemplos:**
- `https://midominio.com/app/basabere` → Detecta dispositivo y actúa en consecuencia
- `https://midominio.com/app/acme` → Detecta dispositivo y actúa en consecuencia

## 🧪 Testing

### 1. Probar en Navegador Desktop
```
https://midominio.com/app/basabere
```
Debería mostrar página informativa con QR code y enlaces a tiendas.

### 2. Probar en Navegador Móvil
```
https://midominio.com/app/basabere
```
Debería intentar abrir la app o redirigir a la tienda.

### 3. Probar Deep Link Directo
En la terminal del dispositivo Android:
```bash
adb shell am start -a android.intent.action.VIEW -d "flavorapp://company/basabere"
```

En iOS (simulador):
```bash
xcrun simctl openurl booted "flavorapp://company/basabere"
```

### 4. Probar API REST

**Obtener configuración:**
```bash
curl https://midominio.com/wp-json/flavor-app/v1/config/basabere
```

**Crear configuración (con auth):**
```bash
curl -X POST https://midominio.com/wp-json/flavor-app/v1/config \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "slug": "test",
    "nombre": "Test Company",
    "api_base": "https://test.com/wp-json"
  }'
```

## 🚨 Troubleshooting

### "Empresa no encontrada"
- Verifica que el slug existe en el panel de admin
- Verifica que la empresa está activa
- Limpia caché de WordPress

### Deep link no funciona en Android
- Verifica que el `intent-filter` está en el `MainActivity`
- Verifica que el esquema es `flavorapp` (minúsculas)
- Reinstala la app después de cambiar el manifest

### Universal link no funciona en iOS
- Verifica que el archivo `apple-app-site-association` es accesible vía HTTPS
- NO debe tener extensión `.json`
- Debe estar en `/.well-known/` o en la raíz
- El content-type debe ser `application/json`
- Reinstala la app después de cambios

### La configuración no se aplica
- Verifica que la API REST es accesible (CORS)
- Comprueba los logs de la app
- Limpia datos de la app y prueba de nuevo

## 📝 Notas Importantes

1. **Seguridad**: Los endpoints de configuración son públicos (necesario para que las apps funcionen). No incluyas información sensible en las configuraciones.

2. **Caché**: Las apps deben cachear la configuración localmente para funcionar offline.

3. **Versionado**: Considera añadir un campo `version` a la configuración para forzar actualizaciones.

4. **Analytics**: Considera tracking de instalaciones por empresa usando los parámetros de las tiendas.

5. **IDs de Apps**: Actualiza los IDs en los enlaces de las tiendas (`com.flavor.community`, `123456789`) con los reales.

## 📚 Referencias

- [Android Deep Links](https://developer.android.com/training/app-links)
- [iOS Universal Links](https://developer.apple.com/ios/universal-links/)
- [uni_links package](https://pub.dev/packages/uni_links)
- [app_links package](https://pub.dev/packages/app_links)
