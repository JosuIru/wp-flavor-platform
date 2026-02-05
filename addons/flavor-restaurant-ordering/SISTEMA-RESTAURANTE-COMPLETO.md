# Sistema Completo de Restaurante - Flavor Platform

## 📦 ¿Qué se ha Creado?

Se ha desarrollado un **addon completo para gestión de pedidos de restaurante** que se integra perfectamente con Flavor Platform y permite:

- 🍽️ Gestionar el menú usando Custom Post Types de WordPress
- 🪑 Administrar mesas del restaurante
- 📱 Recibir pedidos desde apps móviles
- 👨‍💼 Panel de administración completo
- 📊 Estadísticas en tiempo real
- 🔌 API REST completa

---

## 📂 Estructura de Archivos Creados

```
flavor-restaurant-ordering/
├── flavor-restaurant-ordering.php          # Plugin principal
├── README.md                                # Documentación técnica
├── GUIA-USUARIO.md                         # Guía para usuarios finales
├── SISTEMA-RESTAURANTE-COMPLETO.md         # Este archivo
│
├── includes/                                # Lógica del sistema
│   ├── class-restaurant-manager.php        # Gestor principal del restaurante
│   ├── class-table-manager.php             # Gestión de mesas
│   ├── class-order-manager.php             # Gestión de pedidos
│   └── class-restaurant-api.php            # Endpoints REST API
│
├── admin/                                   # Panel de administración
│   ├── class-restaurant-settings.php       # Configuración del restaurante
│   └── class-order-admin.php               # Admin de pedidos y mesas
│
└── assets/                                  # Recursos frontend
    ├── css/
    │   ├── restaurant-settings.css         # Estilos de configuración
    │   └── restaurant-admin.css            # Estilos de administración
    └── js/
        ├── restaurant-settings.js          # JS de configuración
        └── restaurant-admin.js             # JS de administración
```

---

## 🎯 Componentes del Sistema

### 1. Base de Datos (4 Tablas)

#### `wp_restaurant_tables`
Almacena las mesas del restaurante:
- Número de mesa
- Nombre/ubicación
- Capacidad
- Estado (disponible, ocupada, reservada, etc.)
- Código QR

#### `wp_restaurant_orders`
Almacena los pedidos:
- Número de pedido único
- Mesa asociada
- Datos del cliente
- Estado
- Totales (subtotal, impuesto, total)
- Fechas

#### `wp_restaurant_order_items`
Items de cada pedido:
- Referencia al pedido
- ID del post/producto
- Cantidad
- Precio unitario
- Subtotal
- Notas especiales

#### `wp_restaurant_order_status_history`
Historial de cambios de estado:
- ID del pedido
- Estado
- Usuario que hizo el cambio
- Notas
- Timestamp

### 2. Managers (Lógica de Negocio)

#### Restaurant Manager (`class-restaurant-manager.php`)
**Responsabilidades:**
- Configuración general del restaurante
- Gestión de vinculación CPTs ↔ Categorías de menú
- Formateo de items del menú para la app
- Cálculo de precios e impuestos
- Obtención de menú completo

**Métodos principales:**
```php
get_menu_cpts()           // Obtener CPTs configurados
get_full_menu()           // Menú completo para apps
get_menu_items($post_type) // Items de un CPT específico
calculate_total($subtotal) // Calcular total con impuestos
format_price($amount)      // Formatear precio
```

#### Table Manager (`class-table-manager.php`)
**Responsabilidades:**
- CRUD de mesas
- Gestión de estados
- Generación de códigos QR
- Verificación de pedidos activos
- Estadísticas de mesas

**Métodos principales:**
```php
create_table($data)        // Crear nueva mesa
get_table($id)             // Obtener mesa por ID
get_tables($args)          // Listar mesas con filtros
update_table($id, $data)   // Actualizar mesa
delete_table($id)          // Eliminar mesa
update_status($id, $status) // Cambiar estado
get_statistics()           // Estadísticas de mesas
```

#### Order Manager (`class-order-manager.php`)
**Responsabilidades:**
- CRUD de pedidos
- Validación de items del menú
- Cálculo de totales
- Gestión de estados
- Historial de cambios
- Estadísticas de ventas

**Métodos principales:**
```php
create_order($data)        // Crear nuevo pedido
get_order($id)             // Obtener pedido por ID
get_orders($args)          // Listar pedidos con filtros
update_status($id, $status, $notes) // Cambiar estado
get_status_history($id)    // Historial de estados
get_statistics($from, $to) // Estadísticas de ventas
```

### 3. API REST (`class-restaurant-api.php`)

#### Endpoints de Menú

```http
GET /wp-json/restaurant/v1/menu
```
Obtener menú completo con todas las categorías e items

```http
GET /wp-json/restaurant/v1/menu/{category}
```
Obtener items de una categoría específica (dishes, drinks, desserts)

#### Endpoints de Mesas

```http
GET /wp-json/restaurant/v1/tables
```
Listar todas las mesas (con filtros opcionales)

```http
GET /wp-json/restaurant/v1/tables/{id}
```
Obtener detalles de una mesa específica

```http
POST /wp-json/restaurant/v1/tables
```
Crear nueva mesa (requiere permisos de admin)

```http
PUT /wp-json/restaurant/v1/tables/{id}
```
Actualizar mesa (requiere permisos de admin)

```http
DELETE /wp-json/restaurant/v1/tables/{id}
```
Eliminar mesa (requiere permisos de admin)

```http
GET /wp-json/restaurant/v1/tables/statistics
```
Estadísticas de mesas (requiere permisos de admin)

#### Endpoints de Pedidos

```http
GET /wp-json/restaurant/v1/orders
```
Listar pedidos (con filtros: status, table_id, date_from, date_to)

```http
POST /wp-json/restaurant/v1/orders
```
Crear nuevo pedido

Ejemplo de body:
```json
{
  "table_id": 5,
  "customer_name": "Juan",
  "customer_phone": "612345678",
  "items": [
    {"post_id": 123, "quantity": 2, "notes": "Sin cebolla"},
    {"post_id": 124, "quantity": 1}
  ]
}
```

```http
GET /wp-json/restaurant/v1/orders/{id}
```
Obtener detalles de un pedido

```http
GET /wp-json/restaurant/v1/orders/number/{order_number}
```
Obtener pedido por número (ej: MESA-260204-0001)

```http
PUT /wp-json/restaurant/v1/orders/{id}/status
```
Actualizar estado del pedido (requiere permisos de admin)

```http
GET /wp-json/restaurant/v1/orders/{id}/history
```
Obtener historial de estados del pedido

#### Endpoints de Estadísticas

```http
GET /wp-json/restaurant/v1/statistics
```
Estadísticas generales (requiere permisos de admin)

```http
GET /wp-json/restaurant/v1/order-statuses
```
Obtener lista de estados disponibles

### 4. Panel de Administración

#### Página de Configuración (`class-restaurant-settings.php`)

**Ubicación:** `Flavor Platform > Restaurante`

**Funcionalidades:**
- Selector visual de CPTs para cada categoría (Platos, Bebidas, Postres)
- Configuración de moneda y símbolo
- Configuración de tasa de impuesto
- Prefijo de pedidos
- Opciones de códigos QR y notificaciones
- Gestión de estados de pedido personalizados
- Vista de endpoints de API disponibles

**Interacción:**
- Drag & drop simulado con doble-click
- Guardado AJAX
- Validación en tiempo real
- Botón flotante de guardar

#### Página de Pedidos (`class-order-admin.php`)

**Ubicación:** `Flavor Platform > Pedidos`

**Funcionalidades:**
- Vista en tiempo real de todos los pedidos
- Tarjetas de estadísticas rápidas
- Filtros por estado, mesa y fecha
- Auto-refresh cada 30 segundos
- Modal de detalles de pedido
- Cambio de estados
- Historial completo

**Vista:**
```
┌─────────────────────────────────────────┐
│  Estadísticas                           │
│  [Pendientes: 3] [Preparando: 5]        │
│  [Listos: 2] [Ingresos: €450.50]        │
├─────────────────────────────────────────┤
│  Filtros: [Estado] [Mesa] [Fecha]      │
├─────────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐  ┌─────────┐│
│  │ Pedido  │  │ Pedido  │  │ Pedido  ││
│  │ #001    │  │ #002    │  │ #003    ││
│  │ Mesa 5  │  │ Mesa 3  │  │ Mesa 7  ││
│  │ €45.50  │  │ €32.00  │  │ €67.80  ││
│  └─────────┘  └─────────┘  └─────────┘│
└─────────────────────────────────────────┘
```

#### Página de Mesas (`class-order-admin.php`)

**Ubicación:** `Flavor Platform > Mesas`

**Funcionalidades:**
- Lista visual de todas las mesas
- Tarjetas de estadísticas
- Creación rápida de mesas
- Edición inline
- Eliminación con validación
- Estados visuales con colores

**Vista:**
```
┌─────────────────────────────────────────┐
│  Estadísticas                           │
│  [Disponibles: 8] [Ocupadas: 4]         │
│  [Reservadas: 2] [Total: 15]            │
├─────────────────────────────────────────┤
│  [+ Agregar Mesa] [Actualizar]          │
├─────────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐  ┌─────────┐│
│  │ Mesa 01 │  │ Mesa 02 │  │ Mesa 03 ││
│  │ 🟢 Disp │  │ 🔴 Ocup │  │ 🟢 Disp ││
│  │ 4 pers. │  │ 6 pers. │  │ 2 pers. ││
│  │[Edit][X]│  │[Edit][X]│  │[Edit][X]││
│  └─────────┘  └─────────┘  └─────────┘│
└─────────────────────────────────────────┘
```

### 5. Assets (CSS y JavaScript)

#### CSS
- **restaurant-settings.css**: Estilos para la configuración
  - Cards visuales
  - Selector de CPTs
  - Tablas de estados
  - Botón flotante

- **restaurant-admin.css**: Estilos para administración
  - Grids responsivos
  - Tarjetas de pedidos/mesas
  - Estados con colores
  - Modales
  - Estadísticas

#### JavaScript
- **restaurant-settings.js**: Lógica de configuración
  - Gestión de selección de CPTs
  - AJAX para guardar
  - Gestión de estados
  - Validaciones

- **restaurant-admin.js**: Lógica de administración
  - Carga de pedidos/mesas
  - Filtros dinámicos
  - Auto-refresh
  - Modales
  - Gestión de estados

---

## 🚀 Cómo Probar el Sistema

### 1. Activar el Plugin

1. En WordPress, ve a **Plugins > Plugins instalados**
2. Encuentra "Flavor Restaurant Ordering"
3. Click en **Activar**

El sistema creará automáticamente:
- 4 tablas en la base de datos
- Configuración inicial
- Registrará los endpoints de la API

### 2. Configurar el Menú

1. Ve a **Flavor Platform > Restaurante**
2. Asigna tus CPTs a las categorías:
   - **Platos**: Selecciona los CPTs de platos principales
   - **Bebidas**: Selecciona los CPTs de bebidas
   - **Postres**: Selecciona los CPTs de postres
3. Configura:
   - Prefijo de mesas: `MESA`
   - Moneda: `EUR`
   - Símbolo: `€`
   - Impuesto: `10`
4. Click en **Guardar Cambios**

### 3. Crear Mesas de Prueba

1. Ve a **Flavor Platform > Mesas**
2. Click en **Agregar Mesa**
3. Crea varias mesas:
   - Mesa 01, capacidad 4, ubicación "Salón"
   - Mesa 02, capacidad 2, ubicación "Terraza"
   - Mesa 03, capacidad 6, ubicación "Salón"
4. Guarda cada una

### 4. Probar la API

#### A. Ver el Menú

Abre en tu navegador:
```
http://tusitio.local/wp-json/restaurant/v1/menu
```

Deberías ver JSON con tu menú completo.

#### B. Ver las Mesas

```
http://tusitio.local/wp-json/restaurant/v1/tables
```

Deberías ver las mesas que creaste.

#### C. Crear un Pedido de Prueba

Usando Postman, cURL o similar:

```bash
curl -X POST http://tusitio.local/wp-json/restaurant/v1/orders \
  -H "Content-Type: application/json" \
  -d '{
    "table_id": 1,
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
  }'
```

Reemplaza `123` y `124` con IDs reales de tus posts/productos.

### 5. Gestionar Pedidos

1. Ve a **Flavor Platform > Pedidos**
2. Verás el pedido que acabas de crear
3. Click en la tarjeta del pedido para ver detalles
4. Click en **Cambiar Estado**
5. Prueba cambiarlo a `preparing`, luego `ready`, etc.

### 6. Ver Estadísticas

1. En la misma página de pedidos
2. Las tarjetas superiores muestran estadísticas en tiempo real
3. Click en **Estadísticas** para ver más detalles

---

## 🔌 Integración con Apps Móviles

### Para App de Cliente

**Flujo recomendado:**

```
1. Abrir app
   └─> GET /restaurant/v1/menu
       └─> Mostrar menú por categorías

2. Usuario selecciona mesa
   └─> Escanear QR o seleccionar de lista
   └─> GET /restaurant/v1/tables

3. Usuario agrega items al carrito
   └─> Almacenar en estado local

4. Usuario confirma pedido
   └─> POST /restaurant/v1/orders
   └─> Guardar order_number

5. Usuario sigue su pedido
   └─> GET /restaurant/v1/orders/number/{order_number}
   └─> Mostrar estado actual
```

### Para App de Admin

**Flujo recomendado:**

```
1. Abrir app de admin
   └─> Autenticarse con WordPress

2. Ver pedidos activos
   └─> GET /restaurant/v1/orders?status=pending,preparing,ready
   └─> Mostrar lista

3. Ver detalles de pedido
   └─> Click en pedido
   └─> GET /restaurant/v1/orders/{id}

4. Cambiar estado
   └─> PUT /restaurant/v1/orders/{id}/status
   └─> Body: {"status": "ready"}

5. Auto-refresh cada 30s
   └─> Volver a GET /restaurant/v1/orders
```

---

## 📊 Datos de Ejemplo

### Estructura de un Item del Menú

```json
{
  "id": 123,
  "name": "Hamburguesa Clásica",
  "description": "Hamburguesa de ternera con queso cheddar, lechuga, tomate y cebolla en pan brioche",
  "post_type": "product",
  "category": "dishes",
  "price": 12.50,
  "image": {
    "url": "https://tusitio.com/wp-content/uploads/2024/hamburguesa.jpg",
    "thumbnail": "...",
    "medium": "...",
    "alt": "Hamburguesa Clásica"
  },
  "available": true,
  "allergens": ["gluten", "lactosa"],
  "customizations": []
}
```

### Estructura de un Pedido

```json
{
  "id": 42,
  "order_number": "MESA-260204-0001",
  "table_id": 5,
  "table": {
    "id": 5,
    "table_number": "05",
    "table_name": "Mesa 05",
    "status": "occupied"
  },
  "customer": {
    "name": "Juan Pérez",
    "phone": "612345678",
    "email": "juan@example.com"
  },
  "status": "preparing",
  "status_label": "Preparando",
  "subtotal": 45.00,
  "tax": 4.50,
  "total": 49.50,
  "total_formatted": "€ 49,50",
  "items": [
    {
      "id": 1,
      "post_id": 123,
      "name": "Hamburguesa Clásica",
      "category": "dishes",
      "quantity": 2,
      "unit_price": 12.50,
      "subtotal": 25.00,
      "notes": "Sin cebolla"
    },
    {
      "id": 2,
      "post_id": 124,
      "name": "Coca Cola",
      "category": "drinks",
      "quantity": 2,
      "unit_price": 2.50,
      "subtotal": 5.00,
      "notes": ""
    }
  ],
  "notes": "Alérgico a frutos secos",
  "created_at": "2024-02-04 14:30:00",
  "updated_at": "2024-02-04 14:35:00"
}
```

---

## ✅ Checklist de Verificación

Después de instalar y configurar, verifica:

- [ ] Plugin activado correctamente
- [ ] Tablas de base de datos creadas (verifica en phpMyAdmin)
- [ ] Configuración guardada en `wp_options` tabla
- [ ] CPTs asignados a categorías del menú
- [ ] Al menos 3 mesas creadas
- [ ] Endpoint `/restaurant/v1/menu` funciona
- [ ] Endpoint `/restaurant/v1/tables` funciona
- [ ] Endpoint `/restaurant/v1/orders` funciona
- [ ] Se puede crear un pedido de prueba
- [ ] Se pueden cambiar estados de pedidos
- [ ] Auto-refresh funciona en la página de pedidos
- [ ] Estadísticas se actualizan correctamente

---

## 🎓 Próximos Pasos

### Para Desarrollo

1. **Códigos QR Reales**
   - Integrar librería de generación de QR
   - Permitir descargar/imprimir QRs de mesas

2. **Notificaciones Push**
   - Integrar con Firebase/OneSignal
   - Notificar a clientes sobre cambios de estado
   - Notificar a admins sobre nuevos pedidos

3. **Pasarela de Pago**
   - Integrar Stripe/PayPal
   - Permitir pago desde la app
   - Propinas opcionales

4. **Reservas**
   - Sistema completo de reservas online
   - Calendar picker
   - Confirmaciones automáticas

5. **Multi-ubicación**
   - Soporte para múltiples restaurantes/sucursales
   - Menús diferentes por ubicación
   - Estadísticas por sucursal

### Para Testing

1. **Tests Unitarios**
   - PHPUnit para managers
   - Tests de API endpoints
   - Tests de cálculos

2. **Tests de Integración**
   - Flujo completo de pedido
   - Cambios de estado
   - Estadísticas

3. **Tests de Carga**
   - Muchos pedidos simultáneos
   - Muchas mesas
   - Gran volumen de items

---

## 📞 Soporte

¿Problemas o dudas?

- 📖 Lee el **README.md** para documentación técnica
- 👤 Lee la **GUIA-USUARIO.md** para uso diario
- 🐛 Reporta bugs en GitHub Issues
- 📧 Contacto: support@flavor-platform.com

---

**¡Sistema completo y listo para usar!** 🎉

Desarrollado con ❤️ por Flavor Platform Team
