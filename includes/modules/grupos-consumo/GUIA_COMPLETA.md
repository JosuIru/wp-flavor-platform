# Guía Completa - Módulo Grupos de Consumo

## 📋 Índice

1. [Introducción](#introducción)
2. [Instalación y Activación](#instalación-y-activación)
3. [Configuración Inicial](#configuración-inicial)
4. [Uso desde el Administrador](#uso-desde-el-administrador)
5. [Uso desde el Frontend](#uso-desde-el-frontend)
6. [Integración con Chat IA](#integración-con-chat-ia)
7. [API REST para Apps Móviles](#api-rest-para-apps-móviles)
8. [Shortcodes Disponibles](#shortcodes-disponibles)

---

## Introducción

El módulo **Grupos de Consumo** permite gestionar pedidos colectivos a productores locales. Es ideal para:

- 🌱 Cooperativas de consumo
- 🥬 Grupos de compra ecológica
- 🤝 Redes de economía social
- 📦 Pedidos colectivos organizados

### Características Principales

- **Custom Post Types**: Productores, Productos, Ciclos de Pedido
- **Gestión de Ciclos**: Apertura/cierre automático de pedidos
- **Pedidos Individuales**: Cada usuario puede hacer su pedido
- **Integración IA**: Claude puede ayudar a hacer pedidos por chat
- **API REST**: Para integración con apps móviles (Flutter)
- **Frontend Completo**: Interfaz lista para usar con shortcodes

---

## Instalación y Activación

### Requisitos

- WordPress 5.8+
- PHP 7.4+
- Plugin Flavor Chat IA instalado

### Activación

1. El módulo se activa automáticamente con Flavor Chat IA
2. Al activarse, crea:
   - Custom Post Types (Productores, Productos, Ciclos)
   - Tabla de base de datos `wp_flavor_gc_pedidos`
   - Taxonomías (Categorías de productos)
   - Estados personalizados para ciclos

---

## Configuración Inicial

### Paso 1: Crear Productores

1. Ir a **Productores > Añadir Productor**
2. Rellenar:
   - **Título**: Nombre del productor
   - **Descripción**: Información sobre el productor
   - **Información del Productor**:
     - Nombre de contacto
     - Teléfono
     - Email
     - Ubicación
     - ✅ Certificación ecológica (opcional)
     - Número de certificado
     - Métodos de producción
3. Añadir **imagen destacada** (foto del productor/granja)
4. **Publicar**

### Paso 2: Crear Productos

1. Ir a **Productores > Productos > Añadir Producto**
2. Rellenar:
   - **Título**: Nombre del producto (ej: "Tomates Ecológicos")
   - **Descripción**: Detalles del producto
   - **Información del Producto**:
     - Productor (seleccionar de la lista)
     - Precio (€)
     - Unidad (kg, g, L, unidad, caja, ramo)
     - Cantidad mínima
     - Stock disponible (vacío = ilimitado)
     - Temporada
     - Origen
3. Asignar **Categoría** (Frutas, Verduras, Lácteos, etc.)
4. Añadir **imagen** del producto
5. **Publicar**

### Paso 3: Crear Ciclo de Pedido

1. Ir a **Productores > Ciclos de Pedido > Crear Ciclo**
2. Rellenar:
   - **Título**: "Ciclo del 1-7 Febrero 2026"
   - **Fecha Apertura**: Cuándo se abre el ciclo
   - **Fecha Cierre**: Cuándo se cierra (se cierra automáticamente)
   - **Fecha Entrega**: Día de reparto
   - **Hora Entrega**: Hora de reparto
   - **Lugar de Entrega**: Dirección del punto de recogida
   - **Notas**: Instrucciones adicionales
3. Seleccionar estado: **Abierto**
4. **Publicar**

---

## Uso desde el Administrador

### Ver Pedidos de un Ciclo

1. Ir a **Productores > Ciclos de Pedido**
2. Clic en un ciclo
3. En el panel lateral verás:
   - **Total Pedidos**: Número de personas
   - **Importe Total**: Suma de todos los pedidos
   - **Productos Más Pedidos**: Top 5

### Columnas Personalizadas

- **Ciclos**: Fechas, Estado (🟢 Abierto / 🟠 Cerrado / 🔵 Entregado), Total Pedidos
- **Productos**: Productor, Precio, Stock

### Cierre Automático

El sistema comprueba cada hora si algún ciclo debe cerrarse:
- Si `fecha_cierre` <= `ahora` → Cambia a estado "Cerrado"
- Se ejecuta mediante WP Cron

---

## Uso desde el Frontend

### Mostrar Ciclo Actual

```
[gc_ciclo_actual]
```

Muestra:
- Nombre del ciclo
- Fecha de cierre
- Fecha de entrega
- Lugar de recogida
- Tiempo restante

### Listar Productos

```
[gc_productos]
```

Parámetros opcionales:
- `categoria="frutas"` - Filtrar por categoría
- `limite="12"` - Número de productos

Ejemplo:
```
[gc_productos categoria="verduras" limite="20"]
```

### Mostrar Mi Pedido

```
[gc_mi_pedido]
```

Muestra el pedido del usuario en el ciclo actual.

---

## Integración con Chat IA

Los usuarios pueden interactuar con el módulo mediante Claude:

### Acciones Disponibles

#### 1. Listar Productos

**Usuario**: "¿Qué productos hay disponibles?"

**Claude** ejecuta: `gc_listar_productos`

Respuesta: Lista de productos con precios, productores, etc.

#### 2. Ver Ciclo Actual

**Usuario**: "¿Cuándo cierra el pedido?"

**Claude** ejecuta: `gc_ciclo_actual`

Respuesta: Info del ciclo, tiempo restante.

#### 3. Hacer Pedido

**Usuario**: "Quiero 2kg de tomates y 1 caja de verduras"

**Claude** ejecuta: `hacer_pedido` con productos

Respuesta: Confirmación y total del pedido.

#### 4. Ver Mi Pedido

**Usuario**: "¿Qué tengo en mi pedido?"

**Claude** ejecuta: `ver_mi_pedido`

Respuesta: Productos, cantidades, total.

#### 5. Modificar Pedido

**Usuario**: "Cambia mi pedido a 3kg de tomates"

**Claude** ejecuta: `modificar_pedido`

Respuesta: Pedido actualizado (si está dentro del plazo).

#### 6. Buscar Productores

**Usuario**: "¿Qué productores ecológicos hay?"

**Claude** ejecuta: `buscar_productor` con `certificacion_eco=true`

Respuesta: Lista de productores certificados.

### Base de Conocimiento

Claude tiene acceso a información sobre:
- Qué es un grupo de consumo
- Cómo funcionan los ciclos
- Ventajas de compra colectiva
- Categorías de productos
- FAQs

---

## API REST para Apps Móviles

Ver documentación completa en: [API_MOBILE.md](./API_MOBILE.md)

### Base URL

```
https://tu-dominio.com/wp-json/flavor-chat-ia/v1
```

### Endpoints Principales

#### GET /pedidos
Lista pedidos colectivos (compatible con el antiguo sistema)

#### GET /pedidos/{id}
Detalles de un pedido específico

#### POST /pedidos/{id}/unirse
Unirse a un pedido colectivo

#### GET /mis-pedidos
Pedidos del usuario autenticado

#### POST /pedidos/{id}/marcar-pagado
Marcar pedido como pagado

#### POST /pedidos/{id}/marcar-recogido
Marcar pedido como recogido

### Autenticación

Requiere JWT o cookies de WordPress:

```http
Authorization: Bearer {token}
```

### Ejemplo Flutter

```dart
import 'package:http/http.dart' as http;

final response = await http.get(
  Uri.parse('$baseUrl/pedidos?estado=abierto'),
);
```

---

## Shortcodes Disponibles

### `[gc_ciclo_actual]`

Muestra información del ciclo actual de pedidos.

**Uso**:
```
[gc_ciclo_actual]
```

**Salida**:
- Nombre del ciclo
- Fecha de cierre
- Fecha de entrega
- Lugar de recogida
- Tiempo restante

---

### `[gc_productos]`

Lista productos disponibles con filtros.

**Parámetros**:
- `categoria` (opcional): frutas, verduras, lacteos, carne, pescado, pan, conservas, bebidas, otros
- `limite` (opcional): Número de productos (default: 12)

**Ejemplos**:
```
[gc_productos]
[gc_productos categoria="frutas" limite="20"]
[gc_productos categoria="verduras"]
```

**Salida**:
- Grid de tarjetas con:
  - Imagen del producto
  - Nombre
  - Productor
  - Precio/unidad
  - Botón "Añadir al pedido"

---

### `[gc_mi_pedido]`

Muestra el pedido del usuario en el ciclo actual.

**Uso**:
```
[gc_mi_pedido]
```

**Requisitos**:
- Usuario debe estar autenticado
- Debe tener un pedido en el ciclo actual

**Salida**:
- Lista de productos pedidos
- Cantidades
- Precios
- Total

---

## CSS Personalizable

El módulo incluye estilos completos en `assets/grupos-consumo.css`:

- `.gc-ciclo-actual` - Información del ciclo
- `.gc-productos-grid` - Grid de productos
- `.gc-producto-card` - Tarjeta individual
- `.gc-mi-pedido` - Resumen del pedido
- `.gc-anadir-pedido` - Botón añadir

Puedes sobrescribir estos estilos en el tema hijo.

---

## JavaScript Interactivo

El módulo incluye `assets/grupos-consumo.js` con:

- Modal para hacer pedidos
- Validación de formularios
- AJAX para pedidos
- Confirmaciones y mensajes de éxito/error

---

## Configuración Avanzada

### Ajustes del Módulo

Accesibles mediante `get_setting()`:

```php
$dias_anticipacion = $this->get_setting('dias_anticipacion_pedido', 7);
$hora_cierre = $this->get_setting('hora_cierre_pedidos', '23:59');
$permitir_modificar = $this->get_setting('permitir_modificar_pedido', true);
$horas_limite = $this->get_setting('horas_limite_modificacion', 24);
$porcentaje_gestion = $this->get_setting('porcentaje_gestion', 5);
```

### Hooks Disponibles

#### Acciones

```php
// Cuando un ciclo se cierra automáticamente
do_action('gc_ciclo_cerrado', $ciclo_id);
```

#### Filtros

(Por implementar según necesidades)

---

## Solución de Problemas

### Los productos no aparecen

✅ Verificar que:
- Los productos están publicados
- Tienen un productor asignado
- Tienen precio configurado

### No se puede hacer pedidos

✅ Verificar que:
- Hay un ciclo con estado "Abierto"
- El ciclo no ha llegado a la fecha de cierre
- El usuario está autenticado

### La tabla de pedidos no existe

✅ Ejecutar:
```php
// El módulo crea la tabla automáticamente al inicializar
// Si falla, desactivar y reactivar el plugin
```

### Los ciclos no se cierran automáticamente

✅ Verificar que:
- WP Cron está funcionando
- No hay errores en el registro de WordPress

---

## Soporte y Desarrollo

Para reportar problemas o solicitar funcionalidades:

1. Revisar la documentación completa
2. Verificar los logs de WordPress
3. Contactar al equipo de desarrollo

---

## Changelog

### v1.0.0 (2026-01-27)
- ✅ Versión inicial completa
- ✅ Custom Post Types (Productores, Productos, Ciclos)
- ✅ Sistema de pedidos
- ✅ Integración con Chat IA (Claude)
- ✅ API REST para móviles
- ✅ Shortcodes frontend
- ✅ Cierre automático de ciclos
- ✅ WPML ready (multiidioma)

---

## Próximas Funcionalidades

- [ ] Panel de gestión de pedidos mejorado
- [ ] Exportación de pedidos a CSV/PDF
- [ ] Notificaciones por email
- [ ] Recordatorios de cierre de ciclo
- [ ] Historial de pedidos por usuario
- [ ] Estadísticas de productos más vendidos
- [ ] Integración con pasarelas de pago
- [ ] App móvil Flutter completa
