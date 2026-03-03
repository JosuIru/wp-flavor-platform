# Tanda 1 - Avance 2026-03-02

## Modulos de la tanda

- `comunidades`
- `red-social`
- `grupos-consumo`
- `reservas`
- `tramites`
- `participacion`
- `espacios-comunes`

## Avance actual

### Corregido en esta tanda

- `comunidades`
  - contratos JS/PHP alineados
  - nonces y formularios compatibles
  - capa federada alineada con `flavor_network_nodes`
  - compatibilidad global para acciones `flavorComunidades.unirse/compartir`
  - flujo legacy adaptado al esquema actual base
- `red-social`
  - dashboard usa shortcodes y assets reales del modulo
- `grupos-consumo`
  - suscripciones y nonces saneados
  - backend mas robusto para consumidor, cesta y suscripcion
- `reservas`
  - handlers frontend y modulo principal alineados
  - calendario y disponibilidad corregidos
- `tramites`
  - dashboard y JS principal alineados
- `participacion`
  - config localizada de JS alineada con frontend real
- `espacios-comunes`
  - config localizada de JS alineada
  - blindaje runtime aplicado al modulo

## Riesgos que siguen vivos

- `comunidades` todavia conserva deuda legacy estructural, aunque ya no depende del esquema antiguo principal
- `grupos-consumo`, `reservas`, `tramites`, `participacion` y `espacios-comunes` requieren validacion runtime real en navegador
- `espacios-comunes` tiene mucha logica AJAX incrustada en vistas admin y frontend, con superficie de error alta

## Estado de validacion runtime

- la instancia `sitio-prueba` responde correctamente por HTTP en `http://localhost:10028` aunque Local la muestre desalineada en estado
- las rutas principales de `Tanda 1` responden `200 OK`:
  - `/mi-portal/comunidades/`
  - `/mi-portal/red-social/`
  - `/mi-portal/grupos-consumo/suscripciones/`
  - `/mi-portal/reservas/`
  - `/mi-portal/tramites/`
  - `/mi-portal/participacion/`
  - `/mi-portal/espacios-comunes/`
- se elimino la contaminacion visible de HTML por notices `_load_textdomain_just_in_time`
- se elimino la fuga de `gcFrontend` hacia paginas de otros modulos del portal
- `grupos-consumo/suscripciones` queda con una sola definicion de `gcFrontend`
- `debug.log` reciente no muestra un fatal nuevo atribuible a `Tanda 1`

## Cierre pendiente de Tanda 1

- ejecutar validacion runtime real por modulo
- registrar errores reproducibles restantes
- corregir solo fallos vivos
- actualizar matriz de salud final de la tanda
