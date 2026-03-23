# Módulo: Facturas

> Sistema completo de facturación para servicios comunitarios

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `facturas` |
| **Versión** | 2.0.0+ |
| **Categoría** | Gestión / Economía |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

Sistema completo de facturación con generación de PDF, gestión de pagos, recordatorios automáticos y configuración de datos fiscales.

### Características Principales

- **Facturación Completa**: Crear, editar, cancelar facturas
- **Generación PDF**: Facturas en formato PDF
- **Gestión de Pagos**: Registro de cobros parciales/totales
- **Series**: Múltiples series de numeración
- **Recordatorios**: Emails automáticos de vencimiento
- **Integración CRM**: Vincular con módulo de clientes
- **Retenciones**: Soporte IRPF y otras retenciones

---

## Tablas de Base de Datos

### `{prefix}_flavor_facturas`

Tabla principal de facturas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `numero` | varchar(50) | Número de factura |
| `serie` | varchar(10) | Serie de facturación |
| `cliente_nombre` | varchar(255) | Nombre del cliente |
| `cliente_nif` | varchar(20) | NIF/CIF |
| `cliente_email` | varchar(100) | Email cliente |
| `cliente_direccion` | text | Dirección |
| `cliente_ref_id` | bigint | ID referencia (CRM) |
| `cliente_ref_tipo` | varchar(50) | Tipo de referencia |
| `fecha_emision` | date | Fecha de emisión |
| `fecha_vencimiento` | date | Fecha de vencimiento |
| `base_imponible` | decimal(12,2) | Base imponible |
| `iva` | decimal(12,2) | IVA total |
| `retenciones` | decimal(12,2) | Retenciones |
| `total` | decimal(12,2) | Total factura |
| `estado` | enum | `pendiente`, `pagada`, `parcial`, `cancelada` |
| `observaciones` | text | Observaciones públicas |
| `notas_internas` | text | Notas internas |
| `empresa_id` | bigint | ID de empresa emisora |
| `created_at` | datetime | Fecha de creación |

### `{prefix}_flavor_facturas_lineas`

Líneas de cada factura.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `factura_id` | bigint | Factura padre |
| `concepto` | varchar(255) | Concepto |
| `descripcion` | text | Descripción |
| `cantidad` | decimal(10,4) | Cantidad |
| `precio_unitario` | decimal(12,4) | Precio unitario |
| `iva_porcentaje` | decimal(5,2) | % IVA |
| `descuento` | decimal(5,2) | % Descuento |
| `total_linea` | decimal(12,2) | Total línea |

### `{prefix}_flavor_facturas_pagos`

Pagos registrados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `factura_id` | bigint | Factura |
| `cantidad` | decimal(12,2) | Cantidad pagada |
| `fecha` | date | Fecha de pago |
| `metodo` | varchar(50) | Método de pago |
| `referencia` | varchar(100) | Referencia |
| `notas` | text | Notas del pago |

### `{prefix}_flavor_facturas_series`

Series de facturación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `prefijo` | varchar(10) | Prefijo (F, R, etc.) |
| `nombre` | varchar(100) | Nombre de la serie |
| `ultimo_numero` | int | Último número usado |
| `año` | int | Año de la serie |
| `activa` | bool | Serie activa |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `serie_predeterminada` | string | F | Serie por defecto |
| `numeracion_inicial` | int | 1 | Número inicial |
| `iva_predeterminado` | int | 21 | % IVA por defecto |
| `requiere_aprobacion` | bool | false | Aprobación previa |
| `enviar_email_automatico` | bool | true | Email al crear |
| `formato_numero` | string | {SERIE}-{YEAR}-{NUM} | Formato numeración |
| `dias_vencimiento` | int | 30 | Días hasta vencimiento |
| `moneda` | string | EUR | Moneda |
| `simbolo_moneda` | string | € | Símbolo |
| `decimales` | int | 2 | Decimales |
| `empresa_nombre` | string | - | Nombre empresa |
| `empresa_nif` | string | - | NIF empresa |
| `empresa_direccion` | string | - | Dirección empresa |
| `empresa_email` | string | - | Email empresa |
| `empresa_telefono` | string | - | Teléfono empresa |
| `empresa_logo` | string | - | URL del logo |
| `cuenta_bancaria` | string | - | IBAN para pagos |
| `pie_factura` | string | - | Texto pie de factura |
| `enviar_recordatorios` | bool | true | Enviar recordatorios |
| `dias_recordatorio` | array | [7,3,1] | Días antes de vencer |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `flavor_facturas_listar` | Listar facturas | Admin |
| `flavor_facturas_crear` | Crear factura | Admin |
| `flavor_facturas_generar_pdf` | Generar PDF | Admin |
| `flavor_facturas_registrar_pago` | Registrar pago | Admin |
| `flavor_facturas_enviar_email` | Enviar por email | Admin |
| `flavor_facturas_cancelar` | Cancelar factura | Admin |
| `flavor_facturas_estadisticas` | Obtener stats | Admin |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_facturas_enviar_recordatorios` | Diario | Enviar recordatorios de vencimiento |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `facturas-dashboard` | Panel con estadísticas |
| Listado | `facturas-listado` | Todas las facturas |
| Nueva | `facturas-nueva` | Crear factura |
| Configuración | `facturas-config` | Configuración |

---

## Integración con Clientes CRM

El módulo se integra con el módulo de Clientes:

- Selector de clientes CRM al crear factura
- Pre-relleno automático de datos
- Vinculación mediante `cliente_ref_id` y `cliente_ref_tipo`
- También soporta: productores (grupos_consumo), usuarios WP

---

## Flujo de Trabajo

```
1. CREAR FACTURA
   Rellenar datos cliente y líneas
   Estado: pendiente
   ↓
2. GENERAR PDF
   Generar documento PDF
   Guardar o enviar por email
   ↓
3. ENVIAR
   Email automático al cliente
   Con PDF adjunto
   ↓
4. RECORDATORIOS
   Emails automáticos según días_recordatorio
   Antes de vencimiento
   ↓
5. REGISTRAR PAGO
   Pago parcial o total
   Estado: parcial o pagada
   ↓
6. (OPCIONAL) CANCELAR
   Anular factura
   Estado: cancelada
```

---

## Permisos

| Acción | Capability |
|--------|------------|
| Ver facturas | `manage_options` |
| Crear facturas | `manage_options` |
| Editar facturas | `manage_options` |
| Cancelar facturas | `manage_options` |
| Configurar | `manage_options` |

---

## Notas de Implementación

- Generación PDF con biblioteca compatible WordPress
- El logo de empresa se incluye en el PDF
- Retenciones: ninguna, IRPF 15%, IRPF 7%
- Formato de número configurable con placeholders
- Los pagos parciales actualizan estado a "parcial"
- Compatible con multi-empresa vía `empresa_id`
