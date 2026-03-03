# Reports

## Informe vigente

- [AUDITORIA-ESTADO-REAL-2026-03-01.md](./AUDITORIA-ESTADO-REAL-2026-03-01.md)
- [AUDITORIA-403-MODULOS-2026-03-01.md](./AUDITORIA-403-MODULOS-2026-03-01.md)
- [INFORME-CONSOLIDADO-CORRECCIONES-2026-03-02.md](./INFORME-CONSOLIDADO-CORRECCIONES-2026-03-02.md)
- [PLAN-REPARACION-MODULOS-2026-03-02.md](./PLAN-REPARACION-MODULOS-2026-03-02.md)
- [BACKLOG-OPERATIVO-MODULOS-2026-03-02.md](./BACKLOG-OPERATIVO-MODULOS-2026-03-02.md)
- [TANDA-1-AVANCE-2026-03-02.md](./TANDA-1-AVANCE-2026-03-02.md)
- [AUDITORIA-ASIDES-MODULOS-2026-03-02.md](./AUDITORIA-ASIDES-MODULOS-2026-03-02.md)
- [AUDITORIA-DASHBOARDS-2026-03-02.md](./AUDITORIA-DASHBOARDS-2026-03-02.md)
- [MATRIZ-FUNCIONALIDADES-PENDIENTES-2026-03-02.md](./MATRIZ-FUNCIONALIDADES-PENDIENTES-2026-03-02.md)
- [modulos_matriz_actual_2026-03-01.csv](./modulos_matriz_actual_2026-03-01.csv)

Este es el conjunto canonico para estado de modulos, completitud, integraciones, riesgos estructurales, correcciones aplicadas y puntos probables de 403/frontend.

El informe consolidado y el backlog ya reflejan tambien:

- refactorizacion del sistema comun de `aside`
- saneamiento amplio de `execute_action()` en multiples modulos
- mitigacion del warning global de regex grande en el portal
- cierre parcial de modulos incompletos como `foros`, `recetas`, `compostaje` y `marketplace`

## Politica de este directorio

- Los reportes fechados pueden conservarse como historico.
- Los reportes que contradicen el estado actual no deben citarse como referencia principal.
- Cuando un reporte quede superado, debe eliminarse o marcarse deprecado de forma explicita.

## Limpieza aplicada

Se han retirado los informes de auditoria de modulos de febrero que ya no coincidían con el codigo ni entre si.
