# Módulo: Trading IA

> Bot de trading simulado con IA para criptomonedas Solana

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `trading_ia` |
| **Versión** | 1.0.0+ |
| **Categoría** | Economía / IA |
| **Experimental** | Sí (requiere activación manual) |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

Bot de trading simulado (paper trading) con IA para criptomonedas Solana. Incluye indicadores técnicos, gestión de riesgo, reglas dinámicas y ciclo automático de trading.

### Características Principales

- **Paper Trading**: Simulación sin riesgo real
- **Indicadores Técnicos**: Análisis automático
- **Gestión de Riesgo**: Stop loss, take profit
- **Reglas Dinámicas**: IA crea reglas adaptativas
- **Bot Automático**: Ciclo de trading programado
- **Alertas**: Notificaciones de precio
- **Portfolio**: Seguimiento de posiciones

---

## Módulo Experimental

Este módulo está **deshabilitado por defecto**. Para activarlo:

```php
$settings['enable_experimental_modules'] = true;
// O específicamente
$settings['experimental_modules'][] = 'trading_ia';
```

---

## Tablas de Base de Datos

### `{prefix}_flavor_trading_ia_trades`

Operaciones de trading.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario |
| `tipo` | enum | `compra`, `venta` |
| `token` | varchar(20) | Token operado |
| `cantidad` | decimal(20,8) | Cantidad |
| `precio_entrada` | decimal(20,8) | Precio de entrada |
| `precio_salida` | decimal(20,8) | Precio de salida |
| `estado` | enum | `abierta`, `cerrada`, `cancelada` |
| `stop_loss` | decimal(20,8) | Stop loss |
| `take_profit` | decimal(20,8) | Take profit |
| `pnl` | decimal(20,8) | Beneficio/pérdida |
| `pnl_porcentaje` | decimal(10,4) | P&L en % |
| `confianza_ia` | int | Confianza IA (0-100) |
| `razon_ia` | text | Razón de la IA |
| `fecha_apertura` | datetime | Fecha apertura |
| `fecha_cierre` | datetime | Fecha cierre |

### `{prefix}_flavor_trading_ia_portfolio`

Portfolio de usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario |
| `balance_usd` | decimal(20,8) | Saldo USD |
| `balance_inicial` | decimal(20,8) | Saldo inicial |
| `tokens_json` | longtext | Tokens en cartera |
| `precios_entrada_json` | longtext | Precios de entrada |
| `fees_acumuladas_usd` | decimal(10,6) | Fees pagadas |
| `contador_trades` | int | Total de trades |

### `{prefix}_flavor_trading_ia_reglas`

Reglas de trading (creadas por IA o usuario).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `regla_id` | varchar(50) | ID de regla |
| `usuario_id` | bigint | Usuario |
| `nombre` | varchar(255) | Nombre de regla |
| `token_condicion` | varchar(20) | Token afectado |
| `indicador` | varchar(50) | Indicador técnico |
| `operador` | varchar(5) | <, >, =, etc. |
| `valor` | decimal(20,8) | Valor de comparación |
| `accion_tipo` | varchar(50) | Tipo de acción |
| `accion_parametros_json` | text | Parámetros |
| `activa` | bool | Regla activa |
| `creada_por` | varchar(10) | `ia` o `usuario` |
| `veces_activada` | int | Contador |

### `{prefix}_flavor_trading_ia_alertas`

Alertas de precio.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario |
| `token` | varchar(20) | Token |
| `tipo_alerta` | enum | precio_mayor, precio_menor, cambio_porcentaje |
| `valor_objetivo` | decimal(20,8) | Valor objetivo |
| `activa` | bool | Alerta activa |
| `notificada` | bool | Ya notificada |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `agresividad` | int | 5 | Nivel de agresividad (1-10) |
| `riesgo_maximo_porcentaje` | float | 5.0 | Riesgo máximo por trade |
| `stop_loss_porcentaje` | float | 3.0 | Stop loss % |
| `take_profit_porcentaje` | float | 5.0 | Take profit % |
| `intervalo_analisis` | int | 60 | Segundos entre análisis |
| `confianza_minima_trade` | int | 60 | Confianza mínima (0-100) |
| `balance_inicial` | float | 1000.0 | Balance inicial USD |
| `auto_ajuste_enabled` | bool | false | Auto-ajuste parámetros |
| `bot_activo` | bool | false | Bot automático activo |
| `tokens_monitoreados` | array | [SOL,BONK,JUP,WIF,JTO] | Tokens a monitorear |
| `max_trades_por_hora` | int | 10 | Máximo trades/hora |
| `max_posiciones_abiertas` | int | 5 | Máximo posiciones |
| `stop_loss_global` | float | 15.0 | Stop loss global % |
| `min_balance_usd` | int | 10 | Balance mínimo |
| `max_reglas` | int | 30 | Máximo reglas activas |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `trading_ia_obtener_estado` | Estado del bot | Usuario |
| `trading_ia_obtener_portfolio` | Ver portfolio | Usuario |
| `trading_ia_obtener_mercado` | Datos de mercado | Usuario |
| `trading_ia_obtener_indicadores` | Indicadores técnicos | Usuario |
| `trading_ia_ejecutar_compra` | Ejecutar compra | Usuario |
| `trading_ia_ejecutar_venta` | Ejecutar venta | Usuario |
| `trading_ia_iniciar_bot` | Iniciar bot automático | Usuario |
| `trading_ia_detener_bot` | Detener bot | Usuario |
| `trading_ia_historial_trades` | Ver historial | Usuario |
| `trading_ia_obtener_reglas` | Listar reglas | Usuario |
| `trading_ia_crear_regla` | Crear regla | Usuario |
| `trading_ia_eliminar_regla` | Eliminar regla | Usuario |
| `trading_ia_actualizar_parametros` | Actualizar config | Usuario |
| `trading_ia_reset` | Reset paper trading | Usuario |
| `trading_ia_estado_riesgo` | Ver estado riesgo | Usuario |
| `trading_ia_agregar_token` | Añadir token | Usuario |
| `trading_ia_eliminar_token` | Quitar token | Usuario |
| `trading_ia_exportar_historial` | Exportar CSV | Usuario |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_trading_ia_ciclo_trading` | Custom interval | Ciclo de análisis y trading |
| `flavor_trading_ia_reporte_diario` | Diario | Generar reporte |
| `flavor_trading_ia_verificar_alertas` | Cada hora | Verificar alertas de precio |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `trading-ia-dashboard` | Panel principal |
| Operaciones | `trading-ia-operaciones` | Historial de trades |
| Configuración | `trading-ia-configuracion` | Parámetros del bot |

---

## Flujo del Bot Automático

```
1. ACTIVAR BOT
   bot_activo = true
   Se programa cron
   ↓
2. CICLO DE ANÁLISIS
   Obtener precios actuales
   Calcular indicadores
   ↓
3. EVALUAR REGLAS
   Verificar reglas activas
   Calcular confianza IA
   ↓
4. DECISIÓN
   Si confianza >= confianza_minima
   Y dentro de límites de riesgo
   ↓
5. EJECUTAR TRADE
   Abrir posición
   Establecer SL/TP
   ↓
6. GESTIÓN
   Monitorear posiciones
   Cerrar si toca SL/TP
   ↓
7. REPETIR
   Siguiente ciclo según intervalo
```

---

## Reglas Dinámicas

La IA puede crear reglas automáticamente:

| Indicador | Descripción |
|-----------|-------------|
| `RSI` | Relative Strength Index |
| `MACD` | Moving Average Convergence |
| `EMA` | Exponential Moving Average |
| `volumen` | Volumen de trading |
| `precio_cambio_1h` | Cambio % última hora |
| `precio_cambio_24h` | Cambio % últimas 24h |

---

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver dashboard | Usuario autenticado |
| Hacer trades | Usuario autenticado |
| Configurar bot | Usuario autenticado |
| Admin panel | `manage_options` |

---

## Notas de Implementación

- Todo es simulación (paper trading)
- Precios obtenidos de APIs reales
- Las reglas IA aprenden del historial
- Stop loss global protege el portfolio
- Rate limiting evita sobrecarga
- Portfolio por usuario independiente
- Exportación CSV disponible
- Compatible con notificaciones del plugin

---

## Advertencias

⚠️ **Módulo Experimental**
- Solo paper trading (simulación)
- No invertir dinero real basándose en esto
- Los indicadores son educativos
- La IA puede cometer errores
