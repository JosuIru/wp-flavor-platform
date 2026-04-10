# Ejemplo: Crear Módulo Completo con Nuevos Componentes

Este documento muestra cómo crear un módulo completamente estandarizado usando todos los nuevos componentes.

## 📚 Ejemplo: Módulo "Biblioteca Comunitaria"

Vamos a crear un módulo completo de biblioteca con 4 páginas estandarizadas.

---

## 1️⃣ Página Principal: Catálogo de Libros

### URL: `/biblioteca/`

### Contenido de la Página:

```
[flavor_page_header
    title="Biblioteca Comunitaria"
    subtitle="Descubre, comparte y solicita libros de la comunidad"
    breadcrumbs="yes"
    background="gradient"
    module="biblioteca"
    current="catalogo"]

[flavor_biblioteca tipo="grid" columnas="4" mostrar_filtros="yes"]
```

### Resultado Esperado:

```
┌─────────────────────────────────────────────────────────┐
│ Mi Portal › Biblioteca                                  │
├─────────────────────────────────────────────────────────┤
│                                                          │
│        Biblioteca Comunitaria                           │
│   Descubre, comparte y solicita libros...              │
│                                                          │
├─────────────────────────────────────────────────────────┤
│ [Catálogo] [Mis Libros] [Compartir Libro] [Solicitar] │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  [Grid de 4 columnas con libros...]                    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Meta de la Página:

- `_flavor_auto_page` = `1` (página auto-creada)
- `_flavor_auto_page_modules` = `biblioteca`
- `_flavor_requires_login` = `0` (pública)
- `_wp_page_template` = `template-full-width.php` (auto-asignado)

---

## 2️⃣ Página Personal: Mis Libros

### URL: `/biblioteca/mis-libros/`

### Contenido de la Página:

```
[flavor_page_header
    title="Mis Libros"
    subtitle="Gestiona los libros que has compartido o prestado"
    breadcrumbs="yes"
    background="white"
    module="biblioteca"
    current="mis-libros"]

[flavor_biblioteca_usuario
    mostrar_compartidos="yes"
    mostrar_prestados="yes"
    mostrar_solicitados="yes"]
```

### Resultado Esperado:

```
┌─────────────────────────────────────────────────────────┐
│ Mi Portal › Biblioteca › Mis Libros                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│              Mis Libros                                 │
│   Gestiona los libros que has compartido...            │
│                                                          │
├─────────────────────────────────────────────────────────┤
│ [Catálogo] [Mis Libros] [Compartir Libro] [Solicitar] │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Pestañas: Compartidos | Prestados | Solicitados        │
│                                                          │
│  [Lista de libros del usuario...]                       │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Meta de la Página:

- `_flavor_auto_page` = `1`
- `_flavor_auto_page_modules` = `biblioteca`
- `_flavor_requires_login` = `1` (requiere login automáticamente por prefijo "mis-")
- Padre: `/biblioteca/`

---

## 3️⃣ Página de Acción: Compartir Libro

### URL: `/biblioteca/compartir/`

### Contenido de la Página:

```
[flavor_page_header
    title="Compartir un Libro"
    subtitle="Añade un libro a la biblioteca comunitaria"
    breadcrumbs="yes"
    background="white"
    module="biblioteca"
    current="compartir"]

[flavor_module_form
    module="biblioteca"
    action="compartir_libro"
    success_redirect="/biblioteca/mis-libros/"
    success_message="¡Libro compartido exitosamente!"]
```

### Resultado Esperado:

```
┌─────────────────────────────────────────────────────────┐
│ Mi Portal › Biblioteca › Compartir un Libro            │
├─────────────────────────────────────────────────────────┤
│                                                          │
│          Compartir un Libro                             │
│   Añade un libro a la biblioteca comunitaria           │
│                                                          │
├─────────────────────────────────────────────────────────┤
│ [Catálogo] [Mis Libros] [Compartir Libro] [Solicitar] │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Formulario:                                            │
│  ┌──────────────────────────────────────┐              │
│  │ Título del libro: [________]          │              │
│  │ Autor: [________]                     │              │
│  │ ISBN: [________]                      │              │
│  │ Estado: [Dropdown]                    │              │
│  │ Disponible para: [☑ Préstamo]        │              │
│  │                                        │              │
│  │ [Cancelar] [Compartir Libro]          │              │
│  └──────────────────────────────────────┘              │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Meta de la Página:

- `_flavor_requires_login` = `1` (por shortcode `[flavor_module_form...]`)
- Padre: `/biblioteca/`

---

## 4️⃣ Página de Acción: Solicitar Libro

### URL: `/biblioteca/solicitar/`

### Contenido de la Página:

```
[flavor_page_header
    title="Solicitar un Libro"
    subtitle="¿No encuentras el libro que buscas? Solicítalo a la comunidad"
    breadcrumbs="yes"
    background="white"
    module="biblioteca"
    current="solicitar"]

[flavor_module_form
    module="biblioteca"
    action="solicitar_libro"
    success_redirect="/biblioteca/mis-libros/?tab=solicitados"
    success_message="Solicitud enviada. Te notificaremos cuando esté disponible."]
```

---

## 🔧 Configuración del Módulo

### Añadir Navegación Predefinida

Editar: `includes/class-module-navigation.php`

```php
private function get_module_nav_items($module_id, $override = '') {
    // ... código existente ...

    $default_items = [
        // ... módulos existentes ...

        // AÑADIR:
        'biblioteca' => [
            [
                'slug' => 'catalogo',
                'label' => __('Catálogo', 'flavor-platform'),
                'url' => home_url('/biblioteca/'),
                'icon' => '📚'
            ],
            [
                'slug' => 'mis-libros',
                'label' => __('Mis Libros', 'flavor-platform'),
                'url' => home_url('/biblioteca/mis-libros/'),
                'icon' => '⭐'
            ],
            [
                'slug' => 'compartir',
                'label' => __('Compartir Libro', 'flavor-platform'),
                'url' => home_url('/biblioteca/compartir/'),
                'icon' => '➕'
            ],
            [
                'slug' => 'solicitar',
                'label' => __('Solicitar Libro', 'flavor-platform'),
                'url' => home_url('/biblioteca/solicitar/'),
                'icon' => '🔔'
            ],
        ],
    ];

    return $default_items[$module_id] ?? [];
}
```

### Añadir Acciones Rápidas al Dashboard

Editar: `includes/class-portal-shortcodes.php`

```php
private function get_quick_actions_smart() {
    // ... código existente ...

    // AÑADIR:
    if ($this->is_module_active('biblioteca')) {
        $actions[] = [
            'icon' => '📚',
            'title' => __('Compartir Libro', 'flavor-platform'),
            'description' => __('Añade un libro a la biblioteca', 'flavor-platform'),
            'url' => home_url('/biblioteca/compartir/'),
        ];

        $actions[] = [
            'icon' => '🔔',
            'title' => __('Solicitar Libro', 'flavor-platform'),
            'description' => __('Pide un libro que necesites', 'flavor-platform'),
            'url' => home_url('/biblioteca/solicitar/'),
        ];
    }

    return $actions;
}
```

---

## 📊 Añadir Stats al Dashboard

El módulo debería registrar su stat card automáticamente.

En el archivo del módulo principal `includes/modules/biblioteca/class-biblioteca-module.php`:

```php
public function register_stats() {
    add_filter('flavor_dashboard_stats', [$this, 'add_stats'], 10, 1);
}

public function add_stats($stats) {
    if (!$this->user_can_access()) {
        return $stats;
    }

    $user_id = get_current_user_id();

    // Contar libros del usuario
    $libros_compartidos = $this->count_user_books($user_id, 'compartidos');
    $libros_prestados = $this->count_user_books($user_id, 'prestados');

    $stats[] = [
        'module' => 'biblioteca',
        'icon' => '📚',
        'label' => __('Biblioteca', 'flavor-platform'),
        'value' => $libros_compartidos,
        'secondary' => [
            [
                'value' => $libros_prestados,
                'label' => __('Prestados', 'flavor-platform'),
            ],
        ],
        'link' => home_url('/biblioteca/mis-libros/'),
        'link_text' => __('Ver mis libros', 'flavor-platform'),
        'badge' => __('Lectura', 'flavor-platform'),
    ];

    return $stats;
}
```

---

## 🎨 Resultado Final en Mi Portal

Cuando el usuario entre a `/mi-portal/`, verá:

```
┌────────────────────────────────────────────────────┐
│                                                     │
│     Buenos días, Juan                               │
│     Tu centro de control comunitario                │
│                                                     │
└────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│              Resumen de Actividad                   │
│                                                      │
│ ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐           │
│ │ 📚  │  │ 📅  │  │ 🎨  │  │ 🔧  │           │
│ │  12  │  │   5  │  │   3  │  │   2  │           │
│ │Libros│  │Event.│  │Taller│  │Incid.│           │
│ │      │  │      │  │      │  │      │           │
│ │Ver → │  │Ver → │  │Ver → │  │Ver → │           │
│ └──────┘  └──────┘  └──────┘  └──────┘           │
│                                                      │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│            ⚡ Acciones Rápidas                       │
│                                                      │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐  │
│ │📚       │ │🔔       │ │📅       │ │🎨       │  │
│ │Compartir│ │Solicitar│ │Crear    │ │Proponer │  │
│ │Libro    │ │Libro    │ │Evento   │ │Taller   │  │
│ │→        │ │→        │ │→        │ │→        │  │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘  │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## ✅ Checklist de Implementación

Para cualquier módulo nuevo, seguir esta lista:

### 1. Páginas

- [ ] Crear página principal (listado/catálogo)
- [ ] Crear página "Mis..." para usuario
- [ ] Crear página de acción principal (crear/compartir)
- [ ] Crear páginas secundarias si necesario

### 2. Usar Componentes Nuevos

- [ ] Usar `[flavor_page_header]` en todas las páginas
- [ ] Incluir parámetros `module` y `current` en header
- [ ] Verificar que `breadcrumbs="yes"` esté activo
- [ ] Elegir background apropiado (gradient para principal, white para resto)

### 3. Navegación

- [ ] Añadir módulo a `class-module-navigation.php`
- [ ] Definir items de navegación (mínimo 3: listado, mis-items, crear)
- [ ] Incluir iconos emoji apropiados
- [ ] Verificar que URLs sean correctas

### 4. Dashboard

- [ ] Añadir acciones rápidas a `get_quick_actions_smart()`
- [ ] Implementar `register_stats()` en módulo
- [ ] Definir valor principal y secundarios
- [ ] Incluir link de acción

### 5. Control de Acceso

- [ ] Verificar que páginas "mis-" requieren login automáticamente
- [ ] Verificar que formularios requieren login
- [ ] Páginas principales públicas si corresponde
- [ ] Usar `Flavor_User_Messages::access_denied()` para errores

### 6. Mensajes

- [ ] Usar mensajes de éxito para acciones completadas
- [ ] Usar mensajes de error mejorados
- [ ] Incluir redirecciones apropiadas tras acciones
- [ ] Mostrar notificaciones en dashboard si relevante

### 7. Responsive

- [ ] Verificar en móvil (< 640px)
- [ ] Verificar en tablet (640px - 1024px)
- [ ] Verificar en desktop (> 1024px)

---

## 📝 Plantilla Rápida

### Página Principal

```
[flavor_page_header
    title="[NOMBRE MÓDULO]"
    subtitle="[DESCRIPCIÓN]"
    breadcrumbs="yes"
    background="gradient"
    module="[ID_MODULO]"
    current="listado"]

[flavor_[ID_MODULO] tipo="grid" columnas="4"]
```

### Página Personal

```
[flavor_page_header
    title="Mis [ITEMS]"
    subtitle="Gestiona tus [items]"
    breadcrumbs="yes"
    background="white"
    module="[ID_MODULO]"
    current="mis-[items]"]

[flavor_[ID_MODULO]_usuario]
```

### Página de Formulario

```
[flavor_page_header
    title="[ACCIÓN] [ITEM]"
    subtitle="[DESCRIPCIÓN DE ACCIÓN]"
    breadcrumbs="yes"
    background="white"
    module="[ID_MODULO]"
    current="[accion]"]

[flavor_module_form
    module="[ID_MODULO]"
    action="[accion]_[item]"]
```

---

## 🎯 Convenciones de Nomenclatura

### URLs
- Principal: `/{modulo}/`
- Personal: `/{modulo}/mis-{items}/`
- Crear: `/{modulo}/crear/`
- Ver uno: `/{modulo}/{id}/`
- Editar: `/{modulo}/{id}/editar/`

### Slugs de Navegación
- `listado` o `catalogo` - Página principal
- `mis-{items}` - Página personal del usuario
- `crear` o nombre de acción - Acción principal
- Acciones específicas del módulo

### Iconos Sugeridos
- 📚 Biblioteca
- 📅 Eventos
- 🎨 Talleres
- 🔧 Incidencias
- 🏛️ Espacios
- 🌱 Grupos Consumo
- 🚲 Bicicletas
- ♻️ Reciclaje
- 🤝 Ayuda Vecinal

---

## 💡 Tips Finales

1. **Consistencia**: Todas las páginas de un módulo deben usar el mismo background en `flavor_page_header`
2. **Breadcrumbs**: Siempre activados excepto en home y mi-portal
3. **Navegación**: Mínimo 3 items (listado, personal, acción)
4. **Acciones Rápidas**: Máximo 2 acciones por módulo en dashboard
5. **Stats**: Valor principal + máximo 2 secundarios
6. **Mensajes**: Siempre usar sistema de mensajes mejorado
7. **Responsive**: Probar en los 3 breakpoints
8. **Login**: Confiar en detección automática, solo marcar manual si necesario

¡Con estos componentes, crear un módulo completo es rápido y consistente! 🚀
