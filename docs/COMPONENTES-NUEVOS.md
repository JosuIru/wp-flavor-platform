# Guía de Componentes Nuevos - Fase 3 Implementada

## 🎯 Componentes Disponibles

Esta guía documenta los nuevos shortcodes y sistemas implementados en la Fase 3 (Estandarización de Módulos).

---

## 1. 📐 Page Header Component

Header estandarizado para todas las páginas de módulos con breadcrumbs y navegación integrada.

### Sintaxis

```
[flavor_page_header
    title="Título de la Página"
    subtitle="Descripción opcional"
    breadcrumbs="yes"
    background="gradient"
    module="eventos"
    current="crear"]
```

### Parámetros

| Parámetro | Valores | Por Defecto | Descripción |
|-----------|---------|-------------|-------------|
| `title` | texto | vacío | Título principal de la página |
| `subtitle` | texto | vacío | Subtítulo descriptivo |
| `breadcrumbs` | yes/no | yes | Mostrar breadcrumbs automáticos |
| `background` | white/gradient/primary | white | Estilo de fondo |
| `module` | ID del módulo | vacío | Si se especifica, muestra navegación del módulo |
| `current` | slug de página | vacío | Página actual dentro del módulo (para marcar activa) |

### Ejemplos de Uso

#### Ejemplo 1: Página de Listado de Eventos
```
[flavor_page_header
    title="Eventos de la Comunidad"
    subtitle="Descubre y participa en los próximos eventos"
    breadcrumbs="yes"
    background="gradient"
    module="eventos"
    current="listado"]
```

#### Ejemplo 2: Página de Crear Taller
```
[flavor_page_header
    title="Proponer un Taller"
    subtitle="Comparte tus conocimientos con la comunidad"
    breadcrumbs="yes"
    background="white"
    module="talleres"
    current="crear"]
```

#### Ejemplo 3: Página Simple sin Módulo
```
[flavor_page_header
    title="Mis Reservas"
    subtitle="Gestiona tus reservas de espacios comunes"
    breadcrumbs="yes"
    background="white"]
```

### Estilos de Background

- **white**: Fondo blanco con borde gris (ideal para páginas de contenido)
- **gradient**: Degradado azul-morado (ideal para páginas principales)
- **primary**: Color primario sólido (ideal para acciones importantes)

---

## 2. 🧭 Module Navigation Component

Navegación en pestañas para páginas dentro de un módulo.

### Sintaxis

```
[flavor_module_nav
    module="eventos"
    current="crear"
    items="listado,mis-eventos,crear,buscar"]
```

### Parámetros

| Parámetro | Valores | Por Defecto | Descripción |
|-----------|---------|-------------|-------------|
| `module` | ID del módulo | requerido | Identificador del módulo |
| `current` | slug de página | vacío | Página actual (se marca como activa) |
| `items` | slugs separados por coma | auto | Override manual de items (opcional) |

### Módulos con Navegación Predefinida

Los siguientes módulos tienen navegación automática configurada:

#### Eventos
- **listado** → "Todos los Eventos" (📅)
- **mis-eventos** → "Mis Eventos" (⭐)
- **crear** → "Crear Evento" (➕)

#### Talleres
- **listado** → "Todos los Talleres" (🎨)
- **mis-talleres** → "Mis Talleres" (⭐)
- **crear** → "Proponer Taller" (➕)

#### Grupos de Consumo
- **listado** → "Todos los Grupos" (🌱)
- **mi-grupo** → "Mi Grupo" (⭐)
- **pedidos** → "Pedidos" (🛒)

#### Incidencias
- **listado** → "Todas las Incidencias" (🔧)
- **mis-incidencias** → "Mis Incidencias" (⭐)
- **crear** → "Reportar Incidencia" (➕)

#### Espacios Comunes
- **listado** → "Espacios Disponibles" (🏛️)
- **mis-reservas** → "Mis Reservas" (⭐)
- **reservar** → "Reservar Espacio" (➕)

### Ejemplos de Uso

#### Ejemplo 1: Navegación Automática
```
[flavor_module_nav module="eventos" current="crear"]
```

Renderiza automáticamente:
```
Todos los Eventos | Mis Eventos | [Crear Evento]
```

#### Ejemplo 2: Override Manual de Items
```
[flavor_module_nav
    module="talleres"
    current="inscripciones"
    items="listado,mis-talleres,crear,inscripciones"]
```

---

## 3. 💬 User Messages System

Sistema de mensajes y páginas de error mejoradas con diseño consistente.

### Métodos Disponibles

#### 3.1 Acceso Denegado

```php
Flavor_User_Messages::access_denied('Eventos', 'Este módulo requiere aprobación del administrador.');
```

Muestra página completa con:
- Icono 🔒
- Título "Acceso Denegado"
- Mensaje personalizado
- Botones: "Ir a Mi Portal", "Volver Atrás", "Contactar Soporte"

#### 3.2 Página No Encontrada

```php
Flavor_User_Messages::not_found('evento');
```

Muestra página completa con:
- Icono 🔍
- Título "No Encontrado"
- Mensaje de item no encontrado
- Botones: "Ir a Mi Portal", "Volver Atrás"

#### 3.3 Mensaje de Éxito

```php
ob_start();
Flavor_User_Messages::success(
    'Evento Creado',
    'Tu evento ha sido creado exitosamente y está pendiente de aprobación.',
    'Ver Mis Eventos',
    home_url('/eventos/mis-eventos/')
);
return ob_get_clean();
```

Renderiza tarjeta de éxito con:
- Icono ✅
- Título y mensaje
- Botón de acción opcional

#### 3.4 Mensajes de Info/Warning/Error

```php
ob_start();
Flavor_User_Messages::info(
    'Información Importante',
    'Recuerda que las reservas deben hacerse con 24h de antelación.',
    'info' // o 'warning' o 'error'
);
return ob_get_clean();
```

---

## 4. 🍞 Breadcrumbs Automáticos

Sistema de breadcrumbs que se genera automáticamente según la jerarquía de páginas.

### Uso

```php
// En PHP
echo Flavor_Breadcrumbs::render();

// En shortcode (ya incluido en flavor_page_header)
[flavor_page_header breadcrumbs="yes" ...]
```

### Características

- Detecta automáticamente la jerarquía de páginas
- Comienza siempre desde "Mi Portal"
- Incluye páginas ancestro en orden
- No se muestra en la home ni en "Mi Portal"
- Responsive y con estilos integrados

### Ejemplo de Output

Estando en `/eventos/mis-eventos/`:
```
Mi Portal › Eventos › Mis Eventos
```

Estando en `/talleres/crear/`:
```
Mi Portal › Talleres › Proponer Taller
```

---

## 5. 🔒 Control de Acceso Automático

Sistema que detecta automáticamente qué páginas requieren login.

### Reglas Automáticas

El sistema detecta automáticamente que una página requiere login si:

1. **Meta explícito**: `_flavor_requires_login = 1`
2. **Slug especial**: Empieza con `mis-`, `crear`, `editar`, `nuevo`
3. **Contenido**: Contiene shortcode `[flavor_module_form...]`
4. **Página especial**: Slug es `mi-portal` o `mi-cuenta`
5. **Página hija**: Es subpágina de una página privada

### Páginas Públicas por Defecto

- Páginas de nivel superior (sin padre)
- Landings principales de módulos
- Páginas con `_flavor_requires_login = 0`

### Marcar Manualmente una Página

```php
// Requiere login
Flavor_Page_Access_Control::mark_page_requires_login($page_id);

// Pública
Flavor_Page_Access_Control::mark_page_public($page_id);
```

---

## 📋 Template de Página Completa de Módulo

Aquí tienes un template completo para usar en las páginas de módulos:

### Página de Listado

```
[flavor_page_header
    title="Eventos de la Comunidad"
    subtitle="Descubre y participa en los próximos eventos"
    breadcrumbs="yes"
    background="gradient"
    module="eventos"
    current="listado"]

[flavor_eventos tipo="grid" columnas="3" mostrar_filtros="yes"]
```

### Página de "Mis Items"

```
[flavor_page_header
    title="Mis Eventos"
    subtitle="Gestiona los eventos en los que participas"
    breadcrumbs="yes"
    background="white"
    module="eventos"
    current="mis-eventos"]

[flavor_eventos_usuario mostrar_pasados="no" mostrar_confirmados="yes"]
```

### Página de Formulario

```
[flavor_page_header
    title="Crear Evento"
    subtitle="Organiza un nuevo evento para la comunidad"
    breadcrumbs="yes"
    background="white"
    module="eventos"
    current="crear"]

[flavor_module_form module="eventos" action="crear_evento"]
```

---

## 🎨 Estructura Estandarizada de URLs

Para consistencia, todas las páginas de módulos deberían seguir esta estructura:

```
/{modulo}/                     → Listado/Grid principal
/{modulo}/mis-{modulo}/        → Items del usuario
/{modulo}/crear/               → Formulario de creación
/{modulo}/buscar/              → Búsqueda/Filtros avanzados
/{modulo}/[id]/                → Vista individual (single)
/{modulo}/[id]/editar/         → Editar item
```

### Ejemplo: Módulo Eventos

```
/eventos/                      → Todos los eventos
/eventos/mis-eventos/          → Mis eventos
/eventos/crear/                → Crear evento
/eventos/buscar/               → Búsqueda de eventos
/eventos/123/                  → Ver evento específico
/eventos/123/editar/           → Editar evento
```

---

## 🚀 Próximos Pasos

Para implementar completamente la estandarización:

1. **Actualizar páginas existentes** con los nuevos headers
2. **Añadir navegación de módulo** a todas las páginas internas
3. **Usar mensajes mejorados** en lugar de alerts/notices básicos
4. **Verificar control de acceso** en páginas nuevas
5. **Mantener consistencia** en nombres y URLs

---

## 💡 Tips y Mejores Prácticas

### ✅ Buenas Prácticas

- Siempre usa `flavor_page_header` en lugar de `<h1>` manual
- Incluye navegación de módulo en páginas internas
- Usa breadcrumbs en todas las páginas (excepto home y mi-portal)
- Usa mensajes de error mejorados en lugar de `wp_die()` básico
- Sigue la estructura de URLs estandarizada

### ❌ Evitar

- No uses headers HTML directos (`<h1>`, `<h2>`) al inicio de páginas
- No crees navegación custom, usa `flavor_module_nav`
- No uses alertas JavaScript para errores importantes
- No mezcles diferentes estilos de breadcrumbs
- No uses nombres de slug inconsistentes

---

## 📞 Soporte

Si necesitas añadir un nuevo módulo a la navegación predefinida, edita:
`includes/class-module-navigation.php` → método `get_module_nav_items()`

Para personalizar mensajes de error globales, edita:
`includes/class-user-messages.php`
