# Documentacion Tecnica

Este directorio ya no debe usarse como fuente de estado historico del proyecto. La referencia vigente para auditoria de modulos y consistencia del sistema es:

- [../reports/AUDITORIA-ESTADO-REAL-2026-03-01.md](../reports/AUDITORIA-ESTADO-REAL-2026-03-01.md)

## Documentos vigentes por uso

### Estado real y auditoria

- [../reports/AUDITORIA-ESTADO-REAL-2026-03-01.md](../reports/AUDITORIA-ESTADO-REAL-2026-03-01.md)

### Arquitectura y desarrollo

- [ARQUITECTURA-MODULOS.md](ARQUITECTURA-MODULOS.md)
- [ESTANDARES-MODULOS.md](ESTANDARES-MODULOS.md)
- [GUIA_MODULOS.md](GUIA_MODULOS.md)
- [REFERENCIA_RAPIDA_MODULOS.md](REFERENCIA_RAPIDA_MODULOS.md)
- [INTEGRACIONES.md](INTEGRACIONES.md)
- [FUNCIONALIDADES-COMPARTIDAS.md](FUNCIONALIDADES-COMPARTIDAS.md)

### Operacion y verificacion

- [CHECKLIST-VERIFICACION.md](CHECKLIST-VERIFICACION.md)
- [PLAN-PRUEBAS.md](PLAN-PRUEBAS.md)
- [CHECKLIST-RELEASE.md](CHECKLIST-RELEASE.md)

## Criterio de limpieza aplicado

Se han retirado de la ruta principal de consulta los documentos de auditoria que:

- daban recuentos de modulos incompatibles entre si
- declaraban versiones contradictorias del sistema
- describian como actual un estado que ya no coincide con el codigo

Si se necesita conservar mas historico, debe mantenerse en `reports/` con marca explicita de obsoleto, no como documentacion canonica.
