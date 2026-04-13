# 🏗️ Arquitectura del Sistema de Generación de APKs

## 📋 Visión General

Sistema completo para generar aplicaciones móviles (Android/iOS) directamente desde WordPress, basado en los módulos activos y la configuración del perfil.

---

## 🎯 Objetivos

1. **Generación automática** de apps Flutter desde WordPress
2. **Configuración visual** sin necesidad de programar
3. **Compilación en la nube** (GitHub Actions, Firebase)
4. **Distribución automática** de APKs
5. **Actualizaciones OTA** (Over-The-Air) de contenido
6. **Sincronización** en tiempo real con WordPress

---

## 🏛️ Componentes del Sistema

### 1. **APK Builder Module** (WordPress Plugin)
**Ubicación**: `includes/modules/apk-builder/`

**Funcionalidades**:
- Panel de configuración de app
- Editor visual de diseño
- Gestor de assets (logos, iconos, splash screens)
- Configurador de módulos incluidos
- Generador de código Flutter
- Integración con servicios de build
- Gestor de versiones
- Sistema de distribución

**Archivos**:
```
includes/modules/apk-builder/
├── class-apk-builder-module.php        # Módulo principal
├── class-apk-config-manager.php        # Gestión de configuración
├── class-apk-code-generator.php        # Generador de código Flutter
├── class-apk-build-service.php         # Integración con servicios build
├── class-apk-assets-manager.php        # Gestión de assets
├── class-apk-version-manager.php       # Control de versiones
├── admin/
│   ├── class-apk-builder-admin.php     # Panel admin
│   ├── views/
│   │   ├── dashboard.php               # Dashboard principal
│   │   ├── configuracion.php           # Configuración app
│   │   ├── diseno.php                  # Editor visual
│   │   ├── modulos.php                 # Selección módulos
│   │   ├── build.php                   # Compilar y descargar
│   │   └── distribucion.php            # Distribución y versiones
│   └── assets/
│       ├── css/
│       └── js/
├── templates/
│   └── flutter/                        # Templates Flutter
│       ├── base/                       # App base
│       ├── modules/                    # Templates de módulos
│       └── widgets/                    # Widgets reutilizables
└── api/
    ├── class-apk-rest-api.php          # API para apps
    └── endpoints/
```

---

### 2. **Flutter App Template** (Código Fuente)
**Ubicación**: `mobile-app-template/`

**Estructura**:
```
mobile-app-template/
├── android/                            # Configuración Android
├── ios/                                # Configuración iOS
├── lib/
│   ├── main.dart                       # Entry point
│   ├── config/
│   │   ├── app_config.dart             # Configuración generada
│   │   ├── api_config.dart             # URLs y endpoints
│   │   └── theme_config.dart           # Tema y colores
│   ├── core/
│   │   ├── services/
│   │   │   ├── api_service.dart        # Cliente HTTP
│   │   │   ├── auth_service.dart       # Autenticación
│   │   │   ├── storage_service.dart    # Almacenamiento local
│   │   │   └── sync_service.dart       # Sincronización
│   │   ├── models/
│   │   │   ├── user.dart
│   │   │   ├── post.dart
│   │   │   └── response.dart
│   │   └── widgets/
│   │       ├── app_drawer.dart
│   │       ├── bottom_nav.dart
│   │       └── loading.dart
│   ├── modules/
│   │   ├── grupos_consumo/
│   │   │   ├── screens/
│   │   │   ├── widgets/
│   │   │   ├── models/
│   │   │   └── services/
│   │   ├── banco_tiempo/
│   │   ├── marketplace/
│   │   ├── woocommerce/
│   │   └── [otros módulos]/
│   └── screens/
│       ├── home_screen.dart
│       ├── splash_screen.dart
│       └── onboarding_screen.dart
├── assets/
│   ├── images/
│   ├── icons/
│   └── fonts/
├── pubspec.yaml                        # Dependencias
├── .github/
│   └── workflows/
│       ├── build-android.yml           # CI/CD Android
│       └── build-ios.yml               # CI/CD iOS
└── build_config.json                   # Configuración de build
```

---

### 3. **Build Pipeline** (GitHub Actions / Firebase)

**Opciones de Build**:

#### Opción A: GitHub Actions (Gratis para repos públicos)
```yaml
# .github/workflows/build-android.yml
name: Build Android APK

on:
  workflow_dispatch:
    inputs:
      config_url:
        description: 'URL to app config'
        required: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-java@v2
      - uses: subosito/flutter-action@v2
      - name: Download config
        run: curl -o lib/config/app_config.dart ${{ github.event.inputs.config_url }}
      - name: Build APK
        run: flutter build apk --release
      - name: Upload APK
        uses: actions/upload-artifact@v2
        with:
          name: app-release.apk
          path: build/app/outputs/flutter-apk/app-release.apk
```

#### Opción B: Codemagic (Especializado en Flutter)
```yaml
# codemagic.yaml
workflows:
  android-workflow:
    name: Android Workflow
    instance_type: mac_mini
    environment:
      flutter: stable
    scripts:
      - flutter pub get
      - flutter build apk --release
    artifacts:
      - build/app/outputs/**/*.apk
```

#### Opción C: Firebase App Distribution
```yaml
# firebase.json
{
  "appDistribution": {
    "serviceCredentialsFile": "service-credentials.json",
    "app": "1:1234567890:android:abcdef1234567890",
    "groups": ["internal-testers", "beta-users"]
  }
}
```

---

### 4. **API Extensions** (WordPress REST)

**Nuevos Endpoints**:

```php
// GET /wp-json/flavor-chat-ia/v1/app/config
// Devuelve configuración completa de la app

// GET /wp-json/flavor-chat-ia/v1/app/modules
// Lista módulos activos con configuración

// GET /wp-json/flavor-chat-ia/v1/app/theme
// Configuración de tema (colores, fuentes)

// POST /wp-json/flavor-chat-ia/v1/app/build
// Inicia proceso de build

// GET /wp-json/flavor-chat-ia/v1/app/build/{id}
// Estado del build

// GET /wp-json/flavor-chat-ia/v1/app/download/{id}
// Descarga APK generado
```

---

## 🔄 Flujo de Trabajo

### Fase 1: Configuración
1. Usuario accede a **Flavor Chat IA > Generador de Apps**
2. Selecciona **perfil** (Grupo Consumo, Tienda, etc.)
3. Configura:
   - Nombre de la app
   - Logo e iconos
   - Colores del tema
   - URL del sitio WordPress
   - Módulos a incluir
   - Configuración de cada módulo

### Fase 2: Generación de Código
1. Sistema genera archivos de configuración Flutter
2. Genera código específico para módulos activos
3. Personaliza tema según colores elegidos
4. Configura URLs y endpoints API
5. Genera archivos de assets

### Fase 3: Compilación
**Opción A: Local (requiere Flutter SDK)**
```bash
# Genera proyecto localmente
wp flavor apk generate

# Usuario compila manualmente
cd generated-app/
flutter build apk --release
```

**Opción B: Nube (automático)**
1. Sistema sube configuración a repositorio GitHub
2. Dispara workflow de GitHub Actions
3. Actions compila la app
4. Sistema descarga APK compilado
5. APK disponible para descarga en WordPress

### Fase 4: Distribución
1. APK disponible en panel admin
2. Opciones de distribución:
   - Descarga directa
   - Firebase App Distribution
   - Play Store (con asistencia)
   - Link de descarga público

### Fase 5: Actualizaciones
1. Usuario hace cambios en WordPress
2. Contenido se actualiza via API (sin recompilar)
3. Para cambios estructurales:
   - Incrementar versión
   - Recompilar app
   - Notificar usuarios de actualización

---

## 📦 Módulos Adaptados para APK

Cada módulo debe tener:

### 1. API REST Completa
```php
// Todos los endpoints necesarios para la app móvil
class Flavor_Chat_GruposConsumo_API {
    // GET, POST, PUT, DELETE
}
```

### 2. Flutter Template
```dart
// lib/modules/grupos_consumo/
// Código Flutter listo para usar
class GruposConsumoModule extends AppModule {
    @override
    List<Route> getRoutes() { ... }

    @override
    Widget getHomeWidget() { ... }
}
```

### 3. Configuración JSON
```json
{
  "id": "grupos_consumo",
  "name": "Grupos de Consumo",
  "icon": "shopping_basket",
  "color": "#46b450",
  "screens": [
    {
      "route": "/pedidos",
      "title": "Pedidos Abiertos",
      "type": "list"
    },
    {
      "route": "/mis-pedidos",
      "title": "Mis Pedidos",
      "type": "detail"
    }
  ],
  "navigation": {
    "showInBottomNav": true,
    "position": 1,
    "icon": "shopping_basket"
  }
}
```

---

## 🎨 Editor Visual

### Panel de Diseño
- **Selector de colores**: Primario, secundario, fondo
- **Tipografía**: Fuente, tamaños
- **Logo**: Upload y preview
- **Iconos**: Generación automática de todos los tamaños
- **Splash screen**: Personalización
- **Preview en vivo**: Simulador visual

### Configuración de Módulos
```
☑ Grupos de Consumo
  ├─ Mostrar en navegación principal
  ├─ Ícono: 🛒
  ├─ Color: #46b450
  └─ Configuración específica:
      ├─ Permitir pedidos desde app
      ├─ Notificaciones push al cerrar ciclo
      └─ Cache de productos: 1 hora

☑ Chat IA
  ├─ Botón flotante
  ├─ Posición: Inferior derecha
  └─ Auto-abrir en primera visita

☑ Perfil de Usuario
  ├─ Mostrar historial
  └─ Permitir edición de datos
```

---

## 🔒 Seguridad

### Autenticación
- **JWT Tokens** para apps móviles
- **Refresh tokens** para sesiones largas
- **Biometría** (huella, Face ID)
- **OAuth** para login social

### Comunicación
- **HTTPS obligatorio**
- **Certificate pinning**
- **Encriptación end-to-end** para datos sensibles

### Storage Local
- **Encriptación** de datos almacenados
- **Secure storage** para tokens
- **Limpieza automática** al logout

---

## 📊 Analytics y Monitoreo

### Integración con:
- **Firebase Analytics**: Eventos y usuarios
- **Crashlytics**: Errores y crashes
- **Google Analytics**: Comportamiento
- **Mixpanel**: Funnels y retención

### Métricas Clave:
- Instalaciones activas
- Tasa de retención
- Tiempo en app
- Acciones por módulo
- Errores y crashes
- Uso de funcionalidades

---

## 🔄 Sincronización

### Estrategia de Cache
```dart
// Niveles de cache
enum CacheStrategy {
  NoCache,           // Siempre desde API
  CacheFirst,        // Cache primero, API como fallback
  NetworkFirst,      // API primero, cache como fallback
  CacheAndNetwork    // Cache inmediato + actualización background
}
```

### Offline First
- **SQLite local** para datos críticos
- **Queue de acciones** cuando no hay conexión
- **Sincronización automática** al recuperar conexión
- **Conflictos**: Last-write-wins o manual

---

## 🚀 Distribución

### Play Store
1. Generar keystore (automático)
2. Firmar APK
3. Crear listing (asistente)
4. Subir a Play Console (API)

### App Store (iOS)
1. Certificados Apple (requiere cuenta)
2. Provisioning profiles
3. Compilación con Xcode Cloud
4. Subir a App Store Connect

### Distribución Directa
- APK descargable desde WordPress
- Link de instalación OTA
- No requiere tiendas oficiales

---

## 💰 Modelo de Negocio

### Free Tier
- Generación ilimitada de apps
- Build manual (descargar código)
- API básica
- Sin branding

### Pro Tier
- Build automático en la nube
- Firebase incluido
- Analytics avanzado
- Push notifications
- Soporte prioritario
- Whitelabel (sin marca)

---

## 📅 Timeline de Implementación

### Sprint 1 (2 semanas): Core
- [x] Arquitectura definida
- [ ] Módulo APK Builder básico
- [ ] Panel de configuración
- [ ] Generador de código Flutter base

### Sprint 2 (2 semanas): Templates
- [ ] App Flutter template base
- [ ] Integración API REST
- [ ] Sistema de autenticación
- [ ] Navegación y routing

### Sprint 3 (2 semanas): Módulos
- [ ] Template Grupos de Consumo
- [ ] Template WooCommerce
- [ ] Template Banco Tiempo
- [ ] Template Marketplace

### Sprint 4 (1 semana): Build
- [ ] Integración GitHub Actions
- [ ] Build automático Android
- [ ] Gestión de versiones
- [ ] Sistema de descarga

### Sprint 5 (1 semana): Diseño
- [ ] Editor visual de tema
- [ ] Gestor de assets
- [ ] Preview en vivo
- [ ] Generación de iconos

### Sprint 6 (1 semana): Testing
- [ ] Tests de integración
- [ ] Build de apps de ejemplo
- [ ] Documentación
- [ ] Video tutoriales

---

## 📖 Documentación Requerida

1. **Guía de Usuario**: Cómo generar tu primera app
2. **Guía de Desarrollador**: Crear templates de módulos
3. **API Reference**: Todos los endpoints documentados
4. **Troubleshooting**: Problemas comunes
5. **FAQs**: Preguntas frecuentes
6. **Video Tutoriales**: Paso a paso visual

---

## 🎯 KPIs de Éxito

- **Tiempo de generación**: < 5 minutos por app
- **Tasa de éxito**: > 95% builds sin error
- **Tiempo de build**: < 10 minutos
- **Tamaño APK**: < 20 MB
- **Satisfacción usuarios**: > 4.5/5 estrellas

---

## 🔮 Futuro (Roadmap Extendido)

### Fase 2
- [ ] Compilación iOS (Mac en la nube)
- [ ] PWA generator (Progressive Web App)
- [ ] Desktop apps (Windows, Mac, Linux)

### Fase 3
- [ ] Marketplace de templates
- [ ] Constructor visual sin código
- [ ] IA para generar diseños automáticamente
- [ ] Multi-idioma automático

### Fase 4
- [ ] Backend as a Service incluido
- [ ] Notificaciones push avanzadas
- [ ] Chat en tiempo real
- [ ] Video llamadas integradas

---

**Este sistema convertirá Flavor Chat IA en una plataforma completa para crear apps móviles sin programar** 🚀
