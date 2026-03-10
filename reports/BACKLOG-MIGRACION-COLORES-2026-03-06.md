# Backlog - Migración Sistema de Colores Centralizado

**Fecha:** 2026-03-06
**Estado:** En progreso

## Sistema Implementado

### Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `includes/themes/class-module-colors.php` | Clase PHP helper para gestión de colores por módulo |
| `assets/css/dashboard-module-components.css` | CSS centralizado con clases `.dm-*` |

### Dashboards Migrados (33/~36)

- [x] `modules/colectivos/views/dashboard.php`
- [x] `modules/eventos/views/dashboard.php`
- [x] `modules/marketplace/views/dashboard.php`
- [x] `modules/grupos-consumo/views/dashboard.php`
- [x] `modules/socios/views/dashboard.php`
- [x] `modules/reservas/views/dashboard.php`
- [x] `modules/foros/views/dashboard.php`
- [x] `modules/participacion/views/dashboard.php`
- [x] `modules/banco-tiempo/views/dashboard.php`
- [x] `modules/huertos-urbanos/views/dashboard.php`
- [x] `modules/biblioteca/views/dashboard.php`
- [x] `modules/cursos/views/dashboard.php`
- [x] `modules/talleres/views/dashboard.php`
- [x] `modules/tramites/views/dashboard.php`
- [x] `modules/incidencias/views/dashboard.php`
- [x] `modules/radio/views/dashboard.php`
- [x] `modules/podcast/views/dashboard.php`
- [x] `modules/transparencia/views/dashboard.php`
- [x] `modules/presupuestos-participativos/views/dashboard.php`
- [x] `modules/comunidades/views/dashboard.php`
- [x] `modules/campanias/views/dashboard.php`
- [x] `modules/carpooling/views/dashboard.php`
- [x] `modules/bicicletas-compartidas/views/dashboard.php`
- [x] `modules/compostaje/views/dashboard.php`
- [x] `modules/parkings/views/dashboard.php`
- [x] `modules/espacios-comunes/views/dashboard.php`
- [x] `modules/economia-don/views/dashboard.php`
- [x] `modules/ayuda-vecinal/views/dashboard.php`
- [x] `modules/mapa-actores/views/dashboard.php`
- [x] `modules/energia-comunitaria/views/dashboard.php`
- [x] `modules/reciclaje/views/dashboard.php`

### Clases CSS Disponibles

```
Estructura:
- dm-dashboard         - Contenedor principal
- dm-header            - Cabecera del dashboard
- dm-header__title     - Título con icono
- dm-card              - Tarjeta genérica
- dm-card--chart       - Tarjeta para gráficos
- dm-card__header      - Cabecera de tarjeta con título y acciones
- dm-card__chart       - Contenedor de canvas para Chart.js
- dm-card__meta        - Metadatos en cabecera de tarjeta
- dm-stats-grid        - Grid de estadísticas (--3, --4)
- dm-stat-card         - Tarjeta de estadística (+ variantes: --primary, --success, --warning, --error, --info, --purple, --pink)
- dm-stat-card__meta   - Texto secundario en stat card
- dm-action-grid       - Grid de acciones rápidas (--2, --3)
- dm-action-card       - Tarjeta de acción (+ variantes)
- dm-action-card__content - Contenido estructurado en action card
- dm-btn               - Botones (--primary, --secondary, --success, --warning, --error, --ghost, --sm, --lg)
- dm-badge             - Badges de estado (--primary, --success, --warning, --error, --info, --secondary)
- dm-table             - Tablas estilizadas
- dm-table__subtitle   - Subtítulo en celda de tabla
- dm-table__muted      - Texto secundario en celda
- dm-alert             - Alertas (--info, --success, --warning, --error)
- dm-progress          - Barras de progreso (--success, --warning, --error)
- dm-grid              - Layouts en grid (--2, --3, --4)
- dm-ranking           - Listas ordenadas con números
- dm-ranking__item     - Item de ranking con label y value
- dm-trend             - Mini gráfico de barras verticales
- dm-trend__value      - Valor numérico bajo la barra
- dm-trend__label      - Etiqueta (mes, etc.)
- dm-badge-list        - Lista de badges con valores
- dm-badge-list__item  - Item con badge y valor
- dm-focus-list        - Lista de métricas/alertas
- dm-focus-list__item  - Item con variantes de color (--success, --warning, --error, --info)
- dm-item-list         - Lista de items estructurados (usuarios, registros)
- dm-item-list__item   - Item con content y meta
- dm-item-list__muted  - Texto secundario en item
- dm-empty             - Estados vacíos con icono
- dm-quick-links       - Enlaces rápidos horizontales
- dm-quick-links__item - Item de enlace rápido

Utilidades:
- dm-text-primary, dm-text-success, dm-text-warning, dm-text-error, dm-text-muted - Colores de texto
- dm-text-xs, dm-text-sm, dm-text-base, dm-text-lg, dm-text-xl - Tamaños de texto
```

## Dashboards Pendientes de Migrar

### Prioridad Alta (uso frecuente)
✅ Todos migrados

### Prioridad Media
✅ Todos migrados

### Prioridad Baja
- [x] `modules/carpooling/views/dashboard.php` ✅
- [x] `modules/bicicletas-compartidas/views/dashboard.php` ✅
- [x] `modules/compostaje/views/dashboard.php` ✅
- [x] `modules/parkings/views/dashboard.php` ✅
- [x] `modules/espacios-comunes/views/dashboard.php` ✅
- [x] `modules/economia-don/views/dashboard.php` ✅
- [x] `modules/ayuda-vecinal/views/dashboard.php` ✅
- [x] `modules/mapa-actores/views/dashboard.php` ✅
- [x] `modules/energia-comunitaria/views/dashboard.php` ✅
- [x] `modules/reciclaje/views/dashboard.php` ✅
- [ ] `modules/documentacion-legal/views/dashboard.php`
- [ ] `modules/seguimiento-denuncias/views/dashboard.php`
- [ ] `modules/email-marketing/views/dashboard.php`
- [ ] `modules/red-social/views/dashboard.php`

### No existen (eliminados del backlog)
- `modules/circulos-cuidados/views/dashboard.php`
- `modules/huella-ecologica/views/dashboard.php`
- `modules/biodiversidad-local/views/dashboard.php`
- `modules/fichaje-empleados/views/dashboard.php`
- `modules/justicia-restaurativa/views/dashboard.php`
- `modules/trabajo-digno/views/dashboard.php`

## Mejoras Futuras Detectadas

### CSS
1. **Variables adicionales de tema**: Añadir más colores semánticos si se necesitan (cyan, indigo, etc.)
2. **Dark mode mejorado**: Revisar contraste de colores en modo oscuro
3. **Print styles**: Añadir estilos para impresión de dashboards

### PHP
1. **Admin UI para colores**: Crear página en admin para que usuarios personalicen colores por módulo sin código
2. **Exportar/Importar paletas**: Permitir guardar y cargar configuraciones de colores
3. **Preview en tiempo real**: Ver cambios de colores sin recargar página

### Documentación
1. **Guía de migración**: Documentar paso a paso cómo migrar un dashboard
2. **Ejemplos de uso**: Snippets de código para cada componente
3. **Storybook/Preview**: Página que muestre todos los componentes disponibles

## Patrón de Migración

```php
// ANTES (inline styles hardcodeados)
<div style="background: #fff; padding: 20px; border-left: 4px solid #3b82f6;">
    <span style="color: #3b82f6;">12</span>
    <span style="color: #64748b;">Total</span>
</div>

// DESPUÉS (clases del sistema)
<div class="dm-stat-card">
    <div class="dm-stat-card__value">12</div>
    <div class="dm-stat-card__label">Total</div>
</div>
```

## Notas

- El sistema ya está activo y cargando CSS en frontend y admin
- Los dashboards no migrados seguirán funcionando con sus estilos inline
- La migración es gradual y no rompe nada existente
