# Guía de Usuario - Flavor Restaurant Ordering

Bienvenido al sistema de gestión de pedidos para restaurantes de Flavor Platform.

## 📖 Índice

1. [Primeros Pasos](#primeros-pasos)
2. [Configurar tu Menú](#configurar-tu-menú)
3. [Gestionar Mesas](#gestionar-mesas)
4. [Recibir y Gestionar Pedidos](#recibir-y-gestionar-pedidos)
5. [Casos de Uso Comunes](#casos-de-uso-comunes)
6. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## Primeros Pasos

### Activación del Plugin

1. Ve a **Plugins > Plugins instalados** en WordPress
2. Busca "Flavor Restaurant Ordering"
3. Click en **Activar**

Al activar, el sistema creará automáticamente:
- Tablas en la base de datos
- Configuración inicial
- Endpoints de la API

### Acceder al Panel

Encontrarás nuevas opciones en el menú **Flavor Platform**:
- **Restaurante** (Configuración general)
- **Pedidos** (Gestión de pedidos)
- **Mesas** (Gestión de mesas)

---

## Configurar tu Menú

### ¿Qué es un CPT?

CPT significa "Custom Post Type" (Tipo de Contenido Personalizado). En WordPress, puedes crear diferentes tipos de contenido además de posts y páginas.

**Ejemplos:**
- Si usas WooCommerce, los productos son un CPT llamado `product`
- Puedes crear CPTs personalizados para "Platos", "Bebidas", "Postres"

### Vincular CPTs a tu Menú

1. Ve a **Flavor Platform > Restaurante**
2. Verás 3 categorías: **Platos**, **Bebidas**, **Postres**
3. En cada categoría:
   - **Selecciona** los CPTs que quieres incluir
   - Haz **doble click** para agregarlos
   - Click en la **X** para eliminarlos

**Ejemplo de configuración:**

```
📌 Platos:
   - product (Productos de WooCommerce)
   - plato_principal (CPT personalizado)

🥤 Bebidas:
   - bebida (CPT personalizado)

🍰 Postres:
   - postre (CPT personalizado)
```

### Campos Importantes en tus Items

Para que los items se muestren correctamente, asegúrate de que tengan:

**Obligatorio:**
- ✅ Título
- ✅ Precio (meta field `_price` o `price`)

**Opcional pero recomendado:**
- 🖼️ Imagen destacada
- 📝 Descripción
- 🏷️ Categorías/taxonomías
- ⚠️ Alérgenos (meta field `_allergens`)

### Configuración General

En la misma página de configuración:

**Prefijo de Mesas:**
```
MESA → Los pedidos se numerarán: MESA-260204-0001
```

**Moneda:**
```
EUR (€) → Todos los precios se mostrarán en euros
```

**Impuesto (IVA):**
```
10% → Se aplicará a todos los pedidos
```

**Opciones:**
- ☑ Habilitar códigos QR para mesas
- ☑ Habilitar notificaciones de pedidos

---

## Gestionar Mesas

### Crear una Mesa

1. Ve a **Flavor Platform > Mesas**
2. Click en **Agregar Mesa**
3. Completa el formulario:

**Número de Mesa** (obligatorio)
```
Ejemplo: 01, A1, T-05
```

**Nombre** (opcional)
```
Ejemplo: "Mesa Terraza 1", "Mesa VIP"
```

**Capacidad**
```
Número de comensales (ej: 4, 6, 8)
```

**Ubicación**
```
Ejemplo: "Terraza", "Salón Principal", "Zona VIP"
```

**Notas**
```
Cualquier información adicional
```

4. Click en **Guardar Mesa**

### Estados de las Mesas

Las mesas pueden tener estos estados:

- 🟢 **Disponible**: Lista para nuevos clientes
- 🔴 **Ocupada**: Tiene clientes actualmente
- 🔵 **Reservada**: Reservada para más tarde
- 🟡 **En limpieza**: Se está preparando
- ⚫ **Deshabilitada**: No disponible temporalmente

### Editar o Eliminar Mesa

1. En la lista de mesas, busca la mesa que quieres modificar
2. Click en **Editar** para cambiar datos
3. Click en **Eliminar** para borrarla (solo si no tiene pedidos activos)

### Códigos QR

Si tienes habilitados los códigos QR:
- Cada mesa tiene un código QR único
- Los clientes escanean el QR con su móvil
- Se abre la app directamente en esa mesa
- Pueden hacer su pedido sin llamar al camarero

---

## Recibir y Gestionar Pedidos

### Panel de Pedidos

Ve a **Flavor Platform > Pedidos**

Verás:

**Tarjetas de Estadísticas:**
- ⏰ Pedidos pendientes
- 🍳 En preparación
- ✅ Listos para servir
- 💰 Ingresos del día

**Filtros:**
- Por estado
- Por mesa
- Por fecha

**Lista de Pedidos:**
Cada tarjeta muestra:
- Número de pedido
- Estado actual
- Mesa
- Cliente
- Items
- Total
- Tiempo transcurrido

### Flujo de un Pedido

```
1. 📱 Cliente hace pedido desde app
      ↓
2. ⏰ Aparece como "Pendiente" en tu panel
      ↓
3. 🍳 Cambias a "Preparando"
      ↓
4. ✅ Cambias a "Listo"
      ↓
5. 🍽️ Cambias a "Servido"
      ↓
6. 💚 Cambias a "Completado"
```

### Cambiar Estado de un Pedido

1. Click en la tarjeta del pedido
2. Se abre el modal con todos los detalles
3. Click en **Cambiar Estado**
4. Introduce el nuevo estado:
   - `pending` → Pendiente
   - `preparing` → Preparando
   - `ready` → Listo
   - `served` → Servido
   - `completed` → Completado
   - `cancelled` → Cancelado
5. Opcionalmente, añade notas
6. Confirmar

### Detalles del Pedido

Al abrir un pedido ves:

**Información del Cliente:**
- Nombre
- Teléfono
- Email
- Mesa

**Items del Pedido:**
- Lista completa de productos
- Cantidad de cada uno
- Precios
- Notas especiales ("sin cebolla", etc.)

**Totales:**
- Subtotal
- IVA
- Total a pagar

### Auto-Actualización

- El panel se refresca cada 30 segundos
- No necesitas recargar manualmente
- Siempre ves los pedidos más recientes

---

## Casos de Uso Comunes

### Caso 1: Restaurante Pequeño

**Configuración:**
- 10 mesas
- Menú dividido en: Entrantes, Principales, Bebidas, Postres
- Sin código QR (los clientes piden a través del personal)

**Flujo:**
1. El camarero toma el pedido
2. Introduce el pedido en la app desde su móvil
3. El chef ve el pedido en la tablet de cocina
4. Marca como "Preparando" → "Listo"
5. El camarero recibe notificación y sirve
6. Marca como "Completado"

### Caso 2: Cafetería Self-Service

**Configuración:**
- Códigos QR en las mesas
- Menú: Cafés, Tés, Bollería, Bocadillos
- Pago en caja

**Flujo:**
1. Cliente escanea QR de su mesa
2. Hace pedido desde su móvil
3. Va a caja a pagar
4. El personal prepara y sirve
5. Sistema marca automáticamente como completado

### Caso 3: Restaurante con Delivery

**Configuración:**
- Mesas + pedidos para llevar
- Integración con sistema de delivery
- Múltiples estados personalizados

**Flujo:**
1. Cliente hace pedido desde app
2. Selecciona "Para llevar" (sin mesa)
3. Cocina prepara
4. Notifica cuando está listo
5. Cliente recoge o se envía por delivery

---

## Preguntas Frecuentes

### ¿Necesito WooCommerce?

No es obligatorio. Puedes:
- Usar WooCommerce si lo tienes
- Crear tus propios CPTs para platos
- Usar cualquier plugin de CPTs

### ¿Los clientes pueden pagar desde la app?

En la versión actual, la app sirve para hacer pedidos, no para pagar.

Puedes integrar pasarelas de pago en futuras versiones o usar:
- Pago en mesa
- TPV tradicional
- Pago en caja

### ¿Puedo tener diferentes precios según la hora?

Actualmente no está soportado nativamente, pero puedes:
1. Usar plugins de precios dinámicos de WooCommerce
2. Crear custom fields con precios por horario
3. Solicitar esta funcionalidad para futuras versiones

### ¿Se pueden hacer reservas de mesas?

El sistema incluye el estado "Reservada" para mesas, pero no tiene un sistema completo de reservas online.

Para reservas puedes:
- Marcar manualmente mesas como reservadas
- Usar un plugin de reservas externo
- Esperar a futuras versiones con esta funcionalidad

### ¿Funciona sin internet?

No. El sistema requiere conexión a internet para:
- Sincronizar pedidos
- Actualizar estados
- Mostrar el menú actualizado

### ¿Puedo dividir la cuenta?

En la versión actual no está soportado. Próximamente.

Alternativa:
- Crear pedidos separados por cliente
- Dividir manualmente el total

### ¿Cómo añado alérgenos a un plato?

En el editor del plato (post/producto):

1. Añade un campo personalizado:
   - Nombre: `_allergens`
   - Valor: `gluten, lactosa, frutos secos` (separados por comas)

2. O usa un plugin de campos personalizados como ACF

### ¿Los pedidos se imprimen automáticamente?

No hay integración con impresoras en esta versión.

Puedes:
- Ver pedidos en tablet/pantalla en la cocina
- Usar plugins de impresión externa
- Esperar futuras versiones con soporte de impresoras

### ¿Puedo exportar estadísticas?

Actualmente las estadísticas se muestran en el panel.

Para exportar:
- Usa el endpoint de API `/restaurant/v1/statistics`
- Copia datos manualmente
- Espera futuras versiones con exportación CSV/PDF

### ¿Se guardan los datos de clientes?

Sí, si el cliente proporciona:
- Nombre
- Teléfono
- Email

Estos datos quedan asociados al pedido para:
- Historial
- Contacto
- Fidelización (futuro)

**Importante:** Cumple con RGPD informando al cliente sobre el uso de sus datos.

### ¿Cómo personalizo los colores de las categorías?

En futuras versiones habrá un color picker.

Actualmente los colores están predefinidos en el código.

Para cambiarlos ahora:
1. Edita `includes/class-restaurant-manager.php`
2. Busca el método `get_category_color()`
3. Cambia los códigos hexadecimales

### ¿Puedo tener subcategorías en el menú?

No directamente, pero puedes:
- Usar las taxonomías de WordPress (categorías)
- Los items del menú incluyen sus categorías
- La app puede mostrarlas como filtros

---

## 💡 Consejos y Mejores Prácticas

### Para Menú

✅ Usa imágenes de buena calidad (mínimo 800x800px)
✅ Escribe descripciones atractivas pero concisas
✅ Indica claramente los alérgenos
✅ Mantén los precios actualizados
✅ Organiza bien las categorías

### Para Mesas

✅ Usa números claros y fáciles de leer
✅ Indica la ubicación para encontrarlas rápido
✅ Mantén actualizado el estado de cada mesa
✅ Revisa periódicamente mesas "ocupadas" antiguas

### Para Pedidos

✅ Cambia los estados rápidamente
✅ Revisa pedidos pendientes cada pocos minutos
✅ Usa las notas para comunicación interna
✅ Verifica que no queden pedidos sin completar

### Para Rendimiento

✅ No tengas cientos de mesas si no son necesarias
✅ Archiva pedidos antiguos periódicamente
✅ Optimiza las imágenes del menú
✅ Usa caché en tu servidor

---

## 🆘 ¿Necesitas Ayuda?

Si tienes problemas:

1. **Consulta el README.md** para problemas técnicos
2. **Revisa esta guía** para dudas de uso
3. **Contacta con soporte**:
   - Email: support@flavor-platform.com
   - GitHub: Issues en el repositorio

---

¡Que disfrutes usando Flavor Restaurant Ordering! 🚀🍽️
