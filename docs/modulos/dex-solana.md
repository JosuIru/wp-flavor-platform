# Módulo: DEX Solana

> Exchange descentralizado para tokens Solana con swap, pools y farming

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `dex_solana` |
| **Versión** | 1.0.0+ |
| **Categoría** | Economía / Blockchain |
| **Experimental** | Sí (requiere activación manual) |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

Exchange descentralizado (DEX) simulado para tokens Solana. Permite swap de tokens vía Jupiter API, pools de liquidez AMM, yield farming y modo dual (paper trading y real).

### Características Principales

- **Swap de Tokens**: Intercambio vía Jupiter API
- **Paper Trading**: Modo simulación sin riesgo real
- **Pools AMM**: Pools de liquidez automáticos
- **Yield Farming**: Programas de farming
- **Portfolio**: Seguimiento de posiciones
- **Historial**: Registro de operaciones

---

## Módulo Experimental

Este módulo está **deshabilitado por defecto**. Para activarlo:

1. Ir a Ajustes → Avanzados
2. Activar "Módulos experimentales"
3. O activar específicamente `dex_solana`

```php
// Verificación interna
$settings['enable_experimental_modules'] = true;
// O
$settings['experimental_modules'][] = 'dex_solana';
```

---

## Tablas de Base de Datos

### `{prefix}_flavor_dex_swaps`

Historial de swaps.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario |
| `token_entrada` | varchar(20) | Token origen |
| `token_salida` | varchar(20) | Token destino |
| `cantidad_entrada` | decimal(20,8) | Cantidad enviada |
| `cantidad_salida` | decimal(20,8) | Cantidad recibida |
| `slippage` | decimal(5,2) | Slippage aplicado |
| `modo` | enum | `paper`, `real` |
| `estado` | enum | `completado`, `fallido` |
| `fecha` | datetime | Fecha del swap |

### `{prefix}_flavor_dex_pools`

Pools de liquidez.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `token_a` | varchar(20) | Token A |
| `token_b` | varchar(20) | Token B |
| `reserva_a` | decimal(20,8) | Reserva de A |
| `reserva_b` | decimal(20,8) | Reserva de B |
| `fee` | decimal(5,4) | Comisión |
| `activo` | bool | Pool activo |

### `{prefix}_flavor_dex_lp_positions`

Posiciones LP de usuarios.

### `{prefix}_flavor_dex_farming`

Programas de farming.

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `modo_activo` | string | paper | `paper` o `real` |
| `balance_inicial_usdc` | float | 1000.0 | Balance inicial |
| `slippage_maximo_porcentaje` | float | 1.0 | Slippage máximo |
| `slippage_defecto_porcentaje` | float | 0.5 | Slippage por defecto |
| `tokens_favoritos` | array | [SOL,USDC,JUP,RAY,BONK] | Tokens favoritos |
| `cache_precios_segundos` | int | 30 | Cache de precios |
| `max_swaps_por_hora` | int | 20 | Rate limit |
| `pools_semilla_activos` | bool | true | Crear pools iniciales |
| `farming_activo` | bool | true | Activar farming |
| `reward_multiplicador` | float | 1.0 | Multiplicador rewards |
| `wallet_address` | string | - | Wallet Solana (modo real) |
| `auto_compound` | bool | false | Auto-compound rewards |

---

## Clases Auxiliares

| Clase | Archivo | Descripción |
|-------|---------|-------------|
| `Jupiter_API` | class-dex-solana-jupiter-api.php | Integración Jupiter |
| `Token_Registry` | class-dex-solana-token-registry.php | Registro de tokens |
| `Portfolio` | class-dex-solana-portfolio.php | Gestión portfolio |
| `Historial` | class-dex-solana-historial.php | Historial operaciones |
| `Swap_Engine` | class-dex-solana-swap-engine.php | Motor de swaps |
| `Pool_Manager` | class-dex-solana-pool-manager.php | Gestión pools |
| `Farming` | class-dex-solana-farming.php | Yield farming |
| `Cerebro` | class-dex-solana-cerebro.php | Lógica IA |

---

## REST API Endpoints

El módulo registra endpoints para integración con frontend SPA.

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `dex-solana-dashboard` | Panel con estadísticas |
| Operaciones | `dex-solana-operaciones` | Swaps, pools, farming |
| Configuración | `dex-solana-configuracion` | Ajustes del DEX |

### Tabs de Operaciones

| Tab | Descripción |
|-----|-------------|
| Swaps | Historial de swaps |
| Pools LP | Pools de liquidez |
| Farming | Programas de farming |

---

## Flujo de Trabajo

```
1. ACTIVAR MÓDULO
   Habilitar en ajustes experimentales
   ↓
2. CONFIGURAR
   Modo paper/real
   Tokens favoritos
   ↓
3. SWAP
   Intercambiar tokens
   Vía Jupiter API (precios reales)
   ↓
4. POOLS (Opcional)
   Proporcionar liquidez
   Ganar fees
   ↓
5. FARMING (Opcional)
   Stakear LP tokens
   Ganar rewards
```

---

## Tokens Conocidos

El módulo incluye registro de tokens populares:

| Token | Símbolo |
|-------|---------|
| Solana | SOL |
| USD Coin | USDC |
| Jupiter | JUP |
| Raydium | RAY |
| Bonk | BONK |

---

## Permisos

| Acción | Capability |
|--------|------------|
| Ver DEX | `read` |
| Hacer swap | Usuario autenticado |
| Configurar | `manage_options` |

---

## Notas de Implementación

- Modo paper usa precios reales pero saldo simulado
- Jupiter API para cotizaciones y routing
- Los pools se "siembran" al activar si está configurado
- Farming simula rewards según configuración
- Rate limiting para evitar abuso de API
- Cache de precios para reducir llamadas
- Compatible con billetera Solana real (modo real)

---

## Advertencias

⚠️ **Módulo Experimental**
- Puede contener bugs
- No usar con fondos reales sin verificar
- El modo real requiere configuración adicional
