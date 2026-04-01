# Matriz de Contextos de Modulos para Dashboards

Fecha: 2026-03-03

## Objetivo

Dejar una referencia explicita y breve de los contextos ya declarados en modulos clave.

Esta matriz sirve para:

- revisar coherencia
- evitar duplicidades
- extender nuevos modulos sin improvisar
- contrastar rapidamente `client_contexts` y `admin_contexts`

## Convencion de lectura

- `client_contexts`: prioridad en `mi-panel`, portal y lectura de perfil de usuario
- `admin_contexts`: prioridad en backend y dashboard unificado
- Si `admin_contexts` no existe, el admin puede caer a `client_contexts`

## Matriz actual

| Modulo | Rol | client_contexts | admin_contexts |
| --- | --- | --- | --- |
| `comunidades` | `base` | `comunidad`, `miembro`, `coordinacion` | `comunidad`, `coordinacion`, `admin` |
| `socios` | `base` | `socios`, `membresia`, `cuenta`, `comunidad` | `socios`, `membresia`, `admin` |
| `colectivos` | `base` | `colectivos`, `asociacion`, `gobernanza`, `comunidad` | `colectivos`, `gobernanza`, `admin` |
| `energia_comunitaria` | `vertical` | `comunidad`, `energia`, `gestion` | `energia`, `gestion`, `admin` |
| `grupos_consumo` | `vertical` | `consumo`, `comunidad`, `coordinacion` | `consumo`, `gestion`, `admin` |
| `banco_tiempo` | `vertical` | `cuidados`, `intercambio`, `comunidad` | `cuidados`, `gestion`, `admin` |
| `ayuda_vecinal` | `vertical` | `cuidados`, `comunidad`, `solidaridad` | `cuidados`, `solidaridad`, `admin` |
| `eventos` | `vertical` | `eventos`, `agenda`, `actividad`, `comunidad` | `eventos`, `agenda`, `admin` |
| `participacion` | `transversal` | `participacion`, `gobernanza`, `comunidad` | `gobernanza`, `participacion`, `admin` |
| `transparencia` | `transversal` | `transparencia`, `gobernanza`, `rendicion_cuentas` | `transparencia`, `gobernanza`, `admin` |
| `huella_ecologica` | `transversal` | `impacto`, `sostenibilidad`, `energia`, `consumo` | `impacto`, `sostenibilidad`, `admin` |
| `economia_suficiencia` | `transversal` | `consumo`, `suficiencia`, `aprendizaje`, `comunidad` | `consumo`, `aprendizaje`, `admin` |
| `saberes_ancestrales` | `transversal` | `aprendizaje`, `comunidad`, `cultura`, `saberes` | `aprendizaje`, `cultura`, `admin` |

## Lectura del mapa

### Bases

- `comunidades` sigue siendo la base mas fuerte para ecosistemas territoriales y de vida compartida
- `socios` cubre mejor membresia y relacion formal con la organizacion
- `colectivos` cubre coordinacion organizativa y gobernanza asociativa

### Verticales

- `energia_comunitaria`, `grupos_consumo`, `banco_tiempo`, `ayuda_vecinal` y `eventos` ya tienen posicion clara dentro de la base `comunidades`
- en admin todos ellos convergen a `gestion` o al contexto operativo dominante

### Transversales

- `participacion` y `transparencia` quedan centrados en gobernanza
- `huella_ecologica` queda centrada en impacto y sostenibilidad
- `economia_suficiencia` y `saberes_ancestrales` quedan centrados en aprendizaje y transformacion cultural

## Reglas observadas en esta matriz

- los modulos `base` no necesitan muchos contextos; deben ser pocos y estructurales
- los `verticales` deben mezclar un contexto operativo y uno relacional
- los `transversales` deben evitar contextos demasiado genericos y centrarse en su aporte principal
- `admin` aparece como contexto tecnico comun en backend, pero no deberia usarse solo

## Huecos pendientes

Conviene revisar y declarar tambien:

- `presupuestos_participativos`
- `talleres`
- `cursos`
- `marketplace`
- `reservas`
- `incidencias`
- `red_social`

## Relacion con otros documentos

- Complementa `GUIA-CONTEXTOS-DASHBOARD-MODULOS.md`
- Complementa `ESTANDAR-METADATOS-ECOSISTEMA-MODULOS.md`
- Sirve como foto concreta del estado que describe `ARQUITECTURA-DASHBOARDS-ECOSISTEMA.md`
