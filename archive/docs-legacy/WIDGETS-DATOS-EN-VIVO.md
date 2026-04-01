# Widgets de Datos en Vivo - Mejora de Interconexión

## Resumen Ejecutivo

Los dashboards de Flavor Platform ahora muestran **datos reales en vivo** de módulos relacionados, en lugar de solo contadores estáticos. Los usuarios pueden ver los últimos 3 elementos de cada módulo relacionado directamente desde el dashboard actual.

**Fecha:** 22 de marzo de 2026
**Versión:** 3.3.0
**Estado:** ✅ Implementado en 3 dashboards (Marketplace, Incidencias, Cursos)

---

## Antes vs Después

### ANTES: Solo Contadores
```
┌─────────────────────────────────┐
│ Próximas Ferias y Mercados (5)  │
│ Eventos donde promocionar        │
│ tus productos                    │
│                                  │
│ [Ver detalles]                   │
└─────────────────────────────────┘
```

### DESPUÉS: Datos Reales
```
┌─────────────────────────────────┐
│ Próximas Ferias y Mercados (3)  │
│ Eventos donde promocionar        │
│ tus productos                    │
│                                  │
│ ┌─────────────────────────────┐ │
│ │ Feria Ecológica    📅 25/03 │ │
│ │ Mercado Local      📅 28/03 │ │
│ │ Festival Artesano  📅 05/04 │ │
│ └─────────────────────────────┘ │
│                                  │
│ [Ver todos →]                    │
└─────────────────────────────────┘
```

---

## Módulos con Datos en Vivo Implementados

### ✅ 1. Marketplace

#### Grupos de Consumo
**Muestra**: Últimos 3 productos disponibles en pedidos colectivos
```php
┌────────────────────────────────────────┐
│ Tomates ecológicos      2.50 €/ud      │
│ Lechugas                1.80 €/ud      │
│ Zanahorias              1.20 €/ud      │
└────────────────────────────────────────┘
```

#### Eventos
**Muestra**: Próximas 3 ferias y mercados
```php
┌────────────────────────────────────────┐
│ Feria Ecológica        📅 25/03/2026   │
│ Mercado Km 0           📅 28/03/2026   │
│ Festival Artesano      📅 05/04/2026   │
└────────────────────────────────────────┘
```

#### Socios
**Muestra**: Top 3 vendedores que son socios
```php
┌────────────────────────────────────────┐
│ María García           ⭐ 12 anuncios  │
│ Juan López             ⭐ 8 anuncios   │
│ Ana Martínez           ⭐ 6 anuncios   │
└────────────────────────────────────────┘
```

---

### ✅ 2. Incidencias

#### Participación
**Muestra**: 3 propuestas más votadas sobre incidencias
```php
┌────────────────────────────────────────┐
│ Reparar calle principal    👍 45 votos │
│ Iluminar parque            👍 32 votos │
│ Arreglar acera             👍 28 votos │
└────────────────────────────────────────┘
```

#### Trabajo Digno
**Muestra**: 3 profesionales disponibles para resolver
```php
┌────────────────────────────────────────┐
│ Fontanería urgente      🔧 reparacion  │
│ Electricista           🔧 mantenimiento│
│ Albañil                🔧 reparacion   │
└────────────────────────────────────────┘
```

#### Eventos
**Muestra**: Próximas 3 reuniones vecinales
```php
┌────────────────────────────────────────┐
│ Asamblea vecinal       📅 30/03/2026   │
│ Reunión barrio norte   📅 02/04/2026   │
│ Consejo de distrito    📅 08/04/2026   │
└────────────────────────────────────────┘
```

#### Transparencia
**Muestra**: Últimas 3 incidencias resueltas públicamente
```php
┌────────────────────────────────────────┐
│ Socavón calle Mayor    ✅ 20/03/2026   │
│ Farola rota            ✅ 18/03/2026   │
│ Bache avenida          ✅ 15/03/2026   │
└────────────────────────────────────────┘
```

---

### ✅ 3. Cursos

#### Talleres
**Muestra**: Próximos 3 talleres prácticos presenciales
```php
┌────────────────────────────────────────┐
│ Carpintería básica     🔧 25/03/2026   │
│ Cocina vegana          🔧 28/03/2026   │
│ Huerto urbano          🔧 05/04/2026   │
└────────────────────────────────────────┘
```

#### Biblioteca
**Muestra**: Últimos 3 recursos añadidos
```php
┌────────────────────────────────────────┐
│ Manual de permacultura  📚 Libro       │
│ Revista EcoVida 2026    📰 Revista     │
│ Curso de Photoshop      💿 DVD         │
└────────────────────────────────────────┘
```

#### Eventos
**Muestra**: Próximos 3 eventos formativos
```php
┌────────────────────────────────────────┐
│ Presentación curso IA   📅 30/03/2026  │
│ Graduación 2026         📅 15/04/2026  │
│ Jornada formativa       📅 20/04/2026  │
└────────────────────────────────────────┘
```

#### Socios
**Muestra**: 3 cursos con mayor descuento para socios
```php
┌────────────────────────────────────────┐
│ Curso Avanzado Python   🎓 -30%        │
│ Marketing Digital       🎓 -25%        │
│ Diseño Gráfico          🎓 -20%        │
└────────────────────────────────────────┘
```

#### Multimedia
**Muestra**: Últimos 3 vídeos de cursos subidos
```php
┌────────────────────────────────────────┐
│ Introducción a React    🎥 20/03/2026  │
│ CSS Grid Avanzado       🎥 18/03/2026  │
│ JavaScript ES2026       🎥 15/03/2026  │
└────────────────────────────────────────┘
```

---

## Arquitectura Técnica

### Estructura de Datos
```php
$modulos_relacionados['nombre-modulo'] = [
    'titulo' => 'Título del Widget (X)',      // X = número de items
    'descripcion' => 'Descripción contextual',
    'icono' => 'dashicons-icono',
    'url' => admin_url('admin.php?page=...'),
    'datos' => $datos_html,                   // ← NUEVO: HTML con datos
];
```

### Generación de Datos
```php
// En lugar de COUNT(*), obtener los datos reales
$items = $wpdb->get_results(
    "SELECT * FROM tabla
     WHERE condiciones
     ORDER BY relevancia DESC
     LIMIT 3"  // Solo 3 para no sobrecargar
);

// Generar HTML con los datos
$datos_html = '<div class="dm-widget-data-list">';
foreach ($items as $item) {
    $datos_html .= sprintf(
        '<div class="dm-widget-item">
            <strong>%s</strong>
            <span class="dm-widget-meta">%s</span>
        </div>',
        esc_html($item->nombre),
        esc_html($item->metadata)
    );
}
$datos_html .= '</div>';
```

### Renderizado Condicional
```php
<div class="dm-widget-relacionado">
    <h3><?php echo esc_html($modulo['titulo']); ?></h3>
    <p><?php echo esc_html($modulo['descripcion']); ?></p>

    <?php if (isset($modulo['datos'])): ?>
        <div class="dm-widget-datos-vivo">
            <?php echo $modulo['datos']; ?>
        </div>
    <?php endif; ?>

    <a href="<?php echo esc_url($modulo['url']); ?>">
        Ver todos →
    </a>
</div>
```

---

## Estilos CSS Reutilizables

### Clases Principales
```css
.dm-widget-relacionado          /* Contenedor del widget */
.dm-widget-datos-vivo           /* Caja de datos en vivo */
.dm-widget-data-list            /* Lista de items */
.dm-widget-item                 /* Item individual */
.dm-widget-meta                 /* Metadatos (fecha, icono) */
```

### Efectos Interactivos
- **Hover en widget**: Sombra + elevación sutil
- **Hover en item**: Cambio de fondo + desplazamiento →
- **Responsive**: En móvil, items en columna
- **Dark mode**: Automático con `prefers-color-scheme`

---

## Iconografía por Tipo de Dato

| Tipo | Icono | Contexto |
|------|-------|----------|
| Fecha | 📅 | Eventos, plazos, vencimientos |
| Precio | € | Productos, ventas, transacciones |
| Valoración | ⭐ | Ratings, destacados, top |
| Votos | 👍 | Participación, propuestas |
| Categoría | 🔧 | Tipos, clasificaciones |
| Estado | ✅ | Completado, resuelto |
| Libro | 📚 | Biblioteca, recursos |
| Vídeo | 🎥 | Multimedia, grabaciones |
| Descuento | 🎓 | Ofertas, beneficios socios |

---

## Beneficios Cuantificados

### Para Usuarios
- ✅ **+300% de información útil** vs antes (3 items vs solo 1 contador)
- ✅ **0 clicks extra** para ver datos relevantes
- ✅ **Contexto inmediato** sin cambiar de página
- ✅ **Descubrimiento natural** de contenido relacionado

### Para Administradores
- ✅ **Vista previa rápida** de módulos relacionados
- ✅ **Detección de problemas** (ej: "No hay eventos próximos")
- ✅ **Monitorización cross-module** sin dashboards separados

### Técnicos
- ✅ **Queries optimizadas**: Solo 3 items por módulo (LIMIT 3)
- ✅ **Caché-friendly**: Queries rápidas con índices
- ✅ **CSS reutilizable**: 1 archivo CSS para todos los dashboards
- ✅ **Extensible**: Fácil añadir nuevos tipos de datos

---

## Rendimiento

### Queries Ejecutadas
Por dashboard con 3 módulos relacionados:
- **Antes**: 3 queries COUNT(*)
- **Después**: 3 queries SELECT * LIMIT 3
- **Impacto**: Mínimo (similar rendimiento)

### Optimizaciones Aplicadas
1. **LIMIT 3**: Solo obtenemos lo necesario
2. **Índices**: Queries sobre campos indexados (fecha, estado, categoria)
3. **Caché**: WordPress cachea automáticamente `get_option()`
4. **Condicional**: Solo se ejecuta si el módulo está activo

---

## Próximos Pasos

### Alta Prioridad
1. **Extender a dashboards restantes**: Grupos Consumo, Socios, Eventos (3 dashboards)
2. **Acciones rápidas**: Click en item → abrir modal/página de detalle
3. **Actualización en tiempo real**: AJAX para refrescar datos sin recargar

### Media Prioridad
4. **Paginación ligera**: Ver más de 3 items con "Ver más ↓"
5. **Filtros inline**: Filtrar items dentro del widget
6. **Widgets configurables**: Admin elige qué módulos relacionados mostrar

### Baja Prioridad
7. **Gráficas mini**: Sparklines de tendencias
8. **Comparativas**: "Este mes vs mes anterior" inline
9. **Personalización por usuario**: Usuario elige qué widgets ver

---

## Archivos Modificados

```
includes/modules/marketplace/views/dashboard-mejorado.php
    - Grupos Consumo: Mostrar productos reales (+30 líneas)
    - Eventos: Mostrar ferias reales (+25 líneas)
    - Socios: Mostrar top vendedores (+30 líneas)
    - Renderizado mejorado con datos (+15 líneas)
    - Estilos inline (+60 líneas)

includes/modules/incidencias/views/dashboard-mejorado.php
    - Participación: Mostrar propuestas votadas (+25 líneas)
    - Trabajo Digno: Mostrar profesionales (+25 líneas)
    - Eventos: Mostrar reuniones (+20 líneas)
    - Transparencia: Mostrar resueltas (+20 líneas)
    - Renderizado mejorado (+15 líneas)
    - Estilos inline (+60 líneas)

includes/modules/cursos/views/dashboard-mejorado.php
    - Talleres: Mostrar próximos (+25 líneas)
    - Biblioteca: Mostrar recursos (+30 líneas)
    - Eventos: Mostrar formativos (+25 líneas)
    - Socios: Mostrar con descuento (+25 líneas)
    - Multimedia: Mostrar vídeos (+25 líneas)
    - Renderizado mejorado (+15 líneas)
    - Estilos inline (+60 líneas)

assets/css/dashboard-components-enhanced.css
    - Widgets de módulos relacionados (+95 líneas)
    - Soporte dark mode (+15 líneas)
    - Responsive mobile (+20 líneas)
```

**Total añadido:** ~640 líneas
**Total modificado:** ~3 archivos dashboards + 1 CSS global

---

## Ejemplo de Uso

Para añadir widgets de datos en vivo a un nuevo dashboard:

```php
// 1. Obtener datos reales (no solo COUNT)
$items = $wpdb->get_results(
    "SELECT * FROM tabla WHERE ... ORDER BY ... LIMIT 3"
);

// 2. Generar HTML de datos
$datos_html = '<div class="dm-widget-data-list">';
foreach ($items as $item) {
    $datos_html .= sprintf(
        '<div class="dm-widget-item">
            <strong>%s</strong>
            <span class="dm-widget-meta">%s</span>
        </div>',
        esc_html($item->titulo),
        '📅 ' . date_i18n('d/m/Y', strtotime($item->fecha))
    );
}
$datos_html .= '</div>';

// 3. Añadir al array de módulos relacionados
$modulos_relacionados['mi-modulo'] = [
    'titulo' => sprintf(__('Mi Módulo (%d)', 'flavor-chat-ia'), count($items)),
    'descripcion' => __('Descripción contextual', 'flavor-chat-ia'),
    'icono' => 'dashicons-icono',
    'url' => admin_url('admin.php?page=mi-modulo'),
    'datos' => $datos_html,  // ← Añadir datos aquí
];

// 4. El renderizado ya está listo (usa clases CSS globales)
```

---

## Conclusión

Los **Widgets de Datos en Vivo** transforman los módulos relacionados de simples enlaces con contadores en **vistas previas funcionales** que muestran información real y útil. Los usuarios ya no necesitan hacer clic para saber qué contiene cada módulo relacionado.

**Estado actual:** Implementado en 3/6 dashboards principales (50%)
**Próximo objetivo:** Completar los 3 dashboards restantes (Grupos Consumo, Socios, Eventos)

---

**Documento generado:** 22 de marzo de 2026
**Versión Flavor Platform:** 3.3.0
**Mantenedor:** Flavor Team
