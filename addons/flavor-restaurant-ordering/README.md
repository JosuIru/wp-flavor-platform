# Flavor Restaurant Ordering

Sistema completo de gestión de pedidos para restaurantes, integrado con Flavor Platform.

## 📋 Descripción

Este addon permite convertir tu sitio WordPress en un sistema completo de pedidos para restaurantes, donde:

- **Los clientes** pueden explorar el menú y hacer pedidos desde la app móvil
- **Los administradores** pueden gestionar mesas, pedidos y ver estadísticas en tiempo real

## ✨ Características Principales

### 🍽️ Gestión de Menú
- Vincula cualquier Custom Post Type de WordPress como elementos del menú
- Organiza items en categorías: Platos, Bebidas, Postres
- Soporte para imágenes, precios y descripciones
- Alérgenos y opciones de personalización

### 🪑 Gestión de Mesas
- Crea y gestiona mesas del restaurante
- Estados: Disponible, Ocupada, Reservada, En limpieza
- Códigos QR para cada mesa (opcional)
- Capacidad y ubicación de mesas

### 📱 Sistema de Pedidos
- Los clientes ordenan desde la app
- Estados personalizables (Pendiente, Preparando, Listo, Servido, Completado)
- Historial completo de cada pedido
- Cálculo automático de totales e impuestos

### 📊 Panel de Administración
- Vista en tiempo real de todos los pedidos
- Gestión de estados de pedidos
- Estadísticas de ventas
- Filtros por fecha, estado y mesa

### 🔌 API REST Completa
- Endpoints para apps móviles
- Autenticación integrada
- Documentación completa

## 📦 Instalación

### Requisitos
- WordPress 5.8 o superior
- PHP 7.4 o superior
- **Flavor Chat IA** (plugin principal) instalado y activo

### Pasos

1. **Descargar** el plugin
2. **Subir** a `/wp-content/plugins/flavor-restaurant-ordering/`
3. **Activar** desde el panel de WordPress
4. El plugin creará automáticamente las tablas de base de datos necesarias

## ⚙️ Configuración

### 1. Configurar Menú

Ve a **Flavor Platform > Restaurante > Configuración**

1. Selecciona qué CPTs forman parte de tu menú
2. Asígnalos a categorías (Platos, Bebidas, Postres)
3. Configura moneda e impuestos
4. Guarda cambios

**Ejemplo:**
```
Platos:
  - product (WooCommerce)
  - plato (CPT personalizado)

Bebidas:
  - bebida (CPT personalizado)

Postres:
  - postre (CPT personalizado)
```

### 2. Crear Mesas

Ve a **Flavor Platform > Restaurante > Mesas**

1. Click en "Agregar Mesa"
2. Completa:
   - Número de mesa (ej: "01", "A1")
   - Nombre (opcional, ej: "Mesa Terraza 1")
   - Capacidad (número de comensales)
   - Ubicación (ej: "Terraza", "Salón")
   - Notas
3. Guardar

Las mesas generarán automáticamente códigos QR si está habilitado.

### 3. Gestión de Pedidos

Ve a **Flavor Platform > Restaurante > Pedidos**

- **Ver pedidos activos**: Filtra por estado, mesa o fecha
- **Cambiar estados**: Click en un pedido y cambia su estado
- **Auto-refresh**: La lista se actualiza cada 30 segundos
- **Estadísticas**: Ver ingresos y pedidos del día

## 🌐 API REST

### Endpoints Disponibles

#### Obtener Menú Completo
```http
GET /wp-json/restaurant/v1/menu
```

**Respuesta:**
```json
{
  "success": true,
  "menu": {
    "dishes": {
      "label": "Platos",
      "icon": "restaurant",
      "color": "#FF5722",
      "items": [...]
    },
    "drinks": {...},
    "desserts": {...}
  }
}
```

#### Obtener Mesas
```http
GET /wp-json/restaurant/v1/tables
```

**Parámetros opcionales:**
- `status`: Filtrar por estado (available, occupied, reserved)
- `location`: Filtrar por ubicación

#### Crear Pedido
```http
POST /wp-json/restaurant/v1/orders
```

**Body:**
```json
{
  "table_id": 5,
  "customer_name": "Juan Pérez",
  "customer_phone": "612345678",
  "customer_email": "juan@example.com",
  "items": [
    {
      "post_id": 123,
      "quantity": 2,
      "notes": "Sin cebolla"
    },
    {
      "post_id": 124,
      "quantity": 1
    }
  ],
  "notes": "Alérgico a frutos secos"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Pedido creado exitosamente",
  "order": {
    "id": 42,
    "order_number": "MESA-260204-0001",
    "status": "pending",
    "total": 45.50,
    ...
  }
}
```

#### Actualizar Estado de Pedido
```http
PUT /wp-json/restaurant/v1/orders/{id}/status
```

**Body:**
```json
{
  "status": "preparing",
  "notes": "Comenzando preparación"
}
```

**Permisos:** Requiere autenticación de administrador

#### Obtener Estadísticas
```http
GET /wp-json/restaurant/v1/statistics
```

**Parámetros opcionales:**
- `date_from`: Fecha desde (Y-m-d)
- `date_to`: Fecha hasta (Y-m-d)

**Respuesta:**
```json
{
  "success": true,
  "statistics": {
    "total_orders": 150,
    "completed_orders": 140,
    "cancelled_orders": 5,
    "active_orders": 5,
    "total_revenue": 3250.75,
    "average_order_value": 23.22
  }
}
```

### Autenticación

Para endpoints que requieren permisos de administrador:

```javascript
// Usando JWT o sesión de WordPress
fetch('/wp-json/restaurant/v1/orders/42/status', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_JWT_TOKEN'
  },
  body: JSON.stringify({
    status: 'ready'
  })
});
```

## 📱 Integración con Apps Móviles

### App de Cliente

1. **Mostrar Menú**
   - Llamar a `/restaurant/v1/menu`
   - Mostrar categorías con sus items
   - Permitir agregar al carrito

2. **Seleccionar Mesa**
   - Escanear QR de la mesa
   - O seleccionar de lista (`/restaurant/v1/tables`)

3. **Hacer Pedido**
   - POST a `/restaurant/v1/orders`
   - Guardar `order_number` para seguimiento

4. **Seguir Pedido**
   - GET `/restaurant/v1/orders/number/{order_number}`
   - Mostrar estado actual

### App de Admin

1. **Ver Pedidos Activos**
   - GET `/restaurant/v1/orders?status=pending,preparing,ready`
   - Mostrar en tarjetas o lista

2. **Actualizar Estados**
   - PUT `/restaurant/v1/orders/{id}/status`
   - Notificar al cliente (futuro: push notifications)

3. **Ver Estadísticas**
   - GET `/restaurant/v1/statistics?date_from=2024-02-04`

## 🎨 Personalización

### Añadir Campo Personalizado al Menú

En tu tema o plugin:

```php
add_filter('flavor_restaurant_format_menu_item', function($data, $post) {
    // Agregar rating del producto
    $data['rating'] = get_post_meta($post->ID, '_rating', true);

    // Agregar si es vegetariano
    $data['vegetarian'] = get_post_meta($post->ID, '_vegetarian', true);

    return $data;
}, 10, 2);
```

### Estados Personalizados

Desde **Configuración > Estados de Pedido**, puedes:
- Agregar nuevos estados
- Modificar etiquetas
- Eliminar estados (excepto los requeridos)

### Modificar Cálculo de Impuestos

```php
add_filter('flavor_restaurant_calculate_tax', function($tax, $subtotal) {
    // Tu lógica personalizada
    return $subtotal * 0.15; // 15% de impuesto
}, 10, 2);
```

## 🔧 Troubleshooting

### Los pedidos no se crean

**Problema:** Error 403 o 500 al crear pedido

**Solución:**
1. Verifica que los permalinks estén configurados (no "Simple")
2. Verifica que los CPTs tengan precios configurados
3. Revisa los logs de errores de PHP

### No aparecen CPTs en el menú

**Problema:** Los CPTs no están disponibles para seleccionar

**Solución:**
1. Verifica que el CPT sea público: `'public' => true`
2. Verifica que tenga `'show_ui' => true`
3. Recarga la página de configuración

### Las mesas no generan QR

**Problema:** Campo QR vacío

**Solución:**
1. Verifica que la opción "Habilitar códigos QR" esté activa
2. Por ahora solo genera la URL, integración con librería QR pendiente

### Error al actualizar estado de pedido

**Problema:** "Estado de pedido inválido"

**Solución:**
- Verifica que el estado exista en **Configuración > Estados de Pedido**
- Usa solo estados configurados

## 📊 Base de Datos

El plugin crea 4 tablas:

### `wp_restaurant_tables`
Almacena las mesas del restaurante

### `wp_restaurant_orders`
Almacena los pedidos

### `wp_restaurant_order_items`
Almacena los items de cada pedido

### `wp_restaurant_order_status_history`
Almacena el historial de cambios de estado

## 🚀 Roadmap

Próximas características:

- [ ] Generación real de códigos QR con librería
- [ ] Notificaciones push para clientes y admins
- [ ] Reservas de mesas
- [ ] Programa de fidelización
- [ ] Múltiples ubicaciones/sucursales
- [ ] Integración con pasarelas de pago
- [ ] Propinas
- [ ] División de cuenta
- [ ] Impresora de cocina
- [ ] Estadísticas avanzadas

## 📝 Changelog

### 1.0.0 (2024-02-04)
- ✨ Versión inicial
- Gestión de menú con CPTs
- Sistema de mesas
- Sistema de pedidos completo
- API REST
- Panel de administración
- Integración con Flavor Platform

## 🤝 Soporte

Para soporte o sugerencias:
- GitHub Issues: [flavor-platform/restaurant-ordering](https://github.com/flavor-platform/restaurant-ordering)
- Email: support@flavor-platform.com

## 📄 Licencia

GPL v2 or later

## 👨‍💻 Autor

**Flavor Platform Team**
- Web: https://flavor-platform.com
- GitHub: [@flavor-platform](https://github.com/flavor-platform)

---

¿Necesitas ayuda? Revisa la documentación completa en [https://docs.flavor-platform.com/restaurant](https://docs.flavor-platform.com/restaurant)
