# Vistas Administrativas - Módulos de Economía Local

Este documento describe las vistas administrativas creadas para los tres módulos de economía local del plugin Flavor Chat IA.

## Resumen Ejecutivo

Se han creado **14 vistas administrativas** con más de **2,600 líneas de código** para los módulos:
- Banco de Tiempo (4 vistas)
- Grupos de Consumo (5 vistas)
- Marketplace (5 vistas)

Todas las vistas siguen los estándares de diseño de WordPress y están completamente localizadas en español.

---

## 1. Banco de Tiempo

### Ubicación
`/includes/modules/banco-tiempo/views/`

### Vistas Creadas

#### 1.1. dashboard.php
**Panel principal con estadísticas y resúmenes**

Características:
- 4 tarjetas de estadísticas principales:
  - Servicios activos
  - Intercambios completados
  - Intercambios pendientes
  - Total horas intercambiadas
- Gráficos interactivos (Chart.js):
  - Servicios por categoría (gráfico de dona)
  - Actividad últimos 6 meses (gráfico de líneas)
- Rankings:
  - Top 10 usuarios por horas ganadas
  - Top 10 usuarios por horas gastadas
- Tabla de intercambios recientes (últimos 10)
- Indicadores de estado con códigos de color

#### 1.2. servicios.php
**Gestión completa de servicios**

Características:
- Listado de servicios con paginación (20 por página)
- Filtros múltiples:
  - Por categoría (cuidados, educación, bricolaje, tecnología, transporte, otros)
  - Por estado (activo, inactivo, completado)
  - Búsqueda por texto
- Acciones:
  - Crear nuevo servicio (modal)
  - Editar servicio existente
  - Cambiar estado (activar/desactivar)
  - Eliminar servicio
- Modal de creación/edición con formulario completo
- Modal de detalles del servicio
- Sistema de nonces para seguridad

#### 1.3. intercambios.php
**Gestión de transacciones entre usuarios**

Características:
- 4 tarjetas de estadísticas rápidas:
  - Pendientes, Aceptados, Completados, Cancelados
- Filtros:
  - Por estado
  - Por rango de fechas (desde/hasta)
- Tabla de intercambios con información completa:
  - ID, servicio, usuarios involucrados
  - Horas, estado, fechas
- Acciones rápidas:
  - Aprobar intercambio pendiente
  - Ver detalles completos
  - Exportar a CSV
- Badges de estado con colores distintivos
- Paginación (25 elementos por página)

#### 1.4. usuarios.php
**Dashboard de usuarios con créditos y actividad**

Características:
- 3 estadísticas generales:
  - Total usuarios activos
  - Total horas circulando
  - Saldo promedio
- Tabla completa de usuarios con:
  - Servicios ofrecidos/activos
  - Horas ganadas/gastadas
  - Saldo actual (con código de color)
  - Intercambios completados/pendientes
- Ordenación flexible:
  - Por saldo (mayor/menor)
  - Por nombre (A-Z / Z-A)
  - Por intercambios
- Exportación a CSV con todos los datos
- Modal de historial de usuario
- Enlaces a perfiles de WordPress

---

## 2. Grupos de Consumo

### Ubicación
`/includes/modules/grupos-consumo/views/`

### Vistas Creadas

#### 2.1. dashboard.php
**Panel principal con estadísticas de pedidos**

Características:
- 6 tarjetas de estadísticas:
  - Total pedidos
  - Pedidos pendientes
  - Pedidos completados
  - Ventas del mes
  - Productos disponibles
  - Productores activos
- Notificación del ciclo actual (si hay uno abierto)
- Gráficos interactivos:
  - Productos más pedidos (barras)
  - Actividad por ciclo (líneas duales)
- Tabla de pedidos recientes
- Enlaces rápidos a gestión de ciclos

#### 2.2. productos.php
**Gestión de catálogo de productos**

Características:
- Redirige al Custom Post Type `gc_producto`
- Aprovecha las funcionalidades nativas de WordPress
- Integración completa con taxonomías y meta boxes

#### 2.3. pedidos.php
**Listado y gestión de pedidos**

Características:
- Filtros:
  - Por estado (pendiente, confirmado, completado, cancelado)
  - Por ciclo
- Tabla de pedidos con:
  - Producto, usuario, cantidades
  - Precios unitarios y totales
  - Estado y fecha
- Límite de 100 pedidos más recientes
- Información completa por pedido

#### 2.4. productores.php
**Gestión de productores locales**

Características:
- Redirige al Custom Post Type `gc_productor`
- Gestión centralizada desde WordPress

#### 2.5. ciclos.php
**Gestión de ciclos de pedidos**

Características:
- Redirige al Custom Post Type `gc_ciclo`
- Gestión de fechas de apertura/cierre
- Control de estados del ciclo

---

## 3. Marketplace

### Ubicación
`/includes/modules/marketplace/views/`

### Vistas Creadas

#### 3.1. dashboard.php
**Panel principal con estadísticas de anuncios**

Características:
- 4 tarjetas de estadísticas:
  - Anuncios activos
  - Anuncios pendientes
  - Categorías activas
  - Vendedores activos
- Panel de anuncios por tipo:
  - Regalo, Venta, Cambio, Alquiler
- Gráficos:
  - Anuncios por categoría (dona)
  - Anuncios por mes (barras)
- Tablas:
  - Anuncios recientes (últimos 10)
  - Vendedores más activos (top 10)

#### 3.2. productos.php
**Gestión de anuncios**

Características:
- Redirige al Custom Post Type `marketplace_item`
- Gestión completa de anuncios

#### 3.3. ventas.php
**Listado específico de ventas**

Características:
- Filtrado automático por tipo "venta"
- Tabla con:
  - Producto y miniatura
  - Vendedor, precio
  - Estado de conservación
  - Ubicación y fecha
- Acciones rápidas (editar, ver)
- Límite de 50 ventas

#### 3.4. vendedores.php
**Dashboard de vendedores**

Características:
- Listado de usuarios con anuncios
- Estadísticas:
  - Nombre, email
  - Total de anuncios
- Enlaces a:
  - Perfil del usuario
  - Todos los anuncios del vendedor
- Ordenación por número de anuncios

#### 3.5. categorias.php
**Gestión de categorías**

Características:
- Redirige a la taxonomía `marketplace_categoria`
- Gestión nativa de términos

---

## Características Técnicas Comunes

### Seguridad
- Verificación de `ABSPATH` en todos los archivos
- Uso de nonces en formularios
- Sanitización de inputs con funciones de WordPress
- Preparación de consultas SQL con `$wpdb->prepare()`
- Capacidades de usuario verificadas

### Diseño y UX
- Uso consistente de WordPress admin color scheme
- Dashicons para todos los iconos
- Grid layout responsive
- Tarjetas de estadísticas con bordes de color
- Badges con estados de color
- Paginación cuando corresponde

### Funcionalidades
- **Gráficos**: Chart.js integrado para visualizaciones
- **Filtros**: Múltiples opciones de filtrado
- **Búsqueda**: Campos de búsqueda donde aplica
- **Exportación**: Botones de exportar a CSV
- **Modales**: Ventanas modales para detalles y formularios
- **Ordenación**: Tablas ordenables
- **Paginación**: Listados paginados automáticamente

### Localización
- Todos los textos en español
- Uso de funciones de i18n listas para traducción
- Formato de fechas con `date_i18n()`
- Formato de números con separadores locales

### Performance
- Consultas optimizadas con índices
- Límites en consultas para evitar sobrecarga
- Uso de prepared statements
- Cache de resultados donde corresponde

---

## Integración con Módulos

Todas las vistas están diseñadas para integrarse perfectamente con:

1. **Banco de Tiempo Module** (`class-banco-tiempo-module.php`)
   - Tablas: `flavor_banco_tiempo_servicios`, `flavor_banco_tiempo_transacciones`
   - API: Métodos de acción definidos

2. **Grupos Consumo Module** (`class-grupos-consumo-module.php`)
   - Tablas: `flavor_gc_pedidos`
   - Custom Post Types: `gc_producto`, `gc_productor`, `gc_ciclo`

3. **Marketplace Module** (`class-marketplace-module.php`)
   - Custom Post Type: `marketplace_item`
   - Taxonomías: `marketplace_tipo`, `marketplace_categoria`

---

## Uso de las Vistas

Para utilizar estas vistas en el panel de administración, los módulos deben registrar páginas de menú que carguen estos archivos:

```php
// Ejemplo para Banco de Tiempo
add_menu_page(
    'Banco de Tiempo',
    'Banco Tiempo',
    'manage_options',
    'banco-tiempo-dashboard',
    function() {
        include __DIR__ . '/views/dashboard.php';
    },
    'dashicons-clock'
);

add_submenu_page(
    'banco-tiempo-dashboard',
    'Servicios',
    'Servicios',
    'manage_options',
    'banco-tiempo-servicios',
    function() {
        include __DIR__ . '/views/servicios.php';
    }
);
```

---

## Próximos Pasos Recomendados

1. **AJAX Handlers**: Implementar endpoints AJAX para:
   - Carga de detalles en modales
   - Actualización de estados sin recargar
   - Validaciones en tiempo real

2. **Exportación Mejorada**:
   - PDF además de CSV
   - Filtros en exportación
   - Exportación programada

3. **Notificaciones**:
   - Integrar con sistema de notificaciones
   - Alertas de acciones importantes
   - Emails automáticos

4. **Responsive Mobile**:
   - Optimizar para tablets y móviles
   - Menús colapsables
   - Tablas responsivas

5. **Ayuda Contextual**:
   - Tooltips explicativos
   - Documentación inline
   - Videos tutoriales

---

## Archivos Creados

### Banco de Tiempo (4 archivos)
1. `/includes/modules/banco-tiempo/views/dashboard.php` (~480 líneas)
2. `/includes/modules/banco-tiempo/views/servicios.php` (~460 líneas)
3. `/includes/modules/banco-tiempo/views/intercambios.php` (~380 líneas)
4. `/includes/modules/banco-tiempo/views/usuarios.php` (~400 líneas)

### Grupos de Consumo (5 archivos)
1. `/includes/modules/grupos-consumo/views/dashboard.php` (~380 líneas)
2. `/includes/modules/grupos-consumo/views/productos.php` (~10 líneas)
3. `/includes/modules/grupos-consumo/views/pedidos.php` (~90 líneas)
4. `/includes/modules/grupos-consumo/views/productores.php` (~10 líneas)
5. `/includes/modules/grupos-consumo/views/ciclos.php` (~10 líneas)

### Marketplace (5 archivos)
1. `/includes/modules/marketplace/views/dashboard.php` (~380 líneas)
2. `/includes/modules/marketplace/views/productos.php` (~10 líneas)
3. `/includes/modules/marketplace/views/ventas.php` (~80 líneas)
4. `/includes/modules/marketplace/views/vendedores.php` (~80 líneas)
5. `/includes/modules/marketplace/views/categorias.php` (~10 líneas)

**Total: 14 archivos, ~2,635 líneas de código**

---

## Contacto y Soporte

Para preguntas o mejoras sobre estas vistas, contactar al equipo de desarrollo de Flavor Chat IA.

---

**Fecha de Creación**: 28 de enero de 2026
**Versión**: 1.0
**Autor**: Claude Code Assistant
