# Módulo: Marketplace

> Mercadillo de compraventa entre particulares

## Descripción

Plataforma de clasificados para que los usuarios publiquen anuncios de venta, compra o intercambio de productos de segunda mano, servicios locales y otros.

## Archivos Principales

```
includes/modules/marketplace/
├── class-marketplace-module.php
├── class-marketplace-dashboard-tab.php
├── install.php
├── views/
│   ├── dashboard.php
│   └── moderacion.php
└── assets/
```

## Tablas de Base de Datos

### wp_flavor_marketplace_anuncios
Anuncios publicados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| titulo | varchar(255) | Título anuncio |
| slug | varchar(255) UNIQUE | URL amigable |
| descripcion | text | Descripción |
| tipo | enum | venta/compra/intercambio/regalo/servicio |
| categoria_id | bigint(20) | FK categoría |
| subcategoria | varchar(100) | Subcategoría |
| precio | decimal(10,2) | Precio |
| precio_negociable | tinyint(1) | Acepta ofertas |
| moneda | varchar(3) | EUR/USD/etc |
| es_gratuito | tinyint(1) | Es regalo |
| condicion | enum | nuevo/seminuevo/buen_estado/usado/para_piezas |
| imagen | varchar(500) | Imagen principal |
| galeria | longtext JSON | Más imágenes |
| usuario_id | bigint(20) | FK vendedor |
| usuario_nombre | varchar(100) | Nombre vendedor |
| usuario_email | varchar(100) | Email contacto |
| usuario_telefono | varchar(20) | Teléfono |
| mostrar_telefono | tinyint(1) | Mostrar teléfono |
| mostrar_email | tinyint(1) | Mostrar email |
| ubicacion_texto | varchar(255) | Ubicación |
| ubicacion_latitud | decimal(10,8) | Coordenada |
| ubicacion_longitud | decimal(11,8) | Coordenada |
| codigo_postal | varchar(10) | CP |
| barrio | varchar(100) | Barrio |
| envio_disponible | tinyint(1) | Hace envíos |
| coste_envio | decimal(10,2) | Coste envío |
| estado | enum | borrador/pendiente/publicado/vendido/reservado/expirado/rechazado |
| motivo_rechazo | text | Si rechazado |
| fecha_publicacion | datetime | Fecha publicación |
| fecha_expiracion | datetime | Fecha expiración |
| es_destacado | tinyint(1) | Anuncio premium |
| fecha_destacado | datetime | Hasta cuándo |
| visualizaciones | int | Vistas |
| favoritos_count | int | En favoritos |
| contactos_count | int | Mensajes recibidos |
| etiquetas | varchar(255) | Tags |
| comunidad_id | bigint(20) | FK comunidad |
| metadata | longtext JSON | Datos extra |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** slug, tipo, categoria_id, estado, usuario_id, fecha_publicacion

### wp_flavor_marketplace_categorias
Categorías de anuncios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(100) | Nombre |
| slug | varchar(100) UNIQUE | Identificador |
| descripcion | text | Descripción |
| icono | varchar(50) | Icono |
| color | varchar(7) | Color |
| imagen | varchar(500) | Imagen |
| padre_id | bigint(20) | Categoría padre |
| activa | tinyint(1) | Está activa |
| orden | int | Orden listado |
| anuncios_count | int | Nº anuncios |

### wp_flavor_marketplace_favoritos
Anuncios guardados por usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| anuncio_id | bigint(20) | FK anuncio |
| usuario_id | bigint(20) | FK usuario |
| created_at | datetime | Fecha |

**Unique:** anuncio_id + usuario_id

### wp_flavor_marketplace_mensajes
Mensajes entre usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| anuncio_id | bigint(20) | FK anuncio |
| conversacion_id | varchar(64) | ID conversación |
| remitente_id | bigint(20) | FK remitente |
| destinatario_id | bigint(20) | FK destinatario |
| mensaje | text | Contenido |
| adjuntos | longtext JSON | Archivos |
| leido | tinyint(1) | Fue leído |
| leido_at | datetime | Fecha lectura |
| created_at | datetime | Fecha envío |

**Índices:** anuncio_id, conversacion_id, remitente_id, destinatario_id

### wp_flavor_marketplace_valoraciones
Valoraciones de vendedores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| vendedor_id | bigint(20) | FK vendedor |
| comprador_id | bigint(20) | FK comprador |
| anuncio_id | bigint(20) | FK anuncio |
| puntuacion | tinyint | 1-5 estrellas |
| comentario | text | Comentario |
| respuesta | text | Respuesta vendedor |
| respuesta_at | datetime | Fecha respuesta |
| verificada | tinyint(1) | Compra verificada |
| created_at | datetime | Fecha |

**Índices:** vendedor_id, comprador_id

### wp_flavor_marketplace_reportes
Reportes de anuncios fraudulentos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| anuncio_id | bigint(20) | FK anuncio |
| usuario_id | bigint(20) | FK reportante |
| motivo | varchar(100) | Motivo |
| descripcion | text | Descripción |
| estado | enum | pendiente/revisando/resuelto/descartado |
| accion_tomada | text | Acción tomada |
| resuelto_por | bigint(20) | FK moderador |
| resuelto_at | datetime | Fecha resolución |
| created_at | datetime | Fecha reporte |

### wp_flavor_marketplace_transacciones
Transacciones registradas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| anuncio_id | bigint(20) | FK anuncio |
| vendedor_id | bigint(20) | FK vendedor |
| comprador_id | bigint(20) | FK comprador |
| precio_acordado | decimal(10,2) | Precio final |
| metodo_pago | varchar(50) | Método |
| estado | enum | pendiente/completada/cancelada/disputa |
| fecha_acuerdo | datetime | Fecha acuerdo |
| fecha_entrega | datetime | Fecha entrega |
| notas | text | Notas |
| created_at | datetime | Fecha |

## Shortcodes

### Explorar

```php
[marketplace_catalogo]
// Catálogo de anuncios
// - tipo: venta|compra|intercambio|regalo|servicio|todos
// - categoria: slug
// - condicion: nuevo|usado|todos
// - orden: recientes|precio_asc|precio_desc|populares
// - precio_min: número
// - precio_max: número
// - limite: número
// - columnas: 2|3|4

[marketplace_buscar]
// Buscador con filtros
// - categorias: mostrar selector
// - mapa: mostrar mapa
// - ubicacion: filtro ubicación
```

### Publicar y Gestionar

```php
[marketplace_publicar]
// Formulario publicar anuncio
// - tipos: venta,compra,intercambio
// - categorias: slugs permitidas

[marketplace_mis_anuncios]
// Anuncios del usuario
// - estado: todos|activos|vendidos|expirados
// - limite: número

[marketplace_favoritos]
// Anuncios guardados

[marketplace_mensajes]
// Bandeja de mensajes
// - anuncio_id: filtrar por anuncio
```

### Detalle

```php
[marketplace_detalle]
// Página del anuncio
// - id: ID (o auto desde URL)

[marketplace_perfil]
// Perfil de vendedor
// - id: ID usuario
// - mostrar_anuncios: true|false
// - mostrar_valoraciones: true|false
```

## Dashboard Tab

**Clase:** `Flavor_Marketplace_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Explorar
- `mis-anuncios` - Mis anuncios
- `publicar` - Nuevo anuncio
- `favoritos` - Guardados
- `mensajes` - Conversaciones
- `compras` - Mis compras
- `ventas` - Mis ventas
- `moderacion` - Solo moderadores

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/marketplace/` | index | Explorar |
| `/mi-portal/marketplace/publicar/` | publicar | Nuevo |
| `/mi-portal/marketplace/mis-anuncios/` | mis-anuncios | Propios |
| `/mi-portal/marketplace/favoritos/` | favoritos | Guardados |
| `/mi-portal/marketplace/mensajes/` | mensajes | Chat |
| `/mi-portal/marketplace/anuncio/{slug}/` | ver | Detalle |
| `/mi-portal/marketplace/vendedor/{id}/` | vendedor | Perfil |
| `/mi-portal/marketplace/compras/` | compras | Historial |
| `/mi-portal/marketplace/ventas/` | ventas | Historial |

## Vinculaciones

| Módulo | Integración |
|--------|-------------|
| comunidades | Mercadillo por comunidad |
| socios | Destacados para socios |
| economia-don | Regalos vinculados |
| chat-interno | Mensajería |

## Configuración

```php
'marketplace' => [
    'enabled' => true,
    'tipos_permitidos' => ['venta', 'compra', 'intercambio', 'regalo', 'servicio'],
    'requiere_aprobacion' => false,
    'max_imagenes' => 10,
    'max_anuncios_activos' => 20,
    'dias_publicacion' => 60,
    'renovacion_automatica' => false,
    'precio_destacado' => 0, // 0 = gratis
    'comision_venta' => 0, // % comisión
    'permitir_envios' => true,
    'chat_integrado' => true,
    'valoraciones' => true,
    'notificaciones' => [
        'anuncio_publicado' => true,
        'mensaje_recibido' => true,
        'anuncio_expira' => true,
        'nueva_valoracion' => true,
    ],
]
```

## Permisos

| Capability | Descripción |
|------------|-------------|
| `mp_publicar` | Publicar anuncios |
| `mp_editar_propio` | Editar propios |
| `mp_contactar` | Enviar mensajes |
| `mp_valorar` | Valorar vendedores |
| `mp_moderar` | Moderar anuncios |
| `mp_destacar` | Destacar anuncios |
