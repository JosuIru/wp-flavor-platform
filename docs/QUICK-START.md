# Flavor Platform - Guía de Inicio Rápido

## Para Empezar en 5 Minutos

### Paso 1: Activar el Plugin
1. Ve a **Plugins → Plugins Instalados**
2. Busca "Flavor Platform"
3. Haz clic en **Activar**

### Paso 2: Seleccionar tu Perfil
1. Ve a **Flavor Platform → Perfiles**
2. Elige el tipo de organización:
   - **Grupo de Consumo** - Pedidos colectivos, productores locales
   - **Comunidad/Asociación** - Socios, eventos, talleres
   - **Banco de Tiempo** - Intercambio de servicios
   - **Barrio** - Ayuda vecinal, recursos compartidos
   - **Tienda Online** - Productos, carrito, pagos
   - ... y más
3. Haz clic en **Activar Perfil**

### Paso 3: Las Páginas se Crean Automáticamente

Según tu perfil, se crearán páginas como:
- `/mi-cuenta/` - Panel del usuario
- `/grupos-consumo/` - Listado de grupos (si es grupo de consumo)
- `/eventos/` - Calendario de eventos
- `/socios/unirme/` - Formulario de alta

### Paso 4: Personalizar el Diseño
1. Ve a **Flavor Platform → Diseño**
2. Elige un tema que te guste
3. O personaliza colores, fuentes y espaciados

### Paso 5: Crear tu Landing Page
1. Ve a **Páginas → Añadir Nueva**
2. Añade el shortcode: `[flavor_landing tipo="tu-perfil"]`
3. Publica la página

---

## Estructura de tu Sitio

```
TU SITIO WEB
│
├── Inicio (Landing Page)
│   └── [flavor_landing tipo="grupo_consumo"]
│
├── /mi-cuenta/                    ← Panel del usuario
│   ├── Mis Pedidos
│   ├── Mi Perfil
│   └── Notificaciones
│
├── /grupos-consumo/               ← Según perfil activo
│   ├── /productos/
│   ├── /unirme/
│   └── /mi-pedido/
│
├── /eventos/
├── /socios/
└── Otras páginas del perfil...
```

---

## Módulos Disponibles

Los módulos son funcionalidades que puedes activar/desactivar.

### Cómo Activar Módulos
1. Ve a **Flavor Platform → Módulos**
2. Busca el módulo deseado
3. Haz clic en **Activar**

### Módulos Populares

| Módulo | Qué Hace |
|--------|----------|
| **Socios** | Gestión de membresías, cuotas, carnet |
| **Eventos** | Calendario, inscripciones, asistencia |
| **Talleres** | Cursos, formaciones, inscripciones |
| **Marketplace** | Anuncios de compra/venta/regalo |
| **Banco de Tiempo** | Intercambio de servicios por horas |
| **Reservas** | Reserva de espacios, citas |
| **Grupos Consumo** | Pedidos colectivos, productores |

---

## Cómo Añadir Contenido

### Crear un Evento
1. Ve a **Eventos → Añadir Nuevo**
2. Rellena título, fecha, lugar
3. Publica

### Crear un Producto (Grupo Consumo)
1. Ve a **Productos GC → Añadir Nuevo**
2. Rellena nombre, precio, productor
3. Publica

### Crear un Taller
1. Ve a **Talleres → Añadir Nuevo**
2. Rellena título, fecha, plazas, precio
3. Publica

---

## Unirse a la Red de Nodos

Conecta tu sitio con otros sitios Flavor Platform para compartir recursos.

### Activar
1. Ve a **Flavor Platform → Red de Nodos**
2. Completa los datos de tu organización
3. Activa la federación

### Qué Puedes Compartir
- Productores y productos (grupos de consumo)
- Servicios (banco de tiempo)
- Eventos públicos
- Anuncios de marketplace

---

## Apps Móviles

### Para Usuarios
1. Descarga la app "Flavor App" (Android/iOS)
2. Abre la app y escanea el QR de tu sitio
3. Ya tienes acceso a tu perfil desde el móvil

### Para Administradores
1. Descarga "Flavor Admin"
2. Escanea el QR desde el panel de WordPress
3. Gestiona pedidos, eventos, socios desde el móvil

---

## Preguntas Frecuentes

### ¿Cómo cambio los colores de mi sitio?
**Flavor Platform → Diseño** → Selecciona un tema o personaliza colores

### ¿Cómo añado una nueva página?
**Páginas → Añadir Nueva** → Usa shortcodes como `[flavor_module_listing module="eventos"]`

### ¿Cómo veo los usuarios registrados?
**Usuarios → Todos los usuarios** o en cada módulo (Socios, etc.)

### ¿Cómo configuro notificaciones?
**Flavor Platform → Notificaciones** → Configura email, push, WhatsApp

### ¿Puedo tener varios perfiles activos?
Sí, puedes combinar módulos de diferentes perfiles

---

## Soporte

- **Documentación completa:** Admin → Flavor Platform → Documentación
- **Tours guiados:** Admin → Flavor Platform → Tours
- **Contacto:** [tu-email-soporte]
