# Integracion Contabilidad (Transversal)

El modulo `contabilidad` permite que cualquier modulo registre movimientos contables sin acoplamiento directo.

## Hook principal

```php
do_action('flavor_contabilidad_registrar_movimiento', [
  'fecha_movimiento'  => '2026-03-12',
  'tipo_movimiento'   => 'ingreso', // ingreso|gasto|ajuste
  'estado'            => 'confirmado', // borrador|confirmado|anulado
  'concepto'          => 'Cobro servicio X',
  'categoria'         => 'servicios',
  'subcategoria'      => 'premium',
  'modulo_origen'     => 'mi_modulo',
  'entidad_tipo'      => 'pedido',
  'entidad_id'        => 123,
  'referencia_tipo'   => 'transaccion',
  'referencia_id'     => 456,
  'tercero_tipo'      => 'cliente',
  'tercero_id'        => 77,
  'tercero_nombre'    => 'Cliente Demo',
  'base_imponible'    => 100.00,
  'iva_porcentaje'    => 21,
  'iva_importe'       => 21.00,
  'retencion_porcentaje' => 0,
  'retencion_importe' => 0,
  'total'             => 121.00,
  'clave_unica'       => 'mi_modulo_tx_456',
  'metadata'          => [
    'canal' => 'web',
  ],
]);
```

## Reglas recomendadas

- Usa `clave_unica` para evitar duplicados (idempotencia).
- Registra en `estado=borrador` cuando el ingreso/gasto no esta confirmado.
- Pasa `modulo_origen` siempre para desglose por modulo.
- Usa `referencia_tipo/referencia_id` para trazabilidad.

## Integraciones ya activas

- Facturas: emision (`borrador`) y cobro (`confirmado`).
- Socios: cuota pagada (`confirmado`).
