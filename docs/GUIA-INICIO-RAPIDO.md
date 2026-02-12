# 🚀 Guía de Inicio Rápido

## ¿Qué hacer AHORA mismo?

Todo está implementado y listo. Sigue estos pasos para activar todo:

---

## ⚡ PASO 1: Migrar Páginas Existentes (5 minutos)

Las páginas antiguas necesitan actualizarse al nuevo formato.

### Opción A: Por WP-CLI (Recomendado)
```bash
wp flavor migrate-pages
```

### Opción B: Por PHP
Crear archivo temporal `migrar-paginas.php` en la raíz:
```php
<?php
require_once('wp-load.php');
require_once('wp-content/plugins/flavor-chat-ia/includes/class-page-migrator.php');

$result = Flavor_Page_Migrator::migrate_all_pages();

echo "Migradas: " . count($result['migrated']) . "\n";
echo "Omitidas: " . count($result['skipped']) . "\n";

foreach ($result['migrated'] as $title) {
    echo " ✅ {$title}\n";
}
```

Ejecutar:
```bash
php migrar-paginas.php
```

**Resultado esperado**: Todas las páginas ahora tendrán breadcrumbs, navegación de módulo y headers modernos.

---

## 🎨 PASO 2: Añadir Menú Adaptativo (2 minutos)

### En el Theme Header

Editar `wp-content/themes/TU-TEMA/header.php`

Buscar donde está el menú actual y reemplazar con:
```php
<?php
// Menú Flavor Adaptativo (reemplaza el menú anterior)
if (function_exists('Flavor_Adaptive_Menu')) {
    echo do_shortcode('[flavor_adaptive_menu]');
}
?>
```

**Resultado esperado**:
- Usuarios NO logueados verán: Inicio, Servicios, Botones de Login/Registro
- Usuarios LOGUEADOS verán: Mi Portal, Servicios, Badge de notificaciones, Avatar con Dropdown

---

## 🔔 PASO 3: Probar Notificaciones (3 minutos)

### Crear una Notificación de Prueba

En `functions.php` del tema (temporal, para probar):
```php
add_action('init', function() {
    if (isset($_GET['test_notification']) && is_user_logged_in()) {
        Flavor_Notifications_System::notify_event_created(
            get_current_user_id(),
            'Evento de Prueba 2024'
        );
        wp_redirect(home_url('/mi-portal/'));
        exit;
    }
});
```

Luego visitar: `tudominio.com/?test_notification=1` (logueado)

**Resultado esperado**: Verás un badge "1" en el icono de notificaciones del menú.

---

## 🌙 PASO 4: Activar Dark Mode (Ya está activo!)

No hay que hacer nada. El botón flotante ya está visible en la esquina inferior derecha.

**Prueba**:
1. Ve a cualquier página
2. Haz click en el botón 🌙
3. Todo cambia a modo oscuro
4. El icono cambia a ☀️
5. Tu preferencia se guarda automáticamente

---

## 🎨 PASO 5: Personalizar Colores (Opcional)

### Crear Página de Configuración

1. Ir a: **Páginas → Añadir nueva**
2. Título: "Configuración"
3. Slug: `configuracion`
4. Contenido: `[flavor_theme_customizer]`
5. Publicar

Luego visitar: `tudominio.com/configuracion/`

**Resultado esperado**: Panel completo con:
- Selector de tema (Claro/Oscuro/Auto)
- 5 Color pickers
- 5 Presets predefinidos
- Botones Guardar/Restaurar

---

## ✅ VERIFICAR QUE TODO FUNCIONA

### Checklist Rápido

Ve a estas URLs y verifica:

#### 1. Mi Portal (`/mi-portal/`)
- ✅ Saludo personalizado (Buenos días/tardes/noches + tu nombre)
- ✅ Stats cards con 4 columnas
- ✅ Acciones rápidas con iconos
- ✅ Feed de actividad (puede estar vacío)
- ✅ Widget de perfil en sidebar
- ✅ Sin breadcrumbs en Mi Portal

#### 2. Página de Módulo (ej: `/eventos/`)
- ✅ Breadcrumbs: Mi Portal › Eventos
- ✅ Header con degradado
- ✅ Navegación: [Todos los Eventos] [Mis Eventos] [Crear Evento]
- ✅ Grid de eventos (o mensaje si no hay)

#### 3. Página Interna (ej: `/eventos/crear/`)
- ✅ Breadcrumbs: Mi Portal › Eventos › Crear Evento
- ✅ Header blanco (sin degradado)
- ✅ Navegación con "Crear Evento" activo
- ✅ Formulario visible

#### 4. Menú Header
- ✅ Logo a la izquierda
- ✅ Navegación centrada
- ✅ Avatar a la derecha (si logueado)
- ✅ Dropdown funciona al hacer click
- ✅ Click fuera cierra el dropdown

#### 5. Dark Mode
- ✅ Botón flotante visible abajo-derecha
- ✅ Click cambia todo el tema
- ✅ Icono cambia (🌙 ↔ ☀️)
- ✅ Recarga la página y mantiene el tema

#### 6. Notificaciones
- ✅ Badge en icono de campana (si hay notificaciones)
- ✅ Click lleva a Mi Portal
- ✅ Se actualiza en tiempo real

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Problema: "No veo el menú adaptativo"

**Solución**:
1. Verificar que añadiste el shortcode en `header.php`
2. Limpiar cache del tema/plugin
3. Verificar que `class-adaptive-menu.php` existe en `includes/`

### Problema: "Dark mode no funciona"

**Solución**:
1. Verificar que `class-theme-customizer.php` está cargado
2. Abrir consola del navegador, verificar que no hay errores JS
3. Limpiar cache del navegador
4. Verificar que CSS de `portal.css` se está cargando

### Problema: "No se ven breadcrumbs"

**Solución**:
1. Ejecutar migración de páginas
2. Verificar que `class-breadcrumbs.php` está cargado
3. Revisar que la página no sea "home" ni "mi-portal" (no tienen breadcrumbs)

### Problema: "Notificaciones no aparecen"

**Solución**:
1. Verificar que tabla fue creada: `wp_flavor_notifications`
2. Crear notificación de prueba (ver PASO 3)
3. Verificar que estás logueado
4. Refrescar página

### Problema: "Páginas no tienen navegación de módulo"

**Solución**:
1. Ejecutar migración de páginas
2. Verificar que el parámetro `module="xxx"` está en el `[flavor_page_header]`
3. Verificar que el módulo está definido en `class-module-navigation.php`

---

## 📱 PROBAR EN MÓVIL

### Responsive Checklist

1. **Menú**: Debe mostrar botón de hamburguesa
2. **Dashboard**: Stats cards apilan en 1 columna
3. **Navegación**: Se puede hacer scroll horizontal
4. **Dark Mode**: Botón flotante sube (bottom: 80px)
5. **Dropdown**: Funciona igual que en desktop

---

## 🎯 SIGUIENTES PASOS RECOMENDADOS

### 1. Añadir más Módulos a Navegación

Editar: `includes/class-module-navigation.php`

En el array `$default_items` añadir tu módulo:
```php
'tu-modulo' => [
    [
        'slug' => 'listado',
        'label' => __('Todos', 'flavor-chat-ia'),
        'url' => home_url('/tu-modulo/'),
        'icon' => '📦'
    ],
    // ... más items
],
```

### 2. Crear Notificaciones Automáticas

En tu módulo, cuando ocurra una acción:
```php
Flavor_Notifications_System::get_instance()->create(
    $user_id,
    'success',
    'Título',
    'Mensaje',
    ['link' => '/url/', 'icon' => '✅']
);
```

### 3. Personalizar Colores del Sitio

1. Ir a `/configuracion/`
2. Elegir preset "Océano" o "Bosque"
3. O usar color pickers personalizados
4. Guardar
5. Todo el sitio cambia de colores

### 4. Crear Nuevas Páginas con el Formato Correcto

Usar Page Creator V2:
```php
// En functions.php o plugin custom
Flavor_Page_Creator_V2::create_or_update_pages();
```

O manualmente al crear página usar:
```
[flavor_page_header
    title="Mi Página"
    subtitle="Descripción"
    breadcrumbs="yes"
    background="white"
    module="mi-modulo"
    current="slug-actual"]

[Contenido aquí]
```

---

## 📚 DOCUMENTACIÓN COMPLETA

Para más detalles ver:
- `IMPLEMENTACION-COMPLETA-FINAL.md` - Resumen ejecutivo completo
- `COMPONENTES-NUEVOS.md` - Guía de todos los shortcodes
- `EJEMPLO-MODULO-COMPLETO.md` - Ejemplo paso a paso
- `RESUMEN-IMPLEMENTACION.md` - Detalles técnicos

---

## ✨ ¡LISTO!

Si seguiste todos los pasos, ahora tienes:

✅ Dashboard moderno como centro del sitio
✅ Menú adaptativo con avatar y dropdown
✅ Dark mode funcional con un click
✅ Sistema de notificaciones activo
✅ Breadcrumbs en todas las páginas
✅ Navegación contextual de módulos
✅ Personalización completa de colores
✅ Páginas con componentes estandarizados
✅ Mensajes de error elegantes
✅ Control de acceso automático

**¡Disfruta del sistema completo!** 🎉

¿Necesitas ayuda? Revisa la documentación o contacta al equipo de desarrollo.
