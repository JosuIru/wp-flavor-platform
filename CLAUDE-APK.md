# CLAUDE-APK.md - Instrucciones para Configuración de APKs

## REGLA FUNDAMENTAL: ALINEACIÓN DE 3 NIVELES

Antes de configurar cualquier APK, DEBES verificar que los módulos están disponibles en los **3 niveles**:

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   WORDPRESS     │ ←→  │    FLUTTER      │ ←→  │      API        │
│   (módulos)     │     │   (templates)   │     │   (endpoints)   │
└─────────────────┘     └─────────────────┘     └─────────────────┘
       ↓                       ↓                       ↓
  ¿Está activo?          ¿Existe screen?       ¿Responde endpoint?
```

### Comportamiento según soporte

| Soporte | Acción de Claude Code |
|---------|----------------------|
| **3/3 completo** | Habilitar automáticamente |
| **2/3 parcial** | **Pedir permiso al usuario** explicando qué falta |
| **1/3 o menos** | Advertir y recomendar no habilitar |

### Cuándo pedir permiso

Si un módulo tiene soporte parcial pero parece necesario para el proyecto, Claude Code DEBE:

1. Explicar qué nivel falta:
   - "El módulo X está activo en WordPress pero no tiene template Flutter"
   - "El módulo X tiene template pero no está activo en WordPress"
2. Explicar las consecuencias:
   - Sin Flutter → Se usará WebView fallback (más lento, menos integrado)
   - Sin WordPress → La funcionalidad no estará disponible en el backend
   - Sin API → Los datos no se sincronizarán correctamente
3. Preguntar explícitamente:
   - "¿Quieres que lo habilite de todas formas usando fallback WebView?"
   - "¿Prefieres que active primero el módulo en WordPress?"

### Ejemplo de solicitud de permiso

```
He ejecutado el inventario y encontré estos módulos con soporte parcial:

┌─────────────────┬───────────┬─────────┬─────┬────────────────────────────┐
│ Módulo          │ WordPress │ Flutter │ API │ Problema                   │
├─────────────────┼───────────┼─────────┼─────┼────────────────────────────┤
│ chat-grupos     │ ✓         │ ✗       │ ✓   │ Sin template Flutter       │
│ energia-solar   │ ✓         │ ✓       │ ✗   │ API no responde            │
│ biblioteca      │ ✗         │ ✓       │ ✓   │ No activo en WordPress     │
└─────────────────┴───────────┴─────────┴─────┴────────────────────────────┘

Para el proyecto de cooperativa que describes, estos módulos podrían ser útiles:

1. **chat-grupos** - Permitiría comunicación entre socios
   → Fallback: WebView (funcional pero más lento)

2. **biblioteca** - Para compartir documentos
   → Requiere: Activar módulo en WordPress primero

¿Quieres que:
- [ ] Habilite chat-grupos con WebView fallback
- [ ] Active biblioteca en WordPress y luego la añada a la app
- [ ] Ignore ambos por ahora
```

---

## PASO 0: Discovery Obligatorio

ANTES de modificar cualquier configuración de APK, ejecuta:

```bash
bash tools/apk-inventory.sh "http://SITIO" "mobile-apps"
```

Este script verifica:
1. Módulos activos en WordPress
2. Templates Flutter implementados (con *_screen.dart)
3. Endpoints API disponibles
4. Configuración actual de app_config.dart
5. Matriz de compatibilidad 3 niveles

---

## Estructura del Proyecto Flutter

```
mobile-apps/
├── lib/
│   ├── core/
│   │   ├── api/              # Cliente HTTP
│   │   ├── config/
│   │   │   └── app_config.dart  # ← CONFIGURACIÓN PRINCIPAL
│   │   ├── modules/
│   │   │   ├── module_definition.dart
│   │   │   └── builders/
│   │   │       └── module_screen_builder.dart
│   │   ├── theme/            # Colores, estilos
│   │   └── widgets/          # Widgets reutilizables
│   ├── features/
│   │   └── modules/          # ← TEMPLATES DE MÓDULOS
│   │       ├── eventos/
│   │       │   └── eventos_screen.dart
│   │       ├── socios/
│   │       ├── foros/
│   │       └── ... (55 módulos)
│   ├── main_admin.dart       # Entry point app admin
│   ├── main_client.dart      # Entry point app cliente
│   └── main.dart             # Entry point general
├── android/                  # Config Android
├── ios/                      # Config iOS
├── pubspec.yaml              # Dependencias
└── build_app.sh              # Script de construcción
```

---

## Archivo Principal: app_config.dart

Ubicación: `mobile-apps/lib/core/config/app_config.dart`

### Campos Obligatorios

```dart
class AppConfig {
  // Identidad de la app
  static const String appName = 'Mi App';           // Nombre visible
  static const String appId = 'com.example.app';    // Package name Android

  // Conexión con WordPress
  static const String serverUrl = 'https://sitio.com';
  static const String apiUrl = '$serverUrl/wp-json/chat-ia-mobile/v1';

  // Módulos habilitados - SOLO los que tienen soporte 3/3
  static const List<String> enabledModules = [
    'eventos',
    'socios',
    'foros',
    // ... solo módulos verificados
  ];
}
```

### Regla de IDs

| WordPress ID | Flutter Folder | Usar en enabledModules |
|--------------|----------------|------------------------|
| `eventos` | `eventos/` | `'eventos'` |
| `grupos-consumo` | `grupos_consumo/` | `'grupos-consumo'` |
| `banco-tiempo` | `banco_tiempo/` | `'banco-tiempo'` |
| `bicicletas-compartidas` | `bicicletas_compartidas/` | `'bicicletas-compartidas'` |

**Nota**: Los IDs usan guiones (`-`), las carpetas Flutter usan guiones bajos (`_`).

---

## Módulos Flutter Disponibles

### Con Soporte Completo (verificar con inventory)

| Categoría | Módulos |
|-----------|---------|
| **Comunidad** | eventos, foros, socios, comunidades, participacion |
| **Economía** | marketplace, grupos-consumo, banco-tiempo, woocommerce |
| **Reservas** | reservas, espacios-comunes, bicicletas-compartidas, parkings |
| **Formación** | cursos, talleres, biblioteca |
| **Movilidad** | carpooling |
| **Medios** | multimedia, radio, podcast |
| **Gestión** | incidencias, tramites, transparencia |
| **Social** | red-social, chat-interno, chat-grupos |

### Categorías de Módulos (ModuleCategory)

```dart
enum ModuleCategory {
  commerce,     // Comercio (marketplace, woocommerce)
  education,    // Educación (cursos, talleres)
  social,       // Social (foros, red-social)
  community,    // Comunidad (socios, espacios-comunes)
  mobility,     // Movilidad (carpooling, bicicletas)
  media,        // Medios (podcast, radio)
  environment,  // Medio Ambiente (incidencias, reciclaje)
  other         // Otros
}
```

---

## Flujo de Trabajo

### 1. Discovery

```bash
# Ejecutar inventario
bash tools/apk-inventory.sh "http://sitio-prueba.local" "mobile-apps"

# Verificar salida - buscar módulos con 3/3
# MÓDULO                    WORDPRESS    FLUTTER      API
# eventos                   ✓            ✓            ✓   ← USAR
# socios                    ✓            ✓            ✓   ← USAR
# chat-grupos               ✓            ✗            ✗   ← NO USAR
```

### 2. Actualizar app_config.dart

Solo incluir módulos con soporte completo:

```dart
static const List<String> enabledModules = [
  'eventos',    // ✓ WordPress + ✓ Flutter + ✓ API
  'socios',     // ✓ WordPress + ✓ Flutter + ✓ API
  'foros',      // ✓ WordPress + ✓ Flutter + ✓ API
  'marketplace' // ✓ WordPress + ✓ Flutter + ✓ API
  // NO incluir: chat-grupos (sin Flutter template)
];
```

### 3. Configurar Branding

```dart
class AppConfig {
  static const String appName = 'Cooperativa Verde';
  static const String appId = 'com.cooperativaverde.app';
  static const String serverUrl = 'https://cooperativaverde.local';
  // ...
}
```

### 4. Construir APK

```bash
cd mobile-apps

# Debug
flutter build apk --debug

# Release
flutter build apk --release

# Con firma personalizada
./build_app.sh --release --sign
```

---

## API de Configuración Remota

### Endpoints para Configurar Apps desde WordPress

Base: `/wp-json/flavor-vbp/v1/app/`
Header: `X-VBP-Key: <API_KEY>`

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/app/config` | GET | Obtener configuración actual |
| `/app/config` | POST | Actualizar configuración |
| `/app/branding` | GET/POST | Logo, nombre, colores |
| `/app/theme` | GET/POST | Tema de colores |
| `/app/modules` | GET/POST | Módulos habilitados |
| `/app/sync-from-site` | POST | Sincronizar con sitio web |

### Ejemplo: Sincronizar App con Sitio

```bash
# Obtener configuración actual
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -s "http://SITIO/wp-json/flavor-vbp/v1/app/config" \
  -H "X-VBP-Key: $API_KEY"

# Sincronizar automáticamente
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/sync-from-site" \
  -H "X-VBP-Key: $API_KEY"
```

---

## Temas y Colores

### Presets Disponibles

| Preset | Colores Principales | Ideal Para |
|--------|---------------------|------------|
| `modern-blue` | #2563EB, #1E40AF | General |
| `emerald-green` | #059669, #047857 | Ecología, cooperativas |
| `purple-violet` | #7C3AED, #5B21B6 | Cultura, creatividad |
| `warm-orange` | #EA580C, #C2410C | Comunidad, social |
| `corporate` | #1F2937, #374151 | Instituciones |

### Archivo de Colores Flutter

`mobile-apps/lib/core/theme/app_colors.dart`

```dart
class AppColors {
  static const Color primary = Color(0xFF059669);
  static const Color secondary = Color(0xFF047857);
  static const Color background = Color(0xFFF9FAFB);
  static const Color surface = Color(0xFFFFFFFF);
  // ...
}
```

---

## Navegación

### Configurar Tabs Inferiores

En `main_client.dart` o mediante API:

```dart
// Tabs principales (máximo 5)
final List<TabConfig> tabs = [
  TabConfig(id: 'home', label: 'Inicio', icon: Icons.home),
  TabConfig(id: 'eventos', label: 'Eventos', icon: Icons.event),
  TabConfig(id: 'marketplace', label: 'Tienda', icon: Icons.store),
  TabConfig(id: 'comunidad', label: 'Comunidad', icon: Icons.groups),
  TabConfig(id: 'perfil', label: 'Perfil', icon: Icons.person),
];
```

### Regla de Navegación

- Solo mostrar tabs para módulos en `enabledModules`
- Si un módulo no tiene screen, usar WebView fallback
- Máximo 5 tabs en barra inferior

---

## Verificación Pre-Build

Antes de construir la APK, verificar:

```bash
cd mobile-apps

# 1. Verificar configuración
grep "enabledModules" lib/core/config/app_config.dart

# 2. Verificar que existen los templates
for module in eventos socios foros; do
  ls lib/features/modules/${module}/*_screen.dart 2>/dev/null && echo "✓ $module"
done

# 3. Verificar serverUrl correcto
grep "serverUrl" lib/core/config/app_config.dart

# 4. Verificar dependencias
flutter pub get

# 5. Analizar errores
flutter analyze
```

---

## Checklist Final APK

```
[ ] Ejecuté apk-inventory.sh
[ ] Solo habilité módulos con soporte 3/3
[ ] serverUrl apunta al sitio correcto
[ ] appName y appId configurados
[ ] Colores/tema consistentes con el sitio web
[ ] Probé en emulador antes de release
[ ] Firmé la APK para release (si aplica)
```

---

## Errores Comunes

### 1. Módulo no aparece en la app

**Causa**: No está en `enabledModules` o no tiene template Flutter
**Solución**: Verificar con `apk-inventory.sh`

### 2. Pantalla en blanco al abrir módulo

**Causa**: Template Flutter existe pero falta *_screen.dart
**Solución**: Usar WebView fallback o deshabilitar módulo

### 3. Error de conexión

**Causa**: serverUrl incorrecto o API no responde
**Solución**: Verificar URL y ejecutar discovery

### 4. Icono/color incorrecto

**Causa**: Módulo no tiene configuración en module_definition.dart
**Solución**: Añadir mapeo en `_getIconForModule` y `_getColorForModule`

---

## Resumen de Reglas

1. **SIEMPRE** ejecutar `apk-inventory.sh` antes de configurar
2. **NUNCA** habilitar módulos sin soporte en los 3 niveles
3. **SIEMPRE** usar IDs con guiones en enabledModules
4. **VERIFICAR** que serverUrl es accesible desde el móvil
5. **SINCRONIZAR** colores y branding con el sitio web
