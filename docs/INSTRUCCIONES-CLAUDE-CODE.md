# Instrucciones para Claude Code: Creación de Sitios Comunitarios

Esta guía explica cómo dar instrucciones a Claude Code para crear y configurar sitios web para comunidades usando Flavor Platform.

## Índice

- [Información Mínima Necesaria](#información-mínima-necesaria)
- [Plantillas Predefinidas](#plantillas-predefinidas)
- [Catálogo de Módulos](#catálogo-de-módulos)
- [Ejemplos de Instrucciones](#ejemplos-de-instrucciones)
- [Capacidades Automáticas](#capacidades-automáticas)
- [Visual Builder Pro](#visual-builder-pro)
- [Flujo de Trabajo](#flujo-de-trabajo)
- [Referencia de APIs](#referencia-de-apis)

---

## Información Mínima Necesaria

Para crear un sitio, Claude Code necesita al menos:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **URL del sitio** | Dirección WordPress donde trabajar | `https://mi-comunidad.local` |
| **Tipo de comunidad** | Qué tipo de organización es | Cooperativa, asociación, vecindario |
| **Módulos principales** | Funcionalidades clave | grupos-consumo, eventos, socios |
| **Nombre** | Nombre de la organización | "Cooperativa Verde" |

### Información Opcional

- Tema de diseño (light, dark, eco, modern)
- Si incluir datos de demostración
- Idioma preferido
- Configuración SEO específica
- Páginas personalizadas adicionales

---

## Plantillas Predefinidas

Las plantillas preconfiguran módulos, páginas y menús para casos de uso comunes:

### Economía Colaborativa

| Plantilla | Descripción | Módulos Incluidos |
|-----------|-------------|-------------------|
| `grupos_consumo` | Cooperativas de consumo ecológico | grupos-consumo, socios, eventos, marketplace |
| `banco_tiempo` | Intercambio de tiempo y habilidades | banco-tiempo, socios, comunidades |
| `economia_solidaria` | Economía social y solidaria | marketplace, banco-tiempo, economia-don |

### Comunidades y Vecindarios

| Plantilla | Descripción | Módulos Incluidos |
|-----------|-------------|-------------------|
| `comunidad` | Comunidades vecinales generales | comunidades, participacion, eventos, foros |
| `barrio` | Gestión de barrio/vecindario | incidencias, avisos-municipales, participacion |
| `pueblo` | Municipios pequeños | transparencia, tramites, eventos, participacion |

### Asociaciones y Organizaciones

| Plantilla | Descripción | Módulos Incluidos |
|-----------|-------------|-------------------|
| `asociacion` | Asociaciones y ONGs | socios, eventos, transparencia, foros |
| `colectivo` | Colectivos y grupos de trabajo | colectivos, participacion, foros |
| `cooperativa` | Cooperativas de trabajo | socios, transparencia, presupuestos-participativos |

### Espacios y Servicios

| Plantilla | Descripción | Módulos Incluidos |
|-----------|-------------|-------------------|
| `coworking` | Espacios de trabajo compartido | reservas, espacios-comunes, eventos |
| `centro_cultural` | Centros culturales | eventos, talleres, biblioteca, multimedia |
| `hub_comunitario` | Hubs y centros comunitarios | espacios-comunes, eventos, cursos, reservas |

### Comercio

| Plantilla | Descripción | Módulos Incluidos |
|-----------|-------------|-------------------|
| `tienda` | Comercio local/online | marketplace, woocommerce |
| `mercado_local` | Mercados de productores | marketplace, grupos-consumo |

---

## Catálogo de Módulos

### Economía y Recursos

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `grupos_consumo` | Grupos de consumo | Pedidos, productores, productos, ciclos |
| `marketplace` | Mercado local | Anuncios, categorías, vendedores |
| `banco_tiempo` | Banco de tiempo | Intercambios, habilidades, saldos |
| `economia_don` | Economía del don | Ofrecimientos gratuitos |
| `economia_suficiencia` | Economía de suficiencia | Recursos compartidos |
| `crowdfunding` | Financiación colectiva | Campañas, donaciones |

### Participación y Gobernanza

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `participacion` | Participación ciudadana | Propuestas, votaciones |
| `presupuestos_participativos` | Presupuestos participativos | Proyectos, votación, seguimiento |
| `transparencia` | Portal de transparencia | Actas, presupuestos, contratos |
| `foros` | Foros de discusión | Categorías, temas, respuestas |
| `encuestas` | Encuestas y votaciones | Crear encuestas, resultados |
| `campanias` | Campañas de firmas | Recogida de firmas, seguimiento |

### Gestión de Miembros

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `socios` | Gestión de socios | Altas, cuotas, carnets |
| `comunidades` | Comunidades/grupos | Crear comunidades, miembros |
| `colectivos` | Colectivos de trabajo | Asambleas, proyectos |

### Actividades y Formación

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `eventos` | Eventos y calendario | Crear eventos, inscripciones |
| `cursos` | Cursos online | Lecciones, matrículas, certificados |
| `talleres` | Talleres presenciales | Inscripciones, materiales |

### Espacios y Recursos

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `reservas` | Sistema de reservas | Reservar recursos/espacios |
| `espacios_comunes` | Espacios compartidos | Gestión de espacios |
| `biblioteca` | Biblioteca comunitaria | Préstamos, catálogo |
| `huertos_urbanos` | Huertos urbanos | Parcelas, cultivos |

### Movilidad Sostenible

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `carpooling` | Compartir coche | Viajes, búsqueda |
| `bicicletas_compartidas` | Bicis compartidas | Estaciones, préstamos |
| `parkings` | Parkings comunitarios | Plazas, reservas |

### Comunicación

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `red_social` | Red social interna | Publicaciones, seguir |
| `chat_interno` | Mensajería | Conversaciones privadas |
| `chat_grupos` | Chat en grupos | Canales, menciones |
| `podcast` | Podcasts | Series, episodios |
| `radio` | Radio comunitaria | Programas, locutores |
| `email_marketing` | Email marketing | Listas, campañas |

### Incidencias y Soporte

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `incidencias` | Sistema de tickets | Crear, asignar, resolver |
| `avisos_municipales` | Avisos oficiales | Publicar avisos |
| `ayuda_vecinal` | Ayuda entre vecinos | Solicitudes de ayuda |
| `seguimiento_denuncias` | Seguimiento denuncias | Timeline, estados |

### Sostenibilidad

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `reciclaje` | Puntos de reciclaje | Mapa, campañas |
| `compostaje` | Compostaje comunitario | Solicitudes, puntos |
| `huella_ecologica` | Huella ecológica | Calculadora, tips |
| `biodiversidad_local` | Biodiversidad | Avistamientos, catálogo |
| `energia_comunitaria` | Energía comunitaria | Comunidades energéticas |

### Cultura y Conocimiento

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `multimedia` | Galería multimedia | Fotos, videos, álbumes |
| `saberes_ancestrales` | Saberes tradicionales | Documentar conocimientos |
| `documentacion_legal` | Documentación legal | Plantillas, guías |

### Otros

| Módulo | Descripción | Funcionalidades |
|--------|-------------|-----------------|
| `tramites` | Trámites online | Solicitudes, estados |
| `trabajo_digno` | Bolsa de empleo | Ofertas, candidatos |
| `sello_conciencia` | Sello de calidad | Certificaciones |
| `mapa_actores` | Mapa de actores | Organizaciones, relaciones |
| `agregador_contenido` | Agregador RSS/YouTube | Noticias externas, videos |

---

## Ejemplos de Instrucciones

### Ejemplo 1: Instrucción Completa

```
Crea un sitio para la Cooperativa de Consumo "EcoVerde" en https://ecoverde.local:

- Plantilla: grupos_consumo
- Módulos adicionales: biblioteca, talleres, banco-tiempo
- Tema: light-eco
- Incluir datos de demostración
- Crear menú principal con: Inicio, Productos, Productores, Eventos, Socios
- SEO: título "EcoVerde - Consumo Responsable", descripción "Cooperativa de consumo ecológico en Bilbao"
```

### Ejemplo 2: Instrucción Intermedia

```
Configura https://mi-barrio.local como comunidad vecinal con:
- Participación ciudadana
- Eventos del barrio
- Foros de discusión
- Sistema de incidencias
- Datos de demo para probar
```

### Ejemplo 3: Instrucción Mínima

```
Crea un sitio de asociación en https://asociacion.local con socios, eventos y transparencia
```

### Ejemplo 4: Personalización Avanzada

```
En https://hub-cultural.local necesito:

1. Módulos:
   - eventos (con inscripciones)
   - talleres (presenciales)
   - cursos (online)
   - biblioteca
   - multimedia (galería)
   - reservas (salas)

2. Páginas personalizadas:
   - Landing con carrusel de eventos destacados
   - Página "Nuestros espacios" con mapa y fotos
   - Página "Hazte socio" con formulario

3. Menú:
   - Inicio
   - Agenda (eventos + talleres)
   - Formación (cursos)
   - Espacios (reservas)
   - Biblioteca
   - Contacto

4. Sin datos de demo (es para producción)
```

### Ejemplo 5: Migración/Actualización

```
En el sitio existente https://cooperativa-actual.com:
- Activa el módulo de transparencia
- Crea la página "Portal de Transparencia" con el shortcode
- Añádela al menú principal
- Configura las categorías: Actas, Presupuestos, Contratos
```

---

## Capacidades Automáticas

Claude Code puede ejecutar automáticamente:

### Configuración del Sitio
- ✅ Establecer perfil de aplicación
- ✅ Activar/desactivar módulos
- ✅ Verificar dependencias entre módulos
- ✅ Aplicar temas de diseño

### Creación de Contenido
- ✅ Crear páginas con shortcodes apropiados
- ✅ Generar menús de navegación
- ✅ Importar datos de demostración
- ✅ Configurar widgets de dashboard

### Visual Builder Pro
- ✅ Crear landing pages con bloques visuales
- ✅ Diseñar secciones personalizadas
- ✅ Aplicar estilos y animaciones
- ✅ Crear templates para CPTs (singles dinámicos)

### SEO y Configuración
- ✅ Configurar título y descripción del sitio
- ✅ Generar meta tags para páginas
- ✅ Configurar URLs amigables

### Validación
- ✅ Verificar que módulos existen antes de activar
- ✅ Comprobar compatibilidad de configuración
- ✅ Reportar errores y advertencias

---

## Visual Builder Pro

Para páginas con diseño personalizado, Claude Code puede usar Visual Builder Pro:

### Bloques Disponibles (114+)

**Layout:**
- section, container, columns, grid, spacer, divider

**Contenido:**
- heading, text, rich-text, image, video, button, icon, list

**Navegación:**
- tabs, accordion, menu, breadcrumbs

**Datos:**
- posts-grid, posts-carousel, query-loop, dynamic-field

**Formularios:**
- form, input, select, checkbox, radio, submit

**Módulos:**
- module-shortcode, dashboard-widget, user-content

### Ejemplo de Instrucción VBP

```
Crea una landing page para la cooperativa con Visual Builder:

Sección Hero:
- Fondo con imagen de productos ecológicos
- Título grande: "Consume Local, Consume Consciente"
- Subtítulo con la misión
- Botón CTA: "Únete" que lleve a /hazte-socio

Sección Productos Destacados:
- Grid 3 columnas con productos del marketplace
- Cada producto con imagen, nombre y precio

Sección Cómo Funciona:
- 3 pasos con iconos: Inscríbete, Haz tu pedido, Recoge

Sección Testimonios:
- Carrusel con opiniones de socios

Footer:
- Contacto, redes sociales, mapa
```

---

## Flujo de Trabajo

### Flujo Típico

```
1. USUARIO: Describe el sitio que necesita
   ↓
2. CLAUDE: Consulta plantillas y módulos disponibles
   ↓
3. CLAUDE: Propone configuración detallada
   ↓
4. USUARIO: Confirma o ajusta
   ↓
5. CLAUDE: Ejecuta creación via APIs
   ↓
6. CLAUDE: Reporta resultados y URLs
```

### Comandos de Verificación

Durante el proceso, Claude Code puede:

```bash
# Ver módulos activos
GET /wp-json/flavor-site-builder/v1/modules

# Ver estado del sitio
GET /wp-json/flavor-site-builder/v1/site/status

# Validar configuración
POST /wp-json/flavor-site-builder/v1/site/validate

# Verificar salud del sistema
GET /wp-json/flavor-site-builder/v1/system/health
```

---

## Referencia de APIs

### Endpoints Principales

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor-site-builder/v1/templates` | GET | Lista plantillas |
| `/flavor-site-builder/v1/modules` | GET | Lista módulos |
| `/flavor-site-builder/v1/site/create` | POST | Crea sitio completo |
| `/flavor-site-builder/v1/site/validate` | POST | Valida configuración |
| `/flavor-site-builder/v1/modules/activate` | POST | Activa módulos |
| `/flavor-site-builder/v1/pages/create-for-modules` | POST | Crea páginas |
| `/flavor-site-builder/v1/menu` | POST | Crea menú |

### Autenticación

Las APIs requieren autenticación con header:
```
X-VBP-Key: <API_KEY>
```

### Documentación Completa

- `docs/api/CLAUDE-API-GUIDE.md` - Guía completa de APIs
- `docs/api/ENDPOINTS-REFERENCE.md` - Referencia de endpoints
- `docs/api/WORKFLOW-CREAR-SITIO.md` - Tutorial paso a paso

---

## Tips y Mejores Prácticas

### ✅ Buenas Prácticas

1. **Especifica el tipo de comunidad** - Ayuda a elegir la plantilla correcta
2. **Indica si es desarrollo o producción** - Afecta si importar demos
3. **Menciona el idioma** - Para configurar correctamente
4. **Lista los módulos prioritarios** - Los más importantes primero

### ❌ Evitar

1. Descripciones vagas sin contexto
2. Pedir muchos módulos sin priorizar
3. No indicar la URL del sitio
4. Asumir que conozco el contexto previo

### 💡 Consejos

- Puedes pedir **ver las opciones** antes de decidir
- Puedes **modificar** después de crear
- Los **datos de demo** se pueden borrar luego
- Cada módulo tiene **documentación** en `docs/modulos/`

---

## Changelog

| Fecha | Cambios |
|-------|---------|
| 2024-03-20 | Versión inicial |
