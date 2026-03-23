# Sistema de Versionado de Modulos

> Documentacion tecnica del sistema de versionado independiente para modulos de Flavor Platform.

## Introduccion

A partir de la version 3.4.0, Flavor Platform implementa un sistema de versionado independiente para cada modulo. Esto permite:

- Versionar modulos de forma independiente al plugin principal
- Definir dependencias entre modulos con restricciones de version
- Verificar compatibilidad con WordPress, PHP y Flavor Platform
- Mantener un changelog granular por modulo
- Validar la estructura de modulos contra un schema JSON

## Archivos del Sistema

| Archivo | Ubicacion | Proposito |
|---------|-----------|-----------|
| `module-schema.json` | `includes/modules/` | Schema JSON que define la estructura valida de `module.json` |
| `class-module-versioning.php` | `includes/` | Clase PHP que gestiona el versionado |
| `module.json` | `includes/modules/{modulo}/` | Archivo de configuracion por modulo |

## Estructura del module.json

### Campos Requeridos

```json
{
  "id": "eventos",
  "name": "Eventos y Calendario",
  "version": "2.0.0"
}
```

### Campos Recomendados

```json
{
  "id": "eventos",
  "name": "Eventos y Calendario",
  "version": "2.0.0",
  "description": "Gestion de eventos y calendario comunitario",
  "author": {
    "name": "Flavor Platform Team",
    "email": "dev@flavor-platform.com"
  },
  "license": "GPL-2.0-or-later",
  "dependencies": {
    "comunidades": "^1.0.0"
  },
  "wp_version_min": "6.0",
  "php_version_min": "7.4",
  "flavor_version_min": "3.3.0"
}
```

### Campos Opcionales Completos

```json
{
  "$schema": "../module-schema.json",
  "id": "eventos",
  "name": "Eventos y Calendario",
  "version": "2.0.0",
  "description": "Descripcion corta",
  "long_description": "Descripcion extendida del modulo",
  "author": {
    "name": "Flavor Platform Team",
    "email": "dev@flavor-platform.com",
    "url": "https://flavor-platform.com"
  },
  "contributors": [
    {
      "name": "Desarrollador",
      "email": "dev@example.com",
      "role": "developer"
    }
  ],
  "license": "GPL-2.0-or-later",
  "dependencies": {
    "comunidades": "^1.0.0"
  },
  "optional_dependencies": {
    "chat-grupos": "^1.0.0"
  },
  "conflicts": {
    "modulo-antiguo": "*"
  },
  "wp_version_min": "6.0",
  "wp_version_tested": "6.7",
  "php_version_min": "7.4",
  "flavor_version_min": "3.3.0",
  "category": "social",
  "tags": ["eventos", "calendario", "comunidad"],
  "icon": "dashicons-calendar-alt",
  "main_class": "Flavor_Chat_Eventos_Module",
  "autoload": {
    "files": ["class-eventos-module.php"]
  },
  "database": {
    "tables": ["flavor_eventos"],
    "migrations": "migrations/"
  },
  "assets": {
    "css": ["assets/css/eventos.css"],
    "js": ["assets/js/eventos.js"]
  },
  "shortcodes": [
    {
      "tag": "flavor_eventos_lista",
      "description": "Lista de eventos"
    }
  ],
  "rest_api": {
    "namespace": "flavor/v1",
    "endpoints": [
      {
        "route": "/eventos",
        "methods": ["GET", "POST"]
      }
    ]
  },
  "hooks": {
    "actions": ["flavor_evento_creado"],
    "filters": ["flavor_eventos_tipos"]
  },
  "capabilities": ["flavor_manage_eventos"],
  "changelog": [
    {
      "version": "2.0.0",
      "date": "2026-03-22",
      "changes": [
        {
          "type": "added",
          "description": "Nueva funcionalidad"
        }
      ]
    }
  ],
  "stability": "stable",
  "deprecated": false,
  "support": {
    "docs": "https://docs.example.com",
    "issues": "https://github.com/example/issues"
  }
}
```

## API de Versionado

### Obtener Instancia

```php
$versioning = Flavor_Module_Versioning::get_instance();
```

### Obtener Informacion de Modulo

```php
$module_info = $versioning->get_module_info('eventos');

if (is_wp_error($module_info)) {
    echo $module_info->get_error_message();
} else {
    echo $module_info['name']; // "Eventos y Calendario"
    echo $module_info['version']; // "2.0.0"
}
```

### Obtener Version de Modulo

```php
$version = $versioning->get_module_version('eventos');
// Retorna "2.0.0" o WP_Error si no existe
```

### Verificar Dependencias

```php
$result = $versioning->verify_dependencies('eventos');

// Estructura del resultado:
[
    'success' => true,
    'satisfied' => [
        ['id' => 'comunidades', 'required' => '^1.0.0', 'installed' => '1.2.0']
    ],
    'missing' => [],
    'incompatible' => []
]
```

### Verificar Compatibilidad

```php
$result = $versioning->verify_compatibility('eventos');

// Estructura del resultado:
[
    'compatible' => true,
    'warnings' => [],
    'errors' => [],
    'details' => [
        'wp_version' => ['required' => '6.0', 'current' => '6.7'],
        'php_version' => ['required' => '7.4', 'current' => '8.2'],
        'flavor_version' => ['required' => '3.3.0', 'current' => '3.4.0']
    ]
]
```

### Obtener Changelog

```php
// Changelog completo
$changelog = $versioning->get_module_changelog('eventos');

// Changelog desde version X hasta version Y
$changelog = $versioning->get_module_changelog('eventos', '1.0.0', '2.0.0');

// Estructura de cada entrada:
[
    'version' => '2.0.0',
    'date' => '2026-03-22',
    'changes' => [
        ['type' => 'added', 'description' => 'Nueva funcionalidad'],
        ['type' => 'fixed', 'description' => 'Correccion de bug']
    ]
]
```

### Listar Todos los Modulos Versionados

```php
$modules = $versioning->get_all_versioned_modules();

// Retorna array asociativo:
[
    'eventos' => [
        'id' => 'eventos',
        'name' => 'Eventos y Calendario',
        'version' => '2.0.0',
        'description' => '...',
        'stability' => 'stable',
        'category' => 'social'
    ],
    // ... mas modulos
]
```

### Verificar Actualizaciones

```php
$has_update = $versioning->has_update_available('eventos', '2.1.0');
// Retorna true si 2.1.0 > version instalada
```

### Generar Reporte de Estado

```php
$report = $versioning->generate_status_report();

// Estructura:
[
    'generated_at' => '2026-03-22 10:30:00',
    'total_modules' => 5,
    'modules' => [
        'eventos' => [
            'info' => [...],
            'compatibility' => [...],
            'dependencies' => [...]
        ]
    ],
    'summary' => [
        'compatible' => 4,
        'incompatible' => 1,
        'with_warnings' => 2
    ]
]
```

## Restricciones de Version (Semver)

El sistema soporta multiples formatos de restriccion siguiendo el estandar Semver:

### Version Exacta

```json
"dependencies": {
    "comunidades": "1.0.0"
}
```

Solo acepta exactamente la version 1.0.0.

### Mayor o Igual

```json
"dependencies": {
    "comunidades": ">=1.0.0"
}
```

Acepta 1.0.0, 1.5.0, 2.0.0, etc.

### Caret (Compatible)

```json
"dependencies": {
    "comunidades": "^1.2.0"
}
```

Acepta >=1.2.0 y <2.0.0. Permite actualizaciones que no rompen compatibilidad.

### Tilde (Aproximada)

```json
"dependencies": {
    "comunidades": "~1.2.0"
}
```

Acepta >=1.2.0 y <1.3.0. Solo permite patches.

### Rango

```json
"dependencies": {
    "comunidades": "1.0.0 - 2.0.0"
}
```

Acepta desde 1.0.0 hasta 2.0.0 inclusive.

### Wildcard

```json
"dependencies": {
    "comunidades": "1.*"
}
```

Acepta cualquier version 1.x.x.

## Categorias de Modulos

Los modulos pueden clasificarse en las siguientes categorias:

| Categoria | Descripcion |
|-----------|-------------|
| `core` | Funcionalidad nuclear del sistema |
| `social` | Interaccion social y comunidad |
| `economy` | Economia colaborativa y transacciones |
| `environment` | Medioambiente y sostenibilidad |
| `governance` | Gobernanza y participacion |
| `communication` | Comunicacion y mensajeria |
| `services` | Servicios comunitarios |
| `business` | Funcionalidad empresarial |
| `education` | Formacion y aprendizaje |
| `culture` | Cultura y entretenimiento |
| `utilities` | Utilidades generales |

## Estados de Estabilidad

| Estado | Descripcion |
|--------|-------------|
| `experimental` | En fase de pruebas, puede cambiar drasticamente |
| `alpha` | Primera version funcional, inestable |
| `beta` | Funcionalidad completa, puede tener bugs |
| `rc` | Release candidate, casi listo para produccion |
| `stable` | Version estable para produccion |
| `deprecated` | Obsoleto, se recomienda migrar a alternativa |

## Tipos de Cambios en Changelog

| Tipo | Descripcion |
|------|-------------|
| `added` | Nueva funcionalidad |
| `changed` | Cambio en funcionalidad existente |
| `deprecated` | Funcionalidad marcada como obsoleta |
| `removed` | Funcionalidad eliminada |
| `fixed` | Correccion de bugs |
| `security` | Parche de seguridad |

## Validacion de Schema

El sistema valida automaticamente los archivos `module.json` contra el schema definido. Los errores comunes incluyen:

- **Campo requerido faltante**: `id`, `name` o `version` no estan presentes
- **Version invalida**: No sigue el formato semver (ej: 1.0.0)
- **ID invalido**: Contiene caracteres no permitidos (solo minusculas, numeros y guiones)
- **Version de WordPress/PHP invalida**: Formato incorrecto

## Ejemplo de Implementacion

Ver el modulo de eventos como referencia completa:

```
includes/modules/eventos/module.json
```

## Migracion de Modulos Existentes

Para agregar versionado a un modulo existente:

1. Crear archivo `module.json` en el directorio del modulo
2. Definir al menos `id`, `name` y `version`
3. Agregar dependencias si las tiene
4. Agregar changelog inicial

```json
{
  "id": "mi-modulo",
  "name": "Mi Modulo",
  "version": "1.0.0",
  "description": "Descripcion de mi modulo",
  "dependencies": {},
  "wp_version_min": "6.0",
  "php_version_min": "7.4",
  "changelog": [
    {
      "version": "1.0.0",
      "date": "2026-03-22",
      "changes": [
        {
          "type": "added",
          "description": "Version inicial con sistema de versionado"
        }
      ]
    }
  ]
}
```

## Integracion con el Sistema de Modulos

La clase `Flavor_Module_Versioning` se integra con el sistema existente de modulos pero es independiente. Los modulos pueden funcionar sin `module.json`, pero pierden las capacidades de:

- Verificacion automatica de dependencias
- Control de compatibilidad
- Changelog estructurado
- Validacion de schema

## Buenas Practicas

1. **Usar semver estrictamente**: major.minor.patch
2. **Documentar cambios**: Mantener changelog actualizado
3. **Definir dependencias minimas**: No sobrespecificar versiones
4. **Usar caret para dependencias**: `^1.0.0` permite actualizaciones compatibles
5. **Testear compatibilidad**: Verificar con versiones declaradas de WP/PHP
6. **Deprecar gradualmente**: Usar `deprecated: true` antes de eliminar
