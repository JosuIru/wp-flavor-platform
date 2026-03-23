# Flavor Platform 3.4.0

Plugin WordPress modular para comunidades, administracion, dashboards, contenidos, economia colaborativa, apps y modulos sectoriales.

## Novedades v3.4.0

- **Sistema de Versionado de Modulos** - Cada modulo puede tener su propia version independiente via `module.json`
- **Verificacion de Dependencias** - Sistema automatico para verificar dependencias entre modulos
- **Verificacion de Compatibilidad** - Comprobacion de versiones de WordPress, PHP y Flavor Platform
- **Changelog por Modulo** - Historial de cambios granular a nivel de modulo

Ver `docs/SISTEMA-VERSIONADO-MODULOS.md` para detalles tecnicos.

## Novedades v3.3.0

- **Bootstrap Modular** - Código principal refactorizado en 5 clases especializadas
- **Sistema de Migrations** - 9 migrations versionadas con WP-CLI
- **CSS Consolidado** - 102 archivos organizados en 5 subdirectorios con build system
- **Federación Completa** - Sincronización de 8 tipos de contenido entre nodos
- **Webhooks** - Notificaciones en tiempo real con firma HMAC-SHA256
- **Shortcodes de Red** - 8 shortcodes para mostrar contenido federado

Ver `docs/ARQUITECTURA-V3.3.md` para detalles técnicos.

## Punto de entrada recomendado

La documentacion canonica para entender el plugin y navegar por sus subsistemas esta en:

- `docs/INDICE-DOCUMENTACION.md`
- `docs/ARQUITECTURA-V3.3.md` (nuevo)

## Lectura minima recomendada

- `docs/FILOSOFIA-PLUGIN.md`
- `docs/PLUGIN-COMPLETO.md`
- `docs/GUIA-ADMINISTRACION.md`
- `docs/GUIA_MODULOS.md`
- `docs/ESTADO-REAL-PLUGIN.md`

## Referencia de estado vigente

La auditoria de referencia para el estado real del sistema pasa a ser:

- `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`

## Alineacion documental realizada

Ya quedaron alineados con esa auditoria:

- `docs/ESTADO-REAL-PLUGIN.md`
- `docs/PLUGIN-COMPLETO.md`
- `docs/GUIA_MODULOS.md`
- `docs/CATALOGO-MODULOS.md`
- `docs/INDICE-DOCUMENTACION.md`

Esos documentos deben leerse como capa canonica actual.

## Nota

Si un documento historico contradice:

- el codigo actual
- la version declarada `3.3.0`
- la auditoria de `2026-03-04`
- la documentacion canonica de `docs/`

debe tratarse como material de contexto, no como fuente unica de verdad.

## Sistema de Versionado de Modulos

A partir de v3.4.0, cada modulo puede tener su version independiente mediante un archivo `module.json` en su directorio.

### Estructura del module.json

```json
{
  "id": "eventos",
  "name": "Eventos y Calendario",
  "version": "2.0.0",
  "description": "Descripcion del modulo",
  "dependencies": {
    "comunidades": "^1.0.0"
  },
  "wp_version_min": "6.0",
  "php_version_min": "7.4",
  "flavor_version_min": "3.3.0"
}
```

### Uso de la API de Versionado

```php
// Obtener instancia
$versioning = Flavor_Module_Versioning::get_instance();

// Obtener version de un modulo
$version = $versioning->get_module_version('eventos');

// Verificar dependencias
$dependencies = $versioning->verify_dependencies('eventos');
if ($dependencies['success']) {
    // Todas las dependencias satisfechas
}

// Verificar compatibilidad
$compatibility = $versioning->verify_compatibility('eventos');
if ($compatibility['compatible']) {
    // Modulo compatible con el sistema
}

// Obtener changelog
$changelog = $versioning->get_module_changelog('eventos');

// Obtener changelog desde version especifica
$changelog = $versioning->get_module_changelog('eventos', '1.0.0', '2.0.0');

// Generar reporte de estado
$report = $versioning->generate_status_report();
```

### Restricciones de Version (Semver)

El sistema soporta las siguientes restricciones de version:

| Formato | Significado | Ejemplo |
|---------|-------------|---------|
| `1.0.0` | Version exacta | Solo 1.0.0 |
| `>=1.0.0` | Mayor o igual | 1.0.0, 1.5.0, 2.0.0 |
| `>1.0.0` | Mayor estricto | 1.0.1, 1.5.0, 2.0.0 |
| `<2.0.0` | Menor estricto | 1.0.0, 1.9.9 |
| `<=2.0.0` | Menor o igual | 1.0.0, 2.0.0 |
| `^1.0.0` | Compatible (caret) | >=1.0.0 <2.0.0 |
| `~1.2.0` | Aproximada (tilde) | >=1.2.0 <1.3.0 |
| `1.0.0 - 2.0.0` | Rango | 1.0.0 a 2.0.0 inclusive |
| `1.*` | Wildcard | 1.0.0, 1.5.3, 1.99.0 |

### Schema Completo

El schema JSON completo esta disponible en:
`includes/modules/module-schema.json`

### Ejemplo de Implementacion

Ver el modulo de eventos como referencia:
`includes/modules/eventos/module.json`
