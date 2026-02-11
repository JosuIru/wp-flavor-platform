# Páginas Frontend Automáticas por Módulo

**Fecha**: 2026-02-11
**Estado**: ✅ Implementado en 17 módulos

## 📋 Sistema de Creación Automática

Todos los módulos con páginas frontend ahora crean automáticamente sus páginas de WordPress cuando se activan. Las páginas son **completamente customizables** con cualquier page builder.

### ✨ Características

✅ **Creación Automática**: Las páginas se crean al activar el módulo
✅ **Una Sola Vez**: No duplica si ya existen
✅ **Actualización Inteligente**: Refresca en admin si faltan
✅ **Totalmente Editable**: Son páginas estándar de WordPress
✅ **Compatible con Page Builders**: Elementor, Gutenberg, Divi, etc.
✅ **Respeta tus Cambios**: No sobrescribe personalizaciones

---

## 📄 Páginas por Módulo

### 1. Eventos (4 páginas)
- `/eventos/` - Listado de eventos próximos
- `/eventos/crear/` - Formulario para crear evento
- `/eventos/inscribirse/` - Formulario de inscripción
- `/eventos/mis-eventos/` - Dashboard de mis eventos

### 2. Banco de Tiempo (4 páginas)
- `/banco-tiempo/` - Listado de servicios
- `/banco-tiempo/ofrecer/` - Ofrecer servicio
- `/banco-tiempo/solicitar/` - Solicitar servicio
- `/banco-tiempo/mis-intercambios/` - Dashboard

### 3. Grupos de Consumo (6 páginas)
- `/grupos-consumo/` - Página principal
- `/grupos-consumo/productos/` - Catálogo de productos
- `/grupos-consumo/panel/` - Panel de gestión
- `/grupos-consumo/mi-cesta/` - Cesta de compra
- `/grupos-consumo/pedidos/` - Historial de pedidos
- `/grupos-consumo/suscripcion/` - Gestión de suscripción

### 4. Talleres (4 páginas)
- `/talleres/` - Listado de talleres
- `/talleres/crear/` - Crear taller
- `/talleres/inscribirse/` - Inscribirse
- `/talleres/mis-talleres/` - Mis talleres

### 5. Marketplace (3 páginas)
- `/marketplace/` - Listado de anuncios
- `/marketplace/publicar/` - Publicar anuncio
- `/marketplace/mis-anuncios/` - Mis anuncios

### 6. Facturas (4 páginas)
- `/facturas/` - Listado de facturas
- `/facturas/crear/` - Nueva factura
- `/facturas/mis-facturas/` - Mis facturas
- `/facturas/buscar/` - Buscar facturas

### 7. Socios (5 páginas)
- `/socios/` - Información de socios
- `/socios/unirme/` - Hacerse socio
- `/socios/mi-perfil/` - Mi perfil
- `/socios/pagar-cuota/` - Pagar cuota
- `/socios/actualizar-datos/` - Actualizar datos

### 8. Fichaje Empleados (6 páginas)
- `/fichaje/` - Dashboard de fichajes
- `/fichaje/entrada/` - Fichar entrada
- `/fichaje/salida/` - Fichar salida
- `/fichaje/pausar/` - Pausar jornada
- `/fichaje/reanudar/` - Reanudar
- `/fichaje/solicitar-correccion/` - Solicitar corrección

### 9. Foros (4 páginas)
- `/foros/` - Listado de temas
- `/foros/crear-tema/` - Crear tema
- `/foros/mis-temas/` - Mis temas
- `/foros/responder/` - Responder

### 10. Incidencias (3 páginas)
- `/incidencias/` - Listado de incidencias
- `/incidencias/reportar/` - Reportar incidencia
- `/incidencias/mis-reportes/` - Mis reportes

### 11. Huertos Urbanos (páginas)
- `/huertos-urbanos/` - Listado de huertos
- `/huertos-urbanos/solicitar/` - Solicitar parcela
- `/huertos-urbanos/mi-huerto/` - Mi huerto

### 12. Espacios Comunes (3 páginas)
- `/espacios-comunes/` - Listado de espacios
- `/espacios-comunes/reservar/` - Reservar espacio
- `/espacios-comunes/mis-reservas/` - Mis reservas

### 13. Presupuestos Participativos (4 páginas)
- `/presupuestos/` - Listado de proyectos
- `/presupuestos/proponer/` - Proponer proyecto
- `/presupuestos/votar/` - Votar proyectos
- `/presupuestos/resultados/` - Resultados

### 14. Avisos Municipales (2 páginas)
- `/avisos/` - Listado de avisos
- `/avisos/mis-avisos/` - Mis avisos

### 15. Biblioteca (3 páginas)
- `/biblioteca/` - Catálogo
- `/biblioteca/reservar/` - Reservar libro
- `/biblioteca/mis-prestamos/` - Mis préstamos

### 16. Carpooling (3 páginas)
- `/carpooling/` - Viajes disponibles
- `/carpooling/publicar/` - Publicar viaje
- `/carpooling/mis-viajes/` - Mis viajes

### 17. Bicicletas Compartidas (3 páginas)
- `/bicicletas-compartidas/` - Estaciones
- `/bicicletas-compartidas/alquilar/` - Alquilar
- `/bicicletas-compartidas/mis-alquileres/` - Mis alquileres

---

## 🎨 Personalización con Page Builders

### Contenido por Defecto

Cada página se crea con shortcodes básicos de Flavor:

```html
<h1>Título del Módulo</h1>
<p>Descripción breve</p>
[flavor_module_listing module="nombre" action="accion" columnas="3"]
```

### Personalización Total

Puedes editar las páginas con:

- ✅ **Gutenberg** (Block Editor)
- ✅ **Elementor**
- ✅ **Divi Builder**
- ✅ **Beaver Builder**
- ✅ **WPBakery**
- ✅ **Cualquier otro page builder**

### Ejemplo de Personalización

**Antes (auto-generado):**
```
/eventos/
├─ Título simple
├─ Shortcode básico
└─ Contenido mínimo
```

**Después (personalizado):**
```
/eventos/
├─ Hero section con imagen
├─ Buscador de eventos
├─ Filtros por categoría
├─ Calendario interactivo
├─ Shortcode de eventos
├─ Newsletter signup
└─ Footer personalizado
```

---

## 🔧 Implementación Técnica

### Métodos Agregados a Cada Módulo

```php
public function init() {
    add_action('init', [$this, 'maybe_create_tables']);
    add_action('init', [$this, 'maybe_create_pages']); // NUEVO
    // ...
}

public function maybe_create_pages() {
    if (!class_exists('Flavor_Page_Creator')) {
        return;
    }

    // En admin: refrescar páginas del módulo
    if (is_admin()) {
        Flavor_Page_Creator::refresh_module_pages('module_id');
        return;
    }

    // En frontend: crear páginas si no existen (solo una vez)
    $pagina = get_page_by_path('module-slug');
    if (!$pagina && !get_option('flavor_module_pages_created')) {
        Flavor_Page_Creator::create_pages_for_modules(['module_id']);
        update_option('flavor_module_pages_created', 1, false);
    }
}
```

### Metadatos de las Páginas

Cada página auto-generada tiene:

```php
_flavor_auto_page = 1  // Marca como página automática
_flavor_auto_page_modules = 'module_id'  // Lista de módulos que usan esta página
```

### Control de Creación

```php
// Opción que previene duplicación
flavor_{module_id}_pages_created = 1
```

---

## 📊 Estadísticas

| Métrica | Valor |
|---------|-------|
| **Módulos con páginas** | 17 |
| **Total de páginas** | ~60 páginas |
| **Archivos modificados** | 41 archivos |
| **Líneas agregadas** | 22,736+ líneas |

---

## 🚀 Activación

### Automática (Recomendado)
1. Activa un módulo desde **Flavor Platform > Compositor**
2. Las páginas se crean automáticamente
3. Visita el frontend para verificar

### Manual (Si es necesario)
```php
// Ejecutar en wp-admin/
if (class_exists('Flavor_Page_Creator')) {
    Flavor_Page_Creator::create_pages_for_modules(['eventos']);
}
```

### Verificación
```sql
-- Ver páginas auto-generadas
SELECT post_title, post_name, post_status
FROM wp_posts
WHERE post_type = 'page'
AND ID IN (
    SELECT post_id FROM wp_postmeta
    WHERE meta_key = '_flavor_auto_page'
    AND meta_value = '1'
)
ORDER BY post_title;
```

---

## ✅ Ventajas del Sistema

1. **No reinventa la rueda**: Usa el sistema de páginas de WordPress
2. **SEO friendly**: URLs limpias, slugs personalizables
3. **Multiidioma compatible**: Funciona con WPML, Polylang, etc.
4. **Performance**: Las páginas se cachean como cualquier otra
5. **Backup incluido**: Se respaldan con cualquier plugin de backup
6. **Revisiones**: Historial de cambios de WordPress
7. **Permisos granulares**: Control de acceso por roles de WP
8. **Media library**: Usa el sistema de medios nativo

---

## 🔄 Mantenimiento

### Refrescar Páginas

El sistema refresca automáticamente en admin, pero si necesitas forzar:

```php
Flavor_Page_Creator::refresh_module_pages('eventos');
```

### Recrear Páginas

Si borraste una página por error:

```php
delete_option('flavor_eventos_pages_created');
// Luego visita el frontend o admin
```

### Limpiar Todas

```php
// Solo si quieres empezar de cero
global $wpdb;
$wpdb->query("DELETE FROM wp_postmeta WHERE meta_key = '_flavor_auto_page'");
$wpdb->query("DELETE FROM wp_options WHERE option_name LIKE 'flavor_%_pages_created'");
```

---

## 📝 Notas Importantes

⚠️ **No modifiques los shortcodes core** si quieres que el módulo funcione. Puedes añadir contenido alrededor, pero mantén el shortcode principal.

✅ **Puedes cambiar todo lo demás**: diseño, colores, tipografías, estructura, bloques adicionales, etc.

✅ **Las páginas persisten** aunque desactives el módulo (comportamiento estándar de WordPress).

✅ **Puedes eliminar páginas** manualmente si no las necesitas. El sistema no las recreará si ya existe la opción `flavor_{module}_pages_created`.

---

*Documento generado automáticamente el 2026-02-11*
*Flavor Platform v3.0+*
