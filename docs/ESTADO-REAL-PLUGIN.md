# Estado Real del Plugin

## Referencia temporal

Este documento resume el estado que puede afirmarse con seguridad a partir de:

- el arbol de codigo actual del plugin
- la auditoria `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`

## Navegacion rapida

- [Lo que si puede afirmarse](#lo-que-si-puede-afirmarse)
- [Lo que no puede afirmarse automaticamente](#lo-que-no-puede-afirmarse-automaticamente)
- [Riesgos operativos mas importantes](#riesgos-operativos-mas-importantes)
- [Madurez general](#madurez-general)
- [Modulos con mejor posicionamiento segun auditoria](#modulos-con-mejor-posicionamiento-segun-auditoria)
- [Como trabajar con este estado](#como-trabajar-con-este-estado)

## Lectura ejecutiva

| Punto | Lectura actual |
|---|---|
| Estado general | Utilizable, pero en fase activa de reestructuracion |
| Base tecnica | Amplia y real, no conceptual |
| Riesgo principal | Coherencia interna y cierre de validacion runtime |
| Fuente principal | `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md` |
| Regla operativa | Validar por flujo antes de prometer alcance |

## Lo que si puede afirmarse

### 1. El plugin tiene una base amplia y utilizable

No estamos ante un esqueleto vacio. Hay:

- 60 modulos con clase principal segun la auditoria vigente
- varias capas de administracion
- APIs, widgets, tabs y frontends reales
- addons presentes en el arbol

Como dato estructural adicional, el loader builtin registra 60 IDs de modulo, pero esa no es la cifra que debe usarse para hablar de estado real o completitud.

### 2. El problema principal es la consistencia

La deuda mas repetida no es ausencia total de codigo, sino desalineacion entre:

- documentacion historica
- bootstrap
- tablas
- integraciones
- patrones de implementacion

### 3. Existen modulos maduros y modulos parciales

No debe hablarse del sistema como si todo tuviera la misma calidad. Hay verticales bien resueltas y otras que siguen necesitando validacion o refactor.

## Lo que no puede afirmarse automaticamente

No debe prometerse sin prueba directa que:

- todos los modulos funcionan en runtime en esta instalacion local
- todas las tablas estan presentes y alineadas
- todas las rutas frontend estan libres de errores de permisos o assets

La auditoria vigente sigue siendo principalmente estatica y mantiene limites de validacion runtime de la instancia local.

## Riesgos operativos mas importantes

- bootstrap central grande y dificil de razonar
- instalacion de tablas repartida en varias capas
- documentacion antigua con cifras contradictorias
- patrones frontend y formularios no uniformes
- posibilidad de que un modulo roto afecte partes amplias del portal

## Madurez general

La lectura correcta del estado es:

- utilizable para trabajo real
- con deuda estructural relevante
- con necesidad de validar por flujo antes de cerrar alcance comercial o funcional
- con una matriz vigente de `60` modulos que separa `43` revisados en profundidad, `16` parciales y `1` fuera del foco principal

## Señales practicas para decidir si un flujo esta listo

- El modulo carga sin errores visibles en el contexto real.
- La accion principal responde sin fatales ni mensajes tecnicos.
- Los permisos del flujo estan claros.
- Los enlaces, tabs o widgets no dependen de rutas legacy rotas.
- Existe evidencia minima de runtime, no solo codigo o documentacion.

## Modulos con mejor posicionamiento segun auditoria

La auditoria y la matriz vigente identifican como bloque mas fuerte, entre otros, a:

- `banco_tiempo`
- `biblioteca`
- `chat_estados`
- `cursos`
- `eventos`
- `grupos_consumo`
- `huertos_urbanos`
- `incidencias`
- `marketplace`
- `participacion`
- `presupuestos_participativos`
- `reciclaje`
- `reservas`
- `talleres`
- `tramites`

Tambien deja claro que hay modulos que siguen pidiendo una tanda especifica de verificacion o refactor:

- `bares`
- `clientes`
- `facturas`
- `trading_ia`
- `themacle`
- `email_marketing`

## Como trabajar con este estado

- usa esta documentacion como mapa principal
- contrasta modulos criticos con el catalogo y el codigo
- valida runtime en la instalacion concreta
- evita usar documentos viejos como canon si contradicen el arbol actual

## Documentos de apoyo

- `PLUGIN-COMPLETO.md`
- `GUIA_MODULOS.md`
- `CATALOGO-MODULOS.md`
- `reports/MATRIZ-REVISION-60-MODULOS-2026-03-03.md`
- `reports/AUDITORIA-ESTADO-REAL-2026-03-04.md`
