# Reports

## Informe vigente

- [PLAN-CIERRE-PRODUCCION-2026-03-04.md](./PLAN-CIERRE-PRODUCCION-2026-03-04.md)
- [BACKLOG-PRIORIZADO-PRODUCCION-2026-03-04.md](./BACKLOG-PRIORIZADO-PRODUCCION-2026-03-04.md)
- [CHECKLIST-PRODUCCION-PLUGIN-2026-03-04.md](./CHECKLIST-PRODUCCION-PLUGIN-2026-03-04.md)
- [MATRIZ-CIERRE-MODULOS-2026-03-04.csv](./MATRIZ-CIERRE-MODULOS-2026-03-04.csv)

- [AUDITORIA-ESTADO-REAL-2026-03-04.md](./AUDITORIA-ESTADO-REAL-2026-03-04.md)
- [INFORME-TECNICO-SESION-RUNTIME-2026-03-04.md](./INFORME-TECNICO-SESION-RUNTIME-2026-03-04.md)
- [TANDA-1-CIERRE-PRODUCCION-2026-03-04.md](./TANDA-1-CIERRE-PRODUCCION-2026-03-04.md)
- [AUDITORIA-ESTADO-REAL-2026-03-01.md](./AUDITORIA-ESTADO-REAL-2026-03-01.md)
- [AUDITORIA-403-MODULOS-2026-03-01.md](./AUDITORIA-403-MODULOS-2026-03-01.md)
- [INFORME-CONSOLIDADO-CORRECCIONES-2026-03-02.md](./INFORME-CONSOLIDADO-CORRECCIONES-2026-03-02.md)
- [PLAN-REPARACION-MODULOS-2026-03-02.md](./PLAN-REPARACION-MODULOS-2026-03-02.md)
- [BACKLOG-OPERATIVO-MODULOS-2026-03-02.md](./BACKLOG-OPERATIVO-MODULOS-2026-03-02.md)
- [TANDA-1-AVANCE-2026-03-02.md](./TANDA-1-AVANCE-2026-03-02.md)
- [AUDITORIA-ASIDES-MODULOS-2026-03-02.md](./AUDITORIA-ASIDES-MODULOS-2026-03-02.md)
- [AUDITORIA-DASHBOARDS-2026-03-02.md](./AUDITORIA-DASHBOARDS-2026-03-02.md)
- [AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.md](./AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.md)
- [AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.csv](./AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.csv)
- [RECLASIFICACION-FUNCIONAL-DASHBOARDS-ADMIN-2026-03-04.md](./RECLASIFICACION-FUNCIONAL-DASHBOARDS-ADMIN-2026-03-04.md)
- [AUDITORIA-GESTOR-DASHBOARDS-2026-03-04.md](./AUDITORIA-GESTOR-DASHBOARDS-2026-03-04.md)
- [BACKLOG-DASHBOARDS-ADMIN-MEDIO-2026-03-04.md](./BACKLOG-DASHBOARDS-ADMIN-MEDIO-2026-03-04.md)
- [MATRIZ-FUNCIONALIDADES-PENDIENTES-2026-03-02.md](./MATRIZ-FUNCIONALIDADES-PENDIENTES-2026-03-02.md)
- [MATRIZ-REVISION-60-MODULOS-2026-03-03.md](./MATRIZ-REVISION-60-MODULOS-2026-03-03.md)
- [BACKLOG-RUNTIME-MODULOS-PARCIALES-2026-03-03.md](./BACKLOG-RUNTIME-MODULOS-PARCIALES-2026-03-03.md)
- [modulos_matriz_actual_2026-03-01.csv](./modulos_matriz_actual_2026-03-01.csv)

El informe de estado real prioritario pasa a ser el de `2026-03-04`. El de `2026-03-01` se mantiene como linea base historica.

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
