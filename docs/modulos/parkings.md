# Módulo: Parkings Comunitarios

> Sistema de gestión de plazas de parking comunitarias

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `parkings` |
| **Versión** | 1.0.0+ |
| **Categoría** | Movilidad / Servicios |
| **Disponible en App** | Cliente |

### Principios Gailu

- `movilidad_sostenible` - Optimización de recursos compartidos
- `economia_local` - Gestión comunitaria de espacios
- `cooperacion` - Sistema de rotación y lista de espera

---

## Descripción

Sistema completo de gestión de parkings comunitarios con reservas temporales, asignaciones permanentes, sistema de rotación y lista de espera. Incluye visualización de planos interactivos con estado en tiempo real.

### Características Principales

- **Gestión de Parkings**: Múltiples parkings con diferentes configuraciones
- **Plazas**: Normal, grande, pequeña, moto, bici, carga eléctrica, movilidad reducida
- **Reservas**: Temporales, por rotación, para eventos
- **Asignaciones**: Propietario, inquilino, rotación, temporal
- **Lista de Espera**: Con prioridades y preferencias
- **Visualización**: Plano interactivo con estado en tiempo real
- **Tarifas**: Por hora, día o mes con comisiones configurables

---

## Tablas de Base de Datos

### `{prefix}_flavor_parkings`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `comunidad_id` | bigint | Comunidad asociada |
| `nombre` | varchar(255) | Nombre del parking |
| `descripcion` | text | Descripción |
| `direccion` | varchar(500) | Dirección completa |
| `latitud` | decimal(10,7) | Coordenada GPS |
| `longitud` | decimal(10,7) | Coordenada GPS |
| `tipo_parking` | enum | `subterraneo`, `superficie`, `cubierto`, `mixto` |
| `total_plazas` | int | Número total de plazas |
| `plazas_residentes` | int | Plazas para residentes |
| `plazas_visitantes` | int | Plazas para visitantes |
| `plazas_movilidad_reducida` | int | Plazas accesibles |
| `plazas_carga_electrica` | int | Plazas con cargador |
| `horario_apertura` | time | Hora apertura |
| `horario_cierre` | time | Hora cierre |
| `acceso_24h` | tinyint | Acceso 24 horas |
| `tipo_acceso` | enum | `mando`, `tarjeta`, `codigo`, `app`, `mixto` |
| `precio_hora_visitante` | decimal | Precio por hora |
| `precio_dia_visitante` | decimal | Precio por día |
| `cuota_mensual_residente` | decimal | Cuota mensual |
| `permite_rotacion` | tinyint | Permite sistema de rotación |
| `periodo_rotacion_dias` | int | Días de rotación |
| `estado` | enum | `activo`, `inactivo`, `mantenimiento` |

### `{prefix}_flavor_parkings_plazas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `parking_id` | bigint | Parking padre |
| `numero_plaza` | varchar(20) | Número identificador |
| `planta` | varchar(10) | Planta/nivel |
| `zona` | varchar(50) | Zona dentro del parking |
| `tipo_plaza` | enum | Ver tipos de plaza |
| `propietario_id` | bigint | Usuario propietario |
| `asignada_a` | bigint | Usuario asignado actual |
| `ancho_cm` | int | Ancho en centímetros |
| `largo_cm` | int | Largo en centímetros |
| `alto_maximo_cm` | int | Altura máxima |
| `tiene_columna` | tinyint | Tiene columna cerca |
| `tiene_cargador` | tinyint | Tiene cargador eléctrico |
| `tipo_cargador` | varchar(50) | Tipo de cargador |
| `potencia_cargador_kw` | decimal | Potencia en kW |
| `posicion_x` | int | Posición X en plano |
| `posicion_y` | int | Posición Y en plano |
| `rotacion` | int | Grados de rotación |
| `estado` | enum | Ver estados de plaza |
| `disponible_alquiler` | tinyint | Disponible para alquilar |
| `precio_hora` | decimal | Precio específico hora |
| `precio_dia` | decimal | Precio específico día |
| `precio_mes` | decimal | Precio específico mes |

### `{prefix}_flavor_parkings_reservas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `plaza_id` | bigint | Plaza reservada |
| `usuario_id` | bigint | Usuario que reserva |
| `tipo_reserva` | enum | `temporal`, `rotacion`, `evento`, `mantenimiento` |
| `fecha_inicio` | datetime | Inicio de reserva |
| `fecha_fin` | datetime | Fin de reserva |
| `hora_entrada` | datetime | Entrada real |
| `hora_salida` | datetime | Salida real |
| `matricula_vehiculo` | varchar(20) | Matrícula |
| `marca_vehiculo` | varchar(100) | Marca |
| `modelo_vehiculo` | varchar(100) | Modelo |
| `precio_total` | decimal | Precio calculado |
| `precio_pagado` | decimal | Precio pagado |
| `codigo_acceso` | varchar(20) | Código de acceso |
| `codigo_qr` | varchar(255) | QR de acceso |
| `estado` | enum | Ver estados de reserva |
| `penalizacion` | decimal | Penalización aplicada |
| `valoracion` | int | Valoración (1-5) |

### `{prefix}_flavor_parkings_asignaciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `plaza_id` | bigint | Plaza asignada |
| `usuario_id` | bigint | Usuario asignatario |
| `tipo_asignacion` | enum | `propietario`, `inquilino`, `rotacion`, `temporal` |
| `fecha_inicio` | date | Inicio de asignación |
| `fecha_fin` | date | Fin (null si indefinida) |
| `es_indefinida` | tinyint | Asignación permanente |
| `cuota_mensual` | decimal | Cuota a pagar |
| `dia_cobro` | int | Día del mes para cobro |
| `vehiculos_autorizados` | text | JSON array de matrículas |
| `mando_entregado` | tinyint | Se entregó mando |
| `numero_mando` | varchar(50) | Número de mando |
| `estado` | enum | `activa`, `pausada`, `finalizada`, `cancelada` |

### `{prefix}_flavor_parkings_lista_espera`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `parking_id` | bigint | Parking solicitado |
| `usuario_id` | bigint | Usuario en espera |
| `tipo_plaza_preferida` | enum | Preferencia de tipo |
| `planta_preferida` | varchar(10) | Planta preferida |
| `tiene_vehiculo_electrico` | tinyint | Necesita cargador |
| `necesita_movilidad_reducida` | tinyint | Necesita accesibilidad |
| `matricula` | varchar(20) | Matrícula del vehículo |
| `presupuesto_maximo` | decimal | Máximo a pagar |
| `urgencia` | enum | `baja`, `media`, `alta`, `urgente` |
| `posicion` | int | Posición en cola |
| `estado` | enum | `activo`, `pausado`, `atendido`, `cancelado`, `expirado` |

---

## Estados

### Estados de Plaza

| Estado | Descripción |
|--------|-------------|
| `libre` | Disponible para uso |
| `ocupada` | Actualmente en uso |
| `reservada` | Con reserva activa |
| `mantenimiento` | En mantenimiento |
| `bloqueada` | Bloqueada temporalmente |

### Estados de Reserva

| Estado | Descripción |
|--------|-------------|
| `pendiente` | Reserva solicitada |
| `confirmada` | Reserva confirmada |
| `activa` | Vehículo dentro |
| `completada` | Reserva finalizada |
| `cancelada` | Cancelada por usuario |
| `no_show` | No se presentó |

### Tipos de Plaza

- `normal` - Plaza estándar
- `grande` - Plaza grande
- `pequena` - Plaza pequeña
- `moto` - Para motocicletas
- `bici` - Para bicicletas
- `carga_electrica` - Con cargador eléctrico
- `movilidad_reducida` - Accesible

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[flavor_mapa_parkings]` | Plano visual del parking | `parking_id`, `altura`, `mostrar_leyenda`, `interactivo`, `mostrar_precios` |
| `[flavor_disponibilidad_parking]` | Calendario de disponibilidad | `parking_id`, `fecha`, `mostrar_calendario`, `dias_vista` |
| `[flavor_mis_reservas_parking]` | Mis reservas activas | - |
| `[flavor_solicitar_plaza]` | Formulario lista de espera | - |
| `[flavor_parking_grid]` | Grid de plazas para reservar | `parking_id` |
| `[flavor_parking_stats]` | Estadísticas del parking | `parking_id` |

### Alias de Shortcodes

| Alias | Equivalente |
|-------|-------------|
| `[parkings_mapa]` | `[flavor_mapa_parkings]` |
| `[parkings_reservar]` | `[flavor_parking_grid]` |
| `[parkings_mis_reservas]` | `[flavor_mis_reservas_parking]` |

### Ejemplo Mapa Visual

```
[flavor_mapa_parkings parking_id="1" altura="500" mostrar_leyenda="true" interactivo="true"]
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `disponible_app` | string | `cliente` | Disponibilidad en app móvil |
| `permite_reserva_temporal` | bool | true | Permitir reservas temporales |
| `permite_rotacion` | bool | true | Permitir sistema de rotación |
| `duracion_minima_reserva_horas` | int | 1 | Mínimo horas de reserva |
| `duracion_maxima_reserva_dias` | int | 30 | Máximo días de reserva |
| `anticipacion_maxima_dias` | int | 90 | Máxima anticipación |
| `precio_hora` | float | 1.50 | Precio por hora default |
| `precio_dia` | float | 10.00 | Precio por día default |
| `precio_mes` | float | 80.00 | Precio mensual default |
| `comision_plataforma` | int | 10 | % comisión plataforma |
| `max_reservas_activas_usuario` | int | 3 | Máximo reservas simultáneas |
| `tiempo_gracia_minutos` | int | 15 | Minutos de gracia |
| `penalizacion_no_show` | float | 5.00 | Penalización por no show |
| `notificar_liberacion` | bool | true | Notificar plazas liberadas |
| `notificar_lista_espera` | bool | true | Notificar cola de espera |
| `rotacion_activa` | bool | true | Sistema rotación activo |
| `periodo_rotacion_dias` | int | 30 | Días entre rotaciones |
| `max_lista_espera` | int | 50 | Máximo en lista espera |
| `requiere_matricula` | bool | true | Obligatoria matrícula |
| `permite_invitados` | bool | false | Permitir sin login |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `parkings_obtener_plaza` | Obtener datos de plaza | Público |
| `parkings_reservar_plaza` | Crear reserva | Usuario |
| `parkings_cancelar_reserva` | Cancelar reserva | Usuario |
| `parkings_solicitar_plaza` | Solicitar en lista espera | Usuario |
| `parkings_cancelar_solicitud` | Cancelar solicitud | Usuario |
| `parkings_obtener_disponibilidad` | Ver disponibilidad | Público |
| `parkings_liberar_plaza` | Liberar plaza ocupada | Usuario |
| `parkings_registrar_entrada` | Registrar entrada | Usuario |
| `parkings_registrar_salida` | Registrar salida | Usuario |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_parkings_check_rotacion` | Diario | Procesar rotación programada |
| `flavor_parkings_check_reservas_expiradas` | Cada hora | Procesar reservas expiradas |

---

## REST API

Base: `/wp-json/flavor-chat/v1/parkings/`

Localizada en frontend:
```javascript
flavorParkings.restUrl // URL base API REST
flavorParkings.nonce   // Nonce de seguridad
```

---

## Integración con App Móvil

```javascript
// Localización frontend
flavorParkings = {
    ajaxUrl: 'admin-ajax.php',
    restUrl: 'flavor-chat/v1/parkings/',
    nonce: 'xxx',
    userId: 123,
    isLoggedIn: true,
    strings: {
        loading: 'Cargando...',
        plazaLibre: 'Libre',
        plazaOcupada: 'Ocupada',
        // ...
    }
}
```

---

## Notas de Implementación

- Las plazas pueden tener posición visual (posicion_x, posicion_y) para mostrar en plano
- El sistema de rotación redistribuye plazas periódicamente
- Las penalizaciones se aplican por no-show
- Compatible con cargadores eléctricos de diferentes tipos
- Soporte para múltiples vehículos por usuario (matrículas JSON)
