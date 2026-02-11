# Referencia Rapida de Endpoints

## Namespaces Disponibles

| Namespace | Base URL | Descripcion |
|-----------|----------|-------------|
| `flavor-chat-ia/v1` | `/wp-json/flavor-chat-ia/v1/` | Grupos de consumo, pedidos, suscripciones |
| `flavor-network/v1` | `/wp-json/flavor-network/v1/` | Red de comunidades, directorio, mapa |
| `flavor/v1` | `/wp-json/flavor/v1/` | PWA, temas, utilidades |

---

## GRUPOS DE CONSUMO (flavor-chat-ia/v1)

### Pedidos Colectivos

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/pedidos` | No | Listar pedidos |
| GET | `/pedidos/{id}` | No | Detalle de pedido |
| POST | `/pedidos/{id}/unirse` | Si | Unirse a pedido |
| POST | `/pedidos/{id}/marcar-pagado` | Si | Marcar como pagado |
| POST | `/pedidos/{id}/marcar-recogido` | Si | Marcar como recogido |
| GET | `/mis-pedidos` | Si | Mis pedidos |

### Perfil y Preferencias

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/gc/perfil` | Si | Perfil del consumidor |
| PUT | `/gc/preferencias` | Si | Actualizar preferencias |

### Lista de Compra

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/gc/lista-compra` | Si | Obtener lista |
| POST | `/gc/lista-compra/agregar` | Si | Agregar producto |
| DELETE | `/gc/lista-compra/{id}` | Si | Quitar producto |

### Suscripciones

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/gc/suscripciones` | Si | Mis suscripciones |
| POST | `/gc/suscripciones` | Si | Crear suscripcion |
| POST | `/gc/suscripciones/{id}/pausar` | Si | Pausar |
| POST | `/gc/suscripciones/{id}/cancelar` | Si | Cancelar |

### Catalogo

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/gc/productos` | No | Catalogo de productos |
| GET | `/gc/productores` | No | Lista de productores |
| GET | `/gc/productores-cercanos` | No | Productores por ubicacion |
| GET | `/gc/cestas-tipo` | No | Tipos de cestas |
| GET | `/gc/ciclos/calendario` | No | Calendario de ciclos |
| GET | `/gc/pedidos/historial` | Si | Historial de pedidos |

---

## RED DE COMUNIDADES (flavor-network/v1)

### Directorio y Mapa

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/directory` | No | Directorio de nodos |
| GET | `/node/{slug}` | No | Perfil de nodo |
| GET | `/node/{slug}/qr` | No | QR del nodo |
| GET | `/map` | No | Datos del mapa |
| GET | `/nearby` | No | Nodos cercanos |
| GET | `/catalog/{slug}` | No | Catalogo del nodo |

### Tablon

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/board` | No | Listar publicaciones |
| POST | `/board` | Admin | Crear publicacion |
| PUT | `/board/{id}` | Admin | Actualizar |
| DELETE | `/board/{id}` | Admin | Eliminar |

### Contenido Compartido

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/content` | No | Listar contenido |
| POST | `/content` | Admin | Crear contenido |
| GET | `/content/{id}` | No | Detalle |
| PUT | `/content/{id}` | Admin | Actualizar |
| DELETE | `/content/{id}` | Admin | Eliminar |

### Eventos

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/events` | No | Listar eventos |
| POST | `/events` | Admin | Crear evento |
| GET | `/events/{id}` | No | Detalle |
| PUT | `/events/{id}` | Admin | Actualizar |
| DELETE | `/events/{id}` | Admin | Eliminar |

### Colaboraciones

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/collaborations` | No | Listar |
| POST | `/collaborations` | Admin | Crear |
| GET | `/collaborations/{id}` | No | Detalle |
| PUT | `/collaborations/{id}` | Admin | Actualizar |
| DELETE | `/collaborations/{id}` | Admin | Eliminar |
| POST | `/collaborations/{id}/join` | Admin | Unirse |

### Conexiones entre Nodos

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| POST | `/connect` | Admin | Solicitar conexion |
| GET | `/connections` | Admin | Listar conexiones |
| PUT | `/connections/{id}` | Admin | Actualizar |
| DELETE | `/connections/{id}` | Admin | Eliminar |

### Mensajeria

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/messages` | Admin | Listar mensajes |
| POST | `/messages` | Admin | Enviar mensaje |
| POST | `/messages/{id}/read` | Admin | Marcar leido |
| POST | `/messages/{id}/reply` | Admin | Responder |
| DELETE | `/messages/{id}` | Admin | Eliminar |

### Alertas Solidarias

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/alerts` | No | Listar alertas |
| POST | `/alerts` | Admin | Crear alerta |
| PUT | `/alerts/{id}` | Admin | Actualizar |
| DELETE | `/alerts/{id}` | Admin | Eliminar |

### Ofertas de Tiempo

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/time-offers` | No | Listar ofertas |
| POST | `/time-offers` | Admin | Crear oferta |
| PUT | `/time-offers/{id}` | Admin | Actualizar |
| DELETE | `/time-offers/{id}` | Admin | Eliminar |

### Preguntas y Respuestas

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/questions` | No | Listar preguntas |
| POST | `/questions` | Admin | Crear pregunta |
| GET | `/questions/{id}` | No | Detalle |
| PUT | `/questions/{id}` | Admin | Actualizar |
| DELETE | `/questions/{id}` | Admin | Eliminar |
| GET | `/questions/{id}/answers` | No | Listar respuestas |
| POST | `/questions/{id}/answers` | Admin | Responder |
| POST | `/answers/{id}/vote` | Admin | Votar respuesta |
| POST | `/answers/{id}/solution` | Admin | Marcar solucion |

### Matching

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/matches` | Admin | Listar matches |
| POST | `/matches` | Admin | Generar matches |
| PUT | `/matches/{id}` | Admin | Responder match |
| DELETE | `/matches/{id}` | Admin | Descartar |
| POST | `/matches/{id}/contact` | Admin | Contactar |

### Sellos de Calidad

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/verify-seal/{slug}` | No | Verificar sello |
| GET | `/seals` | Admin | Listar sellos |
| POST | `/seals` | Admin | Crear sello |
| PUT | `/seals/{id}` | Admin | Actualizar |
| DELETE | `/seals/{id}` | Admin | Eliminar |

### Newsletter

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/newsletters` | Admin | Listar newsletters |
| POST | `/newsletters` | Admin | Crear |
| GET | `/newsletters/{id}` | Admin | Detalle |
| PUT | `/newsletters/{id}` | Admin | Actualizar |
| DELETE | `/newsletters/{id}` | Admin | Eliminar |
| POST | `/newsletters/{id}/send` | Admin | Enviar |
| GET | `/newsletter-subscribers` | Admin | Listar suscriptores |
| POST | `/newsletter-subscribers` | Admin | Agregar suscriptor |
| DELETE | `/newsletter-subscribers/{id}` | Admin | Eliminar suscriptor |
| GET | `/newsletter-auto-content` | Admin | Contenido automatico |

### Favoritos y Recomendaciones

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/favorites` | Admin | Mis favoritos |
| POST | `/favorites` | Admin | Toggle favorito |
| GET | `/recommendations` | Admin | Recomendaciones |
| POST | `/recommendations` | Admin | Crear |
| DELETE | `/recommendations/{id}` | Admin | Eliminar |

### Nodo Local

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/local-node` | Admin | Obtener nodo local |
| POST | `/local-node` | Admin | Guardar nodo local |

### Estadisticas

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/stats` | No | Estadisticas de red |

---

## PWA Y TEMAS (flavor/v1)

### PWA

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/pwa/sync` | No | URLs para sincronizar |
| GET | `/pwa/cache-status` | Admin | Estado del cache |

### Temas

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/themes` | No | Lista de temas |
| GET | `/themes/active` | No | Tema activo |
| POST | `/themes/active` | Admin | Establecer tema |

### Documentacion

| Metodo | Endpoint | Auth | Descripcion |
|--------|----------|------|-------------|
| GET | `/docs/endpoints` | Admin | Lista de endpoints JSON |
| GET | `/docs/openapi` | No | Especificacion OpenAPI |

---

## Leyenda

- **Auth No**: Endpoint publico, no requiere autenticacion
- **Auth Si**: Requiere usuario autenticado (JWT o Nonce)
- **Auth Admin**: Requiere permisos de administrador (`manage_options`)
