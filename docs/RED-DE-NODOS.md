# Red de Nodos Federada

## Descripción General

El sistema de red permite conectar múltiples instalaciones de Flavor Platform en una red federada descentralizada. Cada nodo es independiente pero puede compartir contenido, descubrir otros nodos y colaborar.

---

## Arquitectura

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   NODO A    │◄───────►│   NODO B    │◄───────►│   NODO C    │
│  (Madrid)   │         │ (Barcelona) │         │ (Valencia)  │
└─────────────┘         └─────────────┘         └─────────────┘
      ▲                       ▲                       ▲
      │                       │                       │
      └───────────────────────┴───────────────────────┘
                    Sincronización P2P
                    (Sin servidor central)
```

### Características

- **Descentralizado**: No hay servidor central
- **Peer-to-peer**: Los nodos se descubren entre sí
- **Sincronización automática**: Cada hora vía WordPress Cron
- **APIs abiertas**: REST API para interoperabilidad

---

## Puente de Contenido (Network Content Bridge)

El archivo `class-network-content-bridge.php` conecta el sistema de integraciones con la red.

### Niveles de Visibilidad

| Nivel | Descripción |
|-------|-------------|
| `privado` | Solo visible en este nodo |
| `conectado` | Visible para nodos conectados |
| `federado` | Visible para nodos federados |
| `publico` | Visible para toda la red |

### Tipos de Contenido Compartible

```php
const NETWORK_SHAREABLE_TYPES = [
    'recetas'    => ['category' => 'saberes'],
    'multimedia' => ['category' => 'contenido'],
    'podcast'    => ['category' => 'contenido'],
    'biblioteca' => ['category' => 'saberes'],
    'eventos'    => ['category' => 'actividades'],
    'cursos'     => ['category' => 'saberes'],
    'talleres'   => ['category' => 'actividades'],
    'productos'  => ['category' => 'catalogo'],
    'servicios'  => ['category' => 'catalogo'],
];
```

---

## Uso

### Compartir Contenido en la Red

Al editar cualquier contenido compatible, aparecerá un campo "Compartir en la Red":

1. Seleccionar nivel de visibilidad
2. Opcionalmente incluir contenido relacionado (integraciones)
3. Guardar

El contenido se sincroniza automáticamente con la red.

### Shortcodes

```php
// Mostrar contenido de la red
[flavor_red_contenido tipo="recetas" limite="10" columnas="3" mostrar_nodo="true"]

// Recetas de la red
[flavor_red_recetas limite="6" columnas="2"]

// Eventos de la red
[flavor_red_eventos limite="10"]
```

#### Parámetros de Shortcodes

| Parámetro | Descripción | Default |
|-----------|-------------|---------|
| `tipo` | Tipo de contenido (recetas, eventos, etc) | todos |
| `limite` | Número máximo de items | 10 |
| `columnas` | Columnas del grid | 3 |
| `mostrar_nodo` | Mostrar info del nodo origen | true |

---

## API REST

### Endpoints

```
GET  /wp-json/flavor-integration/v1/network-content
GET  /wp-json/flavor-integration/v1/network-content/{id}
GET  /wp-json/flavor-integration/v1/network-stats
```

### Obtener Contenido de la Red

```bash
GET /wp-json/flavor-integration/v1/network-content?tipo=recetas&pagina=1&por_pagina=20
```

**Respuesta:**
```json
{
  "contenido": [
    {
      "id": 123,
      "titulo": "Receta de Paella",
      "descripcion": "Receta tradicional...",
      "url_externa": "https://nodo-remoto.com/receta/paella",
      "imagen_url": "https://...",
      "nodo_nombre": "Comunidad Valencia",
      "nodo_logo": "https://...",
      "metadata": {
        "tiempo_preparacion": 45,
        "dificultad": "media"
      }
    }
  ],
  "total": 156,
  "paginas": 8,
  "pagina": 1
}
```

### Obtener Estadísticas

```bash
GET /wp-json/flavor-integration/v1/network-stats
```

**Respuesta:**
```json
{
  "total": 1250,
  "por_tipo": {
    "recetas": 450,
    "eventos": 230,
    "productos": 570
  },
  "tipos_disponibles": ["recetas", "multimedia", "podcast", ...]
}
```

---

## Funciones PHP

### Obtener Contenido Federado

```php
// Usando el filtro
$contenido = apply_filters('flavor_get_network_content', [], 'recetas', [
    'limite' => 20,
    'excluir_local' => true, // No incluir contenido local
    'nodo_id' => null,       // Filtrar por nodo específico
]);
```

### Publicar en la Red Manualmente

```php
// Normalmente es automático, pero se puede forzar
$bridge = Flavor_Network_Content_Bridge::get_instance();
$bridge->sync_content_to_network($post_id, $post);
```

---

## Hooks y Filtros

### Acciones

```php
// Cuando se comparte contenido
do_action('flavor_network_content_shared', $post_id, $tipo_contenido, $visibility);

// Cuando se quita de la red
do_action('flavor_network_content_removed', $post_id);
```

### Filtros

```php
// Personalizar tipos de post compartibles
add_filter('flavor_network_shareable_post_types', function($types) {
    $types[] = 'mi_custom_post_type';
    return $types;
});

// Personalizar metadata enviada
add_filter('flavor_network_content_metadata', function($metadata, $post_id, $post) {
    $metadata['mi_campo'] = get_post_meta($post_id, '_mi_campo', true);
    return $metadata;
}, 10, 3);
```

---

## Base de Datos

### Tabla: `wp_flavor_network_shared_content`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nodo_id | bigint | ID del nodo propietario |
| tipo_contenido | varchar | Tipo (recetas, eventos, etc) |
| titulo | varchar | Título del contenido |
| descripcion | text | Descripción corta |
| url_externa | varchar | URL al contenido original |
| imagen_url | varchar | URL de imagen destacada |
| visible_red | tinyint | Si es visible en la red |
| nivel_visibilidad | varchar | privado/conectado/federado/publico |
| referencia_local | bigint | ID del post/item local |
| metadata | longtext | JSON con datos adicionales |
| estado | varchar | activo/oculto/eliminado |

---

## Widget de Dashboard

El sistema incluye un widget de WordPress que muestra contenido reciente de otros nodos:

- Aparece automáticamente en el dashboard de admin
- Muestra los últimos 5 items compartidos
- Enlace para ver todo el contenido de la red

---

## Integración con Integraciones

Cuando compartes contenido con la opción "Incluir contenido relacionado":

```json
{
  "metadata": {
    "integraciones": {
      "recetas": [
        {"id": 45, "titulo": "Receta vinculada", "url": "..."}
      ],
      "multimedia": [
        {"id": 78, "titulo": "Video tutorial", "url": "..."}
      ]
    }
  }
}
```

Esto permite que los nodos remotos vean las relaciones entre contenidos.

---

## Seguridad

- Los endpoints públicos no requieren autenticación
- La publicación requiere `edit_posts` capability
- El contenido se sanitiza antes de enviarse
- Los nodos remotos no pueden modificar contenido local

---

## Troubleshooting

### El contenido no se sincroniza

1. Verificar que el nodo local está configurado
2. Verificar que la tabla `wp_flavor_network_shared_content` existe
3. Verificar nivel de visibilidad (no debe ser "privado")

### El shortcode no muestra nada

1. Verificar que hay contenido en la red
2. Probar con `excluir_local="false"` para ver contenido local también
3. Verificar logs de PHP

### Error "Nodo no configurado"

1. Ir a Flavor > Red > Configurar Nodo Local
2. Completar información del nodo
3. Guardar configuración
