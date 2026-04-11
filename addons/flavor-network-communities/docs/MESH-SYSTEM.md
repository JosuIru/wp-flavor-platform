# Sistema P2P/Mesh - Flavor Network Communities

## Visión General

El sistema mesh transforma Flavor Network Communities de un grafo DAG jerárquico a una **red P2P verdaderamente descentralizada** con:

- **Gossip Protocol** para propagación de mensajes
- **CRDTs** para resolución de conflictos sin coordinador central
- **Identidad criptográfica** con Ed25519
- **Topología mesh** que permite ciclos y múltiples caminos

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                    CAPA DE APLICACIÓN                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │  Contenido  │  │   Eventos   │  │Colaboraciones│             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
└─────────┼────────────────┼────────────────┼─────────────────────┘
          │                │                │
┌─────────▼────────────────▼────────────────▼─────────────────────┐
│                    CAPA DE SINCRONIZACIÓN                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ CRDT Manager│  │Vector Clocks│  │  Sync Log   │             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
└─────────┼────────────────┼────────────────┼─────────────────────┘
          │                │                │
┌─────────▼────────────────▼────────────────▼─────────────────────┐
│                    CAPA DE PROTOCOLO                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   Gossip    │  │Peer Discovery│ │ Mesh Topology│             │
│  │  Protocol   │  │   (PEX)     │  │             │             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
└─────────┼────────────────┼────────────────┼─────────────────────┘
          │                │                │
┌─────────▼────────────────▼────────────────▼─────────────────────┐
│                    CAPA DE TRANSPORTE                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │  REST API   │  │ Async HTTP  │  │   Cache     │             │
│  │(flavor-mesh)│  │ (curl_multi)│  │ (transients)│             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
```

## Componentes Principales

### 1. Identidad de Peer (Ed25519)

Cada nodo tiene una identidad criptográfica única:

```php
// El peer_id es el hash SHA-256 de la clave pública
$peer_id = hash('sha256', $public_key_ed25519);

// Las claves se generan automáticamente al activar
$keypair = sodium_crypto_sign_keypair();
$public_key = sodium_crypto_sign_publickey($keypair);
$private_key = sodium_crypto_sign_secretkey($keypair);
```

**Tabla:** `flavor_network_peers`

| Campo | Descripción |
|-------|-------------|
| `peer_id` | Hash SHA-256 de clave pública (64 chars) |
| `public_key_ed25519` | Clave pública en base64 |
| `private_key_encrypted` | Clave privada cifrada (solo peer local) |
| `reputacion_score` | Score 0-100 basado en comportamiento |
| `trust_level` | unknown → seen → verified → trusted |

### 2. Gossip Protocol

Propagación epidémica de mensajes adaptada a WordPress:

```
1. Mensaje creado → guardar local → firmar con Ed25519
2. Enviar a 1 peer inmediatamente (fire-and-forget, 500ms timeout)
3. Cron cada minuto: procesar batch pendiente en paralelo
4. Receptor: verificar firma → guardar → forward si TTL > 0
```

**Tipos de mensaje:**
- `peer_announce` - Anunciar nuevo peer
- `data_update` - Actualización de contenido
- `heartbeat` - Latido de vida
- `crdt_sync` - Sincronización CRDT
- `peer_exchange` - Intercambio de listas
- `content_share` - Contenido compartido
- `alert` - Alertas urgentes

**Tabla:** `flavor_network_gossip_messages`

### 3. CRDTs (Conflict-free Replicated Data Types)

Resolución de conflictos sin coordinador:

| CRDT | Uso | Ejemplo |
|------|-----|---------|
| **LWW-Register** | Campos simples | Título, descripción |
| **OR-Set** | Colecciones | Tags, categorías |
| **G-Counter** | Contadores incrementales | Vistas, likes |
| **PN-Counter** | Contadores bidireccionales | Votos +/- |
| **Vector Clock** | Ordenamiento causal | Detectar concurrencia |

```php
// Ejemplo: Merge de LWW-Register
$local = new Flavor_LWW_Register('titulo_local', microtime(true), 'peer_a');
$remote = new Flavor_LWW_Register('titulo_remoto', microtime(true) + 1, 'peer_b');
$merged = $local->merge($remote);
// Resultado: 'titulo_remoto' (timestamp más reciente gana)

// Ejemplo: Merge de OR-Set
$set_a = new Flavor_OR_Set('peer_a');
$set_a->add('tag1')->add('tag2');
$set_b = new Flavor_OR_Set('peer_b');
$set_b->add('tag2')->add('tag3');
$merged = $set_a->merge($set_b);
// Resultado: ['tag1', 'tag2', 'tag3']
```

### 4. Peer Discovery

Descubrimiento de peers sin DHT:

1. **Bootstrap Nodes** - Nodos conocidos para arrancar
2. **Peer Exchange (PEX)** - Intercambiar listas con peers conocidos
3. **Gossip Announce** - Anunciar presencia via gossip

```php
// Añadir nodo bootstrap
Flavor_Peer_Discovery::instance()->add_bootstrap_node(
    'https://bootstrap.flavor.network',
    'Flavor Network Bootstrap'
);

// Descubrir peers
$new_peers = Flavor_Peer_Discovery::instance()->discover();
```

### 5. Topología Mesh

A diferencia del DAG original, el mesh permite:

- **Ciclos** - Un peer puede conectar con cualquier otro
- **Múltiples caminos** - Redundancia en la red
- **Límite de conexiones** - Máximo 20 por defecto

```php
// Verificar si se puede conectar
$topology = Flavor_Network_Mesh_Topology::instance();
if ($topology->can_connect($peer_a, $peer_b)) {
    $topology->establish_connection($peer_a, $peer_b);
}

// Encontrar ruta más corta
$path = $topology->find_shortest_path($from_peer, $to_peer);
```

## API REST

**Namespace:** `flavor-mesh/v1`

### Endpoints Públicos

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/health` | GET | Estado del sistema |
| `/peers/list` | GET | Lista de peers conocidos |
| `/peers/{peer_id}` | GET | Info de un peer |

### Endpoints Autenticados

Requieren firma Ed25519 en headers:
- `X-Mesh-Peer-Id`: ID del peer
- `X-Mesh-Timestamp`: Unix timestamp
- `X-Mesh-Signature`: Firma del mensaje

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/gossip/receive` | POST | Recibir mensaje gossip |
| `/peers/exchange` | POST | Intercambiar listas PEX |
| `/mesh/connect` | POST | Solicitar conexión |
| `/mesh/handshake` | POST | Completar handshake |
| `/sync/push` | POST | Enviar datos sync |
| `/sync/pull` | POST | Solicitar datos sync |
| `/crdt/merge` | POST | Merge de estado CRDT |

## Cron Jobs

| Hook | Intervalo | Función |
|------|-----------|---------|
| `flavor_mesh_gossip_batch` | 1 min | Procesar cola gossip |
| `flavor_mesh_heartbeat` | 5 min | Enviar heartbeats |
| `flavor_mesh_peer_discovery` | 1 hora | Descubrir nuevos peers |
| `flavor_mesh_cleanup_expired` | 1 hora | Limpiar mensajes expirados |

## Optimizaciones

### Sistema de Caché

Dos niveles para reducir queries:

```php
// Usar caché
$peer = flavor_mesh_cache()->get_peer($peer_id);
$online = flavor_mesh_cache()->get_online_peers(20);

// Cache-aside pattern
$stats = flavor_mesh_cache()->remember('stats', function() {
    return calculate_expensive_stats();
}, 300); // TTL 5 minutos
```

**TTLs:**
- `SHORT_TTL` = 60s (datos volátiles)
- `DEFAULT_TTL` = 300s (datos normales)
- `LONG_TTL` = 3600s (datos estables)

### Requests Paralelas

```php
// Enviar a múltiples peers en paralelo
$async = flavor_mesh_async();
$results = $async->send_parallel([
    ['url' => 'https://peer1.local/api', 'data' => $payload],
    ['url' => 'https://peer2.local/api', 'data' => $payload],
    ['url' => 'https://peer3.local/api', 'data' => $payload],
], 5); // timeout 5s

// Fire-and-forget (no bloquea)
$async->fire_and_forget($url, $data);
```

## Panel de Administración

Accesible desde: **Red → Panel Mesh**

Muestra:
- Estado del peer local
- Peers conectados
- Estadísticas de gossip
- Cola de mensajes pendientes
- Logs de sincronización
- Gestión de nodos bootstrap

## Migración desde Sistema Legacy

Para migrar nodos existentes a peers:

```bash
# Via WP-CLI
wp flavor-mesh migrate-nodes --dry-run  # Ver qué se migraría
wp flavor-mesh migrate-nodes            # Ejecutar migración
```

O via script:

```php
Flavor_Mesh_Migration::migrate_all_nodes();
```

## Testing

### Test Standalone (CRDTs + Crypto)

```bash
cd addons/flavor-network-communities
php tools/test-mesh-system.php --standalone
```

### Test Multi-Nodo

```bash
php tools/test-mesh-multi-node.php \
  --local-url=http://sitio1.local \
  --peer-url=http://sitio2.local
```

## Troubleshooting

### El peer local no se crea

```php
// Verificar que sodium está disponible
var_dump(extension_loaded('sodium'));

// Forzar creación
Flavor_Network_Peer::create_local_peer();
```

### Los mensajes gossip no se propagan

1. Verificar cron: `wp cron event list`
2. Verificar peers online: Panel Mesh → Peers
3. Revisar logs: `wp-content/debug.log`

### Error de firma inválida

```php
// Verificar que las claves son correctas
$peer = Flavor_Network_Peer::get_local();
$signature = $peer->sign('test');
var_dump($peer->verify('test', $signature)); // debe ser true
```

### Conflictos de CRDT

Los CRDTs resuelven automáticamente. Para debug:

```php
$manager = Flavor_CRDT_Manager::instance();
$state = $manager->get_crdt_state('shared_content', '123', 'titulo');
var_dump($state);
```

## Glosario

| Término | Definición |
|---------|------------|
| **Peer** | Nodo de la red con identidad criptográfica |
| **Gossip** | Protocolo de propagación epidémica |
| **CRDT** | Estructura de datos que converge sin coordinación |
| **Vector Clock** | Estructura para ordenar eventos causalmente |
| **TTL** | Time-to-live, saltos restantes de un mensaje |
| **Handshake** | Proceso de establecer conexión entre peers |
| **Bootstrap** | Nodo conocido para descubrir la red |
| **PEX** | Peer Exchange, intercambio de listas de peers |
