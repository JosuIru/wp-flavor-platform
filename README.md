# Flavor Platform 3.0

**La plataforma modular definitiva para crear experiencias de chat con IA en WordPress**

[![Version](https://img.shields.io/badge/version-3.0.0-blue.svg)](https://github.com/gailu-labs/flavor-platform)
[![WordPress](https://img.shields.io/badge/wordpress-5.8%2B-green.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPLv2-orange.svg)](LICENSE)

---

## 🚀 Versión 3.0 - Arquitectura Modular Completa

### ✨ Novedades Principales

- **Sistema de Addons**: Arquitectura completamente modular
- **Performance Cache**: Reducción 60-80% en queries
- **Dashboard Rediseñado**: Vista general intuitiva
- **Setup Wizard**: Configuración guiada en 6 pasos
- **Tours Interactivos**: Onboarding con Shepherd.js
- **Marketplace**: Explora e instala addons con un click
- **Actualizaciones Automáticas**: Sistema completo de updates
- **Licenciamiento**: Soporte para addons premium
- **Sandbox de Seguridad**: Validación y límites de recursos

### 📊 Mejoras de Performance

**Antes (v2.x):**
- 30 MB core
- 80 archivos cargados
- 200+ queries por request

**Ahora (v3.0):**
- 21 MB core (-30%)
- ~35 archivos base
- 40-80 queries (-70%)
- Hit rate cache: 70-90%

---

## 📦 Instalación Rápida

```bash
# Requisitos
WordPress 5.8+
PHP 7.4+
MySQL 5.7+
```

1. Descargar e instalar plugin
2. Activar Flavor Platform
3. Setup Wizard se inicia automáticamente
4. Configurar en 5 minutos

---

## 🎯 Características Principales

### Chat IA Potente
- Claude (Anthropic)
- OpenAI (GPT-4, GPT-3.5)
- DeepSeek
- Mistral

### Sistema de Addons
```php
// Crear addon en minutos
add_action('flavor_register_addons', function() {
    Flavor_Addon_Manager::register_addon('mi-addon', [
        'name' => 'Mi Addon',
        'version' => '1.0.0',
        'init_callback' => 'mi_addon_init',
    ]);
});
```

### Performance Cache
```php
$cache = flavor_cache();
$datos = $cache->remember('clave', function() {
    return generar_datos();
}, 'grupo', HOUR_IN_SECONDS);
```

---

## 📚 Documentación

### 📖 Documentos Principales

- **[Estado Actual del Proyecto](ESTADO_ACTUAL_PROYECTO.md)** - Estado consolidado y roadmap
- **[Verificación de Implementaciones](VERIFICACION_IMPLEMENTACIONES.md)** - Auditoría exhaustiva de código (Feb 11, 2026)
- **[Arquitectura de Módulos](ARQUITECTURA_MODULOS.md)** - Estructura modular del sistema

### 🚀 Guías de Inicio

- **[Quick Start](docs/QUICK-START.md)** - Inicio rápido en 5 minutos
- **[Construir Aplicación](docs/CONSTRUIR_APLICACION.md)** - Cómo crear una app completa
- **[Configuración Apps](docs/CONFIGURACION-APPS.md)** - Setup de apps móviles

### 🏗️ Arquitectura y Sistema

- **[Ecosystem](docs/ECOSYSTEM.md)** - Visión general del ecosistema
- **[Sistema de Addons](docs/ADDON-SYSTEM.md)** - Arquitectura de addons
- **[Crear Addons](docs/ADDON-EXAMPLE.md)** - Ejemplo paso a paso
- **[Sistema Completo v3](docs/SISTEMA-COMPLETO-V3.md)** - Arquitectura completa

### 📱 Apps Móviles

- **[README Apps](mobile-apps/README.md)** - Instalación y configuración Flutter
- **[Documentación Apps](mobile-apps/DOCUMENTACION_APPS.md)** - Guía completa apps
- **[Auditoría Apps](mobile-apps/AUDITORIA_COMPLETA_APPS.md)** - Estado y checklist

### 🔧 Desarrollo

- **[Guía de Módulos](docs/GUIA_MODULOS.md)** - Desarrollo de módulos
- **[Referencia Rápida](docs/REFERENCIA_RAPIDA_MODULOS.md)** - API de módulos
- **[CPT Apps Guía](docs/CPT-APPS-GUIA.md)** - Custom Post Types
- **[Design Guide](docs/DESIGN-GUIDE.md)** - Guía de diseño
- **[Permissions Usage](docs/PERMISSIONS-USAGE.md)** - Sistema de permisos

### 📊 Calidad y Mejoras

- **[Gaps and Improvements](docs/GAPS-AND-IMPROVEMENTS.md)** - Mejoras pendientes
- **[Plan de Pruebas](docs/PLAN-PRUEBAS.md)** - Estrategia de testing
- **[Checklist Release](docs/CHECKLIST-RELEASE.md)** - Pre-release checklist
- **[Mejoras v3.0](docs/MEJORAS-V3.0.md)** - Changelog v3.0
- **[UX Módulos Apps](docs/UX-MODULOS-APPS.md)** - Mejoras UX

### 🔀 Migración

- **[Migración Network](docs/MIGRATION-NETWORK.md)** - Migrar red de nodos
- **[Migración Web Builder](docs/MIGRATION-WEB-BUILDER.md)** - Migrar page builder

### 🌐 APIs

- **[Endpoints](docs/api/ENDPOINTS.md)** - Documentación endpoints REST
- **[Contratos JSON](docs/CONTRATOS-JSON.md)** - Schemas de datos

---

## 🧩 Addons Oficiales

- 🎨 **Web Builder Pro** - Constructor de landing pages
- 🌐 **Network Communities** - Sistema de comunidades
- 💰 **Advertising Pro** - Gestión de publicidad
- ⚙️ **Admin Assistant** - Atajos de teclado + IA

---

## 🆘 Soporte

- **Documentación:** [docs/](docs/)
- **Issues:** GitHub Issues
- **Email:** support@gailu.net

---

## 📄 Licencia

GPL v2 or later

---

**Desarrollado por [Gailu Labs](https://gailu.net)** 🚀
