# Backlog Runtime De Modulos Parciales 2026-03-03

Este backlog baja la `MATRIZ-REVISION-60-MODULOS-2026-03-03.md` a una ejecucion concreta sobre los modulos que hoy siguen en estado `Parcial`.

## Objetivo

Subir modulos de `Parcial` a `Repasado intensivo` cuando cumplan:

- carga real en `mi-portal` o dashboard sin `500`, `403`, `404`
- tabs y widgets con contenido coherente
- formularios y acciones principales funcionando
- sin mensajes tecnicos al usuario
- sin rutas legacy rotas ni shortcodes impresos literalmente

## Bloqueo actual

En esta pasada no se pudo validar runtime desde CLI porque `127.0.0.1:10028` no responde.

Rutas probadas con fallo de conexion:

- `/mi-portal/facturas/`
- `/mi-portal/clientes/`
- `/mi-portal/socios/`
- `/mi-portal/bares/`
- `/mi-portal/email-marketing/`
- `/mi-portal/woocommerce/`
- `/mi-portal/fichaje-empleados/`
- `/mi-portal/sello-conciencia/`

## Tanda A. Parciales con mejor retorno

### Modulos

- `facturas`
- `clientes`
- `socios`
- `bares`

### Validacion minima

- cargar raiz del modulo
- revisar `aside` y tabs
- abrir accion principal de crear/ver/editar si existe
- revisar mensajes visibles y enlaces

### Criterio de promocion

- si no hay errores visibles y la accion principal responde, pasan a `Repasado intensivo`

## Tanda B. Parciales administrativos o de dashboard

### Modulos

- `email-marketing`
- `fichaje-empleados`
- `sello-conciencia`
- `clientes`

### Validacion minima

- dashboard tab
- widget
- subtabs `ver`, `editar`, `stats`, `detalle` cuando existan

### Criterio de promocion

- dashboard sin enlaces muertos
- formularios o vistas con backend real o degradacion honesta

## Tanda C. Parciales tecnicos o de entorno

### Modulos

- `woocommerce`
- `trading_ia`
- `dex_solana`
- `themacle`

### Validacion minima

- carga del modulo
- fallbacks admin/portal
- ausencia de fatales

### Criterio de promocion

- si el modulo no rompe y sus vistas base son coherentes, subir a `Repasado intensivo` solo si hay evidencia runtime

## Tanda D. Parciales de navegacion o contrato

### Modulos

- `bicicletas_compartidas`
- `carpooling`
- `huella_ecologica`
- `seguimiento_denuncias`
- `mapa_actores`

### Validacion minima

- raiz del modulo
- una accion secundaria
- enlaces del `aside`

### Criterio de promocion

- si ya estan saneados a nivel de contrato y la pantalla no rompe, pueden subir rapido

## Tanda E. Cierre especifico

### Modulos

- `sello_conciencia`
- `facturas`
- `clientes`

### Motivo

Son los `Parcial` con mejor retorno de calidad percibida porque exponen dashboards y flujos claros al usuario.

## Secuencia recomendada

1. `facturas`
2. `clientes`
3. `socios`
4. `bares`
5. `email-marketing`
6. `fichaje-empleados`
7. `sello-conciencia`
8. `woocommerce`
9. `themacle`
10. `bicicletas-compartidas`
11. `carpooling`
12. `huella-ecologica`
13. `seguimiento-denuncias`
14. `mapa-actores`
15. `trading-ia`
16. `dex-solana`

## Salida esperada

Al cerrar esta tanda deberia quedar:

- una nueva matriz con menos modulos `Parcial`
- evidencia runtime modulo por modulo
- lista corta de modulos que realmente dependen de entorno externo o que conviene congelar
