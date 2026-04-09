# Documentación de Módulos - Flavor Platform

Este directorio contiene la documentación técnica detallada de cada módulo del plugin.

> **Estado**: 18 de 60+ módulos documentados (30%)
> **Última actualización**: 2026-03-19

## Índice de Módulos

### Módulos Documentados (18)

| Módulo | Categoría | Descripción |
|--------|-----------|-------------|
| [banco-tiempo](./banco-tiempo.md) | Economía | Intercambio de servicios por tiempo |
| [biblioteca](./biblioteca.md) | Formación | Préstamo de libros comunitarios |
| [comunidades](./comunidades.md) | Social | Comunidades y grupos virtuales |
| [cursos](./cursos.md) | Formación | Plataforma de formación online |
| [email-marketing](./email-marketing.md) | Marketing | Newsletters y campañas email |
| [encuestas](./encuestas.md) | Participación | Encuestas, formularios y quizzes |
| [espacios-comunes](./espacios-comunes.md) | Servicios | Reserva de espacios compartidos |
| [eventos](./eventos.md) | Social | Gestión de eventos y actividades |
| [foros](./foros.md) | Social | Foros de discusión comunitarios |
| [grupos-consumo](./grupos-consumo.md) | Economía | Grupos de consumo responsable |
| [incidencias](./incidencias.md) | Servicios | Tickets de soporte e incidencias |
| [marketplace](./marketplace.md) | Economía | Mercadillo local |
| [participacion](./participacion.md) | Gobernanza | Participación ciudadana |
| [presupuestos-participativos](./presupuestos-participativos.md) | Gobernanza | Presupuestos participativos |
| [reservas](./reservas.md) | Servicios | Sistema genérico de reservas |
| [socios](./socios.md) | Gestión | Gestión de membresías |
| [talleres](./talleres.md) | Formación | Talleres y workshops |
| [transparencia](./transparencia.md) | Gobernanza | Portal de transparencia |

### Módulos Pendientes de Documentar

#### Categoría: Economía Local
- advertising - Sistema de publicidad
- bares - Directorio de hostelería
- clientes - Gestión de clientes
- economia-don - Economía del don
- economia-suficiencia - Autogestión económica
- empresarial - Gestión empresarial
- facturas - Sistema de facturación
- trabajo-digno - Bolsa de empleo ético

#### Categoría: Movilidad
- bicicletas-compartidas - Bike sharing
- carpooling - Viajes compartidos
- parkings - Parking compartido

#### Categoría: Social y Comunicación
- avisos-municipales - Avisos oficiales
- chat-estados - Estados de usuario
- chat-grupos - Chat grupal
- chat-interno - Mensajería privada
- colectivos - Asociaciones y colectivos
- multimedia - Galería multimedia
- podcast - Podcasts comunitarios
- radio - Radio comunitaria
- red-social - Red social interna

#### Categoría: Ecología y Sostenibilidad
- biodiversidad-local - Especies locales
- compostaje - Compostaje comunitario
- huella-ecologica - Huella ecológica
- huertos-urbanos - Huertos urbanos
- reciclaje - Reciclaje y puntos limpios
- sello-conciencia - Certificaciones sostenibles

#### Categoría: Cuidados y Comunidad
- ayuda-vecinal - Ayuda entre vecinos
- circulos-cuidados - Círculos de apoyo mutuo
- justicia-restaurativa - Mediación comunitaria
- saberes-ancestrales - Conocimientos tradicionales

#### Categoría: Administración
- campanias - Campañas sociales
- documentacion-legal - Documentos legales
- fichaje-empleados - Control horario
- mapa-actores - Red de actores locales
- recetas - Recetario comunitario
- seguimiento-denuncias - Seguimiento de denuncias
- tramites - Trámites administrativos

---

## Estructura de Documentación

Cada archivo de módulo incluye:

| Sección | Contenido |
|---------|-----------|
| **Información General** | ID, versión, categoría, principios Gailu |
| **Descripción** | Propósito y funcionalidades principales |
| **Tablas BD** | Esquema de tablas con campos y tipos |
| **Endpoints API** | Endpoints REST con métodos y parámetros |
| **Shortcodes** | Shortcodes disponibles con atributos |
| **Configuración** | Opciones configurables del módulo |
| **Estados** | Estados posibles de las entidades |
| **Permisos** | Capabilities y roles requeridos |
| **Cron Jobs** | Tareas programadas |
| **Integraciones** | Relaciones con otros módulos |
| **Dashboard Tabs** | Pestañas del panel de usuario |
| **AJAX Actions** | Acciones AJAX disponibles |

## Categorías de Módulos

| Categoría | Módulos | Principio Gailu |
|-----------|---------|-----------------|
| **Economía** | banco-tiempo, grupos-consumo, marketplace... | Economía local |
| **Formación** | cursos, talleres, biblioteca | Aprendizaje |
| **Social** | eventos, foros, comunidades, red-social | Comunidad |
| **Gobernanza** | participacion, transparencia, presupuestos-participativos | Democracia |
| **Servicios** | reservas, espacios-comunes, incidencias | Cuidados |
| **Ecología** | huertos-urbanos, compostaje, reciclaje | Sostenibilidad |
| **Movilidad** | carpooling, bicicletas, parkings | Movilidad sostenible |

## Principios Gailu

Los módulos están alineados con los principios de la filosofía Gailu:

- **Economía local**: Fomentar economías circulares y de proximidad
- **Aprendizaje**: Compartir conocimientos y habilidades
- **Cuidados**: Atención mutua y bienestar comunitario
- **Sostenibilidad**: Reducir impacto ambiental
- **Democracia**: Participación y transparencia en decisiones
