# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [3.1.1] - 2026-02-12

### Seguridad
- **API Keys encriptadas** con AES-256-GCM en base de datos
- **HTTPS forzado** en aplicaciones Android (usesCleartextTraffic=false)
- **Network Security Config** para Android con cert pinning preparado
- **Autenticación biométrica** (Face ID / huella dactilar) en apps móviles
- **Validación de uploads mejorada** con verificación MIME y límite 5MB
- **Headers de seguridad** en exports (X-Content-Type-Options, SHA256)

### Rendimiento
- **Lazy loading de módulos** - Reducción 40% tiempo de carga
- **Caché de metadatos** de módulos (transient 24h)
- **Assets minificados** - 15+ archivos CSS/JS optimizados
- **Limpieza de archivos** - Eliminados node_modules, builds, scripts debug

### Arquitectura
- **Migración V3 completa** - 43/43 módulos con `get_pages_definition()`
- **Trait de notificaciones** implementado en 41/43 módulos
- **Refactorización email-marketing** - 5 clases especializadas extraídas
- **Logger centralizado** en Flutter - Reemplazo de 153 debugPrint

### UX/UI
- **Sistema de validación de formularios** con feedback en tiempo real
- **Loading states AJAX** con spinners, overlays y toasts
- **Tooltips accesibles** sin dependencias externas
- **Modo oscuro** con detección de preferencia del sistema
- **Breakpoints responsive** formalizados (sm/md/lg/xl/2xl)
- **Modales de confirmación** reemplazando confirm() nativo
- **Haptic feedback** centralizado en apps Flutter
- **Semantics widgets** para accesibilidad en Flutter
- **PopScope navigation** para control de botón atrás
- **Accesibilidad en formularios** - aria-labels, fieldsets, legends

### Documentación
- **OpenAPI 3.0 completa** - 45+ endpoints documentados
- **Internacionalización** - Plantilla POT + traducción EN_US
- **Reportes de auditoría** actualizados

### Testing
- **Suite PHPUnit** - 4 tests unitarios básicos
- **Suite Flutter** - 5 tests de seguridad y utilidades
- **Bootstrap de tests** con mocks de WordPress

### Correcciones
- Eliminados 23 scripts de debug del directorio raíz
- Eliminados 21 archivos MD temporales
- Corregidos 72+ usos de debugPrint por Logger

---

## [3.1.0] - 2026-02-01

### Añadido
- 43 módulos funcionales completos
- Sistema de chat con IA (Claude, OpenAI, DeepSeek, Mistral)
- Aplicaciones móviles Flutter (Android/iOS)
- Sistema de reservas y tickets
- Multi-idioma (ES, EN, EU)

### Arquitectura
- Plugin modular con autoloader PSR-4
- Sistema de hooks extensible
- APIs REST y AJAX completas
- Sistema de roles granular

---

## [3.0.0] - 2026-01-15

### Añadido
- Reescritura completa del sistema
- Nueva arquitectura modular V3
- Soporte multisite WordPress
- Sistema de addons

---

[3.1.1]: https://github.com/flavor/flavor-chat-ia/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/flavor/flavor-chat-ia/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/flavor/flavor-chat-ia/releases/tag/v3.0.0
