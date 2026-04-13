# 🔍 Sistema de Previsualización y Vistas Frontend

## ✅ Sistema de Previsualización Implementado

### Archivos Creados:

1. **`/includes/web-builder/class-preview-handler.php`** (280 líneas)
   - Gestiona las peticiones de vista previa
   - AJAX endpoint para guardar preview temporal
   - Renderiza ventana de preview con barra superior
   - Toggle de dispositivos (Desktop/Tablet/Mobile)
   - Transients con expiración de 1 hora

2. **`/includes/web-builder/class-component-renderer.php`** (140 líneas)
   - Renderiza componentes en el frontend
   - Busca templates en tema/plugin
   - Aplica settings CSS (margin, padding, background, align)
   - Renderiza layouts completos
   - Manejo de errores amigable

3. **Actualizaciones en `/assets/js/page-builder.js`**:
   - Método `openPreview()` añadido
   - Llamada AJAX a `flavor_save_preview`
   - Abre ventana popup centrada
   - Loading states con spinner

4. **Actualizaciones en `/assets/css/page-builder.css`**:
   - Animación de spinner
   - Estilos de loading
   - Mensajes de error
   - Hover effects en botón preview

### Cómo Funciona:

```
1. Usuario hace clic en "Vista Previa" en el Page Builder
   ↓
2. JavaScript captura el layout actual
   ↓
3. AJAX envía layout a servidor (sin guardar en BD)
   ↓
4. Servidor guarda en transient temporal (1h)
   ↓
5. Devuelve URL única con nonce de seguridad
   ↓
6. Se abre ventana popup con:
   - Barra superior (MODO VISTA PREVIA)
   - Botones de dispositivo (Desktop/Tablet/Mobile)
   - Botón de cerrar
   - Contenido renderizado con todos los componentes
```

### Características de la Vista Previa:

✅ **Seguridad**:
- Nonces de WordPress
- Transients con expiración
- Solo usuarios con `edit_posts`

✅ **Responsive**:
- Toggle Desktop (100%)
- Toggle Tablet (768px)
- Toggle Mobile (375px)
- Animaciones suaves entre vistas

✅ **Visual**:
- Barra superior oscura con advertencia
- Iconos de dispositivos
- Contenedor adaptativo
- Estilos del sistema de diseño aplicados

✅ **UX**:
- Ventana popup centrada
- Spinner de carga
- Mensajes de error claros
- Botón cerrar funcional

### Uso:

1. **En el Page Builder**:
   - Añade componentes a tu página
   - Haz clic en "Vista Previa"
   - Se abre ventana con preview en tiempo real
   - Cambia entre vistas de dispositivos
   - Cierra cuando termines

2. **Renderizado Frontend**:
   - Las páginas con layout guardan en `_flavor_page_layout`
   - El filtro `the_content` detecta páginas con layout
   - Renderiza automáticamente los componentes
   - Aplica estilos del sistema de diseño

---

## 🌐 Vistas Públicas del Frontend

### Estado: EN PROCESO ⏳

Se están creando vistas públicas completas para **19 módulos**:

### Estructura de Vistas por Módulo:

Cada módulo tendrá en `/includes/modules/{module}/frontend/`:

```
frontend/
├── archive.php      - Listado principal (catálogo)
├── single.php       - Página de detalle
├── search.php       - Resultados de búsqueda
└── filters.php      - Sidebar de filtros (si aplica)
```

### Módulos con Vistas Frontend:

#### **Economía** (3 módulos)
1. ✅ **Banco de Tiempo**
   - Catálogo de servicios ofrecidos
   - Detalle de servicio con solicitud
   - Perfil de usuario con servicios

2. ✅ **Grupos de Consumo**
   - Catálogo de productos
   - Detalle de producto
   - Formulario de pedido
   - Ciclo activo

3. ✅ **Marketplace**
   - Grid de anuncios
   - Detalle de anuncio
   - Perfil de vendedor
   - Formulario de contacto

#### **Movilidad** (3 módulos)
4. ✅ **Carpooling**
   - Viajes disponibles
   - Detalle de viaje con mapa
   - Formulario de reserva
   - Perfil de conductor

5. ✅ **Parkings**
   - Plazas disponibles
   - Detalle de plaza con mapa
   - Calendario de disponibilidad
   - Formulario de reserva

6. ✅ **Bicicletas Compartidas**
   - Mapa de estaciones
   - Disponibilidad en tiempo real
   - Cómo usar el servicio

#### **Educación** (3 módulos)
7. ✅ **Cursos**
   - Catálogo de cursos
   - Detalle con programa
   - Formulario de matrícula
   - Perfil de instructor

8. ✅ **Biblioteca**
   - Catálogo de libros
   - Detalle de libro
   - Solicitud de préstamo
   - Historial de usuario

9. ✅ **Talleres**
   - Talleres próximos
   - Detalle de taller
   - Formulario de inscripción

#### **Comunidad** (3 módulos)
10. ✅ **Espacios Comunes**
    - Catálogo de espacios
    - Detalle con fotos
    - Calendario de disponibilidad
    - Formulario de reserva

11. ✅ **Ayuda Vecinal**
    - Solicitudes públicas
    - Detalle de solicitud
    - Formulario de ofrecimiento

12. ✅ **Eventos**
    - Calendario de eventos
    - Detalle de evento
    - Compra de entradas
    - Compartir en redes

#### **Medios** (3 módulos)
13. ✅ **Podcast**
    - Lista de episodios
    - Reproductor integrado
    - Suscripción a serie
    - Notas del episodio

14. ✅ **Radio**
    - Reproductor en vivo
    - Programación semanal
    - Programas on-demand
    - Info de locutores

15. ✅ **Multimedia**
    - Galería de fotos/videos
    - Vista de álbum
    - Lightbox para imágenes
    - Compartir en redes

#### **Ambiente** (3 módulos)
16. ✅ **Reciclaje**
    - Mapa de puntos limpios
    - Horarios de recogida
    - Guía de reciclaje
    - Estadísticas de impacto

17. ✅ **Compostaje**
    - Mapa de composteras
    - Cómo participar
    - Calendario de mantenimiento

18. ✅ **Huertos Urbanos**
    - Parcelas disponibles
    - Formulario de solicitud
    - Guía de cultivo

#### **Gestión** (1 módulo)
19. ✅ **Incidencias**
    - Formulario de reporte
    - Seguimiento de incidencia
    - Mapa de incidencias
    - Estado público

---

## 🎨 Características de las Vistas Frontend

### Diseño Consistente:

- **Tailwind CSS**: Utility-first para rápido desarrollo
- **CSS Variables**: Sistema de diseño integrado
- **Responsive**: Mobile-first, adaptado a todos los dispositivos
- **Accesible**: ARIA labels, semántica HTML5

### Componentes Comunes:

```php
<!-- Breadcrumbs -->
<nav class="flavor-breadcrumbs">
    <a href="/">Inicio</a> → <a href="/cursos">Cursos</a> → Curso Actual
</nav>

<!-- Card de Item -->
<div class="flavor-card hover:shadow-lg transition">
    <img src="..." class="w-full h-48 object-cover">
    <div class="p-6">
        <h3>Título</h3>
        <p>Descripción...</p>
        <a href="..." class="flavor-button">Ver más</a>
    </div>
</div>

<!-- Filtros -->
<aside class="flavor-filters">
    <h3>Filtrar por:</h3>
    <select name="categoria">...</select>
    <input type="search" placeholder="Buscar...">
</aside>

<!-- Paginación -->
<nav class="flavor-pagination">
    <a href="?page=1">‹ Anterior</a>
    <span>Página 2 de 10</span>
    <a href="?page=3">Siguiente ›</a>
</nav>
```

### Funcionalidades Incluidas:

✅ **Búsqueda y Filtros**:
- Campo de búsqueda en tiempo real
- Filtros por categoría, fecha, estado
- Ordenación (más reciente, popular, etc.)

✅ **Interactividad**:
- Formularios AJAX
- Loading states
- Success/error messages
- Validación frontend

✅ **Social**:
- Botones de compartir (Facebook, Twitter, WhatsApp)
- Likes/favoritos
- Comentarios (si aplica)

✅ **Media**:
- Lightbox para imágenes
- Reproductores de audio/video
- Mapas interactivos (Leaflet)
- Galerías responsivas

✅ **Formularios**:
- Validación HTML5
- Mensajes de error inline
- Confirmación visual
- Prevención de spam (nonces)

---

## 📝 Integración de las Vistas

### 1. Registro de Templates

Cada módulo debe registrar sus templates frontend:

```php
// En el archivo del módulo
public function register_frontend() {
    add_filter('template_include', [$this, 'load_frontend_template']);
}

public function load_frontend_template($template) {
    // Si es archive de nuestro CPT
    if (is_post_type_archive('banco_tiempo_servicio')) {
        $custom_template = __DIR__ . '/frontend/archive.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }

    // Si es single de nuestro CPT
    if (is_singular('banco_tiempo_servicio')) {
        $custom_template = __DIR__ . '/frontend/single.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }

    return $template;
}
```

### 2. Enqueue de Assets

```php
public function enqueue_frontend_assets() {
    if (is_post_type_archive('banco_tiempo_servicio') || is_singular('banco_tiempo_servicio')) {
        wp_enqueue_style(
            'banco-tiempo-frontend',
            plugins_url('assets/css/frontend.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'banco-tiempo-frontend',
            plugins_url('assets/js/frontend.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );
    }
}
```

### 3. AJAX Endpoints

Para funcionalidades dinámicas:

```php
// Búsqueda AJAX
add_action('wp_ajax_banco_tiempo_search', [$this, 'ajax_search']);
add_action('wp_ajax_nopriv_banco_tiempo_search', [$this, 'ajax_search']);

public function ajax_search() {
    check_ajax_referer('banco_tiempo_search', 'nonce');

    $query_args = [
        'post_type' => 'banco_tiempo_servicio',
        's' => sanitize_text_field($_POST['search']),
        'posts_per_page' => 12,
        // ... más args
    ];

    $query = new WP_Query($query_args);

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            include __DIR__ . '/frontend/partials/card.php';
        }
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
```

---

## 🚀 Ejemplo Completo: Módulo de Cursos

### Archive (Catálogo de Cursos)

**URL**: `/cursos/`

**Vista**: `cursos/frontend/archive.php`

**Muestra**:
- Grid de cards de cursos
- Filtros por categoría y nivel
- Búsqueda
- Paginación
- Breadcrumbs

### Single (Detalle de Curso)

**URL**: `/cursos/introduccion-a-wordpress/`

**Vista**: `cursos/frontend/single.php`

**Muestra**:
- Información del curso
- Programa/temario
- Instructor
- Precio y duración
- Formulario de matrícula
- Cursos relacionados
- Botones de compartir

---

## ✅ Checklist de Implementación

Por cada módulo:

- [ ] Crear directorio `/frontend/`
- [ ] Crear `archive.php`, `single.php`, `search.php`
- [ ] Registrar `template_include` filter
- [ ] Crear CSS específico si es necesario
- [ ] Crear JS para interactividad
- [ ] Añadir AJAX endpoints si se necesita
- [ ] Testear responsive design
- [ ] Verificar accesibilidad
- [ ] Probar formularios
- [ ] Verificar seguridad (nonces, sanitization)

---

## 📊 Estado del Proyecto

### ✅ Completado:
- Sistema de previsualización del Page Builder
- Renderizador de componentes frontend
- Handler de preview con transients
- Toggle responsive en preview

### ⏳ En Progreso:
- 19 módulos con vistas frontend completas
- Integración de todos los templates
- Testing de funcionalidades

### 📅 Próximos Pasos:
1. Finalizar todas las vistas frontend
2. Crear assets CSS/JS compartidos
3. Implementar AJAX endpoints
4. Testing exhaustivo
5. Documentación de uso

---

**Fecha**: 2026-01-28
**Versión**: 1.0.0
**Estado**: En desarrollo activo
